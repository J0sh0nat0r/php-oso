<?php

namespace J0sh0nat0r\Oso;

use InvalidArgumentException;
use J0sh0nat0r\Oso\DataFiltering\Adapter;
use J0sh0nat0r\Oso\DataFiltering\Filter;
use J0sh0nat0r\Oso\Exceptions\DataFilteringConfigurationException;
use J0sh0nat0r\Oso\Exceptions\DuplicateClassAliasException;
use J0sh0nat0r\Oso\Exceptions\InstantiationException;
use J0sh0nat0r\Oso\Exceptions\InvalidFieldNameException;
use J0sh0nat0r\Oso\Exceptions\OsoException;
use J0sh0nat0r\Oso\Exceptions\UnexpectedExpressionException;
use J0sh0nat0r\Oso\Exceptions\UnregisteredClassException;
use J0sh0nat0r\Oso\Exceptions\UnregisteredInstanceException;
use J0sh0nat0r\Oso\FFI\Polar;
use JetBrains\PhpStorm\ArrayShape;
use ReflectionException;
use stdClass;

/**
 * @template TQuery
 * @template TResource
 */
class Host
{
    /**
     * @var Adapter<TQuery, TResource>
     */
    public Adapter $adapter;

    /**
     * Maps: class alias -> UserType.
     */
    protected TypeMap $types;

    /**
     * Maps: class id -> class instance.
     *
     * @var array<int, mixed>
     */
    protected array $instances = [];

    protected bool $acceptExpression = false;

    public function __construct(protected Polar $ffiPolar)
    {
        $this->adapter = new class() implements Adapter {
            public function buildQuery(Filter $filter): void
            {
                throw new DataFilteringConfigurationException();
            }

            public function executeQuery($query): iterable
            {
                throw new DataFilteringConfigurationException();
            }
        };

        $this->types = new TypeMap();
    }

    public function setAcceptExpression(bool $acceptExpression): void
    {
        $this->acceptExpression = $acceptExpression;
    }

    public function getTypes(): TypeMap
    {
        return $this->types;
    }

    public function getType(string|ClassType $class): ?UserType
    {
        return $this->types[$class] ?? null;
    }

    /**
     * @throws ReflectionException
     */
    public function serializeTypes(): array
    {
        $polarTypes = [];

        foreach ($this->distinctUserTypes() as $type) {
            $fieldTypes = [];

            foreach ($type->fields as $name => $value) {
                if ($value instanceof Relation) {
                    $fieldTypes[$name] = $value;
                } else {
                    $classType = ClassType::fromName($value);

                    $classTag = $this->getType($classType)?->name;

                    if ($classTag === null) {
                        throw new UnregisteredClassException($classType->getName());
                    }

                    $fieldTypes[$name] = [
                        'Base' => [
                            'class_tag' => $classTag,
                        ],
                    ];
                }
            }

            $polarTypes[$type->name] = (object) $fieldTypes;
        }

        return $polarTypes;
    }

    /**
     * @param array<string, string|Relation> $fields
     */
    public function cacheClass(ClassType $class, ?string $name, array $fields): string
    {
        $name ??= array_slice(explode('\\', $class->getName()), -1)[0];

        $existing = $this->types[$name] ?? null;

        if ($existing !== null) {
            throw new DuplicateClassAliasException($name, $class, $existing);
        }

        $userType = new UserType(
            $name,
            $class,
            $this->cacheInstance($class),
            $fields,
        );

        $this->types[$name] = $this->types[$class] = $userType;

        return $name;
    }

    public function registerMros(): void
    {
        foreach ($this->distinctUserTypes() as $type) {
            if ($type->classType->isPrimitive()) {
                continue;
            }

            $parent = $type->classType->getParentType();
            $mro = [];

            while ($parent !== false) {
                $userType = $this->getType($parent);

                if ($userType !== null) {
                    $mro[] = $userType->id;
                }

                $parent = $parent->getParentType();
            }

            $this->ffiPolar->registerMro($type->name, $mro);
        }
    }

    public function getInstance(int $instanceId)
    {
        if ($this->hasInstance($instanceId)) {
            return $this->instances[$instanceId];
        }

        throw new UnregisteredInstanceException($instanceId);
    }

    public function hasInstance(int $instanceId): bool
    {
        return array_key_exists($instanceId, $this->instances);
    }

    public function cacheInstance($value, ?int $id = null): int
    {
        $id ??= $this->ffiPolar->newId();

        $this->instances[$id] = $value;

        return $id;
    }

    public function makeInstance(string $className, array $args, int $id): object
    {
        $class = $this->getClass($className);

        if ($class->isPrimitive()) {
            throw new InstantiationException($className);
        }

        $instance = new ($class->getName())(...$args);

        $this->cacheInstance($instance, $id);

        return $instance;
    }

    public function isa(array $instance, string $classTag): bool
    {
        return $this->getClass($classTag)->isInstance($this->toPhp($instance));
    }

    /**
     * @throws ReflectionException
     */
    public function isaWithPath(string $baseTag, array $path, string $classTag): bool
    {
        $tag = $baseTag;

        foreach ($path as $pathElement) {
            $field = $this->toPhp($pathElement);

            if (!is_string($field)) {
                throw new InvalidFieldNameException($field);
            }

            $userType = $this->getType($tag);
            if ($userType === null) {
                return false;
            }

            $fieldType = $userType->fields[$field] ?? null;
            if ($fieldType === null) {
                return false;
            }

            if ($fieldType instanceof Relation) {
                switch ($fieldType->kind) {
                    case RelationKind::One:
                        $otherType = $this->getType($fieldType->otherType)?->classType;

                        if ($otherType === null) {
                            throw new UnregisteredClassException($fieldType->otherType);
                        }

                        $fieldType = $otherType;
                        break;

                    case RelationKind::Many:
                        $fieldType = ClassType::fromName('array');
                        break;
                }
            }

            $newBase = $this->getType($fieldType);
            if ($newBase === null) {
                return false;
            }

            $tag = $newBase->name;
        }

        return $classTag === $tag;
    }

    public function isSubclass(string $leftTag, string $rightTag): bool
    {
        $leftClass = $this->getClass($leftTag);
        $rightClass = $this->getClass($rightTag);

        return $leftClass->getName() === $rightClass->getName() || $leftClass->isSubclassOf($rightClass);
    }

    public function externalOp(PolarComparisonOperator $op, array $args): bool
    {
        [$left, $right] = $this->toPhpArray($args);

        return match ($op) {
            PolarComparisonOperator::Eq  => is_object($left) && is_object($right) ? $left == $right : $left === $right,
            PolarComparisonOperator::Neq => is_object($left) && is_object($right) ? $left != $right : $left !== $right,
            PolarComparisonOperator::Geq => $left >= $right,
            PolarComparisonOperator::Gt  => $left > $right,
            PolarComparisonOperator::Leq => $left <= $right,
            PolarComparisonOperator::Lt  => $left < $right,
        };
    }

    #[ArrayShape(['value' => 'array'])]
    public function toPolarTerm($value): array
    {
        if (is_bool($value)) {
            return self::term('Boolean', $value);
        }

        if (is_int($value)) {
            return self::term('Number', ['Integer' => $value]);
        }

        if (is_float($value)) {
            if ($value === INF) {
                $value = 'Infinity';
            } elseif ($value === -INF) {
                $value = '-Infinity';
            } elseif (is_nan($value)) {
                $value = 'NaN';
            }

            return self::term('Number', ['Float' => $value]);
        }

        if (is_string($value)) {
            return self::term('String', $value);
        }

        if (is_array($value)) {
            if (array_is_list($value)) {
                return self::term('List', array_map(
                    $this->toPolarTerm(...),
                    $value
                ));
            }

            $fields = [];

            foreach ($value as $k => $v) {
                if (!is_string($k)) {
                    throw new InvalidArgumentException(
                        'Cannot convert array with non-string keys to Polar'
                    );
                }

                $fields[$k] = $this->toPolarTerm($v);
            }

            return self::term('Dictionary', ['fields' => $fields]);
        }

        if ($value instanceof Predicate) {
            return self::term('Call', [
                'name' => $value->name,
                'args' => array_map(
                    $this->toPolarTerm(...),
                    $value->args
                ),
            ]);
        }

        if ($value instanceof Variable) {
            return self::term('Variable', $value->name);
        }

        if ($value instanceof Expression) {
            return self::term('Expression', [
                'operator' => $value->operator->value,
                'args'     => array_map(
                    $this->toPolarTerm(...),
                    $value->args
                ),
            ]);
        }

        if ($value instanceof Pattern) {
            $dict = empty($value->fields)
                ? ['Dictionary' => ['fields' => new stdClass()]]
                : $this->toPolarTerm($value->fields)['value'];

            if (empty($value->tag)) {
                return self::term('Pattern', $dict);
            }

            return self::term('Pattern', [
                'Instance' => [
                    'tag'    => $value->tag,
                    'fields' => $dict['Dictionary'],
                ],
            ]);
        }

        if ($value instanceof ClassType && isset($this->types[$value])) {
            $instanceId = $classId = $this->types[$value]->id;
        }

        $registeredType = $this->getType(ClassType::fromInstance($value));

        if ($registeredType !== null) {
            $classRepr = $registeredType->name;
            $classId = $registeredType->id;
        }

        $instanceId = $this->cacheInstance($value, $instanceId ?? null);

        return self::term('ExternalInstance', [
            'instance_id' => $instanceId,
            'repr'        => self::repr($value),
            'class_repr'  => $classRepr ?? null,
            'class_id'    => $classId ?? null,
        ]);
    }

    public function toPhp(array $term): mixed
    {
        $value = $term['value'];
        $tag = array_keys($value)[0];
        $data = $value[$tag];

        return match ($tag) {
            'String', 'Boolean'  => $data,
            'Number'             => match (array_keys($data)[0]) {
                'Integer'        => $data['Integer'],
                'Float'          => is_string($data['Float']) ? match ($data['Float']) {
                    'Infinity'   => INF,
                    '-Infinity'  => -INF,
                    'NaN'        => NAN,
                    default      => throw new OsoException("Expected a floating point number, got \"{$data['Float']}\"")
                }
                : $data['Float'],
                default   => throw new OsoException('Expected a Number, got "'.array_keys($data)[0].'"'),
            },
            'List'               => $this->toPhpArray($data),
            'Dictionary'         => $this->toPhpArray((array) $data['fields']),
            'ExternalInstance'   => $this->getInstance($data['instance_id']),
            'Call'               => new Predicate($data['name'], ...$this->toPhpArray($data['args'])),
            'Variable'           => new Variable($data),
            'Expression'         => $this->acceptExpression
                ? new Expression(
                    PolarOperator::from($data['operator']),
                    ...$this->toPhpArray($data['args'])
                )
                : throw new UnexpectedExpressionException(),
            'Pattern'            => match (array_keys($data)[0]) {
                'Instance'       => new Pattern(
                    $data['Instance']['tag'],
                    $this->toPhp(self::term('Dictionary', [
                        'fields' => $data['Instance']['fields']['fields'],
                    ]))
                ),
                'Dictionary' => new Pattern(
                    fields: $this->toPhp(['value' => $data])
                ),
                default      => throw new OsoException('Expected a Pattern, got \"'.array_keys($value)[0].'"')
            },
            default              => throw new OsoException("Unknown tag: '$tag'")
        };
    }

    public function toPhpArray(array $value): array
    {
        return array_map($this->toPhp(...), $value);
    }

    /**
     * @return iterable<UserType>
     */
    protected function distinctUserTypes(): iterable
    {
        foreach ($this->types as $key => $type) {
            if (is_string($key)) {
                yield $key => $type;
            }
        }
    }

    protected function getClass(string $name): ClassType
    {
        $class = $this->types[$name] ?? null;

        if ($class === null) {
            throw new UnregisteredClassException($name);
        }

        return $class->classType;
    }

    public static function repr(mixed $value): ?string
    {
        $repr = get_debug_type($value);

        if (is_object($value)) {
            $repr .= '@'.spl_object_id($value);
        }

        return $repr;
    }

    #[ArrayShape(['value' => 'array'])]
    private static function term(string $type, mixed $data): array
    {
        return ['value' => [$type => $data]];
    }
}

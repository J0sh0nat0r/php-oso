<?php

namespace J0sh0nat0r\Oso;

use ArrayIterator;
use BadMethodCallException;
use Generator;
use Iterator;
use IteratorAggregate;
use IteratorIterator;
use J0sh0nat0r\Oso\Exceptions\DuplicateInstanceRegistrationException;
use J0sh0nat0r\Oso\Exceptions\InternalErrorException;
use J0sh0nat0r\Oso\Exceptions\InvalidAttributeException;
use J0sh0nat0r\Oso\Exceptions\InvalidCallException;
use J0sh0nat0r\Oso\Exceptions\InvalidFieldNameException;
use J0sh0nat0r\Oso\Exceptions\InvalidIteratorException;
use J0sh0nat0r\Oso\FFI\Query as FFIQuery;
use ReflectionException;

class Query implements IteratorAggregate
{
    /**
     * @var array<int, Iterator>
     */
    protected array $calls = [];

    public function __construct(protected FFIQuery $ffiQuery, protected Host $host, array $bindings)
    {
        foreach ($bindings as $name => $value) {
            $this->bind($name, $value);
        }
    }

    protected function handleCall(string $attrName, ?array $args, array $polarInstance, int $callId): void
    {
        $instance = $this->host->toPhp($polarInstance);

        try {
            if ($args !== null) {
                try {
                    $result = $instance->{$attrName}(...array_map(
                        $this->host->toPhp(...),
                        $args
                    ));
                } catch (BadMethodCallException) {
                    throw new InvalidCallException($instance, $attrName);
                }
            } else {
                if (
                    !is_object($instance)
                    || (
                        !property_exists($instance, $attrName)
                        && !method_exists($instance, '__get')
                    )
                ) {
                    throw new InvalidAttributeException($instance, $attrName);
                }

                $result = $instance->{$attrName};
            }

        } catch (InvalidAttributeException|InvalidCallException $e) {
            $result = null;

            $this->ffiQuery->applicationError($e->getMessage());
        }

        $term = $this->host->toPolar($result);

        $this->ffiQuery->callResult($callId, $term);
    }

    protected function results(): Generator
    {
        while (true) {
            $event = $this->ffiQuery->nextEvent();

            $kind = array_keys($event)[0];
            $data = $event[$kind];

            switch ($kind) {
                case 'Done':
                    return;
                case 'Result':
                    yield $this->host->toPhpArray($data['bindings']);
                    break;
                default:
                {
                    $handler = "handle$kind";

                    if (!method_exists($this, $handler)) {
                        throw new InternalErrorException("Unhandled event kind: $kind");
                    }

                    $this->$handler($data);
                    break;
                }
            }
        }
    }

    protected function bind(string $name, $value): void
    {
        $this->ffiQuery->bind($name, $this->host->toPolar($value));
    }

    protected function handleDebug(): void
    {
        trigger_error('Debug events are not (currently) supported in PHP');
    }

    protected function handleExternalCall($data): void
    {
        $args = $data['args'] ?? null;

        if (!empty($data['kwargs'])) {
            throw new InternalErrorException('kwargs are currently unsupported');
        }

        $this->handleCall($data['attribute'], $args, $data['instance'], $data['call_id']);
    }

    protected function handleExternalIsSubclass(array $data): void
    {
        $answer = $this->host->isSubclass(
            $data['left_class_tag'],
            $data['right_class_tag']
        );

        $this->ffiQuery->questionResult($data['call_id'], $answer);
    }

    protected function handleExternalIsa(array $data): void
    {
        $answer = $this->host->isa($data['instance'], $data['class_tag']);

        $this->ffiQuery->questionResult($data['call_id'], $answer);
    }

    /**
     * @throws ReflectionException
     */
    protected function handleExternalIsaWithPath(array $data): void
    {
        $answer = $this->host->isaWithPath($data['base_tag'], $data['path'], $data['class_tag']);

        $this->ffiQuery->questionResult($data['call_id'], $answer);
    }

    protected function handleExternalOp(array $data): void
    {
        $answer = $this->host->externalOp(
            PolarComparisonOperator::from($data['operator']),
            $data['args']
        );

        $this->ffiQuery->questionResult($data['call_id'], $answer);
    }

    protected function handleMakeExternal(array $data): void
    {
        $instanceId = $data['instance_id'];

        if ($this->host->hasInstance($instanceId)) {
            throw new DuplicateInstanceRegistrationException($instanceId);
        }

        $call = $data['constructor']['value']['Call'];

        if (isset($call['kwargs'])) {
            throw new InternalErrorException('kwargs are currently unsupported');
        }

        $this->host->makeInstance($call['name'], $this->host->toPhpArray($call['args']), $instanceId);
    }

    protected function handleNextExternal(array $data): void
    {
        $callId = $data['call_id'];

        if (!isset($this->calls[$callId])) {
            $value = $this->host->toPhp($data['iterable']);

            if ($value instanceof Iterator) {
                $iterator = $value;
            } elseif ($value instanceof IteratorAggregate) {
                $iterator = new IteratorIterator($value->getIterator());
            } elseif (is_array($value)) {
                $iterator = new ArrayIterator($value);
            } else {
                throw new InvalidIteratorException($value);
            }

            $iterator->rewind();

            $this->calls[$callId] = $iterator;
        } else {
            $iterator = $this->calls[$callId];
        }

        if (!$iterator->valid()) {
            $this->ffiQuery->callResult($callId, null);

            unset($this->calls[$callId]);

            return;
        }

        $value = $this->host->toPolar($iterator->current());

        $iterator->next();

        $this->ffiQuery->callResult($callId, $value);
    }

    public function getIterator(): Generator
    {
        return $this->results();
    }
}

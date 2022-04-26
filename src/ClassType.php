<?php

namespace J0sh0nat0r\Oso;

use ReflectionClass;
use ReflectionException;

class ClassType
{
    protected const PRIMITIVE_TYPES = [
        'array',
        'boolean',
        'double',
        'float',
        'integer',
        'NULL',
        'resource',
        'resource (closed)',
        'string',
    ];

    /**
     * @param ?ReflectionClass<object> $reflectionClass
     */
    protected function __construct(protected string $name, public readonly ?ReflectionClass $reflectionClass)
    {
    }

    /**
     * @throws ReflectionException
     */
    public static function fromName(string $name): self
    {
        if (!in_array($name, self::PRIMITIVE_TYPES, true)) {
            $reflectionClass = new ReflectionClass($name);
        }

        return new self(self::normalizeType($name), $reflectionClass ?? null);
    }

    /**
     * @throws ReflectionException
     */
    public static function fromInstance(mixed $instance): self
    {
        $name = self::getNormalizedType($instance);

        if ($name === 'object') {
            $name = $instance::class;
            $reflectionClass = new ReflectionClass($instance);
        }

        return new self($name, $reflectionClass ?? null);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function isPrimitive(): bool
    {
        return $this->reflectionClass === null;
    }

    public function isInstance(mixed $instance): bool
    {
        $instanceType = self::getNormalizedType($instance);

        if ($instanceType === $this->name && $this->isPrimitive()) {
            return true;
        }

        return (bool) $this->reflectionClass?->isInstance($instance);
    }

    public function isSubclassOf(ClassType $classType): bool
    {
        return (bool) $this->reflectionClass?->isSubclassOf($classType->reflectionClass);
    }

    public function getParentType(): false|self
    {
        if ($this->isPrimitive()) {
            return false;
        }

        $parent = $this->reflectionClass->getParentClass();

        if (!$parent) {
            return false;
        }

        return new self($parent->getName(), $parent);
    }

    protected static function getNormalizedType(mixed $value): string
    {
        return self::normalizeType(gettype($value));
    }

    /*
     * Some primitive types should be considered to be equivalent, so we must normalize the names
     */
    protected static function normalizeType(string $type): string
    {
        return match ($type) {
            'double', 'float', => 'float',
            'resource', 'resource (closed)' => 'resource',
            default => $type
        };
    }
}

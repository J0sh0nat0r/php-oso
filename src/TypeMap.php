<?php

namespace J0sh0nat0r\Oso;

use ArrayAccess;
use InvalidArgumentException;
use IteratorAggregate;
use Traversable;

/**
 * @implements ArrayAccess<string|ClassType, UserType>
 * @implements IteratorAggregate<string|ClassType, UserType>
 */
class TypeMap implements ArrayAccess, IteratorAggregate
{
    /**
     * @var array<string, UserType>
     */
    protected array $classTypes;
    /**
     * @var array<string, UserType>>
     */
    protected array $namedTypes;

    public function offsetExists(mixed $offset): bool
    {
        if ($offset instanceof ClassType) {
            return isset($this->classTypes[$offset->getName()]);
        }

        if(is_string($offset)) {
            return isset($this->namedTypes[$offset]);
        }

        /** @phpstan-ignore-next-line */
        throw new InvalidArgumentException('Illegal offset type');
    }

    public function offsetGet(mixed $offset): UserType
    {
        if ($offset instanceof ClassType) {
            return $this->classTypes[$offset->getName()];
        }

        if(is_string($offset)) {
            return $this->namedTypes[$offset];
        }

        /** @phpstan-ignore-next-line */
        throw new InvalidArgumentException('Illegal offset type');
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        if (!($value instanceof UserType)) {
            throw new InvalidArgumentException('Illegal value type');
        }

        if ($offset instanceof ClassType) {
            $this->classTypes[$offset->getName()] = $value;
            return;
        }

        if(is_string($offset)) {
            $this->namedTypes[$offset] = $value;
            return;
        }

        throw new InvalidArgumentException('Illegal offset type: ' . get_debug_type($offset));
    }

    public function offsetUnset(mixed $offset): void
    {
        if ($offset instanceof ClassType) {
            unset($this->classTypes[$offset->getName()]);

            return;
        }

        if(is_string($offset)) {
            unset($this->namedTypes[$offset]);

            return;
        }

        /** @phpstan-ignore-next-line */
        throw new InvalidArgumentException('Illegal offset type');
    }

    public function getIterator(): Traversable
    {
        yield from $this->namedTypes;

        foreach ($this->classTypes as $type) {
            yield $type->classType => $type;
        }
    }
}
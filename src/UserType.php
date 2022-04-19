<?php

namespace J0sh0nat0r\Oso;

class UserType
{
    /**
     * @param array<string, string|Relation> $fields
     */
    public function __construct(public string $name, public ClassType $classType, public int $id, public array $fields)
    {
    }
}
<?php

namespace J0sh0nat0r\Oso;

class Predicate
{
    public array $args;

    public function __construct(public string $name, ...$args)
    {
        $this->args = $args;
    }
}
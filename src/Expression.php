<?php

namespace J0sh0nat0r\Oso;

class Expression
{
    public array $args;

    public function __construct(public PolarOperator $operator, ...$args)
    {
        $this->args = $args;
    }
}

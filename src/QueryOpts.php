<?php

namespace J0sh0nat0r\Oso;

class QueryOpts
{
    public function __construct(public readonly bool $acceptExpression = false, public readonly array $bindings = [])
    {
    }

    public static function default(): self
    {
        return new self();
    }
}
<?php

namespace J0sh0nat0r\Oso\Tests\PolarTestSupport;

class A
{
    public function __construct(public string $x)
    {
    }

    public function foo(): int
    {
        return -1;
    }
}

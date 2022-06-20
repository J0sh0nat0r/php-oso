<?php

namespace J0sh0nat0r\Oso\Tests\PolarTestSupport;

class BC
{
    public function __construct(public string $y)
    {

    }

    public function foo(): int
    {
        return -1;
    }
}

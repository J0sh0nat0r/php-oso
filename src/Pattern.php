<?php

namespace J0sh0nat0r\Oso;

class Pattern
{
    public function __construct(public ?string $tag = null, public array $fields = [])
    {
    }
}
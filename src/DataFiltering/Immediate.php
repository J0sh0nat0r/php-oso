<?php

namespace J0sh0nat0r\Oso\DataFiltering;

use J0sh0nat0r\Oso\Host;

class Immediate
{
    public function __construct(public mixed $value)
    {
    }

    public static function parse(Host $host, array $data): self
    {
        return new self($host->toPhp(['value' => $data]));
    }
}
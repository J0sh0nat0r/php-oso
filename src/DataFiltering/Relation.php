<?php

namespace J0sh0nat0r\Oso\DataFiltering;

class Relation
{
    public function __construct(public string $fromTypeName, public string $fromFieldName, public string $toTypeName)
    {
    }

    public static function parse(array $data): self
    {
        return new self(...$data);
    }
}
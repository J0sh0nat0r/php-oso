<?php

namespace J0sh0nat0r\Oso;

use JetBrains\PhpStorm\ArrayShape;
use JsonSerializable;

class Source implements JsonSerializable
{
    public function __construct(public string $src, public ?string $filename)
    {
    }

    #[ArrayShape(['src' => "string", 'filename' => "null|string"])]
    public function jsonSerialize(): array
    {
        return [
            'src' => $this->src,
            'filename' => $this->filename,
        ];
    }
}
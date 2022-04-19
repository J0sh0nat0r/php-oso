<?php

namespace J0sh0nat0r\Oso\Exceptions;

class InlineQueryFailedException extends PolarException
{
    public function __construct(string $source)
    {
        parent::__construct("Inline query failed: $source");
    }
}

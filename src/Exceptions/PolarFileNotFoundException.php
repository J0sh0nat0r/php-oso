<?php

namespace J0sh0nat0r\Oso\Exceptions;

class PolarFileNotFoundException extends PolarException
{
    public function __construct(string $file)
    {
        parent::__construct("Could not find file: $file");
    }
}

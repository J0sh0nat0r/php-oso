<?php

namespace J0sh0nat0r\Oso\Exceptions;

class PolarFileExtensionException extends PolarException
{
    public function __construct(string $file)
    {
        parent::__construct("Polar files must have .polar extension. Offending file: $file");
    }
}

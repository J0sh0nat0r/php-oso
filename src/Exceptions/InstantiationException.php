<?php

namespace J0sh0nat0r\Oso\Exceptions;

class InstantiationException extends PolarException
{
    public function __construct(string $type)
    {
        parent::__construct("The type $type is not instantiable");
    }
}

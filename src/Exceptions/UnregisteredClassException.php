<?php

namespace J0sh0nat0r\Oso\Exceptions;

class UnregisteredClassException extends PolarException
{
    public function __construct(string $name)
    {
        parent::__construct("Unregistered class: $name.");
    }
}

<?php

namespace J0sh0nat0r\Oso\Exceptions;

use J0sh0nat0r\Oso\Host;

class InvalidCallException extends PolarException
{
    public function __construct(mixed $instance, string $field)
    {
        parent::__construct(Host::repr($instance) . "::$field is not callable");
    }
}
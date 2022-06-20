<?php

namespace J0sh0nat0r\Oso\Exceptions;

use J0sh0nat0r\Oso\Host;

class InvalidAttributeException extends PolarException
{
    public function __construct(mixed $instance, string $field)
    {
        parent::__construct("$field not found on ".Host::repr($instance));
    }
}

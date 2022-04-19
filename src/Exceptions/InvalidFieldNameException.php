<?php

namespace J0sh0nat0r\Oso\Exceptions;

use J0sh0nat0r\Oso\Host;

class InvalidFieldNameException extends PolarException
{
    public function __construct(mixed $name)
    {
        parent::__construct(Host::repr($name).' is not a valid field name');
    }
}

<?php

namespace J0sh0nat0r\Oso\Exceptions;

use J0sh0nat0r\Oso\Host;

class InvalidIteratorException extends PolarException
{
    public function __construct($term)
    {
        parent::__construct(Host::repr($term) . ' is not iterable');
    }
}

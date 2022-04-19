<?php

namespace J0sh0nat0r\Oso\Exceptions;

class UnregisteredInstanceException extends PolarException
{
    public function __construct(int $id)
    {
        parent::__construct("Unregistered instance: $id");
    }
}

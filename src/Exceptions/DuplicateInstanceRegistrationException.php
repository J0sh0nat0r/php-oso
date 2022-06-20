<?php

namespace J0sh0nat0r\Oso\Exceptions;

class DuplicateInstanceRegistrationException extends PolarException
{
    public function __construct(int $id)
    {
        parent::__construct("Attempted to register instance $id, but an instance with that ID already exists");
    }
}

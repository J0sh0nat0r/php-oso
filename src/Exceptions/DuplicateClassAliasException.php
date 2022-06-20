<?php

namespace J0sh0nat0r\Oso\Exceptions;

use J0sh0nat0r\Oso\ClassType;
use J0sh0nat0r\Oso\UserType;

class DuplicateClassAliasException extends PolarException
{
    public function __construct(string $name, ClassType $class, UserType $existing)
    {
        parent::__construct(
            "Attempted to alias {$class->getName()} as '$name', but {$existing->classType->getName()} already has that alias"
        );
    }
}

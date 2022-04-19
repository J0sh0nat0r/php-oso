<?php

namespace J0sh0nat0r\Oso\Exceptions;

class DataFilteringConfigurationException extends PolarException
{
    public function __construct()
    {
        parent::__construct("Missing 'adapter' implementation. Did you forget to call `Oso::setDataFilteringAdapter`?");
    }
}

<?php

namespace J0sh0nat0r\Oso\FFI;

use J0sh0nat0r\Oso\Exceptions\InternalErrorException;
use J0sh0nat0r\Oso\Exceptions\OsoException;

class CResultVoid extends CResult
{
    public function check(): void
    {
        if ($this->error->isNull()) {
            return;
        }

        if ($this->ptr->result !== null) {
            throw new InternalErrorException('Both result and error pointers are non-null');
        }

        throw OsoException::fromJson($this->error->value());
    }
}

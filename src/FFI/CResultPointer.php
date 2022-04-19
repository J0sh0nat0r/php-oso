<?php

namespace J0sh0nat0r\Oso\FFI;

use FFI\CData;
use J0sh0nat0r\Oso\Exceptions\OsoException;

/**
 * @template T of AutoPointer
 */
class CResultPointer extends CResult
{
    protected AutoPointer $result;

    /**
     * @param class-string<T> $resultType
     */
    public function __construct(Ffi $polarLib, CData $ptr, string $resultType)
    {
        parent::__construct($polarLib, $ptr);

        $this->result = new $resultType($polarLib, $ptr->result);
    }

    /**
     * @return T
     */
    public function check(): object
    {
        if ($this->error->isNull()) {
            return $this->result;
        }

        if (!$this->result->isNull()) {
            throw new OsoException('Internal error: both result and error pointers are non-null');
        }

        throw OsoException::fromJson($this->error->value());
    }
}

<?php

namespace J0sh0nat0r\Oso\FFI;

use FFI\CData;

abstract class AutoPointer
{
    protected CData $ptr;

    public function __construct(protected PolarLib $polarLib, ?CData $ptr)
    {
        $this->ptr = $ptr ?? \FFI::cast('void*', 0);
    }

    public function get(): CData
    {
        return $this->ptr;
    }

    public function isNull(): bool
    {
        return \FFI::isNull($this->ptr);
    }

    public function __destruct()
    {
        if (!$this->isNull()) {
            $this->free();
        }
    }

    abstract protected function free(): int;
}

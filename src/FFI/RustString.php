<?php

namespace J0sh0nat0r\Oso\FFI;

use FFI;

class RustString extends AutoPointer
{
    public function value(): ?string
    {
        return $this->isNull() ? null : FFI::string($this->ptr);
    }

    protected function free(): int
    {
        return $this->polarLib->stringFree($this);
    }
}
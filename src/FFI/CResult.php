<?php

namespace J0sh0nat0r\Oso\FFI;

use FFI\CData;

class CResult extends AutoPointer
{
    protected RustString $error;

    public function __construct(PolarLib $polarLib, CData $ptr)
    {
        parent::__construct($polarLib, $ptr);

        $this->error = new RustString($polarLib, $ptr->error);
    }

    protected function free(): int
    {
        return $this->polarLib->resultFree($this);
    }
}

<?php

namespace J0sh0nat0r\Oso\Exceptions;

use J0sh0nat0r\Oso\FFI\Ffi;
use RuntimeException;

class OsoException extends RuntimeException
{
    public function __construct(string $message, protected array $details = [])
    {
        parent::__construct($message);
    }

    public function getDetails(): array
    {
        return $this->details;
    }

    public static function fromJson(string $json): self
    {
        try {
            $data = Ffi::deserialize($json);
        } catch (InternalErrorException) {
            return new self($json);
        }

        // TODO: Implement parsing of exceptions
        return new self($json, $data);
    }
}

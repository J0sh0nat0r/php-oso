<?php

namespace J0sh0nat0r\Oso\FFI;

use J0sh0nat0r\Oso\Exceptions\InternalErrorException;
use J0sh0nat0r\Oso\Exceptions\OsoException;
use J0sh0nat0r\Oso\Source;

class Polar extends AutoPointer
{
    public function newId(): int
    {
        return $this->polarLib->polarGetExternalId($this);
    }

    public function buildDataFilter(array $types, array $partialResults, string $variable, string $classTag): array
    {
        $plan = $this->polarLib->polarBuildDataFilter(
            $this,
            Ffi::serialize($types),
            Ffi::serialize($partialResults),
            $variable,
            $classTag
        );

        $this->processMessages();

        return Ffi::deserialize($plan->check()->value());
    }

    /**
     * @param array<array|Source> $sources
     */
    public function load(array $sources): void
    {
        $this->polarLib->polarLoad($this, Ffi::serialize($sources))->check();
        $this->processMessages();
    }

    public function clearRules(): void
    {
        $this->polarLib->polarClearRules($this)->check();
        $this->processMessages();
    }

    public function newQueryFromStr(string $queryStr): Query
    {
        $query = $this->polarLib->polarNewQuery($this, $queryStr, 0)->check();
        $this->processMessages();
        return $query;
    }

    public function newQueryFromTerm(array $queryTerm): Query
    {
        $query = $this->polarLib->polarNewQueryFromTerm($this, Ffi::serialize($queryTerm), 0)->check();
        $this->processMessages();
        return $query;
    }

    public function nextInlineQuery(): ?Query
    {
        $query = $this->polarLib->polarNextInlineQuery($this, 0);
        $this->processMessages();
        return $query;
    }

    public function registerConstant(string $name, array $value): void
    {
        $this->polarLib->polarRegisterConstant($this, $name, Ffi::serialize($value))->check();
    }

    public function registerMro(string $name, array $mro): void
    {
        $this->polarLib->polarRegisterMro($this, $name, Ffi::serialize($mro))->check();
    }

    public function nextMessage(): ?string
    {
        return $this->polarLib->polarNextPolarMessage($this)->check()->value();
    }

    protected function processMessages(): void
    {
        while ($message = $this->nextMessage()) {
            self::processMessage($message);
        }
    }

    protected function free(): int
    {
        return $this->polarLib->polarFree($this);
    }

    public static function processMessage(string $msgStr): void
    {
        try {
            ['kind' => $kind, 'msg' => $msg] = Ffi::deserialize($msgStr);

            match ($kind) {
                'Print' => trigger_error($msg),
                'Warning' => trigger_error($msg, E_USER_WARNING),
                // Ignored
                default => null
            };
        } catch (InternalErrorException) {
            throw new OsoException("Invalid JSON message: $msgStr");
        }
    }
}
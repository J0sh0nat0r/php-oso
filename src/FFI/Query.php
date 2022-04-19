<?php

namespace J0sh0nat0r\Oso\FFI;

class Query extends AutoPointer
{
    public function questionResult(int $callId, bool $result): void
    {
        $this->polarLib->polarQuestionResult($this, $callId, (int) $result)->check();
    }

    public function callResult(int $callId, ?array $value): void
    {
        $this->polarLib->polarCallResult($this, $callId, Ffi::serialize($value))->check();
    }

    public function applicationError(string $message): void
    {
        $this->polarLib->polarApplicationError($this, $message)->check();
    }

    public function nextEvent(): array
    {
        $event = $this->polarLib->polarNextQueryEvent($this)->check()->value();

        $this->processMessages();

        return Ffi::deserialize($event);
    }

    public function debugCommand(string $value): void
    {
        $this->polarLib->polarDebugCommand($this, $value)->check();

        $this->processMessages();
    }

    public function nextMessage(): ?string
    {
        return $this->polarLib->polarNextQueryMessage($this)->check()->value();
    }

    public function source(): string
    {
        return $this->polarLib->polarQuerySourceInfo($this)->check()->value();
    }

    public function bind(string $name, array $value): void
    {
        $this->polarLib->polarBind($this, $name, Ffi::serialize($value))->check();
    }

    protected function processMessages(): void
    {
        while ($message = $this->nextMessage()) {
            Polar::processMessage($message);
        }
    }

    protected function free(): int
    {
        return $this->polarLib->queryFree($this);
    }
}
<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions;

final readonly class ActionRunResult
{
    /** @param array<string,string> $errors */
    public function __construct(
        public array $errors,
        public ActionRuntime $runtime
    ) {}

    public function redirectUrl(): string
    {
        return $this->runtime->redirectUrl();
    }

    /** @return list<string> */
    public function emittedEvents(): array
    {
        return $this->runtime->emittedEvents();
    }

    /** @return list<string> */
    public function executedActionKeys(): array
    {
        return $this->runtime->executedActionKeys();
    }
}

<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Duplicate;

final class DuplicateDecision
{
    public function __construct(
        public readonly bool $enabled,
        public readonly string $fingerprint,
        public readonly ?int $duplicateSubmissionId,
        public readonly string $behavior,
        public readonly string $message
    ) {}

    public function isDuplicate(): bool
    {
        return $this->duplicateSubmissionId !== null;
    }

    public function allowsDuplicate(): bool
    {
        return $this->behavior === 'allow';
    }

    public function blocksDuplicate(): bool
    {
        return $this->isDuplicate() && !$this->allowsDuplicate();
    }
}

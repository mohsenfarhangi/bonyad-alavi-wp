<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Events;

final readonly class SubmissionCreated
{
    public function __construct(
        public int $submissionId,
        public string $formSlug,
        public array $data
    ) {}
}

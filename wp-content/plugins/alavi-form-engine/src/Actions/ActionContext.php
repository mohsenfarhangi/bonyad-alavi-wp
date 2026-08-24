<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions;

final readonly class ActionContext
{
    public function __construct(
        public int $submissionId,
        public string $formSlug,
        public array $data,
        public array $submission,
        public string $event = 'submitted'
    ) {}
}

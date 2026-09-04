<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions;

final readonly class ActionContext
{
    /** @param list<string> $fieldKeys */
    public function __construct(
        public int $submissionId,
        public string $formSlug,
        public array $data,
        public array $submission,
        public string $event = 'submission.submitted',
        public string $formTitle = '',
        public array $fieldKeys = [],
        public array $form = [],
        public ?ActionRuntime $runtime = null
    ) {}

    public function withRuntime(ActionRuntime $runtime): self
    {
        return new self(
            $this->submissionId,
            $this->formSlug,
            $this->data,
            $this->submission,
            $this->event,
            $this->formTitle,
            $this->fieldKeys,
            $this->form,
            $runtime
        );
    }
}

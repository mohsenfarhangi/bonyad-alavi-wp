<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions;

use BonyadAlavi\FormEngine\Actions\Tokens\TokenResolver;
use BonyadAlavi\FormEngine\Repository\SubmissionRepository;
use RuntimeException;

final class AddNoteAction implements ActionInterface
{
    public function __construct(
        private readonly SubmissionRepository $submissions,
        private readonly TokenResolver $tokens
    ) {}

    public function handle(ActionContext $context, array $config = []): void
    {
        $note = trim($this->tokens->resolve((string)($config['note'] ?? ''), $context));
        if ($note === '') throw new RuntimeException('متن یادداشت داخلی خالی است.');
        $author = max(0, (int)($config['user_id'] ?? 0));
        if ($author <= 0) $author = get_current_user_id();
        $id = $this->submissions->addNote($context->submissionId, $author, $note);
        if ($id <= 0) throw new RuntimeException('ثبت یادداشت داخلی ناموفق بود.');
        $this->submissions->audit($context->submissionId, $context->formSlug, 'note.created', [
            'source'=>'action',
            'note_id'=>$id,
        ]);
        $context->runtime?->set('note_id', $id);
    }
}

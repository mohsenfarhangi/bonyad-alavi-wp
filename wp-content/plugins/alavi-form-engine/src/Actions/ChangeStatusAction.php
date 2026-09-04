<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions;

use BonyadAlavi\FormEngine\Repository\SubmissionRepository;
use RuntimeException;

final class ChangeStatusAction implements ActionInterface
{
    public function __construct(private readonly SubmissionRepository $submissions) {}

    public function handle(ActionContext $context, array $config = []): void
    {
        $status = sanitize_key((string)($config['status'] ?? ''));
        if ($status === '') throw new RuntimeException('وضعیت مقصد تعریف نشده است.');
        $workflow = (array)($context->form['workflow'] ?? []);
        if ($workflow !== [] && !array_key_exists($status, $workflow)) {
            throw new RuntimeException('وضعیت مقصد در Workflow این فرم وجود ندارد.');
        }
        $row = $this->submissions->find($context->submissionId, true);
        if (!$row) throw new RuntimeException('Submission برای تغییر وضعیت پیدا نشد.');
        $before = (string)($row['status'] ?? '');
        if ($before === $status) {
            $context->runtime?->set('status', $status);
            return;
        }
        if (!$this->submissions->update($context->submissionId, [
            'status'=>$status,
            'updated_at'=>current_time('mysql', true),
        ])) {
            throw new RuntimeException('ذخیره وضعیت جدید Submission ناموفق بود.');
        }
        $this->submissions->audit($context->submissionId, $context->formSlug, 'submission.status_changed', [
            'from'=>$before,
            'to'=>$status,
            'source'=>'action',
        ]);
        $context->runtime?->set('status', $status);
        $context->runtime?->emit('submission.status_changed');
    }
}

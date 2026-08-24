<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions;

final class EmailAction implements ActionInterface
{
    public function handle(ActionContext $context, array $config = []): void
    {
        $to = (string)($config['to'] ?? get_option('admin_email'));
        $subject = (string)($config['subject'] ?? 'ثبت فرم جدید: ' . $context->formSlug);
        $body = (string)($config['body'] ?? "یک ثبت جدید با کد رهگیری {$context->submission['tracking_code']} ایجاد شد.");
        $body = strtr($body, [
            '{{tracking_code}}'=>(string)$context->submission['tracking_code'],
            '{{submission_id}}'=>(string)$context->submissionId,
            '{{form_slug}}'=>$context->formSlug,
        ]);
        wp_mail($to, $subject, $body);
    }
}

<?php
declare(strict_types=1);

use BonyadAlavi\FormEngine\Actions\ActionContext;
use BonyadAlavi\FormEngine\Actions\ActionInterface;
use RuntimeException;
use WP_Error;

final class BA_Quick_Support_Payment_Action implements ActionInterface
{
    public function __construct(private readonly BA_Quick_Support_Payment_Service $payments) {}

    public function handle(ActionContext $context, array $config = []): void
    {
        if ($context->event !== 'submission.submitted') return;

        if (empty($context->context['category_id'])) {
            throw new RuntimeException('دسته پرداخت سریع به فرم متصل نیست یا Context آن معتبر نیست.');
        }

        $result = $this->payments->process($context->context, $context->data, $context->submissionId);
        if (is_wp_error($result)) {
            throw new RuntimeException($result->get_error_message());
        }

        $redirect = esc_url_raw((string)($result['redirect'] ?? ''));
        if ($redirect === '') {
            throw new RuntimeException('آدرس انتقال به درگاه پرداخت دریافت نشد.');
        }

        $context->runtime?->setRedirect($redirect);
    }
}

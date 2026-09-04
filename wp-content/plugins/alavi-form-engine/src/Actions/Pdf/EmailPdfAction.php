<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions\Pdf;

use BonyadAlavi\FormEngine\Actions\ActionContext;
use BonyadAlavi\FormEngine\Actions\ActionInterface;
use BonyadAlavi\FormEngine\Actions\Tokens\TokenResolver;
use RuntimeException;

final class EmailPdfAction implements ActionInterface
{
    public function __construct(
        private readonly PdfGenerator $generator,
        private readonly TokenResolver $tokens
    ) {}

    public function handle(ActionContext $context, array $config = []): void
    {
        $to = trim($this->tokens->resolve((string)($config['to'] ?? ''), $context));
        if ($to === '') throw new RuntimeException('گیرنده Email PDF خالی است.');
        $path = (string)($context->runtime?->get('pdf_path', '') ?? '');
        if ($path === '' || !is_readable($path) || empty($config['reuse_generated'])) {
            $filename = $this->tokens->resolve((string)($config['filename'] ?? ''), $context);
            $pdf = $this->generator->generate($context, $filename);
            $path = $pdf['path'];
            if ($context->runtime) foreach ($pdf as $key=>$value) $context->runtime->set('pdf_'.$key, $value);
        }
        $subject = $this->tokens->resolve((string)($config['subject'] ?? 'PDF ثبت {{tracking_code}}'), $context);
        $body = $this->tokens->resolve((string)($config['body'] ?? 'فایل PDF ثبت فرم پیوست شده است.'), $context);
        if (wp_mail($to, $subject, $body, [], [$path]) !== true) {
            throw new RuntimeException('ارسال Email دارای PDF ناموفق بود.');
        }
    }
}

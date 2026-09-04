<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions;

use BonyadAlavi\FormEngine\Actions\Tokens\TokenResolver;
use RuntimeException;

final class EmailAction implements ActionInterface
{
    public function __construct(private readonly TokenResolver $tokens) {}

    public function handle(ActionContext $context, array $config = []): void
    {
        $toTemplate = (string)($config['to'] ?? get_option('admin_email'));
        $subjectTemplate = (string)($config['subject'] ?? 'ثبت فرم جدید: {{form_title}}');
        $bodyTemplate = (string)($config['body'] ?? 'ثبت فرم با کد رهگیری {{tracking_code}} انجام شد.');

        $to = trim($this->tokens->resolve($toTemplate, $context));
        $subject = $this->tokens->resolve($subjectTemplate, $context);
        $body = $this->tokens->resolve($bodyTemplate, $context);
        $resolvedHeaders = $this->tokens->resolveValue((array)($config['headers'] ?? []), $context);
        $headers = [];
        foreach ((array)$resolvedHeaders as $key => $value) {
            if (is_string($key)) $headers[] = $key . ': ' . (string)$value;
            else $headers[] = (string)$value;
        }

        if ($to === '') {
            throw new RuntimeException('Email recipient is empty after token resolution.');
        }

        $sent = wp_mail($to, $subject, $body, $headers);
        if ($sent !== true) {
            throw new RuntimeException('WordPress could not send the email.');
        }
    }
}

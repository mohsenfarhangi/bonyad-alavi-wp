<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions\Sms;

use BonyadAlavi\FormEngine\Actions\ActionContext;
use BonyadAlavi\FormEngine\Actions\ActionInterface;
use BonyadAlavi\FormEngine\Actions\Tokens\TokenResolver;
use RuntimeException;

final class SmsAction implements ActionInterface
{
    private readonly SmsProviderRegistry $providers;

    public function __construct(
        SmsProviderInterface|SmsProviderRegistry $provider,
        private readonly TokenResolver $tokens
    ) {
        // Backward compatibility for developer code/tests that constructed the
        // Action with one provider before provider routing was introduced.
        $this->providers = $provider instanceof SmsProviderRegistry
            ? $provider
            : SmsProviderRegistry::single($provider);
    }

    public function handle(ActionContext $context, array $config = []): void
    {
        $recipient = $this->resolveRecipient($context, $config);
        $recipient = $this->normalizeIranMobile($recipient);
        if ($recipient === '') {
            throw new RuntimeException('SMS recipient is not a valid Iranian mobile number.');
        }

        $mode = sanitize_key((string)($config['mode'] ?? 'free'));
        if (!in_array($mode, ['free','pattern'], true)) $mode = 'free';

        $sender = trim($this->tokens->resolve((string)($config['sender'] ?? ''), $context));
        $body = '';
        $patternCode = '';
        $patternValues = [];

        if ($mode === 'pattern') {
            $patternCode = trim($this->tokens->resolve((string)($config['pattern_code'] ?? $config['body_id'] ?? ''), $context));
            if ($patternCode === '') {
                throw new RuntimeException('SMS pattern/body ID is empty.');
            }

            $resolved = $this->tokens->resolveValue((array)($config['pattern_values'] ?? $config['pattern_args'] ?? []), $context);
            foreach ((array)$resolved as $value) {
                if (is_scalar($value) || $value === null) {
                    $patternValues[] = (string)$value;
                    continue;
                }
                $encoded = wp_json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                $patternValues[] = is_string($encoded) ? $encoded : '';
            }
        } else {
            $body = $this->tokens->resolve((string)($config['body'] ?? $config['text'] ?? ''), $context);
            if (trim($body) === '') {
                throw new RuntimeException('SMS body is empty after token resolution.');
            }
        }

        $providerKey = sanitize_key((string)($config['provider'] ?? SmsProviderRegistry::USE_DEFAULT));
        $provider = $this->providers->resolve($providerKey, $mode);

        $result = $provider->send(new SmsMessage(
            $recipient,
            $mode,
            $body,
            $patternCode,
            $patternValues,
            $sender
        ));

        if (empty($result['success'])) {
            $error = trim((string)($result['error'] ?? 'SMS provider rejected the request.'));
            throw new RuntimeException($error !== '' ? $error : 'SMS provider rejected the request.');
        }
    }

    private function resolveRecipient(ActionContext $context, array $config): string
    {
        $source = sanitize_key((string)($config['recipient_source'] ?? 'field'));

        return match ($source) {
            'manual' => trim($this->tokens->resolve((string)($config['recipient_value'] ?? ''), $context)),
            'token' => trim($this->tokens->resolve((string)($config['recipient_token'] ?? $config['recipient_value'] ?? ''), $context)),
            'user' => $this->recipientFromWordPressUser($context, $config),
            default => $this->recipientFromField($context, (string)($config['recipient_field'] ?? '')),
        };
    }

    private function recipientFromField(ActionContext $context, string $fieldKey): string
    {
        $fieldKey = trim($fieldKey);
        if ($fieldKey === '' || ($context->fieldKeys !== [] && !in_array($fieldKey, $context->fieldKeys, true))) {
            return '';
        }

        $value = $context->data;
        foreach (explode('.', $fieldKey) as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) return '';
            $value = $value[$segment];
        }

        return is_scalar($value) ? trim((string)$value) : '';
    }

    private function recipientFromWordPressUser(ActionContext $context, array $config): string
    {
        $userIdTemplate = (string)($config['recipient_user_id'] ?? '');
        $resolvedUserId = trim($this->tokens->resolve($userIdTemplate, $context));
        $userId = (int)$resolvedUserId;
        if ($userId <= 0) return '';

        $user = get_userdata($userId);
        if (!$user) return '';

        $metaKey = sanitize_key((string)($config['recipient_user_meta'] ?? 'billing_phone'));
        if ($metaKey === '') return '';
        $phone = get_user_meta($userId, $metaKey, true);
        return is_scalar($phone) ? trim((string)$phone) : '';
    }

    private function normalizeIranMobile(string $phone): string
    {
        $phone = strtr(trim($phone), [
            '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
            '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
        ]);
        $phone = preg_replace('/[^0-9+]/u', '', $phone) ?? '';

        if (str_starts_with($phone, '+98')) $phone = '0' . substr($phone, 3);
        elseif (str_starts_with($phone, '0098')) $phone = '0' . substr($phone, 4);
        elseif (str_starts_with($phone, '98') && strlen($phone) === 12) $phone = '0' . substr($phone, 2);
        elseif (str_starts_with($phone, '9') && strlen($phone) === 10) $phone = '0' . $phone;

        return preg_match('/^09\d{9}$/', $phone) === 1 ? $phone : '';
    }
}

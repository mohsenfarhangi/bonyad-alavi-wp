<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions\Sms;

use InvalidArgumentException;

/**
 * Per-form OTP message configuration. Only two server-owned placeholders are
 * expanded; a code must be present exactly once. Never substitute arbitrary
 * submitted values, tokens, tracking codes or applicant attributes.
 */
final class SmsOtpMessageFactory
{
    public const DEFAULT_TEXT = "کد تایید بازیابی فرم: {{code}}\nاعتبار: ۵ دقیقه. این کد را در اختیار دیگران قرار ندهید.";

    /**
     * @param array<string,mixed> $settings
     */
    public static function make(array $settings, string $recipient, string $code): SmsMessage
    {
        if (!preg_match('/^[0-9]{6}$/D', $code)) {
            throw new InvalidArgumentException('کد تأیید نامعتبر است.');
        }
        $mode = (string)($settings['otp_sms_mode'] ?? 'free');
        if (!in_array($mode, ['free','pattern'], true)) {
            throw new InvalidArgumentException('روش ارسال پیامک بازیابی نامعتبر است.');
        }

        if ($mode === 'free') {
            $template = (string)($settings['otp_sms_text'] ?? self::DEFAULT_TEXT);
            self::validateTemplates([$template]);
            if (strlen($template) > 2800) {
                throw new InvalidArgumentException('متن پیامک بازیابی طولانی است.');
            }
            return new SmsMessage($recipient, 'free', self::render($template, $code));
        }

        $bodyId = trim((string)($settings['otp_sms_pattern_code'] ?? ''));
        if (!preg_match('/^[1-9][0-9]{0,9}$/D', $bodyId)) {
            throw new InvalidArgumentException('شناسه پترن / BodyId باید عدد صحیح مثبت باشد.');
        }
        $values = $settings['otp_sms_pattern_values'] ?? ['{{code}}'];
        if (is_string($values)) $values = preg_split('/\r\n|\r|\n/', $values);
        if (!is_array($values) || !array_is_list($values) || $values === [] || count($values) > 10) {
            throw new InvalidArgumentException('تعداد متغیرهای پترن معتبر نیست.');
        }
        foreach ($values as $value) {
            if (!is_string($value) || trim($value) === '' || strlen($value) > 600) {
                throw new InvalidArgumentException('مقدار متغیر پترن خالی یا نامعتبر است.');
            }
        }
        self::validateTemplates($values);
        return new SmsMessage(
            $recipient,
            'pattern',
            '',
            $bodyId,
            array_map(static fn(string $value): string => self::render($value, $code), $values)
        );
    }

    /** @param list<string> $values */
    private static function validateTemplates(array $values): void
    {
        $combined = implode("\n", $values);
        if (substr_count($combined, '{{code}}') !== 1) {
            throw new InvalidArgumentException('در متن یا متغیرهای پترن، {{code}} باید دقیقاً یک بار استفاده شود.');
        }
        $unknown = preg_replace('/\{\{code\}\}|\{\{minutes\}\}/u', '', $combined) ?? $combined;
        if (str_contains($unknown, '{{') || str_contains($unknown, '}}')) {
            throw new InvalidArgumentException('در پیامک بازیابی فقط {{code}} و {{minutes}} پشتیبانی می‌شوند.');
        }
    }

    private static function render(string $template, string $code): string
    {
        return strtr($template, ['{{code}}'=>$code, '{{minutes}}'=>'5']);
    }
}

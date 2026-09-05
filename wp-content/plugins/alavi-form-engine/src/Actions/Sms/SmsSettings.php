<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions\Sms;

/**
 * Normalizes SMS settings across legacy and current AFE option layouts.
 *
 * Current layout: afe_settings['sms']['enabled']
 * Legacy compatibility: afe_settings['sms_enabled']
 */
final class SmsSettings
{
    /** @param array<string,mixed> $settings */
    public static function section(array $settings): array
    {
        return isset($settings['sms']) && is_array($settings['sms']) ? (array)$settings['sms'] : [];
    }

    /** @param array<string,mixed> $settings */
    public static function enabled(array $settings): bool
    {
        $sms = self::section($settings);

        if (array_key_exists('enabled', $sms)) {
            return self::toBool($sms['enabled']);
        }

        if (array_key_exists('sms_enabled', $settings)) {
            return self::toBool($settings['sms_enabled']);
        }

        return false;
    }

    /** @param array<string,mixed> $settings */
    public static function defaultProvider(array $settings): string
    {
        $sms = self::section($settings);
        $key = sanitize_key((string)($sms['default_provider'] ?? $sms['provider'] ?? 'melipayamak'));
        return $key !== '' ? $key : 'melipayamak';
    }

    /** @param mixed $value */
    private static function toBool(mixed $value): bool
    {
        if (is_bool($value)) return $value;
        if (is_int($value) || is_float($value)) return (int)$value !== 0;
        $value = strtolower(trim((string)$value));
        return in_array($value, ['1','true','yes','on','enabled'], true);
    }
}

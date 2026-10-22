<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions\Sms;

/**
 * Bounded, opt-in SMS diagnostics. Never store phone numbers, message text,
 * request bodies, auth tokens, raw provider responses or exception messages.
 * Only whitelisted numeric codes and internal stage names cross this boundary.
 */
final class SmsDebugLog
{
    public const OPTION = 'afe_sms_debug_events';
    private const MAX_EVENTS = 40;
    private const CHANNELS = ['test','action','otp'];
    private const STAGES = ['accepted','rejected','transport','http','invalid_response','validation','unavailable','exception'];

    public static function enabled(): bool
    {
        // AFE supports isolated PHP provider tests without a WordPress bootstrap.
        if (!function_exists('get_option')) return false;
        $settings = (array)get_option('afe_settings', []);
        return !empty($settings['sms']['debug_enabled']);
    }

    /** @return array<string,int|string> */
    public static function describe(string $channel, string $provider, array $result, string $stage = ''): array
    {
        $debug = is_array($result['debug'] ?? null) ? $result['debug'] : [];
        $ok = !empty($result['success']);
        if ($stage === '' && is_string($debug['stage'] ?? null)) $stage = $debug['stage'];
        $stage = in_array($stage, self::STAGES, true) ? $stage : ($ok ? 'accepted' : 'rejected');
        $entry = [
            'at' => gmdate('Y-m-d H:i:s'),
            'channel' => in_array($channel, self::CHANNELS, true) ? $channel : 'action',
            'provider' => sanitize_key($provider),
            'status' => $ok ? 'success' : 'failed',
            'stage' => $stage,
        ];
        foreach (['http_status','ret_status','value_code'] as $key) {
            $value = $debug[$key] ?? null;
            if ((is_int($value) || (is_string($value) && preg_match('/^-?[0-9]{1,4}$/D', $value)))
                && (int)$value >= -999 && (int)$value <= 999) {
                $entry[$key] = (int)$value;
            }
        }
        // Provider receipts are IDs, not response dumps; never return arbitrary
        // provider-supplied text or a freeform error under a diagnostic key.
        $receipt = (string)($result['message_id'] ?? '');
        if ($ok && preg_match('/^[0-9]{8,40}$/D', $receipt)) $entry['message_id'] = $receipt;
        return $entry;
    }

    /** @return array<string,int|string> */
    public static function record(string $channel, string $provider, array $result, string $stage = ''): array
    {
        $entry = self::describe($channel, $provider, $result, $stage);
        if (!self::enabled()) return $entry;
        $items = self::all();
        array_unshift($items, $entry);
        update_option(self::OPTION, array_slice($items, 0, self::MAX_EVENTS), false);
        return $entry;
    }

    /** @return list<array<string,mixed>> */
    public static function all(): array
    {
        $stored = get_option(self::OPTION, []);
        return is_array($stored) ? array_slice(array_values(array_filter($stored, 'is_array')), 0, self::MAX_EVENTS) : [];
    }

    public static function clear(): void
    {
        delete_option(self::OPTION);
    }
}

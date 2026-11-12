<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Context;

use WP_Error;

/**
 * Issues short-lived signed form-context tokens and verifies them on submit.
 *
 * Context is deliberately kept outside applicant field data and submission
 * storage. A provider must re-resolve its canonical values during verification.
 */
final class FormContextManager
{
    private const TOKEN_VERSION = 1;
    private const TOKEN_TTL = 43200; // 12 hours.

    public function __construct(private readonly FormContextRegistry $providers) {}

    /**
     * @return array{token:string,context:array<string,mixed>,provider:string}|WP_Error
     */
    public function issue(string $formSlug, array $settings): array|WP_Error
    {
        $formSlug = sanitize_key($formSlug);
        $providerKey = sanitize_key((string)($settings['provider'] ?? ''));
        $source = sanitize_key((string)($settings['source'] ?? ''));
        $provider = $this->providers->get($providerKey);

        if ($formSlug === '' || !$provider) {
            return new WP_Error('afe_context_provider', 'ارائه‌دهنده Context فرم معتبر نیست.');
        }
        if (!in_array($source, $provider->supportedSources(), true)) {
            return new WP_Error('afe_context_source', 'منبع Context انتخاب‌شده پشتیبانی نمی‌شود.');
        }

        $settings['provider'] = $providerKey;
        $settings['source'] = $source;
        $settings['identifier_type'] = in_array(($settings['identifier_type'] ?? 'slug'), ['id','slug'], true)
            ? (string)($settings['identifier_type'] ?? 'slug')
            : 'slug';

        if ($source === 'query_parameter') {
            $parameter = sanitize_key((string)($settings['query_parameter'] ?? ''));
            if ($parameter === '' || in_array($parameter, ['action','_wpnonce','_afe_nonce','afe_form_slug'], true)) {
                return new WP_Error('afe_context_parameter', 'نام پارامتر URL برای Context معتبر نیست.');
            }
            $settings['query_parameter'] = $parameter;
        }

        if ($source === 'fixed') {
            $fixed = trim((string)($settings['fixed_value'] ?? ''));
            $prefix = $providerKey . '|';
            if (str_starts_with($fixed, $prefix)) $fixed = substr($fixed, strlen($prefix));
            if ($fixed === '') return new WP_Error('afe_context_fixed', 'مقدار ثابت Context انتخاب نشده است.');
            $settings['fixed_value'] = $fixed;
        }

        $resolved = $provider->resolve($settings, $formSlug);
        if (is_wp_error($resolved)) return $resolved;
        $canonical = $provider->validate($resolved, $formSlug);
        if (is_wp_error($canonical)) return $canonical;
        if ($canonical === []) return new WP_Error('afe_context_empty', 'ارائه‌دهنده Context داده معتبری برنگرداند.');

        $issuedAt = time();
        $payload = [
            'v' => self::TOKEN_VERSION,
            'form' => $formSlug,
            'provider' => $providerKey,
            'context' => $canonical,
            'iat' => $issuedAt,
            'exp' => $issuedAt + self::TOKEN_TTL,
        ];

        $json = wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($json) || $json === '') {
            return new WP_Error('afe_context_encode', 'ساخت توکن Context انجام نشد.');
        }

        $encoded = rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
        $secret = $this->secret();
        if ($secret === '') return new WP_Error('afe_context_secret', 'کلید امنیتی WordPress برای Context در دسترس نیست.');

        $signature = hash_hmac('sha256', $encoded, $secret);
        return [
            'token' => $encoded . '.' . $signature,
            'context' => $canonical,
            'provider' => $providerKey,
        ];
    }

    /**
     * Missing tokens mean that the form was rendered without a Context provider.
     *
     * @return array<string,mixed>|WP_Error
     */
    public function verify(string $formSlug, string $token): array|WP_Error
    {
        $token = trim($token);
        if ($token === '') return [];
        if (strlen($token) > 8192) return new WP_Error('afe_context_token_size', 'توکن Context معتبر نیست.');

        $parts = explode('.', $token);
        if (count($parts) !== 2 || $parts[0] === '' || !preg_match('/^[a-f0-9]{64}$/', $parts[1])) {
            return new WP_Error('afe_context_token_format', 'توکن Context معتبر نیست.');
        }

        $secret = $this->secret();
        if ($secret === '') return new WP_Error('afe_context_secret', 'کلید امنیتی WordPress برای Context در دسترس نیست.');
        $expected = hash_hmac('sha256', $parts[0], $secret);
        if (!hash_equals($expected, $parts[1])) {
            return new WP_Error('afe_context_signature', 'اعتبار امنیتی Context تأیید نشد.');
        }

        $base64 = strtr($parts[0], '-_', '+/');
        $base64 .= str_repeat('=', (4 - strlen($base64) % 4) % 4);
        $json = base64_decode($base64, true);
        $payload = is_string($json) ? json_decode($json, true) : null;
        if (!is_array($payload) || (int)($payload['v'] ?? 0) !== self::TOKEN_VERSION) {
            return new WP_Error('afe_context_payload', 'ساختار Context معتبر نیست.');
        }

        $formSlug = sanitize_key($formSlug);
        $issuedAt = (int)($payload['iat'] ?? 0);
        $expiresAt = (int)($payload['exp'] ?? 0);
        $now = time();
        if (($payload['form'] ?? '') !== $formSlug) {
            return new WP_Error('afe_context_form', 'این Context متعلق به فرم دیگری است.');
        }
        if ($issuedAt <= 0 || $issuedAt > $now + 60 || $expiresAt <= $now || $expiresAt <= $issuedAt || ($expiresAt - $issuedAt) > self::TOKEN_TTL + 60) {
            return new WP_Error('afe_context_expired', 'اعتبار Context منقضی شده است؛ صفحه را تازه‌سازی کنید.');
        }

        $providerKey = sanitize_key((string)($payload['provider'] ?? ''));
        $provider = $this->providers->get($providerKey);
        $context = $payload['context'] ?? null;
        if (!$provider || !is_array($context)) {
            return new WP_Error('afe_context_provider', 'ارائه‌دهنده یا داده Context دیگر معتبر نیست.');
        }

        // Never trust the values embedded in the browser token without asking
        // the provider to confirm that the referenced entity is still available.
        return $provider->validate($context, $formSlug);
    }

    private function secret(): string
    {
        if (!function_exists('wp_salt')) return '';
        return (string)wp_salt('auth');
    }
}

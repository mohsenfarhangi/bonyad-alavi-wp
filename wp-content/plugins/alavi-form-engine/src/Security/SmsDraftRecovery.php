<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Security;

use BonyadAlavi\FormEngine\Actions\Sms\SmsMessage;
use BonyadAlavi\FormEngine\Actions\Sms\SmsProviderRegistry;
use BonyadAlavi\FormEngine\Actions\Sms\SmsDebugLog;
use BonyadAlavi\FormEngine\Duplicate\DuplicatePolicy;
use BonyadAlavi\FormEngine\Repository\SubmissionRepository;
use BonyadAlavi\FormEngine\Security\Validators;

/**
 * Opt-in recovery of an unlocked draft using an SMS OTP sent to its stored mobile.
 * No secret, tracking code, submission ID or existence status is disclosed before
 * the OTP is validated. Grants are scoped to one draft and expire after one hour.
 */
final class SmsDraftRecovery
{
    public function __construct(
        private readonly SubmissionRepository $submissions,
        private readonly DuplicatePolicy $duplicates,
        private readonly SmsProviderRegistry $sms
    ) {}

    public static function enabled(array $form): bool
    {
        $settings = (array)($form['settings'] ?? []);
        $duplicate = (array)($settings['duplicate'] ?? []);
        return !empty($settings['save_draft']) && !empty($settings['editing_enabled'])
            && !empty($duplicate['enabled']) && !empty($duplicate['otp_enabled'])
            && (string)($duplicate['otp_mobile_field'] ?? '') !== '';
    }

    public static function normalizeMobile(string $input): string
    {
        $number = preg_replace('/[^0-9+]/', '', Validators::latinDigits($input)) ?? '';
        if (str_starts_with($number, '+98')) $number = '0'.substr($number, 3);
        elseif (str_starts_with($number, '0098')) $number = '0'.substr($number, 4);
        elseif (preg_match('/^98[0-9]{10}$/D', $number)) $number = '0'.substr($number, 2);
        return preg_match('/^09[0-9]{9}$/D', $number) ? $number : '';
    }

    /**
     * Always returns the same public success shape, including for unknown
     * fingerprints and phone numbers, to avoid enumerating registered groups.
     */
    public function start(array $form, array $data, string $mobile): array
    {
        if (!self::enabled($form)) return ['success'=>false, 'message'=>'بازیابی پیامکی برای این فرم فعال نیست.'];

        $slug = (string)$form['slug'];
        $requestId = bin2hex(random_bytes(20));
        $code = (string)random_int(100000, 999999);
        $rowId = 0;
        $candidate = self::normalizeMobile($mobile);
        $config = (array)$form['settings']['duplicate'];
        $field = (string)$config['otp_mobile_field'];
        $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
        $ipKey = 'afe_sms_recovery_ip_'.hash_hmac('sha256', $slug.'|'.$ip, wp_salt('auth'));
        $phoneKey = 'afe_sms_recovery_phone_'.hash_hmac('sha256', $slug.'|'.$candidate, wp_salt('auth'));
        $blocked = (int)get_transient($ipKey) >= 3
            || $candidate === '' || (int)get_transient($phoneKey) >= 3;

        if (!$blocked && $this->duplicates->missingFields($form, $data) === []) {
            $decision = $this->duplicates->evaluate($form, $data);
            if ($decision->isDuplicate() && $decision->duplicateSubmissionId) {
                $row = $this->submissions->find((int)$decision->duplicateSubmissionId);
                $stored = self::normalizeMobile((string)(($row['data'] ?? [])[$field] ?? ''));
                if ($row && (string)($row['form_slug'] ?? '') === $slug
                    && (string)($row['status'] ?? '') === 'draft' && empty($row['is_locked'])
                    && $stored !== '' && hash_equals($stored, $candidate)) {
                    $rowId = (int)$row['id'];
                }
            }
        }

        // Count unknown records and failed lookups identically. A fake challenge
        // keeps verification errors indistinguishable from wrong OTPs.
        if (!$blocked) {
            set_transient($ipKey, (int)get_transient($ipKey)+1, 15*MINUTE_IN_SECONDS);
            set_transient($phoneKey, (int)get_transient($phoneKey)+1, 15*MINUTE_IN_SECONDS);
        }
        $key = $this->challengeKey($requestId);
        // Rate-limited or malformed requests still receive the same public
        // response, but must not allocate unlimited fake transient records.
        if (!$blocked) {
            set_transient($key, [
                'form'=>$slug, 'row_id'=>$rowId, 'attempts'=>0,
                'hash'=>$this->hashCode($requestId, $code),
                'expires'=>time()+5*MINUTE_IN_SECONDS,
            ], 5*MINUTE_IN_SECONDS);
        }

        if ($rowId > 0 && !$blocked) {
            $providerKey = $this->sms->effectiveKey(sanitize_key((string)($config['otp_provider'] ?? 'default')));
            try {
                $provider = $this->sms->resolve($providerKey, 'free');
                // Only the saved mobile is used. Never send a caller-controlled
                // message or echo any provider response to the browser.
                $result = $provider->send(new SmsMessage(
                    $stored, 'free',
                    'کد تایید بازیابی فرم: '.$code."\n".'اعتبار: ۵ دقیقه. این کد را در اختیار دیگران قرار ندهید.'
                ));
                SmsDebugLog::record('otp', $providerKey, $result);
                if (empty($result['success'])) {
                    // A failed delivery must not leave a usable challenge.
                    delete_transient($key);
                }
            } catch (\Throwable) {
                SmsDebugLog::record('otp', $providerKey, ['success'=>false], 'exception');
                delete_transient($key);
            }
        }

        return [
            'success'=>true, 'request_id'=>$requestId,
            'message'=>'اگر اطلاعات ثبت و شماره همراه مطابقت داشته باشند، کد تأیید ارسال می‌شود.',
        ];
    }

    public function verify(array $form, string $requestId, string $otp): array
    {
        $error = ['success'=>false, 'message'=>'کد نامعتبر یا منقضی شده است.'];
        if (!self::enabled($form) || !preg_match('/^[a-f0-9]{40}$/D', $requestId)
            || !preg_match('/^[0-9]{6}$/D', Validators::latinDigits($otp))) return $error;
        $key = $this->challengeKey($requestId);
        $stored = get_transient($key);
        if (!is_array($stored) || ($stored['form'] ?? '') !== (string)$form['slug']) return $error;
        if ((int)($stored['expires'] ?? 0) <= time()) { delete_transient($key); return $error; }
        $attempts = (int)($stored['attempts'] ?? 0)+1;
        if ($attempts > 5) { delete_transient($key); return $error; }
        if (!hash_equals((string)($stored['hash'] ?? ''), $this->hashCode($requestId, Validators::latinDigits($otp)))
            || (int)($stored['row_id'] ?? 0) <= 0) {
            if ($attempts >= 5) delete_transient($key);
            else {
                $stored['attempts'] = $attempts;
                set_transient($key, $stored, max(1, (int)$stored['expires']-time()));
            }
            return $error;
        }

        $row = $this->submissions->find((int)$stored['row_id']);
        if (!$row || (string)($row['form_slug'] ?? '') !== (string)$form['slug']
            || (string)($row['status'] ?? '') !== 'draft' || !empty($row['is_locked'])) {
            delete_transient($key);
            return $error;
        }
        delete_transient($key);
        $token = bin2hex(random_bytes(32));
        set_transient($this->grantKey($token), [
            'form'=>(string)$form['slug'], 'row_id'=>(int)$row['id'],
        ], HOUR_IN_SECONDS);

        $base = remove_query_arg(['afe_edit','afe_tracking','afe_submission','afe_recovery'], wp_get_referer() ?: home_url('/'));
        $url = add_query_arg('afe_recovery', $token, $base);
        return ['success'=>true, 'edit_url'=>$url, 'message'=>'شماره همراه تأیید شد.'];
    }

    public function authorize(array $form, array $row, string $token): bool
    {
        if (!self::enabled($form) || !preg_match('/^[a-f0-9]{64}$/D', $token)) return false;
        $grant = get_transient($this->grantKey($token));
        return is_array($grant)
            && hash_equals((string)($grant['form'] ?? ''), (string)$form['slug'])
            && (int)($grant['row_id'] ?? 0) === (int)($row['id'] ?? 0)
            && (string)($row['status'] ?? '') === 'draft'
            && empty($row['is_locked']);
    }

    public function resolve(array $form, string $token): ?array
    {
        if (!self::enabled($form) || !preg_match('/^[a-f0-9]{64}$/D', $token)) return null;
        $grant = get_transient($this->grantKey($token));
        if (!is_array($grant) || ($grant['form'] ?? '') !== (string)$form['slug']) return null;
        $row = $this->submissions->find((int)($grant['row_id'] ?? 0));
        return $row && $this->authorize($form, $row, $token) ? $row : null;
    }

    private function hashCode(string $requestId, string $code): string
    {
        return hash_hmac('sha256', $requestId.'|'.$code, wp_salt('auth'));
    }

    private function challengeKey(string $id): string { return 'afe_sms_otp_'.hash('sha256', $id); }
    private function grantKey(string $token): string { return 'afe_sms_grant_'.hash('sha256', $token); }
}

<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions\Sms;

/**
 * Farapayamak REST JSON provider (https://rest.payamak-panel.com).
 *
 * Its credentials are separate from the older MeliPayamak adapter and must be
 * decrypted by the AFE SecretStore before constructing this provider.
 * Never expose request bodies, credentials or untrusted raw API responses in
 * Action execution logs.
 */
final class FarapayamakProvider implements SmsProviderInterface
{
    public const KEY = 'farapayamak';
    private const FREE_ENDPOINT = 'https://rest.payamak-panel.com/api/SendSMS/SendSMS';
    private const PATTERN_ENDPOINT = 'https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber';

    public function __construct(private readonly array $settings) {}

    /** @return array{success:bool,message_id?:string,error?:string} */
    public function send(SmsMessage $message): array
    {
        $username = trim((string)($this->settings['username'] ?? ''));
        $apiKey = (string)($this->settings['api_key'] ?? '');
        if ($username === '' || trim($apiKey) === '') {
            return $this->failure('نام کاربری یا کلید API فراپیامک تنظیم نشده است.');
        }

        if (!preg_match('/^09[0-9]{9}$/D', $message->recipient)) {
            return $this->failure('شماره موبایل گیرنده معتبر نیست.');
        }

        $payload = [
            'username' => $username,
            'password' => $apiKey,
        ];

        if ($message->mode === 'free') {
            $sender = trim($message->sender !== '' ? $message->sender : (string)($this->settings['sender'] ?? ''));
            if ($sender === '') return $this->failure('شماره فرستنده فراپیامک تنظیم نشده است.');
            if (trim($message->body) === '') return $this->failure('متن پیامک خالی است.');
            $payload += [
                'to' => $message->recipient,
                'from' => $sender,
                'text' => $message->body,
                'isflash' => false,
            ];
            $endpoint = self::FREE_ENDPOINT;
        } elseif ($message->mode === 'pattern') {
            $bodyId = trim($message->patternCode);
            if ($bodyId === '' || !ctype_digit($bodyId) || (int)$bodyId <= 0
                || (string)(int)$bodyId !== ltrim($bodyId, '0')) {
                return $this->failure('شناسه متن خدماتی (bodyId) باید عدد صحیح مثبت باشد.');
            }
            // BaseServiceNumber accepts a single text string, not an args array.
            // The ordered Action pattern values follow the legacy service's
            // semicolon-separated substitution convention.
            $text = implode(';', array_values($message->patternValues));
            if (trim($text) === '') return $this->failure('مقادیر متن خدماتی خالی است.');
            $payload += [
                'text' => $text,
                'to' => $message->recipient,
                'bodyId' => (int)$bodyId,
            ];
            $endpoint = self::PATTERN_ENDPOINT;
        } else {
            return $this->failure('روش ارسال پیامک فراپیامک پشتیبانی نمی‌شود.');
        }

        $body = wp_json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if (!is_string($body)) return $this->failure('امکان ساخت درخواست فراپیامک وجود ندارد.');

        $response = wp_remote_post($endpoint, [
            'timeout' => 20,
            'redirection' => 0,
            'sslverify' => true,
            'headers' => ['Content-Type' => 'application/json; charset=utf-8'],
            'body' => $body,
        ]);

        if (is_wp_error($response)) {
            // Transport errors may include URLs or echoed request details.
            return $this->failure('ارتباط با سرویس فراپیامک برقرار نشد.');
        }

        $http = (int)wp_remote_retrieve_response_code($response);
        if ($http < 200 || $http >= 300) {
            return $this->failure('سرویس فراپیامک پاسخ HTTP ناموفق داد (' . $http . ').');
        }

        $decoded = json_decode((string)wp_remote_retrieve_body($response), true);
        if (!is_array($decoded) || !array_key_exists('RetStatus', $decoded) || !array_key_exists('Value', $decoded)) {
            return $this->failure('ساختار پاسخ فراپیامک معتبر نیست.');
        }

        $status = $decoded['RetStatus'];
        $value = $decoded['Value'];
        if (!is_numeric($status) || !is_scalar($value)) {
            return $this->failure('ساختار وضعیت پاسخ فراپیامک معتبر نیست.');
        }
        $code = trim((string)$value);
        $knownErrors = [
            '-112'=>'دسترسی وب‌سرویس غیرفعال است.', '-111'=>'IP سرور مجاز نیست.',
            '-110'=>'ارسال کلید API الزامی است.', '-109'=>'تنظیم IP مجاز الزامی است.',
            '-108'=>'IP سرور مسدود شده است.', '-100'=>'پارامتر نامعتبر یا خطای داخلی.',
            '-10'=>'ارسال لینک در متغیرهای الگو مجاز نیست.',
            '-6'=>'خطای داخلی سرویس.', '-5'=>'متغیرهای الگو با متن تأییدشده سازگار نیست.',
            '-4'=>'شناسه متن خدماتی معتبر یا تأییدشده نیست.',
            '-3'=>'خط ارسال تعریف نشده است.', '-2'=>'برای ارسال خدماتی فقط یک گیرنده مجاز است.',
            '-1'=>'دسترسی به سرویس خدماتی محدود است.',
            '0'=>'نام کاربری یا کلید API نامعتبر است.',
            '2'=>'اعتبار حساب کافی نیست.', '3'=>'سقف ارسال روزانه تکمیل شده است.',
            '4'=>'محدودیت حجم ارسال.', '5'=>'شماره فرستنده نامعتبر است.',
            '6'=>'ارسال از خط مجاز نیست یا سامانه در حال به‌روزرسانی است.',
            '7'=>'متن حاوی کلمه فیلترشده است.',
            '9'=>'ارسال از خط عمومی مجاز نیست.', '10'=>'حساب غیرفعال است.',
            '11'=>'ارسال انجام نشد.', '12'=>'مدارک حساب کامل نیست.',
            '14'=>'ارسال لینک در متن مجاز نیست.', '15'=>'عبارت لغو الزامی است.',
            '16'=>'گیرنده‌ای یافت نشد.', '17'=>'متن پیامک خالی است.',
            '18'=>'شماره گیرنده نامعتبر است.', '19'=>'سقف ارسال API تکمیل شده است.',
        ];

        // RetStatus is necessary but not sufficient. HTTP 200 and RetStatus=1
        // can still accompany an application-level Value error (e.g. -1).
        if ((int)$status !== 1 || $code === '' || isset($knownErrors[$code])
            || !preg_match('/^[0-9]+(?:[,;][0-9]+)*$/D', $code)) {
            $reason = $knownErrors[$code] ?? 'پاسخ سرویس نشان‌دهنده ارسال موفق نیست.';
            return $this->failure('فراپیامک: ' . $reason . ' (RetStatus ' . (int)$status . ').');
        }

        // Value=1 is a documented generic success code, not a receipt ID.
        return $code === '1'
            ? ['success'=>true]
            : ['success'=>true, 'message_id'=>$code];
    }

    /** @return array{success:false,error:string} */
    private function failure(string $reason): array
    {
        return ['success'=>false, 'error'=>$reason];
    }
}

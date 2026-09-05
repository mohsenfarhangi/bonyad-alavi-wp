<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Actions\Sms;

use Throwable;

/**
 * Adapter for the public API of the "Persian WooCommerce SMS" plugin.
 *
 * Credentials and gateway settings are never copied into AFE. The adapter asks
 * PWSMS() for the currently active gateway and delegates sending through the
 * plugin's public send_sms() method, so changing the gateway there is picked up
 * automatically by AFE.
 */
final class PersianWooCommerceSmsProvider implements SmsProviderInterface
{
    public const KEY = 'persian_woocommerce_sms';

    public function isAvailable(): bool
    {
        if (!function_exists('PWSMS')) return false;
        try {
            $helper = PWSMS();
        } catch (Throwable) {
            return false;
        }
        return is_object($helper)
            && method_exists($helper, 'send_sms')
            && method_exists($helper, 'get_sms_gateway');
    }

    /** @return list<string> */
    public function supportedModes(): array
    {
        if (!$this->isAvailable()) return [];
        $modes = ['free'];
        $gateway = $this->gateway();
        if ($gateway !== null && $this->patternStrategy($gateway) !== '') $modes[] = 'pattern';

        if (function_exists('apply_filters')) {
            $modes = (array)apply_filters('afe_pwsms_supported_modes', $modes, $this->gatewayInfo($gateway), $gateway);
        }
        $modes = array_values(array_unique(array_filter(array_map('sanitize_key', $modes))));
        return array_values(array_intersect($modes, ['free','pattern']));
    }

    /** @return array<string,mixed> */
    public function status(): array
    {
        $gateway = $this->gateway();
        $info = $this->gatewayInfo($gateway);
        return [
            'available'=>$this->isAvailable(),
            'plugin_version'=>defined('PWSMS_VERSION') ? (string)PWSMS_VERSION : '',
            'gateway'=>$info,
            'modes'=>$this->supportedModes(),
        ];
    }

    public function send(SmsMessage $message): array
    {
        if (!$this->isAvailable()) {
            return ['success'=>false, 'error'=>'افزونه Persian WooCommerce SMS فعال نیست یا API عمومی PWSMS() در دسترس نیست.'];
        }

        $gateway = $this->gateway();
        if ($gateway === null) {
            return ['success'=>false, 'error'=>'درگاه فعال Persian WooCommerce SMS قابل تشخیص نیست. تنظیمات وب‌سرویس آن افزونه را بررسی کنید.'];
        }

        if (!in_array($message->mode, $this->supportedModes(), true)) {
            $name = (string)($this->gatewayInfo($gateway)['name'] ?? get_class($gateway));
            return ['success'=>false, 'error'=>'درگاه فعال Persian WooCommerce SMS «'.$name.'» از روش '.$message->mode.' در این Integration پشتیبانی نمی‌کند.'];
        }

        if ($message->mode === 'pattern') {
            $payloadMessage = $this->patternPayload($gateway, $message);
            if ($payloadMessage === '') {
                return ['success'=>false, 'error'=>'فرمت Pattern برای درگاه فعال Persian WooCommerce SMS شناخته‌شده نیست.'];
            }
        } else {
            $payloadMessage = $message->body;
        }

        $data = [
            'mobile'=>$message->recipient,
            'message'=>$payloadMessage,
            'type'=>'afe',
            // Rich metadata for newer/extended gateways. Older PWSMS gateways
            // ignore extra keys and continue using mobile/message.
            'afe_source'=>'alavi_form_engine',
            'afe_mode'=>$message->mode,
            'pattern_code'=>$message->patternCode,
            'body_id'=>$message->patternCode,
            'pattern_values'=>array_values($message->patternValues),
            'pattern_args'=>array_values($message->patternValues),
        ];
        if (function_exists('apply_filters')) {
            $data = (array)apply_filters('afe_pwsms_send_data', $data, $message, $this->gatewayInfo($gateway), $gateway);
        }

        try {
            $helper = PWSMS();
            $result = $helper->send_sms($data);
        } catch (Throwable $e) {
            return ['success'=>false, 'error'=>'خطا در Persian WooCommerce SMS: '.$e->getMessage()];
        }

        if ($result === true) {
            return ['success'=>true, 'raw'=>['provider'=>self::KEY,'gateway'=>$this->gatewayInfo($gateway)]];
        }

        $error = $this->resultMessage($result);
        return [
            'success'=>false,
            'error'=>$error !== '' ? $error : 'Persian WooCommerce SMS ارسال را ناموفق اعلام کرد.',
            'raw'=>['provider'=>self::KEY,'gateway'=>$this->gatewayInfo($gateway),'result'=>$this->safeValue($result)],
        ];
    }

    private function gateway(): ?object
    {
        if (!$this->isAvailable()) return null;
        try {
            $helper = PWSMS();
            $gateway = $helper->get_sms_gateway();
        } catch (Throwable) {
            return null;
        }
        if (!is_object($gateway)) return null;
        $class = strtolower(get_class($gateway));
        if (str_ends_with($class, '\\logger') || $class === 'logger') return null;
        return $gateway;
    }

    /** @return array{class:string,id:string,name:string,strategy:string} */
    private function gatewayInfo(?object $gateway): array
    {
        if ($gateway === null) return ['class'=>'','id'=>'','name'=>'','strategy'=>''];
        $class = get_class($gateway);
        $id = '';
        $name = '';
        try {
            if (is_callable([$class,'id'])) $id = (string)$class::id();
            if (is_callable([$class,'name'])) $name = (string)$class::name();
        } catch (Throwable) {}
        return ['class'=>$class,'id'=>$id,'name'=>$name,'strategy'=>$this->patternStrategy($gateway)];
    }

    private function patternStrategy(object $gateway): string
    {
        $class = strtolower(get_class($gateway));
        $id = '';
        $name = '';
        try {
            if (is_callable([get_class($gateway),'id'])) $id = strtolower((string)get_class($gateway)::id());
            if (is_callable([get_class($gateway),'name'])) $name = strtolower((string)get_class($gateway)::name());
        } catch (Throwable) {}
        $signature = $class.' '.$id.' '.$name;

        $strategy = '';
        if (str_contains($signature, 'melipayamakpattern') || (str_contains($signature, 'melipayamak') && (str_contains($signature, 'خدماتی') || str_contains($signature, 'ترکیبی') || str_contains($signature, 'combined')))) {
            $strategy = 'melipayamak_pattern';
        } elseif (str_contains($signature, 'kavenegar') && (str_contains($signature, 'lookup') || str_contains($signature, 'look_up'))) {
            $strategy = 'kavenegar_lookup';
        }

        if (function_exists('apply_filters')) {
            $strategy = sanitize_key((string)apply_filters('afe_pwsms_pattern_strategy', $strategy, $this->gatewayInfoWithoutStrategy($gateway), $gateway));
        }
        return $strategy;
    }

    /** @return array{class:string,id:string,name:string} */
    private function gatewayInfoWithoutStrategy(object $gateway): array
    {
        $class = get_class($gateway);
        $id = '';
        $name = '';
        try {
            if (is_callable([$class,'id'])) $id = (string)$class::id();
            if (is_callable([$class,'name'])) $name = (string)$class::name();
        } catch (Throwable) {}
        return ['class'=>$class,'id'=>$id,'name'=>$name];
    }

    private function patternPayload(object $gateway, SmsMessage $message): string
    {
        $strategy = $this->patternStrategy($gateway);
        $payload = '';

        if ($strategy === 'melipayamak_pattern') {
            $payload = $message->patternCode.'@';
            if ($message->patternValues !== []) $payload .= implode('##', array_values($message->patternValues)).'##';
            $payload .= 'shared';
        } elseif ($strategy === 'kavenegar_lookup') {
            if (count($message->patternValues) > 5) return '';
            $tokenNames = ['token','token2','token3','token10','token20'];
            $payload = 'template='.$message->patternCode;
            foreach (array_values($message->patternValues) as $index => $value) {
                if (!isset($tokenNames[$index])) break;
                $payload .= $tokenNames[$index].'='.$value;
            }
        }

        if (function_exists('apply_filters')) {
            $filtered = apply_filters('afe_pwsms_pattern_payload', $payload, $message, $this->gatewayInfoWithoutStrategy($gateway), $gateway, $strategy);
            if (is_string($filtered)) $payload = $filtered;
        }
        return trim($payload);
    }

    private function resultMessage(mixed $result): string
    {
        if (is_string($result)) return trim($result);
        if ($result === false) return 'Persian WooCommerce SMS ارسال را ناموفق اعلام کرد.';
        if ($result === null) return 'Persian WooCommerce SMS پاسخ معتبری برنگرداند.';
        if (is_scalar($result)) return trim((string)$result);
        $json = wp_json_encode($result, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return is_string($json) ? $json : '';
    }

    private function safeValue(mixed $value): mixed
    {
        if (is_scalar($value) || $value === null || is_array($value)) return $value;
        if ($value instanceof \JsonSerializable) return $value->jsonSerialize();
        return get_debug_type($value);
    }
}

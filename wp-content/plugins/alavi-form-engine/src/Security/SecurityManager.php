<?php
declare(strict_types=1);

namespace BonyadAlavi\FormEngine\Security;

use WP_Error;

final class SecurityManager
{
    public function verifyRequest(string $formSlug, int $limit = 10): true|WP_Error
    {
        if (!isset($_POST['_afe_nonce']) || !wp_verify_nonce(sanitize_text_field(wp_unslash($_POST['_afe_nonce'])), 'afe_submit_' . $formSlug)) {
            return new WP_Error('csrf', 'اعتبار درخواست منقضی شده است. صفحه را تازه‌سازی کنید.');
        }
        if (!empty($_POST['_afe_website'])) {
            return new WP_Error('spam', 'درخواست نامعتبر است.');
        }
        if (!$this->rateLimit($formSlug, $limit)) {
            return new WP_Error('rate_limit', 'تعداد درخواست‌ها بیش از حد مجاز است. کمی بعد دوباره تلاش کنید.');
        }
        return true;
    }

    private function rateLimit(string $formSlug, int $limit): bool
    {
        $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
        $key = 'afe_rl_' . md5($formSlug . '|' . $ip);
        $count = (int)get_transient($key);
        if ($count >= max(1,$limit)) return false;
        set_transient($key, $count+1, MINUTE_IN_SECONDS * 10);
        return true;
    }

    public function captchaMarkup(string $provider): string
    {
        if ($provider === 'none') return '';
        if ($provider === 'google') {
            $settings = get_option('afe_settings', []);
            $siteKey = (string)($settings['recaptcha_site_key'] ?? '');
            if ($siteKey === '') return '<div class="afe-notice afe-notice-warning">کلید Site Key گوگل reCAPTCHA تنظیم نشده است.</div>';
            wp_enqueue_script('google-recaptcha', 'https://www.google.com/recaptcha/api.js?hl=fa', [], null, true);
            return '<div class="g-recaptcha" data-sitekey="'.esc_attr($siteKey).'"></div>';
        }

        $challenge = $this->newCustomCaptchaChallenge();
        return '<div class="afe-captcha" data-afe-captcha>'
             . '<div class="afe-captcha__head"><label>برای اطمینان از انسانی بودن: <span data-afe-captcha-question>'.esc_html($challenge['question']).'</span></label>'
             . '<button type="button" class="afe-captcha__refresh" data-afe-captcha-refresh aria-label="تولید کپچای جدید" title="تولید کپچای جدید">↻ <span>کپچای جدید</span></button></div>'
             . '<input type="number" name="_afe_captcha_answer" required inputmode="numeric" autocomplete="off">'
             . '<input type="hidden" name="_afe_captcha_token" value="'.esc_attr($challenge['token']).'"></div>';
    }

    /** @return array{question:string,token:string} */
    public function newCustomCaptchaChallenge(): array
    {
        $a = random_int(1, 9);
        $b = random_int(1, 9);
        $token = wp_generate_password(24, false, false);
        set_transient('afe_captcha_' . hash('sha256',$token), (string)($a+$b), 15*MINUTE_IN_SECONDS);
        return ['question'=>$a.' + '.$b.' = ؟','token'=>$token];
    }

    public function verifyCaptcha(string $provider): true|WP_Error
    {
        if ($provider === 'none') return true;
        if ($provider === 'google') {
            $settings = get_option('afe_settings', []);
            $secret = (string)($settings['recaptcha_secret_key'] ?? '');
            $response = sanitize_text_field(wp_unslash($_POST['g-recaptcha-response'] ?? ''));
            if ($secret === '' || $response === '') return new WP_Error('captcha', 'کپچا تکمیل نشده است.');
            $remote = wp_remote_post('https://www.google.com/recaptcha/api/siteverify', [
                'timeout'=>10,
                'body'=>['secret'=>$secret,'response'=>$response,'remoteip'=>$_SERVER['REMOTE_ADDR']??''],
            ]);
            if (is_wp_error($remote)) return new WP_Error('captcha_http','اعتبارسنجی کپچا در دسترس نیست.');
            $json = json_decode(wp_remote_retrieve_body($remote), true);
            return !empty($json['success']) ? true : new WP_Error('captcha','کپچا نامعتبر است.');
        }
        $token = sanitize_text_field(wp_unslash($_POST['_afe_captcha_token'] ?? ''));
        $answer = sanitize_text_field(wp_unslash($_POST['_afe_captcha_answer'] ?? ''));
        if ($token === '') return new WP_Error('captcha','کپچا نامعتبر است.');
        $key = 'afe_captcha_' . hash('sha256',$token);
        $expected = get_transient($key);
        delete_transient($key);
        return $expected !== false && hash_equals((string)$expected, (string)$answer)
            ? true : new WP_Error('captcha','پاسخ کپچا صحیح نیست.');
    }
}

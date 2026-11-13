<?php
declare(strict_types=1);

defined('ABSPATH') || exit;

/**
 * Keeps Quick Support gateway returns on the page that hosted the AFE form
 * and replaces that form with an editable, status-specific payment result.
 */
final class BA_Quick_Support_Payment_Return
{
    private const FORM_SLUG = 'quick-support-payment';
    private const QUERY_FLAG = 'ba_qs_payment_result';
    private const QUERY_ORDER = 'ba_qs_order_id';
    private const QUERY_TOKEN = 'ba_qs_token';

    public static function boot(): void
    {
        add_filter('afe_form_render_override', [self::class, 'renderResult'], 10, 4);
        add_filter('afe_form_payment_result_template_definitions', [self::class, 'templateDefinitions'], 10, 4);

        add_filter('woocommerce_get_return_url', [self::class, 'filterReturnUrl'], 20, 2);
        add_filter('woocommerce_get_checkout_order_received_url', [self::class, 'filterReturnUrl'], 20, 2);
        add_filter('woocommerce_get_checkout_payment_url', [self::class, 'filterReturnUrl'], 20, 2);
        add_filter('woocommerce_get_cancel_order_url', [self::class, 'filterCancelUrl'], 20, 3);
        add_filter('woocommerce_get_cancel_order_url_raw', [self::class, 'filterCancelUrl'], 20, 3);
    }

    /**
     * @return array<string,array{label:string,description:string,default:string,tokens:list<string>,rows:int}>
     */
    public static function templateDefinitions($definitions, string $slug, array $form = [], array $settings = []): array
    {
        if ($slug !== self::FORM_SLUG) return is_array($definitions) ? $definitions : [];

        $tokens = [
            '{{order_number}}',
            '{{amount}}',
            '{{category}}',
            '{{transaction_id}}',
            '{{status}}',
            '{{date}}',
            '{{message}}',
            '{{retry_url}}',
        ];

        return [
            'success' => [
                'label' => 'پرداخت موفق',
                'description' => 'زمانی نمایش داده می‌شود که وضعیت سفارش در ووکامرس پرداخت‌شده باشد.',
                'default' => <<<'HTML'
<section class="ba-quick-support-payment-result ba-quick-support-payment-result--success" role="status">
    <div class="ba-quick-support-payment-result__icon" aria-hidden="true">✓</div>
    <div class="ba-quick-support-payment-result__body">
        <span class="ba-quick-support-payment-result__eyebrow">پرداخت موفق</span>
        <h2>حمایت شما با موفقیت ثبت شد</h2>
        <p>{{message}}</p>
        <dl class="ba-quick-support-payment-result__details">
            <div><dt>موضوع حمایت</dt><dd>{{category}}</dd></div>
            <div><dt>مبلغ پرداختی</dt><dd>{{amount}}</dd></div>
            <div><dt>شماره سفارش</dt><dd>{{order_number}}</dd></div>
            <div><dt>شماره تراکنش</dt><dd>{{transaction_id}}</dd></div>
        </dl>
    </div>
</section>
HTML,
                'tokens' => $tokens,
                'rows' => 11,
            ],
            'failed' => [
                'label' => 'پرداخت ناموفق',
                'description' => 'برای سفارش‌هایی که درگاه یا ووکامرس وضعیت آن‌ها را ناموفق ثبت کرده است.',
                'default' => <<<'HTML'
<section class="ba-quick-support-payment-result ba-quick-support-payment-result--failed" role="alert">
    <div class="ba-quick-support-payment-result__icon" aria-hidden="true">!</div>
    <div class="ba-quick-support-payment-result__body">
        <span class="ba-quick-support-payment-result__eyebrow">پرداخت ناموفق</span>
        <h2>پرداخت شما تأیید نشد</h2>
        <p>{{message}}</p>
        <dl class="ba-quick-support-payment-result__details">
            <div><dt>موضوع حمایت</dt><dd>{{category}}</dd></div>
            <div><dt>مبلغ</dt><dd>{{amount}}</dd></div>
            <div><dt>شماره سفارش</dt><dd>{{order_number}}</dd></div>
        </dl>
        <a class="ba-quick-support-payment-result__retry" href="{{retry_url}}">بازگشت و تلاش مجدد</a>
    </div>
</section>
HTML,
                'tokens' => $tokens,
                'rows' => 10,
            ],
            'cancelled' => [
                'label' => 'پرداخت لغوشده',
                'description' => 'زمانی نمایش داده می‌شود که سفارش توسط کاربر یا درگاه لغو شده باشد.',
                'default' => <<<'HTML'
<section class="ba-quick-support-payment-result ba-quick-support-payment-result--cancelled" role="status">
    <div class="ba-quick-support-payment-result__icon" aria-hidden="true">×</div>
    <div class="ba-quick-support-payment-result__body">
        <span class="ba-quick-support-payment-result__eyebrow">پرداخت لغوشده</span>
        <h2>فرآیند پرداخت لغو شد</h2>
        <p>{{message}}</p>
        <dl class="ba-quick-support-payment-result__details">
            <div><dt>موضوع حمایت</dt><dd>{{category}}</dd></div>
            <div><dt>مبلغ</dt><dd>{{amount}}</dd></div>
            <div><dt>شماره سفارش</dt><dd>{{order_number}}</dd></div>
        </dl>
        <a class="ba-quick-support-payment-result__retry" href="{{retry_url}}">بازگشت به فرم پرداخت</a>
    </div>
</section>
HTML,
                'tokens' => $tokens,
                'rows' => 10,
            ],
            'pending' => [
                'label' => 'در انتظار تأیید',
                'description' => 'برای زمانی که هنوز پاسخ نهایی بانک در وضعیت سفارش ثبت نشده است.',
                'default' => <<<'HTML'
<section class="ba-quick-support-payment-result ba-quick-support-payment-result--pending" role="status">
    <div class="ba-quick-support-payment-result__icon" aria-hidden="true">…</div>
    <div class="ba-quick-support-payment-result__body">
        <span class="ba-quick-support-payment-result__eyebrow">در انتظار تأیید</span>
        <h2>وضعیت پرداخت هنوز نهایی نشده است</h2>
        <p>{{message}}</p>
        <dl class="ba-quick-support-payment-result__details">
            <div><dt>مبلغ</dt><dd>{{amount}}</dd></div>
            <div><dt>شماره سفارش</dt><dd>{{order_number}}</dd></div>
            <div><dt>وضعیت سفارش</dt><dd>{{status}}</dd></div>
        </dl>
        <p class="ba-quick-support-payment-result__hint">لطفاً چند لحظه دیگر همین صفحه را تازه‌سازی کنید. تا مشخص‌شدن نتیجه، پرداخت را دوباره انجام ندهید.</p>
    </div>
</section>
HTML,
                'tokens' => $tokens,
                'rows' => 10,
            ],
            'unknown' => [
                'label' => 'وضعیت نامشخص',
                'description' => 'برای وضعیت‌هایی که در دسته موفق، ناموفق، لغوشده یا در انتظار قرار نمی‌گیرند.',
                'default' => <<<'HTML'
<section class="ba-quick-support-payment-result ba-quick-support-payment-result--unknown" role="status">
    <div class="ba-quick-support-payment-result__icon" aria-hidden="true">?</div>
    <div class="ba-quick-support-payment-result__body">
        <span class="ba-quick-support-payment-result__eyebrow">وضعیت نامشخص</span>
        <h2>نتیجه پرداخت هنوز قابل تشخیص نیست</h2>
        <p>{{message}}</p>
        <dl class="ba-quick-support-payment-result__details">
            <div><dt>مبلغ</dt><dd>{{amount}}</dd></div>
            <div><dt>شماره سفارش</dt><dd>{{order_number}}</dd></div>
            <div><dt>وضعیت سفارش</dt><dd>{{status}}</dd></div>
        </dl>
        <p class="ba-quick-support-payment-result__hint">در صورت کسر وجه از حساب، از ثبت پرداخت مجدد خودداری کنید تا وضعیت تراکنش بررسی شود.</p>
    </div>
</section>
HTML,
                'tokens' => $tokens,
                'rows' => 10,
            ],
        ];
    }

    public static function filterReturnUrl($url, $order)
    {
        if (!self::isQuickSupportOrder($order)) return $url;

        $resultUrl = self::resultUrl($order);
        return $resultUrl !== '' ? $resultUrl : $url;
    }

    public static function filterCancelUrl($url, $order, $redirect = '')
    {
        if (!self::isQuickSupportOrder($order)) return $url;

        $resultUrl = self::resultUrl($order);
        if ($resultUrl === '' || !is_string($url) || $url === '') return $url;

        // WooCommerce's own cancel handler updates the order first, then sends
        // the browser back to this signed, same-site result URL.
        return add_query_arg('redirect', $resultUrl, $url);
    }

    public static function renderResult($original, string $slug, array $form, array $formContext = [])
    {
        if ($slug !== self::FORM_SLUG) return $original;

        $flagValue = $_GET[self::QUERY_FLAG] ?? '';
        if (!is_scalar($flagValue) || (string)wp_unslash((string)$flagValue) !== '1') return $original;

        $orderValue = $_GET[self::QUERY_ORDER] ?? 0;
        $tokenValue = $_GET[self::QUERY_TOKEN] ?? '';
        if (!is_scalar($orderValue) || !is_scalar($tokenValue)) return $original;

        $orderId = absint(wp_unslash((string)$orderValue));
        $token = sanitize_text_field(wp_unslash((string)$tokenValue));
        if ($orderId <= 0 || $token === '' || !function_exists('wc_get_order')) return $original;

        $order = wc_get_order($orderId);
        if (!self::isQuickSupportOrder($order)) return $original;

        $storedToken = (string)$order->get_meta(BA_Quick_Support_Payment_Service::ORDER_RETURN_TOKEN, true);
        if ($storedToken === '' || !hash_equals($storedToken, $token)) return $original;

        $returnUrl = self::safeReturnUrl((string)$order->get_meta(BA_Quick_Support_Payment_Service::ORDER_RETURN_URL, true));
        if ($returnUrl === '') return $original;

        if (function_exists('nocache_headers')) nocache_headers();
        wp_enqueue_style('afe-frontend');

        $customCss = self::sanitizeCss((string)($form['settings']['payment_result_css'] ?? ''));
        $css = self::defaultCss();
        if ($customCss !== '') $css .= "\n" . $customCss;
        wp_add_inline_style('afe-frontend', $css);

        $statusKey = self::statusKey($order);
        $definitions = self::templateDefinitions([], self::FORM_SLUG, $form, (array)($form['settings'] ?? []));
        $defaultTemplate = (string)($definitions[$statusKey]['default'] ?? $definitions['unknown']['default']);
        $customTemplates = (array)($form['settings']['payment_result_templates'] ?? []);
        $template = trim((string)($customTemplates[$statusKey] ?? ''));
        if ($template === '') $template = $defaultTemplate;

        $statusName = function_exists('wc_get_order_status_name')
            ? (string)wc_get_order_status_name((string)$order->get_status())
            : (string)$order->get_status();

        $created = $order->get_date_created();
        $date = $created && function_exists('wc_format_datetime')
            ? (string)wc_format_datetime($created, 'Y/m/d H:i')
            : '';

        $messages = [
            'success' => 'بانک پرداخت را تأیید کرده است. از همراهی شما در حمایت از این طرح سپاسگزاریم.',
            'failed' => 'بانک پرداخت را تأیید نکرده است. در صورت تمایل می‌توانید دوباره تلاش کنید.',
            'cancelled' => 'پرداخت تکمیل نشد و سفارش لغو شده است. در صورت تمایل می‌توانید دوباره اقدام کنید.',
            'pending' => 'هنوز پاسخ نهایی از بانک دریافت نشده است. وضعیت سفارش پس از دریافت پاسخ به‌روزرسانی می‌شود.',
            'unknown' => 'وضعیت سفارش در حال حاضر «' . $statusName . '» است و نتیجه قطعی پرداخت مشخص نیست.',
        ];

        $amount = function_exists('wc_price')
            ? wp_kses_post(wc_price((float)$order->get_total(), ['currency' => $order->get_currency()]))
            : esc_html((string)$order->get_total());

        $tokens = [
            '{{order_number}}' => esc_html((string)$order->get_order_number()),
            '{{amount}}' => $amount,
            '{{category}}' => esc_html((string)$order->get_meta(BA_Quick_Support_Payment_Service::ORDER_CATEGORY_TITLE, true)),
            '{{transaction_id}}' => esc_html((string)($order->get_transaction_id() ?: '—')),
            '{{status}}' => esc_html($statusName),
            '{{date}}' => esc_html($date),
            '{{message}}' => esc_html((string)($messages[$statusKey] ?? $messages['unknown'])),
            '{{retry_url}}' => esc_url($returnUrl),
        ];

        $html = strtr(wp_kses_post($template), $tokens);
        $html = wp_kses_post($html);

        return '<div class="afe-shell afe-isolation-strong ba-quick-support-payment-return" dir="rtl">' . $html . '</div>';
    }

    private static function isQuickSupportOrder($order): bool
    {
        return is_object($order)
            && method_exists($order, 'get_meta')
            && (string)$order->get_meta(BA_Quick_Support_Payment_Service::ORDER_MARKER, true) === '1';
    }

    private static function statusKey($order): string
    {
        if (method_exists($order, 'is_paid') && $order->is_paid()) return 'success';
        if (method_exists($order, 'has_status') && $order->has_status('cancelled')) return 'cancelled';
        if (method_exists($order, 'has_status') && $order->has_status('failed')) return 'failed';
        if (method_exists($order, 'has_status') && $order->has_status(['pending', 'on-hold'])) return 'pending';
        return 'unknown';
    }

    private static function resultUrl($order): string
    {
        $base = self::safeReturnUrl((string)$order->get_meta(BA_Quick_Support_Payment_Service::ORDER_RETURN_URL, true));
        $token = (string)$order->get_meta(BA_Quick_Support_Payment_Service::ORDER_RETURN_TOKEN, true);
        if ($base === '' || $token === '') return '';

        return add_query_arg([
            self::QUERY_FLAG => '1',
            self::QUERY_ORDER => (string)$order->get_id(),
            self::QUERY_TOKEN => $token,
        ], $base);
    }

    private static function safeReturnUrl(string $url): string
    {
        $url = trim($url);
        if ($url === '') return '';

        $url = wp_validate_redirect($url, '');
        if (!is_string($url) || $url === '') return '';

        $host = strtolower((string)wp_parse_url($url, PHP_URL_HOST));
        $siteHost = strtolower((string)wp_parse_url(home_url('/'), PHP_URL_HOST));
        if ($host === '' || $siteHost === '' || $host !== $siteHost) return '';

        $path = (string)wp_parse_url($url, PHP_URL_PATH);
        if (str_contains($path, '/wp-admin/') || str_contains($path, '/wp-login.php')) return '';

        return remove_query_arg([self::QUERY_FLAG, self::QUERY_ORDER, self::QUERY_TOKEN], $url);
    }

    private static function sanitizeCss(string $css): string
    {
        $css = wp_strip_all_tags($css);
        $css = preg_replace('/@import\b|url\s*\([^)]*\)|expression\s*\([^)]*\)|javascript\s*:/i', '', $css) ?? $css;
        return trim($css);
    }

    private static function defaultCss(): string
    {
        return <<<'CSS'
.ba-quick-support-payment-return{width:100%;max-width:780px;margin:0 auto}
.ba-quick-support-payment-result{display:grid;grid-template-columns:58px minmax(0,1fr);gap:18px;align-items:start;padding:28px;border:1px solid #e1e9e5;border-radius:14px;background:#fff;color:#243b32;box-shadow:0 8px 28px rgba(21,55,41,.06)}
.ba-quick-support-payment-result__icon{display:flex;align-items:center;justify-content:center;width:54px;height:54px;border-radius:50%;font-size:27px;font-weight:700;background:#edf4f0;color:#176b4c}
.ba-quick-support-payment-result__eyebrow{display:inline-block;margin:0 0 8px;color:#176b4c;font-size:12px;font-weight:700}
.ba-quick-support-payment-result h2{margin:0 0 10px;color:#183c2e;font-size:21px;line-height:1.7}
.ba-quick-support-payment-result p{margin:0 0 14px;color:#5d6b64;line-height:2}
.ba-quick-support-payment-result__details{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:10px;margin:18px 0 0;padding:14px;border-radius:10px;background:#f7faf8}
.ba-quick-support-payment-result__details>div{min-width:0}
.ba-quick-support-payment-result__details dt{margin-bottom:4px;color:#78857e;font-size:11px}
.ba-quick-support-payment-result__details dd{margin:0;color:#243b32;font-size:13px;font-weight:700;overflow-wrap:anywhere}
.ba-quick-support-payment-result--failed .ba-quick-support-payment-result__icon,.ba-quick-support-payment-result--cancelled .ba-quick-support-payment-result__icon{background:#fff1ef;color:#b33b31}
.ba-quick-support-payment-result--failed .ba-quick-support-payment-result__eyebrow,.ba-quick-support-payment-result--cancelled .ba-quick-support-payment-result__eyebrow{color:#a63b32}
.ba-quick-support-payment-result--pending .ba-quick-support-payment-result__icon,.ba-quick-support-payment-result--unknown .ba-quick-support-payment-result__icon{background:#fff7e8;color:#91620c}
.ba-quick-support-payment-result--pending .ba-quick-support-payment-result__eyebrow,.ba-quick-support-payment-result--unknown .ba-quick-support-payment-result__eyebrow{color:#91620c}
.ba-quick-support-payment-result__retry{display:inline-flex;align-items:center;justify-content:center;margin-top:18px;padding:10px 18px;border-radius:8px;background:#176b4c;color:#fff!important;text-decoration:none!important;font-size:13px;font-weight:700}
.ba-quick-support-payment-result__retry:hover{background:#10563c;color:#fff!important}
.ba-quick-support-payment-result__hint{margin-top:16px!important;padding:10px 12px;border-right:3px solid #d6b15b;background:#fffaf0;font-size:12px}
@media(max-width:600px){.ba-quick-support-payment-result{grid-template-columns:1fr;padding:20px}.ba-quick-support-payment-result__icon{width:46px;height:46px;font-size:23px}.ba-quick-support-payment-result__details{grid-template-columns:1fr}}
CSS;
    }
}

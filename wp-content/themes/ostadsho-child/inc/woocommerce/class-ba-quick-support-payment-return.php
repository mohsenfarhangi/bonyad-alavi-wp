<?php
declare(strict_types=1);

defined('ABSPATH') || exit;

/** Securely returns Quick Support payments to the page containing their form. */
final class BA_Quick_Support_Payment_Return
{
    private const RETURN_META = '_ba_qs_return_url';

    public static function boot(): void
    {
        add_filter('woocommerce_get_return_url', [self::class, 'returnUrl'], 20, 2);
        add_filter('woocommerce_get_cancel_order_url', [self::class, 'cancelUrl'], 20, 3);
        add_filter('afe_form_render_override', [self::class, 'renderResult'], 20, 5);
    }

    public static function returnUrl(string $url, $order): string
    {
        if (!$order instanceof WC_Order || !self::isQuickSupportOrder($order)) return $url;
        return self::resultUrl($order, 'return');
    }

    public static function cancelUrl(string $url, $order, string $redirect = ''): string
    {
        if (!$order instanceof WC_Order || !self::isQuickSupportOrder($order)) return $url;
        return add_query_arg('redirect', rawurlencode(self::resultUrl($order, 'cancelled')), $url);
    }

    public static function renderResult($override, string $slug, array $form, string $contextToken = '', array $formContext = [])
    {
        if ($slug !== 'quick-support-payment' || empty($_GET['ba_qs_payment_result'])) return $override;
        $orderId = absint($_GET['ba_qs_order'] ?? 0);
        $key = sanitize_text_field(wp_unslash((string)($_GET['ba_qs_key'] ?? '')));
        $order = $orderId ? wc_get_order($orderId) : false;
        if (!$order instanceof WC_Order || !self::isQuickSupportOrder($order) || $key === '' || !hash_equals($order->get_order_key(), $key)) {
            return '<div class="afe-notice afe-notice-error">اطلاعات بازگشت پرداخت معتبر نیست. برای پیگیری با پشتیبانی تماس بگیرید.</div>';
        }

        $status = self::status($order);
        $templates = (array)($form['settings']['quick_support_result_templates'] ?? []);
        $defaults = self::defaultTemplates();
        $html = (string)($templates[$status] ?? $defaults[$status]);
        $customer = trim($order->get_billing_first_name().' '.$order->get_billing_last_name());
        $amount = function_exists('wc_price') ? wp_strip_all_tags(wc_price((float)$order->get_total(), ['currency'=>$order->get_currency()])) : (string)$order->get_total();
        $html = strtr(wp_kses_post($html), [
            '{{order_number}}' => esc_html($order->get_order_number()),
            '{{amount}}' => esc_html($amount),
            '{{category_title}}' => esc_html((string)$order->get_meta(BA_Quick_Support_Payment_Service::ORDER_CATEGORY_TITLE, true)),
            '{{customer_name}}' => esc_html($customer),
            '{{gateway_title}}' => esc_html($order->get_payment_method_title()),
        ]);
        $css = (string)($form['settings']['quick_support_result_css'] ?? '');
        $style = $css !== '' ? '<style>'.wp_strip_all_tags($css).'</style>' : '';
        return $style.'<div class="ba-quick-support-payment ba-quick-support-payment--result" dir="rtl">'.$html.'</div>';
    }

    private static function resultUrl(WC_Order $order, string $result): string
    {
        $target = wp_validate_redirect((string)$order->get_meta(self::RETURN_META, true), home_url('/'));
        $host = (string)wp_parse_url($target, PHP_URL_HOST);
        $homeHost = (string)wp_parse_url(home_url('/'), PHP_URL_HOST);
        if ($host === '' || strcasecmp($host, $homeHost) !== 0) $target = home_url('/');
        return add_query_arg([
            'ba_qs_payment_result' => $result,
            'ba_qs_order' => $order->get_id(),
            'ba_qs_key' => $order->get_order_key(),
        ], $target);
    }

    private static function isQuickSupportOrder(WC_Order $order): bool
    {
        return $order->get_created_via() === BA_Quick_Support_Payment_Service::ORDER_CREATED_VIA
            && (string)$order->get_meta(BA_Quick_Support_Payment_Service::ORDER_MARKER, true) === '1';
    }

    private static function status(WC_Order $order): string
    {
        if ($order->is_paid()) return 'success';
        if ($order->has_status(['cancelled'])) return 'cancelled';
        if ($order->has_status(['failed','refunded'])) return 'failed';
        return 'pending';
    }

    private static function defaultTemplates(): array
    {
        return [
            'success' => '<section class="ba-qs-payment-result is-success"><h2>پرداخت شما با موفقیت انجام شد</h2><p>از همراهی و حمایت ارزشمند شما سپاسگزاریم.</p><p>شماره سفارش: {{order_number}} — مبلغ: {{amount}}</p><p>موضوع حمایت: {{category_title}}</p></section>',
            'failed' => '<section class="ba-qs-payment-result is-failed"><h2>پرداخت انجام نشد</h2><p>پرداخت شما تأیید نشد. در صورت کسر وجه، مبلغ طبق ضوابط بانکی به حساب شما بازخواهد گشت.</p><p>شماره سفارش: {{order_number}}</p></section>',
            'cancelled' => '<section class="ba-qs-payment-result is-cancelled"><h2>پرداخت لغو شد</h2><p>فرآیند پرداخت را لغو کرده‌اید. در صورت تمایل می‌توانید دوباره اقدام کنید.</p><p>شماره سفارش: {{order_number}}</p></section>',
            'pending' => '<section class="ba-qs-payment-result is-pending"><h2>وضعیت پرداخت در حال بررسی است</h2><p>تأیید نهایی پرداخت از بانک دریافت نشده است. لطفاً کمی بعد وضعیت سفارش را بررسی کنید.</p><p>شماره سفارش: {{order_number}}</p></section>',
        ];
    }
}

<?php
declare(strict_types=1);

if (!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/');

$GLOBALS['ba_qs_filters'] = [];
$GLOBALS['ba_qs_order'] = null;
$GLOBALS['ba_qs_inline_css'] = '';

function add_filter(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void {
    $GLOBALS['ba_qs_filters'][$hook][] = [$callback, $priority, $acceptedArgs];
}
function absint(mixed $value): int { return abs((int)$value); }
function wp_unslash(mixed $value): mixed {
    if (is_array($value)) return array_map('wp_unslash', $value);
    return is_string($value) ? stripslashes($value) : $value;
}
function sanitize_text_field(string $value): string { return trim(strip_tags($value)); }
function esc_html(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function esc_url(string $value): string { return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function wp_kses_post(string $value): string { return $value; }
function wp_strip_all_tags(string $value): string { return strip_tags($value); }
function wp_parse_url(string $url, int $component = -1): mixed { return parse_url($url, $component); }
function home_url(string $path = '/'): string { return 'https://bonyadalavi.ir' . $path; }
function wp_validate_redirect(string $url, string $fallback = ''): string {
    $host = strtolower((string)parse_url($url, PHP_URL_HOST));
    return in_array($host, ['bonyadalavi.ir'], true) ? $url : $fallback;
}
function remove_query_arg(array|string $keys, string $url): string {
    $parts = parse_url($url);
    parse_str((string)($parts['query'] ?? ''), $query);
    foreach ((array)$keys as $key) unset($query[$key]);
    $base = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? 'bonyadalavi.ir') . ($parts['path'] ?? '/');
    if (!empty($query)) $base .= '?' . http_build_query($query);
    if (isset($parts['fragment'])) $base .= '#' . $parts['fragment'];
    return $base;
}
function add_query_arg(array|string $key, mixed $value = null, string $url = ''): string {
    if (is_array($key)) {
        $args = $key;
        $url = (string)$value;
    } else {
        $args = [$key => $value];
    }
    $parts = parse_url($url);
    parse_str((string)($parts['query'] ?? ''), $query);
    foreach ($args as $name => $item) $query[$name] = $item;
    $base = ($parts['scheme'] ?? 'https') . '://' . ($parts['host'] ?? 'bonyadalavi.ir') . ($parts['path'] ?? '/');
    if (!empty($query)) $base .= '?' . http_build_query($query);
    if (isset($parts['fragment'])) $base .= '#' . $parts['fragment'];
    return $base;
}
function wc_get_order(int $id): mixed {
    return $GLOBALS['ba_qs_order'] instanceof BA_QS_Test_Order && $GLOBALS['ba_qs_order']->get_id() === $id
        ? $GLOBALS['ba_qs_order']
        : null;
}
function wc_price(float $amount, array $args = []): string {
    return '<span class="amount">' . number_format($amount, 0, '.', ',') . ' ' . esc_html((string)($args['currency'] ?? 'IRR')) . '</span>';
}
function wc_get_order_status_name(string $status): string { return ucfirst(str_replace('-', ' ', $status)); }
function wc_format_datetime(string $date, string $format = ''): string { return $date; }
function wp_enqueue_style(string $handle): void {}
function wp_add_inline_style(string $handle, string $css): bool {
    $GLOBALS['ba_qs_inline_css'] .= $css;
    return true;
}
function nocache_headers(): void {}
function get_query_var(string $key): mixed { return $GLOBALS['ba_qs_query_vars'][$key] ?? ''; }
function wc_get_checkout_url(): string { return 'https://bonyadalavi.ir/checkout/'; }

final class BA_Quick_Support_Payment_Service
{
    public const ORDER_MARKER = '_ba_quick_support';
    public const ORDER_CREATED_VIA = 'ba_quick_support';
    public const ORDER_RETURN_URL = '_ba_quick_support_return_url';
    public const ORDER_RETURN_TOKEN = '_ba_quick_support_return_token';
    public const ORDER_CATEGORY_TITLE = '_ba_quick_support_category_title';
}

final class BA_QS_Test_Order
{
    public function __construct(
        private array $meta,
        private string $status = 'processing',
        private bool $paid = true,
        private string $createdVia = 'ba_quick_support'
    ) {}
    public function get_meta(string $key, bool $single = true): mixed { return $this->meta[$key] ?? ''; }
    public function get_id(): int { return 321; }
    public function get_created_via(): string { return $this->createdVia; }
    public function get_order_key(): string { return 'order-key-secret'; }
    public function get_billing_first_name(): string { return 'Test'; }
    public function get_billing_last_name(): string { return 'Customer'; }
    public function get_payment_method_title(): string { return 'Test Gateway'; }
    public function get_order_number(): string { return '321'; }
    public function get_transaction_id(): string { return 'TX-123'; }
    public function get_total(): float { return 50000; }
    public function get_currency(): string { return 'IRR'; }
    public function get_status(): string { return $this->status; }
    public function get_date_created(): string { return '1405/08/22 10:00'; }
    public function is_paid(): bool { return $this->paid; }
    public function has_status(string|array $statuses): bool {
        return in_array($this->status, (array)$statuses, true);
    }
    public function setStatus(string $status, bool $paid = false): void {
        $this->status = $status;
        $this->paid = $paid;
    }
}

require_once dirname(__DIR__) . '/inc/woocommerce/class-ba-quick-support-payment-return.php';

BA_Quick_Support_Payment_Return::boot();

$baseUrl = 'https://bonyadalavi.ir/participation/quick-support/economic-empowerment/';
$GLOBALS['ba_qs_order'] = new BA_QS_Test_Order([
    BA_Quick_Support_Payment_Service::ORDER_MARKER => 1,
    BA_Quick_Support_Payment_Service::ORDER_RETURN_URL => $baseUrl,
    BA_Quick_Support_Payment_Service::ORDER_RETURN_TOKEN => 'return-secret',
    BA_Quick_Support_Payment_Service::ORDER_CATEGORY_TITLE => 'توانمندسازی اقتصادی',
]);

$GLOBALS['ba_qs_query_vars']['order-pay'] = 321;
$_GET = ['key'=>'order-key-secret'];
$callbackUrl = BA_Quick_Support_Payment_Return::filterSepCallbackUrl('https://bonyadalavi.ir/?wc-api=WC_Sep_Payment_Gateway', 'WC_Sep_Payment_Gateway');
parse_str((string)parse_url($callbackUrl, PHP_URL_QUERY), $callbackQuery);
$_GET = ['wc-api'=>'WC_Sep_Payment_Gateway', 'wc_order'=>'321', 'ba_qs_callback_token'=>'return-secret'];
$sepRedirect = BA_Quick_Support_Payment_Return::filterSepFailureRedirect(wc_get_checkout_url(), 302);
$_GET['ba_qs_callback_token'] = 'wrong';
$badSepRedirect = BA_Quick_Support_Payment_Return::filterSepFailureRedirect(wc_get_checkout_url(), 302);
$_GET['ba_qs_callback_token'] = 'return-secret';
$_GET['wc-api'] = 'other-gateway';
$otherSepRedirect = BA_Quick_Support_Payment_Return::filterSepFailureRedirect(wc_get_checkout_url(), 302);

$definitions = BA_Quick_Support_Payment_Return::templateDefinitions([], 'quick-support-payment');
$returnUrl = BA_Quick_Support_Payment_Return::filterReturnUrl('https://example.com/order-received/', $GLOBALS['ba_qs_order']);
parse_str((string)parse_url($returnUrl, PHP_URL_QUERY), $returnQuery);

$cancelUrl = BA_Quick_Support_Payment_Return::filterCancelUrl(
    'https://bonyadalavi.ir/checkout/?cancel_order=true',
    $GLOBALS['ba_qs_order']
);
parse_str((string)parse_url($cancelUrl, PHP_URL_QUERY), $cancelQuery);

$externalOrder = new BA_QS_Test_Order([
    BA_Quick_Support_Payment_Service::ORDER_MARKER => 1,
    BA_Quick_Support_Payment_Service::ORDER_RETURN_URL => 'https://attacker.example/return/',
    BA_Quick_Support_Payment_Service::ORDER_RETURN_TOKEN => 'other-secret',
]);
$externalReturnUrl = BA_Quick_Support_Payment_Return::filterReturnUrl('https://bonyadalavi.ir/thank-you/', $externalOrder);
$unrelatedOrder = new BA_QS_Test_Order([
    BA_Quick_Support_Payment_Service::ORDER_MARKER => 0,
    BA_Quick_Support_Payment_Service::ORDER_RETURN_URL => $baseUrl,
    BA_Quick_Support_Payment_Service::ORDER_RETURN_TOKEN => 'return-secret',
]);
$unrelatedReturnUrl = BA_Quick_Support_Payment_Return::filterReturnUrl('https://bonyadalavi.ir/thank-you/', $unrelatedOrder);
$wrongCreatedViaOrder = new BA_QS_Test_Order([
    BA_Quick_Support_Payment_Service::ORDER_MARKER => 1,
    BA_Quick_Support_Payment_Service::ORDER_RETURN_URL => $baseUrl,
    BA_Quick_Support_Payment_Service::ORDER_RETURN_TOKEN => 'return-secret',
], 'processing', true, 'bap_quick_checkout');
$wrongCreatedViaReturnUrl = BA_Quick_Support_Payment_Return::filterReturnUrl('https://bonyadalavi.ir/thank-you/', $wrongCreatedViaOrder);

$_GET = [
    'ba_qs_payment_result' => '1',
    'ba_qs_order_id' => '321',
    'ba_qs_token' => 'return-secret',
];
$form = [
    'settings' => [
        'payment_result_templates' => [
            'success' => '<section class="custom-result"><h2>سفارش {{order_number}}</h2><p>{{category}}</p><p>{{amount}}</p></section>',
        ],
        'payment_result_css' => '.custom-result{color:#176b4c}@import url(https://example.com/x.css);',
    ],
];
$successHtml = BA_Quick_Support_Payment_Return::renderResult(null, 'quick-support-payment', $form, 'context-token', []);
$GLOBALS['ba_qs_order']->setStatus('failed', false);
$failedHtml = BA_Quick_Support_Payment_Return::renderResult(null, 'quick-support-payment', ['settings'=>[]]);
$GLOBALS['ba_qs_order']->setStatus('pending', false);
$pendingHtml = BA_Quick_Support_Payment_Return::renderResult(null, 'quick-support-payment', ['settings'=>[]]);
$GLOBALS['ba_qs_order']->setStatus('cancelled', false);
$cancelledHtml = BA_Quick_Support_Payment_Return::renderResult(null, 'quick-support-payment', ['settings'=>[]]);
$GLOBALS['ba_qs_order']->setStatus('refunded', false);
$unknownHtml = BA_Quick_Support_Payment_Return::renderResult(null, 'quick-support-payment', ['settings'=>[]]);

$_GET['ba_qs_token'] = 'invalid-token';
$invalidToken = BA_Quick_Support_Payment_Return::renderResult('normal-form', 'quick-support-payment', ['settings'=>[]]);
$_GET['ba_qs_token'] = 'return-secret';
$_GET['ba_qs_payment_result'] = ['1'];
$arrayFlag = BA_Quick_Support_Payment_Return::renderResult('normal-form', 'quick-support-payment', ['settings'=>[]]);

$legacyOrder = new BA_QS_Test_Order([
    BA_Quick_Support_Payment_Service::ORDER_MARKER => 1,
    '_ba_qs_return_url' => $baseUrl,
    BA_Quick_Support_Payment_Service::ORDER_CATEGORY_TITLE => 'توانمندسازی اقتصادی',
], 'processing', true);
$GLOBALS['ba_qs_order'] = $legacyOrder;
$legacyReturnUrl = BA_Quick_Support_Payment_Return::filterReturnUrl('https://example.com/order-received/', $legacyOrder);
parse_str((string)parse_url($legacyReturnUrl, PHP_URL_QUERY), $legacyReturnQuery);
$_GET = [
    'ba_qs_payment_result' => 'return',
    'ba_qs_order' => '321',
    'ba_qs_key' => 'order-key-secret',
];
$legacyHtml = BA_Quick_Support_Payment_Return::renderResult(
    null,
    'quick-support-payment',
    ['settings'=>['quick_support_result_templates'=>['success'=>'<p>{{category_title}}|{{customer_name}}|{{gateway_title}}</p>']]],
    'legacy-context',
    []
);

$checks = [
    'sep-callback-carries-token' => ($callbackQuery['ba_qs_callback_token'] ?? '') === 'return-secret',
    'sep-failure-returns-to-form' => str_starts_with($sepRedirect, $baseUrl),
    'sep-invalid-token-keeps-checkout' => $badSepRedirect === wc_get_checkout_url(),
    'other-gateway-keeps-checkout' => $otherSepRedirect === wc_get_checkout_url(),
    'five-editable-status-templates' => count($definitions) === 5
        && isset($definitions['success'], $definitions['failed'], $definitions['cancelled'], $definitions['pending'], $definitions['unknown']),
    'success-return-uses-original-page-and-signed-token' => str_starts_with($returnUrl, $baseUrl)
        && ($returnQuery['ba_qs_payment_result'] ?? '') === '1'
        && ($returnQuery['ba_qs_order_id'] ?? '') === '321'
        && ($returnQuery['ba_qs_token'] ?? '') === 'return-secret',
    'cancel-return-targets-same-page-result' => str_contains((string)($cancelQuery['redirect'] ?? ''), $baseUrl)
        && str_contains((string)($cancelQuery['redirect'] ?? ''), 'ba_qs_token=return-secret'),
    'custom-success-template-renders-order-and-amount' => is_string($successHtml)
        && str_contains($successHtml, 'سفارش 321')
        && str_contains($successHtml, 'توانمندسازی اقتصادی')
        && str_contains($successHtml, '50,000'),
    'failed-order-uses-failed-template' => is_string($failedHtml) && str_contains($failedHtml, 'پرداخت ناموفق'),
    'pending-order-uses-pending-template' => is_string($pendingHtml) && str_contains($pendingHtml, 'در انتظار تأیید'),
    'cancelled-order-uses-cancelled-template' => is_string($cancelledHtml) && str_contains($cancelledHtml, 'پرداخت لغوشده'),
    'unknown-order-uses-unknown-template' => is_string($unknownHtml) && str_contains($unknownHtml, 'وضعیت نامشخص'),
    'invalid-return-token-does-not-replace-form' => $invalidToken === 'normal-form',
    'result-styles-are-rendered-after-head' => is_string($successHtml) && str_contains($successHtml, '<style>') && str_contains($successHtml, '.custom-result{color:#176b4c}'),
    'unsafe-css-import-is-not-emitted' => !str_contains($successHtml, '@import')
        && !str_contains($successHtml, 'url('),
    'unsafe-external-return-url-is-rejected' => $externalReturnUrl === 'https://bonyadalavi.ir/thank-you/',
    'unrelated-order-return-url-is-unchanged' => $unrelatedReturnUrl === 'https://bonyadalavi.ir/thank-you/',
    'order-received-url-filter-is-registered' => !empty($GLOBALS['ba_qs_filters']['woocommerce_get_checkout_order_received_url']),
    'checkout-payment-url-is-not-overridden' => empty($GLOBALS['ba_qs_filters']['woocommerce_get_checkout_payment_url']),
    'afe-render-override-keeps-five-argument-contract' => !empty($GLOBALS['ba_qs_filters']['afe_form_render_override'])
        && $GLOBALS['ba_qs_filters']['afe_form_render_override'][0][2] === 5,
    'wrong-created-via-order-is-not-quick-support' => $wrongCreatedViaReturnUrl === 'https://bonyadalavi.ir/thank-you/',
    'legacy-order-return-keeps-original-page-and-order-key' => str_starts_with($legacyReturnUrl, $baseUrl)
        && ($legacyReturnQuery['ba_qs_payment_result'] ?? '') === 'return'
        && ($legacyReturnQuery['ba_qs_order'] ?? '') === '321'
        && ($legacyReturnQuery['ba_qs_key'] ?? '') === 'order-key-secret',
    'legacy-template-token-aliases-render' => is_string($legacyHtml)
        && str_contains($legacyHtml, 'توانمندسازی اقتصادی')
        && str_contains($legacyHtml, 'Test Customer')
        && str_contains($legacyHtml, 'Test Gateway'),
    'array-query-flag-does-not-replace-form' => $arrayFlag === 'normal-form',
    'unrelated-form-template-hook-is-empty' => BA_Quick_Support_Payment_Return::templateDefinitions([], 'contact-us') === [],
];

foreach ($checks as $name => $ok) {
    echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
    if (!$ok) exit(1);
}

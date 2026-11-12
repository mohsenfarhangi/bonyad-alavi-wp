<?php
declare(strict_types=1);

if (!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/');
$GLOBALS['ba_qs_test_settings'] = [
    'allow_customer_gateway_selection'=>false,
    'quick_support_gateway_allowlist'=>['GatewayA'],
];
$GLOBALS['ba_qs_test_meta'] = [
    41 => ['_ba_qs_gateway_allowlist'=>[]],
    42 => ['_ba_qs_gateway_allowlist'=>['gatewayB']],
    43 => ['_ba_qs_gateway_allowlist'=>['missingGateway']],
    99 => ['_ba_quick_support_technical_product'=>'1'],
];

function ba_get_participation_settings(): array { return $GLOBALS['ba_qs_test_settings']; }
function absint(mixed $value): int { return abs((int)$value); }
function get_post_meta(int $id, string $key, bool $single = true): mixed {
    return $GLOBALS['ba_qs_test_meta'][$id][$key] ?? '';
}
function wc_get_price_decimals(): int { return (int)($GLOBALS['ba_qs_decimals'] ?? 0); }
function wc_format_decimal(mixed $value, mixed $decimals = false): string {
    return number_format((float)$value, $decimals === false ? 2 : (int)$decimals, '.', '');
}

final class BA_Quick_Support_Category
{
    public const META_GATEWAYS = '_ba_qs_gateway_allowlist';
}
final class BA_Participation_Payment_Gateway_Service
{
    public function get_active_gateway_choices(): array
    {
        return ['GatewayA'=>'Gateway A','gatewayB'=>'Gateway B'];
    }
    public function sanitize_gateway_id($id): string
    {
        return trim((string)$id);
    }
}

require_once dirname(__DIR__) . '/inc/woocommerce/class-ba-quick-support-payment-service.php';

$global = BA_Quick_Support_Payment_Service::gateway_choices_for_context(['category_id'=>41]);
$categoryOverride = BA_Quick_Support_Payment_Service::gateway_choices_for_context(['category_id'=>42]);
$invalidOverride = BA_Quick_Support_Payment_Service::gateway_choices_for_context(['category_id'=>43]);

$GLOBALS['ba_qs_test_settings']['quick_support_gateway_allowlist'] = [];
$allActive = BA_Quick_Support_Payment_Service::gateway_choices_for_context(['category_id'=>41]);
$GLOBALS['ba_qs_test_settings']['allow_customer_gateway_selection'] = true;
$selectionEnabled = BA_Quick_Support_Payment_Service::customer_gateway_selection_enabled();
$GLOBALS['ba_qs_test_settings']['allow_customer_gateway_selection'] = false;
$selectionDisabled = !BA_Quick_Support_Payment_Service::customer_gateway_selection_enabled();

$GLOBALS['ba_qs_decimals'] = 0;
$persianAmount = BA_Quick_Support_Payment_Service::normalizeAmount('۵۰٬۰۰۰');
$GLOBALS['ba_qs_decimals'] = 2;
$decimalAmount = BA_Quick_Support_Payment_Service::normalizeAmount('123.45');

$blockedDirect = BA_Quick_Support_Payment_Service::guard_technical_product_add_to_cart(true, 99, 1, 0, [], [
    '_ba_qs_internal'=>1,
]);
$GLOBALS['ba_qs_internal_isolated_cart'] = true;
$allowedIsolated = BA_Quick_Support_Payment_Service::guard_technical_product_add_to_cart(true, 99, 1, 0, [], [
    '_ba_qs_internal'=>1,
]);
unset($GLOBALS['ba_qs_internal_isolated_cart']);

$checks = [
    'empty-category-allowlist-inherits-global' => array_keys($global) === ['GatewayA'],
    'category-allowlist-overrides-global' => array_keys($categoryOverride) === ['gatewayB'],
    'inactive-or-unknown-override-is-not-exposed' => $invalidOverride === [],
    'empty-global-allowlist-means-all-active' => array_keys($allActive) === ['GatewayA','gatewayB'],
    'customer-selection-setting-enabled' => $selectionEnabled,
    'customer-selection-setting-disabled' => $selectionDisabled,
    'persian-thousands-normalized' => $persianAmount === '50000',
    'currency-decimal-precision-preserved' => $decimalAmount === '123.45',
    'technical-product-direct-add-blocked-even-with-client-marker' => $blockedDirect === false,
    'technical-product-allowed-only-inside-server-isolated-context' => $allowedIsolated === true,
];

foreach ($checks as $name => $ok) {
    echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
    if (!$ok) exit(1);
}

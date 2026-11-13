<?php
declare(strict_types=1);

if (!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/');
if (!defined('OBJECT')) define('OBJECT', 'OBJECT');

function absint(mixed $value): int { return abs((int)$value); }
function sanitize_text_field(string $value): string { return trim(strip_tags($value)); }
function sanitize_key(string $value): string { return strtolower((string)preg_replace('/[^a-z0-9_\-]/i', '', $value)); }
function current_time(string $format): string { return '2026-11-13'; }
function wp_date(string $format, ?int $timestamp = null): string { return date($format, $timestamp ?? time()); }
function get_post(int $id): ?WP_Post { return $GLOBALS['ba_qs_report_categories'][$id] ?? null; }
function wc_get_order_statuses(): array {
    return [
        'wc-pending'=>'در انتظار پرداخت',
        'wc-processing'=>'در حال انجام',
        'wc-completed'=>'تکمیل‌شده',
        'wc-on-hold'=>'در انتظار بررسی',
        'wc-failed'=>'ناموفق',
        'wc-cancelled'=>'لغوشده',
        'wc-refunded'=>'مستردشده',
    ];
}
function wc_get_orders(array $query): object {
    $orders = $GLOBALS['ba_qs_report_orders'];
    foreach ((array)($query['status'] ?? []) as $status) {
        $allowed[] = str_starts_with((string)$status, 'wc-') ? substr((string)$status, 3) : (string)$status;
    }
    $allowed = $allowed ?? [];
    $orders = array_values(array_filter($orders, static function($order) use ($query, $allowed): bool {
        if ($allowed && !in_array($order->get_status(), $allowed, true)) return false;
        foreach ((array)($query['meta_query'] ?? []) as $condition) {
            if (!is_array($condition) || empty($condition['key'])) continue;
            $actual = $order->get_meta((string)$condition['key'], true);
            $expected = $condition['value'] ?? '';
            $compare = $condition['compare'] ?? '=';
            if ($compare === '=' && (string)$actual !== (string)$expected) return false;
            if ($compare === '>=' && (float)$actual < (float)$expected) return false;
            if ($compare === '<=' && (float)$actual > (float)$expected) return false;
        }
        if (!empty($query['include']) && !in_array($order->get_id(), $query['include'], true)) return false;
        return true;
    }));
    usort($orders, static fn($a, $b): int => $b->get_id() <=> $a->get_id());
    $limit = (int)($query['limit'] ?? 25);
    $page = (int)($query['page'] ?? 1);
    $total = count($orders);
    $paged = array_slice($orders, ($page - 1) * $limit, $limit);
    return (object)[
        'orders' => $paged,
        'total' => $total,
        'max_num_pages' => max(1, (int)ceil($total / max(1, $limit))),
    ];
}

final class BA_Quick_Support_Category { public const POST_TYPE = 'ba_quick_support'; }

final class WP_Post
{
    public function __construct(public int $ID, public string $post_title) {}
}

final class BA_QS_Report_Test_Role
{
    private array $caps = [];
    public function add_cap(string $cap): void { $this->caps[$cap] = true; }
    public function has_cap(string $cap): bool { return !empty($this->caps[$cap]); }
}
$GLOBALS['ba_qs_report_roles'] = [
    'administrator' => new BA_QS_Report_Test_Role(),
    'editor' => new BA_QS_Report_Test_Role(),
];
$GLOBALS['ba_qs_report_options'] = [];
$GLOBALS['ba_qs_report_menu'] = [];
function get_role(string $slug): ?BA_QS_Report_Test_Role { return $GLOBALS['ba_qs_report_roles'][$slug] ?? null; }
function get_option(string $key, mixed $default = false): mixed { return $GLOBALS['ba_qs_report_options'][$key] ?? $default; }
function update_option(string $key, mixed $value, mixed $autoload = null): bool { $GLOBALS['ba_qs_report_options'][$key] = $value; return true; }
function add_action(string $hook, callable $callback, int $priority = 10, int $acceptedArgs = 1): void {}
function add_submenu_page(string $parent, string $pageTitle, string $menuTitle, string $capability, string $slug, callable $callback): void {
    $GLOBALS['ba_qs_report_menu'] = compact('parent', 'pageTitle', 'menuTitle', 'capability', 'slug');
}

final class BA_Quick_Support_Payment_Service
{
    public const ORDER_MARKER = '_ba_quick_support';
    public const ORDER_CATEGORY_ID = '_ba_quick_support_category_id';
    public const ORDER_CATEGORY_TITLE = '_ba_quick_support_category_title';
    public const ORDER_SUBMISSION_ID = '_ba_afe_submission_id';
    public const ORDER_AMOUNT = '_ba_quick_support_amount';
    public const ORDER_GATEWAY_ID = '_ba_quick_support_gateway_id';
    public static function normalizeAmount(mixed $value): string {
        $value = preg_replace('/[^0-9.]/', '', (string)$value) ?? '';
        return $value === '' ? '0' : $value;
    }
}

final class WC_Order
{
    public function __construct(
        private int $id,
        private string $status,
        private ?DateTimeImmutable $paidDate,
        private float $total,
        private float $refunded,
        private array $meta,
        private string $gatewayTitle = 'درگاه آزمایشی'
    ) {}
    public function get_id(): int { return $this->id; }
    public function get_status(): string { return $this->status; }
    public function get_date_paid(): ?DateTimeImmutable { return $this->paidDate; }
    public function get_total(): string { return (string)$this->total; }
    public function get_total_refunded(): string { return (string)$this->refunded; }
    public function get_meta(string $key, bool $single = true): mixed { return $this->meta[$key] ?? ''; }
    public function get_payment_method_title(): string { return $this->gatewayTitle; }
}
$GLOBALS['ba_qs_report_categories'] = [
    41 => new WP_Post(41, 'آموزش، مهارت و پرورش'),
    42 => new WP_Post(42, 'بهداشت و درمان'),
];

require_once dirname(__DIR__) . '/inc/quick-support/class-ba-quick-support-report-service.php';
require_once dirname(__DIR__) . '/inc/quick-support/class-ba-quick-support-reports.php';

$marker = BA_Quick_Support_Payment_Service::ORDER_MARKER;
$categoryKey = BA_Quick_Support_Payment_Service::ORDER_CATEGORY_ID;
$titleKey = BA_Quick_Support_Payment_Service::ORDER_CATEGORY_TITLE;
$gatewayKey = BA_Quick_Support_Payment_Service::ORDER_GATEWAY_ID;
$GLOBALS['ba_qs_report_orders'] = [
    new WC_Order(101, 'processing', new DateTimeImmutable('2026-11-10 10:00:00'), 1000, 200, [
        $marker=>1, $categoryKey=>41, $titleKey=>'آموزش، مهارت و پرورش', $gatewayKey=>'sep',
    ], 'سپ'),
    new WC_Order(102, 'refunded', new DateTimeImmutable('2026-11-11 11:00:00'), 500, 500, [
        $marker=>1, $categoryKey=>42, $titleKey=>'بهداشت و درمان', $gatewayKey=>'mellat',
    ], 'به‌پرداخت ملت'),
    new WC_Order(103, 'pending', null, 300, 0, [
        $marker=>1, $categoryKey=>41, $titleKey=>'آموزش، مهارت و پرورش', $gatewayKey=>'sep',
    ]),
    new WC_Order(104, 'failed', null, 250, 0, [
        $marker=>1, $categoryKey=>41, $titleKey=>'آموزش، مهارت و پرورش', $gatewayKey=>'sep',
    ]),
    new WC_Order(105, 'completed', new DateTimeImmutable('2026-11-12 12:00:00'), 999, 0, [
        $categoryKey=>41, $titleKey=>'سفارش عادی', $gatewayKey=>'sep',
    ]),
];

$service = new BA_Quick_Support_Report_Service();
$filters = $service->normalize_filters([
    'date_from'=>'2026-11-20',
    'date_to'=>'2026-11-01',
    'category_id'=>'41',
    'gateway_id'=>'GatewayX',
    'status'=>'wc-processing',
    'min_amount'=>'5000',
    'max_amount'=>'1000',
    'order_id'=>'101',
    'search'=>'  نام آزمایشی  ',
    'period'=>'month',
]);
$query = $service->build_query($filters, 2, 50);
$summary = $service->summary(['period'=>'day']);

BA_Quick_Support_Reports::sync_admin_capabilities();
BA_Quick_Support_Reports::register_menu();

$checks = [
    'date-range-is-normalized' => $filters['date_from'] === '2026-11-01' && $filters['date_to'] === '2026-11-20',
    'amount-range-is-normalized' => $filters['min_amount'] === '1000' && $filters['max_amount'] === '5000',
    'customer-search-is-trimmed' => $filters['search'] === 'نام آزمایشی',
    'period-is-whitelisted' => $filters['period'] === 'month',
    'order-query-always-requires-quick-support-marker' => isset($query['meta_query'][0]['key']) && $query['meta_query'][0]['key'] === $marker,
    'order-query-filters-category-and-gateway' => count($query['meta_query']) === 5,
    'order-query-supports-hpos-compatible-date-range' => $query['date_created'] === '2026-11-01...2026-11-20',
    'order-query-paginates' => $query['page'] === 2 && $query['limit'] === 50 && $query['paginate'] === true,
    'summary-gross-includes-only-confirmed-quick-support-orders' => $summary['gross'] === 1500.0,
    'summary-net-deducts-partial-and-full-refunds' => $summary['net'] === 800.0,
    'summary-counts-paid-orders-with-valid-paid-date' => $summary['paid_count'] === 2,
    'summary-average-uses-net-paid-amount' => $summary['average_net'] === 400.0,
    'summary-pending-and-failed-orders-are-separate' => $summary['pending_count'] === 1 && $summary['failed_count'] === 1,
    'summary-groups-by-category-and-gateway' => count($summary['category']) === 2 && count($summary['gateway']) === 2,
    'summary-groups-by-paid-date' => isset($summary['period']['2026-11-10'], $summary['period']['2026-11-11']),
    'administrator-receives-report-and-export-capabilities' => get_role('administrator')->has_cap(BA_Quick_Support_Reports::VIEW_CAPABILITY) && get_role('administrator')->has_cap(BA_Quick_Support_Reports::EXPORT_CAPABILITY),
    'editor-does-not-receive-report-capabilities-automatically' => !get_role('editor')->has_cap(BA_Quick_Support_Reports::VIEW_CAPABILITY) && !get_role('editor')->has_cap(BA_Quick_Support_Reports::EXPORT_CAPABILITY),
    'reports-menu-uses-independent-view-capability' => $GLOBALS['ba_qs_report_menu']['capability'] === BA_Quick_Support_Reports::VIEW_CAPABILITY,
];

foreach ($checks as $name => $ok) {
    echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
    if (!$ok) exit(1);
}

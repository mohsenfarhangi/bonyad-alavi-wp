<?php
declare(strict_types=1);

if (!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/');
if (!defined('OBJECT')) define('OBJECT', 'OBJECT');

final class WP_Error
{
    public function __construct(private string $code = '', private string $message = '') {}
    public function get_error_code(): string { return $this->code; }
    public function get_error_message(): string { return $this->message; }
}
final class WP_Post
{
    public function __construct(
        public int $ID,
        public string $post_type,
        public string $post_status,
        public string $post_name,
        public string $post_title,
        public string $post_excerpt = ''
    ) {}
}

$GLOBALS['ba_qs_posts'] = [
    41 => new WP_Post(41, 'ba_quick_support', 'publish', 'education-skills', 'آموزش، مهارت و پرورش', 'توسعه آموزش و مهارت'),
    42 => new WP_Post(42, 'ba_quick_support', 'publish', 'inactive-category', 'دسته غیرفعال', ''),
];
$GLOBALS['ba_qs_meta'] = [
    41 => [
        '_ba_qs_active'=>'1',
        '_ba_qs_minimum_amount'=>'1000',
        '_ba_qs_maximum_amount'=>'10000',
        '_ba_qs_suggested_amounts'=>['500','1000','5000','20000'],
        'ba_qs_icon'=>'icon-park-outline:book-open',
    ],
    42 => ['_ba_qs_active'=>'0'],
];
$GLOBALS['ba_qs_current_post'] = 41;

function is_wp_error(mixed $value): bool { return $value instanceof WP_Error; }
function absint(mixed $value): int { return abs((int)$value); }
function sanitize_key(string $value): string { return strtolower((string)preg_replace('/[^a-z0-9_\-]/i', '', $value)); }
function sanitize_title(string $value): string { return strtolower(trim((string)preg_replace('/[^a-z0-9\-]+/i', '-', $value), '-')); }
function sanitize_text_field(string $value): string { return trim(strip_tags($value)); }
function wp_unslash(mixed $value): mixed { return $value; }
function get_post(mixed $id): ?WP_Post { return $GLOBALS['ba_qs_posts'][(int)$id] ?? null; }
function get_post_meta(int $id, string $key, bool $single = true): mixed { return $GLOBALS['ba_qs_meta'][$id][$key] ?? ''; }
function get_the_excerpt(WP_Post $post): string { return $post->post_excerpt; }
function get_queried_object_id(): int { return (int)$GLOBALS['ba_qs_current_post']; }
function get_woocommerce_currency_symbol(): string { return 'ریال'; }
function wc_get_price_decimals(): int { return 0; }
function wc_format_decimal(mixed $value): string { return (string)(float)$value; }
function get_page_by_path(string $slug, mixed $output = OBJECT, string $postType = 'page'): ?WP_Post {
    foreach ($GLOBALS['ba_qs_posts'] as $post) {
        if ($post->post_type === $postType && $post->post_name === $slug) return $post;
    }
    return null;
}
function get_posts(array $args = []): array {
    $posts = [];
    foreach ($GLOBALS['ba_qs_posts'] as $id => $post) {
        if (($args['post_type'] ?? '') !== $post->post_type) continue;
        if (($args['post_status'] ?? '') !== $post->post_status) continue;
        if (!empty($args['meta_key']) && get_post_meta($id, (string)$args['meta_key'], true) !== ($args['meta_value'] ?? null)) continue;
        $posts[] = $post;
    }
    return $posts;
}

$pluginRoot = dirname(__DIR__, 3) . '/plugins/alavi-form-engine';
spl_autoload_register(static function(string $class) use ($pluginRoot): void {
    $prefix = 'BonyadAlavi\\FormEngine\\';
    if (!str_starts_with($class, $prefix)) return;
    $file = $pluginRoot . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_readable($file)) require_once $file;
});

require_once dirname(__DIR__) . '/inc/quick-support/class-ba-quick-support-category.php';
require_once dirname(__DIR__) . '/inc/quick-support/class-ba-quick-support-category-context-provider.php';

$provider = new BA_Quick_Support_Category_Context_Provider();
$current = $provider->resolve(['source'=>'current_page'], 'quick-support-payment');
$fixed = $provider->resolve(['source'=>'fixed','fixed_value'=>'41'], 'quick-support-payment');

$_GET['support_category'] = 'education-skills';
$querySlug = $provider->resolve([
    'source'=>'query_parameter',
    'query_parameter'=>'support_category',
    'identifier_type'=>'slug',
], 'quick-support-payment');

$_GET['support_category'] = '41';
$queryId = $provider->resolve([
    'source'=>'query_parameter',
    'query_parameter'=>'support_category',
    'identifier_type'=>'id',
], 'quick-support-payment');

$checks = [
    'current-page-source-resolves-category' => is_array($current) && ($current['category_id'] ?? 0) === 41,
    'fixed-source-resolves-category' => is_array($fixed) && ($fixed['category_slug'] ?? '') === 'education-skills',
    'query-slug-source-resolves-category' => is_array($querySlug) && ($querySlug['category_id'] ?? 0) === 41,
    'query-id-source-resolves-category' => is_array($queryId) && ($queryId['category_id'] ?? 0) === 41,
    'category-title-and-excerpt-in-context' => is_array($current)
        && ($current['category_title'] ?? '') === 'آموزش، مهارت و پرورش'
        && ($current['category_excerpt'] ?? '') === 'توسعه آموزش و مهارت',
    'amount-bounds-in-context' => is_array($current)
        && ($current['field_attributes']['amount']['data-afe-amount-min'] ?? '') === '1000'
        && ($current['field_attributes']['amount']['data-afe-amount-max'] ?? '') === '10000',
    'suggestions-filtered-to-allowed-range' => is_array($current)
        && ($current['suggested_amounts'] ?? []) === ['1000','5000'],
    'icon-available-for-elementor-custom-field' => is_array($current)
        && ($current['category_icon'] ?? '') === 'icon-park-outline:book-open',
    'inactive-category-rejected' => is_wp_error($provider->resolve([
        'source'=>'fixed','fixed_value'=>'42',
    ], 'quick-support-payment')),
    'slug-mismatch-rejected' => is_wp_error($provider->validate([
        'category_id'=>41,'category_slug'=>'another-category',
    ], 'quick-support-payment')),
];

foreach ($checks as $name => $ok) {
    echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
    if (!$ok) exit(1);
}

<?php
/**
 * Quick Support admin dashboard, filters and exports.
 *
 * @package OstadshoChild
 */

defined('ABSPATH') || exit;

final class BA_Quick_Support_Reports
{
    public const PAGE_SLUG = 'ba-quick-support-reports';
    public const VIEW_CAPABILITY = 'view_ba_quick_support_reports';
    public const EXPORT_CAPABILITY = 'export_ba_quick_support_reports';
    private const CAPS_VERSION_OPTION = 'ba_quick_support_reports_caps_version';
    private const FILTER_NONCE = 'ba_quick_support_report_filter';
    private const EXPORT_NONCE = 'ba_quick_support_report_export';

    private static ?BA_Quick_Support_Report_Service $reports = null;

    public static function boot(): void
    {
        add_action('init', [self::class, 'sync_admin_capabilities'], 2);
        add_action('admin_menu', [self::class, 'register_menu'], 99);
        add_action('admin_post_ba_quick_support_export', [self::class, 'handle_export']);
    }

    /**
     * Only the built-in Administrator receives report capabilities by default.
     * Other roles can be configured explicitly in PublishPress Capabilities.
     */
    public static function sync_admin_capabilities(): void
    {
        if (get_option(self::CAPS_VERSION_OPTION) === '1') return;
        $administrator = get_role('administrator');
        if (!$administrator) return;

        $administrator->add_cap(self::VIEW_CAPABILITY);
        $administrator->add_cap(self::EXPORT_CAPABILITY);
        update_option(self::CAPS_VERSION_OPTION, '1', false);
    }

    public static function register_menu(): void
    {
        add_submenu_page(
            'edit.php?post_type=' . BA_Quick_Support_Category::POST_TYPE,
            'گزارش‌های پرداخت سریع',
            'گزارش‌ها',
            self::VIEW_CAPABILITY,
            self::PAGE_SLUG,
            [self::class, 'render_page']
        );
    }

    public static function render_page(): void
    {
        if (!current_user_can(self::VIEW_CAPABILITY)) {
            wp_die('شما اجازه مشاهده گزارش‌های پرداخت سریع را ندارید.');
        }

        if (!function_exists('wc_get_orders')) {
            echo '<div class="wrap"><h1>گزارش‌های پرداخت سریع</h1><div class="notice notice-error"><p>برای نمایش گزارش‌ها، ووکامرس باید فعال باشد.</p></div></div>';
            return;
        }

        $raw = [];
        if (strtoupper((string)($_SERVER['REQUEST_METHOD'] ?? 'GET')) === 'POST') {
            check_admin_referer(self::FILTER_NONCE, '_ba_qs_filter_nonce');
            $raw = isset($_POST['filters']) && is_array($_POST['filters'])
                ? (array)wp_unslash($_POST['filters'])
                : [];
        }

        $filters = self::service()->normalize_filters($raw);
        $page = max(1, absint($_GET['paged'] ?? 1));
        $result = self::service()->page($filters, $page);
        $summary = self::service()->summary($filters);

        echo '<div class="wrap ba-quick-support-reports">';
        echo '<h1>گزارش‌های پرداخت سریع</h1>';
        echo '<p class="description">گزارش بر اساس سفارش‌های پرداخت سریع ووکامرس محاسبه می‌شود. مبالغ خالص پس از کسر بازپرداخت‌ها نمایش داده می‌شوند.</p>';

        self::render_filters($filters);
        self::render_summary($summary);
        self::render_breakdowns($summary);
        self::render_orders($result['orders']);
        self::render_pagination($filters, $page, (int)$result['pages']);

        echo '</div>';
    }

    private static function service(): BA_Quick_Support_Report_Service
    {
        return self::$reports ??= new BA_Quick_Support_Report_Service();
    }

    private static function render_filters(array $filters): void
    {
        $categories = get_posts([
            'post_type' => BA_Quick_Support_Category::POST_TYPE,
            'post_status' => ['publish', 'draft', 'pending', 'private'],
            'posts_per_page' => -1,
            'orderby' => 'menu_order title',
            'order' => 'ASC',
        ]);
        $gateways = [];
        if (function_exists('WC') && WC() && isset(WC()->payment_gateways())) {
            foreach ((array)WC()->payment_gateways()->payment_gateways() as $gateway) {
                if (is_object($gateway) && isset($gateway->id)) {
                    $gateways[(string)$gateway->id] = (string)($gateway->title ?? $gateway->id);
                }
            }
        }
        if (!empty($filters['gateway_id']) && !isset($gateways[$filters['gateway_id']])) {
            $gateways[$filters['gateway_id']] = $filters['gateway_id'];
        }

        echo '<form method="post" action="' . esc_url(admin_url('admin.php?page=' . self::PAGE_SLUG)) . '" class="ba-qs-report-filters">';
        wp_nonce_field(self::FILTER_NONCE, '_ba_qs_filter_nonce');
        echo '<div class="ba-qs-report-filter-grid">';
        self::date_input('date_from', 'از تاریخ', (string)$filters['date_from']);
        self::date_input('date_to', 'تا تاریخ', (string)$filters['date_to']);

        echo '<label><span>دسته‌بندی</span><select name="filters[category_id]"><option value="0">همه دسته‌ها</option>';
        foreach ($categories as $category) {
            if (!$category instanceof WP_Post) continue;
            echo '<option value="' . (int)$category->ID . '" ' . selected((int)$filters['category_id'], (int)$category->ID, false) . '>' . esc_html($category->post_title) . '</option>';
        }
        echo '</select></label>';

        echo '<label><span>درگاه پرداخت</span><select name="filters[gateway_id]"><option value="">همه درگاه‌ها</option>';
        foreach ($gateways as $id => $title) {
            echo '<option value="' . esc_attr($id) . '" ' . selected((string)$filters['gateway_id'], (string)$id, false) . '>' . esc_html($title) . '</option>';
        }
        echo '</select></label>';

        echo '<label><span>وضعیت سفارش</span><select name="filters[status]"><option value="">همه وضعیت‌ها</option>';
        foreach ((array)wc_get_order_statuses() as $status => $label) {
            echo '<option value="' . esc_attr($status) . '" ' . selected((string)$filters['status'], (string)$status, false) . '>' . esc_html($label) . '</option>';
        }
        echo '</select></label>';

        self::text_input('min_amount', 'حداقل مبلغ', (string)$filters['min_amount'], 'number');
        self::text_input('max_amount', 'حداکثر مبلغ', (string)$filters['max_amount'], 'number');
        self::text_input('order_id', 'شماره سفارش', (string)$filters['order_id'], 'number');
        self::text_input('search', 'نام یا شماره همراه', (string)$filters['search'], 'search');

        echo '<label><span>گروه‌بندی نمودار</span><select name="filters[period]">';
        echo '<option value="day" ' . selected((string)$filters['period'], 'day', false) . '>روزانه</option>';
        echo '<option value="month" ' . selected((string)$filters['period'], 'month', false) . '>ماهانه</option>';
        echo '</select></label>';
        echo '</div><div class="ba-qs-report-filter-actions"><button type="submit" class="button button-primary">اعمال فیلترها</button> <a class="button" href="' . esc_url(admin_url('admin.php?page=' . self::PAGE_SLUG)) . '">پاک کردن فیلترها</a></div></form>';

        if (current_user_can(self::EXPORT_CAPABILITY)) {
            echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '" class="ba-qs-report-export">';
            echo '<input type="hidden" name="action" value="ba_quick_support_export">';
            wp_nonce_field(self::EXPORT_NONCE, '_ba_qs_export_nonce');
            self::render_hidden_filters($filters);
            echo '<button class="button" type="submit" name="format" value="xlsx">دریافت Excel</button> ';
            echo '<button class="button" type="submit" name="format" value="csv">دریافت CSV</button>';
            echo '</form>';
        } else {
            echo '<p class="description">برای دریافت خروجی، مجوز مستقل export_ba_quick_support_reports لازم است.</p>';
        }
    }

    private static function date_input(string $key, string $label, string $value): void
    {
        echo '<label><span>' . esc_html($label) . '</span><input type="date" name="filters[' . esc_attr($key) . ']" value="' . esc_attr($value) . '"></label>';
    }

    private static function text_input(string $key, string $label, string $value, string $type = 'text'): void
    {
        echo '<label><span>' . esc_html($label) . '</span><input type="' . esc_attr($type) . '" name="filters[' . esc_attr($key) . ']" value="' . esc_attr($value) . '"></label>';
    }

    private static function render_hidden_filters(array $filters): void
    {
        foreach ($filters as $key => $value) {
            echo '<input type="hidden" name="filters[' . esc_attr((string)$key) . ']" value="' . esc_attr((string)$value) . '">';
        }
    }

    private static function render_summary(array $summary): void
    {
        $cards = [
            ['مجموع ناخالص پرداخت‌های موفق', (float)$summary['gross']],
            ['مجموع خالص پس از بازپرداخت', (float)$summary['net']],
            ['تعداد پرداخت‌های موفق', (int)$summary['paid_count'], true],
            ['میانگین خالص هر پرداخت', (float)$summary['average_net']],
            ['سفارش‌های در انتظار', (int)$summary['pending_count'], true],
            ['سفارش‌های ناموفق/لغوشده', (int)$summary['failed_count'], true],
        ];

        echo '<div class="ba-qs-report-cards">';
        foreach ($cards as [$label, $value, $count]) {
            echo '<section class="ba-qs-report-card"><span>' . esc_html($label) . '</span><strong>';
            echo !empty($count) ? esc_html(number_format_i18n((int)$value)) : wp_kses_post(wc_price((float)$value));
            echo '</strong></section>';
        }
        echo '</div>';
    }

    private static function render_breakdowns(array $summary): void
    {
        $groups = [
            'category' => 'مقایسه دسته‌بندی‌ها',
            'gateway' => 'مقایسه درگاه‌ها',
            'period' => 'گزارش زمانی',
        ];

        echo '<div class="ba-qs-report-breakdowns">';
        foreach ($groups as $key => $title) {
            echo '<section class="postbox"><div class="postbox-header"><h2 class="hndle">' . esc_html($title) . '</h2></div><div class="inside">';
            if (empty($summary[$key])) {
                echo '<p>داده‌ای برای این بازه وجود ندارد.</p>';
            } else {
                echo '<table class="widefat striped"><thead><tr><th>عنوان</th><th>تعداد پرداخت موفق</th><th>مبلغ خالص</th></tr></thead><tbody>';
                foreach ($summary[$key] as $item) {
                    echo '<tr><td>' . esc_html((string)$item['label']) . '</td><td>' . esc_html(number_format_i18n((int)$item['count'])) . '</td><td>' . wp_kses_post(wc_price((float)$item['net'])) . '</td></tr>';
                }
                echo '</tbody></table>';
            }
            echo '</div></section>';
        }
        echo '</div>';
    }

    private static function render_orders(array $orders): void
    {
        echo '<h2>تراکنش‌ها و سفارش‌ها</h2><div class="ba-qs-report-table-wrap"><table class="widefat striped"><thead><tr>';
        foreach (['سفارش','تاریخ','پرداخت‌کننده','شماره همراه','دسته','درگاه','مبلغ سفارش','بازپرداخت','خالص','وضعیت','پیوندها'] as $heading) {
            echo '<th>' . esc_html($heading) . '</th>';
        }
        echo '</tr></thead><tbody>';

        if ($orders === []) {
            echo '<tr><td colspan="11">سفارشی مطابق فیلترهای انتخاب‌شده پیدا نشد.</td></tr>';
        }

        foreach ($orders as $order) {
            if (!$order instanceof WC_Order) continue;
            $id = (int)$order->get_id();
            $category = (string)$order->get_meta(BA_Quick_Support_Payment_Service::ORDER_CATEGORY_TITLE, true);
            $submissionId = (int)$order->get_meta(BA_Quick_Support_Payment_Service::ORDER_SUBMISSION_ID, true);
            $created = $order->get_date_created();
            $date = $created instanceof DateTimeInterface ? wp_date('Y-m-d H:i', $created->getTimestamp()) : '—';
            $customer = trim((string)$order->get_billing_first_name() . ' ' . (string)$order->get_billing_last_name());
            $status = (string)$order->get_status();
            $statuses = (array)wc_get_order_statuses();
            $statusLabel = $statuses['wc-' . $status] ?? $statuses[$status] ?? $status;
            $total = (float)$order->get_total();
            $refund = (float)$order->get_total_refunded();
            $net = max(0.0, $total - $refund);
            $orderUrl = function_exists('get_edit_order_url')
                ? get_edit_order_url($id)
                : admin_url('post.php?post=' . $id . '&action=edit');

            echo '<tr>';
            echo '<td><strong>#' . esc_html((string)$order->get_order_number()) . '</strong></td>';
            echo '<td>' . esc_html($date) . '</td>';
            echo '<td>' . esc_html($customer !== '' ? $customer : '—') . '</td>';
            echo '<td><span dir="ltr">' . esc_html((string)$order->get_billing_phone()) . '</span></td>';
            echo '<td>' . esc_html($category !== '' ? $category : '—') . '</td>';
            echo '<td>' . esc_html((string)$order->get_payment_method_title()) . '</td>';
            echo '<td>' . wp_kses_post(wc_price($total, ['currency' => $order->get_currency()])) . '</td>';
            echo '<td>' . wp_kses_post(wc_price($refund, ['currency' => $order->get_currency()])) . '</td>';
            echo '<td><strong>' . wp_kses_post(wc_price($net, ['currency' => $order->get_currency()])) . '</strong></td>';
            echo '<td>' . esc_html((string)$statusLabel) . '</td>';
            echo '<td><a class="button button-small" href="' . esc_url($orderUrl) . '">سفارش</a>';
            if ($submissionId > 0) {
                $submissionUrl = admin_url('admin.php?page=alavi-form-engine-submissions&action=view&submission=' . $submissionId);
                echo ' <a class="button button-small" href="' . esc_url($submissionUrl) . '">فرم</a>';
            }
            echo '</td></tr>';
        }

        echo '</tbody></table></div>';
    }

    private static function render_pagination(array $filters, int $page, int $pages): void
    {
        if ($pages <= 1) return;
        $start = max(1, $page - 3);
        $end = min($pages, $page + 3);
        echo '<div class="tablenav"><div class="tablenav-pages">';
        for ($target = $start; $target <= $end; $target++) {
            echo '<form method="post" action="' . esc_url(admin_url('admin.php?page=' . self::PAGE_SLUG . '&paged=' . $target)) . '" style="display:inline-block;margin:0 2px">';
            wp_nonce_field(self::FILTER_NONCE, '_ba_qs_filter_nonce');
            self::render_hidden_filters($filters);
            echo '<button class="button ' . ($target === $page ? 'button-primary' : '') . '" type="submit">' . (int)$target . '</button></form>';
        }
        echo '</div></div>';
    }

    public static function handle_export(): void
    {
        if (!current_user_can(self::EXPORT_CAPABILITY)) {
            wp_die('شما اجازه دریافت خروجی گزارش پرداخت سریع را ندارید.');
        }
        check_admin_referer(self::EXPORT_NONCE, '_ba_qs_export_nonce');

        $raw = isset($_POST['filters']) && is_array($_POST['filters'])
            ? (array)wp_unslash($_POST['filters'])
            : [];
        $filters = self::service()->normalize_filters($raw);
        $format = sanitize_key((string)wp_unslash($_POST['format'] ?? 'csv'));

        try {
            if ($format === 'xlsx') {
                self::export_xlsx($filters);
            }
            if ($format === 'csv') {
                self::export_csv($filters);
            }
            wp_die('فرمت خروجی نامعتبر است.');
        } catch (Throwable $exception) {
            error_log('[Quick Support Reports] ' . $exception->getMessage());
            wp_die(esc_html('ساخت خروجی ناموفق بود: ' . $exception->getMessage()));
        }
    }

    private static function export_csv(array $filters): never
    {
        self::clean_output_buffers();
        nocache_headers();
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="quick-support-report-' . gmdate('Ymd-His') . '.csv"');
        header('X-Content-Type-Options: nosniff');

        echo "\xEF\xBB\xBF";
        $output = fopen('php://output', 'wb');
        if ($output === false) wp_die('ایجاد فایل CSV ممکن نشد.');

        fputcsv($output, self::export_headers());
        self::service()->iterate($filters, static function ($order) use ($output): void {
            fputcsv($output, self::export_row($order));
        });
        fclose($output);
        exit;
    }

    private static function export_xlsx(array $filters): never
    {
        $filename = 'quick-support-report-' . gmdate('Ymd-His') . '.xlsx';
        \BonyadAlavi\FormEngine\Export\ExportAutoloadScope::run(static function () use ($filters, $filename): never {
            if (!\BonyadAlavi\FormEngine\Export\ExportPackageStatus::excelReady()) {
                throw new RuntimeException('پکیج Excel افزونه Alavi Form Engine در دسترس نیست.');
            }
            $missing = \BonyadAlavi\FormEngine\Export\ExportPackageStatus::excelMissingExtensions();
            if ($missing !== []) {
                throw new RuntimeException('افزونه‌های PHP موردنیاز Excel فعال نیستند: ' . implode(', ', $missing));
            }
            \BonyadAlavi\FormEngine\Export\ExportAutoloadScope::assertOwnExcelRuntime();

            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('پرداخت سریع');
            $sheet->setRightToLeft(true);
            foreach (self::export_headers() as $index => $heading) {
                $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
                $sheet->setCellValueExplicit($column . '1', $heading, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                $sheet->getColumnDimension($column)->setWidth($index === 2 ? 26 : 20);
            }
            $lastColumn = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex(count(self::export_headers()));
            $sheet->getStyle('A1:' . $lastColumn . '1')->getFont()->setBold(true);
            $sheet->freezePane('A2');
            $row = 2;
            self::service()->iterate($filters, static function ($order) use ($sheet, &$row): void {
                foreach (self::export_row($order) as $index => $value) {
                    $column = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($index + 1);
                    $sheet->setCellValueExplicit($column . $row, (string)$value, \PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
                }
                $row++;
            });
            $sheet->setAutoFilter('A1:' . $lastColumn . max(1, $row - 1));

            $temporary = function_exists('wp_tempnam') ? wp_tempnam($filename) : tempnam(sys_get_temp_dir(), 'ba-qs-xlsx-');
            if (!is_string($temporary) || $temporary === '') {
                $spreadsheet->disconnectWorksheets();
                throw new RuntimeException('ساخت فایل موقت Excel ناموفق بود.');
            }

            try {
                $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
                $writer->save($temporary);
                $spreadsheet->disconnectWorksheets();
                clearstatcache(true, $temporary);
                if (!is_file($temporary) || filesize($temporary) < 4 || file_get_contents($temporary, false, null, 0, 2) !== 'PK') {
                    throw new RuntimeException('فایل XLSX معتبر تولید نشد.');
                }
                \BonyadAlavi\FormEngine\Export\BinaryDownload::streamFile(
                    $temporary,
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    $filename,
                    true
                );
            } catch (Throwable $exception) {
                $spreadsheet->disconnectWorksheets();
                if (is_file($temporary)) @unlink($temporary);
                throw $exception;
            }
        });
        throw new RuntimeException('خروجی Excel بدون ارسال فایل پایان یافت.');
    }

    private static function export_headers(): array
    {
        return [
            'شماره سفارش',
            'تاریخ ثبت سفارش',
            'نام پرداخت‌کننده',
            'شماره همراه',
            'دسته پرداخت سریع',
            'شناسه درگاه',
            'عنوان درگاه',
            'وضعیت سفارش',
            'مبلغ سفارش',
            'مبلغ بازپرداخت',
            'مبلغ خالص',
            'واحد پول',
            'شناسه ثبت فرم',
        ];
    }

    private static function export_row(WC_Order $order): array
    {
        $created = $order->get_date_created();
        $date = $created instanceof DateTimeInterface ? wp_date('Y-m-d H:i:s', $created->getTimestamp()) : '';
        $customer = trim((string)$order->get_billing_first_name() . ' ' . (string)$order->get_billing_last_name());
        $status = (string)$order->get_status();
        $statuses = (array)wc_get_order_statuses();
        $statusLabel = $statuses['wc-' . $status] ?? $statuses[$status] ?? $status;
        $total = (float)$order->get_total();
        $refund = (float)$order->get_total_refunded();

        return [
            (string)$order->get_order_number(),
            $date,
            $customer,
            (string)$order->get_billing_phone(),
            (string)$order->get_meta(BA_Quick_Support_Payment_Service::ORDER_CATEGORY_TITLE, true),
            (string)$order->get_meta(BA_Quick_Support_Payment_Service::ORDER_GATEWAY_ID, true),
            (string)$order->get_payment_method_title(),
            (string)$statusLabel,
            number_format($total, wc_get_price_decimals(), '.', ''),
            number_format($refund, wc_get_price_decimals(), '.', ''),
            number_format(max(0.0, $total - $refund), wc_get_price_decimals(), '.', ''),
            (string)$order->get_currency(),
            (string)$order->get_meta(BA_Quick_Support_Payment_Service::ORDER_SUBMISSION_ID, true),
        ];
    }

    private static function clean_output_buffers(): void
    {
        while (ob_get_level() > 0) {
            if (!@ob_end_clean()) break;
        }
    }
}

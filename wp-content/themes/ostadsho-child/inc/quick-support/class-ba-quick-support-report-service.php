<?php
/**
 * HPOS-compatible financial reporting for Quick Support WooCommerce orders.
 *
 * @package OstadshoChild
 */

defined('ABSPATH') || exit;

final class BA_Quick_Support_Report_Service
{
    public const PAGE_SIZE = 25;
    public const BATCH_SIZE = 100;

    /**
     * Normalize untrusted report filters. Personal data is accepted only from
     * POST forms; callers must not copy these values into URLs or logs.
     */
    public function normalize_filters(array $raw): array
    {
        $from = $this->valid_date($raw['date_from'] ?? '');
        $to = $this->valid_date($raw['date_to'] ?? '');
        if ($from !== '' && $to !== '' && $from > $to) {
            [$from, $to] = [$to, $from];
        }

        $statuses = function_exists('wc_get_order_statuses') ? array_keys(wc_get_order_statuses()) : [];
        $status = sanitize_key((string)($raw['status'] ?? ''));
        if ($status !== '' && !in_array($status, $statuses, true)) {
            $status = '';
        }

        $period = sanitize_key((string)($raw['period'] ?? 'day'));
        if (!in_array($period, ['day', 'month'], true)) {
            $period = 'day';
        }

        $minimum = BA_Quick_Support_Payment_Service::normalizeAmount($raw['min_amount'] ?? '');
        $maximum = BA_Quick_Support_Payment_Service::normalizeAmount($raw['max_amount'] ?? '');
        if ((float)$minimum <= 0) $minimum = '';
        if ((float)$maximum <= 0) $maximum = '';
        if ($minimum !== '' && $maximum !== '' && (float)$minimum > (float)$maximum) {
            [$minimum, $maximum] = [$maximum, $minimum];
        }

        return [
            'date_from' => $from,
            'date_to' => $to,
            'category_id' => absint($raw['category_id'] ?? 0),
            'gateway_id' => sanitize_text_field((string)($raw['gateway_id'] ?? '')),
            'status' => $status,
            'min_amount' => $minimum,
            'max_amount' => $maximum,
            'order_id' => absint($raw['order_id'] ?? 0),
            'search' => function_exists('mb_substr') ? mb_substr(sanitize_text_field((string)($raw['search'] ?? '')), 0, 100) : substr(sanitize_text_field((string)($raw['search'] ?? '')), 0, 100),
            'period' => $period,
        ];
    }

    /**
     * Build a WooCommerce order query. Source filtering uses created_via and
     * metadata filtering uses meta_query through wc_get_orders() for HPOS support.
     */
    public function build_query(array $filters, int $page = 1, int $limit = self::PAGE_SIZE): array
    {
        $meta = [
            'relation' => 'AND',
            [
                'key' => BA_Quick_Support_Payment_Service::ORDER_MARKER,
                'value' => '1',
                'compare' => '=',
            ],
        ];

        if (!empty($filters['category_id'])) {
            $meta[] = [
                'key' => BA_Quick_Support_Payment_Service::ORDER_CATEGORY_ID,
                'value' => (string)(int)$filters['category_id'],
                'compare' => '=',
            ];
        }

        if (!empty($filters['gateway_id'])) {
            $meta[] = [
                'key' => BA_Quick_Support_Payment_Service::ORDER_GATEWAY_ID,
                'value' => (string)$filters['gateway_id'],
                'compare' => '=',
            ];
        }

        if (!empty($filters['min_amount'])) {
            $meta[] = [
                'key' => BA_Quick_Support_Payment_Service::ORDER_AMOUNT,
                'value' => (string)$filters['min_amount'],
                'compare' => '>=',
                'type' => 'DECIMAL',
            ];
        }

        if (!empty($filters['max_amount'])) {
            $meta[] = [
                'key' => BA_Quick_Support_Payment_Service::ORDER_AMOUNT,
                'value' => (string)$filters['max_amount'],
                'compare' => '<=',
                'type' => 'DECIMAL',
            ];
        }

        $allStatuses = function_exists('wc_get_order_statuses') ? array_keys(wc_get_order_statuses()) : [];
        $query = [
            // Keep this dashboard strictly scoped to category-based Quick Support orders.
            // Product/project participation uses the separate 'bap_quick_checkout' flow.
            'created_via' => BA_Quick_Support_Payment_Service::ORDER_CREATED_VIA,
            'status' => !empty($filters['status']) ? [$filters['status']] : $allStatuses,
            'limit' => max(1, min(500, $limit)),
            'page' => max(1, $page),
            'paginate' => true,
            'orderby' => 'date',
            'order' => 'DESC',
            'meta_query' => $meta,
        ];

        $from = (string)($filters['date_from'] ?? '');
        $to = (string)($filters['date_to'] ?? '');
        if ($from !== '' || $to !== '') {
            $query['date_created'] = ($from !== '' ? $from : '1970-01-01') . '...' . ($to !== '' ? $to : current_time('Y-m-d'));
        }

        if (!empty($filters['order_id'])) {
            $query['include'] = [(int)$filters['order_id']];
        }

        if (!empty($filters['search'])) {
            $query['search'] = (string)$filters['search'];
        }

        return $query;
    }

    /** @return array{orders:array,total:int,pages:int} */
    public function page(array $filters, int $page = 1, int $limit = self::PAGE_SIZE): array
    {
        $result = wc_get_orders($this->build_query($filters, $page, $limit));

        if (is_object($result) && isset($result->orders)) {
            return [
                'orders' => is_array($result->orders) ? $result->orders : [],
                'total' => (int)($result->total ?? 0),
                'pages' => max(1, (int)($result->max_num_pages ?? 1)),
            ];
        }

        $orders = is_array($result) ? $result : [];
        return [
            'orders' => $orders,
            'total' => count($orders),
            'pages' => count($orders) >= $limit ? $page + 1 : $page,
        ];
    }

    /**
     * Aggregate every order matching the filters in bounded batches.
     *
     * @return array<string,mixed>
     */
    public function summary(array $filters): array
    {
        $summary = [
            'gross' => 0.0,
            'net' => 0.0,
            'paid_count' => 0,
            'average_net' => 0.0,
            'pending_count' => 0,
            'failed_count' => 0,
            'category' => [],
            'gateway' => [],
            'period' => [],
        ];

        $this->iterate($filters, function ($order) use (&$summary, $filters): void {
            if (!$order instanceof WC_Order) return;

            $status = (string)$order->get_status();
            $paidDate = $order->get_date_paid();
            $isPaid = $paidDate !== null;
            $total = max(0.0, (float)$order->get_total());
            $refunded = max(0.0, (float)$order->get_total_refunded());
            $net = max(0.0, $total - $refunded);

            if (in_array($status, ['pending', 'on-hold'], true)) {
                $summary['pending_count']++;
            }
            if (in_array($status, ['failed', 'cancelled'], true)) {
                $summary['failed_count']++;
            }

            if (!$isPaid) return;

            $summary['gross'] += $total;
            $summary['net'] += $net;
            $summary['paid_count']++;

            $categoryId = (int)$order->get_meta(BA_Quick_Support_Payment_Service::ORDER_CATEGORY_ID, true);
            $categoryTitle = (string)$order->get_meta(BA_Quick_Support_Payment_Service::ORDER_CATEGORY_TITLE, true);
            if ($categoryTitle === '' && $categoryId > 0) {
                $category = get_post($categoryId);
                $categoryTitle = $category instanceof WP_Post ? $category->post_title : ('دسته #' . $categoryId);
            }
            if ($categoryTitle === '') $categoryTitle = 'بدون عنوان';
            $this->add_breakdown($summary['category'], (string)($categoryId ?: 'unknown'), $categoryTitle, $net);

            $gatewayId = (string)$order->get_meta(BA_Quick_Support_Payment_Service::ORDER_GATEWAY_ID, true);
            $gatewayTitle = (string)$order->get_payment_method_title();
            if ($gatewayTitle === '') $gatewayTitle = $gatewayId !== '' ? $gatewayId : 'نامشخص';
            $this->add_breakdown($summary['gateway'], $gatewayId !== '' ? $gatewayId : 'unknown', $gatewayTitle, $net);

            $timestamp = $paidDate instanceof DateTimeInterface ? $paidDate->getTimestamp() : 0;
            $key = $timestamp > 0 ? wp_date($filters['period'] === 'month' ? 'Y-m' : 'Y-m-d', $timestamp) : 'unknown';
            $this->add_breakdown($summary['period'], $key, $key, $net);
        });

        $summary['average_net'] = $summary['paid_count'] > 0 ? $summary['net'] / $summary['paid_count'] : 0.0;

        foreach (['category', 'gateway'] as $key) {
            uasort($summary[$key], static fn(array $a, array $b): int => $b['net'] <=> $a['net']);
        }
        krsort($summary['period']);

        return $summary;
    }

    /**
     * Iterate all matching orders without loading the complete result set.
     *
     * @param callable(WC_Order):void $callback
     */
    public function iterate(array $filters, callable $callback): void
    {
        $page = 1;
        do {
            $result = wc_get_orders($this->build_query($filters, $page, self::BATCH_SIZE));
            if (is_object($result) && isset($result->orders)) {
                $orders = is_array($result->orders) ? $result->orders : [];
                $pages = max(1, (int)($result->max_num_pages ?? 1));
            } else {
                $orders = is_array($result) ? $result : [];
                $pages = count($orders) >= self::BATCH_SIZE ? $page + 1 : $page;
            }

            foreach ($orders as $order) {
                if ($order instanceof WC_Order) $callback($order);
            }

            $page++;
        } while ($page <= $pages);
    }

    private function add_breakdown(array &$target, string $key, string $label, float $net): void
    {
        if (!isset($target[$key])) {
            $target[$key] = ['label' => $label, 'count' => 0, 'net' => 0.0];
        }
        $target[$key]['count']++;
        $target[$key]['net'] += $net;
    }

    private function valid_date(mixed $value): string
    {
        $value = sanitize_text_field((string)$value);
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) return '';
        [$year, $month, $day] = array_map('intval', explode('-', $value));
        return checkdate($month, $day, $year) ? $value : '';
    }
}

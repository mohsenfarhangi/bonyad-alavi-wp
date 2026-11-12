<?php
/**
 * Direct isolated WooCommerce payments for Quick Support categories.
 *
 * @package OstadshoChild
 */

defined('ABSPATH') || exit;

final class BA_Quick_Support_Payment_Service
{
    public const PRODUCT_MARKER = '_ba_quick_support_technical_product';
    public const ORDER_MARKER = '_ba_quick_support';
    public const ORDER_CATEGORY_ID = '_ba_quick_support_category_id';
    public const ORDER_CATEGORY_SLUG = '_ba_quick_support_category_slug';
    public const ORDER_CATEGORY_TITLE = '_ba_quick_support_category_title';
    public const ORDER_SUBMISSION_ID = '_ba_afe_submission_id';
    public const ORDER_AMOUNT = '_ba_quick_support_amount';
    public const ORDER_GATEWAY_ID = '_ba_quick_support_gateway_id';
    public const CART_INTERNAL_MARKER = '_ba_qs_internal';

    private BA_Participation_Payment_Gateway_Service $gatewayService;

    public function __construct(?BA_Participation_Payment_Gateway_Service $gatewayService = null)
    {
        $this->gatewayService = $gatewayService ?: new BA_Participation_Payment_Gateway_Service();
    }

    public static function boot(): void
    {
        add_filter('woocommerce_add_to_cart_validation', [self::class, 'guard_technical_product_add_to_cart'], 5, 6);
    }

    public static function customer_gateway_selection_enabled(): bool
    {
        $settings = self::participationSettings();
        return !empty($settings['allow_customer_gateway_selection']);
    }

    /**
     * @return array<string,string>
     */
    public static function gateway_choices_for_context(array $context): array
    {
        $categoryId = absint($context['category_id'] ?? 0);
        if (!$categoryId) return [];

        $gatewayService = new BA_Participation_Payment_Gateway_Service();
        $active = $gatewayService->get_active_gateway_choices();
        if ($active === []) return [];

        $categoryAllowlist = array_values(array_filter(array_map('strval', (array)get_post_meta(
            $categoryId,
            BA_Quick_Support_Category::META_GATEWAYS,
            true
        ))));

        $settings = self::participationSettings();
        $globalAllowlist = array_values(array_filter(array_map('strval', (array)($settings['quick_support_gateway_allowlist'] ?? []))));
        $allowed = $categoryAllowlist !== [] ? $categoryAllowlist : $globalAllowlist;

        if ($allowed === []) return $active;
        return array_intersect_key($active, array_fill_keys($allowed, true));
    }

    /**
     * @return array<string,string>
     */
    private static function participationSettings(): array
    {
        if (function_exists('ba_get_participation_settings')) {
            return (array)ba_get_participation_settings();
        }
        $settings = get_option('ba_participation_settings', []);
        return is_array($settings) ? $settings : [];
    }

    public static function normalizeAmount($value): string
    {
        $value = strtr((string)$value, [
            '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
            '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
            '٫'=>'.','٬'=>',',
        ]);
        $value = str_replace([',', ' ', ' '], '', $value);
        $value = preg_replace('/[^0-9.]/', '', $value) ?? '';
        if ($value === '' || substr_count($value, '.') > 1 || !is_numeric($value)) return '0';
        $decimals = function_exists('wc_get_price_decimals') ? max(0, (int)wc_get_price_decimals()) : 0;
        return function_exists('wc_format_decimal')
            ? (string)wc_format_decimal($value, $decimals)
            : number_format((float)$value, $decimals, '.', '');
    }

    /**
     * Start payment for an already persisted AFE submission.
     *
     * @return array{order_id:int,redirect:string}|WP_Error
     */
    public function process(array $context, array $data, int $submissionId): array|WP_Error
    {
        if (!function_exists('WC') || !function_exists('wc_create_order') || !function_exists('wc_get_orders')) {
            return new WP_Error('ba_qs_woocommerce_unavailable', 'زیرساخت سفارش و پرداخت WooCommerce در دسترس نیست.');
        }
        if ($submissionId <= 0) {
            return new WP_Error('ba_qs_submission_missing', 'شناسه ثبت فرم برای پرداخت معتبر نیست.');
        }

        $categoryId = absint($context['category_id'] ?? 0);
        $category = $categoryId ? get_post($categoryId) : null;
        if (!BA_Quick_Support_Category::is_active($category)) {
            return new WP_Error('ba_qs_category_unavailable', 'دسته انتخاب‌شده دیگر برای دریافت حمایت فعال نیست.');
        }

        $amount = self::normalizeAmount($data['amount'] ?? '');
        $numericAmount = (float)$amount;
        if ($numericAmount <= 0) {
            return new WP_Error('ba_qs_amount_invalid', 'مبلغ حمایت باید بیشتر از صفر باشد.');
        }

        $minimum = (float)BA_Quick_Support_Category::normalizeAmount(get_post_meta($categoryId, BA_Quick_Support_Category::META_MINIMUM, true));
        $maximum = (float)BA_Quick_Support_Category::normalizeAmount(get_post_meta($categoryId, BA_Quick_Support_Category::META_MAXIMUM, true));
        if ($minimum > 0 && $numericAmount < $minimum) {
            return new WP_Error('ba_qs_amount_minimum', sprintf('حداقل مبلغ حمایت %s است.', wp_strip_all_tags(wc_price($minimum))));
        }
        if ($maximum > 0 && $numericAmount > $maximum) {
            return new WP_Error('ba_qs_amount_maximum', sprintf('حداکثر مبلغ حمایت %s است.', wp_strip_all_tags(wc_price($maximum))));
        }

        $allowedGateways = self::gateway_choices_for_context($context);
        if ($allowedGateways === []) {
            return new WP_Error('ba_qs_no_gateway', 'برای این دسته هیچ درگاه پرداخت فعال و مجازی تنظیم نشده است.');
        }

        $settings = self::participationSettings();
        $selectionEnabled = !empty($settings['allow_customer_gateway_selection']);
        if ($selectionEnabled) {
            $gatewayId = $this->sanitizeGatewayId($data['payment_gateway'] ?? '');
            if ($gatewayId === '' && count($allowedGateways) === 1) {
                $gatewayId = (string)array_key_first($allowedGateways);
            }
            if ($gatewayId === '') {
                return new WP_Error('ba_qs_gateway_required', 'لطفاً درگاه پرداخت را انتخاب کنید.');
            }
            if (!isset($allowedGateways[$gatewayId])) {
                return new WP_Error('ba_qs_gateway_forbidden', 'درگاه انتخاب‌شده برای این دسته مجاز نیست.');
            }
        } else {
            $gatewayId = $this->sanitizeGatewayId($settings['payment_gateway'] ?? '');
            if ($gatewayId === '' || !isset($allowedGateways[$gatewayId])) {
                return new WP_Error('ba_qs_default_gateway_invalid', 'درگاه پیش‌فرض مشارکت فعال یا در فهرست مجاز این دسته نیست.');
            }
        }

        $gateway = $this->gatewayService->get_gateway_by_id($gatewayId);
        if (is_wp_error($gateway)) return $gateway;
        if (method_exists($gateway, 'has_fields') && $gateway->has_fields()) {
            return new WP_Error('ba_qs_gateway_fields', 'درگاه انتخاب‌شده به فیلد پرداخت داخل صفحه نیاز دارد و برای پرداخت مستقیم مناسب نیست.');
        }

        return $this->withSubmissionLock($submissionId, function() use ($context, $data, $submissionId, $category, $categoryId, $amount, $gateway, $gatewayId) {
            $product = $this->technicalProduct();
            if (is_wp_error($product)) return $product;

            $order = $this->findOrderForSubmission($submissionId);
            if ($order && $order->is_paid()) {
                return new WP_Error('ba_qs_already_paid', 'برای این ثبت، پرداخت تأییدشده وجود دارد.');
            }
            if ($order && (int)$order->get_meta(self::ORDER_CATEGORY_ID, true) !== $categoryId) {
                return new WP_Error('ba_qs_order_context_mismatch', 'سفارش قبلی این ثبت به دسته دیگری متصل است.');
            }

            $order = $this->createOrUpdateOrder($order, $product, $category, $categoryId, $submissionId, $amount, $gateway, $gatewayId, $data);
            if (is_wp_error($order)) return $order;

            $originalPaymentMethodExists = array_key_exists('payment_method', $_POST);
            $originalPaymentMethod = $_POST['payment_method'] ?? null;
            $_POST['payment_method'] = $gateway->id;

            try {
                $payment = $this->gatewayService->with_isolated_cart(
                    $product,
                    (float)$amount,
                    function($isolatedCart) use ($gateway, $order, $data) {
                        if (!$gateway->is_available()) {
                            return new WP_Error('ba_qs_gateway_unavailable', 'درگاه پرداخت در حال حاضر در دسترس نیست.');
                        }

                        if (function_exists('WC') && WC()->session) {
                            WC()->session->set('chosen_payment_method', $gateway->id);
                            WC()->session->set('order_awaiting_payment', $order->get_id());
                            if (is_callable([WC()->session, 'save_data'])) WC()->session->save_data();
                        }

                        try {
                            $result = $gateway->process_payment($order->get_id());
                        } catch (Throwable $exception) {
                            return new WP_Error('ba_qs_gateway_exception', $exception->getMessage());
                        }

                        if (!is_array($result) || ($result['result'] ?? '') !== 'success' || empty($result['redirect'])) {
                            return new WP_Error('ba_qs_gateway_failed', 'درگاه پرداخت پاسخ معتبر برای انتقال به بانک برنگرداند.');
                        }

                        return apply_filters('woocommerce_payment_successful_result', $result, $order->get_id());
                    },
                    [self::CART_INTERNAL_MARKER => 1]
                );
            } finally {
                if ($originalPaymentMethodExists) {
                    $_POST['payment_method'] = $originalPaymentMethod;
                } else {
                    unset($_POST['payment_method']);
                }
            }

            if (is_wp_error($payment)) return $payment;
            $redirect = is_array($payment) ? esc_url_raw((string)($payment['redirect'] ?? '')) : '';
            if ($redirect === '') {
                return new WP_Error('ba_qs_gateway_redirect_missing', 'آدرس انتقال به درگاه پرداخت دریافت نشد.');
            }

            return ['order_id'=>(int)$order->get_id(), 'redirect'=>$redirect];
        });
    }

    private function createOrUpdateOrder($order, WC_Product $product, WP_Post $category, int $categoryId, int $submissionId, string $amount, WC_Payment_Gateway $gateway, string $gatewayId, array $data): WC_Order|WP_Error
    {
        try {
            if (!$order instanceof WC_Order) {
                $order = wc_create_order([
                    'customer_id' => get_current_user_id(),
                    'created_via' => 'ba_quick_support',
                ]);
                if (is_wp_error($order)) return $order;
            } else {
                $order->set_status('pending');
            }

            $order->set_billing_first_name(sanitize_text_field((string)($data['first_name'] ?? '')));
            $order->set_billing_last_name(sanitize_text_field((string)($data['last_name'] ?? '')));
            $order->set_billing_phone(sanitize_text_field((string)($data['mobile'] ?? '')));
            $email = sanitize_email((string)($data['email'] ?? ''));
            $order->set_billing_email($email);
            $order->set_payment_method($gateway);
            $order->set_currency(get_woocommerce_currency());
            $order->set_prices_include_tax(false);
            if (class_exists('WC_Geolocation')) $order->set_customer_ip_address(WC_Geolocation::get_ip_address());
            if (function_exists('wc_get_user_agent')) $order->set_customer_user_agent(wc_get_user_agent());

            $item = null;
            foreach ($order->get_items('line_item') as $existingItem) {
                if ($existingItem instanceof WC_Order_Item_Product && (int)$existingItem->get_product_id() === (int)$product->get_id()) {
                    $item = $existingItem;
                    break;
                }
            }

            if (!$item) {
                $itemId = $order->add_product($product, 1, [
                    'subtotal' => (float)$amount,
                    'total' => (float)$amount,
                ]);
                $item = $itemId ? $order->get_item($itemId) : null;
            }

            if (!$item instanceof WC_Order_Item_Product) {
                return new WP_Error('ba_qs_order_item_failed', 'افزودن ردیف حمایت به سفارش انجام نشد.');
            }

            $item->set_name('حمایت سریع — ' . $category->post_title);
            $item->set_quantity(1);
            $item->set_subtotal((float)$amount);
            $item->set_total((float)$amount);
            $item->update_meta_data('_ba_quick_support_category_id', $categoryId);
            $item->update_meta_data('_ba_quick_support_category_title', $category->post_title);
            $item->update_meta_data(self::ORDER_AMOUNT, $amount);
            $item->save();

            $order->update_meta_data(self::ORDER_MARKER, 1);
            $order->update_meta_data(self::ORDER_CATEGORY_ID, $categoryId);
            $order->update_meta_data(self::ORDER_CATEGORY_SLUG, $category->post_name);
            $order->update_meta_data(self::ORDER_CATEGORY_TITLE, $category->post_title);
            $order->update_meta_data(self::ORDER_SUBMISSION_ID, $submissionId);
            $order->update_meta_data(self::ORDER_AMOUNT, $amount);
            $order->update_meta_data(self::ORDER_GATEWAY_ID, $gatewayId);
            $order->calculate_totals(false);
            $order->save();

            return $order;
        } catch (Throwable $exception) {
            return new WP_Error('ba_qs_order_create_failed', $exception->getMessage());
        }
    }

    private function technicalProduct(): WC_Product|WP_Error
    {
        $productId = absint(get_option('ba_quick_support_product_id', 0));
        $product = $productId ? wc_get_product($productId) : false;

        if (!$product instanceof WC_Product || get_post_meta($productId, self::PRODUCT_MARKER, true) !== '1') {
            $matches = get_posts([
                'post_type' => 'product',
                'post_status' => 'publish',
                'numberposts' => 1,
                'fields' => 'ids',
                'meta_key' => self::PRODUCT_MARKER,
                'meta_value' => '1',
            ]);
            $productId = !empty($matches[0]) ? absint($matches[0]) : 0;
            $product = $productId ? wc_get_product($productId) : false;
        }

        if ($product instanceof WC_Product) {
            update_option('ba_quick_support_product_id', $product->get_id(), false);
            return $product;
        }

        if (!class_exists('WC_Product_Simple')) {
            return new WP_Error('ba_qs_product_class_missing', 'نوع محصول فنی WooCommerce در دسترس نیست.');
        }

        try {
            $product = new WC_Product_Simple();
            $product->set_name('حمایت سریع بنیاد علوی');
            $product->set_status('publish');
            $product->set_catalog_visibility('hidden');
            $product->set_virtual(true);
            $product->set_downloadable(false);
            $product->set_sold_individually(true);
            $product->set_manage_stock(false);
            $product->set_stock_status('instock');
            $product->set_tax_status('none');
            $product->set_regular_price('1');
            $product->set_price('1');
            $productId = $product->save();
            if (!$productId) return new WP_Error('ba_qs_product_create_failed', 'ساخت محصول فنی پرداخت سریع ناموفق بود.');

            update_post_meta($productId, self::PRODUCT_MARKER, '1');
            update_option('ba_quick_support_product_id', $productId, false);
            return $product;
        } catch (Throwable $exception) {
            return new WP_Error('ba_qs_product_create_failed', $exception->getMessage());
        }
    }

    private function findOrderForSubmission(int $submissionId): ?WC_Order
    {
        $orders = wc_get_orders([
            'limit' => 1,
            'return' => 'objects',
            'meta_key' => self::ORDER_SUBMISSION_ID,
            'meta_value' => (string)$submissionId,
            'orderby' => 'date',
            'order' => 'DESC',
        ]);
        if (!is_array($orders) || empty($orders[0]) || !$orders[0] instanceof WC_Order) return null;
        return $orders[0];
    }

    private function withSubmissionLock(int $submissionId, callable $callback): array|WP_Error
    {
        $key = 'ba_qs_payment_lock_' . $submissionId;
        $locked = add_option($key, (string)time(), '', false);
        if (!$locked) {
            $created = (int)get_option($key, 0);
            if ($created > 0 && (time() - $created) > 300) {
                delete_option($key);
                $locked = add_option($key, (string)time(), '', false);
            }
        }
        if (!$locked) return new WP_Error('ba_qs_payment_in_progress', 'پرداخت این ثبت در حال پردازش است؛ چند لحظه دیگر دوباره تلاش کنید.');

        try {
            return $callback();
        } finally {
            delete_option($key);
        }
    }

    private function sanitizeGatewayId($gatewayId): string
    {
        return $this->gatewayService->sanitize_gateway_id($gatewayId);
    }

    public static function guard_technical_product_add_to_cart($passed, $productId, $quantity, $variationId = 0, $variations = [], $cartItemData = [])
    {
        $productId = absint($productId);
        if (!$productId || get_post_meta($productId, self::PRODUCT_MARKER, true) !== '1') return $passed;

        $internal = !empty($GLOBALS['ba_qs_internal_isolated_cart'])
            && is_array($cartItemData)
            && !empty($cartItemData[self::CART_INTERNAL_MARKER]);

        if ($internal) return $passed;

        if (function_exists('wc_add_notice')) {
            wc_add_notice('محصول فنی پرداخت سریع فقط در مسیر داخلی پرداخت قابل استفاده است.', 'error');
        }
        return false;
    }
}

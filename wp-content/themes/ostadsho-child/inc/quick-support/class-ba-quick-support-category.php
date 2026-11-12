<?php
/**
 * Quick Support category content model and editor fields.
 *
 * @package OstadshoChild
 */

defined('ABSPATH') || exit;

final class BA_Quick_Support_Category
{
    public const POST_TYPE = 'ba_quick_support';

    public const META_ACTIVE = '_ba_qs_active';
    public const META_MINIMUM = '_ba_qs_minimum_amount';
    public const META_MAXIMUM = '_ba_qs_maximum_amount';
    public const META_SUGGESTED = '_ba_qs_suggested_amounts';
    public const META_ICON = 'ba_qs_icon';
    public const META_GATEWAYS = '_ba_qs_gateway_allowlist';

    public static function boot(): void
    {
        add_action('init', [self::class, 'ensure_admin_capability'], 1);
        add_action('init', [self::class, 'register_post_type'], 5);
        add_action('init', [self::class, 'seed_default_categories'], 20);
        add_action('init', [self::class, 'maybe_flush_rewrite_rules'], 999);
        add_action('admin_init', [self::class, 'ensure_elementor_support'], 20);
        add_action('pre_get_posts', [self::class, 'filter_public_queries']);
        add_action('add_meta_boxes_' . self::POST_TYPE, [self::class, 'register_meta_box']);
        add_action('save_post_' . self::POST_TYPE, [self::class, 'save_meta'], 10, 2);
    }

    public static function register_post_type(): void
    {
        register_post_type(self::POST_TYPE, [
            'labels' => [
                'name' => 'دسته‌بندی‌های پرداخت سریع',
                'singular_name' => 'دسته پرداخت سریع',
                'add_new' => 'افزودن دسته',
                'add_new_item' => 'افزودن دسته پرداخت سریع',
                'edit_item' => 'ویرایش دسته پرداخت سریع',
                'new_item' => 'دسته پرداخت سریع جدید',
                'view_item' => 'مشاهده دسته',
                'search_items' => 'جستجوی دسته‌ها',
                'not_found' => 'دسته‌ای پیدا نشد.',
                'menu_name' => 'پرداخت سریع',
            ],
            'public' => true,
            'publicly_queryable' => true,
            'show_ui' => true,
            'show_in_menu' => true,
            'show_in_rest' => true,
            'show_in_nav_menus' => true,
            'has_archive' => false,
            'query_var' => true,
            'rewrite' => [
                'slug' => 'participation/quick-support',
                'with_front' => false,
            ],
            'menu_position' => 26,
            'menu_icon' => 'dashicons-heart',
            'supports' => ['title', 'editor', 'excerpt', 'thumbnail', 'page-attributes'],
            'capability_type' => 'post',
            'capabilities' => [
                'edit_post' => 'manage_ba_quick_support_categories',
                'delete_post' => 'manage_ba_quick_support_categories',
                'edit_posts' => 'manage_ba_quick_support_categories',
                'edit_others_posts' => 'manage_ba_quick_support_categories',
                'publish_posts' => 'manage_ba_quick_support_categories',
                'read_private_posts' => 'manage_ba_quick_support_categories',
                'delete_posts' => 'manage_ba_quick_support_categories',
                'delete_private_posts' => 'manage_ba_quick_support_categories',
                'delete_published_posts' => 'manage_ba_quick_support_categories',
                'delete_others_posts' => 'manage_ba_quick_support_categories',
                'edit_private_posts' => 'manage_ba_quick_support_categories',
                'edit_published_posts' => 'manage_ba_quick_support_categories',
                'create_posts' => 'manage_ba_quick_support_categories',
            ],
            'map_meta_cap' => true,
        ]);

    }

    public static function ensure_admin_capability(): void
    {
        $administrator = get_role('administrator');
        if ($administrator && !$administrator->has_cap('manage_ba_quick_support_categories')) {
            $administrator->add_cap('manage_ba_quick_support_categories');
        }
    }

    public static function maybe_flush_rewrite_rules(): void
    {
        if (get_option('ba_quick_support_rewrite_version') === '1') return;

        // Run after other plugins and post types have registered their rewrites.
        flush_rewrite_rules(false);
        update_option('ba_quick_support_rewrite_version', '1', false);
    }

    public static function seed_default_categories(): void
    {
        if (get_option('ba_quick_support_seed_version') === '1') return;

        $defaults = [
            [
                'economic-empowerment',
                'توانمندسازی اقتصادی',
                'ایجاد فرصت‌های شغلی برای کسب درآمد پایدار',
            ],
            [
                'education-skills',
                'آموزش، مهارت و پرورش',
                'توسعه آموزش و مهارت برای شکوفایی استعدادها',
            ],
            [
                'health-treatment',
                'بهداشت و درمان',
                'گسترش خدمات سلامت با هدف پیشگیری و درمان',
            ],
            [
                'infrastructure',
                'عمرانی و زیرساختی',
                'توسعه زیرساخت‌های ضروری برای دسترسی بهتر و ایمن‌تر',
            ],
        ];

        $seedComplete = true;
        foreach ($defaults as [$slug, $title, $excerpt]) {
            if (get_page_by_path($slug, OBJECT, self::POST_TYPE)) continue;

            $postId = wp_insert_post([
                'post_type' => self::POST_TYPE,
                'post_status' => 'publish',
                'post_title' => $title,
                'post_name' => $slug,
                'post_excerpt' => $excerpt,
                'menu_order' => 0,
            ], true);

            if (is_wp_error($postId) || !$postId) {
                $seedComplete = false;
                continue;
            }

            update_post_meta((int)$postId, self::META_ACTIVE, '1');
            update_post_meta((int)$postId, self::META_MINIMUM, '0');
            update_post_meta((int)$postId, self::META_MAXIMUM, '0');
            update_post_meta((int)$postId, self::META_SUGGESTED, []);
            update_post_meta((int)$postId, self::META_GATEWAYS, []);
        }

        if ($seedComplete) update_option('ba_quick_support_seed_version', '1', false);
    }

    public static function ensure_elementor_support(): void
    {
        if (!class_exists('Elementor\\Plugin')) return;

        $supported = get_option('elementor_cpt_support', ['post', 'page']);
        $supported = is_array($supported) ? $supported : ['post', 'page'];
        if (!in_array(self::POST_TYPE, $supported, true)) {
            $supported[] = self::POST_TYPE;
            update_option('elementor_cpt_support', array_values(array_unique($supported)));
        }
    }

    public static function filter_public_queries($query): void
    {
        if (!$query instanceof WP_Query || is_admin()) return;

        $postType = $query->get('post_type');
        $matches = $postType === self::POST_TYPE
            || (is_array($postType) && in_array(self::POST_TYPE, $postType, true));
        if (!$matches) return;

        $metaQuery = (array)$query->get('meta_query');
        $metaQuery[] = [
            'key' => self::META_ACTIVE,
            'value' => '1',
            'compare' => '=',
        ];
        $query->set('meta_query', $metaQuery);
    }

    public static function register_meta_box(): void
    {
        add_meta_box(
            'ba-quick-support-category-settings',
            'تنظیمات دسته پرداخت سریع',
            [self::class, 'render_meta_box'],
            self::POST_TYPE,
            'normal',
            'high'
        );
    }

    public static function render_meta_box($post): void
    {
        wp_nonce_field('ba_quick_support_category_save', 'ba_quick_support_category_nonce');

        $active = get_post_meta($post->ID, self::META_ACTIVE, true);
        $minimum = (string)get_post_meta($post->ID, self::META_MINIMUM, true);
        $maximum = (string)get_post_meta($post->ID, self::META_MAXIMUM, true);
        $suggested = (array)get_post_meta($post->ID, self::META_SUGGESTED, true);
        $icon = (string)get_post_meta($post->ID, self::META_ICON, true);
        $allowlist = array_map('strval', (array)get_post_meta($post->ID, self::META_GATEWAYS, true));
        $currency = function_exists('get_woocommerce_currency_symbol') ? get_woocommerce_currency_symbol() : '';

        $gatewayChoices = class_exists('BA_Participation_Payment_Gateway_Service')
            ? (new BA_Participation_Payment_Gateway_Service())->get_active_gateway_choices()
            : [];

        echo '<p><label><input type="checkbox" name="ba_qs_active" value="1" ' . checked($active === '' || $active === '1', true, false) . '> این دسته در پرداخت سریع فعال باشد.</label></p>';
        echo '<p><label for="ba_qs_minimum"><strong>حداقل مبلغ</strong></label><br><input id="ba_qs_minimum" type="text" inputmode="decimal" class="regular-text" name="ba_qs_minimum" value="' . esc_attr($minimum) . '"> ' . esc_html($currency) . '<br><span class="description">صفر یعنی حداقل مبلغ اختصاصی تعریف نشده است؛ مبلغ پرداخت همچنان باید مثبت باشد.</span></p>';
        echo '<p><label for="ba_qs_maximum"><strong>حداکثر مبلغ</strong></label><br><input id="ba_qs_maximum" type="text" inputmode="decimal" class="regular-text" name="ba_qs_maximum" value="' . esc_attr($maximum) . '"> ' . esc_html($currency) . '<br><span class="description">صفر یعنی سقف اختصاصی تعریف نشده است.</span></p>';
        echo '<p><label for="ba_qs_suggested"><strong>مبالغ پیشنهادی</strong></label><br><textarea id="ba_qs_suggested" class="large-text" rows="3" name="ba_qs_suggested">' . esc_textarea(implode(', ', array_map('strval', $suggested))) . '</textarea><span class="description">مبالغ را با کاما یا خط جدید جدا کنید.</span></p>';
        echo '<p><label for="ba_qs_icon"><strong>کلاس یا شناسه آیکون</strong></label><br><input id="ba_qs_icon" type="text" class="regular-text" name="ba_qs_icon" value="' . esc_attr($icon) . '"><span class="description">اختیاری؛ برای استفاده در کارت Loop Item.</span></p>';
        echo '<fieldset><legend><strong>درگاه‌های مجاز این دسته</strong></legend><p class="description">اگر هیچ درگاهی انتخاب نشود، فهرست مجاز عمومی از تنظیمات مشارکت به ارث می‌رسد.</p>';

        if ($gatewayChoices === []) {
            echo '<p>درگاه فعال WooCommerce پیدا نشد.</p>';
        } else {
            foreach ($gatewayChoices as $gatewayId => $gatewayTitle) {
                echo '<label style="display:block;margin:5px 0"><input type="checkbox" name="ba_qs_gateways[]" value="' . esc_attr($gatewayId) . '" ' . checked(in_array((string)$gatewayId, $allowlist, true), true, false) . '> ' . esc_html($gatewayTitle) . '</label>';
            }
        }
        echo '</fieldset>';
    }

    public static function save_meta(int $postId, $post): void
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (wp_is_post_revision($postId) || wp_is_post_autosave($postId)) return;
        if (!isset($_POST['ba_quick_support_category_nonce'])) return;

        $nonce = sanitize_text_field(wp_unslash($_POST['ba_quick_support_category_nonce']));
        if (!wp_verify_nonce($nonce, 'ba_quick_support_category_save')) return;
        if (!current_user_can('edit_post', $postId)) return;

        update_post_meta($postId, self::META_ACTIVE, !empty($_POST['ba_qs_active']) ? '1' : '0');
        update_post_meta($postId, self::META_MINIMUM, self::normalizeAmount(wp_unslash($_POST['ba_qs_minimum'] ?? '0')));
        update_post_meta($postId, self::META_MAXIMUM, self::normalizeAmount(wp_unslash($_POST['ba_qs_maximum'] ?? '0')));

        $suggestedRaw = wp_unslash($_POST['ba_qs_suggested'] ?? '');
        $suggestedParts = is_array($suggestedRaw) ? $suggestedRaw : preg_split('/[,\r\n]+/', (string)$suggestedRaw);
        $suggested = [];
        foreach ((array)$suggestedParts as $value) {
            $amount = self::normalizeAmount($value);
            if ((float)$amount > 0) $suggested[] = $amount;
        }
        update_post_meta($postId, self::META_SUGGESTED, array_values(array_unique($suggested)));
        update_post_meta($postId, self::META_ICON, sanitize_text_field(wp_unslash($_POST['ba_qs_icon'] ?? '')));

        $submittedGateways = isset($_POST['ba_qs_gateways']) && is_array($_POST['ba_qs_gateways'])
            ? array_map('strval', wp_unslash($_POST['ba_qs_gateways']))
            : [];
        $choices = class_exists('BA_Participation_Payment_Gateway_Service')
            ? (new BA_Participation_Payment_Gateway_Service())->get_active_gateway_choices()
            : [];
        $allowlist = [];
        foreach ($submittedGateways as $gatewayId) {
            $gatewayId = class_exists('BA_Participation_Payment_Gateway_Service')
                ? (new BA_Participation_Payment_Gateway_Service())->sanitize_gateway_id($gatewayId)
                : sanitize_text_field($gatewayId);
            if ($gatewayId !== '' && isset($choices[$gatewayId])) $allowlist[] = $gatewayId;
        }
        update_post_meta($postId, self::META_GATEWAYS, array_values(array_unique($allowlist)));
    }

    public static function is_active($post): bool
    {
        return $post instanceof WP_Post
            && $post->post_type === self::POST_TYPE
            && $post->post_status === 'publish'
            && get_post_meta($post->ID, self::META_ACTIVE, true) === '1';
    }

    /** @return array<string,string> */
    public static function fixed_options(): array
    {
        $posts = get_posts([
            'post_type' => self::POST_TYPE,
            'post_status' => 'publish',
            'numberposts' => 500,
            'orderby' => ['menu_order' => 'ASC', 'title' => 'ASC'],
            'order' => 'ASC',
            'meta_key' => self::META_ACTIVE,
            'meta_value' => '1',
        ]);

        $options = [];
        foreach ($posts as $post) {
            if ($post instanceof WP_Post) $options[(string)$post->ID] = $post->post_title;
        }
        return $options;
    }

    /** @return array<string,mixed> */
    public static function context($post): array
    {
        if (!self::is_active($post)) return [];

        $minimum = self::normalizeAmount(get_post_meta($post->ID, self::META_MINIMUM, true));
        $maximum = self::normalizeAmount(get_post_meta($post->ID, self::META_MAXIMUM, true));
        $suggested = array_values(array_filter(array_map([self::class, 'normalizeAmount'], (array)get_post_meta($post->ID, self::META_SUGGESTED, true)), static fn($amount): bool => (float)$amount > 0));
        $suggested = array_values(array_filter($suggested, static function($amount) use ($minimum, $maximum): bool {
            $numeric = (float)$amount;
            return ((float)$minimum <= 0 || $numeric >= (float)$minimum)
                && ((float)$maximum <= 0 || $numeric <= (float)$maximum);
        }));
        $decimals = function_exists('wc_get_price_decimals') ? max(0, (int)wc_get_price_decimals()) : 0;
        $attributes = [
            'data-afe-amount-min' => (float)$minimum > 0 ? $minimum : '',
            'data-afe-amount-max' => (float)$maximum > 0 ? $maximum : '',
        ];

        return [
            'category_id' => (int)$post->ID,
            'category_slug' => (string)$post->post_name,
            'category_title' => (string)$post->post_title,
            'category_excerpt' => (string)get_the_excerpt($post),
            'category_icon' => (string)get_post_meta($post->ID, self::META_ICON, true),
            'minimum_amount' => $minimum,
            'maximum_amount' => $maximum,
            'suggested_amounts' => $suggested,
            'currency_symbol' => function_exists('get_woocommerce_currency_symbol') ? (string)get_woocommerce_currency_symbol() : '',
            'currency_decimals' => $decimals,
            'field_attributes' => ['amount' => $attributes],
        ];
    }

    public static function normalizeAmount($value): string
    {
        $value = trim((string)$value);
        if (str_contains($value, '-') || str_contains($value, '−')) return '0';
        $value = strtr($value, [
        if (preg_match('/[^0-9.,\\s]/u', $value)) return '0';
            '۰'=>'0','۱'=>'1','۲'=>'2','۳'=>'3','۴'=>'4','۵'=>'5','۶'=>'6','۷'=>'7','۸'=>'8','۹'=>'9',
            '٠'=>'0','١'=>'1','٢'=>'2','٣'=>'3','٤'=>'4','٥'=>'5','٦'=>'6','٧'=>'7','٨'=>'8','٩'=>'9',
            '٫'=>'.','٬'=>',',
        ]);
        $value = str_replace([',', ' ', ' '], '', $value);
        $value = preg_replace('/[^0-9.]/', '', $value) ?? '';
        if (substr_count($value, '.') > 1) return '0';
        if ($value === '' || !is_numeric($value)) return '0';
        return function_exists('wc_format_decimal') ? (string)wc_format_decimal($value) : (string)(float)$value;
    }
}

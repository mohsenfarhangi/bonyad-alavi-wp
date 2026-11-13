<?php
/**
 * Quick Support category Context provider for Alavi Form Engine.
 *
 * @package OstadshoChild
 */

use BonyadAlavi\FormEngine\Context\FormContextProviderInterface;

defined('ABSPATH') || exit;

final class BA_Quick_Support_Category_Context_Provider implements FormContextProviderInterface
{
    public function key(): string
    {
        return 'ba_quick_support_category';
    }

    public function label(): string
    {
        return 'دسته‌بندی پرداخت سریع';
    }

    public function supportedSources(): array
    {
        return ['current_page', 'fixed', 'query_parameter'];
    }

    public function fixedOptions(): array
    {
        return BA_Quick_Support_Category::fixed_options();
    }

    public function resolve(array $settings, string $formSlug): array|WP_Error
    {
        $source = (string)($settings['source'] ?? '');
        $post = null;

        if ($source === 'current_page') {
            $post = get_post(get_queried_object_id());
        } elseif ($source === 'fixed') {
            $post = get_post(absint($settings['fixed_value'] ?? 0));
        } elseif ($source === 'query_parameter') {
            $parameter = sanitize_key((string)($settings['query_parameter'] ?? ''));
            $raw = $parameter !== '' ? wp_unslash($_GET[$parameter] ?? '') : '';
            if (is_array($raw)) return new WP_Error('ba_qs_context_parameter', 'شناسه دسته در پارامتر URL معتبر نیست.');

            $identifier = sanitize_text_field((string)$raw);
            if ($identifier === '') return new WP_Error('ba_qs_context_missing', 'دسته پرداخت سریع در URL مشخص نشده است.');

            if (($settings['identifier_type'] ?? 'slug') === 'id') {
                $post = get_post(absint($identifier));
            } else {
                $post = get_page_by_path(sanitize_title($identifier), OBJECT, BA_Quick_Support_Category::POST_TYPE);
            }
        }

        if (!BA_Quick_Support_Category::is_active($post)) {
            return new WP_Error('ba_qs_context_unavailable', 'دسته پرداخت سریع پیدا نشد یا غیرفعال است.');
        }

        return BA_Quick_Support_Category::context($post);
    }

    public function validate(array $context, string $formSlug): array|WP_Error
    {
        $categoryId = absint($context['category_id'] ?? 0);
        $post = $categoryId > 0 ? get_post($categoryId) : null;

        if (!BA_Quick_Support_Category::is_active($post)) {
            return new WP_Error('ba_qs_context_unavailable', 'دسته پرداخت سریع دیگر فعال یا منتشرشده نیست.');
        }

        $providedSlug = (string)($context['category_slug'] ?? '');
        if ($providedSlug !== '' && !hash_equals((string)$post->post_name, $providedSlug)) {
            return new WP_Error('ba_qs_context_mismatch', 'شناسه دسته پرداخت سریع با اطلاعات معتبر آن مطابقت ندارد.');
        }

        return BA_Quick_Support_Category::context($post);
    }
}

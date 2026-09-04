<?php

function my_theme_enqueue_styles()
{
    wp_enqueue_style('parent-style', get_template_directory_uri() . '/style.css');

    wp_enqueue_style(
        'child-style',
        get_stylesheet_directory_uri() . '/style.css',
        array('parent-style'),
        wp_get_theme()->get('Version')
    );
    wp_enqueue_style(
        'ba-wpform',
        get_stylesheet_directory_uri() . '/assets/css/ba-wpform-style.css',
        array('child-style'),
        wp_get_theme()->get('Version')
    );

    if ( function_exists( 'is_cart' ) && is_cart() ) {
        wp_enqueue_style(
            'ba-participation-cart',
            get_stylesheet_directory_uri() . '/assets/css/ba-participation-cart.css',
            array( 'child-style' ),
            wp_get_theme()->get('Version')
        );
        wp_enqueue_script(
            'ba-participation-cart',
            get_stylesheet_directory_uri() . '/assets/js/ba-participation-cart.js',
            array(),
            wp_get_theme()->get('Version'),
            true
        );
        wp_localize_script(
            'ba-participation-cart',
            'BAPCart',
            array(
                'ajaxUrl' => class_exists( 'WC_AJAX' ) ? WC_AJAX::get_endpoint( 'bap_update_participation_cart' ) : '',
                'nonce'   => wp_create_nonce( 'bap_participation_cart' ),
                'i18n'    => array(
                    'updating' => 'در حال به‌روزرسانی…',
                    'error'    => 'به‌روزرسانی سبد انجام نشد. لطفاً دوباره تلاش کنید.',
                ),
            )
        );
    }

    if ( function_exists( 'is_checkout' ) && is_checkout() && ! is_order_received_page() ) {
        wp_enqueue_style(
            'ba-participation-checkout',
            get_stylesheet_directory_uri() . '/assets/css/ba-participation-checkout.css',
            array( 'child-style' ),
            wp_get_theme()->get('Version')
        );
    }

    if ( function_exists( 'is_order_received_page' ) && is_order_received_page() ) {
        wp_enqueue_style(
            'ba-participation-thankyou',
            get_stylesheet_directory_uri() . '/assets/css/ba-participation-thankyou.css',
            array( 'child-style' ),
            wp_get_theme()->get('Version')
        );
        wp_enqueue_script(
            'ba-participation-thankyou',
            get_stylesheet_directory_uri() . '/assets/js/ba-participation-thankyou.js',
            array(),
            wp_get_theme()->get('Version'),
            true
        );
    }
}
add_action('wp_enqueue_scripts', 'my_theme_enqueue_styles', 999);

/**
 * پروژه‌های مشارکت نیاز به اطلاعات نشانی ندارند؛ این فیلدها از checkout حذف می‌شوند.
 */
function bap_remove_checkout_address_fields( $fields ) {
	$remove_fields = array( 'address_1', 'address_2', 'state', 'city', 'postcode' );

	foreach ( array( 'billing', 'shipping' ) as $section ) {
		foreach ( $remove_fields as $field ) {
			unset( $fields[ $section ][ $section . '_' . $field ] );
		}
	}

	return $fields;
}
add_filter( 'woocommerce_checkout_fields', 'bap_remove_checkout_address_fields', 20 );



include __DIR__."/inc/shortcodes/active-projects-count.php";
include __DIR__."/inc/shortcodes/product-crowdfunding-data.php";

// ووکامرس : سوالات متداول
require_once get_stylesheet_directory() . '/inc/woocommerce/product-faq-metabox.php';
/* المنتور: ویجت صفحه مشارکت مردمی */
require_once get_stylesheet_directory() . '/inc/elementor/elementor-widgets.php';
// المنتور: داینامیک‌تگ‌های تنظیمات بنیاد علوی
require_once get_stylesheet_directory() . '/inc/elementor/dynamic-tags.php';

//افزودن به سبد خرید مشارکت مردمی
require_once get_stylesheet_directory() . '/inc/woocommerce/class-ba-woocommerce-participation.php';

// سرویس و تب تنظیمات مرکز حرکت‌های مردمی و جهادی.
require_once get_stylesheet_directory() . '/inc/services/class-ba-center-settings-service.php';
require_once get_stylesheet_directory() . '/inc/admin/settings/class-ba-center-settings-tab.php';

// تنظیمات مدیریتی بنیاد علوی: صفحه تب‌دار، مشارکت مردمی و مدیریت دسترسی.
require_once get_stylesheet_directory() . '/inc/admin/settings/tab-participation.php';
require_once get_stylesheet_directory() . '/inc/admin/settings/tab-access-management.php';
require_once get_stylesheet_directory() . '/inc/admin/settings/class-ba-settings-page.php';
BA_Settings_Page::init();


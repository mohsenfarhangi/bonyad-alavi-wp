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

    // Mobile navigation styling (scoped to the custom mobile menu).
    wp_enqueue_style(
        'ba-mobile-menu',
        get_stylesheet_directory_uri() . '/assets/css/alavi-mobile-menu.css',
        array('child-style'),
        filemtime( get_stylesheet_directory() . '/assets/css/alavi-mobile-menu.css' )
    );

    // Mobile sidebar enhancements, safe on pages without the widget.
    wp_enqueue_script(
        'ba-mobile-menu',
        get_stylesheet_directory_uri() . '/assets/js/alavi-mobile-menu.js',
        array(),
        filemtime( get_stylesheet_directory() . '/assets/js/alavi-mobile-menu.js' ),
        true
    );

    // Front-end custom behavior shared across all site pages.
    wp_enqueue_script(
        'ba-global-custom',
        get_stylesheet_directory_uri() . '/assets/js/custom.js',
        array(),
        filemtime( get_stylesheet_directory() . '/assets/js/custom.js' ),
        true
    );

    // Responsive styling and post-submit presentation for the AFE facilitator form.
    // Selectors are scoped to its slug so other forms are unaffected.
    wp_enqueue_style(
        'ba-facilitator-recruitment-form',
        get_stylesheet_directory_uri() . '/assets/css/ba-facilitator-recruitment-form.css',
        array('child-style'),
        filemtime( get_stylesheet_directory() . '/assets/css/ba-facilitator-recruitment-form.css' )
    );
    wp_enqueue_script(
        'ba-facilitator-recruitment-form',
        get_stylesheet_directory_uri() . '/assets/js/ba-facilitator-recruitment-form.js',
        array(),
        filemtime( get_stylesheet_directory() . '/assets/js/ba-facilitator-recruitment-form.js' ),
        true
    );

    // Presentation and calculated stay duration for the camp admission form only.
    wp_enqueue_style(
        'ba-camp-admission-form',
        get_stylesheet_directory_uri() . '/assets/css/ba-camp-admission-form.css',
        array('child-style'),
        filemtime( get_stylesheet_directory() . '/assets/css/ba-camp-admission-form.css' )
    );
    wp_enqueue_script(
        'ba-camp-admission-form',
        get_stylesheet_directory_uri() . '/assets/js/ba-camp-admission-form.js',
        array(),
        filemtime( get_stylesheet_directory() . '/assets/js/ba-camp-admission-form.js' ),
        true
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

// کامپوننت‌های عمومی مدیریت.
require_once get_stylesheet_directory() . '/inc/admin/components/class-ba-admin-repeater-component.php';

// ووکامرس: سوالات متداول محصول.
require_once get_stylesheet_directory() . '/inc/woocommerce/product-faq-metabox.php';
/* المنتور: ویجت صفحه مشارکت مردمی */
require_once get_stylesheet_directory() . '/inc/elementor/elementor-widgets.php';
// المنتور: داینامیک‌تگ‌های تنظیمات بنیاد علوی
require_once get_stylesheet_directory() . '/inc/elementor/dynamic-tags.php';

// پرداخت و سبد مشارکت مردمی.
require_once get_stylesheet_directory() . '/inc/woocommerce/class-ba-participation-payment-gateway-service.php';
require_once get_stylesheet_directory() . '/inc/woocommerce/class-ba-participation-quick-checkout-service.php';
require_once get_stylesheet_directory() . '/inc/woocommerce/class-ba-woocommerce-participation.php';

// پرداخت سریع دسته‌ای و Context Provider عمومی AFE.

add_action( 'wp_enqueue_scripts', function () {
	if ( ! is_singular( 'ba_quick_support' ) ) {
		return;
	}

	$theme_dir = get_stylesheet_directory();
	$theme_uri = get_stylesheet_directory_uri();
	$style_path = '/assets/css/ba-quick-support-form.css';
	$script_path = '/assets/js/ba-quick-support-form.js';
	$style_file = $theme_dir . $style_path;
	$script_file = $theme_dir . $script_path;

	if ( is_readable( $style_file ) ) {
		wp_enqueue_style(
			'ba-quick-support-form',
			$theme_uri . $style_path,
			array(),
			(string) filemtime( $style_file )
		);
	}

	if ( is_readable( $script_file ) ) {
		wp_enqueue_script(
			'ba-quick-support-form',
			$theme_uri . $script_path,
			array(),
			(string) filemtime( $script_file ),
			true
		);
	}
}, 20 );
require_once get_stylesheet_directory() . '/inc/quick-support/class-ba-quick-support-category.php';
require_once get_stylesheet_directory() . '/inc/quick-support/class-ba-quick-support-category-context-provider.php';
require_once get_stylesheet_directory() . '/inc/woocommerce/class-ba-quick-support-payment-service.php';
require_once get_stylesheet_directory() . '/inc/woocommerce/class-ba-quick-support-payment-action.php';
require_once get_stylesheet_directory() . '/inc/quick-support/class-ba-quick-support-report-service.php';
require_once get_stylesheet_directory() . '/inc/quick-support/class-ba-quick-support-reports.php';
BA_Quick_Support_Category::boot();
BA_Quick_Support_Payment_Service::boot();
BA_Quick_Support_Reports::boot();

// سرویس و تب تنظیمات مرکز حرکت‌های مردمی و جهادی.
require_once get_stylesheet_directory() . '/inc/services/class-ba-center-settings-service.php';
require_once get_stylesheet_directory() . '/inc/admin/settings/class-ba-center-settings-tab.php';
require_once get_stylesheet_directory() . '/inc/studies-center/class-ba-studies-center-bootstrap.php';

// تنظیمات مدیریتی بنیاد علوی: صفحه تب‌دار، مشارکت مردمی و مدیریت دسترسی.
require_once get_stylesheet_directory() . '/inc/admin/settings/tab-participation.php';
require_once get_stylesheet_directory() . '/inc/admin/settings/class-ba-settings-access-service.php';
require_once get_stylesheet_directory() . '/inc/admin/settings/tab-access-management.php';
require_once get_stylesheet_directory() . '/inc/admin/settings/class-ba-settings-page.php';
require_once get_stylesheet_directory() . '/inc/admin/settings/class-ba-settings-ajax-controller.php';
BA_Settings_Page::init();
BA_Settings_Ajax_Controller::init();

// فرم اختصاصی ثبت‌نام گروه‌های مردمی و جهادی؛ موتور فرم داخل Alavi Form Engine باقی می‌ماند.
require_once get_stylesheet_directory() . '/inc/forms/class-ba-jihadi-group-registration-form.php';
add_action( 'afe_register_forms', array( BA_Jihadi_Group_Registration_Form::class, 'register' ) );

// فرم تماس با ما؛ Source Definition در قالب و موتور فرم داخل Alavi Form Engine است.
require_once get_stylesheet_directory() . '/inc/forms/class-ba-contact-form.php';
add_action( 'afe_register_forms', array( BA_Contact_Form::class, 'register' ) );

// فرم جذب تسهیلگر؛ تمام مدیریت، اعتبارسنجی و ذخیره‌سازی با Alavi Form Engine است.
require_once get_stylesheet_directory() . '/inc/forms/class-ba-facilitator-recruitment-form.php';
add_action( 'afe_register_forms', array( BA_Facilitator_Recruitment_Form::class, 'register' ) );

// فرم پذیرش مجتمع اردوگاهی امام خمینی (ره)؛ محاسبه و اعتبارسنجی مدت اقامت نیز به همین فرم محدود است.
require_once get_stylesheet_directory() . '/inc/forms/class-ba-camp-admission-form.php';
add_action( 'afe_register_validator_definitions', array( BA_Camp_Admission_Form::class, 'registerValidators' ) );
add_action( 'afe_register_forms', array( BA_Camp_Admission_Form::class, 'register' ) );

 // فرم مشترک پرداخت سریع مشارکت و قرارداد عمومی Context در AFE.
require_once get_stylesheet_directory() . '/inc/forms/class-ba-quick-support-form.php';
require_once get_stylesheet_directory() . '/inc/woocommerce/class-ba-quick-support-payment-return.php';
BA_Quick_Support_Payment_Return::boot();
add_action( 'afe_register_form_context_providers', array( BA_Quick_Support_Form::class, 'registerContextProvider' ) );
add_action( 'afe_register_data_sources', array( BA_Quick_Support_Form::class, 'registerDataSources' ) );
add_action( 'afe_register_validator_definitions', array( BA_Quick_Support_Form::class, 'registerValidators' ) );
add_action( 'afe_register_action_definitions', array( BA_Quick_Support_Form::class, 'registerActionDefinition' ) );
add_action( 'afe_register_forms', array( BA_Quick_Support_Form::class, 'register' ) );

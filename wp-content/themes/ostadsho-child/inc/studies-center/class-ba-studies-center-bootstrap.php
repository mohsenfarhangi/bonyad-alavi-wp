<?php
/**
 * Bootstrap مرکز مطالعات: مدل داده، متاباکس نشست، REST فیلتر و تب تنظیمات.
 *
 * ویجت‌های Elementor این ماژول در inc/elementor/elementor-widgets.php ثبت می‌شوند.
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_stylesheet_directory() . '/inc/services/class-ba-studies-center-settings-service.php';
require_once get_stylesheet_directory() . '/inc/studies-center/class-ba-studies-formatter.php';
require_once get_stylesheet_directory() . '/inc/studies-center/class-ba-studies-content-types.php';
require_once get_stylesheet_directory() . '/inc/studies-center/class-ba-studies-session-meta.php';
require_once get_stylesheet_directory() . '/inc/studies-center/class-ba-studies-query-service.php';
require_once get_stylesheet_directory() . '/inc/studies-center/class-ba-studies-card-renderer.php';
require_once get_stylesheet_directory() . '/inc/studies-center/class-ba-studies-rest-controller.php';
require_once get_stylesheet_directory() . '/inc/admin/settings/class-ba-studies-center-settings-tab.php';

BA_Studies_Content_Types::register_hooks();
BA_Studies_Session_Meta::register_hooks();
BA_Studies_Rest_Controller::register_hooks();

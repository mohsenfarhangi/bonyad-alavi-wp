<?php
/**
 * پایه مشترک ویجت‌های صفحه «ارتباط با ما».
 *
 * فقط توابع کمکی امن (لینک، جهت متن) و ثبت نام دسته را دارد؛ هیچ markup مشترکی ندارد.
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Elementor\Widget_Base;

abstract class Bonyad_Alavi_Contact_Page_Widget extends Widget_Base {

	const STYLE_HANDLE = 'bonyad-alavi-contact-page';

	public function get_categories() {
		return array( 'bonyad-alavi' );
	}

	public function get_style_depends() {
		return array( self::STYLE_HANDLE );
	}

	/**
	 * فقط http/https/tel/mailto مجاز است؛ هر چیز دیگر (مثل javascript:) رد می‌شود.
	 */
	public static function safe_url( $url ) {
		$url = trim( (string) $url );

		if ( '' === $url ) {
			return '';
		}

		if ( preg_match( '/^[a-z][a-z0-9+.\-]*:/i', $url ) && ! preg_match( '/^(https?|tel|mailto):/i', $url ) ) {
			return '';
		}

		return (string) esc_url( $url, array( 'http', 'https', 'tel', 'mailto' ) );
	}

	/**
	 * جهت متن: فقط rtl یا ltr. مقدار پیش‌فرض rtl (جهت صفحه).
	 */
	public static function safe_dir( $dir ) {
		return 'ltr' === strtolower( (string) $dir ) ? 'ltr' : 'rtl';
	}

	/**
	 * Repeaterها در Elementor گاهی آرایه خالی یا غیرآرایه می‌دهند.
	 */
	protected function get_rows( $settings, $key ) {
		$rows = $settings[ $key ] ?? array();

		return is_array( $rows ) ? array_values( array_filter( $rows, 'is_array' ) ) : array();
	}
}

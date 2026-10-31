<?php
/**
 * سرویس تنظیمات مرکز مطالعات.
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * مالک schema، مقادیر پیش‌فرض و sanitize تنظیمات مرکز مطالعات است.
 *
 * متن‌ها و ظاهر صفحه در Elementor مدیریت می‌شوند؛ این تنظیمات فقط داده‌محور هستند:
 * تفکیک کتاب/پژوهش در publications، override آمار و لینک آرشیوها.
 */
final class BA_Studies_Center_Settings_Service {

	const OPTION_NAME = 'ba_studies_center_settings';
	const CAPABILITY  = 'ba_manage_studies_center';

	/**
	 * مقادیر پیش‌فرض.
	 *
	 * @return array
	 */
	public static function get_defaults() {
		return array(
			'books_cats'     => array(),
			'research_cats'  => array(),
			'stats_books'    => '',
			'stats_sessions' => '',
			'stats_research' => '',
			'books_url'      => '',
			'sessions_url'   => '',
			'research_url'   => '',
		);
	}

	/**
	 * تنظیمات ذخیره‌شده را با پیش‌فرض‌ها ادغام می‌کند.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$saved = get_option( self::OPTION_NAME, array() );

		if ( ! is_array( $saved ) ) {
			$saved = array();
		}

		return wp_parse_args( $saved, self::get_defaults() );
	}

	/**
	 * ورودی تب را sanitize می‌کند.
	 *
	 * @param mixed $input ورودی خام.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$input  = is_array( $input ) ? $input : array();
		$output = self::get_defaults();

		foreach ( array( 'books_cats', 'research_cats' ) as $key ) {
			$ids = isset( $input[ $key ] ) ? (array) $input[ $key ] : array();
			$ids = array_values( array_unique( array_filter( array_map( 'absint', $ids ) ) ) );

			$output[ $key ] = $ids;
		}

		// یک دسته نمی‌تواند هم کتاب باشد هم پژوهش؛ پژوهش اولویت دارد تا محتوا تکرار نشود.
		$output['books_cats'] = array_values( array_diff( $output['books_cats'], $output['research_cats'] ) );

		foreach ( array( 'stats_books', 'stats_sessions', 'stats_research' ) as $key ) {
			$raw            = isset( $input[ $key ] ) ? self::normalize_digits( (string) $input[ $key ] ) : '';
			$raw            = preg_replace( '/[^0-9]/', '', $raw );
			$output[ $key ] = '' === $raw ? '' : (string) absint( $raw );
		}

		foreach ( array( 'books_url', 'sessions_url', 'research_url' ) as $key ) {
			$output[ $key ] = isset( $input[ $key ] ) ? esc_url_raw( trim( (string) $input[ $key ] ) ) : '';
		}

		return $output;
	}

	/**
	 * override دستی یک آمار را برمی‌گرداند؛ null یعنی شمارش خودکار.
	 *
	 * @param string $type books|sessions|research.
	 * @return int|null
	 */
	public static function get_stat_override( $type ) {
		$settings = self::get_settings();
		$key      = 'stats_' . sanitize_key( $type );

		if ( ! isset( $settings[ $key ] ) || '' === $settings[ $key ] ) {
			return null;
		}

		return absint( $settings[ $key ] );
	}

	/**
	 * لینک آرشیو پیش‌فرض یک نوع خروجی.
	 *
	 * ترتیب: لینک ذخیره‌شده تب → آرشیو اولین دسته انتخاب‌شده publications → آرشیو publications.
	 *
	 * @param string $type books|sessions|research.
	 * @return string
	 */
	public static function get_archive_url( $type ) {
		$settings = self::get_settings();
		$type     = sanitize_key( $type );

		if ( ! empty( $settings[ $type . '_url' ] ) ) {
			return $settings[ $type . '_url' ];
		}

		if ( in_array( $type, array( 'books', 'research' ), true ) ) {
			$cats = $settings[ $type . '_cats' ];

			if ( $cats && taxonomy_exists( BA_Studies_Content_Types::PUBLICATION_CAT_TAX ) ) {
				$link = get_term_link( (int) reset( $cats ), BA_Studies_Content_Types::PUBLICATION_CAT_TAX );

				if ( ! is_wp_error( $link ) ) {
					return $link;
				}
			}

			$archive = get_post_type_archive_link( BA_Studies_Content_Types::PUBLICATION_TYPE );

			return $archive ? $archive : '';
		}

		return '';
	}

	/**
	 * ارقام فارسی/عربی را به لاتین تبدیل می‌کند.
	 *
	 * @param string $value مقدار.
	 * @return string
	 */
	public static function normalize_digits( $value ) {
		return strtr(
			(string) $value,
			array(
				'۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
				'۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
				'٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
				'٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
			)
		);
	}
}

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
	const HERO_SLIDES_MAX = 12;

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
			'hero_slides'    => array(),
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

		$output['hero_slides'] = self::sanitize_hero_slides( $input['hero_slides'] ?? array() );

		return $output;
	}

	/**
	 * اسلایدهای تصویری Hero را sanitize می‌کند.
	 *
	 * هر ردیف: تصویر دسکتاپ، تصویر تبلت/موبایل (هر دو پیوست تصویری)، لینک و alt. ردیفی که
	 * هیچ تصویر معتبری ندارد حذف می‌شود؛ اگر فقط یکی از دو تصویر انتخاب شده باشد، همان برای
	 * همه اندازه‌ها استفاده می‌شود. ترتیب ردیف‌ها حفظ می‌شود.
	 *
	 * @param mixed $rows ردیف‌های خام.
	 * @return array[]
	 */
	public static function sanitize_hero_slides( $rows ) {
		if ( ! is_array( $rows ) ) {
			return array();
		}

		$slides = array();

		foreach ( $rows as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$desktop = self::sanitize_image_id( $row['image_id'] ?? 0 );
			$mobile  = self::sanitize_image_id( $row['mobile_image_id'] ?? 0 );

			if ( ! $desktop && ! $mobile ) {
				continue;
			}

			$slides[] = array(
				'image_id'        => $desktop,
				'mobile_image_id' => $mobile,
				'url'             => esc_url_raw( trim( (string) ( $row['url'] ?? '' ) ) ),
				'alt'             => sanitize_text_field( (string) ( $row['alt'] ?? '' ) ),
			);

			if ( count( $slides ) >= self::HERO_SLIDES_MAX ) {
				break;
			}
		}

		return $slides;
	}

	/**
	 * شناسه پیوست فقط وقتی معتبر است که واقعاً یک تصویر باشد.
	 *
	 * @param mixed $value مقدار خام.
	 * @return int
	 */
	private static function sanitize_image_id( $value ) {
		$id = absint( $value );

		return $id && wp_attachment_is_image( $id ) ? $id : 0;
	}

	/**
	 * اسلایدهای تصویری ذخیره‌شده در تنظیمات، آماده رندر.
	 *
	 * اگر حداقل یک اسلاید معتبر باشد، اولویت با همین‌هاست و تصاویر ویجت Elementor نادیده
	 * گرفته می‌شوند. خروجی خالی یعنی تنظیمات تصویری وجود ندارد.
	 *
	 * @return array[] هر آیتم: image, image_w, image_h, mobile_image, mobile_w, mobile_h, alt, url.
	 */
	public static function get_hero_slides() {
		$settings = self::get_settings();
		$slides   = array();

		foreach ( (array) $settings['hero_slides'] as $row ) {
			$desktop = self::get_image_source( (int) ( $row['image_id'] ?? 0 ) );
			$mobile  = self::get_image_source( (int) ( $row['mobile_image_id'] ?? 0 ) );

			if ( ! $desktop && ! $mobile ) {
				continue;
			}

			$desktop = $desktop ? $desktop : $mobile;
			$mobile  = $mobile ? $mobile : $desktop;

			$slides[] = array(
				'image'        => $desktop['url'],
				'image_w'      => $desktop['width'],
				'image_h'      => $desktop['height'],
				'mobile_image' => $mobile['url'],
				'mobile_w'     => $mobile['width'],
				'mobile_h'     => $mobile['height'],
				'alt'          => (string) ( $row['alt'] ?? '' ),
				'url'          => (string) ( $row['url'] ?? '' ),
			);
		}

		return $slides;
	}

	/**
	 * آدرس و ابعاد اصلی یک پیوست تصویری.
	 *
	 * @param int $id شناسه پیوست.
	 * @return array{url: string, width: int, height: int}|null
	 */
	private static function get_image_source( $id ) {
		if ( ! $id ) {
			return null;
		}

		$src = wp_get_attachment_image_src( $id, 'full' );

		if ( ! is_array( $src ) || empty( $src[0] ) ) {
			return null;
		}

		return array(
			'url'    => (string) $src[0],
			'width'  => (int) ( $src[1] ?? 0 ),
			'height' => (int) ( $src[2] ?? 0 ),
		);
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

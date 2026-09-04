<?php
/**
 * سرویس تنظیمات صفحه مرکز هماهنگی حرکت‌های مردمی و جهادی.
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * دسترسی، مقداردهی پیش‌فرض و پاک‌سازی تنظیمات مرکز را در یک نقطه متمرکز می‌کند.
 */
final class BA_Center_Settings_Service {

	const OPTION_NAME = 'ba_jihadi_center_settings';

	/**
	 * تنظیمات پیش‌فرض مرکز را برمی‌گرداند.
	 *
	 * @return array
	 */
	public static function get_defaults() {
		return array(
			'hero_title'          => 'مرکز هماهنگی حرکت های مردمی و جهادی',
			'hero_button_text'    => 'ثبت‌نام گروه‌های مردمی و جهادی',
			'hero_button_url'     => '',
			'hero_background_id'  => 0,
			'stats_title'         => 'گزارش برخط اقدامات',
			'stats_subtitle'      => 'بر پایه آمارهای شاخص منتشرشده در سال ۱۴۰۵',
			'stats'               => self::get_default_stats(),
			'intro_title'         => 'مرکز هماهنگی حرکت‌های مردمی و جهادی',
			'intro_description'   => 'مرکز هماهنگی حرکت‌های مردمی و جهادی بنیاد علوی با هدف شناسایی، توانمندسازی و همراهی ظرفیت‌های مردمی فعالیت می‌کند. برنامه‌های مرکز در سه بخش اصلی «خانه نوآوری جهادی»، «اردوهای توانمندساز» و «پویش‌ها» دنبال می‌شوند و هر بخش مسیر مشخصی برای توسعه ایده‌ها، ارتقای توان گروه‌ها و گسترش مشارکت‌های مردمی دارد.',
			'system_cards'        => self::get_default_system_cards(),
			'partners_kicker'     => '',
			'partners_title'      => '',
			'partners_subtitle'   => '',
			'partners'            => array(),
			'faq_kicker'          => '',
			'faq_title'           => '',
			'faq_subtitle'        => '',
			'faqs'                => array(),
		);
	}

	/**
	 * آمار پیش‌فرض را مطابق طرح تأییدشده برمی‌گرداند.
	 *
	 * @return array
	 */
	private static function get_default_stats() {
		return array(
			array(
				'number'   => '+11,700',
				'title'    => 'زائر اولی اعزام‌شده به اربعین',
				'subtitle' => 'پویش اربعین ۱۴۰۵',
			),
			array(
				'number'   => '20,000',
				'title'    => 'جفت کفش توزیع‌شده برای دانش‌آموزان',
				'subtitle' => 'پویش «همگام علوی»',
			),
			array(
				'number'   => '+2,000',
				'title'    => 'دریافت‌کننده خدمات پزشکی رایگان',
				'subtitle' => 'اردوهای جهادی سلامت ۱۴۰۵',
			),
			array(
				'number'   => '300',
				'title'    => 'منطقه هدف بنیاد علوی',
				'subtitle' => 'گستره برنامه‌های بنیاد',
			),
		);
	}

	/**
	 * کارت‌های پیش‌فرض معرفی مرکز را مطابق طرح برمی‌گرداند.
	 *
	 * @return array
	 */
	private static function get_default_system_cards() {
		return array(
			array( 'icon_id' => 0, 'title' => 'خانه نوآوری جهادی', 'url' => '' ),
			array( 'icon_id' => 0, 'title' => 'اردوهای توانمندساز', 'url' => '' ),
			array( 'icon_id' => 0, 'title' => 'پویش‌ها', 'url' => '' ),
		);
	}

	/**
	 * تنظیمات ذخیره‌شده را همراه با مقادیر پیش‌فرض دریافت می‌کند.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$saved = get_option( self::OPTION_NAME, array() );
		$saved = is_array( $saved ) ? $saved : array();

		return array_replace( self::get_defaults(), $saved );
	}

	/**
	 * یک مقدار متنی داشبورد را فقط در صورت غیرخالی بودن برمی‌گرداند و در غیر این صورت مقدار جایگزین را استفاده می‌کند.
	 *
	 * @param string $key      کلید تنظیمات.
	 * @param mixed  $fallback مقدار جایگزین.
	 * @return mixed
	 */
	public static function resolve_value( $key, $fallback = '' ) {
		$settings = self::get_settings();
		$value    = isset( $settings[ $key ] ) ? $settings[ $key ] : null;

		if ( is_string( $value ) ) {
			return '' !== trim( $value ) ? $value : $fallback;
		}

		return ! empty( $value ) ? $value : $fallback;
	}

	/**
	 * مشخص می‌کند آیا حداقل یک همراه معتبر در تنظیمات داشبورد وجود دارد یا خیر.
	 *
	 * @return bool
	 */
	public static function has_dashboard_partners() {
		$settings = self::get_settings();

		foreach ( (array) $settings['partners'] as $partner ) {
			if ( self::is_valid_partner( $partner ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * مشخص می‌کند آیا حداقل یک پرسش معتبر در تنظیمات داشبورد وجود دارد یا خیر.
	 *
	 * @return bool
	 */
	public static function has_dashboard_faqs() {
		$settings = self::get_settings();

		foreach ( (array) $settings['faqs'] as $faq ) {
			if ( ! empty( $faq['question'] ) || ! empty( $faq['answer'] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * تنظیمات ارسالی از داشبورد را پیش از ذخیره پاک‌سازی می‌کند.
	 *
	 * @param mixed $input داده خام فرم.
	 * @return array
	 */
	public static function sanitize( $input ) {
		$input = is_array( $input ) ? $input : array();

		return array(
			'hero_title'         => sanitize_text_field( self::array_value( $input, 'hero_title' ) ),
			'hero_button_text'   => sanitize_text_field( self::array_value( $input, 'hero_button_text' ) ),
			'hero_button_url'    => esc_url_raw( self::array_value( $input, 'hero_button_url' ) ),
			'hero_background_id' => absint( self::array_value( $input, 'hero_background_id', 0 ) ),
			'stats_title'        => sanitize_text_field( self::array_value( $input, 'stats_title' ) ),
			'stats_subtitle'     => sanitize_text_field( self::array_value( $input, 'stats_subtitle' ) ),
			'stats'              => self::sanitize_stats( self::array_value( $input, 'stats', array() ) ),
			'intro_title'        => sanitize_text_field( self::array_value( $input, 'intro_title' ) ),
			'intro_description'  => wp_kses_post( self::array_value( $input, 'intro_description' ) ),
			'system_cards'       => self::sanitize_system_cards( self::array_value( $input, 'system_cards', array() ) ),
			'partners_kicker'    => sanitize_text_field( self::array_value( $input, 'partners_kicker' ) ),
			'partners_title'     => sanitize_text_field( self::array_value( $input, 'partners_title' ) ),
			'partners_subtitle'  => sanitize_text_field( self::array_value( $input, 'partners_subtitle' ) ),
			'partners'           => self::sanitize_partners( self::array_value( $input, 'partners', array() ) ),
			'faq_kicker'         => sanitize_text_field( self::array_value( $input, 'faq_kicker' ) ),
			'faq_title'          => sanitize_text_field( self::array_value( $input, 'faq_title' ) ),
			'faq_subtitle'       => sanitize_text_field( self::array_value( $input, 'faq_subtitle' ) ),
			'faqs'               => self::sanitize_faqs( self::array_value( $input, 'faqs', array() ) ),
		);
	}

	/**
	 * یک کلید آرایه را با مقدار پیش‌فرض امن دریافت می‌کند.
	 *
	 * @param array  $array   آرایه ورودی.
	 * @param string $key     کلید.
	 * @param mixed  $default مقدار پیش‌فرض.
	 * @return mixed
	 */
	private static function array_value( array $array, $key, $default = '' ) {
		return isset( $array[ $key ] ) ? $array[ $key ] : $default;
	}

	/**
	 * ردیف‌های آمار را پاک‌سازی و ردیف‌های کاملاً خالی را حذف می‌کند.
	 *
	 * @param mixed $items ردیف‌ها.
	 * @return array
	 */
	private static function sanitize_stats( $items ) {
		$clean = array();

		foreach ( is_array( $items ) ? $items : array() as $item ) {
			$item = is_array( $item ) ? $item : array();
			$row  = array(
				'number'   => sanitize_text_field( self::array_value( $item, 'number' ) ),
				'title'    => sanitize_text_field( self::array_value( $item, 'title' ) ),
				'subtitle' => sanitize_text_field( self::array_value( $item, 'subtitle' ) ),
			);

			if ( array_filter( $row, 'strlen' ) ) {
				$clean[] = $row;
			}
		}

		return $clean;
	}

	/**
	 * کارت‌های سامانه را پاک‌سازی می‌کند.
	 *
	 * @param mixed $items ردیف‌ها.
	 * @return array
	 */
	private static function sanitize_system_cards( $items ) {
		$clean = array();

		foreach ( is_array( $items ) ? $items : array() as $item ) {
			$item = is_array( $item ) ? $item : array();
			$row  = array(
				'icon_id' => absint( self::array_value( $item, 'icon_id', 0 ) ),
				'title'   => sanitize_text_field( self::array_value( $item, 'title' ) ),
				'url'     => esc_url_raw( self::array_value( $item, 'url' ) ),
			);

			if ( $row['icon_id'] || $row['title'] || $row['url'] ) {
				$clean[] = $row;
			}
		}

		return $clean;
	}

	/**
	 * همراهان مرکز را با رعایت انتخاب انحصاری تصویر یا SVG پاک‌سازی می‌کند.
	 *
	 * @param mixed $items ردیف‌ها.
	 * @return array
	 */
	private static function sanitize_partners( $items ) {
		$clean = array();

		foreach ( is_array( $items ) ? $items : array() as $item ) {
			$item       = is_array( $item ) ? $item : array();
			$media_type = 'svg' === self::array_value( $item, 'media_type' ) ? 'svg' : 'image';
			$row        = array(
				'media_type' => $media_type,
				'image_id'   => 'image' === $media_type ? absint( self::array_value( $item, 'image_id', 0 ) ) : 0,
				'svg_id'     => 'svg' === $media_type ? absint( self::array_value( $item, 'svg_id', 0 ) ) : 0,
				'title'      => sanitize_text_field( self::array_value( $item, 'title' ) ),
				'url'        => esc_url_raw( self::array_value( $item, 'url' ) ),
			);

			if ( self::is_valid_partner( $row ) ) {
				$clean[] = $row;
			}
		}

		return $clean;
	}

	/**
	 * معتبر بودن یک همراه را بر اساس حداقل یکی از فیلدهای قابل نمایش بررسی می‌کند.
	 *
	 * @param mixed $partner داده همراه.
	 * @return bool
	 */
	private static function is_valid_partner( $partner ) {
		if ( ! is_array( $partner ) ) {
			return false;
		}

		return ! empty( $partner['image_id'] ) || ! empty( $partner['svg_id'] ) || ! empty( $partner['title'] );
	}

	/**
	 * پرسش‌های متداول را پاک‌سازی و ردیف‌های کاملاً خالی را حذف می‌کند.
	 *
	 * @param mixed $items ردیف‌ها.
	 * @return array
	 */
	private static function sanitize_faqs( $items ) {
		$clean = array();

		foreach ( is_array( $items ) ? $items : array() as $item ) {
			$item = is_array( $item ) ? $item : array();
			$row  = array(
				'question' => sanitize_text_field( self::array_value( $item, 'question' ) ),
				'answer'   => wp_kses_post( self::array_value( $item, 'answer' ) ),
			);

			if ( $row['question'] || $row['answer'] ) {
				$clean[] = $row;
			}
		}

		return $clean;
	}
}

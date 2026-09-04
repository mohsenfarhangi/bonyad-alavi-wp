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
 * دسترسی، اولویت‌دهی، مقداردهی پیش‌فرض و پاک‌سازی تنظیمات مرکز را متمرکز می‌کند.
 */
final class BA_Center_Settings_Service {

	const OPTION_NAME = 'ba_jihadi_center_settings';
	const SCHEMA_VERSION = '0.4.0';

	/**
	 * تنظیمات پیش‌فرض مرکز را برمی‌گرداند.
	 *
	 * @return array
	 */
	public static function get_defaults() {
		return array(
			'hero_enabled'            => '1',
			'hero_eyebrow'            => 'بنیاد علوی',
			'hero_title'              => 'مرکز هماهنگی حرکت های مردمی و جهادی',
			'hero_button_text'        => 'ثبت‌نام گروه‌های مردمی و جهادی',
			'hero_button_url'         => '',
			'hero_background_id'      => 0,
			'stats_enabled'           => '1',
			'stats_title'             => 'گزارش برخط اقدامات',
			'stats_subtitle'          => 'بر پایه آمارهای شاخص منتشرشده در سال ۱۴۰۵',
			'stats'                   => self::get_default_stats(),
			'intro_enabled'           => '1',
			'intro_kicker'            => 'معرفی مرکز',
			'intro_title'             => 'مرکز هماهنگی حرکت‌های مردمی و جهادی',
			'intro_description'       => 'مرکز هماهنگی حرکت‌های مردمی و جهادی بنیاد علوی با هدف شناسایی، توانمندسازی و همراهی ظرفیت‌های مردمی فعالیت می‌کند. برنامه‌های مرکز در سه بخش اصلی «خانه نوآوری جهادی»، «اردوهای توانمندساز» و «پویش‌ها» دنبال می‌شوند و هر بخش مسیر مشخصی برای توسعه ایده‌ها، ارتقای توان گروه‌ها و گسترش مشارکت‌های مردمی دارد.',
			'system_cards'            => self::get_default_system_cards(),
			'news_enabled'            => '1',
			'news_kicker'             => 'اخبار مرکز',
			'news_title'              => 'آخرین اخبار و رویدادها',
			'news_subtitle'           => 'تازه‌ترین خبرها، گزارش‌ها و رویدادهای مرتبط با فعالیت‌های مرکز و گروه‌های مردمی.',
			'news_all_text'           => 'مشاهده همه اخبار',
			'news_all_url'            => '',
			'news_post_types'         => array( 'post' ),
			'news_posts_per_page'     => 4,
			'news_categories'         => array(),
			'news_tags'               => array(),
			'news_authors'            => array(),
			'news_include_ids'        => '',
			'news_exclude_ids'        => '',
			'news_orderby'            => 'date',
			'news_order'              => 'DESC',
			'news_offset'             => 0,
			'news_ignore_sticky'      => 'yes',
			'news_date_after'         => '',
			'news_date_before'        => '',
			'media_enabled'           => '1',
			'media_kicker'            => 'روایت تصویری فعالیت‌ها',
			'media_title'             => 'چندرسانه‌ای',
			'media_subtitle'          => 'ویدیوها و گزارش‌های تصویری از فعالیت‌ها، اردوها و برنامه‌های اجراشده مرکز.',
			'media_all_text'          => 'مشاهده همه ویدیوها',
			'media_all_url'           => '',
			'media_duration_meta_key' => '',
			'media_post_types'        => array( 'post' ),
			'media_posts_per_page'    => 3,
			'media_categories'        => array(),
			'media_tags'              => array(),
			'media_authors'           => array(),
			'media_include_ids'       => '',
			'media_exclude_ids'       => '',
			'media_orderby'           => 'date',
			'media_order'             => 'DESC',
			'media_offset'            => 0,
			'media_ignore_sticky'     => 'yes',
			'media_date_after'        => '',
			'media_date_before'       => '',
			'partners_enabled'        => '1',
			'partners_kicker'         => 'همراهان مرکز',
			'partners_title'          => 'نهادها و مجموعه‌های همکار',
			'partners_subtitle'       => 'نهادها و مجموعه‌هایی که در مسیر برنامه‌های مرکز همراه هستند.',
			'partners'                => array(),
			'faq_enabled'             => '1',
			'faq_kicker'              => 'پرسش‌های متداول',
			'faq_title'               => 'پاسخ به سوالات رایج',
			'faq_subtitle'            => 'پاسخ پرسش‌های رایج کاربران سامانه‌ها و مخاطبان مرکز در این بخش قرار می‌گیرد.',
			'faqs'                    => array(),
		);
	}

	/**
	 * آمار پیش‌فرض را مطابق طرح تأییدشده برمی‌گرداند.
	 *
	 * @return array
	 */
	private static function get_default_stats() {
		return array(
			array( 'number' => '+11,700', 'title' => 'زائر اولی اعزام‌شده به اربعین', 'subtitle' => 'پویش اربعین ۱۴۰۵' ),
			array( 'number' => '20,000', 'title' => 'جفت کفش توزیع‌شده برای دانش‌آموزان', 'subtitle' => 'پویش «همگام علوی»' ),
			array( 'number' => '+2,000', 'title' => 'دریافت‌کننده خدمات پزشکی رایگان', 'subtitle' => 'اردوهای جهادی سلامت ۱۴۰۵' ),
			array( 'number' => '300', 'title' => 'منطقه هدف بنیاد علوی', 'subtitle' => 'گستره برنامه‌های بنیاد' ),
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
	 * فقط داده واقعاً ذخیره‌شده در option را بدون تزریق پیش‌فرض برمی‌گرداند.
	 *
	 * @return array
	 */
	public static function get_saved_settings() {
		$saved = get_option( self::OPTION_NAME, array() );
		return is_array( $saved ) ? $saved : array();
	}

	/**
	 * تنظیمات ذخیره‌شده را همراه با مقادیر پیش‌فرض برای فرم مدیریت برمی‌گرداند.
	 *
	 * @return array
	 */
	public static function get_settings() {
		$defaults = self::get_defaults();
		$saved    = self::get_saved_settings();
		$settings = array_replace( $defaults, $saved );

		$is_current_schema = isset( $saved['settings_schema_version'] ) && version_compare( (string) $saved['settings_schema_version'], self::SCHEMA_VERSION, '>=' );
		if ( ! $is_current_schema ) {
			foreach ( array( 'partners_kicker', 'partners_title', 'partners_subtitle', 'faq_kicker', 'faq_title', 'faq_subtitle' ) as $legacy_fallback_key ) {
				if ( array_key_exists( $legacy_fallback_key, $saved ) && '' === trim( (string) $saved[ $legacy_fallback_key ] ) ) {
					$settings[ $legacy_fallback_key ] = $defaults[ $legacy_fallback_key ];
				}
			}
		}

		return $settings;
	}

	/**
	 * بررسی می‌کند یک کلید مشخص واقعاً در داشبورد ذخیره شده است یا خیر.
	 * این تفاوت برای تشخیص اولویت داشبورد نسبت به Elementor ضروری است.
	 *
	 * @param string $key کلید تنظیمات.
	 * @return bool
	 */
	public static function has_dashboard_override( $key ) {
		return array_key_exists( $key, self::get_saved_settings() );
	}

	/**
	 * تنظیمات مؤثر ویجت را با اولویت مقادیر ذخیره‌شده داشبورد می‌سازد.
	 * مقادیر لینک داشبورد به ساختار استاندارد URL کنترل Elementor تبدیل می‌شوند.
	 *
	 * @param array $elementor تنظیمات خام Elementor.
	 * @return array
	 */
	public static function get_effective_settings( array $elementor ) {
		$saved     = self::get_saved_settings();
		$settings  = self::get_settings();
		$effective = $elementor;
		$used_dashboard = array();

		foreach ( array_keys( self::get_defaults() ) as $key ) {
			if ( self::should_use_dashboard_value( $key, $saved, $settings ) ) {
				$effective[ $key ] = $settings[ $key ];
				$used_dashboard[ $key ] = true;
			} elseif ( ! array_key_exists( $key, $effective ) ) {
				$effective[ $key ] = $settings[ $key ];
			}
		}

		foreach ( array( 'hero_button_url', 'news_all_url', 'media_all_url' ) as $link_key ) {
			if ( ! empty( $used_dashboard[ $link_key ] ) ) {
				$effective[ $link_key ] = self::normalize_link( $settings[ $link_key ] );
			}
		}

		return $effective;
	}

	/**
	 * با حفظ سازگاری نسخه‌های قدیمی مشخص می‌کند یک مقدار داشبورد باید روی Elementor اعمال شود یا خیر.
	 * پس از اولین ذخیره با schema جدید، حتی مقدار خالی نیز یک انتخاب آگاهانه و دارای اولویت است.
	 *
	 * @param string $key      کلید تنظیمات.
	 * @param array  $saved    داده خام ذخیره‌شده.
	 * @param array  $settings داده نهایی داشبورد.
	 * @return bool
	 */
	private static function should_use_dashboard_value( $key, array $saved, array $settings ) {
		if ( ! array_key_exists( $key, $saved ) ) {
			return false;
		}

		$is_current_schema = isset( $saved['settings_schema_version'] ) && version_compare( (string) $saved['settings_schema_version'], self::SCHEMA_VERSION, '>=' );
		if ( $is_current_schema ) {
			return true;
		}

		if ( in_array( $key, array( 'partners_kicker', 'partners_title', 'partners_subtitle', 'faq_kicker', 'faq_title', 'faq_subtitle' ), true ) ) {
			return '' !== trim( (string) $saved[ $key ] );
		}
		if ( 'partners' === $key ) {
			return self::has_dashboard_partners();
		}
		if ( 'faqs' === $key ) {
			return self::has_dashboard_faqs();
		}

		return true;
	}

	/**
	 * وضعیت فعال بودن یک سکشن را از تنظیمات مؤثر می‌خواند.
	 *
	 * @param array  $settings تنظیمات مؤثر.
	 * @param string $section  نام سکشن بدون پسوند enabled.
	 * @return bool
	 */
	public static function is_section_enabled( array $settings, $section ) {
		$value = isset( $settings[ $section . '_enabled' ] ) ? $settings[ $section . '_enabled' ] : 'yes';
		return in_array( $value, array( true, 1, '1', 'yes', 'on' ), true );
	}

	/**
	 * یک مقدار لینک ساده داشبورد را به ساختار URL کنترل Elementor تبدیل می‌کند.
	 *
	 * @param mixed $url نشانی ذخیره‌شده.
	 * @return array
	 */
	private static function normalize_link( $url ) {
		return array(
			'url'         => is_string( $url ) ? $url : '',
			'is_external' => '',
			'nofollow'    => '',
		);
	}

	/**
	 * مشخص می‌کند آیا حداقل یک همراه معتبر در تنظیمات داشبورد وجود دارد یا خیر.
	 *
	 * @return bool
	 */
	public static function has_dashboard_partners() {
		foreach ( (array) self::get_settings()['partners'] as $partner ) {
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
		foreach ( (array) self::get_settings()['faqs'] as $faq ) {
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
		$output = array(
			'settings_schema_version' => self::SCHEMA_VERSION,
			'hero_enabled'            => self::sanitize_toggle( self::array_value( $input, 'hero_enabled', '0' ) ),
			'hero_eyebrow'            => sanitize_text_field( self::array_value( $input, 'hero_eyebrow' ) ),
			'hero_title'              => sanitize_text_field( self::array_value( $input, 'hero_title' ) ),
			'hero_button_text'        => sanitize_text_field( self::array_value( $input, 'hero_button_text' ) ),
			'hero_button_url'         => esc_url_raw( self::array_value( $input, 'hero_button_url' ) ),
			'hero_background_id'      => absint( self::array_value( $input, 'hero_background_id', 0 ) ),
			'stats_enabled'           => self::sanitize_toggle( self::array_value( $input, 'stats_enabled', '0' ) ),
			'stats_title'             => sanitize_text_field( self::array_value( $input, 'stats_title' ) ),
			'stats_subtitle'          => sanitize_text_field( self::array_value( $input, 'stats_subtitle' ) ),
			'stats'                   => self::sanitize_stats( self::array_value( $input, 'stats', array() ) ),
			'intro_enabled'           => self::sanitize_toggle( self::array_value( $input, 'intro_enabled', '0' ) ),
			'intro_kicker'            => sanitize_text_field( self::array_value( $input, 'intro_kicker' ) ),
			'intro_title'             => sanitize_text_field( self::array_value( $input, 'intro_title' ) ),
			'intro_description'       => wp_kses_post( self::array_value( $input, 'intro_description' ) ),
			'system_cards'            => self::sanitize_system_cards( self::array_value( $input, 'system_cards', array() ) ),
			'news_enabled'            => self::sanitize_toggle( self::array_value( $input, 'news_enabled', '0' ) ),
			'news_kicker'             => sanitize_text_field( self::array_value( $input, 'news_kicker' ) ),
			'news_title'              => sanitize_text_field( self::array_value( $input, 'news_title' ) ),
			'news_subtitle'           => sanitize_text_field( self::array_value( $input, 'news_subtitle' ) ),
			'news_all_text'           => sanitize_text_field( self::array_value( $input, 'news_all_text' ) ),
			'news_all_url'            => esc_url_raw( self::array_value( $input, 'news_all_url' ) ),
			'media_enabled'           => self::sanitize_toggle( self::array_value( $input, 'media_enabled', '0' ) ),
			'media_kicker'            => sanitize_text_field( self::array_value( $input, 'media_kicker' ) ),
			'media_title'             => sanitize_text_field( self::array_value( $input, 'media_title' ) ),
			'media_subtitle'          => sanitize_text_field( self::array_value( $input, 'media_subtitle' ) ),
			'media_all_text'          => sanitize_text_field( self::array_value( $input, 'media_all_text' ) ),
			'media_all_url'           => esc_url_raw( self::array_value( $input, 'media_all_url' ) ),
			'media_duration_meta_key' => sanitize_key( self::array_value( $input, 'media_duration_meta_key' ) ),
			'partners_enabled'        => self::sanitize_toggle( self::array_value( $input, 'partners_enabled', '0' ) ),
			'partners_kicker'         => sanitize_text_field( self::array_value( $input, 'partners_kicker' ) ),
			'partners_title'          => sanitize_text_field( self::array_value( $input, 'partners_title' ) ),
			'partners_subtitle'       => sanitize_text_field( self::array_value( $input, 'partners_subtitle' ) ),
			'partners'                => self::sanitize_partners( self::array_value( $input, 'partners', array() ) ),
			'faq_enabled'             => self::sanitize_toggle( self::array_value( $input, 'faq_enabled', '0' ) ),
			'faq_kicker'              => sanitize_text_field( self::array_value( $input, 'faq_kicker' ) ),
			'faq_title'               => sanitize_text_field( self::array_value( $input, 'faq_title' ) ),
			'faq_subtitle'            => sanitize_text_field( self::array_value( $input, 'faq_subtitle' ) ),
			'faqs'                    => self::sanitize_faqs( self::array_value( $input, 'faqs', array() ) ),
		);

		$output = array_merge( $output, self::sanitize_query_settings( $input, 'news', 4 ) );
		$output = array_merge( $output, self::sanitize_query_settings( $input, 'media', 3 ) );
		return $output;
	}

	/**
	 * تنظیمات Query یک سکشن را با نام‌گذاری مشترک Elementor پاک‌سازی می‌کند.
	 *
	 * @param array  $input         داده فرم.
	 * @param string $prefix        پیشوند سکشن.
	 * @param int    $default_count تعداد پیش‌فرض.
	 * @return array
	 */
	private static function sanitize_query_settings( array $input, $prefix, $default_count ) {
		$orderby_allowed = array( 'date', 'modified', 'title', 'menu_order', 'rand', 'ID' );
		$orderby = (string) self::array_value( $input, $prefix . '_orderby', 'date' );
		$order   = strtoupper( (string) self::array_value( $input, $prefix . '_order', 'DESC' ) );
		$post_types = array_map( 'sanitize_key', (array) self::array_value( $input, $prefix . '_post_types', array( 'post' ) ) );
		$available  = get_post_types( array( 'public' => true ), 'names' );
		$post_types = array_values( array_intersect( $post_types, $available ) );

		return array(
			$prefix . '_post_types'     => $post_types ? $post_types : array( 'post' ),
			$prefix . '_posts_per_page' => max( 1, min( 30, absint( self::array_value( $input, $prefix . '_posts_per_page', $default_count ) ) ) ),
			$prefix . '_categories'     => self::sanitize_ids( self::array_value( $input, $prefix . '_categories', array() ) ),
			$prefix . '_tags'           => self::sanitize_ids( self::array_value( $input, $prefix . '_tags', array() ) ),
			$prefix . '_authors'        => self::sanitize_ids( self::array_value( $input, $prefix . '_authors', array() ) ),
			$prefix . '_include_ids'    => sanitize_text_field( self::array_value( $input, $prefix . '_include_ids' ) ),
			$prefix . '_exclude_ids'    => sanitize_text_field( self::array_value( $input, $prefix . '_exclude_ids' ) ),
			$prefix . '_orderby'        => in_array( $orderby, $orderby_allowed, true ) ? $orderby : 'date',
			$prefix . '_order'          => 'ASC' === $order ? 'ASC' : 'DESC',
			$prefix . '_offset'         => absint( self::array_value( $input, $prefix . '_offset', 0 ) ),
			$prefix . '_ignore_sticky'  => self::sanitize_elementor_toggle( self::array_value( $input, $prefix . '_ignore_sticky', '' ) ),
			$prefix . '_date_after'     => sanitize_text_field( self::array_value( $input, $prefix . '_date_after' ) ),
			$prefix . '_date_before'    => sanitize_text_field( self::array_value( $input, $prefix . '_date_before' ) ),
		);
	}

	/**
	 * مقدار checkbox داشبورد را به 1 یا 0 محدود می‌کند.
	 *
	 * @param mixed $value مقدار خام.
	 * @return string
	 */
	private static function sanitize_toggle( $value ) {
		return in_array( $value, array( 1, '1', true, 'yes', 'on' ), true ) ? '1' : '0';
	}

	/**
	 * مقدار Switcher سازگار با Query Service را به yes یا رشته خالی محدود می‌کند.
	 *
	 * @param mixed $value مقدار خام.
	 * @return string
	 */
	private static function sanitize_elementor_toggle( $value ) {
		return in_array( $value, array( 1, '1', true, 'yes', 'on' ), true ) ? 'yes' : '';
	}

	/**
	 * یک لیست شناسه را به اعداد مثبت و یکتا تبدیل می‌کند.
	 *
	 * @param mixed $items داده خام.
	 * @return array
	 */
	private static function sanitize_ids( $items ) {
		return array_values( array_unique( array_filter( array_map( 'absint', (array) $items ) ) ) );
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
			$row = array(
				'number' => sanitize_text_field( self::array_value( $item, 'number' ) ),
				'title' => sanitize_text_field( self::array_value( $item, 'title' ) ),
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
			$row = array(
				'icon_id' => absint( self::array_value( $item, 'icon_id', 0 ) ),
				'title' => sanitize_text_field( self::array_value( $item, 'title' ) ),
				'url' => esc_url_raw( self::array_value( $item, 'url' ) ),
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
			$item = is_array( $item ) ? $item : array();
			$media_type = 'svg' === self::array_value( $item, 'media_type' ) ? 'svg' : 'image';
			$row = array(
				'media_type' => $media_type,
				'image_id' => 'image' === $media_type ? absint( self::array_value( $item, 'image_id', 0 ) ) : 0,
				'svg_id' => 'svg' === $media_type ? absint( self::array_value( $item, 'svg_id', 0 ) ) : 0,
				'title' => sanitize_text_field( self::array_value( $item, 'title' ) ),
				'url' => esc_url_raw( self::array_value( $item, 'url' ) ),
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
		return is_array( $partner ) && ( ! empty( $partner['image_id'] ) || ! empty( $partner['svg_id'] ) || ! empty( $partner['title'] ) );
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
			$row = array(
				'question' => sanitize_text_field( self::array_value( $item, 'question' ) ),
				'answer' => wp_kses_post( self::array_value( $item, 'answer' ) ),
			);
			if ( $row['question'] || $row['answer'] ) {
				$clean[] = $row;
			}
		}
		return $clean;
	}
}

<?php
/**
 * مدل داده مرکز مطالعات: نوع محتوای نشست و Taxonomyهای حوزه و نوع پژوهش.
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ثبت CPT نشست، Taxonomyهای مشترک و ساخت یک‌باره حوزه‌های پیش‌فرض.
 *
 * کتاب‌ها و پژوهش‌ها همان Post Type موجود `publications` هستند و این کلاس
 * دسترسی ویرایش آن را تغییر نمی‌دهد؛ فقط Taxonomyهای جدید را به آن متصل می‌کند.
 */
final class BA_Studies_Content_Types {

	const SESSION_POST_TYPE    = 'ba_study_session';
	const AREA_TAXONOMY        = 'ba_study_area';
	const RESEARCH_TYPE_TAX    = 'ba_research_type';
	const PUBLICATION_TYPE     = 'publications';
	const PUBLICATION_CAT_TAX  = 'publications-cat';
	const ASSIGN_TERMS_CAP     = 'ba_assign_study_terms';
	const AREAS_SEEDED_OPTION  = 'ba_studies_areas_seeded';
	const REWRITE_VERSION      = '1';
	const REWRITE_OPTION       = 'ba_studies_rewrite_version';

	/**
	 * هوک‌های ثبت مدل داده را متصل می‌کند.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		// اولویت ۲۰ تا Post Type والد publications قبل از اتصال Taxonomy ثبت شده باشد.
		add_action( 'init', array( __CLASS__, 'register' ), 20 );
		add_action( 'admin_init', array( __CLASS__, 'seed_default_areas' ) );
		add_action( 'admin_init', array( __CLASS__, 'maybe_flush_rewrite_rules' ), 30 );
		add_filter( 'user_has_cap', array( __CLASS__, 'grant_assign_terms_cap' ), 10, 4 );
	}

	/**
	 * حوزه‌های پیش‌فرض مرکز را برمی‌گرداند (نام => slug).
	 *
	 * @return array<string,string>
	 */
	public static function get_default_areas() {
		return array(
			'مدیریت دانش'             => 'knowledge-management',
			'نوآوری اجتماعی'          => 'social-innovation',
			'پیشرفت و آبادانی'        => 'development',
			'توانمندسازی اقتصادی'     => 'economic-empowerment',
			'آموزش، مهارت و پرورش'    => 'education-skills',
			'بهداشت و درمان'          => 'health',
			'عمرانی و زیرساختی'       => 'infrastructure',
			'سایر حوزه‌ها'            => 'other-areas',
		);
	}

	/**
	 * CPT نشست و Taxonomyها را ثبت می‌کند.
	 *
	 * @return void
	 */
	public static function register() {
		self::register_session_post_type();
		self::register_area_taxonomy();
		self::register_research_type_taxonomy();
	}

	/**
	 * Capabilityهای primitive نشست را همگی به Capability مرکز مطالعات وصل می‌کند.
	 *
	 * با map_meta_cap=true، متاکپ‌های edit_post/delete_post/read_post به همین
	 * primitiveها نگاشت می‌شوند؛ بنابراین یک Capability دسترسی کامل نشست‌ها را می‌دهد.
	 *
	 * @return array<string,string>
	 */
	public static function get_session_capabilities() {
		$cap = BA_Studies_Center_Settings_Service::CAPABILITY;

		return array(
			'edit_posts'             => $cap,
			'edit_others_posts'      => $cap,
			'edit_private_posts'     => $cap,
			'edit_published_posts'   => $cap,
			'publish_posts'          => $cap,
			'read_private_posts'     => $cap,
			'delete_posts'           => $cap,
			'delete_private_posts'   => $cap,
			'delete_published_posts' => $cap,
			'delete_others_posts'    => $cap,
			'create_posts'           => $cap,
		);
	}

	/**
	 * CPT نشست‌ها و رویدادهای تخصصی.
	 *
	 * @return void
	 */
	private static function register_session_post_type() {
		register_post_type(
			self::SESSION_POST_TYPE,
			array(
				'labels'          => array(
					'name'               => 'نشست‌ها و رویدادها',
					'singular_name'      => 'نشست',
					'menu_name'          => 'نشست‌های مرکز مطالعات',
					'add_new'            => 'افزودن نشست',
					'add_new_item'       => 'افزودن نشست جدید',
					'edit_item'          => 'ویرایش نشست',
					'new_item'           => 'نشست جدید',
					'view_item'          => 'مشاهده نشست',
					'search_items'       => 'جستجوی نشست‌ها',
					'not_found'          => 'نشستی یافت نشد.',
					'not_found_in_trash' => 'نشستی در زباله‌دان نیست.',
					'all_items'          => 'همه نشست‌ها',
				),
				'public'          => true,
				'show_in_rest'    => true,
				'has_archive'     => false,
				'menu_icon'       => 'dashicons-video-alt3',
				'menu_position'   => 21,
				'supports'        => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' ),
				'rewrite'         => array(
					'slug'       => 'studies-center/sessions',
					'with_front' => false,
				),
				'capability_type' => array( 'ba_study_session', 'ba_study_sessions' ),
				'capabilities'    => self::get_session_capabilities(),
				'map_meta_cap'    => true,
				'taxonomies'      => array( self::AREA_TAXONOMY ),
			)
		);
	}

	/**
	 * Taxonomy مشترک «حوزه» برای کتاب، پژوهش و نشست.
	 *
	 * @return void
	 */
	private static function register_area_taxonomy() {
		register_taxonomy(
			self::AREA_TAXONOMY,
			array( self::SESSION_POST_TYPE, self::PUBLICATION_TYPE ),
			array(
				'labels'            => array(
					'name'          => 'حوزه‌های مرکز مطالعات',
					'singular_name' => 'حوزه',
					'menu_name'     => 'حوزه‌ها',
					'all_items'     => 'همه حوزه‌ها',
					'edit_item'     => 'ویرایش حوزه',
					'add_new_item'  => 'افزودن حوزه',
					'search_items'  => 'جستجوی حوزه‌ها',
				),
				'hierarchical'      => true,
				'public'            => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => array(
					'slug'       => 'studies-center/area',
					'with_front' => false,
				),
				'capabilities'      => self::get_term_capabilities(),
			)
		);
	}

	/**
	 * Taxonomy قابل مدیریت «نوع پژوهش» روی publications.
	 *
	 * @return void
	 */
	private static function register_research_type_taxonomy() {
		register_taxonomy(
			self::RESEARCH_TYPE_TAX,
			array( self::PUBLICATION_TYPE ),
			array(
				'labels'            => array(
					'name'          => 'نوع پژوهش',
					'singular_name' => 'نوع پژوهش',
					'menu_name'     => 'نوع پژوهش',
					'all_items'     => 'همه انواع پژوهش',
					'edit_item'     => 'ویرایش نوع پژوهش',
					'add_new_item'  => 'افزودن نوع پژوهش',
				),
				'hierarchical'      => true,
				'public'            => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => false,
				'capabilities'      => self::get_term_capabilities(),
			)
		);
	}

	/**
	 * مدیریت termها با Capability مرکز؛ انتساب term با Capability مشتق‌شده.
	 *
	 * @return array<string,string>
	 */
	private static function get_term_capabilities() {
		$cap = BA_Studies_Center_Settings_Service::CAPABILITY;

		return array(
			'manage_terms' => $cap,
			'edit_terms'   => $cap,
			'delete_terms' => $cap,
			'assign_terms' => self::ASSIGN_TERMS_CAP,
		);
	}

	/**
	 * انتساب حوزه/نوع پژوهش را به ویرایشگران فعلی publications و مدیران مرکز می‌دهد.
	 *
	 * فقط Capability اختصاصی همین بخش را مشتق می‌کند و دسترسی publications را تغییر نمی‌دهد.
	 *
	 * @param array   $allcaps همه Capabilityهای کاربر.
	 * @param array   $caps    Capabilityهای درخواست‌شده.
	 * @param array   $args    آرگومان‌ها.
	 * @param WP_User $user    کاربر.
	 * @return array
	 */
	public static function grant_assign_terms_cap( $allcaps, $caps, $args, $user ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed
		if ( ! in_array( self::ASSIGN_TERMS_CAP, (array) $caps, true ) ) {
			return $allcaps;
		}

		if ( ! empty( $allcaps['edit_posts'] ) || ! empty( $allcaps[ BA_Studies_Center_Settings_Service::CAPABILITY ] ) ) {
			$allcaps[ self::ASSIGN_TERMS_CAP ] = true;
		}

		return $allcaps;
	}

	/**
	 * حوزه‌های پیش‌فرض را فقط یک‌بار می‌سازد.
	 *
	 * پس از ثبت flag، حتی اگر مدیر حوزه‌ای را حذف یا ویرایش کند، دوباره ساخته نمی‌شود.
	 *
	 * @return void
	 */
	public static function seed_default_areas() {
		if ( get_option( self::AREAS_SEEDED_OPTION ) || ! taxonomy_exists( self::AREA_TAXONOMY ) ) {
			return;
		}

		foreach ( self::get_default_areas() as $name => $slug ) {
			if ( term_exists( $name, self::AREA_TAXONOMY ) || term_exists( $slug, self::AREA_TAXONOMY ) ) {
				continue;
			}

			wp_insert_term( $name, self::AREA_TAXONOMY, array( 'slug' => $slug ) );
		}

		update_option( self::AREAS_SEEDED_OPTION, gmdate( 'c' ), false );
	}

	/**
	 * Rewrite rules را فقط هنگام تغییر نسخه مدل داده یک‌بار flush می‌کند.
	 *
	 * @return void
	 */
	public static function maybe_flush_rewrite_rules() {
		if ( self::REWRITE_VERSION === get_option( self::REWRITE_OPTION ) ) {
			return;
		}

		flush_rewrite_rules( false );
		update_option( self::REWRITE_OPTION, self::REWRITE_VERSION, false );
	}
}

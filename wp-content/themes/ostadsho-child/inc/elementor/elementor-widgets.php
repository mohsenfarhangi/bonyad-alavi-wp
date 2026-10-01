<?php
/**
 * Bootstrap شی‌گرا برای ویجت‌ها و Assetهای اختصاصی المنتور در قالب فرزند بنیاد علوی.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once get_stylesheet_directory() . '/inc/helpers/class-ba-media-helper.php';
require_once get_stylesheet_directory() . '/inc/services/class-ba-product-media-service.php';
require_once get_stylesheet_directory() . '/inc/services/class-ba-content-query-service.php';

/**
 * مسئول ثبت فایل‌های CSS و JavaScript ویجت‌های اختصاصی المنتور.
 *
 * این کلاس فقط Assetها را مدیریت می‌کند تا مسئولیت ثبت ویجت‌ها از آن جدا باقی بماند.
 */
final class BA_Elementor_Assets {

	/**
	 * Hookهای لازم برای ثبت Assetها در فرانت‌اند و ادیتور المنتور را متصل می‌کند.
	 */
	public function register_hooks() {
		add_action( 'wp_enqueue_scripts', array( $this, 'register_assets' ), 5 );
		add_action( 'elementor/frontend/after_register_styles', array( $this, 'register_styles' ) );
		add_action( 'elementor/frontend/after_register_scripts', array( $this, 'register_scripts' ) );
	}

	/**
	 * همه استایل‌ها و اسکریپت‌های مورد نیاز ویجت‌ها را ثبت می‌کند.
	 */
	public function register_assets() {
		$this->register_styles();
		$this->register_scripts();
	}

	/**
	 * استایل‌های ویجت‌ها را بدون enqueue سراسری ثبت می‌کند تا Elementor فقط در محل نیاز بارگذاری کند.
	 */
	public function register_styles() {
		$this->register_style(
			'bonyad-alavi-participation-widget',
			'assets/css/bonyad-alavi-participation-widget.css'
		);

		$this->register_style(
			'bonyad-alavi-product-gallery-widget',
			'assets/css/bonyad-alavi-product-gallery-widget.css'
		);

		$this->register_style(
			'bonyad-alavi-jihadi-center-widget',
			'assets/css/bonyad-alavi-jihadi-center-widget.css'
		);

		$this->register_style(
			'bonyad-alavi-home-hero-widget',
			'assets/css/bonyad-alavi-home-hero-widget.css'
		);

		$this->register_style(
			'bonyad-alavi-home-quick-links-widget',
			'assets/css/bonyad-alavi-home-quick-links-widget.css'
		);

		$this->register_style(
			'bonyad-alavi-home-live-stats-widget',
			'assets/css/bonyad-alavi-home-live-stats-widget.css'
		);

		$this->register_style(
			'bonyad-alavi-home-about-widget',
			'assets/css/bonyad-alavi-home-about-widget.css'
		);
	}

	/**
	 * اسکریپت هسته کاروسل و Handlerهای اختصاصی ویجت‌ها را با وابستگی‌های صحیح ثبت می‌کند.
	 */
	public function register_scripts() {
		$this->register_script(
			'ba-product-carousel-core',
			'assets/js/ba-product-carousel-core.js',
			array()
		);

		$this->register_script(
			'bonyad-alavi-participation-widget',
			'assets/js/bonyad-alavi-participation-widget.js',
			array( 'elementor-frontend', 'ba-product-carousel-core', 'wc-country-select', 'wc-address-i18n' )
		);

		$this->register_script(
			'bonyad-alavi-product-gallery-widget',
			'assets/js/bonyad-alavi-product-gallery-widget.js',
			array( 'elementor-frontend', 'ba-product-carousel-core' )
		);

		$this->register_script(
			'bonyad-alavi-jihadi-center-widget',
			'assets/js/bonyad-alavi-jihadi-center-widget.js',
			array( 'elementor-frontend' )
		);

		$this->register_script(
			'bonyad-alavi-home-hero-widget',
			'assets/js/bonyad-alavi-home-hero-widget.js',
			array( 'elementor-frontend' )
		);

		$this->register_script(
			'bonyad-alavi-home-quick-links-widget',
			'assets/js/bonyad-alavi-home-quick-links-widget.js',
			array( 'elementor-frontend' )
		);

		$this->register_script(
			'bonyad-alavi-home-about-widget',
			'assets/js/bonyad-alavi-home-about-widget.js',
			array( 'elementor-frontend' )
		);
	}

	/**
	 * یک فایل CSS را با نسخه مبتنی بر filemtime ثبت می‌کند.
	 *
	 * @param string $handle        نام Handle وردپرس.
	 * @param string $relative_path مسیر نسبی فایل در قالب فرزند.
	 */
	private function register_style( $handle, $relative_path ) {
		if ( wp_style_is( $handle, 'registered' ) ) {
			return;
		}

		wp_register_style(
			$handle,
			trailingslashit( get_stylesheet_directory_uri() ) . ltrim( $relative_path, '/' ),
			array(),
			$this->get_asset_version( $relative_path )
		);
	}

	/**
	 * یک فایل JavaScript را با وابستگی‌ها و نسخه مبتنی بر filemtime ثبت می‌کند.
	 *
	 * @param string $handle        نام Handle وردپرس.
	 * @param string $relative_path مسیر نسبی فایل در قالب فرزند.
	 * @param array  $dependencies  وابستگی‌های اسکریپت.
	 */
	private function register_script( $handle, $relative_path, array $dependencies ) {
		if ( wp_script_is( $handle, 'registered' ) ) {
			return;
		}

		wp_register_script(
			$handle,
			trailingslashit( get_stylesheet_directory_uri() ) . ltrim( $relative_path, '/' ),
			$dependencies,
			$this->get_asset_version( $relative_path ),
			true
		);
	}

	/**
	 * نسخه Asset را از زمان آخرین تغییر فایل می‌سازد تا Cache پس از Deploy خودکار باطل شود.
	 *
	 * @param string $relative_path مسیر نسبی فایل در قالب فرزند.
	 *
	 * @return string
	 */
	private function get_asset_version( $relative_path ) {
		$file = trailingslashit( get_stylesheet_directory() ) . ltrim( $relative_path, '/' );

		return file_exists( $file ) ? (string) filemtime( $file ) : '1.0.0';
	}
}

/**
 * مسئول ثبت دسته و کلاس‌های ویجت اختصاصی در Elementor Widgets Manager.
 */
final class BA_Elementor_Widgets_Registrar {

	/**
	 * Hookهای لازم برای ساخت دسته و ثبت ویجت‌های اختصاصی را متصل می‌کند.
	 */
	public function register_hooks() {
		add_action( 'elementor/elements/categories_registered', array( $this, 'register_category' ) );
		add_action( 'elementor/widgets/register', array( $this, 'register_widgets' ) );
	}

	/**
	 * دسته «بنیاد علوی» را در پنل ویجت‌های Elementor ایجاد می‌کند.
	 *
	 * @param Elementor\Elements_Manager $elements_manager مدیر عناصر المنتور.
	 */
	public function register_category( $elements_manager ) {
		$elements_manager->add_category(
			'bonyad-alavi',
			array(
				'title' => esc_html__( 'بنیاد علوی', 'bonyad-alavi-child' ),
				'icon'  => 'fa fa-plug',
			)
		);
	}

	/**
	 * همه ویجت‌های اختصاصی قالب را پس از آماده‌شدن Widgets Manager ثبت می‌کند.
	 *
	 * @param Elementor\Widgets_Manager $widgets_manager مدیر ویجت‌های المنتور.
	 */
	public function register_widgets( $widgets_manager ) {
		require_once get_stylesheet_directory() . '/inc/elementor/widgets/class-bonyad-alavi-participation-widget.php';
		require_once get_stylesheet_directory() . '/inc/elementor/widgets/class-bonyad-alavi-product-gallery-widget.php';
		require_once get_stylesheet_directory() . '/inc/elementor/widgets/class-bonyad-alavi-jihadi-center-widget.php';
		require_once get_stylesheet_directory() . '/inc/elementor/widgets/class-bonyad-alavi-home-hero-widget.php';
		require_once get_stylesheet_directory() . '/inc/elementor/widgets/class-bonyad-alavi-home-quick-links-widget.php';
		require_once get_stylesheet_directory() . '/inc/elementor/widgets/class-bonyad-alavi-home-live-stats-widget.php';
		require_once get_stylesheet_directory() . '/inc/elementor/widgets/class-bonyad-alavi-home-about-widget.php';

		$widgets_manager->register( new \Bonyad_Alavi_Participation_Widget() );
		$widgets_manager->register( new \Bonyad_Alavi_Product_Gallery_Widget() );
		$widgets_manager->register( new \Bonyad_Alavi_Jihadi_Center_Widget() );
		$widgets_manager->register( new \Bonyad_Alavi_Home_Hero_Widget() );
		$widgets_manager->register( new \Bonyad_Alavi_Home_Quick_Links_Widget() );
		$widgets_manager->register( new \Bonyad_Alavi_Home_Live_Stats_Widget() );
		$widgets_manager->register( new \Bonyad_Alavi_Home_About_Widget() );
	}
}

$ba_elementor_assets = new BA_Elementor_Assets();
$ba_elementor_assets->register_hooks();

$ba_elementor_widgets_registrar = new BA_Elementor_Widgets_Registrar();
$ba_elementor_widgets_registrar->register_hooks();

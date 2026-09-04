<?php
/**
 * تب تنظیمات مرکز هماهنگی حرکت‌های مردمی و جهادی.
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * مسئول ثبت تب، فرم مدیریتی و Assetهای تنظیمات مرکز است.
 */
final class BA_Center_Settings_Tab {

	const TAB_ID     = 'jihadi-center';
	const CAPABILITY = 'ba_manage_jihadi_center_settings';

	/**
	 * هوک‌های لازم برای تب تنظیمات را ثبت می‌کند.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_filter( 'ba_settings_tabs', array( __CLASS__, 'register_tab' ) );
	}

	/**
	 * تب مرکز را به رجیستری تنظیمات بنیاد علوی اضافه می‌کند.
	 *
	 * @param array $tabs تب‌های موجود.
	 * @return array
	 */
	public static function register_tab( $tabs ) {
		$tabs[ self::TAB_ID ] = array(
			'title'             => 'مرکز حرکت‌های مردمی و جهادی',
			'capability'        => self::CAPABILITY,
			'option_group'      => 'ba_jihadi_center_settings_group',
			'option_name'       => BA_Center_Settings_Service::OPTION_NAME,
			'page_slug'         => 'ba-settings-jihadi-center',
			'register_callback' => array( __CLASS__, 'register_setting' ),
			'render_callback'   => array( __CLASS__, 'render' ),
			'assets_callback'   => array( __CLASS__, 'enqueue_assets' ),
			'save_label'        => 'ذخیره تنظیمات مرکز',
		);

		return $tabs;
	}

	/**
	 * گزینه مرکز را در Settings API ثبت می‌کند.
	 *
	 * @param array $tab پیکربندی تب.
	 * @return void
	 */
	public static function register_setting( $tab ) {
		register_setting(
			$tab['option_group'],
			$tab['option_name'],
			array(
				'type'              => 'array',
				'sanitize_callback' => array( 'BA_Center_Settings_Service', 'sanitize' ),
				'default'           => BA_Center_Settings_Service::get_defaults(),
			)
		);
	}

	/**
	 * Assetهای اختصاصی تب مرکز و Repeater عمومی را برای تب فعال بارگذاری می‌کند.
	 *
	 * این متد از طریق assets_callback رجیستری تنظیمات فراخوانی می‌شود و نباید
	 * وضعیت تب را مستقیماً از پارامترهای URL تشخیص دهد.
	 *
	 * @param array  $tab         پیکربندی تب فعال از Registry تنظیمات.
	 * @param string $hook_suffix شناسه صفحه جاری مدیریت وردپرس.
	 * @return void
	 */
	public static function enqueue_assets( $tab = array(), $hook_suffix = '' ) {
		BA_Admin_Repeater_Component::enqueue_assets();
		wp_enqueue_media();

		$style_relative = 'assets/css/ba-center-settings.css';
		$script_relative = 'assets/js/ba-center-settings.js';
		$style_path = trailingslashit( get_stylesheet_directory() ) . $style_relative;
		$script_path = trailingslashit( get_stylesheet_directory() ) . $script_relative;
		$style_version = file_exists( $style_path ) ? (string) filemtime( $style_path ) : wp_get_theme()->get( 'Version' );
		$script_version = file_exists( $script_path ) ? (string) filemtime( $script_path ) : wp_get_theme()->get( 'Version' );

		wp_enqueue_style(
			'ba-center-settings',
			trailingslashit( get_stylesheet_directory_uri() ) . $style_relative,
			array( 'ba-admin-settings', BA_Admin_Repeater_Component::STYLE_HANDLE ),
			$style_version
		);
		wp_enqueue_script(
			'ba-center-settings',
			trailingslashit( get_stylesheet_directory_uri() ) . $script_relative,
			array( 'jquery', BA_Admin_Repeater_Component::SCRIPT_HANDLE ),
			$script_version,
			true
		);
		wp_localize_script(
			'ba-center-settings',
			'BACenterSettings',
			array(
				'mediaTitle'  => 'انتخاب فایل رسانه‌ای',
				'mediaButton' => 'استفاده از این فایل',
				'remove'      => 'حذف',
			)
		);
	}

	/**
	 * فرم کامل تنظیمات مرکز را رندر می‌کند.
	 *
	 * @param array $tab پیکربندی تب.
	 * @return void
	 */
	public static function render( $tab ) {
		$settings = BA_Center_Settings_Service::get_settings();
		$name     = $tab['option_name'];
		?>
		<form id="<?php echo esc_attr( $tab['form_id'] ); ?>" method="post" action="options.php" class="ba-center-settings ba-settings-form" data-ba-settings-form>
			<input type="hidden" name="ba_settings_tab" value="<?php echo esc_attr( $tab['id'] ); ?>" />
			<?php settings_fields( $tab['option_group'] ); ?>
			<p class="ba-center-settings__lead">محتوای ثابت صفحه مرکز از این تب مدیریت می‌شود. در بخش‌هایی که کنترل متناظر Elementor وجود دارد، مقدار معتبر این صفحه اولویت دارد.</p>

			<?php self::render_hero_section( $name, $settings ); ?>
			<?php self::render_stats_section( $name, $settings ); ?>
			<?php self::render_intro_section( $name, $settings ); ?>
			<?php self::render_system_cards_section( $name, $settings ); ?>
			<?php self::render_news_section( $name, $settings ); ?>
			<?php self::render_media_section( $name, $settings ); ?>
			<?php self::render_partners_section( $name, $settings ); ?>
			<?php self::render_faq_section( $name, $settings ); ?>

		</form>
		<?php
	}

	/**
	 * فیلدهای یک ردیف آمار را برای Repeater عمومی رندر می‌کند.
	 *
	 * @param string|int $index   اندیس ردیف.
	 * @param array      $item    داده ردیف.
	 * @param mixed      $context نام option والد.
	 * @return void
	 */
	public static function render_stat_repeater_fields( $index, array $item, $context ) {
		$item = wp_parse_args( $item, array( 'number' => '', 'title' => '', 'subtitle' => '' ) );
		$name = (string) $context;
		?>
		<div class="ba-center-settings__grid">
			<?php self::render_repeater_text_field( $name, 'stats', $index, 'number', 'عدد', $item['number'] ); ?>
			<?php self::render_repeater_text_field( $name, 'stats', $index, 'title', 'عنوان', $item['title'] ); ?>
			<?php self::render_repeater_text_field( $name, 'stats', $index, 'subtitle', 'زیرعنوان', $item['subtitle'] ); ?>
		</div>
		<?php
	}

	/**
	 * فیلدهای یک کارت سامانه را برای Repeater عمومی رندر می‌کند.
	 *
	 * @param string|int $index   اندیس ردیف.
	 * @param array      $item    داده ردیف.
	 * @param mixed      $context نام option والد.
	 * @return void
	 */
	public static function render_system_card_repeater_fields( $index, array $item, $context ) {
		$item  = wp_parse_args( $item, array( 'media_type' => 'image', 'image_id' => 0, 'svg_id' => 0, 'icon_id' => 0, 'title' => '', 'url' => '' ) );
		$name  = (string) $context;
		$media = BA_Center_Settings_Service::normalize_system_card_media( $item );

		self::render_repeater_media_switcher(
			$name,
			'system_cards',
			$index,
			$media,
			array(
				'image_label'  => 'تصویر',
				'image_button' => 'انتخاب تصویر',
				'svg_label'    => 'آیکون / SVG',
				'svg_button'   => 'انتخاب SVG',
			)
		);
		?>
		<div class="ba-center-settings__grid">
			<?php self::render_repeater_text_field( $name, 'system_cards', $index, 'title', 'عنوان', $item['title'] ); ?>
			<?php self::render_repeater_url_field( $name, 'system_cards', $index, 'url', 'لینک', $item['url'] ); ?>
		</div>
		<?php
	}

	/**
	 * فیلدهای یک همراه مرکز را برای Repeater عمومی رندر می‌کند.
	 *
	 * @param string|int $index   اندیس ردیف.
	 * @param array      $item    داده ردیف.
	 * @param mixed      $context نام option والد.
	 * @return void
	 */
	public static function render_partner_repeater_fields( $index, array $item, $context ) {
		$item = wp_parse_args( $item, array( 'media_type' => 'image', 'image_id' => 0, 'svg_id' => 0, 'title' => '', 'url' => '' ) );
		$name = (string) $context;

		self::render_repeater_media_switcher(
			$name,
			'partners',
			$index,
			$item,
			array(
				'image_label'  => 'تصویر لوگو',
				'image_button' => 'انتخاب تصویر',
				'svg_label'    => 'فایل SVG / آیکون',
				'svg_button'   => 'انتخاب SVG',
			)
		);
		?>
		<div class="ba-center-settings__grid">
			<?php self::render_repeater_text_field( $name, 'partners', $index, 'title', 'عنوان (اختیاری)', $item['title'] ); ?>
			<?php self::render_repeater_url_field( $name, 'partners', $index, 'url', 'لینک (اختیاری)', $item['url'] ); ?>
		</div>
		<?php
	}

	/**
	 * انتخاب انحصاری تصویر یا SVG را برای Repeaterهای مدیریت رندر می‌کند.
	 * این Helper بین کارت‌های سامانه و همراهان مشترک است تا رفتار Switcher تکرار نشود.
	 *
	 * @param string     $name       نام option والد.
	 * @param string     $collection کلید مجموعه Repeater.
	 * @param string|int $index      اندیس ردیف.
	 * @param array      $item       داده رسانه ردیف.
	 * @param array      $labels     برچسب‌ها و متن دکمه‌ها.
	 * @return void
	 */
	private static function render_repeater_media_switcher( $name, $collection, $index, array $item, array $labels = array() ) {
		$labels = wp_parse_args( $labels, array(
			'image_label'  => 'تصویر',
			'image_button' => 'انتخاب تصویر',
			'svg_label'    => 'آیکون / SVG',
			'svg_button'   => 'انتخاب SVG',
		) );
		$type = 'svg' === ( $item['media_type'] ?? 'image' ) ? 'svg' : 'image';
		$media_type_name = BA_Admin_Repeater_Component::field_name( $name, $collection, $index, 'media_type' );
		?>
		<div class="ba-center-settings__media-switch-row" data-ba-media-switch-row>
			<div class="ba-center-settings__switcher">
				<span>تصویر</span>
				<label><input type="checkbox" data-ba-media-type-switch <?php checked( 'svg', $type ); ?>><span></span></label>
				<span>آیکون / SVG</span>
				<input type="hidden" data-ba-media-type-value name="<?php echo esc_attr( $media_type_name ); ?>" value="<?php echo esc_attr( $type ); ?>">
			</div>
			<div data-ba-media-mode="image" <?php echo 'image' === $type ? '' : 'hidden'; ?>>
				<?php self::render_media_control( BA_Admin_Repeater_Component::field_name( $name, $collection, $index, 'image_id' ), $labels['image_label'], absint( $item['image_id'] ?? 0 ), 'image', $labels['image_button'] ); ?>
			</div>
			<div data-ba-media-mode="svg" <?php echo 'svg' === $type ? '' : 'hidden'; ?>>
				<?php self::render_media_control( BA_Admin_Repeater_Component::field_name( $name, $collection, $index, 'svg_id' ), $labels['svg_label'], absint( $item['svg_id'] ?? 0 ), 'image/svg+xml', $labels['svg_button'] ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * فیلدهای یک پرسش متداول را برای Repeater عمومی رندر می‌کند.
	 *
	 * @param string|int $index   اندیس ردیف.
	 * @param array      $item    داده ردیف.
	 * @param mixed      $context نام option والد.
	 * @return void
	 */
	public static function render_faq_repeater_fields( $index, array $item, $context ) {
		$item = wp_parse_args( $item, array( 'question' => '', 'answer' => '' ) );
		$name = (string) $context;
		?>
		<?php self::render_repeater_text_field( $name, 'faqs', $index, 'question', 'سؤال', $item['question'], true ); ?>
		<label class="ba-center-settings__field ba-center-settings__field--wide">
			<span class="ba-center-settings__label">پاسخ</span>
			<textarea rows="5" name="<?php echo esc_attr( BA_Admin_Repeater_Component::field_name( $name, 'faqs', $index, 'answer' ) ); ?>"><?php echo esc_textarea( $item['answer'] ); ?></textarea>
		</label>
		<?php
	}

	/**
	 * بخش تنظیمات Hero را رندر می‌کند.
	 *
	 * @param string $name     نام option.
	 * @param array  $settings تنظیمات.
	 * @return void
	 */
	private static function render_hero_section( $name, array $settings ) {
		?>
		<section class="ba-center-settings__section">
			<h2 class="ba-center-settings__section-title">Hero</h2>
			<?php self::render_toggle( $name, 'hero_enabled', 'نمایش سکشن Hero', $settings['hero_enabled'] ); ?>
			<div class="ba-center-settings__grid">
				<?php self::render_text( $name, 'hero_eyebrow', 'متن بالای عنوان', $settings['hero_eyebrow'] ); ?>
				<?php self::render_text( $name, 'hero_title', 'عنوان هیرو', $settings['hero_title'] ); ?>
				<?php self::render_text( $name, 'hero_button_text', 'متن دکمه ثبت‌نام', $settings['hero_button_text'] ); ?>
				<?php self::render_url( $name, 'hero_button_url', 'لینک دکمه ثبت‌نام', $settings['hero_button_url'] ); ?>
			</div>
			<?php self::render_media_field( $name, 'hero_background_id', 'تصویر پس‌زمینه Hero', absint( $settings['hero_background_id'] ), 'image' ); ?>
		</section>
		<?php
	}

	/**
	 * بخش آمار و Repeater آن را رندر می‌کند.
	 *
	 * @param string $name     نام option.
	 * @param array  $settings تنظیمات.
	 * @return void
	 */
	private static function render_stats_section( $name, array $settings ) {
		?>
		<section class="ba-center-settings__section">
			<h2 class="ba-center-settings__section-title">آمار مرکز</h2>
			<?php self::render_toggle( $name, 'stats_enabled', 'نمایش سکشن آمار', $settings['stats_enabled'] ); ?>
			<div class="ba-center-settings__grid">
				<?php self::render_text( $name, 'stats_title', 'عنوان پنل آمار', $settings['stats_title'] ); ?>
				<?php self::render_text( $name, 'stats_subtitle', 'زیرعنوان پنل آمار', $settings['stats_subtitle'] ); ?>
			</div>
			<?php
			BA_Admin_Repeater_Component::render(
				array(
					'id'           => 'ba-center-stats-repeater',
					'items'        => (array) $settings['stats'],
					'item_label'   => 'آیتم آمار',
					'add_label'    => 'افزودن آمار',
					'empty_label'  => 'هنوز آماری اضافه نشده است.',
					'row_renderer' => array( __CLASS__, 'render_stat_repeater_fields' ),
					'context'      => $name,
				)
			);
			?>
		</section>
		<?php
	}

	/**
	 * بخش معرفی مرکز و ویرایشگر توضیحات را رندر می‌کند.
	 *
	 * @param string $name     نام option.
	 * @param array  $settings تنظیمات.
	 * @return void
	 */
	private static function render_intro_section( $name, array $settings ) {
		?>
		<section class="ba-center-settings__section">
			<h2 class="ba-center-settings__section-title">معرفی مرکز</h2>
			<?php self::render_toggle( $name, 'intro_enabled', 'نمایش سکشن معرفی مرکز', $settings['intro_enabled'] ); ?>
			<div class="ba-center-settings__grid">
				<?php self::render_text( $name, 'intro_kicker', 'Kicker', $settings['intro_kicker'] ); ?>
				<?php self::render_text( $name, 'intro_title', 'عنوان', $settings['intro_title'] ); ?>
			</div>
			<div class="ba-center-settings__field ba-center-settings__field--wide">
				<label class="ba-center-settings__label" for="ba-center-intro-description">توضیحات</label>
				<?php
				wp_editor(
					$settings['intro_description'],
					'ba-center-intro-description',
					array(
						'textarea_name' => $name . '[intro_description]',
						'textarea_rows' => 8,
						'media_buttons' => false,
					)
				);
				?>
			</div>
		</section>
		<?php
	}

	/**
	 * Repeater کارت‌های سامانه را رندر می‌کند.
	 *
	 * @param string $name     نام option.
	 * @param array  $settings تنظیمات.
	 * @return void
	 */
	private static function render_system_cards_section( $name, array $settings ) {
		?>
		<section class="ba-center-settings__section">
			<h2 class="ba-center-settings__section-title">کارت‌های بخش‌های مرکز</h2>
			<?php
			BA_Admin_Repeater_Component::render(
				array(
					'id'           => 'ba-center-system-cards-repeater',
					'items'        => (array) $settings['system_cards'],
					'item_label'   => 'کارت مرکز',
					'add_label'    => 'افزودن کارت',
					'empty_label'  => 'هنوز کارتی برای این بخش اضافه نشده است.',
					'row_renderer' => array( __CLASS__, 'render_system_card_repeater_fields' ),
					'context'      => $name,
				)
			);
			?>
		</section>
		<?php
	}


	/**
	 * تنظیمات کامل سکشن اخبار و Query آن را در داشبورد رندر می‌کند.
	 *
	 * @param string $name     نام option.
	 * @param array  $settings تنظیمات.
	 * @return void
	 */
	private static function render_news_section( $name, array $settings ) {
		?>
		<section class="ba-center-settings__section">
			<h2 class="ba-center-settings__section-title">اخبار مرکز</h2>
			<?php self::render_toggle( $name, 'news_enabled', 'نمایش سکشن اخبار', $settings['news_enabled'] ); ?>
			<div class="ba-center-settings__grid">
				<?php self::render_text( $name, 'news_kicker', 'Kicker', $settings['news_kicker'] ); ?>
				<?php self::render_text( $name, 'news_title', 'عنوان', $settings['news_title'] ); ?>
				<?php self::render_text( $name, 'news_subtitle', 'زیرعنوان', $settings['news_subtitle'] ); ?>
				<?php self::render_text( $name, 'news_all_text', 'متن مشاهده همه', $settings['news_all_text'] ); ?>
				<?php self::render_url( $name, 'news_all_url', 'لینک مشاهده همه', $settings['news_all_url'] ); ?>
			</div>
			<?php self::render_query_fields( $name, $settings, 'news', 'Query اخبار' ); ?>
		</section>
		<?php
	}

	/**
	 * تنظیمات کامل سکشن چندرسانه‌ای و Query آن را در داشبورد رندر می‌کند.
	 *
	 * @param string $name     نام option.
	 * @param array  $settings تنظیمات.
	 * @return void
	 */
	private static function render_media_section( $name, array $settings ) {
		?>
		<section class="ba-center-settings__section">
			<h2 class="ba-center-settings__section-title">چندرسانه‌ای</h2>
			<?php self::render_toggle( $name, 'media_enabled', 'نمایش سکشن چندرسانه‌ای', $settings['media_enabled'] ); ?>
			<div class="ba-center-settings__grid">
				<?php self::render_text( $name, 'media_kicker', 'Kicker', $settings['media_kicker'] ); ?>
				<?php self::render_text( $name, 'media_title', 'عنوان', $settings['media_title'] ); ?>
				<?php self::render_text( $name, 'media_subtitle', 'زیرعنوان', $settings['media_subtitle'] ); ?>
				<?php self::render_text( $name, 'media_all_text', 'متن مشاهده همه', $settings['media_all_text'] ); ?>
				<?php self::render_url( $name, 'media_all_url', 'لینک مشاهده همه', $settings['media_all_url'] ); ?>
				<?php self::render_text( $name, 'media_duration_meta_key', 'کلید متای مدت ویدیو', $settings['media_duration_meta_key'] ); ?>
			</div>
			<?php self::render_query_fields( $name, $settings, 'media', 'Query چندرسانه‌ای' ); ?>
		</section>
		<?php
	}

	/**
	 * فیلدهای Query مشترک اخبار و چندرسانه‌ای را رندر می‌کند.
	 * نام فیلدها عمداً با کنترل‌های Elementor یکسان است تا Resolver مشترک قابل استفاده باشد.
	 *
	 * @param string $name     نام option.
	 * @param array  $settings تنظیمات.
	 * @param string $prefix   پیشوند Query.
	 * @param string $title    عنوان مجموعه.
	 * @return void
	 */
	private static function render_query_fields( $name, array $settings, $prefix, $title ) {
		$post_types = get_post_types( array( 'public' => true ), 'objects' );
		$post_type_options = array();
		foreach ( $post_types as $post_type ) {
			$post_type_options[ $post_type->name ] = $post_type->labels->singular_name ?: $post_type->label;
		}
		$category_options = self::get_term_options( 'category' );
		$tag_options      = self::get_term_options( 'post_tag' );
		$author_options   = self::get_author_options();
		?>
		<div class="ba-center-settings__query">
			<h3 class="ba-center-settings__subsection-title"><?php echo esc_html( $title ); ?></h3>
			<div class="ba-center-settings__grid">
				<?php self::render_multiselect( $name, $prefix . '_post_types', 'Post Type', $post_type_options, (array) $settings[ $prefix . '_post_types' ] ); ?>
				<?php self::render_number( $name, $prefix . '_posts_per_page', 'تعداد نمایش', $settings[ $prefix . '_posts_per_page' ], 1, 30 ); ?>
				<?php self::render_multiselect( $name, $prefix . '_categories', 'دسته‌بندی‌ها', $category_options, (array) $settings[ $prefix . '_categories' ] ); ?>
				<?php self::render_multiselect( $name, $prefix . '_tags', 'برچسب‌ها', $tag_options, (array) $settings[ $prefix . '_tags' ] ); ?>
				<?php self::render_multiselect( $name, $prefix . '_authors', 'نویسندگان', $author_options, (array) $settings[ $prefix . '_authors' ] ); ?>
				<?php self::render_text( $name, $prefix . '_include_ids', 'فقط شناسه نوشته‌ها', $settings[ $prefix . '_include_ids' ] ); ?>
				<?php self::render_text( $name, $prefix . '_exclude_ids', 'حذف شناسه نوشته‌ها', $settings[ $prefix . '_exclude_ids' ] ); ?>
				<?php self::render_select( $name, $prefix . '_orderby', 'مرتب‌سازی بر اساس', array( 'date' => 'تاریخ انتشار', 'modified' => 'آخرین ویرایش', 'title' => 'عنوان', 'menu_order' => 'ترتیب منو', 'rand' => 'تصادفی', 'ID' => 'شناسه' ), $settings[ $prefix . '_orderby' ] ); ?>
				<?php self::render_select( $name, $prefix . '_order', 'ترتیب', array( 'DESC' => 'نزولی', 'ASC' => 'صعودی' ), $settings[ $prefix . '_order' ] ); ?>
				<?php self::render_number( $name, $prefix . '_offset', 'Offset', $settings[ $prefix . '_offset' ], 0 ); ?>
				<?php self::render_text( $name, $prefix . '_date_after', 'تاریخ از', $settings[ $prefix . '_date_after' ] ); ?>
				<?php self::render_text( $name, $prefix . '_date_before', 'تاریخ تا', $settings[ $prefix . '_date_before' ] ); ?>
			</div>
			<?php self::render_elementor_toggle( $name, $prefix . '_ignore_sticky', 'نادیده گرفتن Sticky Posts', $settings[ $prefix . '_ignore_sticky' ] ); ?>
		</div>
		<?php
	}

	/**
	 * گزینه‌های یک taxonomy را برای select داشبورد می‌سازد.
	 *
	 * @param string $taxonomy نام taxonomy.
	 * @return array
	 */
	private static function get_term_options( $taxonomy ) {
		$options = array();
		$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
		if ( is_wp_error( $terms ) ) {
			return $options;
		}
		foreach ( $terms as $term ) {
			$options[ (string) $term->term_id ] = $term->name;
		}
		return $options;
	}

	/**
	 * گزینه‌های نویسندگان را برای select داشبورد می‌سازد.
	 *
	 * @return array
	 */
	private static function get_author_options() {
		$options = array();
		$users = get_users( array( 'who' => 'authors', 'fields' => array( 'ID', 'display_name' ) ) );
		foreach ( $users as $user ) {
			$options[ (string) $user->ID ] = $user->display_name;
		}
		return $options;
	}

	/**
	 * تنظیمات سرصفحه و Repeater همراهان را رندر می‌کند.
	 *
	 * @param string $name     نام option.
	 * @param array  $settings تنظیمات.
	 * @return void
	 */
	private static function render_partners_section( $name, array $settings ) {
		?>
		<section class="ba-center-settings__section">
			<h2 class="ba-center-settings__section-title">همراهان مرکز</h2>
			<?php self::render_toggle( $name, 'partners_enabled', 'نمایش سکشن همراهان', $settings['partners_enabled'] ); ?>
			<p class="description">مقادیر ذخیره‌شده این بخش بر کنترل‌های متناظر Elementor اولویت دارند.</p>
			<div class="ba-center-settings__grid">
				<?php self::render_text( $name, 'partners_kicker', 'Kicker', $settings['partners_kicker'] ); ?>
				<?php self::render_text( $name, 'partners_title', 'عنوان', $settings['partners_title'] ); ?>
				<?php self::render_text( $name, 'partners_subtitle', 'زیرعنوان', $settings['partners_subtitle'] ); ?>
			</div>
			<?php
			BA_Admin_Repeater_Component::render(
				array(
					'id'           => 'ba-center-partners-repeater',
					'items'        => (array) $settings['partners'],
					'item_label'   => 'همراه مرکز',
					'add_label'    => 'افزودن همراه',
					'empty_label'  => 'هنوز همراهی اضافه نشده است.',
					'row_renderer' => array( __CLASS__, 'render_partner_repeater_fields' ),
					'context'      => $name,
				)
			);
			?>
		</section>
		<?php
	}

	/**
	 * تنظیمات سرصفحه و Repeater پرسش‌های متداول را رندر می‌کند.
	 *
	 * @param string $name     نام option.
	 * @param array  $settings تنظیمات.
	 * @return void
	 */
	private static function render_faq_section( $name, array $settings ) {
		?>
		<section class="ba-center-settings__section">
			<h2 class="ba-center-settings__section-title">پرسش‌های متداول</h2>
			<?php self::render_toggle( $name, 'faq_enabled', 'نمایش سکشن پرسش‌های متداول', $settings['faq_enabled'] ); ?>
			<p class="description">مقادیر ذخیره‌شده این بخش بر کنترل‌های متناظر Elementor اولویت دارند.</p>
			<div class="ba-center-settings__grid">
				<?php self::render_text( $name, 'faq_kicker', 'Kicker', $settings['faq_kicker'] ); ?>
				<?php self::render_text( $name, 'faq_title', 'عنوان', $settings['faq_title'] ); ?>
				<?php self::render_text( $name, 'faq_subtitle', 'زیرعنوان', $settings['faq_subtitle'] ); ?>
			</div>
			<?php
			BA_Admin_Repeater_Component::render(
				array(
					'id'           => 'ba-center-faq-repeater',
					'items'        => (array) $settings['faqs'],
					'item_label'   => 'پرسش',
					'add_label'    => 'افزودن پرسش',
					'empty_label'  => 'هنوز پرسشی اضافه نشده است.',
					'row_renderer' => array( __CLASS__, 'render_faq_repeater_fields' ),
					'context'      => $name,
				)
			);
			?>
		</section>
		<?php
	}


	/**
	 * Switcher استاندارد فعال/غیرفعال سکشن را با مقدار 1/0 رندر می‌کند.
	 *
	 * @param string $name  نام option.
	 * @param string $key   کلید.
	 * @param string $label برچسب.
	 * @param mixed  $value مقدار فعلی.
	 * @return void
	 */
	private static function render_toggle( $name, $key, $label, $value ) {
		?>
		<label class="ba-center-settings__toggle">
			<input type="hidden" name="<?php echo esc_attr( $name . '[' . $key . ']' ); ?>" value="0">
			<input type="checkbox" name="<?php echo esc_attr( $name . '[' . $key . ']' ); ?>" value="1" <?php checked( in_array( $value, array( 1, '1', true, 'yes', 'on' ), true ) ); ?>>
			<span class="ba-center-settings__toggle-text"><?php echo esc_html( $label ); ?></span>
		</label>
		<?php
	}

	/**
	 * Switcher سازگار با مقدار yes کنترل‌های Query Elementor را رندر می‌کند.
	 *
	 * @param string $name  نام option.
	 * @param string $key   کلید.
	 * @param string $label برچسب.
	 * @param mixed  $value مقدار فعلی.
	 * @return void
	 */
	private static function render_elementor_toggle( $name, $key, $label, $value ) {
		?>
		<label class="ba-center-settings__toggle">
			<input type="hidden" name="<?php echo esc_attr( $name . '[' . $key . ']' ); ?>" value="">
			<input type="checkbox" name="<?php echo esc_attr( $name . '[' . $key . ']' ); ?>" value="yes" <?php checked( 'yes', $value ); ?>>
			<span class="ba-center-settings__toggle-text"><?php echo esc_html( $label ); ?></span>
		</label>
		<?php
	}

	/**
	 * یک ورودی عدد استاندارد را رندر می‌کند.
	 *
	 * @param string   $name  نام option.
	 * @param string   $key   کلید.
	 * @param string   $label برچسب.
	 * @param int      $value مقدار.
	 * @param int      $min   حداقل.
	 * @param int|null $max   حداکثر اختیاری.
	 * @return void
	 */
	private static function render_number( $name, $key, $label, $value, $min = 0, $max = null ) {
		?>
		<div class="ba-center-settings__field">
			<label class="ba-center-settings__label"><?php echo esc_html( $label ); ?></label>
			<input type="number" name="<?php echo esc_attr( $name . '[' . $key . ']' ); ?>" value="<?php echo esc_attr( (int) $value ); ?>" min="<?php echo esc_attr( (int) $min ); ?>" <?php echo null !== $max ? 'max="' . esc_attr( (int) $max ) . '"' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		</div>
		<?php
	}

	/**
	 * یک select استاندارد را رندر می‌کند.
	 *
	 * @param string $name    نام option.
	 * @param string $key     کلید.
	 * @param string $label   برچسب.
	 * @param array  $options گزینه‌ها.
	 * @param mixed  $value   مقدار انتخاب‌شده.
	 * @return void
	 */
	private static function render_select( $name, $key, $label, array $options, $value ) {
		?>
		<div class="ba-center-settings__field">
			<label class="ba-center-settings__label"><?php echo esc_html( $label ); ?></label>
			<select name="<?php echo esc_attr( $name . '[' . $key . ']' ); ?>">
				<?php foreach ( $options as $option_value => $option_label ) : ?>
					<option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( (string) $value, (string) $option_value ); ?>><?php echo esc_html( $option_label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
	}

	/**
	 * یک select چندانتخابی استاندارد را رندر می‌کند.
	 *
	 * @param string $name     نام option.
	 * @param string $key      کلید.
	 * @param string $label    برچسب.
	 * @param array  $options  گزینه‌ها.
	 * @param array  $selected مقادیر انتخاب‌شده.
	 * @return void
	 */
	private static function render_multiselect( $name, $key, $label, array $options, array $selected ) {
		$selected = array_map( 'strval', $selected );
		?>
		<div class="ba-center-settings__field">
			<label class="ba-center-settings__label"><?php echo esc_html( $label ); ?></label>
			<select multiple size="6" name="<?php echo esc_attr( $name . '[' . $key . '][]' ); ?>">
				<?php foreach ( $options as $option_value => $option_label ) : ?>
					<option value="<?php echo esc_attr( $option_value ); ?>" <?php selected( in_array( (string) $option_value, $selected, true ) ); ?>><?php echo esc_html( $option_label ); ?></option>
				<?php endforeach; ?>
			</select>
		</div>
		<?php
	}

	/**
	 * یک ورودی متن استاندارد را رندر می‌کند.
	 *
	 * @param string $name  نام option.
	 * @param string $key   کلید.
	 * @param string $label برچسب.
	 * @param string $value مقدار.
	 * @return void
	 */
	private static function render_text( $name, $key, $label, $value ) {
		?>
		<div class="ba-center-settings__field">
			<label class="ba-center-settings__label"><?php echo esc_html( $label ); ?></label>
			<input type="text" class="regular-text" name="<?php echo esc_attr( $name . '[' . $key . ']' ); ?>" value="<?php echo esc_attr( $value ); ?>">
		</div>
		<?php
	}

	/**
	 * یک ورودی URL استاندارد را رندر می‌کند.
	 *
	 * @param string $name  نام option.
	 * @param string $key   کلید.
	 * @param string $label برچسب.
	 * @param string $value مقدار.
	 * @return void
	 */
	private static function render_url( $name, $key, $label, $value ) {
		?>
		<div class="ba-center-settings__field">
			<label class="ba-center-settings__label"><?php echo esc_html( $label ); ?></label>
			<input type="url" class="regular-text ltr" dir="ltr" name="<?php echo esc_attr( $name . '[' . $key . ']' ); ?>" value="<?php echo esc_attr( $value ); ?>">
		</div>
		<?php
	}

	/**
	 * انتخاب یک فایل رسانه‌ای سطح اصلی را همراه با پیش‌نمایش رندر می‌کند.
	 *
	 * @param string $name       نام option.
	 * @param string $key        کلید.
	 * @param string $label      برچسب.
	 * @param int    $attachment شناسه پیوست.
	 * @param string $accept     نوع مورد انتظار.
	 * @return void
	 */
	private static function render_media_field( $name, $key, $label, $attachment, $accept = 'image' ) {
		self::render_media_control(
			$name . '[' . $key . ']',
			$label,
			$attachment,
			$accept,
			'انتخاب رسانه',
			true
		);
	}

	/**
	 * کنترل رسانه‌ای قابل استفاده مجدد برای فیلدهای ساده و تو در تو می‌سازد.
	 *
	 * @param string $input_name  نام کامل input.
	 * @param string $label       برچسب.
	 * @param int    $attachment  شناسه پیوست.
	 * @param string $accept      نوع رسانه مورد انتظار.
	 * @param string $button_text متن دکمه انتخاب.
	 * @param bool   $wide        آیا فیلد تمام عرض باشد.
	 * @return void
	 */
	private static function render_media_control( $input_name, $label, $attachment, $accept = 'image', $button_text = 'انتخاب رسانه', $wide = false ) {
		$classes = 'ba-center-settings__field';
		if ( $wide ) {
			$classes .= ' ba-center-settings__field--wide';
		}
		?>
		<div class="<?php echo esc_attr( $classes ); ?>" data-ba-media-field data-accept="<?php echo esc_attr( $accept ); ?>">
			<span class="ba-center-settings__label"><?php echo esc_html( $label ); ?></span>
			<input type="hidden" data-ba-media-input name="<?php echo esc_attr( $input_name ); ?>" value="<?php echo esc_attr( absint( $attachment ) ); ?>">
			<div class="ba-center-settings__media-preview" data-ba-media-preview><?php echo self::get_attachment_preview( absint( $attachment ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			<div class="ba-center-settings__media-actions">
				<button type="button" class="button" data-ba-media-select><?php echo esc_html( $button_text ); ?></button>
				<button type="button" class="button-link-delete" data-ba-media-remove <?php echo $attachment ? '' : 'hidden'; ?>>حذف</button>
			</div>
		</div>
		<?php
	}

	/**
	 * یک فیلد متن داخل Repeater را با نام استاندارد Component رندر می‌کند.
	 *
	 * @param string     $name       نام option.
	 * @param string     $collection کلید مجموعه.
	 * @param string|int $index      اندیس ردیف.
	 * @param string     $field      کلید فیلد.
	 * @param string     $label      برچسب.
	 * @param string     $value      مقدار.
	 * @param bool       $wide       آیا فیلد تمام عرض باشد.
	 * @return void
	 */
	private static function render_repeater_text_field( $name, $collection, $index, $field, $label, $value, $wide = false ) {
		$classes = 'ba-center-settings__field';
		if ( $wide ) {
			$classes .= ' ba-center-settings__field--wide';
		}
		?>
		<label class="<?php echo esc_attr( $classes ); ?>">
			<span class="ba-center-settings__label"><?php echo esc_html( $label ); ?></span>
			<input type="text" name="<?php echo esc_attr( BA_Admin_Repeater_Component::field_name( $name, $collection, $index, $field ) ); ?>" value="<?php echo esc_attr( $value ); ?>">
		</label>
		<?php
	}

	/**
	 * یک فیلد URL داخل Repeater را با نام استاندارد Component رندر می‌کند.
	 *
	 * @param string     $name       نام option.
	 * @param string     $collection کلید مجموعه.
	 * @param string|int $index      اندیس ردیف.
	 * @param string     $field      کلید فیلد.
	 * @param string     $label      برچسب.
	 * @param string     $value      مقدار.
	 * @return void
	 */
	private static function render_repeater_url_field( $name, $collection, $index, $field, $label, $value ) {
		?>
		<label class="ba-center-settings__field">
			<span class="ba-center-settings__label"><?php echo esc_html( $label ); ?></span>
			<input type="url" dir="ltr" name="<?php echo esc_attr( BA_Admin_Repeater_Component::field_name( $name, $collection, $index, $field ) ); ?>" value="<?php echo esc_attr( $value ); ?>">
		</label>
		<?php
	}

	/**
	 * پیش‌نمایش پیوست رسانه‌ای را برای داشبورد می‌سازد.
	 *
	 * @param int $attachment_id شناسه پیوست.
	 * @return string
	 */
	private static function get_attachment_preview( $attachment_id ) {
		if ( ! $attachment_id ) {
			return '';
		}

		$url = wp_get_attachment_url( $attachment_id );
		if ( ! $url ) {
			return '';
		}

		return sprintf( '<img src="%s" alt="">', esc_url( $url ) );
	}
}

BA_Center_Settings_Tab::register_hooks();

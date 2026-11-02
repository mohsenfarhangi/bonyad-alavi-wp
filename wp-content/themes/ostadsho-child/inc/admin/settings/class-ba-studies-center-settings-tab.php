<?php
/**
 * تب تنظیمات مرکز مطالعات.
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ثبت تب «مرکز مطالعات» در رجیستری تنظیمات بنیاد علوی (ADR-003).
 *
 * Capability این تب در «مدیریت دسترسی‌ها» خودکار ظاهر می‌شود و همان Capability
 * مدیریت کامل نشست‌ها را هم می‌دهد (BA_Studies_Content_Types::get_session_capabilities).
 */
final class BA_Studies_Center_Settings_Tab {

	const TAB_ID = 'studies-center';

	/**
	 * هوک ثبت تب.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_filter( 'ba_settings_tabs', array( __CLASS__, 'register_tab' ) );
	}

	/**
	 * تب را به رجیستری اضافه می‌کند.
	 *
	 * @param array $tabs تب‌ها.
	 * @return array
	 */
	public static function register_tab( $tabs ) {
		$tabs[ self::TAB_ID ] = array(
			'title'             => 'مرکز مطالعات',
			'capability'        => BA_Studies_Center_Settings_Service::CAPABILITY,
			'option_group'      => 'ba_studies_center_settings_group',
			'option_name'       => BA_Studies_Center_Settings_Service::OPTION_NAME,
			'page_slug'         => 'ba-settings-studies-center',
			'register_callback' => array( __CLASS__, 'register_setting' ),
			'render_callback'   => array( __CLASS__, 'render' ),
			'assets_callback'   => array( __CLASS__, 'enqueue_assets' ),
			'save_label'        => 'ذخیره تنظیمات مرکز مطالعات',
		);

		return $tabs;
	}

	/**
	 * ثبت option در Settings API.
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
				'sanitize_callback' => array( 'BA_Studies_Center_Settings_Service', 'sanitize' ),
				'default'           => BA_Studies_Center_Settings_Service::get_defaults(),
			)
		);
	}

	/**
	 * Assetهای انتخاب تصویر Hero و Repeater مشترک را فقط برای این تب بارگذاری می‌کند.
	 *
	 * @param array  $tab         پیکربندی تب.
	 * @param string $hook_suffix صفحه جاری.
	 * @return void
	 */
	public static function enqueue_assets( $tab = array(), $hook_suffix = '' ) {
		BA_Admin_Repeater_Component::enqueue_assets();
		wp_enqueue_media();

		$dir = trailingslashit( get_stylesheet_directory() );
		$uri = trailingslashit( get_stylesheet_directory_uri() );
		$css = 'assets/css/ba-studies-settings.css';
		$js  = 'assets/js/ba-studies-settings.js';

		wp_enqueue_style(
			'ba-studies-settings',
			$uri . $css,
			array( 'ba-admin-settings', BA_Admin_Repeater_Component::STYLE_HANDLE ),
			file_exists( $dir . $css ) ? (string) filemtime( $dir . $css ) : wp_get_theme()->get( 'Version' )
		);
		wp_enqueue_script(
			'ba-studies-settings',
			$uri . $js,
			array( 'jquery', BA_Admin_Repeater_Component::SCRIPT_HANDLE ),
			file_exists( $dir . $js ) ? (string) filemtime( $dir . $js ) : wp_get_theme()->get( 'Version' ),
			true
		);
		wp_localize_script(
			'ba-studies-settings',
			'BAStudiesSettings',
			array(
				'mediaTitle'  => 'انتخاب تصویر اسلایدر',
				'mediaButton' => 'استفاده از این تصویر',
				'notImage'    => 'یک فایل تصویری (JPG، PNG یا WebP) انتخاب کنید.',
			)
		);
	}

	/**
	 * بخش اسلایدهای تصویری Hero با Repeater مشترک.
	 *
	 * @param string $name     نام option.
	 * @param array  $settings تنظیمات.
	 * @return void
	 */
	private static function render_hero_slides( $name, array $settings ) {
		?>
		<h2>اسلایدر Hero</h2>
		<p class="description">
			تصاویر انتخاب‌شده در این بخش <strong>اولویت دارند</strong>: اگر حداقل یک اسلاید اینجا باشد، تصاویر اسلایدر داخل ویجت Elementor نمایش داده نمی‌شوند. اگر این بخش خالی باشد، تصاویر ویجت استفاده می‌شوند.
			تصویر دسکتاپ برای عرض بیش از ۱۰۸۰ پیکسل و تصویر تبلت و موبایل برای عرض ۱۰۸۰ پیکسل و کمتر است؛ ارتفاع اسلایدر از نسبت خود تصویر می‌آید. اگر فقط یکی را انتخاب کنید، برای همه اندازه‌ها استفاده می‌شود.
		</p>
		<?php
		BA_Admin_Repeater_Component::render(
			array(
				'id'           => 'ba-studies-hero-slides',
				'items'        => (array) $settings['hero_slides'],
				'item_label'   => 'اسلاید',
				'add_label'    => 'افزودن اسلاید',
				'empty_label'  => 'اسلایدی انتخاب نشده است؛ تصاویر ویجت Elementor استفاده می‌شوند.',
				'row_renderer' => array( __CLASS__, 'render_hero_slide_row' ),
				'context'      => $name,
				'class_name'   => 'ba-studies-settings__slides',
			)
		);
	}

	/**
	 * یک ردیف اسلاید Hero.
	 *
	 * @param string|int $index   اندیس ردیف.
	 * @param array      $item    داده ردیف.
	 * @param string     $name    نام option.
	 * @return void
	 */
	public static function render_hero_slide_row( $index, array $item, $name ) {
		$item = wp_parse_args(
			$item,
			array(
				'image_id'        => 0,
				'mobile_image_id' => 0,
				'url'             => '',
				'alt'             => '',
			)
		);
		?>
		<div class="ba-studies-settings__slide-grid">
			<?php self::render_media_control( BA_Admin_Repeater_Component::field_name( $name, 'hero_slides', $index, 'image_id' ), 'تصویر دسکتاپ (بیش از ۱۰۸۰ پیکسل)', absint( $item['image_id'] ) ); ?>
			<?php self::render_media_control( BA_Admin_Repeater_Component::field_name( $name, 'hero_slides', $index, 'mobile_image_id' ), 'تصویر تبلت و موبایل (تا ۱۰۸۰ پیکسل)', absint( $item['mobile_image_id'] ) ); ?>
			<label class="ba-studies-settings__field">
				<span class="ba-studies-settings__label">لینک اسلاید (اختیاری)</span>
				<input type="url" dir="ltr" class="regular-text" placeholder="https://" name="<?php echo esc_attr( BA_Admin_Repeater_Component::field_name( $name, 'hero_slides', $index, 'url' ) ); ?>" value="<?php echo esc_attr( $item['url'] ); ?>">
			</label>
			<label class="ba-studies-settings__field">
				<span class="ba-studies-settings__label">متن جایگزین تصویر (alt)</span>
				<input type="text" class="regular-text" name="<?php echo esc_attr( BA_Admin_Repeater_Component::field_name( $name, 'hero_slides', $index, 'alt' ) ); ?>" value="<?php echo esc_attr( $item['alt'] ); ?>">
			</label>
		</div>
		<?php
	}

	/**
	 * کنترل انتخاب یک تصویر از کتابخانه رسانه.
	 *
	 * @param string $input_name نام کامل input.
	 * @param string $label      برچسب.
	 * @param int    $attachment شناسه پیوست.
	 * @return void
	 */
	private static function render_media_control( $input_name, $label, $attachment ) {
		$preview = $attachment ? wp_get_attachment_image_url( $attachment, 'medium' ) : '';
		?>
		<div class="ba-studies-settings__field" data-ba-studies-media>
			<span class="ba-studies-settings__label"><?php echo esc_html( $label ); ?></span>
			<input type="hidden" data-ba-studies-media-input name="<?php echo esc_attr( $input_name ); ?>" value="<?php echo esc_attr( $attachment ); ?>">
			<div class="ba-studies-settings__preview" data-ba-studies-media-preview><?php echo $preview ? '<img src="' . esc_url( $preview ) . '" alt="">' : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above ?></div>
			<div class="ba-studies-settings__media-actions">
				<button type="button" class="button" data-ba-studies-media-select>انتخاب تصویر</button>
				<button type="button" class="button-link-delete" data-ba-studies-media-remove <?php echo $attachment ? '' : 'hidden'; ?>>حذف</button>
			</div>
		</div>
		<?php
	}

	/**
	 * فرم تب.
	 *
	 * @param array $tab پیکربندی تب فعال.
	 * @return void
	 */
	public static function render( $tab ) {
		$settings = BA_Studies_Center_Settings_Service::get_settings();
		$name     = $tab['option_name'];
		?>
		<form id="<?php echo esc_attr( $tab['form_id'] ); ?>" method="post" action="options.php" class="ba-studies-settings ba-settings-form" data-ba-settings-form>
			<input type="hidden" name="ba_settings_tab" value="<?php echo esc_attr( $tab['id'] ); ?>" />
			<?php settings_fields( $tab['option_group'] ); ?>
			<p>متن‌ها و ظاهر صفحه مرکز مطالعات در Elementor مدیریت می‌شوند. این تب فقط اسلایدهای Hero، مقدار دستی آمار و لینک آرشیوها را تعیین می‌کند. دسته‌های کتاب، پژوهش و نشست داخل ویجت‌های Elementor انتخاب می‌شوند. نشست‌ها مقاله‌اند و اطلاعات و حوزه آن‌ها در صفحه ویرایش مقاله است.</p>

			<?php self::render_hero_slides( $name, $settings ); ?>

			<h2>آمار Hero</h2>
			<p class="description">خالی = شمارش خودکار محتوای منتشرشده. عدد = مقدار ثابت.</p>
			<table class="form-table" role="presentation">
				<?php self::render_number_row( $name, 'stats_books', 'کتاب‌ها و انتشارات', $settings['stats_books'] ); ?>
				<?php self::render_number_row( $name, 'stats_sessions', 'نشست‌ها و رویدادهای تخصصی', $settings['stats_sessions'] ); ?>
				<?php self::render_number_row( $name, 'stats_research', 'پژوهش‌ها و گزارش‌های تحلیلی', $settings['stats_research'] ); ?>
			</table>

			<h2>لینک آرشیوها</h2>
			<p class="description">پیش‌فرض لینک «بیشتر» و کارت‌های آمار وقتی در Elementor لینکی تعیین نشده باشد. خالی برای کتاب/پژوهش = آرشیو انتشارات.</p>
			<table class="form-table" role="presentation">
				<?php self::render_url_row( $name, 'books_url', 'آرشیو کتاب‌ها', $settings['books_url'] ); ?>
				<?php self::render_url_row( $name, 'sessions_url', 'صفحه آرشیو نشست‌ها', $settings['sessions_url'] ); ?>
				<?php self::render_url_row( $name, 'research_url', 'آرشیو پژوهش‌ها', $settings['research_url'] ); ?>
			</table>
		</form>
		<?php
	}

	/**
	 * ردیف عدد آمار.
	 *
	 * @param string $name  نام option.
	 * @param string $key   کلید.
	 * @param string $label برچسب.
	 * @param string $value مقدار.
	 * @return void
	 */
	private static function render_number_row( $name, $key, $label, $value ) {
		$id = 'ba-studies-' . $key;
		?>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td><input id="<?php echo esc_attr( $id ); ?>" type="text" inputmode="numeric" class="small-text" name="<?php echo esc_attr( $name . '[' . $key . ']' ); ?>" value="<?php echo esc_attr( $value ); ?>" placeholder="خودکار" /></td>
		</tr>
		<?php
	}

	/**
	 * ردیف لینک.
	 *
	 * @param string $name  نام option.
	 * @param string $key   کلید.
	 * @param string $label برچسب.
	 * @param string $value مقدار.
	 * @return void
	 */
	private static function render_url_row( $name, $key, $label, $value ) {
		$id = 'ba-studies-' . $key;
		?>
		<tr>
			<th scope="row"><label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label></th>
			<td><input id="<?php echo esc_attr( $id ); ?>" type="url" class="regular-text" dir="ltr" name="<?php echo esc_attr( $name . '[' . $key . ']' ); ?>" value="<?php echo esc_attr( $value ); ?>" placeholder="https://" /></td>
		</tr>
		<?php
	}
}

BA_Studies_Center_Settings_Tab::register_hooks();

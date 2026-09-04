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
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
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
	 * Assetهای اختصاصی تب مرکز و Repeater عمومی را فقط در همان تب بارگذاری می‌کند.
	 *
	 * @return void
	 */
	public static function enqueue_assets() {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'ba-settings' !== $page || self::TAB_ID !== $tab ) {
			return;
		}

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
		<form method="post" action="options.php" class="ba-center-settings">
			<?php settings_fields( $tab['option_group'] ); ?>
			<p class="ba-center-settings__lead">محتوای ثابت صفحه مرکز از این تب مدیریت می‌شود. در بخش‌هایی که کنترل متناظر Elementor وجود دارد، مقدار معتبر این صفحه اولویت دارد.</p>

			<?php self::render_hero_section( $name, $settings ); ?>
			<?php self::render_stats_section( $name, $settings ); ?>
			<?php self::render_intro_section( $name, $settings ); ?>
			<?php self::render_system_cards_section( $name, $settings ); ?>
			<?php self::render_partners_section( $name, $settings ); ?>
			<?php self::render_faq_section( $name, $settings ); ?>

			<?php submit_button( 'ذخیره تنظیمات مرکز' ); ?>
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
		$item = wp_parse_args( $item, array( 'icon_id' => 0, 'title' => '', 'url' => '' ) );
		$name = (string) $context;
		?>
		<div class="ba-center-settings__grid">
			<?php self::render_repeater_text_field( $name, 'system_cards', $index, 'title', 'عنوان', $item['title'] ); ?>
			<?php self::render_repeater_url_field( $name, 'system_cards', $index, 'url', 'لینک', $item['url'] ); ?>
		</div>
		<?php
		self::render_media_control(
			BA_Admin_Repeater_Component::field_name( $name, 'system_cards', $index, 'icon_id' ),
			'آیکون (تصویر یا SVG)',
			absint( $item['icon_id'] ),
			'image',
			'انتخاب آیکون'
		);
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
		$type = 'svg' === $item['media_type'] ? 'svg' : 'image';
		$media_type_name = BA_Admin_Repeater_Component::field_name( $name, 'partners', $index, 'media_type' );
		?>
		<div class="ba-center-settings__partner" data-ba-partner-row>
			<div class="ba-center-settings__switcher">
				<span>تصویر</span>
				<label><input type="checkbox" data-ba-media-type-switch <?php checked( 'svg', $type ); ?>><span></span></label>
				<span>SVG / آیکون</span>
				<input type="hidden" data-ba-media-type-value name="<?php echo esc_attr( $media_type_name ); ?>" value="<?php echo esc_attr( $type ); ?>">
			</div>
			<div data-ba-media-mode="image" <?php echo 'image' === $type ? '' : 'hidden'; ?>>
				<?php
				self::render_media_control(
					BA_Admin_Repeater_Component::field_name( $name, 'partners', $index, 'image_id' ),
					'تصویر لوگو',
					absint( $item['image_id'] ),
					'image',
					'انتخاب تصویر'
				);
				?>
			</div>
			<div data-ba-media-mode="svg" <?php echo 'svg' === $type ? '' : 'hidden'; ?>>
				<?php
				self::render_media_control(
					BA_Admin_Repeater_Component::field_name( $name, 'partners', $index, 'svg_id' ),
					'فایل SVG / آیکون',
					absint( $item['svg_id'] ),
					'image/svg+xml',
					'انتخاب SVG'
				);
				?>
			</div>
			<div class="ba-center-settings__grid">
				<?php self::render_repeater_text_field( $name, 'partners', $index, 'title', 'عنوان (اختیاری)', $item['title'] ); ?>
				<?php self::render_repeater_url_field( $name, 'partners', $index, 'url', 'لینک (اختیاری)', $item['url'] ); ?>
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
			<div class="ba-center-settings__grid">
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
			<?php self::render_text( $name, 'intro_title', 'عنوان', $settings['intro_title'] ); ?>
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
			<p class="description">اگر حداقل یک همراه معتبر در این بخش ذخیره شود، لیست همراهان Elementor به‌طور کامل نادیده گرفته می‌شود.</p>
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
			<p class="description">اگر حداقل یک پرسش معتبر در این بخش ذخیره شود، Repeater پرسش‌های Elementor به‌طور کامل نادیده گرفته می‌شود.</p>
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

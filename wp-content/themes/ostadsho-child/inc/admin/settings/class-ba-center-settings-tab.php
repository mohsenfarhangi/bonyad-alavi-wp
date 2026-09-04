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
	 * Assetهای اختصاصی تب مرکز را فقط در همان تب بارگذاری می‌کند.
	 *
	 * @return void
	 */
	public static function enqueue_assets() {
		$page = isset( $_GET['page'] ) ? sanitize_key( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$tab  = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if ( 'ba-settings' !== $page || self::TAB_ID !== $tab ) {
			return;
		}

		$version = wp_get_theme()->get( 'Version' );
		wp_enqueue_media();
		wp_enqueue_style( 'ba-center-settings', get_stylesheet_directory_uri() . '/assets/css/ba-center-settings.css', array( 'ba-admin-settings' ), $version );
		wp_enqueue_script( 'ba-center-settings', get_stylesheet_directory_uri() . '/assets/js/ba-center-settings.js', array( 'jquery' ), $version, true );
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
			<div class="ba-center-settings__repeater" data-ba-repeater="stats">
				<div class="ba-center-settings__repeater-list" data-ba-repeater-list>
					<?php foreach ( (array) $settings['stats'] as $index => $item ) : ?>
						<?php self::render_stat_row( $name, $index, $item ); ?>
					<?php endforeach; ?>
				</div>
				<button type="button" class="button button-secondary" data-ba-repeater-add data-template="ba-center-stat-template">افزودن آمار</button>
			</div>
			<?php self::render_template( 'ba-center-stat-template', self::get_stat_row_template( $name ) ); ?>
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
			<div class="ba-center-settings__repeater" data-ba-repeater="system-cards">
				<div class="ba-center-settings__repeater-list" data-ba-repeater-list>
					<?php foreach ( (array) $settings['system_cards'] as $index => $item ) : ?>
						<?php self::render_system_card_row( $name, $index, $item ); ?>
					<?php endforeach; ?>
				</div>
				<button type="button" class="button button-secondary" data-ba-repeater-add data-template="ba-center-system-card-template">افزودن کارت</button>
			</div>
			<?php self::render_template( 'ba-center-system-card-template', self::get_system_card_row_template( $name ) ); ?>
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
			<div class="ba-center-settings__repeater" data-ba-repeater="partners">
				<div class="ba-center-settings__repeater-list" data-ba-repeater-list>
					<?php foreach ( (array) $settings['partners'] as $index => $item ) : ?>
						<?php self::render_partner_row( $name, $index, $item ); ?>
					<?php endforeach; ?>
				</div>
				<button type="button" class="button button-secondary" data-ba-repeater-add data-template="ba-center-partner-template">افزودن همراه</button>
			</div>
			<?php self::render_template( 'ba-center-partner-template', self::get_partner_row_template( $name ) ); ?>
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
			<div class="ba-center-settings__repeater" data-ba-repeater="faqs">
				<div class="ba-center-settings__repeater-list" data-ba-repeater-list>
					<?php foreach ( (array) $settings['faqs'] as $index => $item ) : ?>
						<?php self::render_faq_row( $name, $index, $item ); ?>
					<?php endforeach; ?>
				</div>
				<button type="button" class="button button-secondary" data-ba-repeater-add data-template="ba-center-faq-template">افزودن پرسش</button>
			</div>
			<?php self::render_template( 'ba-center-faq-template', self::get_faq_row_template( $name ) ); ?>
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
	 * انتخاب یک فایل رسانه‌ای را همراه با پیش‌نمایش رندر می‌کند.
	 *
	 * @param string $name       نام option.
	 * @param string $key        کلید.
	 * @param string $label      برچسب.
	 * @param int    $attachment شناسه پیوست.
	 * @param string $accept     نوع مورد انتظار.
	 * @return void
	 */
	private static function render_media_field( $name, $key, $label, $attachment, $accept = 'image' ) {
		?>
		<div class="ba-center-settings__field ba-center-settings__field--wide" data-ba-media-field data-accept="<?php echo esc_attr( $accept ); ?>">
			<label class="ba-center-settings__label"><?php echo esc_html( $label ); ?></label>
			<input type="hidden" data-ba-media-input name="<?php echo esc_attr( $name . '[' . $key . ']' ); ?>" value="<?php echo esc_attr( $attachment ); ?>">
			<div class="ba-center-settings__media-preview" data-ba-media-preview><?php echo self::get_attachment_preview( $attachment ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
			<div class="ba-center-settings__media-actions">
				<button type="button" class="button" data-ba-media-select>انتخاب رسانه</button>
				<button type="button" class="button-link-delete" data-ba-media-remove <?php echo $attachment ? '' : 'hidden'; ?>>حذف</button>
			</div>
		</div>
		<?php
	}

	/**
	 * یک ردیف آمار را رندر می‌کند.
	 *
	 * @param string $name  نام option.
	 * @param int    $index اندیس.
	 * @param array  $item  داده ردیف.
	 * @return void
	 */
	private static function render_stat_row( $name, $index, array $item ) {
		echo str_replace( '__INDEX__', (string) absint( $index ), self::get_stat_row_template( $name, $item ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * قالب HTML یک ردیف آمار را می‌سازد.
	 *
	 * @param string $name نام option.
	 * @param array  $item مقادیر ردیف.
	 * @return string
	 */
	private static function get_stat_row_template( $name, array $item = array() ) {
		$item = wp_parse_args( $item, array( 'number' => '', 'title' => '', 'subtitle' => '' ) );
		ob_start();
		?>
		<div class="ba-center-settings__repeater-item" data-ba-repeater-item>
			<div class="ba-center-settings__repeater-toolbar"><strong>آیتم آمار</strong><button type="button" class="button-link-delete" data-ba-repeater-remove>حذف</button></div>
			<div class="ba-center-settings__grid">
				<label class="ba-center-settings__field"><span class="ba-center-settings__label">عدد</span><input type="text" name="<?php echo esc_attr( $name ); ?>[stats][__INDEX__][number]" value="<?php echo esc_attr( $item['number'] ); ?>"></label>
				<label class="ba-center-settings__field"><span class="ba-center-settings__label">عنوان</span><input type="text" name="<?php echo esc_attr( $name ); ?>[stats][__INDEX__][title]" value="<?php echo esc_attr( $item['title'] ); ?>"></label>
				<label class="ba-center-settings__field"><span class="ba-center-settings__label">زیرعنوان</span><input type="text" name="<?php echo esc_attr( $name ); ?>[stats][__INDEX__][subtitle]" value="<?php echo esc_attr( $item['subtitle'] ); ?>"></label>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * یک ردیف کارت سامانه را رندر می‌کند.
	 *
	 * @param string $name  نام option.
	 * @param int    $index اندیس.
	 * @param array  $item  داده ردیف.
	 * @return void
	 */
	private static function render_system_card_row( $name, $index, array $item ) {
		echo str_replace( '__INDEX__', (string) absint( $index ), self::get_system_card_row_template( $name, $item ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * قالب HTML یک کارت سامانه را می‌سازد.
	 *
	 * @param string $name نام option.
	 * @param array  $item مقادیر ردیف.
	 * @return string
	 */
	private static function get_system_card_row_template( $name, array $item = array() ) {
		$item = wp_parse_args( $item, array( 'icon_id' => 0, 'title' => '', 'url' => '' ) );
		ob_start();
		?>
		<div class="ba-center-settings__repeater-item" data-ba-repeater-item>
			<div class="ba-center-settings__repeater-toolbar"><strong>کارت مرکز</strong><button type="button" class="button-link-delete" data-ba-repeater-remove>حذف</button></div>
			<div class="ba-center-settings__grid">
				<label class="ba-center-settings__field"><span class="ba-center-settings__label">عنوان</span><input type="text" name="<?php echo esc_attr( $name ); ?>[system_cards][__INDEX__][title]" value="<?php echo esc_attr( $item['title'] ); ?>"></label>
				<label class="ba-center-settings__field"><span class="ba-center-settings__label">لینک</span><input type="url" dir="ltr" name="<?php echo esc_attr( $name ); ?>[system_cards][__INDEX__][url]" value="<?php echo esc_attr( $item['url'] ); ?>"></label>
			</div>
			<div class="ba-center-settings__field" data-ba-media-field data-accept="image">
				<span class="ba-center-settings__label">آیکون (تصویر یا SVG)</span>
				<input type="hidden" data-ba-media-input name="<?php echo esc_attr( $name ); ?>[system_cards][__INDEX__][icon_id]" value="<?php echo esc_attr( absint( $item['icon_id'] ) ); ?>">
				<div class="ba-center-settings__media-preview" data-ba-media-preview><?php echo self::get_attachment_preview( absint( $item['icon_id'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
				<div class="ba-center-settings__media-actions"><button type="button" class="button" data-ba-media-select>انتخاب آیکون</button><button type="button" class="button-link-delete" data-ba-media-remove <?php echo $item['icon_id'] ? '' : 'hidden'; ?>>حذف</button></div>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * یک ردیف همراه را رندر می‌کند.
	 *
	 * @param string $name  نام option.
	 * @param int    $index اندیس.
	 * @param array  $item  داده ردیف.
	 * @return void
	 */
	private static function render_partner_row( $name, $index, array $item ) {
		echo str_replace( '__INDEX__', (string) absint( $index ), self::get_partner_row_template( $name, $item ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * قالب HTML یک همراه را با Switcher نوع رسانه می‌سازد.
	 *
	 * @param string $name نام option.
	 * @param array  $item مقادیر ردیف.
	 * @return string
	 */
	private static function get_partner_row_template( $name, array $item = array() ) {
		$item = wp_parse_args( $item, array( 'media_type' => 'image', 'image_id' => 0, 'svg_id' => 0, 'title' => '', 'url' => '' ) );
		$type = 'svg' === $item['media_type'] ? 'svg' : 'image';
		ob_start();
		?>
		<div class="ba-center-settings__repeater-item" data-ba-repeater-item data-ba-partner-row>
			<div class="ba-center-settings__repeater-toolbar"><strong>همراه مرکز</strong><button type="button" class="button-link-delete" data-ba-repeater-remove>حذف</button></div>
			<div class="ba-center-settings__switcher">
				<span>تصویر</span>
				<label><input type="checkbox" data-ba-media-type-switch <?php checked( 'svg', $type ); ?>><span></span></label>
				<span>SVG / آیکون</span>
				<input type="hidden" data-ba-media-type-value name="<?php echo esc_attr( $name ); ?>[partners][__INDEX__][media_type]" value="<?php echo esc_attr( $type ); ?>">
			</div>
			<div data-ba-media-mode="image" <?php echo 'image' === $type ? '' : 'hidden'; ?>>
				<div class="ba-center-settings__field" data-ba-media-field data-accept="image"><span class="ba-center-settings__label">تصویر لوگو</span><input type="hidden" data-ba-media-input name="<?php echo esc_attr( $name ); ?>[partners][__INDEX__][image_id]" value="<?php echo esc_attr( absint( $item['image_id'] ) ); ?>"><div class="ba-center-settings__media-preview" data-ba-media-preview><?php echo self::get_attachment_preview( absint( $item['image_id'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><div class="ba-center-settings__media-actions"><button type="button" class="button" data-ba-media-select>انتخاب تصویر</button><button type="button" class="button-link-delete" data-ba-media-remove <?php echo $item['image_id'] ? '' : 'hidden'; ?>>حذف</button></div></div>
			</div>
			<div data-ba-media-mode="svg" <?php echo 'svg' === $type ? '' : 'hidden'; ?>>
				<div class="ba-center-settings__field" data-ba-media-field data-accept="image/svg+xml"><span class="ba-center-settings__label">فایل SVG / آیکون</span><input type="hidden" data-ba-media-input name="<?php echo esc_attr( $name ); ?>[partners][__INDEX__][svg_id]" value="<?php echo esc_attr( absint( $item['svg_id'] ) ); ?>"><div class="ba-center-settings__media-preview" data-ba-media-preview><?php echo self::get_attachment_preview( absint( $item['svg_id'] ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div><div class="ba-center-settings__media-actions"><button type="button" class="button" data-ba-media-select>انتخاب SVG</button><button type="button" class="button-link-delete" data-ba-media-remove <?php echo $item['svg_id'] ? '' : 'hidden'; ?>>حذف</button></div></div>
			</div>
			<div class="ba-center-settings__grid">
				<label class="ba-center-settings__field"><span class="ba-center-settings__label">عنوان (اختیاری)</span><input type="text" name="<?php echo esc_attr( $name ); ?>[partners][__INDEX__][title]" value="<?php echo esc_attr( $item['title'] ); ?>"></label>
				<label class="ba-center-settings__field"><span class="ba-center-settings__label">لینک (اختیاری)</span><input type="url" dir="ltr" name="<?php echo esc_attr( $name ); ?>[partners][__INDEX__][url]" value="<?php echo esc_attr( $item['url'] ); ?>"></label>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * یک ردیف پرسش متداول را رندر می‌کند.
	 *
	 * @param string $name  نام option.
	 * @param int    $index اندیس.
	 * @param array  $item  داده ردیف.
	 * @return void
	 */
	private static function render_faq_row( $name, $index, array $item ) {
		echo str_replace( '__INDEX__', (string) absint( $index ), self::get_faq_row_template( $name, $item ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}

	/**
	 * قالب HTML یک پرسش متداول را می‌سازد.
	 *
	 * @param string $name نام option.
	 * @param array  $item مقادیر ردیف.
	 * @return string
	 */
	private static function get_faq_row_template( $name, array $item = array() ) {
		$item = wp_parse_args( $item, array( 'question' => '', 'answer' => '' ) );
		ob_start();
		?>
		<div class="ba-center-settings__repeater-item" data-ba-repeater-item>
			<div class="ba-center-settings__repeater-toolbar"><strong>پرسش</strong><button type="button" class="button-link-delete" data-ba-repeater-remove>حذف</button></div>
			<label class="ba-center-settings__field ba-center-settings__field--wide"><span class="ba-center-settings__label">سؤال</span><input type="text" name="<?php echo esc_attr( $name ); ?>[faqs][__INDEX__][question]" value="<?php echo esc_attr( $item['question'] ); ?>"></label>
			<label class="ba-center-settings__field ba-center-settings__field--wide"><span class="ba-center-settings__label">پاسخ</span><textarea rows="5" name="<?php echo esc_attr( $name ); ?>[faqs][__INDEX__][answer]" ><?php echo esc_textarea( $item['answer'] ); ?></textarea></label>
		</div>
		<?php
		return ob_get_clean();
	}

	/**
	 * قالب یک Repeater را در script غیرقابل اجرا قرار می‌دهد.
	 *
	 * @param string $id   شناسه قالب.
	 * @param string $html HTML قالب.
	 * @return void
	 */
	private static function render_template( $id, $html ) {
		printf( '<script type="text/html" id="%1$s">%2$s</script>', esc_attr( $id ), $html ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
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

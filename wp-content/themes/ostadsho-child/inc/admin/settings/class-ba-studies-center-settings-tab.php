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
	 * فرم تب.
	 *
	 * @param array $tab پیکربندی تب فعال.
	 * @return void
	 */
	public static function render( $tab ) {
		$settings = BA_Studies_Center_Settings_Service::get_settings();
		$name     = $tab['option_name'];
		$terms    = taxonomy_exists( BA_Studies_Content_Types::PUBLICATION_CAT_TAX )
			? get_terms(
				array(
					'taxonomy'   => BA_Studies_Content_Types::PUBLICATION_CAT_TAX,
					'hide_empty' => false,
				)
			)
			: array();
		$terms    = is_array( $terms ) ? $terms : array();
		?>
		<form id="<?php echo esc_attr( $tab['form_id'] ); ?>" method="post" action="options.php" class="ba-studies-settings ba-settings-form" data-ba-settings-form>
			<input type="hidden" name="ba_settings_tab" value="<?php echo esc_attr( $tab['id'] ); ?>" />
			<?php settings_fields( $tab['option_group'] ); ?>
			<p>متن‌ها و ظاهر صفحه مرکز مطالعات در Elementor مدیریت می‌شوند. این تب منبع داده کتاب‌ها و پژوهش‌ها، آمار و لینک آرشیوها را تعیین می‌کند. نشست‌ها از منوی «نشست‌های مرکز مطالعات» و حوزه‌ها از زیرمنوی «حوزه‌ها» مدیریت می‌شوند.</p>

			<?php if ( ! $terms ) : ?>
				<div class="notice notice-warning inline"><p>Taxonomy دسته‌های انتشارات (<code>publications-cat</code>) پیدا نشد یا خالی است.</p></div>
			<?php endif; ?>

			<h2>منبع کتاب‌ها و پژوهش‌ها (انتشارات)</h2>
			<table class="form-table" role="presentation">
				<tr>
					<th scope="row">دسته‌های پژوهش‌ها و گزارش‌های تحلیلی</th>
					<td>
						<?php self::render_term_checkboxes( $name . '[research_cats][]', $terms, $settings['research_cats'] ); ?>
						<p class="description">انتشارات این دسته‌ها در سکشن پژوهش‌ها نمایش داده و از سکشن کتاب‌ها حذف می‌شوند. تا وقتی دسته‌ای انتخاب نشود، سکشن پژوهش‌ها خالی است.</p>
					</td>
				</tr>
				<tr>
					<th scope="row">دسته‌های کتاب‌ها و انتشارات</th>
					<td>
						<?php self::render_term_checkboxes( $name . '[books_cats][]', $terms, $settings['books_cats'] ); ?>
						<p class="description">هیچ انتخاب = همه انتشارات به‌جز دسته‌های پژوهش. دسته‌ای که هم‌زمان پژوهش باشد هنگام ذخیره از این فهرست حذف می‌شود.</p>
					</td>
				</tr>
			</table>

			<h2>آمار Hero</h2>
			<p class="description">خالی = شمارش خودکار محتوای منتشرشده. عدد = مقدار ثابت.</p>
			<table class="form-table" role="presentation">
				<?php self::render_number_row( $name, 'stats_books', 'کتاب‌ها و انتشارات', $settings['stats_books'] ); ?>
				<?php self::render_number_row( $name, 'stats_sessions', 'نشست‌ها و رویدادهای تخصصی', $settings['stats_sessions'] ); ?>
				<?php self::render_number_row( $name, 'stats_research', 'پژوهش‌ها و گزارش‌های تحلیلی', $settings['stats_research'] ); ?>
			</table>

			<h2>لینک آرشیوها</h2>
			<p class="description">پیش‌فرض لینک «بیشتر» و کارت‌های آمار وقتی در Elementor لینکی تعیین نشده باشد. خالی برای کتاب/پژوهش = آرشیو اولین دسته انتخاب‌شده.</p>
			<table class="form-table" role="presentation">
				<?php self::render_url_row( $name, 'books_url', 'آرشیو کتاب‌ها', $settings['books_url'] ); ?>
				<?php self::render_url_row( $name, 'sessions_url', 'صفحه آرشیو نشست‌ها', $settings['sessions_url'] ); ?>
				<?php self::render_url_row( $name, 'research_url', 'آرشیو پژوهش‌ها', $settings['research_url'] ); ?>
			</table>
		</form>
		<?php
	}

	/**
	 * فهرست checkbox دسته‌ها.
	 *
	 * @param string    $field    نام ورودی.
	 * @param WP_Term[] $terms    termها.
	 * @param int[]     $selected انتخاب‌ها.
	 * @return void
	 */
	private static function render_term_checkboxes( $field, array $terms, array $selected ) {
		echo '<fieldset class="ba-studies-settings__terms">';

		foreach ( $terms as $term ) {
			printf(
				'<label style="display:inline-block;margin:0 0 6px 18px"><input type="checkbox" name="%1$s" value="%2$d" %3$s /> %4$s <span class="description">(%5$s)</span></label>',
				esc_attr( $field ),
				(int) $term->term_id,
				checked( in_array( (int) $term->term_id, $selected, true ), true, false ),
				esc_html( $term->name ),
				esc_html( BA_Studies_Formatter::number( $term->count ) )
			);
		}

		echo '</fieldset>';
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

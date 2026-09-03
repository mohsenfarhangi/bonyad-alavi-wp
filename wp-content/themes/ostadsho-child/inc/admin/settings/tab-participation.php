<?php
/**
 * Participation settings tab.
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register participation tab in the dynamic settings registry.
 *
 * @param array $tabs Existing tabs.
 * @return array
 */
function ba_register_participation_settings_tab( $tabs ) {
	$tabs['participation'] = array(
		'title'             => 'تنظیمات مشارکت مردمی',
		'capability'        => 'ba_manage_participation_settings',
		'option_group'      => 'ba_participation_settings_group',
		'option_name'       => 'ba_participation_settings',
		'page_slug'         => 'ba-settings-participation',
		'register_callback' => 'ba_register_participation_settings_fields',
	);

	return $tabs;
}
add_filter( 'ba_settings_tabs', 'ba_register_participation_settings_tab' );

/**
 * Register Settings API fields for participation tab.
 *
 * @param array $tab Tab configuration.
 * @return void
 */
function ba_register_participation_settings_fields( $tab ) {
	register_setting(
		$tab['option_group'],
		$tab['option_name'],
		array(
			'type'              => 'array',
			'sanitize_callback' => 'ba_sanitize_participation_settings',
			'default'           => array(
				'donated_amount_title'          => 'مبلغ واریز شده توسط خیرین',
				'donated_amount'          => 0,
				'completed_projects_title'      => 'تعداد پروژه‌های انجام شده با مشارکت مردم',
				'completed_projects'      => 0,
				'executed_projects_value_title' => 'ارزش پروژه‌های اجرا شده با همکاری خیرین',
				'executed_projects_value' => 0,
				'slider_images'           => array(),
			),
		)
	);

	add_settings_section(
		'ba_participation_general_section',
		'',
		'ba_render_participation_settings_intro',
		$tab['page_slug']
	);

	add_settings_field(
		'donated_amount_title',
		'عنوان مبلغ واریز شده توسط خیرین',
		'ba_render_participation_text_field',
		$tab['page_slug'],
		'ba_participation_general_section',
		array(
			'option_name' => $tab['option_name'],
			'key'         => 'donated_amount_title',
			'description' => 'عنوانی که بالای مبلغ واریزشده توسط خیرین نمایش داده می‌شود.',
		)
	);

	add_settings_field(
		'donated_amount',
		'مبلغ واریز شده توسط خیرین',
		'ba_render_participation_number_field',
		$tab['page_slug'],
		'ba_participation_general_section',
		array(
			'option_name' => $tab['option_name'],
			'key'         => 'donated_amount',
			'format'      => true,
			'description' => 'مبلغ را به‌صورت عدد وارد کنید. هنگام ورود، ارقام سه‌رقم سه‌رقم جدا می‌شوند.',
		)
	);

	add_settings_field(
		'completed_projects_title',
		'عنوان تعداد پروژه‌های انجام شده با مشارکت مردم',
		'ba_render_participation_text_field',
		$tab['page_slug'],
		'ba_participation_general_section',
		array(
			'option_name' => $tab['option_name'],
			'key'         => 'completed_projects_title',
			'description' => 'عنوانی که بالای تعداد پروژه‌های تکمیل‌شده نمایش داده می‌شود.',
		)
	);

	add_settings_field(
		'completed_projects',
		'تعداد پروژه‌های انجام شده با مشارکت مردم',
		'ba_render_participation_number_field',
		$tab['page_slug'],
		'ba_participation_general_section',
		array(
			'option_name' => $tab['option_name'],
			'key'         => 'completed_projects',
			'format'      => false,
			'description' => 'تعداد پروژه‌های تکمیل‌شده را به‌صورت عدد صحیح وارد کنید.',
		)
	);

	add_settings_field(
		'executed_projects_value_title',
		'عنوان ارزش پروژه‌های اجرا شده با همکاری خیرین',
		'ba_render_participation_text_field',
		$tab['page_slug'],
		'ba_participation_general_section',
		array(
			'option_name' => $tab['option_name'],
			'key'         => 'executed_projects_value_title',
			'description' => 'عنوانی که بالای ارزش پروژه‌های اجراشده نمایش داده می‌شود.',
		)
	);

	add_settings_field(
		'executed_projects_value',
		'ارزش پروژه‌های اجرا شده با همکاری خیرین',
		'ba_render_participation_number_field',
		$tab['page_slug'],
		'ba_participation_general_section',
		array(
			'option_name' => $tab['option_name'],
			'key'         => 'executed_projects_value',
			'format'      => true,
			'description' => 'مبلغ نمایشی سه‌رقم سه‌رقم جدا می‌شود.',
		)
	);

	add_settings_field(
		'slider_images',
		'تصاویر اسلایدر',
		'ba_render_participation_slider_field',
		$tab['page_slug'],
		'ba_participation_general_section',
		array(
			'option_name' => $tab['option_name'],
			'key'         => 'slider_images',
		)
	);
}

/**
 * Intro content.
 *
 * @return void
 */
function ba_render_participation_settings_intro() {
	printf(
		'<p class="ba-settings-description">%s</p>',
		esc_html__( 'اعداد و تصاویر عمومی بخش مشارکت مردمی از این قسمت مدیریت می‌شوند.', 'ostadsho-child' )
	);
}

/**
 * Get participation options with defaults.
 *
 * @return array
 */
function ba_get_participation_settings() {
	$defaults = array(
		'donated_amount_title'          => 'مبلغ واریز شده توسط خیرین',
		'donated_amount'          => 0,
		'completed_projects_title'      => 'تعداد پروژه‌های انجام شده با مشارکت مردم',
		'completed_projects'      => 0,
		'executed_projects_value_title' => 'ارزش پروژه‌های اجرا شده با همکاری خیرین',
		'executed_projects_value' => 0,
		'slider_images'           => array(),
	);

	$options = get_option( 'ba_participation_settings', array() );

	return wp_parse_args( is_array( $options ) ? $options : array(), $defaults );
}

/**
 * Convert Persian/Arabic digits to ASCII digits.
 *
 * @param mixed $value Input value.
 * @return string
 */
function ba_normalize_numeric_digits( $value ) {
	$value = (string) $value;

	return strtr(
		$value,
		array(
			'۰' => '0',
			'۱' => '1',
			'۲' => '2',
			'۳' => '3',
			'۴' => '4',
			'۵' => '5',
			'۶' => '6',
			'۷' => '7',
			'۸' => '8',
			'۹' => '9',
			'٠' => '0',
			'١' => '1',
			'٢' => '2',
			'٣' => '3',
			'٤' => '4',
			'٥' => '5',
			'٦' => '6',
			'٧' => '7',
			'٨' => '8',
			'٩' => '9',
		)
	);
}

/**
 * Sanitize a non-negative integer while accepting formatted numbers.
 *
 * @param mixed $value Input value.
 * @return int
 */
function ba_sanitize_integer_value( $value ) {
	$value = ba_normalize_numeric_digits( $value );
	$value = preg_replace( '/[^0-9]/', '', $value );

	if ( '' === $value ) {
		return 0;
	}

	return max( 0, (int) $value );
}

/**
 * Sanitize participation settings before saving to database.
 *
 * @param mixed $input Submitted option value.
 * @return array
 */
function ba_sanitize_participation_settings( $input ) {
	$input = is_array( $input ) ? $input : array();

	$slider_images = isset( $input['slider_images'] ) ? $input['slider_images'] : array();

	if ( is_string( $slider_images ) ) {
		$slider_images = explode( ',', $slider_images );
	}

	$slider_images = is_array( $slider_images ) ? $slider_images : array();
	$slider_images = array_values( array_unique( array_filter( array_map( 'absint', $slider_images ) ) ) );

	return array(
		'donated_amount_title'          => sanitize_text_field( isset( $input['donated_amount_title'] ) ? $input['donated_amount_title'] : '' ),
		'donated_amount'          => ba_sanitize_integer_value( isset( $input['donated_amount'] ) ? $input['donated_amount'] : 0 ),
		'completed_projects_title'      => sanitize_text_field( isset( $input['completed_projects_title'] ) ? $input['completed_projects_title'] : '' ),
		'completed_projects'      => ba_sanitize_integer_value( isset( $input['completed_projects'] ) ? $input['completed_projects'] : 0 ),
		'executed_projects_value_title' => sanitize_text_field( isset( $input['executed_projects_value_title'] ) ? $input['executed_projects_value_title'] : '' ),
		'executed_projects_value' => ba_sanitize_integer_value( isset( $input['executed_projects_value'] ) ? $input['executed_projects_value'] : 0 ),
		'slider_images'           => $slider_images,
	);
}

/**
 * Render a text setting field.
 *
 * @param array $args Field args.
 * @return void
 */
function ba_render_participation_text_field( $args ) {
	$options = ba_get_participation_settings();
	$key     = $args['key'];
	$value   = isset( $options[ $key ] ) ? (string) $options[ $key ] : '';
	?>
	<input
		type="text"
		class="regular-text"
		name="<?php echo esc_attr( $args['option_name'] . '[' . $key . ']' ); ?>"
		value="<?php echo esc_attr( $value ); ?>"
	/>
	<?php if ( ! empty( $args['description'] ) ) : ?>
		<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
	<?php endif; ?>
	<?php
}

/**
 * Render numeric settings field.
 *
 * @param array $args Field args.
 * @return void
 */
function ba_render_participation_number_field( $args ) {
	$options     = ba_get_participation_settings();
	$key         = $args['key'];
	$value       = isset( $options[ $key ] ) ? ba_sanitize_integer_value( $options[ $key ] ) : 0;
	$is_formatted = ! empty( $args['format'] );
	$display     = $is_formatted ? number_format( $value, 0, '.', ',' ) : (string) $value;
	$class       = $is_formatted ? 'regular-text ba-number-input ba-number-input--formatted' : 'regular-text ba-number-input';
	?>
	<input
		type="text"
		inputmode="numeric"
		autocomplete="off"
		class="<?php echo esc_attr( $class ); ?>"
		name="<?php echo esc_attr( $args['option_name'] . '[' . $key . ']' ); ?>"
		value="<?php echo esc_attr( $display ); ?>"
		data-format-thousands="<?php echo $is_formatted ? '1' : '0'; ?>"
	/>
	<?php if ( ! empty( $args['description'] ) ) : ?>
		<p class="description"><?php echo esc_html( $args['description'] ); ?></p>
	<?php endif; ?>
	<?php
}

/**
 * Render multi-image media selector for slider.
 *
 * @param array $args Field args.
 * @return void
 */
function ba_render_participation_slider_field( $args ) {
	$options   = ba_get_participation_settings();
	$image_ids = isset( $options[ $args['key'] ] ) && is_array( $options[ $args['key'] ] )
		? array_values( array_filter( array_map( 'absint', $options[ $args['key'] ] ) ) )
		: array();
	?>
	<div class="ba-media-picker" data-ba-media-picker>
		<input
			type="hidden"
			class="ba-media-picker__input"
			name="<?php echo esc_attr( $args['option_name'] . '[' . $args['key'] . ']' ); ?>"
			value="<?php echo esc_attr( implode( ',', $image_ids ) ); ?>"
		/>

		<div class="ba-media-picker__actions">
			<button type="button" class="button button-secondary ba-media-picker__select">انتخاب تصاویر</button>
			<button type="button" class="button-link-delete ba-media-picker__clear" <?php echo empty( $image_ids ) ? 'hidden' : ''; ?>>حذف همه</button>
		</div>

		<ul class="ba-media-picker__preview" aria-live="polite">
			<?php foreach ( $image_ids as $image_id ) : ?>
				<?php
				$thumbnail = wp_get_attachment_image_url( $image_id, 'thumbnail' );
				if ( ! $thumbnail ) {
					continue;
				}
				?>
				<li class="ba-media-picker__item" data-id="<?php echo esc_attr( $image_id ); ?>">
					<img src="<?php echo esc_url( $thumbnail ); ?>" alt="" />
					<button type="button" class="ba-media-picker__remove" aria-label="حذف تصویر">×</button>
					<span class="dashicons dashicons-move ba-media-picker__drag" aria-hidden="true"></span>
				</li>
			<?php endforeach; ?>
		</ul>

		<p class="ba-media-picker__empty" <?php echo ! empty( $image_ids ) ? 'hidden' : ''; ?>>هنوز تصویری برای اسلایدر انتخاب نشده است.</p>
		<p class="description">می‌توانید چند تصویر را از کتابخانه رسانه وردپرس/ووکامرس انتخاب کنید و با کشیدن تصاویر، ترتیب اسلایدر را تغییر دهید.</p>
	</div>
	<?php
}

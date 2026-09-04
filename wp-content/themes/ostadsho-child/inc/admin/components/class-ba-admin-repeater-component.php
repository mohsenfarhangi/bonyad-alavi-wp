<?php
/**
 * کامپوننت عمومی Repeater برای رابط‌های سفارشی مدیریت وردپرس.
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * رندر، Asset و قرارداد رفتاری Repeaterهای سفارشی wp-admin را متمرکز می‌کند.
 *
 * این کامپوننت برای فرم‌های سفارشی مدیریت، متاباکس‌ها و صفحات Settings API است.
 * Repeater داخلی Elementor باید همچنان از کلاس Elementor\Repeater استفاده کند.
 */
final class BA_Admin_Repeater_Component {

	const INDEX_TOKEN   = '__INDEX__';
	const STYLE_HANDLE  = 'ba-admin-repeater';
	const SCRIPT_HANDLE = 'ba-admin-repeater';

	/**
	 * Assetهای عمومی Repeater را با نسخه مبتنی بر زمان تغییر فایل بارگذاری می‌کند.
	 *
	 * @return void
	 */
	public static function enqueue_assets() {
		$style_relative = 'assets/css/ba-admin-repeater.css';
		$script_relative = 'assets/js/ba-admin-repeater.js';
		$style_path = trailingslashit( get_stylesheet_directory() ) . $style_relative;
		$script_path = trailingslashit( get_stylesheet_directory() ) . $script_relative;
		$style_version = file_exists( $style_path ) ? (string) filemtime( $style_path ) : wp_get_theme()->get( 'Version' );
		$script_version = file_exists( $script_path ) ? (string) filemtime( $script_path ) : wp_get_theme()->get( 'Version' );

		wp_enqueue_style(
			self::STYLE_HANDLE,
			trailingslashit( get_stylesheet_directory_uri() ) . $style_relative,
			array(),
			$style_version
		);

		wp_enqueue_script(
			self::SCRIPT_HANDLE,
			trailingslashit( get_stylesheet_directory_uri() ) . $script_relative,
			array(),
			$script_version,
			true
		);
	}

	/**
	 * یک Repeater کامل را بر اساس Callback رندر فیلدهای هر ردیف نمایش می‌دهد.
	 *
	 * آرگومان row_renderer باید یک callable با امضای زیر باشد:
	 * callback( string|int $index, array $item, mixed $context ): void|string
	 *
	 * @param array $args تنظیمات Repeater.
	 * @return void
	 */
	public static function render( array $args ) {
		$args = wp_parse_args(
			$args,
			array(
				'id'                  => '',
				'items'               => array(),
				'item_label'          => 'آیتم',
				'add_label'           => 'افزودن آیتم',
				'empty_label'         => 'هنوز آیتمی اضافه نشده است.',
				'row_renderer'        => null,
				'context'             => null,
				'show_order_controls' => true,
				'class_name'          => '',
			)
		);

		if ( ! is_callable( $args['row_renderer'] ) ) {
			return;
		}

		$items = array_values(
			array_filter(
				(array) $args['items'],
				'is_array'
			)
		);
		$next_index = count( $items );
		$id = $args['id'] ? sanitize_html_class( $args['id'] ) : wp_unique_id( 'ba-admin-repeater-' );
		$classes = 'ba-admin-repeater';

		if ( $args['class_name'] ) {
			$classes .= ' ' . sanitize_html_class( $args['class_name'] );
		}
		?>
		<div
			id="<?php echo esc_attr( $id ); ?>"
			class="<?php echo esc_attr( $classes ); ?>"
			data-ba-admin-repeater
			data-ba-admin-repeater-next-index="<?php echo esc_attr( $next_index ); ?>"
		>
			<div class="ba-admin-repeater__list" data-ba-admin-repeater-list>
				<?php foreach ( $items as $index => $item ) : ?>
					<?php self::render_item( $index, $item, $args ); ?>
				<?php endforeach; ?>
			</div>

			<p class="ba-admin-repeater__empty" data-ba-admin-repeater-empty <?php echo $items ? 'hidden' : ''; ?>>
				<?php echo esc_html( $args['empty_label'] ); ?>
			</p>

			<div class="ba-admin-repeater__footer">
				<button type="button" class="button button-secondary ba-admin-repeater__add" data-ba-admin-repeater-add>
					<?php echo esc_html( $args['add_label'] ); ?>
				</button>
			</div>

			<template data-ba-admin-repeater-template>
				<?php self::render_item( self::INDEX_TOKEN, array(), $args ); ?>
			</template>
		</div>
		<?php
	}

	/**
	 * نام یک فیلد تو در تو را برای ردیف Repeater تولید می‌کند.
	 *
	 * @param string     $base_name  نام پایه option یا ورودی اصلی.
	 * @param string     $collection کلید آرایه Repeater.
	 * @param string|int $index      اندیس ردیف یا توکن INDEX.
	 * @param string     $field      نام فیلد داخل ردیف.
	 * @return string
	 */
	public static function field_name( $base_name, $collection, $index, $field ) {
		if ( '' === (string) $collection ) {
			return sprintf(
				'%1$s[%2$s][%3$s]',
				(string) $base_name,
				(string) $index,
				(string) $field
			);
		}

		return sprintf(
			'%1$s[%2$s][%3$s][%4$s]',
			(string) $base_name,
			(string) $collection,
			(string) $index,
			(string) $field
		);
	}

	/**
	 * توکن استاندارد اندیس را برای ساخت Template در اختیار مصرف‌کننده قرار می‌دهد.
	 *
	 * @return string
	 */
	public static function get_index_token() {
		return self::INDEX_TOKEN;
	}

	/**
	 * Wrapper استاندارد یک ردیف را ساخته و Callback فیلدهای مصرف‌کننده را اجرا می‌کند.
	 *
	 * @param string|int $index اندیس ردیف.
	 * @param array      $item  داده ردیف.
	 * @param array      $args  تنظیمات Repeater.
	 * @return void
	 */
	private static function render_item( $index, array $item, array $args ) {
		?>
		<div class="ba-admin-repeater__item" data-ba-admin-repeater-item>
			<div class="ba-admin-repeater__toolbar">
				<strong class="ba-admin-repeater__title"><?php echo esc_html( $args['item_label'] ); ?></strong>
				<div class="ba-admin-repeater__actions">
					<?php if ( $args['show_order_controls'] ) : ?>
						<button type="button" class="button button-small ba-admin-repeater__move" data-ba-admin-repeater-up aria-label="<?php echo esc_attr__( 'انتقال به بالا', 'bonyad-alavi-child' ); ?>">↑</button>
						<button type="button" class="button button-small ba-admin-repeater__move" data-ba-admin-repeater-down aria-label="<?php echo esc_attr__( 'انتقال به پایین', 'bonyad-alavi-child' ); ?>">↓</button>
					<?php endif; ?>
					<button type="button" class="button-link-delete ba-admin-repeater__remove" data-ba-admin-repeater-remove>
						<?php esc_html_e( 'حذف', 'bonyad-alavi-child' ); ?>
					</button>
				</div>
			</div>
			<div class="ba-admin-repeater__body">
				<?php self::render_row_content( $args['row_renderer'], $index, $item, $args['context'] ); ?>
			</div>
		</div>
		<?php
	}

	/**
	 * خروجی Callback ردیف را چه به‌صورت echo و چه return امن برای رندر جمع‌آوری می‌کند.
	 *
	 * @param callable   $renderer Callback رندر.
	 * @param string|int $index    اندیس ردیف.
	 * @param array      $item     داده ردیف.
	 * @param mixed      $context  Context مصرف‌کننده.
	 * @return void
	 */
	private static function render_row_content( $renderer, $index, array $item, $context ) {
		ob_start();
		$returned = call_user_func( $renderer, $index, $item, $context );
		$echoed = ob_get_clean();

		if ( '' !== $echoed ) {
			echo $echoed; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- خروجی توسط Callback مصرف‌کننده Escape می‌شود.
		}

		if ( is_string( $returned ) && '' !== $returned ) {
			echo $returned; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- خروجی توسط Callback مصرف‌کننده Escape می‌شود.
		}
	}
}

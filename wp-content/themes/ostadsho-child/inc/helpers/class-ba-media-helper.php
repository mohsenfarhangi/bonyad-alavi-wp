<?php
/**
 * ابزارهای مشترک برای رندر و مدیریت خروجی تصاویر قالب.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Helper مشترک برای ساخت متن جایگزین و HTML امن تصاویر.
 */
final class BA_Media_Helper {

	/**
	 * متن جایگزین یک Attachment را با fallback مشخص برمی‌گرداند.
	 *
	 * @param int    $attachment_id شناسه فایل رسانه‌ای.
	 * @param string $fallback      متن جایگزین در صورت خالی بودن alt.
	 *
	 * @return string
	 */
	public static function get_attachment_alt( $attachment_id, $fallback = '' ) {
		$alt = get_post_meta( absint( $attachment_id ), '_wp_attachment_image_alt', true );

		return '' !== trim( (string) $alt ) ? (string) $alt : (string) $fallback;
	}

	/**
	 * HTML تصویر را از داده نرمال‌شده رسانه تولید می‌کند تا ویجت‌ها منطق تکراری نداشته باشند.
	 *
	 * @param array  $item       داده نرمال‌شده تصویر شامل id، url و alt.
	 * @param string $size       اندازه ثبت‌شده وردپرس.
	 * @param array  $attributes ویژگی‌های HTML تصویر.
	 *
	 * @return string
	 */
	public static function render_image( array $item, $size = 'full', array $attributes = array() ) {
		$attachment_id = isset( $item['id'] ) ? absint( $item['id'] ) : 0;
		$alt           = isset( $item['alt'] ) ? (string) $item['alt'] : '';
		$attributes    = array_merge( array( 'alt' => $alt ), $attributes );

		if ( $attachment_id ) {
			return (string) wp_get_attachment_image( $attachment_id, $size, false, $attributes );
		}

		$url = isset( $item['url'] ) ? esc_url( $item['url'] ) : '';
		if ( '' === $url ) {
			return '';
		}

		$html_attributes = array();
		foreach ( $attributes as $name => $value ) {
			if ( false === $value || null === $value ) {
				continue;
			}

			if ( true === $value ) {
				$html_attributes[] = esc_attr( $name );
				continue;
			}

			$html_attributes[] = sprintf( '%s="%s"', esc_attr( $name ), esc_attr( (string) $value ) );
		}

		return sprintf(
			'<img src="%1$s" %2$s>',
			$url,
			implode( ' ', $html_attributes )
		);
	}

	/**
	 * آیکون Chevron مشترک کنترل‌های قبلی/بعدی را بر اساس جهت برمی‌گرداند.
	 *
	 * @param string $direction جهت منطقی آیکون؛ prev یا next.
	 *
	 * @return string
	 */
	public static function get_chevron_svg( $direction ) {
		if ( 'prev' === $direction ) {
			$path = 'M12.6 12L8.7 8.1q-.275-.275-.275-.7t.275-.7t.7-.275t.7.275l4.6 4.6q.15.15.213.325t.062.375t-.062.375t-.213.325l-4.6 4.6q-.275.275-.7.275t-.7-.275t-.275-.7t.275-.7z';
		} else {
			$path = 'm10.8 12l3.9 3.9q.275.275.275.7t-.275.7t-.7.275t-.7-.275l-4.6-4.6q-.15-.15-.212-.325T8.425 12t.063-.375t.212-.325l4.6-4.6q.275-.275.7-.275t.7.275t.275.7t-.275.7z';
		}

		return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="' . esc_attr( $path ) . '"/></svg>';
	}

	/**
	 * آیکون مناسب دکمه بزرگ‌نمایی یا گالری را تولید می‌کند.
	 *
	 * @param bool $gallery_mode اگر true باشد آیکون گالری نمایش داده می‌شود.
	 *
	 * @return string
	 */
	public static function get_gallery_action_svg( $gallery_mode ) {
		if ( $gallery_mode ) {
			return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 4h11a2 2 0 0 1 2 2v1h1a2 2 0 0 1 2 2v9a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2v-1H4a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2Zm0 2v9h1V9a2 2 0 0 1 2-2h8V6H4Zm3 3v9h11V9H7Zm2 7 2.35-3 1.8 2.2 1.35-1.7L17 16H9Zm6.5-5.75a1.25 1.25 0 1 0 0 2.5 1.25 1.25 0 0 0 0-2.5Z"/></svg>';
		}

		return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M11 4a7 7 0 1 0 4.9 12l4 4 1.4-1.4-4-4A7 7 0 0 0 11 4Zm0 2a5 5 0 1 1 0 10 5 5 0 0 1 0-10Z"/></svg>';
	}

}

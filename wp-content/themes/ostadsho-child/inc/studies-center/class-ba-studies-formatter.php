<?php
/**
 * Helperهای نمایشی مرکز مطالعات: ارقام فارسی، نوع فایل و آیکون‌های SVG.
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * توابع خالص و بدون وابستگی به دیتابیس برای خروجی ویجت‌ها.
 */
final class BA_Studies_Formatter {

	/**
	 * ارقام لاتین را فارسی می‌کند (idempotent).
	 *
	 * @param string $value مقدار.
	 * @return string
	 */
	public static function fa_digits( $value ) {
		return strtr( (string) $value, array( '0' => '۰', '1' => '۱', '2' => '۲', '3' => '۳', '4' => '۴', '5' => '۵', '6' => '۶', '7' => '۷', '8' => '۸', '9' => '۹' ) );
	}

	/**
	 * عدد را با جداکننده هزارگان «٬» و ارقام فارسی برمی‌گرداند.
	 *
	 * @param int $number عدد.
	 * @return string
	 */
	public static function number( $number ) {
		return self::fa_digits( number_format( max( 0, (int) $number ), 0, '', '٬' ) );
	}

	/**
	 * دسته نمایشی فایل برای انتخاب آیکون.
	 *
	 * @param string $mime      نوع MIME.
	 * @param string $extension پسوند.
	 * @return string video|audio|image|archive|document
	 */
	public static function file_kind( $mime, $extension ) {
		$mime      = strtolower( (string) $mime );
		$extension = strtolower( (string) $extension );

		if ( 0 === strpos( $mime, 'video/' ) || in_array( $extension, array( 'mp4', 'webm', 'mov', 'mkv' ), true ) ) {
			return 'video';
		}

		if ( 0 === strpos( $mime, 'audio/' ) || in_array( $extension, array( 'mp3', 'm4a', 'ogg', 'wav' ), true ) ) {
			return 'audio';
		}

		if ( 0 === strpos( $mime, 'image/' ) ) {
			return 'image';
		}

		if ( in_array( $extension, array( 'zip', 'rar', '7z' ), true ) ) {
			return 'archive';
		}

		return 'document';
	}

	/**
	 * آیکون‌های inline تزئینی (aria-hidden) با رنگ currentColor.
	 *
	 * @param string $name نام آیکون.
	 * @return string
	 */
	public static function icon( $name ) {
		$paths = array(
			'folder'        => '<path d="M3 7.5A1.5 1.5 0 0 1 4.5 6h4.2l2 2h8.8A1.5 1.5 0 0 1 21 9.5v8A1.5 1.5 0 0 1 19.5 19h-15A1.5 1.5 0 0 1 3 17.5z" fill="currentColor" stroke="none"/><path d="M3 10.5h18" stroke="#fff" stroke-width="1.4"/>',
			'camera'        => '<rect x="2.5" y="6.5" width="13" height="11" rx="2" fill="currentColor" stroke="none"/><path d="M15.5 10.5 21 7.5v9l-5.5-3z" fill="currentColor" stroke="none"/>',
			'document'      => '<path d="M14 3H7a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V8z"/><path d="M14 3v5h5M9 13h6M9 17h6M9 9h2"/>',
			'calendar'      => '<rect x="4" y="5" width="16" height="15" rx="2"/><path d="M4 10h16M8 3v4M16 3v4"/>',
			'arrow-start'   => '<path d="M19 12H5M11 6l-6 6 6 6"/>',
			'chevron-right' => '<path d="m9 6 6 6-6 6"/>',
			'chevron-left'  => '<path d="m15 6-6 6 6 6"/>',
			'play'          => '<path d="M8 5.5v13l11-6.5z" fill="currentColor" stroke="none"/>',
			'video'         => '<rect x="2.5" y="6.5" width="13" height="11" rx="2" fill="currentColor" stroke="none"/><path d="M15.5 10.5 21 7.5v9l-5.5-3z" fill="currentColor" stroke="none"/>',
			'audio'         => '<path d="M9 18V6l11-2v12"/><circle cx="6.5" cy="18" r="2.5"/><circle cx="17.5" cy="16" r="2.5"/>',
			'image'         => '<rect x="3" y="4" width="18" height="16" rx="2"/><circle cx="9" cy="10" r="2"/><path d="m21 16-5-5-9 9"/>',
			'archive'       => '<rect x="4" y="3" width="16" height="18" rx="2"/><path d="M12 3v2M12 7v2M12 11v2M10.5 15h3v3h-3z"/>',
		);

		if ( ! isset( $paths[ $name ] ) ) {
			return '';
		}

		return '<svg class="ba-studies-icon ba-studies-icon--' . esc_attr( $name ) . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
	}
}

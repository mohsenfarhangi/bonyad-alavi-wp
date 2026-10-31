<?php
/**
 * رندر مشترک کارت‌های کتاب، نشست و پژوهش.
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * markup کارت‌ها یک‌جا ساخته می‌شود تا کاروسل، گرید آرشیو و پاسخ REST فیلتر یکسان باشند.
 */
final class BA_Studies_Card_Renderer {

	/**
	 * گزینه‌های پیش‌فرض نمایش کارت.
	 *
	 * @return array
	 */
	public static function get_default_options() {
		return array(
			'more_text'    => 'مطالعه بیشتر',
			'show_date'    => true,
			'show_excerpt' => true,
			'show_badge'   => true,
			'title_tag'    => 'h3',
		);
	}

	/**
	 * فهرست کارت‌ها را به‌صورت slideهای Swiper برمی‌گرداند.
	 *
	 * @param array $items آیتم‌های نرمال‌شده.
	 * @param array $opts  گزینه‌های نمایش.
	 * @return string
	 */
	public static function render_slides( array $items, array $opts = array() ) {
		$html = '';

		foreach ( $items as $item ) {
			$html .= '<div class="swiper-slide ba-studies-carousel__slide">' . self::render_card( $item, $opts ) . '</div>';
		}

		return $html;
	}

	/**
	 * یک کارت را رندر می‌کند.
	 *
	 * @param array $item آیتم نرمال‌شده.
	 * @param array $opts گزینه‌های نمایش.
	 * @return string
	 */
	public static function render_card( array $item, array $opts = array() ) {
		$opts  = wp_parse_args( $opts, self::get_default_options() );
		$type  = BA_Studies_Query_Service::sanitize_type( $item['type'] ?? 'books' );
		$tag   = in_array( $opts['title_tag'], array( 'h2', 'h3', 'h4', 'h5', 'h6', 'div' ), true ) ? $opts['title_tag'] : 'h3';
		$url   = (string) ( $item['url'] ?? '' );
		$title = (string) ( $item['title'] ?? '' );
		$areas = isset( $item['areas'] ) ? implode( ' ', array_map( 'sanitize_title', (array) $item['areas'] ) ) : '';

		$media = '<a class="ba-studies-card__media" href="' . esc_url( $url ) . '" tabindex="-1" aria-hidden="true">';

		if ( ! empty( $item['image'] ) ) {
			$media .= '<img src="' . esc_url( $item['image'] ) . '" alt="' . esc_attr( (string) ( $item['image_alt'] ?? '' ) ) . '" loading="lazy" decoding="async">';
		} else {
			$media .= '<span class="ba-studies-card__placeholder"></span>';
		}

		if ( 'sessions' === $type ) {
			$media .= '<span class="ba-studies-card__play">' . BA_Studies_Formatter::icon( 'play' ) . '</span>';
		}

		$media .= '</a>';

		$body = '';

		if ( 'research' === $type && $opts['show_badge'] && ! empty( $item['badge'] ) ) {
			$body .= '<span class="ba-studies-card__badge">' . esc_html( $item['badge'] ) . '</span>';
		}

		$body .= '<' . $tag . ' class="ba-studies-card__title"><a href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a></' . $tag . '>';

		if ( 'books' !== $type && $opts['show_excerpt'] && ! empty( $item['excerpt'] ) ) {
			$body .= '<p class="ba-studies-card__excerpt">' . esc_html( wp_strip_all_tags( (string) $item['excerpt'] ) ) . '</p>';
		}

		$footer = '';

		if ( $opts['show_date'] && ! empty( $item['date'] ) ) {
			$footer .= '<span class="ba-studies-card__date">' . BA_Studies_Formatter::icon( 'calendar' ) . '<span>' . esc_html( $item['date'] ) . '</span></span>';
		}

		if ( '' !== (string) $opts['more_text'] ) {
			$footer .= '<a class="ba-studies-card__more" href="' . esc_url( $url ) . '"><span>' . esc_html( $opts['more_text'] ) . '</span>' . BA_Studies_Formatter::icon( 'arrow-start' ) . '</a>';
		}

		if ( '' !== $footer ) {
			$body .= '<div class="ba-studies-card__footer">' . $footer . '</div>';
		}

		return '<article class="ba-studies-card ba-studies-card--' . esc_attr( $type ) . '" data-areas="' . esc_attr( $areas ) . '">'
			. $media
			. '<div class="ba-studies-card__body">' . $body . '</div>'
			. '</article>';
	}

	/**
	 * پیام خالی بودن نتیجه.
	 *
	 * @param string $text متن.
	 * @param bool   $hidden مخفی در رندر اولیه.
	 * @return string
	 */
	public static function render_empty( $text, $hidden = false ) {
		return '<p class="ba-studies-empty" data-ba-studies-empty role="status"' . ( $hidden ? ' hidden' : '' ) . '>' . esc_html( $text ) . '</p>';
	}
}

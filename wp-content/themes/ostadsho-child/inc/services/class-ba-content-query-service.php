<?php
/**
 * سرویس مشترک ساخت Query برای ویجت‌های محتوایی قالب بنیاد علوی.
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * تنظیمات Query المنتور را به آرگومان‌های استاندارد WP_Query تبدیل می‌کند.
 */
final class BA_Content_Query_Service {

	/**
	 * Query را با پیشوند کنترل‌های یک سکشن می‌سازد.
	 *
	 * @param array  $settings تنظیمات ویجت.
	 * @param string $prefix   پیشوند کنترل‌ها مانند news، media یا ticker.
	 * @return WP_Query
	 */
	public function create_query( array $settings, $prefix ) {
		$args = array(
			'post_type'           => $this->resolve_post_types( $this->value( $settings, $prefix . '_post_types', array( 'post' ) ) ),
			'post_status'         => 'publish',
			'posts_per_page'      => max( 1, absint( $this->value( $settings, $prefix . '_posts_per_page', 4 ) ) ),
			'orderby'             => $this->resolve_orderby( $this->value( $settings, $prefix . '_orderby', 'date' ) ),
			'order'               => 'ASC' === strtoupper( (string) $this->value( $settings, $prefix . '_order', 'DESC' ) ) ? 'ASC' : 'DESC',
			'offset'              => absint( $this->value( $settings, $prefix . '_offset', 0 ) ),
			'ignore_sticky_posts' => 'yes' === $this->value( $settings, $prefix . '_ignore_sticky', 'yes' ),
		);

		$include_ids = $this->parse_ids( $this->value( $settings, $prefix . '_include_ids', '' ) );
		$exclude_ids = $this->parse_ids( $this->value( $settings, $prefix . '_exclude_ids', '' ) );
		$categories  = array_map( 'absint', (array) $this->value( $settings, $prefix . '_categories', array() ) );
		$tags        = array_map( 'absint', (array) $this->value( $settings, $prefix . '_tags', array() ) );
		$authors     = array_map( 'absint', (array) $this->value( $settings, $prefix . '_authors', array() ) );
		$after       = sanitize_text_field( $this->value( $settings, $prefix . '_date_after', '' ) );
		$before      = sanitize_text_field( $this->value( $settings, $prefix . '_date_before', '' ) );

		if ( $include_ids ) {
			$args['post__in'] = $include_ids;
		}

		if ( $exclude_ids ) {
			$args['post__not_in'] = $exclude_ids;
		}

		if ( $categories ) {
			$args['category__in'] = array_values( array_filter( $categories ) );
		}

		if ( $tags ) {
			$args['tag__in'] = array_values( array_filter( $tags ) );
		}

		if ( $authors ) {
			$args['author__in'] = array_values( array_filter( $authors ) );
		}

		if ( $after || $before ) {
			$date_clause = array( 'inclusive' => true );
			if ( $after ) {
				$date_clause['after'] = $after;
			}
			if ( $before ) {
				$date_clause['before'] = $before;
			}
			$args['date_query'] = array( $date_clause );
		}

		return new WP_Query( $args );
	}

	/**
	 * یک مقدار تنظیم را با مقدار پیش‌فرض دریافت می‌کند.
	 *
	 * @param array  $settings تنظیمات.
	 * @param string $key      کلید.
	 * @param mixed  $default  مقدار پیش‌فرض.
	 * @return mixed
	 */
	private function value( array $settings, $key, $default = '' ) {
		return isset( $settings[ $key ] ) ? $settings[ $key ] : $default;
	}


	/**
	 * مقدار مرتب‌سازی را به گزینه‌های امن WP_Query محدود می‌کند.
	 *
	 * @param mixed $orderby مقدار ارسالی Elementor.
	 * @return string
	 */
	private function resolve_orderby( $orderby ) {
		$allowed = array( 'date', 'modified', 'title', 'menu_order', 'rand', 'ID' );
		$orderby = (string) $orderby;

		return in_array( $orderby, $allowed, true ) ? $orderby : 'date';
	}

	/**
	 * Post Typeهای عمومی و معتبر را از انتخاب کاربر استخراج می‌کند.
	 *
	 * @param mixed $post_types مقادیر انتخاب‌شده.
	 * @return array|string
	 */
	private function resolve_post_types( $post_types ) {
		$available = get_post_types( array( 'public' => true ), 'names' );
		$selected  = array_intersect( array_map( 'sanitize_key', (array) $post_types ), $available );

		return $selected ? array_values( $selected ) : 'post';
	}

	/**
	 * رشته شناسه‌ها را به آرایه اعداد مثبت و یکتا تبدیل می‌کند.
	 *
	 * @param mixed $value رشته یا آرایه شناسه‌ها.
	 * @return array
	 */
	private function parse_ids( $value ) {
		$values = is_array( $value ) ? $value : preg_split( '/[\s,]+/', (string) $value );
		$values = array_map( 'absint', $values );

		return array_values( array_unique( array_filter( $values ) ) );
	}
}

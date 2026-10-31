<?php
/**
 * سرویس Query کتاب‌ها، نشست‌ها و پژوهش‌های مرکز مطالعات.
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * تنها مالک ساخت WP_Query و شمارش سه نوع خروجی مرکز مطالعات است.
 *
 * - books:    publications در دسته‌های کتاب، بدون دسته‌های پژوهش.
 * - research: publications در دسته‌های پژوهش (از تب تنظیمات).
 * - sessions: CPT نشست.
 * فیلتر حوزه روی هر سه با Taxonomy مشترک ba_study_area اعمال می‌شود.
 */
final class BA_Studies_Query_Service {

	const TYPES = array( 'books', 'sessions', 'research' );

	/**
	 * نوع معتبر را برمی‌گرداند.
	 *
	 * @param string $type نوع.
	 * @return string
	 */
	public static function sanitize_type( $type ) {
		$type = sanitize_key( (string) $type );

		return in_array( $type, self::TYPES, true ) ? $type : 'books';
	}

	/**
	 * شناسه‌های دسته انتخاب‌شده در ویجت یا REST؛ فقط شناسه‌های مثبت و یکتا.
	 *
	 * @param mixed $value آرایه شناسه‌ها یا CSV.
	 * @return int[]
	 */
	public static function sanitize_category_ids( $value ) {
		if ( is_string( $value ) ) {
			$value = explode( ',', $value );
		}
		if ( ! is_array( $value ) ) {
			return array();
		}

		$ids = array();
		foreach ( $value as $raw ) {
			if ( ! is_scalar( $raw ) || ! preg_match( '/^[0-9]+$/', (string) $raw ) ) {
				continue;
			}
			$id = absint( $raw );
			if ( $id ) {
				$ids[ $id ] = $id;
			}
			if ( count( $ids ) >= 50 ) {
				break;
			}
		}
		return array_values( $ids );
	}

	/**
	 * فرمت ایمن دسته‌ها برای REST و داده‌های کاروسل.
	 *
	 * @param mixed $value مقادیر دسته.
	 * @return string
	 */
	public static function sanitize_category_csv( $value ) {
		return implode( ',', self::sanitize_category_ids( $value ) );
	}

	/**
	 * شرط‌های Taxonomy از اطلاعات واقعی Term ساخته می‌شوند.
	 * داخل یک taxonomy = OR؛ بین taxonomyها = AND.
	 *
	 * @param int[] $ids شناسه termها.
	 * @return array|null null برای شناسه‌ای که دیگر وجود ندارد.
	 */
	private static function selected_term_clauses( array $ids ) {
		$groups = array();
		foreach ( $ids as $id ) {
			$term = get_term( $id );
			if ( ! $term || is_wp_error( $term ) || empty( $term->taxonomy ) ) {
				return null;
			}
			$groups[ $term->taxonomy ][] = (int) $id;
		}
		$clauses = array();
		foreach ( $groups as $taxonomy => $term_ids ) {
			$clauses[] = array(
				'taxonomy'         => $taxonomy,
				'field'            => 'term_id',
				'terms'            => $term_ids,
				'operator'         => 'IN',
				'include_children' => true,
			);
		}
		return $clauses;
	}

	/**
	 * Post Typeهای قابل Query برای کاروسل نشست‌ها بر اساس taxonomyهای انتخاب‌شده.
	 *
	 * اگر taxonomy انتخاب‌شده روی CPT نشست ثبت باشد (مثل حوزه)، فقط نشست‌ها می‌آیند.
	 * اگر روی CPT نشست ثبت نباشد (مثل دسته‌بندی نوشته‌ها)، Post Typeهای مشترک همه
	 * taxonomyهای انتخاب‌شده استفاده می‌شوند؛ شروط taxonomyها همچنان با AND ترکیب‌اند.
	 *
	 * @param array $clauses شروط tax_query ساخته‌شده از termهای انتخاب‌شده.
	 * @return string[] خالی یعنی هیچ Post Type مشترکی وجود ندارد.
	 */
	private static function post_types_for_clauses( array $clauses ) {
		$common = null;

		foreach ( $clauses as $clause ) {
			$taxonomy = get_taxonomy( $clause['taxonomy'] );
			$types    = $taxonomy && ! empty( $taxonomy->object_type ) ? array_values( (array) $taxonomy->object_type ) : array();
			$common   = null === $common ? $types : array_values( array_intersect( $common, $types ) );
		}

		if ( null === $common ) {
			return array( BA_Studies_Content_Types::SESSION_POST_TYPE );
		}

		if ( in_array( BA_Studies_Content_Types::SESSION_POST_TYPE, $common, true ) ) {
			return array( BA_Studies_Content_Types::SESSION_POST_TYPE );
		}

		return $common;
	}

	/**
	 * آرگومان‌های WP_Query یک نوع خروجی؛ null یعنی منبع پیکربندی نشده است.
	 *
	 * @param string $type books|sessions|research.
	 * @param array  $opts area, categories, limit, paged, orderby, order.
	 * @return array|null
	 */
	public static function build_args( $type, array $opts = array() ) {
		$type = self::sanitize_type( $type );
		$opts = wp_parse_args(
			$opts,
			array(
				'categories' => array(),
				'area'    => '',
				'limit'   => 8,
				'paged'   => 1,
				'orderby' => 'date',
				'order'   => 'DESC',
			)
		);

		$orderby = in_array( $opts['orderby'], array( 'date', 'title', 'menu_order', 'rand', 'modified' ), true ) ? $opts['orderby'] : 'date';
		$args    = array(
			'post_status'         => 'publish',
			'posts_per_page'      => max( 1, min( 48, (int) $opts['limit'] ) ),
			'paged'               => max( 1, (int) $opts['paged'] ),
			'orderby'             => $orderby,
			'order'               => 'ASC' === strtoupper( (string) $opts['order'] ) ? 'ASC' : 'DESC',
			'ignore_sticky_posts' => true,
		);
		$tax     = array();
		$selected_categories = self::sanitize_category_ids( $opts['categories'] );

		if ( 'sessions' === $type ) {
			$args['post_type'] = BA_Studies_Content_Types::SESSION_POST_TYPE;
			if ( $selected_categories ) {
				$clauses = self::selected_term_clauses( $selected_categories );
				if ( null === $clauses ) {
					return null;
				}
				$tax = array_merge( $tax, $clauses );

				$post_types = self::post_types_for_clauses( $clauses );
				if ( ! $post_types ) {
					return null;
				}
				$args['post_type'] = 1 === count( $post_types ) ? reset( $post_types ) : $post_types;
			}
		} else {
			$settings          = BA_Studies_Center_Settings_Service::get_settings();
			$args['post_type'] = BA_Studies_Content_Types::PUBLICATION_TYPE;
			if ( 'research' === $type ) {
				// دسته‌های مدیریت بر همه گزینه‌های ویجت مقدم‌اند.
				$research_cats = ! empty( $settings['research_cats'] ) ? $settings['research_cats'] : $selected_categories;
				if ( ! $research_cats ) {
					return null;
				}
				if ( ! empty( $settings['research_cats'] ) ) {
					$tax[] = self::cat_clause( $settings['research_cats'], 'IN' );
				} else {
					$clauses = self::selected_term_clauses( $selected_categories );
					if ( null === $clauses ) {
						return null;
					}
					$tax = array_merge( $tax, $clauses );
				}
			} else {
				if ( ! empty( $settings['books_cats'] ) ) {
					$tax[] = self::cat_clause( $settings['books_cats'], 'IN' );
				} elseif ( $selected_categories ) {
					$clauses = self::selected_term_clauses( $selected_categories );
					if ( null === $clauses ) {
						return null;
					}
					$tax = array_merge( $tax, $clauses );
				}
				// پژوهش‌های تنظیم‌شده سراسری به کتاب‌ها وارد نمی‌شوند.
				if ( ! empty( $settings['research_cats'] ) ) {
					$tax[] = self::cat_clause( $settings['research_cats'], 'NOT IN' );
				}
			}
		}

		$area = sanitize_title( (string) $opts['area'] );

		if ( '' !== $area ) {
			$tax[] = array(
				'taxonomy'         => BA_Studies_Content_Types::AREA_TAXONOMY,
				'field'            => 'slug',
				'terms'            => array( $area ),
				'include_children' => true,
			);
		}

		if ( $tax ) {
			$args['tax_query'] = array_merge( array( 'relation' => 'AND' ), $tax ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		}

		return apply_filters( 'ba_studies_query_args', $args, $type, $opts );
	}

	/**
	 * شرط Taxonomy دسته‌های publications.
	 *
	 * @param int[]  $ids      شناسه termها.
	 * @param string $operator IN|NOT IN.
	 * @return array
	 */
	private static function cat_clause( array $ids, $operator ) {
		return array(
			'taxonomy'         => BA_Studies_Content_Types::PUBLICATION_CAT_TAX,
			'field'            => 'term_id',
			'terms'            => array_map( 'absint', $ids ),
			'operator'         => $operator,
			'include_children' => true,
		);
	}

	/**
	 * آیتم‌های نرمال‌شده برای کارت‌ها.
	 *
	 * @param string $type        نوع.
	 * @param array  $opts        گزینه‌های Query.
	 * @param string $date_format فرمت تاریخ.
	 * @return array{items: array, total: int, pages: int}
	 */
	public static function get_items( $type, array $opts = array(), $date_format = 'j F Y' ) {
		$type = self::sanitize_type( $type );
		$args = self::build_args( $type, $opts );

		if ( null === $args ) {
			return array( 'items' => array(), 'total' => 0, 'pages' => 0 );
		}

		$query = new WP_Query( $args );
		$items = array();

		foreach ( $query->posts as $post ) {
			$items[] = self::normalize( $post, $type, $date_format );
		}

		wp_reset_postdata();

		return array(
			'items' => $items,
			'total' => (int) $query->found_posts,
			'pages' => (int) $query->max_num_pages,
		);
	}

	/**
	 * تعداد واقعی محتوای منتشرشده یک نوع.
	 *
	 * @param string $type نوع.
	 * @return int
	 */
	public static function count( $type ) {
		$args = self::build_args( $type, array( 'limit' => 1 ) );

		if ( null === $args ) {
			return 0;
		}

		$args['fields']                 = 'ids';
		$args['update_post_meta_cache'] = false;
		$args['update_post_term_cache'] = false;

		$query = new WP_Query( $args );

		return (int) $query->found_posts;
	}

	/**
	 * مقدار نمایشی آمار: override تب تنظیمات یا شمارش خودکار.
	 *
	 * @param string $type نوع.
	 * @return int
	 */
	public static function stat( $type ) {
		$override = BA_Studies_Center_Settings_Service::get_stat_override( self::sanitize_type( $type ) );

		return null === $override ? self::count( $type ) : $override;
	}

	/**
	 * یک پست را به ساختار کارت تبدیل می‌کند.
	 *
	 * @param WP_Post $post        پست.
	 * @param string  $type        نوع.
	 * @param string  $date_format فرمت تاریخ.
	 * @return array
	 */
	public static function normalize( $post, $type, $date_format = 'j F Y' ) {
		$thumb_id = get_post_thumbnail_id( $post );
		$date     = get_the_date( $date_format, $post );

		if ( 'sessions' === $type ) {
			$meta = BA_Studies_Session_Meta::get( $post->ID );

			if ( $meta['event_date'] ) {
				$timestamp = strtotime( $meta['event_date'] . ' 12:00:00' );
				$date      = $timestamp ? date_i18n( $date_format, $timestamp ) : $date;
			}
		}

		$badge = '';

		if ( 'research' === $type ) {
			$types = get_the_terms( $post, BA_Studies_Content_Types::RESEARCH_TYPE_TAX );
			$badge = is_array( $types ) && $types ? reset( $types )->name : '';
		}

		$areas   = get_the_terms( $post, BA_Studies_Content_Types::AREA_TAXONOMY );
		$excerpt = has_excerpt( $post ) ? get_the_excerpt( $post ) : wp_trim_words( wp_strip_all_tags( strip_shortcodes( $post->post_content ) ), 32, '…' );

		return array(
			'id'        => (int) $post->ID,
			'type'      => $type,
			'title'     => get_the_title( $post ),
			'url'       => get_permalink( $post ),
			'image'     => $thumb_id ? wp_get_attachment_image_url( $thumb_id, 'medium_large' ) : '',
			'image_alt' => $thumb_id ? (string) get_post_meta( $thumb_id, '_wp_attachment_image_alt', true ) : '',
			'date'      => BA_Studies_Formatter::fa_digits( (string) $date ),
			'excerpt'   => $excerpt,
			'badge'     => $badge,
			'areas'     => is_array( $areas ) ? wp_list_pluck( $areas, 'slug' ) : array(),
		);
	}

	/**
	 * حوزه انتخاب‌شده در URL صفحه (?area=).
	 *
	 * @return string
	 */
	public static function get_requested_area() {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- فیلتر عمومی فقط‌خواندنی.
		return isset( $_GET['area'] ) ? sanitize_title( wp_unslash( (string) $_GET['area'] ) ) : '';
	}
}

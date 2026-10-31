<?php
/**
 * Stubهای حداقلی WordPress/Elementor برای رندر ایزوله ویجت‌های مرکز مطالعات.
 *
 * فقط برای harness مقایسه بصری؛ جزو قالب production نیست و فقط از CLI اجرا می‌شود.
 */

declare(strict_types=1);

namespace HarnessGuard {
	if ( PHP_SAPI !== 'cli' ) {
		exit;
	}
}

namespace Elementor {
	final class Controls_Manager {
		const TAB_CONTENT = 'content';
		const TAB_STYLE   = 'style';
		const TEXT        = 'text';
		const TEXTAREA    = 'textarea';
		const SELECT      = 'select';
		const SELECT2     = 'select2';
		const SWITCHER    = 'switcher';
		const NUMBER      = 'number';
		const URL         = 'url';
		const MEDIA       = 'media';
		const REPEATER    = 'repeater';
		const SLIDER      = 'slider';
		const COLOR       = 'color';
		const DIMENSIONS  = 'dimensions';
		const CHOOSE      = 'choose';
	}

	final class Group_Control_Typography {
		public static function get_type() {
			return 'typography';
		}
	}

	class Repeater {
		public function add_control( $id, array $args ) {}
		public function get_controls() {
			return array();
		}
	}

	abstract class Widget_Base {
		private $defaults = array();
		private $overrides;

		public function __construct( array $overrides = array() ) {
			$this->overrides = $overrides;
			$this->register_controls();
		}

		abstract protected function register_controls();
		abstract protected function render();

		protected function start_controls_section( $id, array $args ) {}
		protected function end_controls_section() {}
		protected function add_group_control( $type, array $args ) {}

		protected function add_control( $id, array $args ) {
			$this->defaults[ $id ] = $args['default'] ?? '';
		}

		protected function add_responsive_control( $id, array $args ) {
			$this->defaults[ $id ]             = $args['default'] ?? '';
			$this->defaults[ $id . '_tablet' ] = $args['tablet_default'] ?? '';
			$this->defaults[ $id . '_mobile' ] = $args['mobile_default'] ?? '';
		}

		public function get_settings_for_display() {
			return array_merge( $this->defaults, $this->overrides );
		}

		private $render_attrs = array();

		protected function add_render_attribute( $key, $attr, $value ) {
			$this->render_attrs[ $key ][ $attr ] = implode( ' ', (array) $value );
		}

		protected function add_link_attributes( $key, array $link ) {
			$this->render_attrs[ $key ]['href'] = $link['url'] ?? '';
		}

		protected function get_render_attribute_string( $key ) {
			$out = '';
			foreach ( $this->render_attrs[ $key ] ?? array() as $attr => $value ) {
				$out .= ' ' . $attr . '="' . htmlspecialchars( (string) $value, ENT_QUOTES, 'UTF-8' ) . '"';
			}
			return ltrim( $out );
		}

		public function get_id() {
			return substr( md5( static::class ), 0, 7 );
		}

		public function render_html() {
			ob_start();
			$this->render();
			return (string) ob_get_clean();
		}
	}
}

namespace {
	define( 'ABSPATH', __DIR__ . '/' );

	function esc_html( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ); }
	function esc_attr( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ); }
	function esc_url( $v ) { return htmlspecialchars( (string) $v, ENT_QUOTES, 'UTF-8' ); }
	function esc_url_raw( $v ) { return (string) $v; }
	function esc_html__( $v, $d = '' ) { return esc_html( $v ); }
	function esc_attr__( $v, $d = '' ) { return esc_attr( $v ); }
	function wp_json_encode( $v ) { return json_encode( $v, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ); }
	function sanitize_html_class( $v ) { return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $v ); }
	function sanitize_title( $v ) { return strtolower( preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $v ) ); }
	function sanitize_key( $v ) { return strtolower( preg_replace( '/[^a-z0-9_\-]/i', '', (string) $v ) ); }
	function wp_parse_args( $args, $defaults = array() ) { return array_merge( $defaults, (array) $args ); }
	function wp_strip_all_tags( $v ) { return strip_tags( (string) $v ); }
	function wp_kses_post( $v ) { return (string) $v; }
	function selected( $a, $b ) { if ( (string) $a === (string) $b ) { echo ' selected'; } }
	function apply_filters( $tag, $value ) { return 'the_content' === $tag ? '<p>' . str_replace( "\n\n", '</p><p>', (string) $value ) . '</p>' : $value; }
	function get_the_ID() { return $GLOBALS['harness_post_id'] ?? 0; }
	function get_post_type( $id ) { return BA_Studies_Content_Types::SESSION_POST_TYPE; }
	function get_post_field( $field, $id ) { return $GLOBALS['harness_session']['body'] ?? ''; }
	function get_terms( $args ) { return $GLOBALS['harness_terms'] ?? array(); }
	function taxonomy_exists( $taxonomy ) { return true; }
	function is_wp_error( $value ) { return false; }

	final class BA_Studies_Content_Types {
		const SESSION_POST_TYPE = 'ba_study_session';
		const AREA_TAXONOMY     = 'ba_study_area';
		const PUBLICATION_CAT_TAX = 'publications-cat';
	}

	final class BA_Studies_Center_Settings_Service {
		public static function get_archive_url( $type ) { return '#archive-' . $type; }
	}

	final class BA_Studies_Rest_Controller {
		public static function get_url() { return '/wp-json/ba/v1/studies/items'; }
	}

	/** نسخه fixture سرویس Query؛ منطق Query واقعی با تست‌های contract پوشش داده می‌شود. */
	final class BA_Studies_Query_Service {
		const TYPES = array( 'books', 'sessions', 'research' );
		public static function sanitize_type( $type ) { return in_array( $type, self::TYPES, true ) ? $type : 'books'; }
		public static function get_requested_area() { return ''; }
		public static function sanitize_category_csv( $value ) {
			return implode( ',', array_values( array_unique( array_filter( array_map( 'intval', (array) $value ) ) ) ) );
		}
		public static function get_items( $type, array $opts = array(), $date_format = '' ) {
			$items = $GLOBALS['harness_items'][ $type ] ?? array();
			$items = array_slice( $items, 0, (int) ( $opts['limit'] ?? 8 ) );
			return array( 'items' => $items, 'total' => count( $items ), 'pages' => 1 );
		}
		public static function stat( $type ) { return $GLOBALS['harness_stats'][ $type ] ?? 0; }
	}

	final class BA_Studies_Session_Meta {
		public static function get_view_data( $post_id, $date_format = '' ) { return $GLOBALS['harness_session']; }
	}
}

<?php
/**
 * REST فقط‌خواندنی برای فیلتر حوزه بدون reload.
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GET ba/v1/studies/items → HTML کارت‌های منتشرشده یک نوع در یک حوزه.
 *
 * فقط محتوای publish برگردانده می‌شود، بنابراین endpoint عمومی است و nonce لازم ندارد.
 */
final class BA_Studies_Rest_Controller {

	const NAMESPACE_V1 = 'ba/v1';
	const ROUTE        = '/studies/items';

	/**
	 * ثبت هوک REST.
	 *
	 * @return void
	 */
	public static function register_hooks() {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}

	/**
	 * آدرس کامل endpoint برای JavaScript.
	 *
	 * @return string
	 */
	public static function get_url() {
		return rest_url( self::NAMESPACE_V1 . self::ROUTE );
	}

	/**
	 * ثبت route.
	 *
	 * @return void
	 */
	public static function register_routes() {
		register_rest_route(
			self::NAMESPACE_V1,
			self::ROUTE,
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( __CLASS__, 'get_items' ),
				'permission_callback' => '__return_true',
				'args'                => array(
					'type'         => array( 'type' => 'string', 'enum' => BA_Studies_Query_Service::TYPES, 'required' => true ),
					'area'         => array( 'type' => 'string', 'default' => '', 'sanitize_callback' => 'sanitize_title' ),
					'limit'        => array( 'type' => 'integer', 'default' => 8, 'minimum' => 1, 'maximum' => 24 ),
					'orderby'      => array( 'type' => 'string', 'default' => 'date', 'enum' => array( 'date', 'title', 'menu_order', 'rand', 'modified' ) ),
					'order'        => array( 'type' => 'string', 'default' => 'DESC', 'enum' => array( 'ASC', 'DESC' ) ),
					'more_text'    => array( 'type' => 'string', 'default' => 'مطالعه بیشتر', 'sanitize_callback' => array( __CLASS__, 'sanitize_short_text' ) ),
					'date_format'  => array( 'type' => 'string', 'default' => 'j F Y', 'sanitize_callback' => array( __CLASS__, 'sanitize_date_format' ) ),
					'show_date'    => array( 'type' => 'boolean', 'default' => true ),
					'show_excerpt' => array( 'type' => 'boolean', 'default' => true ),
					'show_badge'   => array( 'type' => 'boolean', 'default' => true ),
					'title_tag'    => array( 'type' => 'string', 'default' => 'h3', 'enum' => array( 'h2', 'h3', 'h4', 'h5', 'h6', 'div' ) ),
				),
			)
		);
	}

	/**
	 * پاسخ endpoint.
	 *
	 * @param WP_REST_Request $request درخواست.
	 * @return WP_REST_Response
	 */
	public static function get_items( WP_REST_Request $request ) {
		$type   = BA_Studies_Query_Service::sanitize_type( $request['type'] );
		$result = BA_Studies_Query_Service::get_items(
			$type,
			array(
				'area'    => $request['area'],
				'limit'   => (int) $request['limit'],
				'orderby' => $request['orderby'],
				'order'   => $request['order'],
			),
			$request['date_format']
		);

		$html = BA_Studies_Card_Renderer::render_slides(
			$result['items'],
			array(
				'more_text'    => $request['more_text'],
				'show_date'    => (bool) $request['show_date'],
				'show_excerpt' => (bool) $request['show_excerpt'],
				'show_badge'   => (bool) $request['show_badge'],
				'title_tag'    => $request['title_tag'],
			)
		);

		$response = new WP_REST_Response(
			array(
				'html'  => $html,
				'count' => count( $result['items'] ),
				'total' => $result['total'],
			)
		);
		$response->header( 'Cache-Control', 'public, max-age=300' );

		return $response;
	}

	/**
	 * متن کوتاه امن (حداکثر ۴۰ کاراکتر).
	 *
	 * @param mixed $value مقدار.
	 * @return string
	 */
	public static function sanitize_short_text( $value ) {
		return mb_substr( sanitize_text_field( (string) $value ), 0, 40 );
	}

	/**
	 * فقط کاراکترهای مجاز فرمت تاریخ PHP.
	 *
	 * @param mixed $value مقدار.
	 * @return string
	 */
	public static function sanitize_date_format( $value ) {
		$value = preg_replace( '/[^a-zA-Z ,.\/\-]/', '', (string) $value );

		return '' === trim( $value ) ? 'j F Y' : mb_substr( $value, 0, 20 );
	}
}

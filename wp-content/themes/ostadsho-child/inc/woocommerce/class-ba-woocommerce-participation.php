<?php

defined( 'ABSPATH' ) || exit;

final class Bonyad_Alavi_WooCommerce_Participation {

	const AJAX_ACTION           = 'bap_add_participation_to_cart';
	const UPDATE_ACTION         = 'bap_update_participation_cart';
	const QUICK_PREPARE_ACTION  = 'bap_prepare_quick_checkout';
	const QUICK_PAYMENT_ACTION  = 'bap_process_quick_payment';
	const NONCE_ACTION          = 'bap_participation_cart';

	const GOAL_META_KEY   = 'goal_amount';

	const CART_AMOUNT_KEY = 'bap_participation_amount';
	const CART_MARKER_KEY = 'bap_participation_project';

	/** @var BA_Participation_Quick_Checkout_Service سرویس Checkout سریع مشارکت. */
	private $quick_checkout_service;

	/**
	 * Hookهای WooCommerce و Endpointهای AJAX مشارکت را ثبت می‌کند.
	 */
	public function __construct() {
		$gateway_service              = new BA_Participation_Payment_Gateway_Service();
		$this->quick_checkout_service = new BA_Participation_Quick_Checkout_Service( $gateway_service );

		/*
		 * AJAX اختصاصی WooCommerce.
		 * برای کاربر مهمان و لاگین‌شده قابل استفاده است.
		 */
		add_action(
			'wc_ajax_' . self::AJAX_ACTION,
			array( $this, 'ajax_add_to_cart' )
		);

		add_action(
			'wc_ajax_' . self::UPDATE_ACTION,
			array( $this, 'ajax_update_participation_cart' )
		);

		add_action(
			'wc_ajax_' . self::QUICK_PREPARE_ACTION,
			array( $this, 'ajax_prepare_quick_checkout' )
		);

		add_action(
			'wc_ajax_' . self::QUICK_PAYMENT_ACTION,
			array( $this, 'ajax_process_quick_payment' )
		);

		/*
		 * اعمال مبلغ مشارکت به‌عنوان قیمت واقعی محصول در سبد.
		 */
		add_action(
			'woocommerce_before_calculate_totals',
			array( $this, 'apply_participation_prices' ),
			20
		);

		/*
		 * اگر محصول پروژه قیمت ثابت نداشته باشد،
		 * همچنان امکان افزودن آن به سبد وجود داشته باشد.
		 */
		add_filter(
			'woocommerce_is_purchasable',
			array( $this, 'make_project_purchasable' ),
			20,
			2
		);

		/*
		 * نمایش مبلغ مشارکت در سبد.
		 */
		add_filter(
			'woocommerce_get_item_data',
			array( $this, 'display_cart_item_data' ),
			10,
			2
		);

		/*
		 * تعداد پروژه همیشه 1 باشد.
		 */
		add_filter(
			'woocommerce_cart_item_quantity',
			array( $this, 'lock_cart_item_quantity' ),
			10,
			3
		);

		/*
		 * ذخیره مبلغ مشارکت داخل آیتم سفارش.
		 */
		add_action(
			'woocommerce_checkout_create_order_line_item',
			array( $this, 'save_order_item_meta' ),
			10,
			4
		);
	}

	public static function get_ajax_endpoint() {

		if ( ! class_exists( 'WC_AJAX' ) ) {
			return '';
		}

		return WC_AJAX::get_endpoint( self::AJAX_ACTION );
	}

	public static function create_nonce() {
		return wp_create_nonce( self::NONCE_ACTION );
	}

	/**
	 * Endpoint مرحله آماده‌سازی فاکتور و Checkout Inline را برمی‌گرداند.
	 *
	 * @return string
	 */
	public static function get_quick_prepare_endpoint() {
		return class_exists( 'WC_AJAX' ) ? WC_AJAX::get_endpoint( self::QUICK_PREPARE_ACTION ) : '';
	}

	/**
	 * Endpoint ایجاد سفارش و شروع پرداخت مستقیم را برمی‌گرداند.
	 *
	 * @return string
	 */
	public static function get_quick_payment_endpoint() {
		return class_exists( 'WC_AJAX' ) ? WC_AJAX::get_endpoint( self::QUICK_PAYMENT_ACTION ) : '';
	}

	public static function get_goal_amount( $product_id ) {

		$product_id = absint( $product_id );

		if ( ! $product_id ) {
			return 0;
		}

		$value = get_post_meta( $product_id, self::GOAL_META_KEY, true );

		return self::normalize_amount( $value );
	}

	public static function get_minimum_amount( $product_id ) {
		return self::normalize_amount( get_post_meta( absint( $product_id ), 'minimum_amount', true ) );
	}

	public static function is_product_in_cart( $product_id ) {

		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return false;
		}

		return false !== self::find_cart_item_key( $product_id );
	}

	public function ajax_add_to_cart() {

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

		if ( ! $nonce ||
			! wp_verify_nonce( $nonce, self::NONCE_ACTION )
		) {
			$this->send_error( 'درخواست معتبر نیست. لطفاً صفحه را تازه‌سازی کنید.', 403 );
		}

		if ( ! function_exists( 'WC' ) ) {
			$this->send_error( 'ووکامرس در دسترس نیست.', 500 );
		}

		if ( ! WC()->cart &&
			function_exists( 'wc_load_cart' )
		) {
			wc_load_cart();
		}

		if ( ! WC()->cart ) {
			$this->send_error( 'سبد مشارکت در دسترس نیست.', 500 );
		}

		$product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
		$amount     = isset( $_POST['amount'] ) ? self::normalize_amount( wp_unslash( $_POST['amount'] ) ) : 0;
		$context    = self::validate_project_amount( $product_id, $amount );

		if ( is_wp_error( $context ) ) {
			$this->send_error( $context->get_error_message() );
		}

		$product     = $context['product'];
		$goal_amount = $context['goal_amount'];
		$amount      = $context['amount'];

		$existing_cart_key = self::find_cart_item_key( $product_id );

		$updated = false;

		/*
		 * اگر پروژه قبلاً داخل سبد باشد،
		 * همان آیتم را ویرایش می‌کنیم.
		 */
		if ( false !== $existing_cart_key ) {

			WC()->cart->cart_contents[ $existing_cart_key ][ self::CART_AMOUNT_KEY ] = $amount;

			WC()->cart->cart_contents[ $existing_cart_key ][ self::CART_MARKER_KEY ] = 1;

			WC()->cart->cart_contents[ $existing_cart_key ]['quantity'] = 1;

			if ( isset( WC()->cart->cart_contents[ $existing_cart_key ]['data'] )
			     && WC()->cart->cart_contents[ $existing_cart_key ]['data'] instanceof WC_Product )
			{
				WC()->cart->cart_contents[ $existing_cart_key ]['data']->set_price( $amount );
			}

			$updated = true;

		} else {

			$cart_item_key = WC()->cart->add_to_cart(
				$product_id,
				1,
				0,
				[],
				[
					self::CART_AMOUNT_KEY => $amount,
					self::CART_MARKER_KEY => 1,
				]
			);

			if ( false === $cart_item_key ) {
				$this->send_error(
					'افزودن پروژه به سبد مشارکت انجام نشد.'
				);
			}
		}

		WC()->cart->calculate_totals();
		WC()->cart->set_session();

		wp_send_json_success(
			[
				'message' => $updated
					? 'مبلغ مشارکت این پروژه در سبد مشارکت به‌روزرسانی شد.'
					: 'مبلغ مشارکت به سبد مشارکت اضافه شد.',

				'updated'     => $updated,
				'amount'      => $amount,
				'goal_amount' => $goal_amount,

				'cart_url' => wc_get_cart_url(),

				'cart_hash' => WC()->cart->get_cart_hash(),

				'cart_count' => WC()->cart->get_cart_contents_count(),

				'fragments' => $this->get_cart_fragments(),
			]
		);
	}

	/**
	 * مرحله اول پرداخت سریع: فاکتور و فیلدهای فعال Checkout را برمی‌گرداند.
	 *
	 * @return void
	 */
	public function ajax_prepare_quick_checkout() {
		$this->verify_ajax_nonce();

		$product_id = isset( $_POST['product_id'] ) ? absint( wp_unslash( $_POST['product_id'] ) ) : 0;
		$amount     = isset( $_POST['amount'] ) ? self::normalize_amount( wp_unslash( $_POST['amount'] ) ) : 0;
		$result     = $this->quick_checkout_service->prepare( $product_id, $amount );

		if ( is_wp_error( $result ) ) {
			$this->send_error( $result->get_error_message() );
		}

		wp_send_json_success( $result );
	}

	/**
	 * مرحله دوم پرداخت سریع: سفارش مستقل را می‌سازد و Redirect درگاه را برمی‌گرداند.
	 *
	 * @return void
	 */
	public function ajax_process_quick_payment() {
		$this->verify_ajax_nonce();

		$posted = array();
		foreach ( $_POST as $key => $value ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$posted[ sanitize_key( $key ) ] = is_array( $value ) ? wc_clean( wp_unslash( $value ) ) : wp_unslash( $value );
		}

		$result = $this->quick_checkout_service->process( $posted );

		if ( is_wp_error( $result ) ) {
			$this->send_error( $result->get_error_message() );
		}

		wp_send_json_success( $result );
	}

	/**
	 * پروژه و مبلغ مشارکت را یک‌بار و به‌صورت مشترک برای Cart و Quick Checkout اعتبارسنجی می‌کند.
	 *
	 * @param int   $product_id شناسه پروژه.
	 * @param float $amount مبلغ مشارکت.
	 * @return array<string,mixed>|WP_Error
	 */
	public static function validate_project_amount( $product_id, $amount ) {
		$product_id = absint( $product_id );
		$amount     = self::normalize_amount( $amount );

		if ( ! $product_id ) {
			return new WP_Error( 'bap_invalid_project', 'پروژه معتبر نیست.' );
		}

		$product = wc_get_product( $product_id );
		if ( ! $product ) {
			return new WP_Error( 'bap_project_missing', 'پروژه پیدا نشد.' );
		}

		if ( 'publish' !== $product->get_status() || ! $product->is_in_stock() ) {
			return new WP_Error( 'bap_project_unavailable', 'این پروژه در حال حاضر قابل مشارکت نیست.' );
		}

		if ( ! $product->is_type( 'simple' ) ) {
			return new WP_Error( 'bap_invalid_product_type', 'نوع محصول این پروژه پشتیبانی نمی‌شود.' );
		}

		$goal_amount = self::get_goal_amount( $product_id );
		if ( $goal_amount <= 0 ) {
			return new WP_Error( 'bap_goal_missing', 'هدف مشارکت مردمی برای این پروژه تعریف نشده است.' );
		}

		$minimum_amount = self::get_minimum_amount( $product_id );
		if ( $amount < max( 1, $minimum_amount ) ) {
			return new WP_Error( 'bap_amount_minimum', sprintf( 'حداقل مبلغ مشارکت %s تومان است.', number_format_i18n( $minimum_amount ) ) );
		}

		if ( $amount > $goal_amount ) {
			return new WP_Error( 'bap_amount_maximum', sprintf( 'مبلغ مشارکت نمی‌تواند بیشتر از %s باشد.', wp_strip_all_tags( wc_price( $goal_amount ) ) ) );
		}

		return array(
			'product'        => $product,
			'amount'         => $amount,
			'goal_amount'    => $goal_amount,
			'minimum_amount' => $minimum_amount,
		);
	}

	public function ajax_update_participation_cart() {

		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! $nonce || ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			$this->send_error( 'درخواست معتبر نیست.', 403 );
		}

		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			$this->send_error( 'سبد مشارکت در دسترس نیست.', 500 );
		}

		$cart_item_key = isset( $_POST['cart_item_key'] ) ? wc_clean( wp_unslash( $_POST['cart_item_key'] ) ) : '';
		$amount        = isset( $_POST['amount'] ) ? self::normalize_amount( wp_unslash( $_POST['amount'] ) ) : 0;
		$cart_item     = $cart_item_key ? WC()->cart->get_cart_item( $cart_item_key ) : false;

		if ( ! $cart_item || empty( $cart_item[ self::CART_MARKER_KEY ] ) ) {
			$this->send_error( 'آیتم سبد مشارکت پیدا نشد.' );
		}

		$goal_amount = self::get_goal_amount( $cart_item['product_id'] );
		$minimum_amount = self::get_minimum_amount( $cart_item['product_id'] );
		if ( $amount < max( 1, $minimum_amount ) || $amount > $goal_amount ) {
			$this->send_error( 'مبلغ مشارکت واردشده معتبر نیست.' );
		}

		WC()->cart->cart_contents[ $cart_item_key ][ self::CART_AMOUNT_KEY ] = $amount;
		WC()->cart->cart_contents[ $cart_item_key ]['quantity'] = 1;
		WC()->cart->calculate_totals();
		WC()->cart->set_session();

		wp_send_json_success(
			array(
				'amount'    => $amount,
				'cart_hash' => WC()->cart->get_cart_hash(),
				'total'     => WC()->cart->get_total(),
			)
		);
	}

	public function apply_participation_prices( $cart ) {

		if ( ! $cart instanceof WC_Cart ) {
			return;
		}

		if ( is_admin() && ! wp_doing_ajax() ) {
			return;
		}

		foreach ( $cart->get_cart() as $cart_item_key => $cart_item ) {

			if ( empty( $cart_item[ self::CART_MARKER_KEY ] )
			     || ! isset( $cart_item[ self::CART_AMOUNT_KEY ] ) ) {
				continue;
			}

			$product_id = isset( $cart_item['product_id'] ) ? absint( $cart_item['product_id'] ) : 0;

			$goal_amount = self::get_goal_amount( $product_id );

			if ( $goal_amount <= 0 ) {
				continue;
			}

			$amount = self::normalize_amount(
				$cart_item[ self::CART_AMOUNT_KEY ]
			);

			/*
			 * اگر goal بعداً کاهش پیدا کرد،
			 * مبلغ موجود در سبد نیز از هدف بالاتر نرود.
			 */
			$amount = min( $amount, $goal_amount );

			if ( $amount <= 0 ) {
				continue;
			}

			$cart->cart_contents[
			$cart_item_key ][ self::CART_AMOUNT_KEY ] = $amount;

			/*
			 * تعداد همیشه یک است تا مبلغ ضرب نشود.
			 */
			$cart->cart_contents[ $cart_item_key ]['quantity'] = 1;

			if ( isset( $cart->cart_contents[ $cart_item_key ]['data'] ) && $cart->cart_contents[ $cart_item_key ]['data'] instanceof WC_Product ) {
				$cart->cart_contents[ $cart_item_key ]['data']->set_price( $amount );
			}
		}
	}

	public function make_project_purchasable( $purchasable, $product ) {

		if ( ! $product instanceof WC_Product ) {
			return $purchasable;
		}

		if ( 'publish' !== $product->get_status() || ! $product->is_type( 'simple' ) ) {
			return $purchasable;
		}

		if ( self::get_goal_amount( $product->get_id() ) > 0 ) {
			return true;
		}

		return $purchasable;
	}

	public function display_cart_item_data( $item_data, $cart_item ) {

		if ( empty( $cart_item[ self::CART_MARKER_KEY ] ) || ! isset(				$cart_item[				self::CART_AMOUNT_KEY ] ) ) {
			return $item_data;
		}

		$amount = self::normalize_amount( $cart_item[ self::CART_AMOUNT_KEY ] );

		$item_data[] = array(
			'key'   => 'مبلغ مشارکت',
			'value' => wc_price( $amount ),
		);

		return $item_data;
	}

	public function lock_cart_item_quantity( $product_quantity, $cart_item_key, $cart_item ) {

		if ( empty( $cart_item[ self::CART_MARKER_KEY ] ) ) {
			return $product_quantity;
		}

		return sprintf(
			'<span class="bap-cart-quantity">1</span>
			<input type="hidden"
				name="cart[%s][qty]"
				value="1">',
			esc_attr( $cart_item_key )
		);
	}

	public function save_order_item_meta( $item, $cart_item_key, $values, $order ) {

		if ( empty( $values[ self::CART_MARKER_KEY ] ) || ! isset( $values[ self::CART_AMOUNT_KEY ] ) ) {
			return;
		}

		$amount = self::normalize_amount( $values[ self::CART_AMOUNT_KEY ] );

		$item->add_meta_data( '_bap_participation_amount', $amount, true );

		$item->add_meta_data( '_bap_goal_amount', self::get_goal_amount( $values['product_id'] ), true );
	}

	private static function find_cart_item_key( $product_id ) {

		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return false;
		}

		$product_id = absint( $product_id );

		foreach ( WC()->cart->get_cart() as $cart_item_key => $cart_item ) {

			if ( isset( $cart_item['product_id'] ) && absint( $cart_item['product_id'] ) === $product_id ) {
				return $cart_item_key;
			}
		}

		return false;
	}

	/**
	 * ارقام فارسی/عربی و جداکننده‌ها را به مبلغ عددی استاندارد تبدیل می‌کند.
	 *
	 * @param mixed $value مقدار خام.
	 * @return float
	 */
	public static function normalize_amount( $value ) {

		$value = (string) $value;

		$value = strtr( $value, [
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
		] );

		$value = preg_replace( '/[^0-9]/', '', $value );

		if ( '' === $value ) {
			return 0;
		}

		return max(
			0,
			(float) $value
		);
	}

	private function get_cart_fragments() {

		ob_start();

		woocommerce_mini_cart();

		$mini_cart = ob_get_clean();

		return apply_filters(
			'woocommerce_add_to_cart_fragments',
			[
				'div.widget_shopping_cart_content' => '<div class="widget_shopping_cart_content">' . $mini_cart . '</div>',
			]
		);
	}

	/**
	 * Nonce مشترک Endpointهای AJAX مشارکت را بررسی می‌کند.
	 *
	 * @return void
	 */
	private function verify_ajax_nonce() {
		$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

		if ( ! $nonce || ! wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			$this->send_error( 'درخواست معتبر نیست. لطفاً صفحه را تازه‌سازی کنید.', 403 );
		}
	}

	private function send_error( $message, $status = 422 ) {

		wp_send_json_error(
			[
				'message' => $message,
			],
			$status
		);
	}
}

if ( class_exists( 'WooCommerce' ) ) {
	new Bonyad_Alavi_WooCommerce_Participation();
}

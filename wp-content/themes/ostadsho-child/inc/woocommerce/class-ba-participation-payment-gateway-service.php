<?php
/**
 * سرویس درگاه پرداخت پرداخت سریع مشارکت مردمی.
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * دسترسی به درگاه فعال انتخاب‌شده و اجرای بررسی availability در یک سبد موقت ایزوله.
 */
final class BA_Participation_Payment_Gateway_Service {

	const OPTION_NAME = 'ba_participation_settings';
	const OPTION_KEY  = 'payment_gateway';

	/**
	 * درگاه‌های فعال ووکامرس را برای نمایش در تنظیمات مدیریت برمی‌گرداند.
	 *
	 * @return array<string,string>
	 */
	public function get_active_gateway_choices() {
		$choices = array();

		foreach ( $this->get_all_gateways() as $gateway ) {
			if ( ! $gateway instanceof WC_Payment_Gateway || 'yes' !== $gateway->enabled ) {
				continue;
			}

			$gateway_id = $this->sanitize_gateway_id( $gateway->id );

			if ( '' === $gateway_id ) {
				continue;
			}

			$choices[ $gateway_id ] = wp_strip_all_tags( $gateway->get_title() ?: $gateway->get_method_title() );
		}

		return $choices;
	}

	/**
	 * شناسه درگاه ذخیره‌شده در تنظیمات مشارکت را برمی‌گرداند.
	 *
	 * @return string
	 */
	public function get_selected_gateway_id() {
		$options    = get_option( self::OPTION_NAME, array() );
		$gateway_id = isset( $options[ self::OPTION_KEY ] ) ? $this->sanitize_gateway_id( $options[ self::OPTION_KEY ] ) : '';

		return $this->resolve_registered_gateway_id( $gateway_id );
	}

	/**
	 * نمونه درگاه انتخاب‌شده و فعال را برمی‌گرداند.
	 *
	 * @return WC_Payment_Gateway|WP_Error
	 */
	public function get_selected_gateway() {
		$gateway_id = $this->get_selected_gateway_id();

		if ( '' === $gateway_id ) {
			return new WP_Error( 'bap_gateway_not_configured', 'درگاه پرداخت مشارکت در تنظیمات بنیاد علوی انتخاب نشده است.' );
		}

		$gateway = $this->find_gateway_by_id( $gateway_id );

		if ( ! $gateway instanceof WC_Payment_Gateway ) {
			return new WP_Error( 'bap_gateway_missing', 'درگاه پرداخت انتخاب‌شده در ووکامرس در دسترس نیست.' );
		}

		if ( 'yes' !== $gateway->enabled ) {
			return new WP_Error( 'bap_gateway_disabled', 'درگاه پرداخت انتخاب‌شده غیرفعال است.' );
		}

		return $gateway;
	}

	/**
	 * شناسه درگاه انتخاب‌شده را برای ذخیره تنظیمات نرمال و پاک‌سازی می‌کند.
	 *
	 * اعتبارسنجی فعال/قابل‌استفاده بودن Gateway عمداً در این مرحله انجام نمی‌شود؛
	 * بعضی افزونه‌های درگاه در درخواست admin-ajax همه Gatewayها را initialize نمی‌کنند
	 * و وابستگی Sanitize به Runtime Gateway Registry می‌تواند انتخاب معتبر را خالی کند.
	 * اعتبارسنجی قطعی در زمان پرداخت توسط get_selected_gateway() و
	 * validate_gateway_availability() انجام می‌شود.
	 *
	 * @param mixed $gateway_id شناسه خام درگاه.
	 * @return string
	 */
	public function sanitize_gateway_id( $gateway_id ) {
		$gateway_id = sanitize_text_field( (string) $gateway_id );

		return (string) preg_replace( '/[^A-Za-z0-9_-]/', '', $gateway_id );
	}

	/**
	 * شناسه ذخیره‌شده را با شناسه واقعی Gateway ثبت‌شده تطبیق می‌دهد.
	 *
	 * این مسیر برای سازگاری با نسخه 0.6.1 لازم است؛ در آن نسخه sanitize_key()
	 * شناسه‌های دارای حروف بزرگ مانند WC_Sep_Payment_Gateway را lowercase می‌کرد.
	 *
	 * @param mixed $gateway_id شناسه ذخیره‌شده یا ارسالی.
	 * @return string
	 */
	public function resolve_registered_gateway_id( $gateway_id ) {
		$gateway_id = $this->sanitize_gateway_id( $gateway_id );

		if ( '' === $gateway_id ) {
			return '';
		}

		$legacy_key = sanitize_key( $gateway_id );

		foreach ( $this->get_all_gateways() as $gateway ) {
			if ( ! $gateway instanceof WC_Payment_Gateway ) {
				continue;
			}

			$registered_id = $this->sanitize_gateway_id( $gateway->id );

			if ( $registered_id === $gateway_id || sanitize_key( $registered_id ) === $legacy_key ) {
				return $registered_id;
			}
		}

		return $gateway_id;
	}

	/**
	 * یک Callback را در بستر سبد موقت شامل فقط همین پروژه اجرا می‌کند و سپس سبد واقعی را بازمی‌گرداند.
	 *
	 * این روش باعث می‌شود بررسی is_available و process_payment درگاه به سایر اقلام سبد کاربر وابسته نباشد.
	 *
	 * @param WC_Product $product محصول پروژه.
	 * @param float      $amount  مبلغ مشارکت.
	 * @param callable   $callback Callback مورد نظر.
	 * @return mixed|WP_Error
	 */
	public function with_isolated_cart( WC_Product $product, $amount, callable $callback ) {
		if ( ! function_exists( 'WC' ) || ! class_exists( 'WC_Cart' ) ) {
			return new WP_Error( 'bap_cart_unavailable', 'زیرساخت سبد ووکامرس در دسترس نیست.' );
		}

		$original_cart       = WC()->cart;
		$session_snapshot    = $this->snapshot_cart_session();
		$persistent_snapshot = $this->snapshot_persistent_cart();
		$isolated_cart       = new WC_Cart();
		$product_copy = clone $product;
		$product_copy->set_price( $amount );
		$product_copy->set_tax_status( 'none' );

		WC()->cart = $isolated_cart;

		try {
			$cart_item_key = $isolated_cart->add_to_cart(
				$product->get_id(),
				1,
				0,
				array(),
				array(
					Bonyad_Alavi_WooCommerce_Participation::CART_AMOUNT_KEY => $amount,
					Bonyad_Alavi_WooCommerce_Participation::CART_MARKER_KEY => 1,
				)
			);

			if ( false === $cart_item_key ) {
				return new WP_Error( 'bap_isolated_cart_failed', 'آماده‌سازی پرداخت سریع انجام نشد.' );
			}

			if ( isset( $isolated_cart->cart_contents[ $cart_item_key ]['data'] ) ) {
				$isolated_cart->cart_contents[ $cart_item_key ]['data'] = $product_copy;
			}

			$isolated_cart->calculate_totals();

			return $callback( $isolated_cart );
		} catch ( Throwable $exception ) {
			return new WP_Error( 'bap_isolated_cart_exception', $exception->getMessage() );
		} finally {
			WC()->cart = $original_cart;
			$this->restore_cart_session( $session_snapshot );
			$this->restore_persistent_cart( $persistent_snapshot );
		}
	}

	/**
	 * بررسی می‌کند درگاه انتخاب‌شده برای همین محصول و مبلغ قابل استفاده باشد.
	 *
	 * @param WC_Payment_Gateway $gateway درگاه انتخابی.
	 * @param WC_Product         $product محصول پروژه.
	 * @param float              $amount مبلغ مشارکت.
	 * @return true|WP_Error
	 */
	public function validate_gateway_availability( WC_Payment_Gateway $gateway, WC_Product $product, $amount ) {
		$result = $this->with_isolated_cart(
			$product,
			$amount,
			static function () use ( $gateway ) {
				return $gateway->is_available();
			}
		);

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		if ( ! $result ) {
			return new WP_Error( 'bap_gateway_unavailable', 'درگاه پرداخت انتخاب‌شده برای این مبلغ یا شرایط سفارش در دسترس نیست.' );
		}

		if ( method_exists( $gateway, 'has_fields' ) && $gateway->has_fields() ) {
			return new WP_Error( 'bap_gateway_has_fields', 'درگاه انتخاب‌شده به فیلد پرداخت داخل صفحه نیاز دارد و برای پرداخت سریع مستقیم مناسب نیست.' );
		}

		return true;
	}


	/**
	 * مقادیر Session مرتبط با سبد واقعی کاربر را برای بازگردانی پس از پرداخت موقت نگه می‌دارد.
	 *
	 * @return array<string,mixed>
	 */
	private function snapshot_cart_session() {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return array();
		}

		$keys     = array( 'cart', 'cart_totals', 'applied_coupons', 'coupon_discount_totals', 'coupon_discount_tax_totals', 'removed_cart_contents' );
		$snapshot = array();

		foreach ( $keys as $key ) {
			$snapshot[ $key ] = WC()->session->get( $key, null );
		}

		return $snapshot;
	}

	/**
	 * Session سبد واقعی کاربر را پس از پایان Context موقت بازمی‌گرداند.
	 *
	 * @param array<string,mixed> $snapshot داده ذخیره‌شده Session.
	 * @return void
	 */
	private function restore_cart_session( array $snapshot ) {
		if ( ! function_exists( 'WC' ) || ! WC()->session ) {
			return;
		}

		foreach ( $snapshot as $key => $value ) {
			WC()->session->set( $key, $value );
		}
	}

	/**
	 * Persistent Cart کاربر لاگین‌شده را قبل از process_payment ذخیره می‌کند.
	 *
	 * @return array{user_id:int,key:string,exists:bool,value:mixed}
	 */
	private function snapshot_persistent_cart() {
		$user_id = get_current_user_id();
		$key     = '_woocommerce_persistent_cart_' . get_current_blog_id();

		if ( ! $user_id ) {
			return array( 'user_id' => 0, 'key' => $key, 'exists' => false, 'value' => null );
		}

		$exists = metadata_exists( 'user', $user_id, $key );

		return array(
			'user_id' => $user_id,
			'key'     => $key,
			'exists'  => $exists,
			'value'   => $exists ? get_user_meta( $user_id, $key, true ) : null,
		);
	}

	/**
	 * Persistent Cart واقعی را اگر درگاه آن را پاک کرده باشد بازسازی می‌کند.
	 *
	 * @param array{user_id:int,key:string,exists:bool,value:mixed} $snapshot داده Persistent Cart.
	 * @return void
	 */
	private function restore_persistent_cart( array $snapshot ) {
		if ( empty( $snapshot['user_id'] ) || empty( $snapshot['key'] ) ) {
			return;
		}

		if ( ! empty( $snapshot['exists'] ) ) {
			update_user_meta( $snapshot['user_id'], $snapshot['key'], $snapshot['value'] );
			return;
		}

		delete_user_meta( $snapshot['user_id'], $snapshot['key'] );
	}


	/**
	 * Gateway ثبت‌شده را با مقایسه شناسه واقعی و حساس به حروف پیدا می‌کند.
	 *
	 * @param string $gateway_id شناسه دقیق Gateway.
	 * @return WC_Payment_Gateway|null
	 */
	private function find_gateway_by_id( $gateway_id ) {
		$gateway_id = $this->sanitize_gateway_id( $gateway_id );

		foreach ( $this->get_all_gateways() as $gateway ) {
			if ( ! $gateway instanceof WC_Payment_Gateway ) {
				continue;
			}

			if ( $this->sanitize_gateway_id( $gateway->id ) === $gateway_id ) {
				return $gateway;
			}
		}

		return null;
	}

	/**
	 * همه نمونه‌های ثبت‌شده درگاه‌های WooCommerce را برمی‌گرداند.
	 *
	 * @return array<string,WC_Payment_Gateway>
	 */
	private function get_all_gateways() {
		if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) {
			return array();
		}

		$gateways = WC()->payment_gateways()->payment_gateways();

		return is_array( $gateways ) ? $gateways : array();
	}
}

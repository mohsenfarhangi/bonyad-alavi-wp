<?php
/**
 * سرویس Checkout سریع و مستقل مشارکت مردمی.
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * فاکتور Inline، اعتبارسنجی فیلدهای Checkout و ساخت سفارش مستقل مشارکت را مدیریت می‌کند.
 */
final class BA_Participation_Quick_Checkout_Service {

	/** @var BA_Participation_Payment_Gateway_Service سرویس درگاه پرداخت. */
	private $gateway_service;

	/**
	 * وابستگی سرویس درگاه را دریافت می‌کند.
	 *
	 * @param BA_Participation_Payment_Gateway_Service $gateway_service سرویس درگاه.
	 */
	public function __construct( BA_Participation_Payment_Gateway_Service $gateway_service ) {
		$this->gateway_service = $gateway_service;
	}

	/**
	 * HTML فاکتور و فیلدهای فعال Checkout را برای مرحله دوم فرم می‌سازد.
	 *
	 * @param int   $product_id شناسه پروژه.
	 * @param float $amount مبلغ مشارکت.
	 * @return array<string,mixed>|WP_Error
	 */
	public function prepare( $product_id, $amount ) {
		if ( function_exists( 'wc_maybe_define_constant' ) ) {
			wc_maybe_define_constant( 'WOOCOMMERCE_CHECKOUT', true );
		}

		$context = Bonyad_Alavi_WooCommerce_Participation::validate_project_amount( $product_id, $amount );
		if ( is_wp_error( $context ) ) {
			return $context;
		}

		$gateway = $this->get_selected_gateway_compat();
		if ( is_wp_error( $gateway ) ) {
			return $gateway;
		}

		$available = $this->gateway_service->validate_gateway_availability( $gateway, $context['product'], $context['amount'] );
		if ( is_wp_error( $available ) ) {
			return $available;
		}

		$checkout = WC_Checkout::instance();
		if ( ! is_user_logged_in() && $checkout->is_registration_required() ) {
			return new WP_Error( 'bap_registration_required', 'پرداخت مهمان در ووکامرس غیرفعال است. برای استفاده از پرداخت سریع ابتدا وارد حساب کاربری شوید.' );
		}

		$html = $this->gateway_service->with_isolated_cart(
			$context['product'],
			$context['amount'],
			function () use ( $context, $gateway ) {
				return $this->render_checkout_markup( $context['product'], $context['amount'], $gateway );
			}
		);

		if ( is_wp_error( $html ) ) {
			return $html;
		}

		return array(
			'html'          => $html,
			'amount'        => $context['amount'],
			'gateway_id'    => $gateway->id,
			'gateway_title' => wp_strip_all_tags( $gateway->get_title() ),
			'pay_label'     => 'پرداخت',
		);
	}

	/**
	 * اطلاعات فرم مرحله دوم را اعتبارسنجی، سفارش مستقل را ایجاد و پرداخت درگاه را اجرا می‌کند.
	 *
	 * @param array<string,mixed> $posted داده خام درخواست.
	 * @return array<string,mixed>|WP_Error
	 */
	public function process( array $posted ) {
		$product_id = isset( $posted['product_id'] ) ? absint( $posted['product_id'] ) : 0;
		$amount     = isset( $posted['amount'] ) ? Bonyad_Alavi_WooCommerce_Participation::normalize_amount( $posted['amount'] ) : 0;
		$context    = Bonyad_Alavi_WooCommerce_Participation::validate_project_amount( $product_id, $amount );

		if ( is_wp_error( $context ) ) {
			return $context;
		}

		$gateway = $this->get_selected_gateway_compat();
		if ( is_wp_error( $gateway ) ) {
			return $gateway;
		}

		$available = $this->gateway_service->validate_gateway_availability( $gateway, $context['product'], $context['amount'] );
		if ( is_wp_error( $available ) ) {
			return $available;
		}

		return $this->gateway_service->with_isolated_cart(
			$context['product'],
			$context['amount'],
			function () use ( $context, $gateway ) {
				return $this->process_in_isolated_context( $context['product'], $context['amount'], $gateway );
			}
		);
	}

	/**
	 * Markup فاکتور، Checkout Fields و قوانین را در Scope ویجت تولید می‌کند.
	 *
	 * @param WC_Product         $product محصول پروژه.
	 * @param float              $amount مبلغ مشارکت.
	 * @param WC_Payment_Gateway $gateway درگاه انتخابی.
	 * @return string
	 */
	private function render_checkout_markup( WC_Product $product, $amount, WC_Payment_Gateway $gateway ) {
		$checkout = WC_Checkout::instance();
		$fields   = $checkout->get_checkout_fields();

		ob_start();
		?>
		<div class="bap__quick-checkout-inner">
			<section class="bap__quick-invoice" aria-labelledby="bap-quick-invoice-title">
				<div class="bap__quick-section-head">
					<strong id="bap-quick-invoice-title">فاکتور مشارکت</strong>
					<button type="button" class="bap__quick-edit-amount">ویرایش مبلغ</button>
				</div>
				<dl class="bap__quick-invoice-list">
					<div><dt>پروژه</dt><dd><?php echo esc_html( $product->get_name() ); ?></dd></div>
					<div><dt>مبلغ مشارکت</dt><dd><?php echo wp_kses_post( wc_price( $amount ) ); ?></dd></div>
					<div><dt>درگاه پرداخت</dt><dd><?php echo esc_html( wp_strip_all_tags( $gateway->get_title() ) ); ?></dd></div>
					<div class="bap__quick-invoice-total"><dt>مبلغ قابل پرداخت</dt><dd><?php echo wp_kses_post( wc_price( $amount ) ); ?></dd></div>
				</dl>
			</section>

			<section class="bap__quick-fields" aria-labelledby="bap-quick-fields-title">
				<div class="bap__quick-section-head"><strong id="bap-quick-fields-title">اطلاعات پرداخت</strong></div>
				<?php $this->render_checkout_fields( $checkout, $fields, $product ); ?>
				<div class="bap__quick-terms"><?php wc_get_template( 'checkout/terms.php' ); ?></div>
			</section>
		</div>
		<?php

		return (string) ob_get_clean();
	}

	/**
	 * گروه‌های فعال فیلد Checkout را با API استاندارد woocommerce_form_field رندر می‌کند.
	 *
	 * @param WC_Checkout $checkout نمونه Checkout.
	 * @param array       $fields فیلدهای فیلترشده Checkout.
	 * @param WC_Product  $product محصول پروژه.
	 * @return void
	 */
	private function render_checkout_fields( WC_Checkout $checkout, array $fields, WC_Product $product ) {
		$section_titles = array(
			'billing'  => 'اطلاعات صورتحساب',
			'shipping' => 'اطلاعات دریافت‌کننده',
			'order'    => 'توضیحات سفارش',
		);

		foreach ( $fields as $section_key => $section_fields ) {
			if ( 'account' === $section_key || ( 'shipping' === $section_key && ! $product->needs_shipping() ) || ! is_array( $section_fields ) || empty( $section_fields ) ) {
				continue;
			}

			$section_title = isset( $section_titles[ $section_key ] ) ? $section_titles[ $section_key ] : 'اطلاعات تکمیلی';
			?>
			<div class="bap__quick-fieldset bap__quick-fieldset--<?php echo esc_attr( sanitize_html_class( $section_key ) ); ?>">
				<span class="bap__quick-fieldset-title"><?php echo esc_html( $section_title ); ?></span>
				<div class="bap__quick-field-grid">
					<?php
					foreach ( $section_fields as $key => $field ) {
						echo woocommerce_form_field( $key, $field, $checkout->get_value( $key ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					?>
				</div>
			</div>
			<?php
		}
	}

	/**
	 * اعتبارسنجی و پرداخت را داخل سبد موقت انجام می‌دهد تا سبد واقعی کاربر تحت تأثیر نباشد.
	 *
	 * @param WC_Product          $product محصول پروژه.
	 * @param float               $amount مبلغ مشارکت.
	 * @param WC_Payment_Gateway $gateway درگاه.
	 * @return array<string,mixed>|WP_Error
	 */
	private function process_in_isolated_context( WC_Product $product, $amount, WC_Payment_Gateway $gateway ) {
		if ( function_exists( 'wc_maybe_define_constant' ) ) {
			wc_maybe_define_constant( 'WOOCOMMERCE_CHECKOUT', true );
		}

		$checkout = WC_Checkout::instance();

		$_POST['payment_method'] = $gateway->id; // phpcs:ignore WordPress.Security.NonceVerification.Missing
		wc_clear_notices();
		do_action( 'woocommerce_before_checkout_process' );
		do_action( 'woocommerce_checkout_process' );

		$data   = $checkout->get_posted_data();
		$errors = $this->validate_checkout_data( $checkout, $data, $product );

		if ( ! $gateway->validate_fields() && ! wc_notice_count( 'error' ) ) {
			$errors->add( 'payment_validation', 'اطلاعات مورد نیاز درگاه پرداخت معتبر نیست.' );
		}

		do_action( 'woocommerce_after_checkout_validation', $data, $errors );
		$this->append_wc_notices_to_errors( $errors );

		if ( $errors->has_errors() ) {
			return new WP_Error( 'bap_checkout_validation', $this->errors_to_message( $errors ) );
		}

		$order = $this->create_order( $product, $amount, $gateway, $data );
		if ( is_wp_error( $order ) ) {
			return $order;
		}

		do_action( 'woocommerce_checkout_order_processed', $order->get_id(), $data, $order );

		if ( function_exists( 'WC' ) && WC()->session ) {
			WC()->session->set( 'chosen_payment_method', $gateway->id );
			WC()->session->set( 'order_awaiting_payment', $order->get_id() );
			if ( is_callable( array( WC()->session, 'save_data' ) ) ) {
				WC()->session->save_data();
			}
		}

		try {
			$result = $gateway->process_payment( $order->get_id() );
		} catch ( Throwable $exception ) {
			return new WP_Error( 'bap_gateway_exception', $exception->getMessage() );
		}

		$this->append_wc_notices_to_errors( $errors );
		if ( $errors->has_errors() ) {
			return new WP_Error( 'bap_gateway_validation', $this->errors_to_message( $errors ) );
		}

		if ( ! is_array( $result ) || 'success' !== ( $result['result'] ?? '' ) || empty( $result['redirect'] ) ) {
			return new WP_Error( 'bap_gateway_failed', 'درگاه پرداخت پاسخ معتبر برای انتقال به بانک برنگرداند.' );
		}

		$result['order_id'] = $order->get_id();
		$result             = apply_filters( 'woocommerce_payment_successful_result', $result, $order->get_id() );

		return array(
			'order_id' => $order->get_id(),
			'redirect' => esc_url_raw( $result['redirect'] ),
		);
	}

	/**
	 * Required و Validatorهای استاندارد فیلدهای Checkout را بررسی می‌کند.
	 *
	 * @param WC_Checkout $checkout نمونه Checkout.
	 * @param array       $data داده Sanitized ووکامرس.
	 * @param WC_Product  $product محصول پروژه.
	 * @return WP_Error
	 */
	private function validate_checkout_data( WC_Checkout $checkout, array $data, WC_Product $product ) {
		$errors = new WP_Error();
		$fields = $checkout->get_checkout_fields();

		foreach ( $fields as $section_key => $section_fields ) {
			if ( 'account' === $section_key || ( 'shipping' === $section_key && ! $product->needs_shipping() ) || ! is_array( $section_fields ) ) {
				continue;
			}

			foreach ( $section_fields as $key => $field ) {
				$value = isset( $data[ $key ] ) ? $data[ $key ] : '';
				$label = ! empty( $field['label'] ) ? wp_strip_all_tags( $field['label'] ) : $key;

				if ( ! empty( $field['required'] ) && '' === trim( (string) $value ) ) {
					$errors->add( $key . '_required', sprintf( 'فیلد «%s» الزامی است.', $label ) );
					continue;
				}

				if ( '' === trim( (string) $value ) ) {
					continue;
				}

				$customer_country_getter = 'shipping' === $section_key ? 'get_shipping_country' : 'get_billing_country';
				$field_country           = ! empty( $data[ $section_key . '_country' ] )
					? $data[ $section_key . '_country' ]
					: ( WC()->customer && is_callable( array( WC()->customer, $customer_country_getter ) ) ? WC()->customer->{$customer_country_getter}() : '' );

				if ( isset( $field['type'] ) && 'country' === $field['type'] && ! WC()->countries->country_exists( $value ) ) {
					$errors->add( $key . '_country', sprintf( 'مقدار «%s» معتبر نیست.', $label ) );
				}

				$validators = ! empty( $field['validate'] ) && is_array( $field['validate'] ) ? $field['validate'] : array();
				foreach ( $validators as $validator ) {
					if ( 'email' === $validator && ! is_email( $value ) ) {
						$errors->add( $key . '_email', sprintf( 'مقدار «%s» یک ایمیل معتبر نیست.', $label ) );
					}
					if ( 'phone' === $validator && class_exists( 'WC_Validation' ) && ! WC_Validation::is_phone( $value, $field_country ) ) {
						$errors->add( $key . '_phone', sprintf( 'مقدار «%s» یک شماره تماس معتبر نیست.', $label ) );
					}
					if ( 'postcode' === $validator && class_exists( 'WC_Validation' ) && ! WC_Validation::is_postcode( $value, $field_country ) ) {
						$errors->add( $key . '_postcode', sprintf( 'مقدار «%s» معتبر نیست.', $label ) );
					}
					if ( 'state' === $validator && $field_country ) {
						$states = WC()->countries->get_states( $field_country );
						if ( is_array( $states ) && ! empty( $states ) && ! isset( $states[ $value ] ) ) {
							$errors->add( $key . '_state', sprintf( 'مقدار «%s» معتبر نیست.', $label ) );
						}
					}
				}
			}
		}

		if ( function_exists( 'wc_terms_and_conditions_checkbox_enabled' ) && wc_terms_and_conditions_checkbox_enabled() && empty( $_POST['terms'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$errors->add( 'terms_required', 'برای ادامه پرداخت باید قوانین و مقررات را تأیید کنید.' );
		}

		return $errors;
	}

	/**
	 * سفارش مستقل را فقط با همین پروژه ایجاد می‌کند و اطلاعات استاندارد Checkout را روی سفارش می‌نشاند.
	 *
	 * @param WC_Product          $product محصول پروژه.
	 * @param float               $amount مبلغ مشارکت.
	 * @param WC_Payment_Gateway $gateway درگاه پرداخت.
	 * @param array               $data داده Checkout.
	 * @return WC_Order|WP_Error
	 */
	private function create_order( WC_Product $product, $amount, WC_Payment_Gateway $gateway, array $data ) {
		try {
			$order = wc_create_order(
				array(
					'customer_id' => apply_filters( 'woocommerce_checkout_customer_id', get_current_user_id() ),
					'created_via' => 'bap_quick_checkout',
				)
			);

			if ( is_wp_error( $order ) ) {
				return $order;
			}

			foreach ( $data as $key => $value ) {
				$setter = 'set_' . $key;
				if ( is_callable( array( $order, $setter ) ) && is_scalar( $value ) ) {
					$order->{$setter}( $value );
					continue;
				}

				if ( is_scalar( $value ) && ( 0 === strpos( $key, 'billing_' ) || 0 === strpos( $key, 'shipping_' ) ) ) {
					$order->update_meta_data( '_' . $key, $value );
				}
			}

			if ( isset( $data['order_comments'] ) ) {
				$order->set_customer_note( (string) $data['order_comments'] );
			}

			$order->set_payment_method( $gateway );
			$order->set_currency( get_woocommerce_currency() );
			$order->set_prices_include_tax( 'yes' === get_option( 'woocommerce_prices_include_tax' ) );
			$order->set_customer_ip_address( WC_Geolocation::get_ip_address() );
			$order->set_customer_user_agent( wc_get_user_agent() );

			$item_id = $order->add_product(
				$product,
				1,
				array(
					'subtotal' => $amount,
					'total'    => $amount,
				)
			);

			$item = $order->get_item( $item_id );
			if ( $item instanceof WC_Order_Item_Product ) {
				$item->add_meta_data( '_bap_participation_amount', $amount, true );
				$item->add_meta_data( '_bap_goal_amount', Bonyad_Alavi_WooCommerce_Participation::get_goal_amount( $product->get_id() ), true );
				$item->save();
			}

			$order->calculate_totals( false );
			do_action( 'woocommerce_checkout_create_order', $order, $data );
			$order->save();
			do_action( 'woocommerce_checkout_update_order_meta', $order->get_id(), $data );
			do_action( 'woocommerce_checkout_order_created', $order );

			return $order;
		} catch ( Throwable $exception ) {
			return new WP_Error( 'bap_order_create_failed', $exception->getMessage() );
		}
	}

	/**
	 * Noticeهای خطای WooCommerce را به WP_Error منتقل می‌کند.
	 *
	 * @param WP_Error $errors مجموعه خطاها.
	 * @return void
	 */
	private function append_wc_notices_to_errors( WP_Error $errors ) {
		if ( ! function_exists( 'wc_get_notices' ) ) {
			return;
		}

		foreach ( wc_get_notices( 'error' ) as $notice ) {
			$message = is_array( $notice ) && isset( $notice['notice'] ) ? wp_strip_all_tags( $notice['notice'] ) : '';
			if ( $message ) {
				$errors->add( 'woocommerce_notice', $message );
			}
		}

		wc_clear_notices();
	}

	/**
	 * چند خطای اعتبارسنجی را به یک پیام خوانا برای پاسخ AJAX تبدیل می‌کند.
	 *
	 * @param WP_Error $errors خطاها.
	 * @return string
	 */
	private function errors_to_message( WP_Error $errors ) {
		$messages = array_values( array_unique( array_filter( array_map( 'wp_strip_all_tags', $errors->get_error_messages() ) ) ) );

		return implode( ' ', $messages );
	}

	/**
	 * درگاه انتخاب‌شده را مستقل از نسخه Service و با حفظ Case شناسه پیدا می‌کند.
	 *
	 * این مسیر دفاعی برای نصب‌های ترکیبی Patch یا OPcache قدیمی است؛ نسخه‌های
	 * قدیمی Service ممکن است شناسه‌هایی مانند WC_Sep_Payment_Gateway را با
	 * sanitize_key() به lowercase تبدیل کنند. این متد Option را مستقیم می‌خواند
	 * و آن را با مقدار واقعی $gateway->id تطبیق می‌دهد.
	 *
	 * @return WC_Payment_Gateway|WP_Error
	 */
	private function get_selected_gateway_compat() {
		$options    = get_option( BA_Participation_Payment_Gateway_Service::OPTION_NAME, array() );
		$gateway_id = isset( $options[ BA_Participation_Payment_Gateway_Service::OPTION_KEY ] )
			? $this->sanitize_gateway_id_compat( $options[ BA_Participation_Payment_Gateway_Service::OPTION_KEY ] )
			: '';

		if ( '' === $gateway_id ) {
			return new WP_Error( 'bap_gateway_not_configured', 'درگاه پرداخت مشارکت در تنظیمات بنیاد علوی انتخاب نشده است.' );
		}

		if ( ! function_exists( 'WC' ) || ! WC()->payment_gateways() ) {
			return new WP_Error( 'bap_gateway_missing', 'درگاه‌های پرداخت WooCommerce در دسترس نیستند.' );
		}

		$gateways   = WC()->payment_gateways()->payment_gateways();
		$legacy_key = strtolower( $gateway_id );

		foreach ( is_array( $gateways ) ? $gateways : array() as $gateway ) {
			if ( ! $gateway instanceof WC_Payment_Gateway ) {
				continue;
			}

			$registered_id = $this->sanitize_gateway_id_compat( $gateway->id );

			if ( $registered_id !== $gateway_id && strtolower( $registered_id ) !== $legacy_key ) {
				continue;
			}

			if ( 'yes' !== $gateway->enabled ) {
				return new WP_Error( 'bap_gateway_disabled', 'درگاه پرداخت انتخاب‌شده غیرفعال است.' );
			}

			return $gateway;
		}

		return new WP_Error( 'bap_gateway_missing', 'درگاه پرداخت انتخاب‌شده در WooCommerce در دسترس نیست.' );
	}

	/**
	 * شناسه Gateway را بدون تغییر حروف بزرگ و کوچک پاک‌سازی می‌کند.
	 *
	 * @param mixed $gateway_id شناسه خام درگاه.
	 * @return string
	 */
	private function sanitize_gateway_id_compat( $gateway_id ) {
		$gateway_id = sanitize_text_field( (string) $gateway_id );

		return (string) preg_replace( '/[^A-Za-z0-9_-]/', '', $gateway_id );
	}

}

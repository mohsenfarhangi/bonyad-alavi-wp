<?php
/**
 * کنترلر ذخیره AJAX تنظیمات بنیاد علوی.
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * درخواست‌های ذخیره تب‌های تنظیمات بنیاد علوی را به یک مسیر امن و مشترک هدایت می‌کند.
 */
final class BA_Settings_Ajax_Controller {

	const AJAX_ACTION  = 'ba_save_settings_ajax';
	const NONCE_ACTION = 'ba_save_settings_ajax';

	/**
	 * هوک AJAX ذخیره تنظیمات را ثبت می‌کند.
	 *
	 * @return void
	 */
	public static function init() {
		add_action( 'wp_ajax_' . self::AJAX_ACTION, array( __CLASS__, 'save' ) );
	}

	/**
	 * درخواست ذخیره را اعتبارسنجی و بر اساس نوع تب به ذخیره‌کننده مناسب واگذار می‌کند.
	 *
	 * @return void
	 */
	public static function save() {
		if ( ! check_ajax_referer( self::NONCE_ACTION, '_ajax_nonce', false ) ) {
			wp_send_json_error(
				array( 'message' => 'نشست ذخیره منقضی شده است. صفحه را تازه‌سازی و دوباره تلاش کنید.' ),
				403
			);
		}

		$tab_id = isset( $_POST['ba_settings_tab'] ) ? sanitize_key( wp_unslash( $_POST['ba_settings_tab'] ) ) : '';
		$tabs   = BA_Settings_Page::get_tabs();

		if ( '' === $tab_id || empty( $tabs[ $tab_id ] ) || ! is_array( $tabs[ $tab_id ] ) ) {
			wp_send_json_error( array( 'message' => 'تب تنظیمات معتبر نیست.' ), 400 );
		}

		$tab        = $tabs[ $tab_id ];
		$capability = ! empty( $tab['capability'] ) ? sanitize_key( $tab['capability'] ) : '';

		if ( '' === $capability || ! current_user_can( $capability ) ) {
			wp_send_json_error( array( 'message' => 'شما اجازه ذخیره این بخش را ندارید.' ), 403 );
		}

		if ( ! empty( $tab['ajax_save_callback'] ) && is_callable( $tab['ajax_save_callback'] ) ) {
			$result = call_user_func( $tab['ajax_save_callback'], wp_unslash( $_POST ) );
			self::send_result( $result, $tab_id );
		}

		$result = self::save_registered_option( $tab );
		self::send_result( $result, $tab_id );
	}

	/**
	 * یک Option ثبت‌شده در Settings API را با همان Sanitize Callback وردپرس ذخیره می‌کند.
	 *
	 * @param array $tab پیکربندی تب.
	 * @return true|WP_Error
	 */
	private static function save_registered_option( array $tab ) {
		if ( empty( $tab['option_name'] ) || empty( $tab['option_group'] ) ) {
			return new WP_Error( 'ba_settings_not_saveable', 'برای این تب مسیر ذخیره تعریف نشده است.' );
		}

		$option_name  = sanitize_key( $tab['option_name'] );
		$option_group = sanitize_key( $tab['option_group'] );
		$form_nonce   = isset( $_POST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ) : '';

		if ( ! wp_verify_nonce( $form_nonce, $option_group . '-options' ) ) {
			return new WP_Error( 'ba_settings_invalid_form_nonce', 'اعتبار فرم منقضی شده است. صفحه را تازه‌سازی کنید.' );
		}

		$value = isset( $_POST[ $option_name ] ) ? wp_unslash( $_POST[ $option_name ] ) : array();

		/*
		 * update_option() مقدار را از sanitize_option() عبور می‌دهد و در نتیجه
		 * sanitize_callback ثبت‌شده توسط register_setting نیز اجرا می‌شود.
		 */
		update_option( $option_name, $value );

		return true;
	}

	/**
	 * نتیجه ذخیره را با قرارداد JSON یکسان به رابط مدیریت برمی‌گرداند.
	 *
	 * @param mixed  $result نتیجه ذخیره.
	 * @param string $tab_id شناسه تب ذخیره‌شده.
	 * @return void
	 */
	private static function send_result( $result, $tab_id ) {
		if ( is_wp_error( $result ) ) {
			wp_send_json_error(
				array( 'message' => $result->get_error_message() ),
				400
			);
		}

		do_action( 'ba_settings_ajax_saved', $tab_id );

		wp_send_json_success(
			array(
				'message' => 'تنظیمات با موفقیت ذخیره شد.',
				'tab'     => $tab_id,
			)
		);
	}
}

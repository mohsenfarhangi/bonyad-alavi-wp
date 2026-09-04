<?php
/**
 * سرویس ذخیره دسترسی تب‌های تنظیمات بنیاد علوی.
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * مسئول اعمال Capabilityهای تب‌های تنظیمات روی نقش‌های وردپرس است.
 */
final class BA_Settings_Access_Service {

	/**
	 * داده فرم دسترسی را اعتبارسنجی و روی نقش‌ها اعمال می‌کند.
	 *
	 * @param array $request داده ارسال‌شده فرم.
	 * @return true|WP_Error
	 */
	public static function save_from_request( array $request ) {
		if ( ! current_user_can( BA_SETTINGS_ACCESS_CAPABILITY ) ) {
			return new WP_Error( 'ba_access_forbidden', 'شما اجازه انجام این عملیات را ندارید.' );
		}

		$capabilities = ba_get_manageable_settings_capabilities();
		$allowed_caps = array_keys( $capabilities );
		$submitted    = isset( $request['role_caps'] ) && is_array( $request['role_caps'] )
			? $request['role_caps']
			: array();

		global $wp_roles;

		if ( ! isset( $wp_roles ) || ! ( $wp_roles instanceof WP_Roles ) ) {
			$wp_roles = wp_roles(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
		}

		foreach ( array_keys( $wp_roles->roles ) as $role_slug ) {
			$role_slug = sanitize_key( $role_slug );
			$role      = get_role( $role_slug );

			if ( ! $role ) {
				continue;
			}

			if ( 'administrator' === $role_slug ) {
				foreach ( $allowed_caps as $capability ) {
					$role->add_cap( $capability );
				}
				continue;
			}

			$role_submitted_caps = isset( $submitted[ $role_slug ] ) && is_array( $submitted[ $role_slug ] )
				? array_map( 'sanitize_key', $submitted[ $role_slug ] )
				: array();
			$role_submitted_caps = array_intersect( $role_submitted_caps, $allowed_caps );

			foreach ( $allowed_caps as $capability ) {
				if ( in_array( $capability, $role_submitted_caps, true ) ) {
					$role->add_cap( $capability );
				} else {
					$role->remove_cap( $capability );
				}
			}
		}

		return true;
	}
}

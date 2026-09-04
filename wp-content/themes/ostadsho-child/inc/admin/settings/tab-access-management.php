<?php
/**
 * Settings access management tab.
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Capability required to manage Bonyad Alavi settings access.
 */
define( 'BA_SETTINGS_ACCESS_CAPABILITY', 'ba_manage_settings_access' );

/**
 * Register access management tab.
 *
 * @param array $tabs Existing tabs.
 * @return array
 */
function ba_register_access_management_settings_tab( $tabs ) {
	$tabs['access-management'] = array(
		'title'           => 'مدیریت دسترسی',
		'capability'      => BA_SETTINGS_ACCESS_CAPABILITY,
		'page_slug'       => 'ba-settings-access-management',
		'render_callback'    => 'ba_render_settings_access_management_tab',
		'ajax_save_callback' => array( 'BA_Settings_Access_Service', 'save_from_request' ),
		'save_label'         => 'ذخیره دسترسی‌ها',
	);

	return $tabs;
}
add_filter( 'ba_settings_tabs', 'ba_register_access_management_settings_tab', 100 );

/**
 * Get settings capabilities that can be assigned to roles.
 *
 * The list is generated from the dynamic settings tab registry, therefore
 * capabilities belonging to future tabs automatically appear here.
 *
 * @return array<string,string> Capability => human-readable tab title.
 */
function ba_get_manageable_settings_capabilities() {
	$capabilities = array();
	$tabs         = apply_filters( 'ba_settings_tabs', array() );

	if ( ! is_array( $tabs ) ) {
		return $capabilities;
	}

	foreach ( $tabs as $tab ) {
		if ( empty( $tab['capability'] ) ) {
			continue;
		}

		$capability = sanitize_key( $tab['capability'] );
		$title      = ! empty( $tab['title'] ) ? sanitize_text_field( $tab['title'] ) : $capability;

		if ( ! isset( $capabilities[ $capability ] ) ) {
			$capabilities[ $capability ] = $title;
		}
	}

	return $capabilities;
}

/**
 * Render access management UI.
 *
 * @return void
 */
function ba_render_settings_access_management_tab( $tab = array() ) {
	if ( ! current_user_can( BA_SETTINGS_ACCESS_CAPABILITY ) ) {
		wp_die( esc_html__( 'شما اجازه مدیریت دسترسی‌های تنظیمات بنیاد علوی را ندارید.', 'ostadsho-child' ) );
	}

	global $wp_roles;

	if ( ! isset( $wp_roles ) || ! ( $wp_roles instanceof WP_Roles ) ) {
		$wp_roles = wp_roles(); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited
	}

	$roles        = $wp_roles->roles;
	$capabilities = ba_get_manageable_settings_capabilities();
	$updated      = isset( $_GET['ba-access-updated'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['ba-access-updated'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended

	if ( $updated ) {
		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html__( 'دسترسی نقش‌ها با موفقیت ذخیره شد.', 'ostadsho-child' )
		);
	}
	?>
	<div class="ba-access-manager">
		<p class="ba-settings-description">
			<?php esc_html_e( 'در این بخش می‌توانید مشخص کنید هر نقش وردپرس به کدام تب از تنظیمات بنیاد علوی دسترسی داشته باشد. این دسترسی‌ها به‌صورت Capability استاندارد وردپرس روی خود Role ذخیره می‌شوند.', 'ostadsho-child' ); ?>
		</p>

		<div class="ba-access-manager__note">
			<span class="dashicons dashicons-shield" aria-hidden="true"></span>
			<p>
				<?php esc_html_e( 'نقش مدیر کل (Administrator) برای جلوگیری از قفل‌شدن پنل همیشه دسترسی کامل به تنظیمات بنیاد علوی دارد و از این صفحه قابل محدودکردن نیست.', 'ostadsho-child' ); ?>
			</p>
		</div>

		<?php if ( empty( $capabilities ) ) : ?>
			<p><?php esc_html_e( 'هیچ Capability قابل مدیریتی برای تب‌های تنظیمات ثبت نشده است.', 'ostadsho-child' ); ?></p>
		<?php else : ?>
			<form id="<?php echo esc_attr( isset( $tab['form_id'] ) ? $tab['form_id'] : 'ba-settings-form-access-management' ); ?>" class="ba-settings-form ba-access-manager__form" method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" data-ba-settings-form>
				<input type="hidden" name="ba_settings_tab" value="access-management" />
				<input type="hidden" name="action" value="ba_save_settings_access" />
				<?php wp_nonce_field( 'ba_save_settings_access', 'ba_settings_access_nonce' ); ?>

				<div class="ba-access-table-wrap">
					<table class="widefat striped ba-access-table">
						<thead>
							<tr>
								<th scope="col" class="ba-access-table__role"><?php esc_html_e( 'نقش کاربری', 'ostadsho-child' ); ?></th>
								<?php foreach ( $capabilities as $capability => $title ) : ?>
									<th scope="col">
										<span class="ba-access-table__tab-title"><?php echo esc_html( $title ); ?></span>
										<code><?php echo esc_html( $capability ); ?></code>
									</th>
								<?php endforeach; ?>
							</tr>
						</thead>
						<tbody>
							<?php foreach ( $roles as $role_slug => $role_data ) : ?>
								<?php
								$role_slug        = sanitize_key( $role_slug );
								$role             = get_role( $role_slug );
								$is_administrator = 'administrator' === $role_slug;

								if ( ! $role ) {
									continue;
								}
								?>
								<tr>
									<th scope="row" class="ba-access-table__role">
										<strong><?php echo esc_html( translate_user_role( $role_data['name'] ) ); ?></strong>
										<code><?php echo esc_html( $role_slug ); ?></code>
									</th>

									<?php foreach ( $capabilities as $capability => $title ) : ?>
										<?php $has_capability = $is_administrator || $role->has_cap( $capability ); ?>
										<td class="ba-access-table__checkbox">
											<label>
												<input
													type="checkbox"
													name="role_caps[<?php echo esc_attr( $role_slug ); ?>][]"
													value="<?php echo esc_attr( $capability ); ?>"
													<?php checked( $has_capability ); ?>
													<?php disabled( $is_administrator ); ?>
												/>
												<span class="screen-reader-text">
													<?php
													printf(
														/* translators: 1: role name, 2: settings tab name. */
														esc_html__( 'دسترسی نقش %1$s به %2$s', 'ostadsho-child' ),
														esc_html( translate_user_role( $role_data['name'] ) ),
														esc_html( $title )
													);
													?>
												</span>
											</label>
										</td>
									<?php endforeach; ?>
								</tr>
							<?php endforeach; ?>
						</tbody>
					</table>
				</div>

			</form>
		<?php endif; ?>
	</div>
	<?php
}

/**
 * Persist role capabilities selected on the access management tab.
 *
 * @return void
 */
function ba_save_settings_access() {
	if ( ! current_user_can( BA_SETTINGS_ACCESS_CAPABILITY ) ) {
		wp_die( esc_html__( 'شما اجازه انجام این عملیات را ندارید.', 'ostadsho-child' ), '', array( 'response' => 403 ) );
	}

	check_admin_referer( 'ba_save_settings_access', 'ba_settings_access_nonce' );

	$result = BA_Settings_Access_Service::save_from_request( wp_unslash( $_POST ) );

	if ( is_wp_error( $result ) ) {
		wp_die( esc_html( $result->get_error_message() ), '', array( 'response' => 400 ) );
	}

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'              => 'ba-settings',
				'tab'               => 'access-management',
				'ba-access-updated' => '1',
			),
			admin_url( 'admin.php' )
		)
	);
	exit;
}
add_action( 'admin_post_ba_save_settings_access', 'ba_save_settings_access' );

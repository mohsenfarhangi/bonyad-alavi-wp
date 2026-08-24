<?php
/**
 * Bonyad Alavi dynamic admin settings page.
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'BA_Settings_Page' ) ) {
	final class BA_Settings_Page {

		const PAGE_SLUG = 'ba-settings';
		const MENU_CAPABILITY = 'read';
		const CAPS_VERSION_OPTION = 'ba_settings_capabilities_signature';

		/**
		 * Bootstrap hooks.
		 *
		 * @return void
		 */
		public static function init() {
			add_action( 'admin_init', array( __CLASS__, 'register_settings' ) );
			add_action( 'admin_init', array( __CLASS__, 'sync_administrator_capabilities' ), 20 );
			// Register late so role/menu customizer plugins have already initialized their admin-menu rules.
			add_action( 'admin_menu', array( __CLASS__, 'register_menu' ), 99 );
			add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		}

		/**
		 * Get all registered settings tabs.
		 *
		 * Add future tabs through the `ba_settings_tabs` filter.
		 *
		 * Required tab arguments:
		 * - title
		 * - capability
		 * - option_group
		 * - option_name
		 * - page_slug
		 * - register_callback (optional callable)
		 * - render_callback (optional callable for custom tab UI)
		 *
		 * @return array
		 */
		public static function get_tabs() {
			$tabs = apply_filters( 'ba_settings_tabs', array() );

			if ( ! is_array( $tabs ) ) {
				return array();
			}

			return $tabs;
		}

		/**
		 * Get tabs the current user is allowed to manage.
		 *
		 * @return array
		 */
		private static function get_accessible_tabs() {
			$accessible = array();

			foreach ( self::get_tabs() as $tab_id => $tab ) {
				if ( empty( $tab['capability'] ) ) {
					continue;
				}

				if ( current_user_can( $tab['capability'] ) ) {
					$accessible[ $tab_id ] = $tab;
				}
			}

			return $accessible;
		}

		/**
		 * Register WordPress Settings API data for every tab.
		 *
		 * @return void
		 */
		public static function register_settings() {
			foreach ( self::get_tabs() as $tab ) {
				if ( empty( $tab['option_group'] ) || empty( $tab['capability'] ) ) {
					continue;
				}

				$option_group = sanitize_key( $tab['option_group'] );
				$capability   = sanitize_key( $tab['capability'] );

				// Settings API normally requires manage_options. Each tab gets its own custom capability instead.
				add_filter(
					'option_page_capability_' . $option_group,
					static function () use ( $capability ) {
						return $capability;
					}
				);

				if ( ! empty( $tab['register_callback'] ) && is_callable( $tab['register_callback'] ) ) {
					call_user_func( $tab['register_callback'], $tab );
				}
			}
		}

		/**
		 * Ensure the administrator role owns all settings capabilities by default.
		 *
		 * Capabilities remain normal WordPress primitive capabilities, so role/capability
		 * manager plugins can add/remove them for any role.
		 *
		 * @return void
		 */
		public static function sync_administrator_capabilities() {
			$capabilities = array();

			foreach ( self::get_tabs() as $tab ) {
				if ( ! empty( $tab['capability'] ) ) {
					$capabilities[] = sanitize_key( $tab['capability'] );
				}
			}

			$capabilities = array_values( array_unique( array_filter( $capabilities ) ) );
			sort( $capabilities );

			if ( empty( $capabilities ) ) {
				return;
			}

			$signature     = md5( wp_json_encode( $capabilities ) );
			$administrator = get_role( 'administrator' );
			$changed       = false;

			if ( $administrator ) {
				foreach ( $capabilities as $capability ) {
					if ( ! $administrator->has_cap( $capability ) ) {
						$administrator->add_cap( $capability );
						$changed = true;
					}
				}

				// Refresh current-user role caps so a newly-added/restored capability is available on this request too.
				if ( $changed ) {
					$current_user = wp_get_current_user();
					if ( $current_user && $current_user->exists() ) {
						$current_user->get_role_caps();
					}
				}
			}

			if ( get_option( self::CAPS_VERSION_OPTION ) !== $signature ) {
				update_option( self::CAPS_VERSION_OPTION, $signature, false );
			}
		}

		/**
		 * Register the top-level settings page for every logged-in admin user.
		 *
		 * The menu uses WordPress core `read` capability only for visibility/routing.
		 * Actual access to settings remains isolated per tab through each tab's
		 * dedicated capability in get_accessible_tabs() and render_page().
		 *
		 * @return void
		 */
		public static function register_menu() {
			add_menu_page(
				'تنظیمات بنیاد علوی',
				'تنظیمات بنیاد علوی',
				self::MENU_CAPABILITY,
				self::PAGE_SLUG,
				array( __CLASS__, 'render_page' ),
				'dashicons-admin-generic',
				58
			);
		}

		/**
		 * Load settings page assets.
		 *
		 * @param string $hook_suffix Current admin hook suffix.
		 * @return void
		 */
		public static function enqueue_assets( $hook_suffix ) {
			if ( empty( $_GET['page'] ) || self::PAGE_SLUG !== sanitize_key( wp_unslash( $_GET['page'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				return;
			}

			/*
			 * Users without a settings-tab capability can still open the page and see
			 * the no-access notice. They do not need media/sortable JavaScript.
			 */
			if ( empty( self::get_accessible_tabs() ) ) {
				$version = wp_get_theme()->get( 'Version' );

				wp_enqueue_style(
					'ba-admin-settings',
					get_stylesheet_directory_uri() . '/assets/css/ba-admin-settings.css',
					array(),
					$version
				);

				return;
			}

			wp_enqueue_media();
			wp_enqueue_script( 'jquery-ui-sortable' );

			$version = wp_get_theme()->get( 'Version' );

			wp_enqueue_style(
				'ba-admin-settings',
				get_stylesheet_directory_uri() . '/assets/css/ba-admin-settings.css',
				array(),
				$version
			);

			wp_enqueue_script(
				'ba-admin-settings',
				get_stylesheet_directory_uri() . '/assets/js/ba-admin-settings.js',
				array( 'jquery', 'jquery-ui-sortable' ),
				$version,
				true
			);

			wp_localize_script(
				'ba-admin-settings',
				'BASettings',
				array(
					'mediaTitle'      => 'انتخاب تصاویر اسلایدر',
					'mediaButton'     => 'استفاده از تصاویر انتخاب‌شده',
					'removeImage'     => 'حذف تصویر',
					'emptyGallery'    => 'هنوز تصویری برای اسلایدر انتخاب نشده است.',
				)
			);
		}

		/**
		 * Resolve active tab from request and permissions.
		 *
		 * @param array $tabs Accessible tabs.
		 * @return string
		 */
		private static function get_active_tab( $tabs ) {
			$requested_tab = isset( $_GET['tab'] ) ? sanitize_key( wp_unslash( $_GET['tab'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

			if ( $requested_tab && isset( $tabs[ $requested_tab ] ) ) {
				return $requested_tab;
			}

			return (string) array_key_first( $tabs );
		}

		/**
		 * Render settings screen and dynamic tabs.
		 *
		 * @return void
		 */
		public static function render_page() {
			$tabs = self::get_accessible_tabs();

			if ( empty( $tabs ) ) {
				?>
				<div class="wrap ba-settings-wrap">
					<h1 class="ba-settings-title">تنظیمات بنیاد علوی</h1>

					<div class="notice notice-warning inline">
						<p><?php esc_html_e( 'شما در حال حاضر به هیچ‌یک از بخش‌های تنظیمات بنیاد علوی دسترسی ندارید.', 'ostadsho-child' ); ?></p>
					</div>
				</div>
				<?php
				return;
			}

			$active_tab = self::get_active_tab( $tabs );
			$tab        = $tabs[ $active_tab ];
			?>
			<div class="wrap ba-settings-wrap">
				<h1 class="ba-settings-title">تنظیمات بنیاد علوی</h1>

				<nav class="nav-tab-wrapper ba-settings-tabs" aria-label="تب‌های تنظیمات">
					<?php foreach ( $tabs as $tab_id => $registered_tab ) : ?>
						<?php
						$url = add_query_arg(
							array(
								'page' => self::PAGE_SLUG,
								'tab'  => $tab_id,
							),
							admin_url( 'admin.php' )
						);
						?>
						<a class="nav-tab <?php echo $active_tab === $tab_id ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( $url ); ?>">
							<?php echo esc_html( $registered_tab['title'] ); ?>
						</a>
					<?php endforeach; ?>
				</nav>

				<?php settings_errors(); ?>

				<div class="ba-settings-card">
					<?php if ( ! empty( $tab['render_callback'] ) && is_callable( $tab['render_callback'] ) ) : ?>
						<?php call_user_func( $tab['render_callback'], $tab ); ?>
					<?php else : ?>
						<form method="post" action="options.php">
							<?php
							settings_fields( $tab['option_group'] );
							do_settings_sections( $tab['page_slug'] );
							submit_button( 'ذخیره تنظیمات' );
							?>
						</form>
					<?php endif; ?>
				</div>
			</div>
			<?php
		}
	}
}

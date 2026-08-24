<?php
/**
 * Elementor widgets bootstrap for the Bonyad Alavi child theme.
 *
 * Copy this file to: /wp-content/themes/YOUR-CHILD-THEME/inc/elementor-widgets.php
 * Then require it from the child-theme functions.php.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolve an asset version from filemtime so browser caches are invalidated after deployment.
 */
function bonyad_alavi_widget_asset_version( $relative_path ) {
	$file = trailingslashit( get_stylesheet_directory() ) . ltrim( $relative_path, '/' );

	return file_exists( $file ) ? (string) filemtime( $file ) : '1.0.0';
}

/**
 * Register the widget stylesheet. It is not enqueued globally; Elementor enqueues it only when
 * the widget exists on the rendered page/editor preview through get_style_depends().
 */
function bonyad_alavi_register_participation_widget_style() {
	$handle = 'bonyad-alavi-participation-widget';

	if ( wp_style_is( $handle, 'registered' ) ) {
		return;
	}

	wp_register_style(
		$handle,
		trailingslashit( get_stylesheet_directory_uri() ) . 'assets/css/bonyad-alavi-participation-widget.css',
		array(),
		bonyad_alavi_widget_asset_version( 'assets/css/bonyad-alavi-participation-widget.css' )
	);
}

/**
 * Register the widget script. Elementor frontend is declared as a dependency because the script
 * uses Elementor's frontend handler API and must also re-initialize inside the editor preview.
 */
function bonyad_alavi_register_participation_widget_script() {
	$handle = 'bonyad-alavi-participation-widget';

	if ( wp_script_is( $handle, 'registered' ) ) {
		return;
	}

	wp_register_script(
		$handle,
		trailingslashit( get_stylesheet_directory_uri() ) . 'assets/js/bonyad-alavi-participation-widget.js',
		array( 'elementor-frontend' ),
		bonyad_alavi_widget_asset_version( 'assets/js/bonyad-alavi-participation-widget.js' ),
		true
	);
}

function bonyad_alavi_register_participation_widget_assets() {
	bonyad_alavi_register_participation_widget_style();
	bonyad_alavi_register_participation_widget_script();
}

add_action( 'wp_enqueue_scripts', 'bonyad_alavi_register_participation_widget_assets', 5 );
add_action( 'elementor/frontend/after_register_styles', 'bonyad_alavi_register_participation_widget_style' );
add_action( 'elementor/frontend/after_register_scripts', 'bonyad_alavi_register_participation_widget_script' );

/**
 * Add a dedicated Elementor category for child-theme widgets.
 */
function bonyad_alavi_register_elementor_category( $elements_manager ) {
	$elements_manager->add_category(
		'bonyad-alavi',
		array(
			'title' => esc_html__( 'بنیاد علوی', 'bonyad-alavi-child' ),
			'icon'  => 'fa fa-plug',
		)
	);
}
add_action( 'elementor/elements/categories_registered', 'bonyad_alavi_register_elementor_category' );

/**
 * Register the custom widget after Elementor has initialized its widget manager.
 */
function bonyad_alavi_register_participation_widget( $widgets_manager ) {
	require_once get_stylesheet_directory() . '/inc/elementor/widgets/class-bonyad-alavi-participation-widget.php';

	$widgets_manager->register( new \Bonyad_Alavi_Participation_Widget() );
}
add_action( 'elementor/widgets/register', 'bonyad_alavi_register_participation_widget' );

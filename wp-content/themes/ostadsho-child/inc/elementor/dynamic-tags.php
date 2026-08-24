<?php
/**
 * Elementor dynamic tags for Bonyad Alavi settings.
 *
 * @package OstadshoChild
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register the Bonyad Alavi dynamic-tag group and tags.
 *
 * Elementor's Dynamic Tags API lives in Elementor Core, while actually using
 * dynamic tags in standard Elementor controls requires Elementor Pro.
 *
 * @param \Elementor\Core\DynamicTags\Manager $dynamic_tags_manager Dynamic tags manager.
 * @return void
 */
function ba_register_elementor_dynamic_tags( $dynamic_tags_manager ) {
	if (
		! class_exists( '\Elementor\Core\DynamicTags\Data_Tag' ) ||
		! class_exists( '\Elementor\Modules\DynamicTags\Module' )
	) {
		return;
	}

	$dynamic_tags_manager->register_group(
		'bonyad-alavi-settings',
		array(
			'title' => esc_html__( 'بنیاد علوی', 'ostadsho-child' ),
		)
	);

	require_once get_stylesheet_directory() . '/inc/elementor/dynamic-tags/class-ba-participation-settings-tags.php';

	$tag_classes = array(
		'BA_Elementor_Donated_Amount_Tag',
		'BA_Elementor_Completed_Projects_Tag',
		'BA_Elementor_Executed_Projects_Value_Tag',
		'BA_Elementor_Participation_Slider_Gallery_Tag',
	);

	foreach ( $tag_classes as $tag_class ) {
		if ( class_exists( $tag_class ) ) {
			$dynamic_tags_manager->register( new $tag_class() );
		}
	}
}
add_action( 'elementor/dynamic_tags/register', 'ba_register_elementor_dynamic_tags' );

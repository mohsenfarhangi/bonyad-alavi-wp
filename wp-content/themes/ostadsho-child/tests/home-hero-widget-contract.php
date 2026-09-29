<?php
declare(strict_types=1);

$theme  = dirname(__DIR__);
$widget = file_get_contents($theme . '/inc/elementor/widgets/class-bonyad-alavi-home-hero-widget.php');
$css    = file_get_contents($theme . '/assets/css/bonyad-alavi-home-hero-widget.css');
$js     = file_get_contents($theme . '/assets/js/bonyad-alavi-home-hero-widget.js');

if ($widget === false || $css === false || $js === false) {
	fwrite(STDERR, "FAIL home hero files missing\n");
	exit(1);
}

$checks = array(
	'widget-name'                  => str_contains($widget, "return 'bonyad_alavi_home_hero';"),
	'slider-repeater'              => str_contains($widget, "'slides'") && str_contains($widget, 'Controls_Manager::REPEATER'),
	'mission-repeater'             => str_contains($widget, "'missions'") && str_contains($widget, 'Controls_Manager::ICONS'),
	'mission-reference-svg-defaults' => str_contains($widget, 'tabler:briefcase-2') && str_contains($widget, 'tabler:school') && str_contains($widget, 'tabler:stethoscope') && str_contains($widget, 'tabler:building-community'),
	'mission-custom-icon-override'   => str_contains($widget, '$has_custom_icon') && str_contains($widget, 'Icons_Manager::render_icon( $icon') && str_contains($widget, 'get_mission_default_svg( $default_icon_key )'),
	'mission-legacy-icon-compat'     => str_contains($widget, "'fas fa-briefcase'") && str_contains($widget, "'fas fa-graduation-cap'") && str_contains($widget, 'is_legacy_default_mission_icon'),
	'mission-default-svg-stroke'     => str_contains($css, '.ba-mission-nav__glyph') && str_contains($css, 'fill: none;') && str_contains($css, 'stroke: currentColor;'),
	'reference-class-structure'      => str_contains($widget, 'class="ba-hero__grid"') && str_contains($widget, 'class="ba-mission-nav"') && str_contains($widget, 'class="ba-slider"') && str_contains($widget, 'class="ba-ticker'),
	'reference-slider-classes'       => str_contains($widget, 'ba-slider__content') && str_contains($widget, 'ba-slider__cta ba-button ba-button--light') && str_contains($widget, 'data-js-slider-arrow'),
	'reference-layout-values'        => str_contains($css, 'grid-template-columns: minmax(220px, var(--ba-mission-column-width, .82fr)) minmax(0, 2.25fr);') && str_contains($css, 'right: clamp(24px, 5vw, 62px);') && str_contains($css, 'bottom: clamp(31px, 6vw, 68px);'),
	'reference-mobile-order'         => str_contains($css, '.ba-home-hero-widget .ba-slider {\n    order: 1;') && str_contains($css, '.ba-home-hero-widget .ba-ticker {\n    order: 2;') && str_contains($css, '.ba-home-hero-widget .ba-mission-nav {\n    order: 3;'),
	'no-style-default-override'      => !str_contains($widget, "'mission_hover_shift', array( 'label' => 'جابجایی افقی'") && !str_contains($widget, "'slider_content_right', array( 'label' => 'فاصله محتوا از راست', 'type' => Controls_Manager::SLIDER, 'size_units' => array( 'px', '%' ), 'range' => array( 'px' => array( 'min' => 0, 'max' => 140 ), '%' => array( 'min' => 0, 'max' => 30 ) ), 'default'") ,
	'legacy-style-ids-invalidated'   => str_contains($widget, "'ref_slider_content_right'") && str_contains($widget, "'ref_mission_icon_color'") && str_contains($widget, "'ref_slider_button_padding'") && str_contains($widget, "'slider_title_tag_reference'"),
	'ticker-shared-query-service'  => str_contains($widget, "BA_Content_Query_Service() )->create_query( \$settings, 'ticker' )"),
	'ticker-default-three'         => str_contains($widget, "'ticker_posts_per_page'") && str_contains($widget, "'default' => 3"),
	'whole-slide-link-contract'    => str_contains($widget, '$whole_link  =') && str_contains($widget, 'ba-slider__whole-link'),
	'button-needs-text-and-link'   => str_contains($widget, "\$has_button  = '' !== \$button_text && '' !== \$link_url;"),
	'no-bundled-default-image'     => !str_contains($widget, 'hero-mehr-alavi.jpg') && !str_contains($widget, 'hero-infrastructure.jpg') && !str_contains($widget, 'hero-event.jpg'),
	'style-typography'             => str_contains($widget, 'mission_title_typography') && str_contains($widget, 'slider_tag_typography') && str_contains($widget, 'slider_title_typography') && str_contains($widget, 'slider_description_typography') && str_contains($widget, 'slider_button_typography') && str_contains($widget, 'ticker_label_typography') && str_contains($widget, 'ticker_item_typography'),
	'hover-controls'               => str_contains($widget, "'mission_state_hover'") && str_contains($widget, "'slider_button_hover'") && str_contains($widget, "'slider_arrow_hover'") && str_contains($widget, "'ticker_item_hover'"),
	'scoped-css'                   => str_contains($css, '.ba-home-hero-widget .ba-hero__grid') && !preg_match('/^\s*(body|html|button|img)\s*\{/m', $css),
	'mobile-ratio'                 => str_contains($css, 'aspect-ratio: var(--ba-mobile-slider-aspect-ratio);'),
	'elementor-js-hook'            => str_contains($js, 'frontend/element_ready/bonyad_alavi_home_hero.default'),
	'multi-instance-js-scope'      => str_contains($js, "root.querySelector('[data-js-slider]')") && str_contains($js, 'WeakMap'),
	'reduced-motion'               => str_contains($js, 'prefers-reduced-motion: reduce') && str_contains($css, '@media (prefers-reduced-motion: reduce)'),
);

foreach ($checks as $name => $ok) {
	echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
	if (!$ok) exit(1);
}

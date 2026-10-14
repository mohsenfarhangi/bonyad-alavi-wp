<?php
declare(strict_types=1);

$theme     = dirname(__DIR__);
$widget    = file_get_contents($theme . '/inc/elementor/widgets/class-bonyad-alavi-home-live-stats-widget.php');
$css       = file_get_contents($theme . '/assets/css/bonyad-alavi-home-live-stats-widget.css');
$script    = file_get_contents($theme . '/assets/js/bonyad-alavi-home-live-stats-widget.js');
$bootstrap = file_get_contents($theme . '/inc/elementor/elementor-widgets.php');

if ($widget === false || $css === false || $script === false || $bootstrap === false) {
	fwrite(STDERR, "FAIL home live stats files missing\n");
	exit(1);
}


/**
 * Ensure Dynamic Tags are enabled on the individual control, including fields
 * within Elementor Repeater; retaining the original field IDs preserves data.
 */
$has_dynamic_control = static function ( string $code, string $owner, string $name ): bool {
	$anchor = $owner . "->add_control(\n\t\t\t'" . $name . "',";
	$start = strpos( $code, $anchor );
	if ( false === $start ) {
		return false;
	}
	$end = strpos( $code, "\n\t\t);", $start );
	if ( false === $end ) {
		return false;
	}
	return 1 === preg_match(
		"/'dynamic'\\s*=>\\s*array\\(\\s*'active'\\s*=>\\s*true\\s*\\)/",
		substr( $code, $start, $end - $start )
	);
};

$checks = array(
	'widget-name'             => str_contains($widget, "return 'bonyad_alavi_home_live_stats';"),
	'swiper-dependency'       => str_contains($widget, 'get_script_depends') && str_contains($widget, "'elementor-frontend', 'swiper', 'bonyad-alavi-home-live-stats-widget'") && str_contains($bootstrap, "assets/js/bonyad-alavi-home-live-stats-widget.js"),
	'repeater'                => str_contains($widget, "'items'") && str_contains($widget, 'Controls_Manager::REPEATER'),
	'numeric-value-control'   => str_contains($widget, "'type'        => Controls_Manager::NUMBER") && str_contains($widget, "'step'        => 1"),
	'dynamic-item-value'      => $has_dynamic_control($widget, '$repeater', 'value'),
	'dynamic-item-label'      => $has_dynamic_control($widget, '$repeater', 'label'),
	'dynamic-header-line-one' => $has_dynamic_control($widget, '$this', 'title_line_one'),
	'dynamic-header-line-two' => $has_dynamic_control($widget, '$this', 'title_line_two'),
	'dynamic-values-render'   => str_contains($widget, '$settings       = $this->get_settings_for_display();') && str_contains($widget, 'format_stat_value( $item[' . "'value'" . ']') && str_contains($widget, "esc_html( trim( (string) ("),
	'reference-title'         => str_contains($widget, "'default'     => 'گزارش برخط'") && str_contains($widget, "'default'     => 'اقدامات'"),
	'six-reference-values'    => str_contains($widget, "'value' => 266549") && str_contains($widget, "'value' => 348969") && str_contains($widget, "'value' => 109729") && str_contains($widget, "'value' => 17939") && str_contains($widget, "'value' => 25232") && str_contains($widget, "'value' => 1163646"),
	'reference-labels'        => str_contains($widget, 'خدمات چشم‌پزشکی') && str_contains($widget, 'خدمات دندان‌پزشکی') && str_contains($widget, 'خدمات به مادران باردار') && str_contains($widget, 'غربالگری کودکان') && str_contains($widget, 'ویزیت برخط') && str_contains($widget, 'آموزش و پیشگیری'),
	'reference-markup'        => str_contains($widget, 'class="ba-live-stats ba-home-live-stats-widget"') && str_contains($widget, 'class="ba-live-stats__panel"') && str_contains($widget, 'class="ba-live-stats__title-line"') && str_contains($widget, 'class="ba-live-stats__grid swiper"') && str_contains($widget, 'class="ba-live-stats__value"') && str_contains($widget, 'class="ba-live-stats__label"'),
	'frontend-formatting'     => str_contains($widget, "preg_replace( '/\\B(?=(\\d{3})+(?!\\d))/', '٬',") && str_contains($widget, "'0' => '۰'") && str_contains($widget, "'9' => '۹'"),
	'reference-panel'         => str_contains($css, 'grid-template-columns: 158px minmax(0, 1fr);') && str_contains($css, 'min-height: 96px;') && str_contains($css, 'padding: 4px;') && str_contains($css, 'gap: 4px;') && str_contains($css, 'border: 2px solid var(--green);') && str_contains($css, 'border-radius: 22px;'),
	'reference-title-style'   => str_contains($css, 'font-size: 16px;') && str_contains($css, 'font-weight: 700;') && str_contains($css, 'line-height: 1.45;') && str_contains($css, 'background: transparent;'),
	'reference-grid'          => str_contains($css, 'display: block;') && str_contains($css, 'background: #fff;') && str_contains($css, 'border-radius: 18px;'),
	'reference-values'        => str_contains($css, 'font-size: clamp(19px, 1.55vw, 24px);') && str_contains($css, 'font-weight: 800;') && str_contains($css, 'margin-top: 7px;') && str_contains($css, 'font-size: 12px;'),
	'reference-separator'     => str_contains($css, 'top: 18%;') && str_contains($css, 'height: 64%;') && str_contains($css, 'background: #dfe8e3;'),
	'reference-tablet'        => str_contains($css, 'grid-template-columns: 148px minmax(0, 1fr);') && str_contains($widget, "'tablet_default' => 3") && str_contains($widget, "'tablet_default' => 2"),
	'reference-mobile'        => str_contains($widget, "'mobile_default' => 2") && str_contains($widget, "'mobile_default' => 3") && str_contains($css, 'border-radius: 14px;') && str_contains($css, 'font-size: 15px;') && str_contains($css, 'white-space: normal;'),
	'swiper-markup'          => str_contains($widget, 'class="swiper-wrapper"') && str_contains($widget, 'class="ba-live-stats__item swiper-slide"') && str_contains($widget, 'data-ba-live-stats-carousel=') && str_contains($widget, 'class="ba-live-stats__title"'),
	'carousel-controls'      => str_contains($widget, "'carousel_columns'") && str_contains($widget, "'carousel_rows'") && str_contains($widget, "'carousel_group'") && str_contains($widget, "'carousel_gap'") && str_contains($widget, "'carousel_row_gap'") && str_contains($widget, "'carousel_navigation'") && str_contains($widget, "'carousel_pagination'") && str_contains($widget, "'carousel_autoplay'"),
	'carousel-safety'        => str_contains($widget, '$single_row_conditions') && str_contains($script, "rows === 1") && str_contains($script, "state.swiper.destroy(true, true)"),
	'elementor-swiper'       => str_contains($script, 'frontend.utils.swiper') && str_contains($script, 'bonyad_alavi_home_live_stats.default'),
	'editor-state-cleanup'   => str_contains($script, 'var instances = new WeakMap()') && str_contains($script, 'elementorFrontend.hooks.addAction') && str_contains($script, 'media.removeEventListener'),
	'responsive-grid'        => str_contains($script, "max-width: 1080px") && str_contains($script, "max-width: 760px") && str_contains($script, "fill: 'row'"),
	'no-bundled-swiper'      => !str_contains($bootstrap, 'assets/vendor/swiper/') && str_contains($widget, "return array( 'swiper'"),
	'style-controls'          => str_contains($widget, "'ref_panel_radius'") && str_contains($widget, "'ref_title_typography'") && str_contains($widget, "'ref_value_typography'") && str_contains($widget, "'ref_separator_color'"),
	'scoped-css'              => str_contains($css, '.ba-home-live-stats-widget .ba-live-stats__panel') && !preg_match('/^\s*(body|html|b|span)\s*\{/m', $css),
	'asset-registration'      => str_contains($bootstrap, "'bonyad-alavi-home-live-stats-widget'") && str_contains($bootstrap, 'class-bonyad-alavi-home-live-stats-widget.php') && str_contains($bootstrap, 'Bonyad_Alavi_Home_Live_Stats_Widget'),
);

foreach ($checks as $name => $ok) {
	echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
	if (!$ok) {
		exit(1);
	}
}

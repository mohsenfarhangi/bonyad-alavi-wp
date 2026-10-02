<?php
declare(strict_types=1);

$theme     = dirname(__DIR__);
$widget    = file_get_contents($theme . '/inc/elementor/widgets/class-bonyad-alavi-home-about-widget.php');
$css       = file_get_contents($theme . '/assets/css/bonyad-alavi-home-about-widget.css');
$js        = file_get_contents($theme . '/assets/js/bonyad-alavi-home-about-widget.js');
$bootstrap = file_get_contents($theme . '/inc/elementor/elementor-widgets.php');
$echarts   = file_get_contents($theme . '/assets/vendor/echarts/echarts.min.js');

if ($widget === false || $css === false || $js === false || $bootstrap === false || $echarts === false) {
	fwrite(STDERR, "FAIL home about files missing\n");
	exit(1);
}

$checks = array(
	'widget-name'              => str_contains($widget, "return 'bonyad_alavi_home_about';"),
	'reference-root'           => str_contains($widget, 'class="ba-impact ba-home-about-widget" id="about"'),
	'actions-repeater'         => str_contains($widget, "'actions'") && str_contains($widget, "'primary'") && str_contains($widget, "'secondary'"),
	'funding-repeater'         => str_contains($widget, "'funding_sources'") && str_contains($widget, "'value'") && str_contains($widget, "'color'"),
	'four-reference-sources'   => str_contains($widget, "'value'         => 14.8") && str_contains($widget, "'value'         => 9.6") && str_contains($widget, "'value'         => 5.4") && str_contains($widget, "'value'         => 3.2"),
	'chart-json-payload'       => str_contains($widget, 'data-js-impact-chart-data') && str_contains($widget, 'wp_json_encode( $chart_payload'),
	'echarts-container'        => str_contains($widget, 'data-js-impact-chart') && !str_contains($widget, 'data-js-impact-segment') && !str_contains($widget, 'data-js-impact-source'),
	'old-php-geometry-removed' => !str_contains($widget, 'build_source_fallback_layout') && !str_contains($widget, 'build_segment_style') && !str_contains($widget, 'build_source_card_style') && !str_contains($widget, 'get_source_class'),
	'summary-repeater'         => str_contains($widget, "'summary_items'") && str_contains($widget, "'show_summary'") && str_contains($widget, "'is_visible'"),
	'summary-no-js-counters'   => !str_contains($widget, 'data-impact-counter') && !str_contains($widget, 'data-impact-total'),
	'local-vendor-registration'=> str_contains($bootstrap, "'apache-echarts'") && str_contains($bootstrap, "'assets/vendor/echarts/echarts.min.js'") && str_contains($bootstrap, "array( 'elementor-frontend', 'apache-echarts' )"),
	'no-cdn-runtime'           => !str_contains($bootstrap, 'cdn.jsdelivr') && !str_contains($widget, 'cdn.jsdelivr') && !str_contains($js, 'cdn.jsdelivr'),
	'echarts-version'          => str_contains($echarts, '6.1.0') && str_contains($echarts, 'Apache Software Foundation'),
	'echarts-pie'              => str_contains($js, "type: 'pie'") && str_contains($js, "radius: ['42%', '58%']"),
	'echarts-label-layout'     => str_contains($js, 'avoidLabelOverlap: true') && str_contains($js, "moveOverlap: 'shiftY'") && str_contains($js, 'labelLine:'),
	'echarts-local-init'       => str_contains($js, 'window.echarts.init') && str_contains($js, "renderer: 'svg'"),
	'minimal-resize'           => str_contains($js, 'ResizeObserver') && str_contains($js, 'chart.resize()'),
	'old-layout-js-removed'    => !str_contains($js, 'resolveImpactCardCollisions') && !str_contains($js, 'positionImpactSourceCards') && !str_contains($js, 'MutationObserver') && !str_contains($js, 'IntersectionObserver') && !str_contains($js, 'impactSegmentX') && !str_contains($js, 'impactSegmentY'),
	'old-card-css-removed'     => !str_contains($css, '.ba-impact__source-card') && !str_contains($css, '.ba-impact__donut-segment') && !str_contains($css, '@keyframes ba-home-about-donut-draw'),
	'chart-css'                => str_contains($css, '.ba-home-about-widget .ba-impact__chart') && str_contains($css, '--ba-impact-label-width: 132px;'),
	'summary-css'              => str_contains($css, '.ba-home-about-widget .ba-impact__summary'),
	'elementor-hook'           => str_contains($js, 'frontend/element_ready/bonyad_alavi_home_about.default'),
	'multi-instance-js'        => str_contains($js, 'WeakMap') && str_contains($js, 'chart.dispose()'),
);

foreach ($checks as $name => $ok) {
	echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
	if (!$ok) {
		exit(1);
	}
}

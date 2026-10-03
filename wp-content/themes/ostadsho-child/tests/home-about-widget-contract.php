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
	'chart-json-payload'       => str_contains($widget, 'data-js-impact-chart-data') && str_contains($widget, 'wp_json_encode( $chart_payload') && !str_contains($widget, 'data-js-impact-funding'),
	'echarts-container'        => str_contains($widget, 'data-js-impact-chart') && !str_contains($widget, 'data-js-impact-segment') && !str_contains($widget, 'data-js-impact-source'),
	'old-php-geometry-removed' => !str_contains($widget, 'build_source_fallback_layout') && !str_contains($widget, 'build_segment_style') && !str_contains($widget, 'build_source_card_style') && !str_contains($widget, 'get_source_class'),
	'funding-head-removed'     => !str_contains($widget, "'funding_kicker'") && !str_contains($widget, "'funding_note'") && !str_contains($widget, 'ba-impact__funding-head') && !str_contains($css, '.ba-impact__funding-head'),
	'beneficiaries-controls'   => str_contains($widget, "'beneficiaries_label'") && str_contains($widget, "'beneficiaries_value'") && str_contains($widget, "'default' => 3250000"),
	'old-summary-removed'      => !str_contains($widget, "'summary_items'") && !str_contains($widget, "'show_summary'") && !str_contains($widget, 'render_summary') && !str_contains($widget, 'get_summary_default_svg') && !str_contains($css, '.ba-impact__summary'),
	'local-vendor-registration'=> str_contains($bootstrap, "'apache-echarts'") && str_contains($bootstrap, "'assets/vendor/echarts/echarts.min.js'") && str_contains($bootstrap, "array( 'elementor-frontend', 'apache-echarts' )"),
	'no-cdn-runtime'           => !str_contains($bootstrap, 'cdn.jsdelivr') && !str_contains($widget, 'cdn.jsdelivr') && !str_contains($js, 'cdn.jsdelivr'),
	'echarts-version'          => str_contains($echarts, '6.1.0') && str_contains($echarts, 'Apache Software Foundation'),
	'echarts-pie'              => str_contains($js, "type: 'pie'") && str_contains($js, "radius: ['42%', '58%']"),
	'echarts-label-layout'     => str_contains($js, 'avoidLabelOverlap: true') && str_contains($js, "moveOverlap: 'shiftY'") && str_contains($js, "alignTo: 'edge'") && str_contains($js, 'edgeDistance: edgeDistance') && str_contains($js, 'bleedMargin:') && str_contains($js, 'labelLine:'),
	'html-value-spans'        => str_contains($widget, 'ba-impact__chart-value-number') && str_contains($widget, 'ba-impact__chart-value-unit') && str_contains($widget, 'data-js-impact-chart-values') && str_contains($css, '.ba-home-about-widget .ba-impact__chart-value-row') && str_contains($css, 'display: flex;') && str_contains($css, '.ba-home-about-widget .ba-impact__chart-value-unit') && str_contains($css, 'order: 1;') && str_contains($css, '.ba-home-about-widget .ba-impact__chart-value-number') && str_contains($css, 'order: 2;'),
	'html-value-positioning'   => str_contains($js, 'syncHtmlValueRows') && str_contains($js, "chart.on('rendered', syncValueRows)") && str_contains($js, "{valueSpace| }") && !str_contains($js, "var valueText ="),
	'echarts-local-init'       => str_contains($js, 'window.echarts.init') && str_contains($js, "renderer: 'svg'"),
	'minimal-resize'           => str_contains($js, 'ResizeObserver') && str_contains($js, 'chart.resize()'),
	'old-layout-js-removed'    => !str_contains($js, 'resolveImpactCardCollisions') && !str_contains($js, 'positionImpactSourceCards') && !str_contains($js, 'MutationObserver') && !str_contains($js, 'IntersectionObserver') && !str_contains($js, 'impactSegmentX') && !str_contains($js, 'impactSegmentY'),
	'old-card-css-removed'     => !str_contains($css, '.ba-impact__source-card') && !str_contains($css, '.ba-impact__donut-segment') && !str_contains($css, '@keyframes ba-home-about-donut-draw'),
	'chart-css'                => str_contains($css, '.ba-home-about-widget .ba-impact__chart') && str_contains($css, 'direction: ltr;') && str_contains($css, 'unicode-bidi: isolate;') && str_contains($css, '--ba-impact-label-width: 132px;') && str_contains($css, '--ba-impact-label-edge-distance: 14px;') && str_contains($js, "chartElement.closest('.ba-impact__funding')"),
	'label-edge-control'       => str_contains($widget, "'ref_chart_label_edge_distance'") && str_contains($widget, "'--ba-impact-label-edge-distance: {{SIZE}}{{UNIT}};'"),
	'beneficiaries-render'     => str_contains($widget, 'ba-impact__beneficiaries') && str_contains($widget, 'ba-impact__beneficiaries-label') && str_contains($widget, 'ba-impact__beneficiaries-value') && str_contains($css, '.ba-home-about-widget .ba-impact__beneficiaries'),
	'compact-responsive'       => str_contains($css, 'padding: 56px 0 52px;') && str_contains($css, 'padding: 50px 0 48px;') && str_contains($css, 'padding: 40px 0 38px;') && str_contains($css, 'padding: 36px 0 34px;'),
	'actions-hover-motion'     => str_contains($css, '.ba-home-about-widget .ba-impact__actions .ba-button:hover') && str_contains($css, 'transform: translateY(-2px);') && str_contains($css, 'background: var(--green-700);') && str_contains($css, 'color: #fff;') && str_contains($css, 'border-color: var(--green-700);') && str_contains($css, 'box-shadow: 0 8px 18px rgba(7, 76, 44, .14);') && !str_contains($css, '.ba-home-about-widget .ba-impact__actions .ba-button::after') && str_contains($css, '@media (prefers-reduced-motion: reduce)'),
	'elementor-hook'           => str_contains($js, 'frontend/element_ready/bonyad_alavi_home_about.default'),
	'multi-instance-js'        => str_contains($js, 'WeakMap') && str_contains($js, 'chart.dispose()'),
);

foreach ($checks as $name => $ok) {
	echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
	if (!$ok) {
		exit(1);
	}
}

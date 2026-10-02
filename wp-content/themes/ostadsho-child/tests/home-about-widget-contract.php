<?php
declare(strict_types=1);

$theme     = dirname(__DIR__);
$widget    = file_get_contents($theme . '/inc/elementor/widgets/class-bonyad-alavi-home-about-widget.php');
$css       = file_get_contents($theme . '/assets/css/bonyad-alavi-home-about-widget.css');
$js        = file_get_contents($theme . '/assets/js/bonyad-alavi-home-about-widget.js');
$bootstrap = file_get_contents($theme . '/inc/elementor/elementor-widgets.php');

if ($widget === false || $css === false || $js === false || $bootstrap === false) {
	fwrite(STDERR, "FAIL home about files missing\n");
	exit(1);
}

$checks = array(
	'widget-name'              => str_contains($widget, "return 'bonyad_alavi_home_about';"),
	'reference-root'           => str_contains($widget, 'class="ba-impact ba-home-about-widget" id="about"'),
	'reference-intro'          => str_contains($widget, 'بنیاد علوی در یک نگاه') && str_contains($widget, 'توانمندسازی پایدار؛ با تمرکز بر ظرفیت‌های واقعی هر منطقه'),
	'actions-repeater'         => str_contains($widget, "'actions'") && str_contains($widget, "'primary'") && str_contains($widget, "'secondary'"),
	'funding-repeater'         => str_contains($widget, "'funding_sources'") && str_contains($widget, "'value'") && str_contains($widget, "'color'"),
	'four-reference-sources'   => str_contains($widget, "'value'         => 14.8") && str_contains($widget, "'value'         => 9.6") && str_contains($widget, "'value'         => 5.4") && str_contains($widget, "'value'         => 3.2"),
	'runtime-donut-math'       => str_contains($js, 'rawShare = total > 0 ? item.value / total * 100 : 0') && str_contains($js, 'Math.min(impactSegmentGap, rawShare * .22)') && str_contains($js, 'visibleShare = Math.max(0, rawShare - gap)') && str_contains($js, 'angleShare * 3.6'),
	'dynamic-css-vars'         => str_contains($widget, '--ba-impact-segment-color:') && str_contains($widget, '--ba-impact-source-color:') && str_contains($js, "'--ba-impact-segment-dash'") && str_contains($js, "'--ba-impact-segment-offset'"),
	'server-fallback-layout'   => str_contains($widget, 'build_source_fallback_layout') && str_contains($widget, "'--ba-impact-source-color:%s;--ba-impact-card-delay:%ss;left:calc(50%% + %spx);top:calc(50%% + %spx);'") && str_contains($widget, '$card_gap     = 10.0;'),
	'summary-repeater'         => str_contains($widget, "'summary_items'") && str_contains($widget, "'show_summary'") && str_contains($widget, "'is_visible'"),
	'summary-auto-total'       => str_contains($widget, "'funding_total'") && str_contains($widget, "$funding_total : max( 0, (float)"),
	'summary-defaults'         => str_contains($widget, 'مجموع منابع (همت)') && str_contains($widget, 'تعداد خدمات‌گیرندگان مستقیم') && str_contains($widget, "'manual_value'     => 3250000"),
	'reference-summary-svg'    => str_contains($widget, 'M4 19h16M6 17V9') && str_contains($widget, 'M16 21v-2a4 4 0 0 0-4-4H6'),
	'persian-formatting'       => str_contains($widget, "number_format( (float) $value, $decimals, '٫', '٬' )") && str_contains($widget, "'0' => '۰'") && str_contains($widget, "'9' => '۹'"),
	'reference-frame'          => str_contains($css, 'grid-template-columns: minmax(0, 1.12fr) minmax(420px, .88fr);') && str_contains($css, 'border-radius: 22px;') && str_contains($css, 'padding: 42px 42px 38px;'),
	'reference-funding'        => str_contains($css, 'padding: 26px 24px 22px;') && str_contains($css, 'background: #f5faf7;') && str_contains($css, 'border-inline-start: 1px solid #dfeae4;'),
	'reference-donut'          => str_contains($css, 'stroke-width: 34;') && str_contains($css, 'width: min(68%, 310px);') && str_contains($css, 'width: 48%;'),
	'reference-source-card'    => str_contains($css, 'width: 154px;') && str_contains($css, 'min-height: 68px;') && str_contains($css, 'border-radius: 13px;') && !str_contains($css, '.ba-home-about-widget .ba-impact__source-card--foundation {'),
	'reference-summary'        => str_contains($css, 'grid-template-columns: repeat(2, minmax(0, 1fr));') && str_contains($css, 'border-radius: 14px;'),
	'reference-responsive'     => str_contains($css, '@media (max-width: 1080px)') && str_contains($css, 'padding: 32px 30px 30px;') && str_contains($css, 'grid-template-columns: repeat(2, minmax(0, 1fr));') && str_contains($css, '@media (max-width: 430px)'),
	'reference-js-behavior'    => str_contains($js, "new Intl.NumberFormat('fa-IR'") && str_contains($js, 'syncImpactFundingData') && str_contains($js, 'rawShare * .22') && str_contains($js, 'impactCardGap = 10') && str_contains($js, 'cardRadius = chartOuterRadius + impactCardGap + cardEdgeDistance') && !str_contains($js, 'orbitRadius') && str_contains($js, 'resolveImpactCardCollisions') && str_contains($js, 'impactVisualInset = 8') && str_contains($js, 'ResizeObserver') && str_contains($js, 'MutationObserver') && str_contains($js, 'IntersectionObserver') && str_contains($js, 'positionImpactSourceCards') && str_contains($js, 'setImpactSourceActive'),
	'multi-instance-js'        => str_contains($js, 'WeakMap') && str_contains($js, "root.querySelector('[data-js-impact-funding]')") && str_contains($js, "impactFunding.classList.remove('is-animation-ready', 'is-visible')"),
	'elementor-hook'           => str_contains($js, 'frontend/element_ready/bonyad_alavi_home_about.default'),
	'style-controls'           => str_contains($widget, "'ref_frame_radius'") && str_contains($widget, "'ref_title_typography'") && str_contains($widget, "'ref_funding_background'") && str_contains($widget, "'ref_summary_value_typography'"),
	'scoped-css'               => str_contains($css, '.ba-home-about-widget .ba-impact__grid') && !preg_match('/^\s*(body|html|a|button)\s*\{/m', $css),
	'asset-registration'       => str_contains($bootstrap, "'bonyad-alavi-home-about-widget'") && str_contains($bootstrap, 'class-bonyad-alavi-home-about-widget.php') && str_contains($bootstrap, 'Bonyad_Alavi_Home_About_Widget'),
);

foreach ($checks as $name => $ok) {
	echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
	if (!$ok) {
		exit(1);
	}
}

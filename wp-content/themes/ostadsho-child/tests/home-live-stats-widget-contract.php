<?php
declare(strict_types=1);

$theme     = dirname(__DIR__);
$widget    = file_get_contents($theme . '/inc/elementor/widgets/class-bonyad-alavi-home-live-stats-widget.php');
$css       = file_get_contents($theme . '/assets/css/bonyad-alavi-home-live-stats-widget.css');
$bootstrap = file_get_contents($theme . '/inc/elementor/elementor-widgets.php');

if ($widget === false || $css === false || $bootstrap === false) {
	fwrite(STDERR, "FAIL home live stats files missing\n");
	exit(1);
}

$checks = array(
	'widget-name'             => str_contains($widget, "return 'bonyad_alavi_home_live_stats';"),
	'no-js-dependency'        => !str_contains($widget, 'get_script_depends') && !str_contains($bootstrap, "'bonyad-alavi-home-live-stats-widget',\n\t\t\t'assets/js/"),
	'repeater'                => str_contains($widget, "'items'") && str_contains($widget, 'Controls_Manager::REPEATER'),
	'numeric-value-control'   => str_contains($widget, "'type'        => Controls_Manager::NUMBER") && str_contains($widget, "'step'        => 1"),
	'reference-title'         => str_contains($widget, "'default'     => 'گزارش برخط'") && str_contains($widget, "'default'     => 'اقدامات'"),
	'six-reference-values'    => str_contains($widget, "'value' => 266549") && str_contains($widget, "'value' => 348969") && str_contains($widget, "'value' => 109729") && str_contains($widget, "'value' => 17939") && str_contains($widget, "'value' => 25232") && str_contains($widget, "'value' => 1163646"),
	'reference-labels'        => str_contains($widget, 'خدمات چشم‌پزشکی') && str_contains($widget, 'خدمات دندان‌پزشکی') && str_contains($widget, 'خدمات به مادران باردار') && str_contains($widget, 'غربالگری کودکان') && str_contains($widget, 'ویزیت برخط') && str_contains($widget, 'آموزش و پیشگیری'),
	'reference-markup'        => str_contains($widget, 'class="ba-live-stats ba-home-live-stats-widget"') && str_contains($widget, 'class="ba-live-stats__panel"') && str_contains($widget, 'class="ba-live-stats__title-line"') && str_contains($widget, 'class="ba-live-stats__grid"') && str_contains($widget, 'class="ba-live-stats__value"') && str_contains($widget, 'class="ba-live-stats__label"'),
	'frontend-formatting'     => str_contains($widget, "preg_replace( '/\\B(?=(\\d{3})+(?!\\d))/', '٬',") && str_contains($widget, "'0' => '۰'") && str_contains($widget, "'9' => '۹'"),
	'reference-panel'         => str_contains($css, 'grid-template-columns: 158px minmax(0, 1fr);') && str_contains($css, 'min-height: 96px;') && str_contains($css, 'padding: 4px;') && str_contains($css, 'gap: 4px;') && str_contains($css, 'border: 2px solid var(--green);') && str_contains($css, 'border-radius: 22px;'),
	'reference-title-style'   => str_contains($css, 'font-size: 16px;') && str_contains($css, 'font-weight: 700;') && str_contains($css, 'line-height: 1.45;') && str_contains($css, 'background: transparent;'),
	'reference-grid'          => str_contains($css, 'grid-template-columns: repeat(6, 1fr);') && str_contains($css, 'background: #fff;') && str_contains($css, 'border-radius: 18px;'),
	'reference-values'        => str_contains($css, 'font-size: clamp(19px, 1.55vw, 24px);') && str_contains($css, 'font-weight: 800;') && str_contains($css, 'margin-top: 7px;') && str_contains($css, 'font-size: 12px;'),
	'reference-separator'     => str_contains($css, 'top: 18%;') && str_contains($css, 'height: 64%;') && str_contains($css, 'background: #dfe8e3;'),
	'reference-tablet'        => str_contains($css, 'grid-template-columns: 148px minmax(0, 1fr);') && str_contains($css, 'grid-template-columns: repeat(3, minmax(0, 1fr));') && str_contains($css, 'min-height: 78px;'),
	'reference-mobile'        => str_contains($css, 'grid-template-columns: repeat(2, minmax(0, 1fr));') && str_contains($css, 'border-radius: 14px;') && str_contains($css, 'font-size: 15px;') && str_contains($css, 'white-space: normal;'),
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

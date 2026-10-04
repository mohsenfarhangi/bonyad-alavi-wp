<?php
declare(strict_types=1);

$theme     = dirname(__DIR__);
$widget    = file_get_contents($theme . '/inc/elementor/widgets/class-bonyad-alavi-section-heading-widget.php');
$css       = file_get_contents($theme . '/assets/css/bonyad-alavi-section-heading-widget.css');
$bootstrap = file_get_contents($theme . '/inc/elementor/elementor-widgets.php');

if ($widget === false || $css === false || $bootstrap === false) {
	fwrite(STDERR, "FAIL section heading files missing\n");
	exit(1);
}

$checks = array(
	'widget-name'           => str_contains($widget, "return 'bonyad_alavi_section_heading';"),
	'no-js-dependency'      => !str_contains($widget, 'get_script_depends') && !str_contains($bootstrap, "'bonyad-alavi-section-heading-widget',\n\t\t\t'assets/js/"),
	'content-controls'      => str_contains($widget, "'eyebrow'") && str_contains($widget, "'title'") && str_contains($widget, "'title_tag'") && str_contains($widget, "'lead'") && str_contains($widget, "'action_text'") && str_contains($widget, "'action_link'"),
	'layout-variants'       => str_contains($widget, "'default' => 'Default'") && str_contains($widget, "'stack'   => 'Stack'") && str_contains($widget, "'card'    => 'Card'") && str_contains($widget, "'ba-section-heading--stack'") && str_contains($widget, "'ba-section-heading--card'"),
	'action-conditional'    => str_contains($widget, "\$has_action  = '' !== \$action_text && ! empty( \$action_link['url'] );"),
	'fixed-action-icon'     => str_contains($widget, '<path d="M19 12H5m6-6-6 6 6 6"></path>') && !str_contains($widget, 'Icons_Manager'),
	'reference-markup'      => str_contains($widget, "'ba-section-heading', 'ba-section-heading-widget'") && str_contains($widget, 'class="ba-section-heading__copy"') && str_contains($widget, 'class="ba-section-heading__eyebrow"') && str_contains($widget, 'class="ba-section-heading__title"') && str_contains($widget, 'class="ba-section-heading__lead"') && str_contains($widget, 'class="ba-section-heading__action"'),
	'reference-default-css' => str_contains($css, 'align-items: flex-end;') && str_contains($css, 'margin-bottom: 20px;') && str_contains($css, 'gap: 18px;') && str_contains($css, 'font-size: clamp(25px, 2.45vw, 34px);') && str_contains($css, 'max-width: 680px;'),
	'reference-card-css'    => str_contains($css, '.ba-section-heading-widget.ba-section-heading--card .ba-section-heading__title') && str_contains($css, 'font-size: 26px;'),
	'reference-mobile-css'  => str_contains($css, '@media (max-width: 760px)') && str_contains($css, '.ba-section-heading-widget:not(.ba-section-heading--stack)') && str_contains($css, 'flex-direction: column;') && str_contains($css, 'margin-bottom: 16px;'),
	'style-controls'        => str_contains($widget, "'ref_margin_bottom'") && str_contains($widget, "'ref_layout_gap'") && str_contains($widget, "'ref_text_align'") && str_contains($widget, "'ref_lead_max_width'") && str_contains($widget, "'ref_eyebrow_line_color'") && str_contains($widget, "'ref_action_hover_color'"),
	'scoped-css'            => str_contains($css, '.ba-section-heading-widget .ba-section-heading__title') && !preg_match('/^\s*(body|html|h[1-6]|a|p)\s*\{/m', $css),
	'asset-registration'    => str_contains($bootstrap, "'bonyad-alavi-section-heading-widget'") && str_contains($bootstrap, 'class-bonyad-alavi-section-heading-widget.php') && str_contains($bootstrap, 'Bonyad_Alavi_Section_Heading_Widget'),
);

foreach ($checks as $name => $ok) {
	echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
	if (!$ok) {
		exit(1);
	}
}

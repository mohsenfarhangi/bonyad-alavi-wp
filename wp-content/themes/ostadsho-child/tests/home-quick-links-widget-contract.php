<?php
declare(strict_types=1);

$theme     = dirname(__DIR__);
$widget    = file_get_contents($theme . '/inc/elementor/widgets/class-bonyad-alavi-home-quick-links-widget.php');
$css       = file_get_contents($theme . '/assets/css/bonyad-alavi-home-quick-links-widget.css');
$js        = file_get_contents($theme . '/assets/js/bonyad-alavi-home-quick-links-widget.js');
$bootstrap = file_get_contents($theme . '/inc/elementor/elementor-widgets.php');

if ($widget === false || $css === false || $js === false || $bootstrap === false) {
    fwrite(STDERR, "FAIL home quick links files missing\n");
    exit(1);
}

$referenceIcons = array(
    'tabler:users-group',
    'tabler:building-community',
    'tabler:gavel',
    'tabler:heart-handshake',
    'tabler:report-analytics',
    'tabler:mosque',
    'tabler:award',
    'tabler:route',
    'tabler:tent',
);

$referenceColors = array(
    '#0f8a57',
    '#2f6fa3',
    '#b7791f',
    '#b44c5e',
    '#6658a6',
    '#247a70',
    '#b57b0d',
    '#3f7eaf',
    '#678a43',
);

$checks = array(
    'widget-name'              => str_contains($widget, "return 'bonyad_alavi_home_quick_links';"),
    'repeater'                 => str_contains($widget, "'items'") && str_contains($widget, 'Controls_Manager::REPEATER'),
    'label-link-icon-color'    => str_contains($widget, "'label'") && str_contains($widget, 'Controls_Manager::URL') && str_contains($widget, 'Controls_Manager::ICONS') && str_contains($widget, "'media_color'"),
    'nine-reference-items'     => substr_count($widget, "'default_icon_key' =>") >= 9,
    'reference-icons'          => count(array_filter($referenceIcons, static fn($icon) => str_contains($widget, $icon))) === count($referenceIcons),
    'reference-colors'         => count(array_filter($referenceColors, static fn($color) => str_contains($widget, $color) && str_contains($css, $color))) === count($referenceColors),
    'reference-markup'         => str_contains($widget, 'class="ba-quick-links ba-home-quick-links-widget"') && str_contains($widget, 'class="ba-quick-links__inner" id="services"') && str_contains($widget, 'class="ba-quick-links__list"') && str_contains($widget, 'data-js-quick-item') && str_contains($widget, 'data-js-quick-more'),
    'reference-more-copy'      => str_contains($widget, '>•••</span>') && str_contains($widget, '>بیشتر</span>') && str_contains($js, "'جمع کردن'"),
    'reference-layout'         => str_contains($css, 'width: 116px;') && str_contains($css, 'flex: 0 0 116px;') && str_contains($css, 'background: #fff;') && str_contains($css, 'border: 1px solid #e7ece9;') && str_contains($css, 'width: 58px;') && str_contains($css, 'gap: 4px;') && str_contains($css, 'min-height: 100px;'),
    'reference-responsive'     => str_contains($css, 'width: 98px;') && str_contains($css, 'width: 86px;') && str_contains($css, 'font-size: 10px;') && str_contains($css, 'justify-content: flex-start;'),
    'reference-overflow-js'    => str_contains($js, 'totalItemsWidth') && str_contains($js, 'for (const item of items)') && str_contains($js, 'if (nextWidth > availableWidth + 1) break;') && str_contains($js, "item.classList.toggle('is-hidden-overflow'") && str_contains($js, "quick.classList.add('is-expanded')"),
    'multi-instance-js'        => str_contains($js, 'WeakMap') && str_contains($js, "root.querySelector('[data-js-quick-list]')"),
    'elementor-hook'           => str_contains($js, 'frontend/element_ready/bonyad_alavi_home_quick_links.default'),
    'custom-icon-override'     => str_contains($widget, '$has_custom_icon') && str_contains($widget, 'Icons_Manager::render_icon'),
    'custom-color-override'    => str_contains($widget, 'get_custom_media_color') && str_contains($widget, "' style="background-color:'") && !str_contains($css, '--ba-quick-link-media-color'),
    'style-controls'           => str_contains($widget, "'ref_item_width'") && str_contains($widget, "'ref_label_typography'") && str_contains($widget, "'ref_icon_size'") && str_contains($widget, "'ref_item_hover_shift'"),
    'scoped-css'               => str_contains($css, '.ba-home-quick-links-widget .ba-quick-links__list') && !preg_match('/^\s*(body|html|button|a)\s*\{/m', $css),
    'asset-registration'       => str_contains($bootstrap, "'bonyad-alavi-home-quick-links-widget'") && str_contains($bootstrap, 'class-bonyad-alavi-home-quick-links-widget.php') && str_contains($bootstrap, 'Bonyad_Alavi_Home_Quick_Links_Widget'),
);

foreach ($checks as $name => $ok) {
    echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
    if (!$ok) exit(1);
}

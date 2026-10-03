<?php
declare(strict_types=1);

$theme     = dirname(__DIR__);
$widget    = file_get_contents($theme . '/inc/elementor/widgets/class-bonyad-alavi-home-news-widget.php');
$css       = file_get_contents($theme . '/assets/css/bonyad-alavi-home-news-widget.css');
$bootstrap = file_get_contents($theme . '/inc/elementor/elementor-widgets.php');
$query     = file_get_contents($theme . '/inc/services/class-ba-content-query-service.php');

if ($widget === false || $css === false || $bootstrap === false || $query === false) {
	fwrite(STDERR, "FAIL home news files missing\n");
	exit(1);
}

$checks = array(
	'widget-name'              => str_contains($widget, "return 'bonyad_alavi_home_news';"),
	'no-js-dependency'         => !str_contains($widget, 'get_script_depends') && !str_contains($bootstrap, "'bonyad-alavi-home-news-widget',\n\t\t\t'assets/js/"),
	'shared-query-service'      => str_contains($widget, "create_query( \$settings, 'news' )"),
	'query-default-four'        => str_contains($widget, "'news_posts_per_page'") && str_contains($widget, "'default' => 4"),
	'query-core-controls'       => str_contains($widget, "'news_post_types'") && str_contains($widget, "'news_categories'") && str_contains($widget, "'news_tags'") && str_contains($widget, "'news_authors'") && str_contains($widget, "'news_include_ids'") && str_contains($widget, "'news_exclude_ids'") && str_contains($widget, "'news_orderby'") && str_contains($widget, "'news_order'") && str_contains($widget, "'news_offset'") && str_contains($widget, "'news_ignore_sticky'") && str_contains($widget, "'news_date_after'") && str_contains($widget, "'news_date_before'"),
	'query-advanced-controls'   => str_contains($widget, "'news_search'") && str_contains($widget, "'news_tax_relation'") && str_contains($widget, "'news_tax_query'") && str_contains($widget, "'include_children'"),
	'query-service-search'      => str_contains($query, "\$args['s'] = \$search;"),
	'query-service-taxonomy'    => str_contains($query, 'build_tax_query') && str_contains($query, "\$args['tax_query'] = \$tax_query;") && str_contains($query, "'include_children'"),
	'default-date-format'       => str_contains($widget, "'default'     => 'F Y'") && str_contains($widget, 'get_the_date( $date_format, $post )'),
	'first-term-label'          => str_contains($widget, 'get_first_term_name') && str_contains($widget, 'reset( $terms )'),
	'clickable-title'           => str_contains($widget, 'class="ba-news-card__title"><a href='),
	'placeholder'               => str_contains($widget, 'ba-news-card__placeholder') && str_contains($css, '.ba-home-news-widget .ba-news-card__placeholder'),
	'all-news-conditional'      => str_contains($widget, "\$has_all_news  = '' !== \$all_news_text && ! empty( \$all_news_link['url'] );"),
	'reference-markup'          => str_contains($widget, 'class="ba-news ba-home-news-widget" id="news"') && str_contains($widget, 'class="ba-news__grid"') && str_contains($widget, 'ba-card ba-card--news'),
	'reference-grid'            => str_contains($css, 'grid-template-columns: repeat(4, minmax(0, 1fr));') && str_contains($css, 'gap: 11px;'),
	'reference-card'            => str_contains($css, 'border: 1px solid #dfe8e3;') && str_contains($css, 'border-radius: 16px;') && str_contains($css, 'aspect-ratio: 16 / 9;') && str_contains($css, 'transform: scale(1.04);'),
	'reference-responsive'      => str_contains($css, 'grid-template-columns: repeat(2, minmax(0, 1fr));') && str_contains($css, 'grid-template-columns: 1fr;') && str_contains($css, 'padding: 58px 0 62px;') && str_contains($css, 'padding: 48px 0 50px;'),
	'style-controls'            => str_contains($widget, "'ref_grid_columns'") && str_contains($widget, "'ref_heading_title_typography'") && str_contains($widget, "'ref_card_radius'") && str_contains($widget, "'ref_card_title_typography'"),
	'scoped-css'                => str_contains($css, '.ba-home-news-widget .ba-news-card') && !preg_match('/^\s*(body|html|h3|a)\s*\{/m', $css),
	'asset-registration'        => str_contains($bootstrap, "'bonyad-alavi-home-news-widget'") && str_contains($bootstrap, 'class-bonyad-alavi-home-news-widget.php') && str_contains($bootstrap, 'Bonyad_Alavi_Home_News_Widget'),
);

foreach ($checks as $name => $ok) {
	echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
	if (!$ok) {
		exit(1);
	}
}

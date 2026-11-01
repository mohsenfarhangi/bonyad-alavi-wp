<?php
declare(strict_types=1);

/**
 * Contract استاتیک مرکز مطالعات: مدل داده، دسترسی، ثبت ویجت‌ها، assetها و templateهای Elementor.
 */

$theme = dirname(__DIR__);
$read  = static function (string $path) use ($theme): string {
	$content = file_get_contents($theme . '/' . $path);
	if ($content === false) {
		fwrite(STDERR, "FAIL missing {$path}\n");
		exit(1);
	}
	return $content;
};

$functions = $read('functions.php');
$bootstrap = $read('inc/studies-center/class-ba-studies-center-bootstrap.php');
$types     = $read('inc/studies-center/class-ba-studies-content-types.php');
$meta      = $read('inc/studies-center/class-ba-studies-session-meta.php');
$query     = $read('inc/studies-center/class-ba-studies-query-service.php');
$rest      = $read('inc/studies-center/class-ba-studies-rest-controller.php');
$renderer  = $read('inc/studies-center/class-ba-studies-card-renderer.php');
$settings  = $read('inc/services/class-ba-studies-center-settings-service.php');
$tab       = $read('inc/admin/settings/class-ba-studies-center-settings-tab.php');
$widgets   = $read('inc/elementor/elementor-widgets.php');
$hero      = $read('inc/elementor/widgets/class-bonyad-alavi-studies-hero-widget.php');
$filter    = $read('inc/elementor/widgets/class-bonyad-alavi-studies-area-filter-widget.php');
$carousel  = $read('inc/elementor/widgets/class-bonyad-alavi-studies-carousel-widget.php');
$grid      = $read('inc/elementor/widgets/class-bonyad-alavi-studies-grid-widget.php');
$base      = $read('inc/elementor/widgets/abstract-bonyad-alavi-studies-session-widget.php');
$video     = $read('inc/elementor/widgets/class-bonyad-alavi-studies-session-video-widget.php');
$content   = $read('inc/elementor/widgets/class-bonyad-alavi-studies-session-content-widget.php');
$info      = $read('inc/elementor/widgets/class-bonyad-alavi-studies-session-info-widget.php');
$files     = $read('inc/elementor/widgets/class-bonyad-alavi-studies-session-files-widget.php');
$css       = $read('assets/css/bonyad-alavi-studies-center.css');
$css_s     = $read('assets/css/bonyad-alavi-studies-session.css');
$js        = $read('assets/js/bonyad-alavi-studies-center.js');
$sjs       = $read('assets/js/bonyad-alavi-studies-session.js');

$studies_lines = array_values(array_filter(explode("\n", $functions), static fn ($l) => str_contains($l, 'studies')));
$templates     = array();
foreach (array('studies-session-single.json', 'studies-center-page.json', 'studies-sessions-archive-page.json') as $file) {
	$templates[$file] = json_decode($read('elementor-templates/' . $file), true);
}
$widget_types = static function (array $elements) use (&$widget_types): array {
	$found = array();
	foreach ($elements as $element) {
		if (($element['elType'] ?? '') === 'widget') {
			$found[] = $element['widgetType'];
		}
		$found = array_merge($found, $widget_types($element['elements'] ?? array()));
	}
	return $found;
};
$session_widgets = is_array($templates['studies-session-single.json']) ? $widget_types($templates['studies-session-single.json']['content']) : array();
$page_widgets    = is_array($templates['studies-center-page.json']) ? $widget_types($templates['studies-center-page.json']['content']) : array();

$checks = array(
	// functions.php: دقیقاً یک خط require و بدون منطق دیگر.
	'functions-single-require'      => count($studies_lines) === 1 && trim($studies_lines[0]) === "require_once get_stylesheet_directory() . '/inc/studies-center/class-ba-studies-center-bootstrap.php';",
	'bootstrap-loads-modules'        => str_contains($bootstrap, 'class-ba-studies-content-types.php') && str_contains($bootstrap, 'class-ba-studies-session-meta.php') && str_contains($bootstrap, 'class-ba-studies-rest-controller.php') && str_contains($bootstrap, 'class-ba-studies-center-settings-tab.php') && str_contains($bootstrap, 'class-ba-studies-center-settings-service.php'),

	// مدل داده.
	'session-cpt'                    => str_contains($types, "const SESSION_POST_TYPE    = 'ba_study_session';") && str_contains($types, "register_post_type(") && str_contains($types, "'supports'        => array( 'title', 'editor', 'excerpt', 'thumbnail', 'revisions' )"),
	'session-caps-mapped-to-studies' => str_contains($types, "'capability_type' => array( 'ba_study_session', 'ba_study_sessions' )") && str_contains($types, "'map_meta_cap'    => true") && str_contains($types, "'capabilities'    => self::get_session_capabilities()") && substr_count($types, '=> $cap,') >= 11 && str_contains($types, "'create_posts'           => \$cap"),
	'area-taxonomy-shared'           => str_contains($types, "const AREA_TAXONOMY        = 'ba_study_area';") && str_contains($types, 'array( self::SESSION_POST_TYPE, self::PUBLICATION_TYPE )'),
	'research-type-taxonomy'         => str_contains($types, "const RESEARCH_TYPE_TAX    = 'ba_research_type';") && str_contains($types, 'register_research_type_taxonomy'),
	'publications-caps-untouched'    => !preg_match("/register_post_type\(\s*self::PUBLICATION_TYPE/", $types) && !str_contains($types, "'publications', array( 'capability"),
	'assign-terms-derived-cap'       => str_contains($types, "'assign_terms' => self::ASSIGN_TERMS_CAP") && str_contains($types, "add_filter( 'user_has_cap'") && str_contains($types, "! empty( \$allcaps['edit_posts'] )"),
	'areas-seeded-once'              => str_contains($types, "const AREAS_SEEDED_OPTION  = 'ba_studies_areas_seeded';") && str_contains($types, 'get_option( self::AREAS_SEEDED_OPTION )') && str_contains($types, 'term_exists( $name, self::AREA_TAXONOMY )') && str_contains($types, 'update_option( self::AREAS_SEEDED_OPTION') && substr_count($types, "=> '") >= 8,
	'rewrite-flush-once'             => str_contains($types, 'maybe_flush_rewrite_rules') && str_contains($types, 'flush_rewrite_rules( false )') && str_contains($types, 'REWRITE_OPTION'),

	// متای نشست.
	'session-meta-nonce-cap'         => str_contains($meta, 'wp_verify_nonce( $nonce, self::NONCE_ACTION )') && str_contains($meta, "current_user_can( 'edit_post', \$post_id )") && str_contains($meta, 'DOING_AUTOSAVE'),
	'session-files-shared-repeater'  => str_contains($meta, 'BA_Admin_Repeater_Component::render(') && str_contains($meta, 'BA_Admin_Repeater_Component::field_name( $base, \'files\'') && !str_contains($meta, 'data-ba-admin-repeater-add'),
	'session-meta-fields'            => str_contains($meta, "'video_id'") && str_contains($meta, "'video_duration'") && str_contains($meta, "'lead'") && str_contains($meta, "'event_type'") && str_contains($meta, "'event_date'") && str_contains($meta, "'time_start'") && str_contains($meta, "'speakers'") && str_contains($meta, "'location'") && str_contains($meta, "'files'"),

	// Query و تفکیک کتاب/پژوهش.
	'books-exclude-research'         => str_contains($query, "self::cat_clause( \$settings['research_cats'], 'NOT IN' )") && str_contains($query, "self::cat_clause( \$settings['books_cats'], 'IN' )"),
	'research-requires-config'       => str_contains($query, 'if ( ! $research_cats ) {') && str_contains($query, 'return null;'),
	'carousel-category-controls'      => str_contains($carousel, "'publication_categories'") && str_contains($carousel, "'session_categories'") && str_contains($carousel, 'Controls_Manager::SELECT2') && str_contains($carousel, 'get_category_options'),
	'carousel-category-rest-parity'   => str_contains($carousel, "BA_Studies_Query_Service::sanitize_category_csv( \$selected_categories )") && str_contains($rest, "'categories' => \$request['categories']") && str_contains($rest, 'sanitize_category_csv'),
	'query-admin-category-precedence' => str_contains($query, "! empty( \$settings['research_cats'] ) ? \$settings['research_cats'] : \$selected_categories") && str_contains($query, 'elseif ( $selected_categories )'),
	'category-options-sitewide'       => str_contains($carousel, "get_taxonomies( array(), 'objects' )") && str_contains($carousel, "'taxonomy'   => array_keys( \$taxonomies )") && substr_count($carousel, 'self::get_category_options()') === 2,
	'category-queries-tax-aware'     => str_contains($query, 'get_term( $id )') && str_contains($query, 'selected_term_clauses( array $ids )') && str_contains($query, "'taxonomy'         => \$taxonomy") && str_contains($query, 'array_merge( $tax, $clauses )'),
	'area-filter-shared-tax'         => str_contains($query, "'taxonomy'         => BA_Studies_Content_Types::AREA_TAXONOMY"),
	'separate-counts'                => str_contains($query, 'public static function count( $type, $categories = array() )') && str_contains($query, 'public static function stat( $type, $categories = array() )') && str_contains($query, "\$args['fields']                 = 'ids';") && str_contains($query, 'get_stat_override'),
	'settings-no-overlap'            => str_contains($settings, "array_diff( \$output['books_cats'], \$output['research_cats'] )"),

	// REST فقط خواندنی.
	'rest-readonly-public'           => str_contains($rest, 'WP_REST_Server::READABLE') && str_contains($rest, "'permission_callback' => '__return_true'") && str_contains($rest, "'enum' => BA_Studies_Query_Service::TYPES") && str_contains($rest, "'maximum' => 24") && !str_contains($rest, 'EDITABLE'),
	'rest-sanitized-presentation'    => str_contains($rest, 'sanitize_short_text') && str_contains($rest, "preg_replace( '/[^a-zA-Z ,.\\/\\-]/', ''"),
	'renderer-escapes'               => str_contains($renderer, "esc_html( \$title )") && str_contains($renderer, 'esc_url( $url )') && str_contains($renderer, 'data-areas="'),

	// تب تنظیمات و دسترسی.
	'tab-registry'                   => str_contains($tab, "add_filter( 'ba_settings_tabs'") && str_contains($tab, "'capability'        => BA_Studies_Center_Settings_Service::CAPABILITY") && str_contains($tab, "'option_name'       => BA_Studies_Center_Settings_Service::OPTION_NAME") && str_contains($tab, "settings_fields( \$tab['option_group'] )") && str_contains($tab, 'data-ba-settings-form'),
	'capability-key'                 => str_contains($settings, "const CAPABILITY  = 'ba_manage_studies_center';"),

	// ویجت‌ها.
	'widget-names'                   => str_contains($hero, "'bonyad_alavi_studies_hero'") && str_contains($filter, "'bonyad_alavi_studies_area_filter'") && str_contains($carousel, "'bonyad_alavi_studies_carousel'") && str_contains($grid, "'bonyad_alavi_studies_grid'") && str_contains($video, "'bonyad_alavi_studies_session_video'") && str_contains($content, "'bonyad_alavi_studies_session_content'") && str_contains($info, "'bonyad_alavi_studies_session_info'") && str_contains($files, "'bonyad_alavi_studies_session_files'"),
	'widget-registration'            => substr_count($widgets, 'new \\Bonyad_Alavi_Studies_') === 8 && str_contains($widgets, 'abstract-bonyad-alavi-studies-session-widget.php') && str_contains($widgets, "'bonyad-alavi-studies-center'") && str_contains($widgets, "'bonyad-alavi-studies-session'"),
	'hero-slider-source'             => str_contains($hero, "'slider_source'") && str_contains($hero, "'default'     => 'manual'") && str_contains($hero, "'auto_count'") && str_contains($hero, "'default'   => 4") && str_contains($hero, "'manual_slides'"),
	'hero-stats-real-count'          => str_contains($hero, 'BA_Studies_Query_Service::stat(') && str_contains($hero, 'BA_Studies_Formatter::number(') && str_contains($hero, "'#studies-books'") && str_contains($hero, "'#studies-sessions'") && str_contains($hero, "'#studies-research'"),
	'hero-stat-categories-same-as-carousel' => str_contains($hero, "'categories'") && str_contains($hero, "\$stat['categories']") && str_contains($hero, 'Bonyad_Alavi_Studies_Carousel_Widget::category_options()') && str_contains($carousel, 'public static function category_options()'),
	'hero-chosen-images'             => str_contains($hero, "'mobile_image'") && str_contains($hero, '<picture>') && str_contains($hero, 'media="(max-width: 1080px)"') && str_contains($hero, 'ba-studies-hero__slide--image-only') && str_contains($hero, '$has_text ) :'),
	'hero-admin-images-priority'     => str_contains($hero, 'BA_Studies_Center_Settings_Service::get_hero_slides()') && strpos($hero, 'get_hero_slides()') < strpos($hero, "'manual' === \$settings['slider_source']"),
	'hero-size-like-participation'   => str_contains($css, '--ba-studies-stats-width: minmax(0, .36fr);') && str_contains($css, 'aspect-ratio: 2.945 / 1;') && str_contains($css, 'var(--ba-studies-hero-height-custom, auto)') && str_contains($css, 'var(--ba-studies-hero-ratio-mobile, 2.945 / 1)') && str_contains($css, '.ba-studies-hero__slide picture') && str_contains($css, 'object-position: 50% 100%') && str_contains($hero, '--ba-studies-hero-ratio-mobile') && str_contains($hero, "'--ba-studies-hero-height-custom: {{SIZE}}{{UNIT}};'") && ! str_contains($hero, "'--ba-studies-hero-ratio:'") && str_contains($hero, 'swiper ba-studies-hero__slider--ratio"'),
	'settings-hero-slides-ui'        => str_contains($tab, "'assets_callback'") && str_contains($tab, "'hero_slides'") && str_contains($tab, 'BA_Admin_Repeater_Component::render(') && str_contains($tab, 'mobile_image_id') && is_file($theme . '/assets/js/ba-studies-settings.js') && is_file($theme . '/assets/css/ba-studies-settings.css'),
	'settings-hero-slides-schema'    => str_contains($settings, "'hero_slides'    => array()") && str_contains($settings, 'public static function sanitize_hero_slides') && str_contains($settings, 'public static function get_hero_slides') && str_contains($settings, 'wp_attachment_is_image'),
	'carousel-types-and-more-link'   => str_contains($carousel, "'books'    => 'کتاب‌ها و انتشارات'") && str_contains($carousel, 'BA_Studies_Center_Settings_Service::get_archive_url( $type )') && str_contains($carousel, "'respond_to_filter'") && str_contains($carousel, "'default' => 'موردی یافت نشد'"),
	'filter-areas-default'           => str_contains($filter, "'همه حوزه‌ها'") && str_contains($filter, 'BA_Studies_Content_Types::AREA_TAXONOMY') && str_contains($filter, "'live'") && str_contains($filter, "'url'"),
	'grid-area-param'                => str_contains($grid, 'BA_Studies_Query_Service::get_requested_area()') && str_contains($grid, "const PAGE_VAR = 'ba_page';"),
	'session-widgets-editor-preview' => str_contains($base, 'is_edit_mode()') && str_contains($base, 'is_preview_mode()') && str_contains($base, "'posts_per_page' => 1"),
	'session-content-no-recursion'   => str_contains($content, 'self::$rendering = true;') && str_contains($content, '|| self::$rendering'),
	'session-info-files-in-card'     => str_contains($info, "'show_files'") && str_contains($info, 'register_files_content_controls()') && str_contains($info, 'register_files_style_controls()') && str_contains($base, "'default' => 'فایل‌های نشست'") && str_contains($base, "'default' => 'دانلود'") && str_contains($info, '<dl class="ba-session-info__table">'),
	'session-widgets-rich-controls'  => str_contains($base, 'protected function add_color_control') && str_contains($base, 'protected function add_typography_control') && str_contains($base, 'protected function add_size_control') && str_contains($base, 'protected function register_files_style_controls') && str_contains($info, "'show_row_' . \$key") && str_contains($info, 'register_card_style_controls') && str_contains($info, "'ref_label_width'") && str_contains($info, 'Group_Control_Box_Shadow') && str_contains($video, "'show_play'") && str_contains($video, "'show_badge'") && str_contains($video, "'badge_position'") && str_contains($video, "'ref_play_size'") && str_contains($video, "'ref_badge_offset'") && str_contains($content, "'lead_tag'") && str_contains($content, "'ref_body_align'") && str_contains($content, "'ref_lead_gap'") && str_contains($files, 'register_files_style_controls()'),
	'session-keeps-saved-control-ids' => str_contains($info, "'ref_border_color'") && str_contains($info, "'ref_title_typography'") && str_contains($base, "'ref_button_bg'") && str_contains($video, "'ref_ratio'") && str_contains($video, "'ref_radius'") && str_contains($content, "'ref_lead_color'") && str_contains($content, "'ref_body_typography'"),
	'session-css-matches-design'     => str_contains($css_s, 'left: var(--ba-session-badge-offset, 12px);') && str_contains($css_s, 'transform: translate(-50%, -50%);') && str_contains($css_s, 'transform: translate(-50%, -50%) scale(1.07);') && !str_contains($css_s, 'margin: -34px 0 0 -34px') && str_contains($css_s, '.ba-session-video.is-clickable') && str_contains($css_s, "font-size: 18px;\n  font-weight: 700;"),
	'session-js-clickable-without-play' => str_contains($sjs, "root.classList.add('is-clickable')") && str_contains($sjs, "root.addEventListener('click', start)") && str_contains($sjs, 'if (!video) return;'),
	'session-template-design-widths' => ($templates['studies-session-single.json']['content'][0]['elements'][0]['settings']['width']['size'] ?? 0) === 58.3 && ($templates['studies-session-single.json']['content'][0]['elements'][1]['settings']['width']['size'] ?? 0) === 39.2 && ($templates['studies-session-single.json']['content'][0]['settings']['background_color'] ?? '') === '#F4F4F4' && !isset($templates['studies-session-single.json']['content'][0]['settings']['background_color_mobile']),

	// Frontend.
	'js-uses-elementor-swiper'       => str_contains($js, 'frontend.utils.swiper') && str_contains($js, "'ba:studies-area-change'") && str_contains($js, 'AbortController') && str_contains($js, "frontend/element_ready/bonyad_alavi_studies_carousel.default"),
	'css-scoped'                     => !preg_match('/^\s*(body|html|a|h[1-6]|img|p)\s*[{,]/m', $css . $css_s) && str_contains($css, '.ba-studies-widget {') && str_contains($css, '@media (max-width: 760px)'),
	'css-tokens-match-home'          => str_contains($css, '--ba-green: #069043;') && str_contains($css, '"peyda"') && str_contains($css_s, '"peyda"'),

	// Templateهای Elementor.
	'templates-valid-json'           => !in_array(null, $templates, true) && ($templates['studies-session-single.json']['type'] ?? '') === 'single-post' && ($templates['studies-center-page.json']['type'] ?? '') === 'page',
	'session-template-only-child'    => $session_widgets === array('bonyad_alavi_studies_session_video', 'bonyad_alavi_studies_session_content', 'bonyad_alavi_studies_session_info'),
	'page-template-widgets'          => in_array('bonyad_alavi_studies_hero', $page_widgets, true) && in_array('bonyad_alavi_studies_area_filter', $page_widgets, true) && in_array('bonyad_alavi_section_heading', $page_widgets, true) && count(array_keys($page_widgets, 'bonyad_alavi_studies_carousel', true)) === 3,

	// ایمنی.
	'no-code-snippets'               => !preg_match('/code[_-]?snippets/i', $bootstrap . $types . $meta . $query . $rest . $tab),
	'harness-cli-only-in-tests'      => str_contains($read('tests/studies-center-harness/render.php'), "if ( PHP_SAPI !== 'cli' )") && str_contains($read('tests/studies-center-harness/stubs.php'), "if ( PHP_SAPI !== 'cli' )") && !preg_match('/studies-center-harness/', $bootstrap . $widgets . $functions),
);

foreach ($checks as $name => $ok) {
	echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
	if (!$ok) {
		exit(1);
	}
}

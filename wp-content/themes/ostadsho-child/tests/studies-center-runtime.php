<?php
declare(strict_types=1);

/**
 * تست runtime منطق خالص مرکز مطالعات با stubهای حداقلی WordPress.
 */

define('ABSPATH', __DIR__ . '/');

$GLOBALS['t_term_taxonomies'] = array(
	1 => 'ba_study_area', 2 => 'ba_study_area',
	3 => 'publications-cat', 4 => 'publications-cat',
	5 => 'publications-cat', 6 => 'publications-cat',
	7 => 'publications-cat', 9 => 'publications-cat',
	41 => 'post_tag', 42 => 'category', 43 => 'ba_research_type',
);
$GLOBALS['t_options'] = array();
$GLOBALS['t_terms']   = array();
$GLOBALS['t_inserts'] = 0;

function get_term($id) { $taxonomy = $GLOBALS['t_term_taxonomies'][(int) $id] ?? null; return $taxonomy ? (object) array('term_id' => (int) $id, 'taxonomy' => $taxonomy) : null; }
function get_taxonomy($tax) {
	$map = array(
		'ba_study_area'    => array('ba_study_session', 'publications'),
		'publications-cat' => array('publications'),
		'ba_research_type' => array('publications'),
		'category'         => array('post'),
		'post_tag'         => array('post'),
	);
	return isset($map[$tax]) ? (object) array('object_type' => $map[$tax]) : false;
}
function get_post_thumbnail_id($post) { return 0; }
function get_the_terms($post, $tax) { return false; }
function has_excerpt($post) { return true; }
function get_the_excerpt($post) { return 'x'; }
function get_the_title($post) { return 'T' . $post->ID; }
function get_permalink($post) { return 'https://example.test/' . $post->ID . '/'; }
/* مثل افزونه‌های تاریخ شمسی: تاریخ را از پست سراسری می‌خواند و آرگومان $post را نادیده می‌گیرد. */
function get_the_date($format, $post = null) { return $GLOBALS['post']->post_date; }
function is_wp_error($value) { return false; }
function get_option($name, $default = false) { return $GLOBALS['t_options'][$name] ?? $default; }
function update_option($name, $value, $autoload = null) { $GLOBALS['t_options'][$name] = $value; return true; }
function wp_parse_args($args, $defaults = array()) { return array_merge($defaults, (array) $args); }
function absint($v) { return abs((int) $v); }
function esc_url_raw($v) { $v = (string) $v; return preg_match('#^https?://#', $v) ? $v : ''; }
function esc_html($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
function esc_attr($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
function esc_url($v) { return htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8'); }
function sanitize_key($v) { return strtolower(preg_replace('/[^a-z0-9_\-]/i', '', (string) $v)); }
function sanitize_title($v) { return strtolower(preg_replace('/[^A-Za-z0-9_-]/', '', (string) $v)); }
function sanitize_text_field($v) { return trim(strip_tags((string) $v)); }
function sanitize_textarea_field($v) { return trim(strip_tags((string) $v)); }
function wp_strip_all_tags($v) { return strip_tags((string) $v); }
function apply_filters($tag, $value) { return $value; }
function add_action() {}
function add_filter() {}
function taxonomy_exists($tax) { return true; }
function term_exists($term, $tax) { return in_array($term, $GLOBALS['t_terms'], true); }
function wp_insert_term($name, $tax, $args = array()) { $GLOBALS['t_terms'][] = $name; $GLOBALS['t_terms'][] = $args['slug']; $GLOBALS['t_inserts']++; return array('term_id' => $GLOBALS['t_inserts']); }

$theme = dirname(__DIR__);
require $theme . '/inc/services/class-ba-studies-center-settings-service.php';
require $theme . '/inc/studies-center/class-ba-studies-formatter.php';
require $theme . '/inc/studies-center/class-ba-studies-content-types.php';
require $theme . '/inc/studies-center/class-ba-studies-session-meta.php';
require $theme . '/inc/studies-center/class-ba-studies-query-service.php';
require $theme . '/inc/studies-center/class-ba-studies-card-renderer.php';
require $theme . '/inc/studies-center/class-ba-studies-rest-controller.php';

$results = array();
$check   = static function (string $name, bool $ok) use (&$results): void { $results[$name] = $ok; };

/* ---------- Formatter ---------- */
$check('fa-number-separator', BA_Studies_Formatter::number(12345) === '۱۲٬۳۴۵');
$check('fa-digits-idempotent', BA_Studies_Formatter::fa_digits(BA_Studies_Formatter::fa_digits('8:30')) === '۸:۳۰');
$check('file-kind', BA_Studies_Formatter::file_kind('video/mp4', 'MP4') === 'video' && BA_Studies_Formatter::file_kind('application/pdf', 'PDF') === 'document');

/* ---------- Settings sanitize ---------- */
$clean = BA_Studies_Center_Settings_Service::sanitize(array(
	'books_cats'     => array('3', '4', '9'),
	'research_cats'  => array('9', 'x'),
	'stats_books'    => '۲۳',
	'stats_sessions' => '',
	'stats_research' => '<b>37</b>',
	'books_url'      => 'javascript:alert(1)',
	'sessions_url'   => 'https://example.test/sessions/',
));
$check('settings-research-wins-overlap', $clean['books_cats'] === array(3, 4) && $clean['research_cats'] === array(9));
$check('settings-stats-digits', $clean['stats_books'] === '23' && $clean['stats_sessions'] === '' && $clean['stats_research'] === '37');
$check('settings-url-sanitized', $clean['books_url'] === '' && $clean['sessions_url'] === 'https://example.test/sessions/');

/* ---------- Query args ---------- */
$GLOBALS['t_options'][BA_Studies_Center_Settings_Service::OPTION_NAME] = array('books_cats' => array(3), 'research_cats' => array(9));
$books = BA_Studies_Query_Service::build_args('books', array('area' => 'health', 'limit' => 500));
$tax   = $books['tax_query'];
$check('books-args', $books['post_type'] === 'publications' && $books['posts_per_page'] === 48
	&& $tax['relation'] === 'AND'
	&& $tax[0]['terms'] === array(3) && $tax[0]['operator'] === 'IN'
	&& $tax[1]['terms'] === array(9) && $tax[1]['operator'] === 'NOT IN'
	&& $tax[2]['taxonomy'] === 'ba_study_area' && $tax[2]['terms'] === array('health'));
$research = BA_Studies_Query_Service::build_args('research');
$check('research-args', $research['post_type'] === 'publications' && $research['tax_query'][0]['terms'] === array(9) && count($research['tax_query']) === 2);
$sessions = BA_Studies_Query_Service::build_args('sessions', array('orderby' => 'DROP TABLE', 'order' => 'asc'));
$check('sessions-args', $sessions['post_type'] === 'ba_study_session' && !isset($sessions['tax_query']) && $sessions['orderby'] === 'date' && $sessions['order'] === 'ASC');
$GLOBALS['t_options'][BA_Studies_Center_Settings_Service::OPTION_NAME] = array();
$check('research-unconfigured-null', BA_Studies_Query_Service::build_args('research') === null);
$check('books-unconfigured-all', !isset(BA_Studies_Query_Service::build_args('books')['tax_query']));
$check('invalid-type-fallback', BA_Studies_Query_Service::sanitize_type('evil') === 'books');

/* ---------- Carousel category filters and priority ---------- */
$check('category-ids-sanitized', BA_Studies_Query_Service::sanitize_category_ids(array('4', '4', '2', 'bad', '-3', '0')) === array(4, 2));
$check('category-csv-sanitized', BA_Studies_Query_Service::sanitize_category_csv('9,7,foo,7,-1') === '9,7');
$GLOBALS['t_options'][BA_Studies_Center_Settings_Service::OPTION_NAME] = array();
$widget_books = BA_Studies_Query_Service::build_args('books', array('categories' => array('4', '7')));
$check('books-widget-categories', $widget_books['tax_query'][0]['terms'] === array(4, 7) && $widget_books['tax_query'][0]['taxonomy'] === 'publications-cat');
$widget_research = BA_Studies_Query_Service::build_args('research', array('categories' => '5,6'));
$check('research-widget-categories', $widget_research['tax_query'][0]['terms'] === array(5, 6));
$check('research-empty-preserved', BA_Studies_Query_Service::build_args('research', array('categories' => '')) === null);
$all_terms = BA_Studies_Query_Service::build_args('books', array('categories' => '41,43'));
$check('all-taxonomies-supported', $all_terms['tax_query'][0]['taxonomy'] === 'post_tag'
	&& $all_terms['tax_query'][1]['taxonomy'] === 'ba_research_type'
	&& $all_terms['tax_query']['relation'] === 'AND');
$mixed_terms = BA_Studies_Query_Service::build_args('research', array('categories' => '4,5,41'));
$check('same-taxonomy-or-different-taxonomy-and', $mixed_terms['tax_query'][0]['terms'] === array(4, 5)
	&& $mixed_terms['tax_query'][0]['taxonomy'] === 'publications-cat'
	&& $mixed_terms['tax_query'][1]['taxonomy'] === 'post_tag');
$check('invalid-term-does-not-broaden', BA_Studies_Query_Service::build_args('books', array('categories' => '999999')) === null);
$check('invalid-research-term-empty', BA_Studies_Query_Service::build_args('research', array('categories' => '999999')) === null);
$session_categories = BA_Studies_Query_Service::build_args('sessions', array('categories' => '1,2', 'area' => 'health'));
$check('session-area-intersection', $session_categories['tax_query'][0]['taxonomy'] === 'ba_study_area'
	&& $session_categories['tax_query'][0]['field'] === 'term_id'
	&& $session_categories['tax_query'][0]['terms'] === array(1, 2)
	&& $session_categories['tax_query'][1]['field'] === 'slug'
	&& $session_categories['tax_query'][1]['terms'] === array('health'));
$check('session-area-keeps-session-cpt', $session_categories['post_type'] === 'ba_study_session');
$check('session-without-categories-keeps-session-cpt', BA_Studies_Query_Service::build_args('sessions', array())['post_type'] === 'ba_study_session');
$session_posts = BA_Studies_Query_Service::build_args('sessions', array('categories' => '42'));
$check('session-post-category-uses-post-type', $session_posts['post_type'] === 'post'
	&& $session_posts['tax_query'][0]['taxonomy'] === 'category'
	&& $session_posts['tax_query'][0]['terms'] === array(42));
$check('session-post-category-with-area-does-not-broaden', BA_Studies_Query_Service::build_args('sessions', array('categories' => '42', 'area' => 'health'))['post_type'] === 'post');
$check('session-incompatible-taxonomies-empty', BA_Studies_Query_Service::build_args('sessions', array('categories' => '42,1')) === null);
$check('session-unregistered-taxonomy-empty', BA_Studies_Query_Service::build_args('sessions', array('categories' => '43,41')) === null);
$GLOBALS['t_options'][BA_Studies_Center_Settings_Service::OPTION_NAME] = array('books_cats' => array(3), 'research_cats' => array(9));
$admin_books = BA_Studies_Query_Service::build_args('books', array('categories' => '4,7'));
$admin_research = BA_Studies_Query_Service::build_args('research', array('categories' => '5,6'));
$check('admin-books-precedence', $admin_books['tax_query'][0]['terms'] === array(3) && $admin_books['tax_query'][1]['terms'] === array(9));
$check('admin-research-precedence', $admin_research['tax_query'][0]['terms'] === array(9));
$GLOBALS['t_options'][BA_Studies_Center_Settings_Service::OPTION_NAME] = array('research_cats' => array(9));
$widget_books_with_exclusion = BA_Studies_Query_Service::build_args('books', array('categories' => '4,9'));
$check('books-research-exclusion', $widget_books_with_exclusion['tax_query'][0]['terms'] === array(4, 9)
	&& $widget_books_with_exclusion['tax_query'][1]['operator'] === 'NOT IN'
	&& $widget_books_with_exclusion['tax_query'][1]['terms'] === array(9));
$GLOBALS['t_options'][BA_Studies_Center_Settings_Service::OPTION_NAME] = array();

/* ---------- Session meta sanitize ---------- */
$meta = BA_Studies_Session_Meta::sanitize(array(
	'video_id'   => '15',
	'event_date' => '۲۰۲۶-۰۹-۰۷',
	'time_start' => '۸:۳۰',
	'time_end'   => '25:00',
	'speakers'   => "الف\nب<script>",
	'files'      => array(
		array('title' => 'ویدئو', 'file_id' => '22', 'meta' => '۱ ساعت'),
		array('title' => 'بدون فایل', 'file_id' => ''),
		'garbage',
	),
));
$check('session-date-normalized', $meta['event_date'] === '2026-09-07');
$check('session-time-validated', $meta['time_start'] === '8:30' && $meta['time_end'] === '');
$check('session-files-filtered', count($meta['files']) === 1 && $meta['files'][0]['file_id'] === 22);
$check('session-text-sanitized', !str_contains($meta['speakers'], '<script>'));

/* ---------- Capabilities ---------- */
$caps = BA_Studies_Content_Types::get_session_capabilities();
$check('session-caps-single-cap', count($caps) === 11 && array_unique(array_values($caps)) === array('ba_manage_studies_center'));
$granted = BA_Studies_Content_Types::grant_assign_terms_cap(array('ba_manage_studies_center' => true), array('ba_assign_study_terms'), array(), null);
$editor  = BA_Studies_Content_Types::grant_assign_terms_cap(array('edit_posts' => true), array('ba_assign_study_terms'), array(), null);
$nobody  = BA_Studies_Content_Types::grant_assign_terms_cap(array('read' => true), array('ba_assign_study_terms'), array(), null);
$other   = BA_Studies_Content_Types::grant_assign_terms_cap(array('edit_posts' => true), array('edit_others_posts'), array(), null);
$check('assign-terms-cap', !empty($granted['ba_assign_study_terms']) && !empty($editor['ba_assign_study_terms']) && empty($nobody['ba_assign_study_terms']) && !isset($other['ba_assign_study_terms']));

/* ---------- Seed یک‌باره ---------- */
$GLOBALS['t_terms'] = array('بهداشت و درمان');
BA_Studies_Content_Types::seed_default_areas();
$first = $GLOBALS['t_inserts'];
$GLOBALS['t_terms'] = array(); // مدیر همه را حذف کرده است.
BA_Studies_Content_Types::seed_default_areas();
$check('seed-skips-existing', $first === 7);
$check('seed-runs-once', $GLOBALS['t_inserts'] === 7 && !empty($GLOBALS['t_options']['ba_studies_areas_seeded']));

/* ---------- Card renderer ---------- */
$item = array('type' => 'sessions', 'title' => '<script>x</script>', 'url' => 'https://example.test/s', 'image' => '', 'image_alt' => '', 'date' => '۱ مهر', 'excerpt' => 'خلاصه', 'badge' => 'ب', 'areas' => array('health'));
$html = BA_Studies_Card_Renderer::render_card($item);
$check('card-escaped', !str_contains($html, '<script>') && str_contains($html, '&lt;script&gt;'));
$check('card-session-play', str_contains($html, 'ba-studies-card__play') && str_contains($html, 'ba-studies-card__excerpt') && str_contains($html, 'data-areas="health"'));
$book = BA_Studies_Card_Renderer::render_card(array_merge($item, array('type' => 'books')));
$check('card-book-no-excerpt', !str_contains($book, 'ba-studies-card__excerpt') && !str_contains($book, '__play'));
$res = BA_Studies_Card_Renderer::render_card(array_merge($item, array('type' => 'research')));
$check('card-research-badge', str_contains($res, 'ba-studies-card__badge'));
$check('empty-hidden', str_contains(BA_Studies_Card_Renderer::render_empty('موردی یافت نشد', true), ' hidden'));

/* ---------- REST sanitizers ---------- */
$check('rest-date-format', BA_Studies_Rest_Controller::sanitize_date_format('j F Y<script>') === 'j F Yscript' && BA_Studies_Rest_Controller::sanitize_date_format('<>') === 'j F Y');
$GLOBALS['post'] = (object) array('ID' => 1, 'post_date' => 'PAGE-DATE');
$card_a = BA_Studies_Query_Service::normalize((object) array('ID' => 10, 'post_date' => '2026-08-01', 'post_content' => ''), 'books');
$card_b = BA_Studies_Query_Service::normalize((object) array('ID' => 11, 'post_date' => '2026-07-15', 'post_content' => ''), 'books');
$check('card-date-uses-own-post', $card_a['date'] !== $card_b['date'] && strpos($card_a['date'], 'PAGE') === false && strpos($card_b['date'], 'PAGE') === false);
$check('card-date-restores-global-post', $GLOBALS['post']->ID === 1);
$check('rest-short-text', mb_strlen(BA_Studies_Rest_Controller::sanitize_short_text(str_repeat('ا', 90))) === 40);

$failed = false;
foreach ($results as $name => $ok) {
	echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
	$failed = $failed || !$ok;
}
exit($failed ? 1 : 0);

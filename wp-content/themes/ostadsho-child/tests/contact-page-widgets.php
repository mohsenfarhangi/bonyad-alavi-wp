<?php
/**
 * تست رفتاری و قراردادی ویجت‌های صفحه «ارتباط با ما» و اصلاح کارت پژوهش مطالعات در عرض کم.
 *
 * اجرا: php tests/contact-page-widgets.php
 */

declare(strict_types=1);

require __DIR__ . '/studies-center-harness/stubs.php';

$theme = dirname(__DIR__);

require $theme . '/inc/elementor/widgets/abstract-bonyad-alavi-contact-page-widget.php';
foreach (array('info', 'table', 'cards', 'map') as $name) {
	require $theme . '/inc/elementor/widgets/class-bonyad-alavi-contact-' . $name . '-widget.php';
}

$results = array();
$check   = static function (string $name, bool $ok) use (&$results): void {
	$results[$name] = $ok;
};
$render  = static function (string $class, array $settings = array()): string {
	return (new $class($settings))->render_html();
};
$count   = static function (string $needle, string $html): int {
	return substr_count($html, $needle);
};

$info  = 'Bonyad_Alavi_Contact_Info_Widget';
$table = 'Bonyad_Alavi_Contact_Table_Widget';
$cards = 'Bonyad_Alavi_Contact_Cards_Widget';
$map   = 'Bonyad_Alavi_Contact_Map_Widget';

/* ---------- اطلاعات تماس ---------- */
$html = $render($info);
$check('info-four-default-items', $count('<li class="ba-contact-info__item">', $html) === 4);
$check('info-default-labels', str_contains($html, 'آدرس:') && str_contains($html, 'کد پستی:') && str_contains($html, 'تلفن:') && str_contains($html, 'ایمیل:'));
$check('info-default-emoji', str_contains($html, '📍') && str_contains($html, '📮') && str_contains($html, '📞') && str_contains($html, '✉️'));
$check('info-default-address-text', str_contains($html, 'تهران، خیابان کریم‌خان زند، بعد از تقاطع حافظ، خیابان شهید گلابی، پلاک ۱۲'));
$check('info-default-tel-link-ltr', str_contains($html, 'href="tel:02183352000"') && str_contains($html, '۰۲۱-۸۳۳۵۲۰۰۰'));
$check('info-default-mail-link', str_contains($html, 'href="mailto:info@bonyadalavi.ir"'));
$check('info-ltr-only-for-postcode-and-phone', $count('dir="ltr"', $html) === 2);
$check('info-default-no-link-for-address', !str_contains($html, '<a href=""'));

$html = $render($info, array('items' => array(
	array('icon' => '', 'label' => 'تست', 'text' => 'x', 'link' => 'javascript:alert(1)', 'text_dir' => 'rtl'),
	array('icon' => '', 'label' => '', 'text' => '', 'link' => 'tel:1', 'text_dir' => 'rtl'),
	array('icon' => '', 'label' => 'دیتا', 'text' => 'y', 'link' => 'data:text/html;base64,AAA', 'text_dir' => 'evil'),
	array('icon' => '🏢', 'label' => 'مجاز', 'text' => 'z', 'link' => 'https://example.test/a', 'text_dir' => 'ltr'),
)));
$check('info-unsafe-link-dropped', !str_contains($html, 'javascript:') && !str_contains($html, 'data:text'));
$check('info-empty-row-skipped', $count('<li class="ba-contact-info__item">', $html) === 3);
$check('info-bad-dir-falls-back-rtl', $count('dir="ltr"', $html) === 1);
$check('info-https-link-allowed', str_contains($html, 'href="https://example.test/a"'));
$check('info-no-icon-no-span', $count('ba-contact-info__icon', $html) === 1);
$html = $render($info, array('items' => array(array('icon' => '', 'label' => '<b>x</b>', 'text' => '<script>a</script>', 'link' => '', 'text_dir' => 'rtl'))));
$check('info-escaped', !str_contains($html, '<script>') && !str_contains($html, '<b>x</b>'));
$check('info-empty-renders-nothing', trim($render($info, array('items' => array()))) === '');

/* ---------- جدول داخلی‌ها ---------- */
$html = $render($table);
$check('table-ten-default-rows', $count('<tr class="ba-contact-table__row">', $html) === 10);
$check('table-default-head', str_contains($html, '>عنوان واحد</th>') && str_contains($html, '>داخلی</th>'));
$check('table-default-values', str_contains($html, '۳۶۸ – ۳۷۰ – ۳۳۵') && str_contains($html, 'مرکز رسانه و روابط عمومی') && str_contains($html, '۳۹۷'));
$check('table-values-ltr', $count('<td class="ba-contact-table__td" dir="ltr">', $html) === 10);
$html = $render($table, array('head_title' => '', 'head_value' => '', 'rows' => array(array('title' => 'الف', 'value' => '1', 'value_dir' => 'rtl'), array('title' => '', 'value' => ''))));
$check('table-no-head-when-both-empty', !str_contains($html, '<thead>'));
$check('table-empty-row-skipped-and-rtl', $count('<tr class="ba-contact-table__row">', $html) === 1 && !str_contains($html, 'dir="ltr"'));
$html = $render($table, array('rows' => array(array('title' => '<i>x</i>', 'value' => '"><script>', 'value_dir' => 'x'))));
$check('table-escaped', !str_contains($html, '<i>') && !str_contains($html, '<script>'));
$check('table-empty-renders-nothing', trim($render($table, array('rows' => array()))) === '');

/* ---------- کارت‌ها ---------- */
$html = $render($cards);
$check('cards-three-default', $count('<div class="ba-contact-cards__card">', $html) === 3);
$check('cards-default-titles', str_contains($html, 'مجتمع اردوگاهی امام خمینی (ره)') && str_contains($html, 'موسسه سلامت') && str_contains($html, 'شرکت اسکان'));
$check('cards-two-phones-split', str_contains($html, '<span dir="ltr">۰۲۱۲۳۵۶۷۷۰۳-۴</span> | <span dir="ltr">۰۹۰۵۳۵۵۱۰۹۲</span>'));
$check('cards-mail-links', $count('href="mailto:', $html) === 3 && str_contains($html, 'mailto:eskan@bonyadalavi.ir'));
$check('cards-icons-default', $count('📞', $html) === 3 && $count('📍', $html) === 3 && $count('✉️', $html) === 3);
$check('cards-title-no-link-by-default', !str_contains($html, '<h4 class="ba-contact-cards__title"><a'));
$html = $render($cards, array('cards' => array(array('title' => 'کارت', 'title_link' => 'javascript:x', 'phone' => '', 'address' => '', 'email' => ''))));
$check('cards-unsafe-title-link-dropped', !str_contains($html, 'javascript:') && !str_contains($html, '<a '));
$check('cards-only-present-lines', !str_contains($html, '<li') && str_contains($html, 'ba-contact-cards__title'));
$html = $render($cards, array('cards' => array(array('title' => 'k', 'title_link' => 'https://example.test/', 'phone' => '1|| 2 |', 'address' => '<b>a</b>', 'email' => 'a@b.test'))));
$check('cards-title-link-and-phone-cleanup', str_contains($html, '<a href="https://example.test/">k</a>') && $count('<span dir="ltr">', $html) === 2 && !str_contains($html, '<b>a</b>'));
$html = $render($cards, array('phone_icon' => '☎', 'address_icon' => '', 'email_icon' => ''));
$check('cards-custom-icons', str_contains($html, '☎') && !str_contains($html, '📞'));
$check('cards-empty-renders-nothing', trim($render($cards, array('cards' => array(array('title' => '', 'phone' => '', 'address' => '', 'email' => ''))))) === '');

/* ---------- نقشه ---------- */
$html = $render($map);
$check('map-default-iframe', str_contains($html, '<iframe class="ba-contact-map__frame" src="https://www.google.com/maps/embed?pb=') && str_contains($html, '!2d51.4138!3d35.7065'));
$check('map-lazy-title-fullscreen', str_contains($html, 'loading="lazy"') && str_contains($html, 'title="نقشه موقعیت بنیاد علوی"') && str_contains($html, 'allowfullscreen'));
foreach (array(
	'http://www.google.com/maps/embed?pb=1',
	'https://evil.test/maps/embed?pb=1',
	'https://www.google.com.evil.test/maps/embed?pb=1',
	'https://www.google.com/maps/embedx?pb=1',
	'https://www.google.com/maps?q=1',
	'javascript:alert(1)',
	'<iframe src="https://www.google.com/maps/embed?pb=1"></iframe>',
	'',
) as $i => $bad) {
	$check('map-rejects-bad-url-' . $i, trim($render($map, array('embed_url' => $bad))) === '');
}
$check('map-accepts-embed-path', Bonyad_Alavi_Contact_Map_Widget::safe_embed_url('https://www.google.com/maps/embed/v1/place?key=k&q=x') !== '');
$html = $render($map, array('map_title' => 'ن"><x'));
$check('map-title-escaped', !str_contains($html, '"><x'));
$html = $render($map, array('map_title' => ''));
$check('map-empty-title-fallback', str_contains($html, 'title="نقشه"'));

/* ---------- قرارداد ثبت و CSS ---------- */
$registrar = (string) file_get_contents($theme . '/inc/elementor/elementor-widgets.php');
foreach (array('Info', 'Table', 'Cards', 'Map') as $n) {
	$check('registered-' . strtolower($n), str_contains($registrar, 'new \\Bonyad_Alavi_Contact_' . $n . '_Widget()') && str_contains($registrar, 'class-bonyad-alavi-contact-' . strtolower($n) . '-widget.php'));
}
$check('registered-style-handle', str_contains($registrar, "'bonyad-alavi-contact-page'") && str_contains($registrar, 'assets/css/bonyad-alavi-contact-page.css'));

$css = (string) file_get_contents($theme . '/assets/css/bonyad-alavi-contact-page.css');
$check('css-no-alavi-class-dependency', !str_contains($css, '.alavi-'));
$bad = 0;
foreach (explode('}', (string) preg_replace('#/\*.*?\*/#s', '', $css)) as $block) {
	$sel = trim(explode('{', $block . '{')[0]);
	if ('' === $sel || str_starts_with($sel, '@')) {
		continue;
	}
	foreach (explode(',', $sel) as $one) {
		$one = trim($one);
		$elements = preg_replace('/[.:][\w-]+(\([^)]*\))?/', ' ', $one);
		if (preg_match('/\b(h[1-6]|a|strong|p|th|td|li|ul|table)\b/', (string) $elements) && substr_count($one, '.') < 2) {
			$bad++;
		}
	}
}
$check('css-element-selectors-two-class', 0 === $bad);
$check('css-no-js-widgets', !str_contains($registrar, 'contact-page.js'));
foreach (array('Info', 'Table', 'Cards', 'Map') as $n) {
	$src = (string) file_get_contents($theme . '/inc/elementor/widgets/class-bonyad-alavi-contact-' . strtolower($n) . '-widget.php');
	$check('style-selectors-two-class-' . strtolower($n), 1 === preg_match('/\\{\\{WRAPPER\\}\\} \\.ba-contact-[a-z]+ /', $src) && !preg_match("/'\\{\\{WRAPPER\\}\\} \\.ba-contact-[a-z]+__/", $src));
}

/* ---------- کارت پژوهش در عرض کم (چهار کارت) ---------- */
$studies = (string) file_get_contents($theme . '/assets/css/bonyad-alavi-studies-center.css');
$check('studies-research-container-query', str_contains($studies, '.ba-studies-section--research .ba-studies-carousel__slide {' . "\n" . '  container-type: inline-size;') && str_contains($studies, '@container (max-width: 300px)'));
$check('studies-research-container-footer-column', (bool) preg_match('/@container \(max-width: 300px\) \{\s*\.ba-studies-card--research \.ba-studies-card__footer \{\s*flex-direction: column;/', $studies));
$check('studies-books-sessions-not-containers', 1 === substr_count($studies, 'container-type'));

$failed = false;
foreach ($results as $name => $ok) {
	echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
	$failed = $failed || !$ok;
}
echo count(array_filter($results)) . '/' . count($results) . PHP_EOL;
exit($failed ? 1 : 0);

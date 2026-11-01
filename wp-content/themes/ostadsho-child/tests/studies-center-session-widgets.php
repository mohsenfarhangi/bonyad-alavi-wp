<?php
/**
 * تست رفتاری ویجت‌های صفحه داخلی نشست: رندر واقعی با stubهای harness و تنظیمات مختلف.
 *
 * اجرا: php tests/studies-center-session-widgets.php
 */

declare(strict_types=1);

require __DIR__ . '/studies-center-harness/stubs.php';

$theme = dirname(__DIR__);

require $theme . '/inc/studies-center/class-ba-studies-formatter.php';
require $theme . '/inc/elementor/widgets/abstract-bonyad-alavi-studies-session-widget.php';
foreach (array('video', 'content', 'info', 'files') as $name) {
	require $theme . '/inc/elementor/widgets/class-bonyad-alavi-studies-session-' . $name . '-widget.php';
}

$GLOBALS['harness_post_id'] = 101;

$fixture = array(
	'title'      => 'ظرفیت‌سازی <script>x</script> برای نوآوری',
	'poster'     => 'poster.jpg',
	'video'      => 'clip.mp4',
	'duration'   => '۲:۴۰',
	'lead'       => 'لید نشست',
	'event_type' => 'نشست تخصصی',
	'event_date' => '۱۶ شهریور ۱۴۰۵',
	'time_start' => '۸:۳۰',
	'time_end'   => '۱۰:۳۰',
	'speakers'   => 'سخنران اول',
	'areas'      => array('نوآوری اجتماعی', 'مدیریت دانش'),
	'location'   => 'تهران',
	'body'       => "پاراگراف اول\n\nپاراگراف دوم",
	'files'      => array(
		array('title' => 'ویدئوی کامل', 'url' => 'https://example.test/a.mp4', 'kind' => 'video', 'meta' => '۱ ساعت · MP4'),
		array('title' => 'گزارش', 'url' => 'https://example.test/b.pdf', 'kind' => 'document', 'meta' => '۱۴ صفحه · PDF'),
	),
);
$GLOBALS['harness_session'] = $fixture;

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

$info    = 'Bonyad_Alavi_Studies_Session_Info_Widget';
$video   = 'Bonyad_Alavi_Studies_Session_Video_Widget';
$content = 'Bonyad_Alavi_Studies_Session_Content_Widget';
$files   = 'Bonyad_Alavi_Studies_Session_Files_Widget';

/* ---------- کارت اطلاعات: پیش‌فرض‌ها ---------- */
$html = $render($info);
$check('info-default-six-rows', $count('<div class="ba-session-info__row">', $html) === 6);
$check('info-default-title-h1', str_contains($html, '<h1 class="ba-session-info__title">'));
$check('info-title-escaped', !str_contains($html, '<script>') && str_contains($html, '&lt;script&gt;'));
$check('info-default-files-two', $count('class="ba-session-file ', $html) === 2);
$check('info-default-download-text', $count('>دانلود</a>', $html) === 2);
$check('info-default-areas-joined', str_contains($html, 'نوآوری اجتماعی، مدیریت دانش'));
$check('info-default-time-separator', str_contains($html, '۸:۳۰ تا ۱۰:۳۰'));

/* ---------- کارت اطلاعات: کنترل‌ها ---------- */
$html = $render($info, array('show_row_speakers' => '', 'show_row_location' => ''));
$check('info-hide-rows', !str_contains($html, 'سخنرانان') && !str_contains($html, 'تهران') && $count('<div class="ba-session-info__row">', $html) === 4);
$html = $render($info, array('label_type' => 'گونه رویداد'));
$check('info-custom-label', str_contains($html, '<dt>گونه رویداد</dt>') && !str_contains($html, '<dt>نوع رویداد</dt>'));
$html = $render($info, array('area_separator' => '/'));
$check('info-custom-area-separator', str_contains($html, 'نوآوری اجتماعی/ مدیریت دانش'));
$html = $render($info, array('show_title' => ''));
$check('info-hide-title', !str_contains($html, 'ba-session-info__title'));
$html = $render($info, array('title_tag' => 'script'));
$check('info-title-tag-whitelist', str_contains($html, '<h1 class="ba-session-info__title">') && !str_contains($html, '<script class'));
$html = $render($info, array('title_tag' => 'h2'));
$check('info-title-tag-h2', str_contains($html, '<h2 class="ba-session-info__title">'));
$html = $render($info, array('show_files' => ''));
$check('info-hide-files', !str_contains($html, 'ba-session-files'));
$html = $render($info, array('show_files_title' => ''));
$check('info-hide-files-title-keeps-list', !str_contains($html, 'ba-session-files__title') && $count('class="ba-session-file ', $html) === 2);
$html = $render($info, array('show_file_icon' => '', 'show_file_meta' => ''));
$check('info-hide-file-icon-and-meta', !str_contains($html, 'ba-session-file__icon') && !str_contains($html, 'ba-session-file__meta'));
$html = $render($info, array('files_title_tag' => 'h3', 'download_text' => 'دریافت'));
$check('info-files-title-tag-and-text', str_contains($html, '<h3 class="ba-session-files__title">') && $count('>دریافت</a>', $html) === 2);
$html = $render($info, array('files_title_tag' => 'iframe'));
$check('info-files-title-tag-whitelist', str_contains($html, '<h2 class="ba-session-files__title">') && !str_contains($html, '<iframe'));

$GLOBALS['harness_session'] = array_merge($fixture, array('speakers' => '', 'location' => '', 'files' => array()));
$html = $render($info);
$check('info-empty-values-skipped', $count('<div class="ba-session-info__row">', $html) === 4 && !str_contains($html, 'ba-session-files'));
$GLOBALS['harness_session'] = $fixture;

/* ---------- فایل‌ها (ویجت مستقل) ---------- */
$html = $render($files);
$check('files-default', $count('class="ba-session-file ', $html) === 2 && str_contains($html, '<h2 class="ba-session-files__title">فایل‌های نشست</h2>'));
$html = $render($files, array('show_files_title' => '', 'show_file_icon' => '', 'show_file_meta' => '', 'download_text' => 'برو'));
$check('files-controls', !str_contains($html, 'ba-session-files__title') && !str_contains($html, 'ba-session-file__icon') && !str_contains($html, 'ba-session-file__meta') && $count('>برو</a>', $html) === 2);
$check('files-download-attribute-and-url', str_contains($html, 'href="https://example.test/a.mp4" download'));

/* ---------- ویدئو ---------- */
$html = $render($video);
$check('video-default-play-and-badge', str_contains($html, 'ba-session-video__play') && str_contains($html, 'کلیپ کوتاه نشست · ۲:۴۰'));
$html = $render($video, array('show_play' => ''));
$check('video-hide-play', !str_contains($html, 'ba-session-video__play') && str_contains($html, 'data-ba-session-video-media'));
$html = $render($video, array('show_badge' => ''));
$check('video-hide-badge', !str_contains($html, 'ba-session-video__badge') && str_contains($html, 'ba-session-video__play'));
$html = $render($video, array('show_duration' => ''));
$check('video-hide-duration', str_contains($html, 'کلیپ کوتاه نشست') && !str_contains($html, '۲:۴۰'));
$html = $render($video, array('badge_text' => 'ویدئو'));
$check('video-custom-badge-text', str_contains($html, 'ویدئو · ۲:۴۰'));
$GLOBALS['harness_session'] = array_merge($fixture, array('video' => ''));
$html = $render($video);
$check('video-poster-only-without-video', str_contains($html, 'ba-session-video--poster') && !str_contains($html, 'ba-session-video__play') && !str_contains($html, 'ba-session-video__badge') && str_contains($html, '<img'));
$GLOBALS['harness_session'] = $fixture;

/* ---------- لید و گزارش ---------- */
$html = $render($content);
$check('content-default', str_contains($html, '<p class="ba-session-content__lead">لید نشست</p>') && str_contains($html, 'ba-session-content__body') && str_contains($html, 'پاراگراف دوم'));
$html = $render($content, array('lead_tag' => 'h2'));
$check('content-lead-tag-h2', str_contains($html, '<h2 class="ba-session-content__lead">لید نشست</h2>'));
$html = $render($content, array('lead_tag' => 'script'));
$check('content-lead-tag-whitelist', str_contains($html, '<p class="ba-session-content__lead">') && !str_contains($html, '<script'));
$html = $render($content, array('show_lead' => ''));
$check('content-hide-lead', !str_contains($html, 'ba-session-content__lead') && str_contains($html, 'ba-session-content__body'));
$html = $render($content, array('show_body' => ''));
$check('content-hide-body', !str_contains($html, 'ba-session-content__body') && str_contains($html, 'ba-session-content__lead'));

$failed = false;
foreach ($results as $name => $ok) {
	echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
	$failed = $failed || !$ok;
}
exit($failed ? 1 : 0);

<?php
/**
 * رندر ایزوله صفحه مرکز مطالعات و صفحه داخلی نشست برای screenshot.
 *
 * استفاده:
 *   php wp-content/themes/ostadsho-child/tests/studies-center-harness/render.php <output-dir> <deps-dir>
 * deps-dir باید node_modules با swiper@8 و vazirmatn داشته باشد (خارج از ریپو).
 * خروجی: index.html (صفحه اصلی) و session.html (صفحه داخلی نشست).
 *
 * چیدمان containerها شبیه‌سازی template المنتور در
 * elementor-templates/ قالب است؛ ظاهر کارت‌ها و ویجت‌ها
 * مستقیماً از CSS/JS قالب می‌آید.
 */

declare(strict_types=1);

if ( PHP_SAPI !== 'cli' ) {
	exit;
}

$out  = $argv[1] ?? '';
$deps = $argv[2] ?? '';

if ( '' === $out || '' === $deps || ! is_dir( $deps ) ) {
	fwrite( STDERR, "usage: php render.php <output-dir> <deps-dir>\n" );
	exit( 1 );
}

@mkdir( $out . '/img', 0777, true );

require __DIR__ . '/stubs.php';

$theme = dirname( __DIR__, 2 );

require $theme . '/inc/studies-center/class-ba-studies-formatter.php';
require $theme . '/inc/studies-center/class-ba-studies-card-renderer.php';
foreach ( array( 'section-heading', 'studies-hero', 'studies-area-filter', 'studies-carousel' ) as $name ) {
	require $theme . '/inc/elementor/widgets/class-bonyad-alavi-' . $name . '-widget.php';
}
require $theme . '/inc/elementor/widgets/abstract-bonyad-alavi-studies-session-widget.php';
foreach ( array( 'video', 'content', 'info' ) as $name ) {
	require $theme . '/inc/elementor/widgets/class-bonyad-alavi-studies-session-' . $name . '-widget.php';
}

/* ---------- تصاویر placeholder (SVG) ---------- */

function harness_svg( $file, $w, $h, $body ) {
	global $out;
	file_put_contents( $out . '/img/' . $file, '<svg xmlns="http://www.w3.org/2000/svg" width="' . $w . '" height="' . $h . '" viewBox="0 0 ' . $w . ' ' . $h . '">' . $body . '</svg>' );
	return 'img/' . $file;
}

function harness_photo( $file, $hue ) {
	return harness_svg(
		$file,
		1200,
		800,
		'<defs><linearGradient id="g" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="hsl(' . $hue . ',22%,62%)"/><stop offset="1" stop-color="hsl(' . ( $hue + 30 ) . ',18%,32%)"/></linearGradient></defs>'
		. '<rect width="1200" height="800" fill="url(#g)"/><rect x="120" y="160" width="420" height="260" rx="10" fill="#f1f4f2" opacity=".85"/>'
		. '<rect x="0" y="560" width="1200" height="240" fill="#6b3b2a" opacity=".55"/><circle cx="820" cy="420" r="70" fill="#2b2b2b" opacity=".45"/><circle cx="980" cy="430" r="64" fill="#2b2b2b" opacity=".45"/>'
	);
}

function harness_cover( $file, $color, $title ) {
	return harness_svg(
		$file,
		600,
		800,
		'<rect width="600" height="800" fill="#fff"/><rect x="520" width="80" height="800" fill="' . $color . '"/>'
		. '<path d="M0 520 L600 380 L600 800 L0 800Z" fill="' . $color . '" opacity=".9"/><circle cx="90" cy="90" r="38" fill="#0b7a3e"/>'
		. '<text x="300" y="300" font-family="Vazirmatn, Tahoma" font-size="64" font-weight="800" fill="#2a2a2a" text-anchor="middle">' . htmlspecialchars( $title ) . '</text>'
	);
}

/* ---------- داده fixture مطابق طرح ---------- */

$GLOBALS['harness_stats'] = array( 'books' => 23, 'sessions' => 48, 'research' => 37 );

$book = static function ( $title, $date, $color, $n ) {
	return array( 'type' => 'books', 'title' => $title, 'url' => '#', 'image' => harness_cover( "book-$n.svg", $color, $title ), 'image_alt' => '', 'date' => $date, 'excerpt' => '', 'badge' => '', 'areas' => array() );
};
$session = static function ( $title, $excerpt, $date, $n ) {
	return array( 'type' => 'sessions', 'title' => $title, 'url' => '#', 'image' => harness_photo( "session-$n.svg", 20 + $n * 40 ), 'image_alt' => '', 'date' => $date, 'excerpt' => $excerpt, 'badge' => '', 'areas' => array() );
};
$research = static function ( $title, $excerpt, $badge, $date, $color, $n ) {
	return array( 'type' => 'research', 'title' => $title, 'url' => '#', 'image' => harness_cover( "research-$n.svg", $color, '' ), 'image_alt' => '', 'date' => $date, 'excerpt' => $excerpt, 'badge' => $badge, 'areas' => array() );
};

$GLOBALS['harness_items'] = array(
	'books'    => array(
		$book( 'هوش هیجانی', '۱۰ مرداد ۱۴۰۵', '#3f8f5a', 1 ),
		$book( 'مدیریت ریسک', '۲۴ تیر ۱۴۰۵', '#1d6b3b', 2 ),
		$book( 'کشت گندم', '۳۰ اردیبهشت ۱۴۰۵', '#c47a2c', 3 ),
		$book( 'پرورش دام سنگین', '۲۶ اردیبهشت ۱۴۰۵', '#8a5a2b', 4 ),
		$book( 'کشت نخود دیم', '۱۲ اردیبهشت ۱۴۰۵', '#5b8c3a', 5 ),
	),
	'sessions' => array(
		$session( 'کارگاه تخصصی سفیران دانش بنیاد علوی برگزار شد', 'این کارگاه با هدف تکمیل درخت دانش و شناسایی و تحلیل شکاف‌های دانشی، سوم شهریور ۱۴۰۵ به میزبانی مرکز برگزار شد.', '۱۱ شهریور ۱۴۰۵', 1 ),
		$session( 'تدوین «درخت دانش»؛ گامی عملیاتی برای استقرار مدیریت دانش', 'نشست تخصصی تدوین و احصای «درخت دانش» حوزه‌های تخصصی بنیاد با حضور سفیران دانش برگزار شد.', '۲۰ مرداد ۱۴۰۵', 2 ),
		$session( 'برگزاری بوت‌کمپ تخصصی مدیریت دانش در بنیاد علوی', 'بوت‌کمپ مدیریت دانش با محوریت آموزش ابعاد اجرایی و عملیاتی استقرار نظام مدیریت دانش برگزار شد.', '۱۰ تیر ۱۴۰۵', 3 ),
		$session( 'نشست هم‌اندیشی پیشرفت مناطق محروم', 'بررسی تجربه‌های موفق پیشرفت محلی با حضور پژوهشگران و فعالان میدانی.', '۲ تیر ۱۴۰۵', 4 ),
	),
	'research' => array(
		$research( 'برنامه راهبردی و عملیاتی بنیاد علوی', 'سند راهنمای اولویت‌ها، برنامه‌ها و شاخص‌های عملکرد بنیاد در بازه ۱۴۰۳ تا ۱۴۰۶.', 'برنامه راهبردی', '۱۵ مرداد ۱۴۰۵', '#0f5c7a', 1 ),
		$research( 'احیا و مرمت قنوات با رویکرد پیشرفت اقتصادی', 'دستورالعمل اجرایی مشارکت در احیای قنات‌ها و اثر آن بر اشتغال و معیشت روستاها.', 'پژوهش میدانی', '۷ تیر ۱۴۰۵', '#1b5fa8', 2 ),
		$research( 'آیین‌نامه اجرایی ارتقای سلامت محرومین', 'چارچوب اجرایی طرح اسما برای ارتقای سلامت مناطق محروم کشور و نحوه ارزیابی آن.', 'گزارش تحلیلی', '۱۸ شهریور ۱۴۰۵', '#1e88c9', 3 ),
	),
);

$GLOBALS['harness_terms'] = array();
foreach ( array( 'مدیریت دانش', 'نوآوری اجتماعی', 'پیشرفت و آبادانی', 'توانمندسازی اقتصادی', 'آموزش، مهارت و پرورش', 'بهداشت و درمان', 'عمرانی و زیرساختی', 'سایر حوزه‌ها' ) as $i => $name ) {
	$GLOBALS['harness_terms'][] = (object) array( 'term_id' => $i, 'taxonomy' => 'ba_study_area', 'slug' => 'area-' . $i, 'name' => $name );
}

// آزمون گزینه‌های قابل جست‌وجوی چند taxonomy متفاوت.
$GLOBALS['harness_terms'][] = (object) array( 'term_id' => 101, 'taxonomy' => 'publications-cat', 'name' => 'کتاب‌های آموزشی', 'slug' => 'education-books' );
$GLOBALS['harness_terms'][] = (object) array( 'term_id' => 102, 'taxonomy' => 'post_tag', 'name' => 'توسعه پایدار', 'slug' => 'sustainable' );
$category_method = new ReflectionMethod( Bonyad_Alavi_Studies_Carousel_Widget::class, 'get_category_options' );
$category_options = $category_method->invoke( null );
if ( ! isset( $category_options['101'], $category_options['102'] )
	|| ! str_contains( $category_options['101'], 'دسته‌های انتشارات' )
	|| ! str_contains( $category_options['102'], 'برچسب‌ها' ) ) {
	throw new RuntimeException( 'All-taxonomy carousel options were not generated correctly.' );
}

$hero_image = harness_photo( 'hero.svg', 120 );
$GLOBALS['harness_items_hero'] = array();

/* ---------- صفحه اصلی ---------- */

$hero = new Bonyad_Alavi_Studies_Hero_Widget(
	array(
		'slider_source' => 'manual',
		'manual_slides' => array_fill(
			0,
			4,
			array(
				'image'     => array( 'url' => $hero_image, 'alt' => '' ),
				'label'     => 'آخرین نشست',
				'title'     => 'ظرفیت‌سازی برای بهره‌گیری از نوآوری اجتماعی در بنیاد علوی',
				'date_text' => '۱۶ شهریور ۱۴۰۵',
				'link'      => array( 'url' => '#' ),
			)
		),
		'autoplay'      => '',
	)
);

$heading = new Bonyad_Alavi_Section_Heading_Widget(
	array(
		'layout'  => 'stack',
		'eyebrow' => 'مرکز مطالعات در یک نگاه',
		'title'   => 'مرکز مطالعات راهبردی و پیشرفت مناطق محروم',
	)
);

$intro = 'این مرکز به عنوان هسته تفکر و بازوی تحلیلی بنیاد علوی، راهبری فکری و پشتیبانی از تصمیم‌سازی‌های کلان بنیاد را بر عهده دارد. هدف اصلی ما این است که سرمایه‌گذاری‌های بنیاد در مناطق محروم با بیشترین اثربخشی، کارایی و پایداری به نتیجه برسد؛ از راه تحلیل دقیق چالش‌ها، طراحی راهکارهای نوآورانه و مبتنی بر شواهد، و ارزیابی مستمر نتایج.';

$filter   = new Bonyad_Alavi_Studies_Area_Filter_Widget();
$books    = new Bonyad_Alavi_Studies_Carousel_Widget( array( 'content_type' => 'books' ) );
$sessions = new Bonyad_Alavi_Studies_Carousel_Widget( array( 'content_type' => 'sessions' ) );
$research = new Bonyad_Alavi_Studies_Carousel_Widget( array( 'content_type' => 'research' ) );

$head = static function ( $title ) use ( $deps, $theme ) {
	$font = $deps . '/node_modules/vazirmatn/Vazirmatn-font-face.css';
	return '<!doctype html><html lang="fa" dir="rtl"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>' . $title . '</title>'
		. '<link rel="stylesheet" href="file://' . $font . '">'
		. '<link rel="stylesheet" href="file://' . $deps . '/node_modules/swiper/swiper-bundle.min.css">'
		. '<link rel="stylesheet" href="file://' . $theme . '/assets/css/bonyad-alavi-section-heading-widget.css">'
		. '<link rel="stylesheet" href="file://' . $theme . '/assets/css/bonyad-alavi-studies-center.css">'
		. '<link rel="stylesheet" href="file://' . $theme . '/assets/css/bonyad-alavi-studies-session.css">'
		. '<link rel="stylesheet" href="file://' . __DIR__ . '/harness.css">'
		. '</head><body>';
};

$foot = '<script src="file://' . $deps . '/node_modules/swiper/swiper-bundle.min.js"></script>'
	. '<script src="file://' . $theme . '/assets/js/bonyad-alavi-studies-center.js"></script>'
	. '<script src="file://' . $theme . '/assets/js/bonyad-alavi-studies-session.js"></script></body></html>';

$page = $head( 'مرکز مطالعات' )
	. '<div class="harness-header">هدر سراسری سایت (خارج از scope)</div>'
	. '<main class="e-con-boxed">'
	. '<div class="e-con">' . $hero->render_html() . '</div>'
	. '<div class="e-con harness-intro"><div class="harness-intro__main">' . $heading->render_html()
	. '<div class="elementor-widget-text-editor"><p>' . $intro . '</p></div></div>'
	. '<div class="harness-intro__side">' . $filter->render_html() . '</div></div>'
	. '<div class="e-con harness-gap">' . $books->render_html() . '</div>'
	. '<div class="e-con harness-gap">' . $sessions->render_html() . '</div>'
	. '<div class="e-con harness-gap">' . $research->render_html() . '</div>'
	. '</main>' . $foot;

file_put_contents( $out . '/index.html', $page );

/* ---------- صفحه داخلی نشست ---------- */

$GLOBALS['harness_post_id'] = 101;
$GLOBALS['harness_session'] = array(
	'title'      => 'ظرفیت‌سازی برای بهره‌گیری از نوآوری اجتماعی در بنیاد علوی',
	'poster'     => harness_photo( 'session-poster.svg', 200 ),
	'video'      => 'clip.mp4',
	'duration'   => '۲:۴۰',
	'lead'       => 'نشست تخصصی «ظرفیت‌سازی برای بهره‌گیری از نوآوری اجتماعی در بنیاد علوی» با حضور فعالان و پژوهشگران این حوزه در مرکز مطالعات برگزار شد.',
	'event_type' => 'نشست تخصصی',
	'event_date' => '۱۶ شهریور ۱۴۰۵',
	'time_start' => '۸:۳۰',
	'time_end'   => '۱۰:۳۰',
	'speakers'   => 'محمدامین صمیمی، روح‌الله حمیدی مطلق، هامون طهماسبی، زهرا معماریان‌پور',
	'areas'      => array( 'نوآوری اجتماعی' ),
	'location'   => 'تهران، خیابان کریمخان زند، خیابان شهید پرویز گلابی، پلاک ۱۲، بنیاد علوی',
	'body'       => "مرکز مطالعات راهبردی و پیشرفت مناطق محروم بنیاد علوی، به همراه مؤسسه تحقیق و توسعه دانشمند، روز ۱۶ شهریور میزبان نشستی بود که پرسش اصلی‌اش این بود: بنیاد علوی درون خود چه ظرفیت‌هایی را برای بهره‌گیری از نوآوری اجتماعی باید ایجاد کند؟\n\nسخنرانان با مرور تجربه‌های داخلی و نمونه‌های موفق، بر سه محور تأکید کردند: شناسایی نوآوران محلی در مناطق هدف، ساده‌سازی فرایند حمایت از طرح‌های کوچک، و سنجش اثر اجتماعی پیش از گسترش هر طرح. در بخش پایانی، حاضران پیشنهادهای خود را برای طراحی سازوکار پشتیبانی از نوآوری اجتماعی در بنیاد مطرح کردند.",
	'files'      => array(
		array( 'title' => 'ویدئوی کامل نشست', 'url' => '#', 'kind' => 'video', 'meta' => '۱ ساعت و ۵۲ دقیقه · MP4' ),
		array( 'title' => 'متن کامل گزارش نشست', 'url' => '#', 'kind' => 'document', 'meta' => '۱۴ صفحه · PDF' ),
		array( 'title' => 'اسلایدهای ارائه سخنرانان', 'url' => '#', 'kind' => 'document', 'meta' => '۳۸ صفحه · PDF' ),
	),
);

$video   = new Bonyad_Alavi_Studies_Session_Video_Widget();
$content = new Bonyad_Alavi_Studies_Session_Content_Widget();
$info    = new Bonyad_Alavi_Studies_Session_Info_Widget();

$session_page = $head( 'نشست' )
	. '<div class="harness-header">هدر سراسری سایت (خارج از scope)</div>'
	. '<main class="harness-session-bg"><div class="e-con-boxed harness-session">'
	. '<div class="harness-session__main">' . $video->render_html() . '<div class="harness-space"></div>' . $content->render_html() . '</div>'
	. '<div class="harness-session__side">' . $info->render_html() . '</div>'
	. '</div></main>' . $foot;

file_put_contents( $out . '/session.html', $session_page );

echo "rendered: $out/index.html, $out/session.html\n";

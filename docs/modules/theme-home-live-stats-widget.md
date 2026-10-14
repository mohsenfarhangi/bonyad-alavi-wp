# MODULE-HOME-LIVE-STATS — Homepage Live Stats Elementor Widget

**Status:** implemented / live acceptance pending  
**Path:** `wp-content/themes/ostadsho-child/inc/elementor/widgets/class-bonyad-alavi-home-live-stats-widget.php`  
**Elementor name:** `bonyad_alavi_home_live_stats`  
**Reference:** `mohsenfarhangi/bonyad-alavi-redesign/redesign/index.html` → `ba-live-stats`

## Purpose

بخش «گزارش برخط اقدامات» صفحه اصلی را به Elementor widget با پوسته و عنوان ثابت و نمایش آمار به‌صورت کاروسل Swiper تبدیل می‌کند. رنگ‌ها، تایپوگرافی، قالب اعداد فارسی و عنوان مرجع حفظ شده‌اند.

Assets:
- `assets/css/bonyad-alavi-home-live-stats-widget.css`
- `assets/js/bonyad-alavi-home-live-stats-widget.js`

Script and style depend on Elementor's bundled `swiper`; no third-party Swiper package is shipped. The Elementor asset registrar uses filemtime versions.

## Markup Contract

کلاس‌های اصلی reference:
- `ba-live-stats`
- `ba-container`
- `ba-live-stats__panel`
- `ba-live-stats__title`
- `ba-live-stats__title-line`
- `ba-live-stats__grid`
- `ba-live-stats__item`
- `ba-live-stats__value`
- `ba-live-stats__label`

`ba-home-live-stats-widget` فقط root scope است. عنوان بیرون کاروسل می‌ماند؛ داخل `ba-live-stats__carousel` یک `ba-live-stats__grid.swiper` با `.swiper-wrapper` و `.ba-live-stats__item.swiper-slide` وجود دارد. Navigation و Pagination جداگانه در پوسته سفید قرار می‌گیرند و وقتی overflow وجود ندارد پنهان می‌شوند.

## Content Contract

Title controls:
- line 1 default: `گزارش برخط`
- line 2 default: `اقدامات`

Stats Repeater:
- `value`: Elementor NUMBER، min=0، step=1؛ Dynamic Tags فعال برای تگ‌های عددی (مانند ACF Number).
- `label`: Elementor TEXT؛ Dynamic Tags فعال برای تگ‌های متنی (مانند ACF Text).
- فیلدهای `title_line_one` و `title_line_two` نیز از Dynamic Tags پشتیبانی می‌کنند.
- داده‌های دستی قدیمی و شناسه‌های کنترل (`value` / `label`) بدون مهاجرت حفظ شده‌اند.
- نمایش داینامیک به Elementor Pro و ارائه‌دهنده تگ (مانند ACF) وابسته است. خود ویجت فقط از مکانیزم استاندارد Elementor استفاده می‌کند؛ `get_field()` یا وابستگی سخت به ACF اضافه نشده است.
- ACF Field در context پست/برگه فعلی انتخاب می‌شود؛ هر آیتم Repeater ویجت باید جداگانه به فیلد متناسب متصل شود. این تغییر ردیف‌های ACF Repeater را خودکار به آیتم‌های کاروسل تبدیل نمی‌کند.

Default reference rows:
- 266549 — خدمات چشم‌پزشکی
- 348969 — خدمات دندان‌پزشکی
- 109729 — خدمات به مادران باردار
- 17939 — غربالگری کودکان
- 25232 — ویزیت برخط
- 1163646 — آموزش و پیشگیری

## Number Formatting Contract

مدیر عدد را بدون separator وارد می‌کند. هنگام render:
1. digit input normalize می‌شود.
2. leading zeroهای غیرضروری حذف می‌شوند.
3. separator هزارگان reference یعنی `٬` اضافه می‌شود.
4. رقم‌های خروجی به فارسی تبدیل می‌شوند.

نمونه:
`266549` → `۲۶۶٬۵۴۹`

این formatting فقط presentation است و مقدار دستی ذخیره‌شده Elementor raw number باقی می‌ماند. هنگام استفاده از Dynamic Tag مقدار resolve‌شده توسط `get_settings_for_display()` نیز همین formatter را طی می‌کند؛ شمارش‌ها عدد صحیح غیرمنفی هستند.

## Visual Contract

Desktop:
- default carousel: 6 columns × 1 row
- section top padding: 24px
- panel: `158px minmax(0,1fr)`
- min-height: 96px
- green shell: background + 2px border
- radius: 22px
- shell padding/gap: 4px/4px
- title lines: 16px / 700 / 1.45
- stats viewport: white, radius18, six visible columns (Swiper grid)
- value: `clamp(19px,1.55vw,24px)`, weight800
- label: 12px, margin-top7
- separator: top18%, height64%, #dfe8e3

Tablet 761–1080:
- default carousel: 3 columns × 2 rows
- section top padding: 20px
- title column: 148px
- Swiper shows 3 columns in two rows
- item min-height: 78px
- third separator hidden per row

Mobile <=760:
- default carousel: 2 columns × 3 rows
- section top padding: 18px
- panel: one column, radius18
- title: horizontal, centered, gap8, font15
- Swiper shows 2 columns in three rows, radius14
- labels wrap
- even item separator hidden and third separator restored after tablet rule

## Style Tab

Controls are available without visual defaults:
- section spacing
- panel min-height/gap/padding/radius/background/border/shadow/title-column width
- title typography/color/background/padding/gap
- grid background/radius
- item padding
- value typography/color
- label typography/color
- separator color

Until the editor changes a control, reference CSS remains authoritative.

## Carousel Controls and JavaScript Contract

- Elementor responsive controls: slidesPerView (columns) 6/3/2; grid rows 1/2/3; columns-per-move default 1; horizontal and vertical spacing default 0.
- Autoplay disabled by default; speed 450ms, delay 5000ms (when enabled); pause on hover and user interaction options.
- Optional arrows (default on), pagination type, touch, grab cursor, keyboard.
- Loop and Free Mode controls are conditionally hidden unless all responsive row settings equal 1, and runtime additionally disables incompatible modes for every multi-row viewport.
- Direction RTL, independent per-instance Swiper state, Elementor element_ready hookup, cleanup/re-initialization on 1080px and 760px breakpoint changes and reduced-motion preference changes.
- JS constructor uses Elementor `elementorFrontend.utils.swiper` and its promise API (with global compatibility fallback).
- Height is calculated per breakpoint from rows and vertical gap. Swiper Grid is responsible for columns; horizontal gap uses `spaceBetween`, vertical gap uses a scoped CSS variable override.
- Arrow and pagination controls are hidden when there is no overflow; existing default six items therefore show no inactive navigation.
- Existing Repeater values and Persian digit/thousands formatting remain unchanged.

## QA

Regression:
`wp-content/themes/ostadsho-child/tests/home-live-stats-widget-contract.php`

Guards:
- widget slug
- Swiper-based JS dependency and asset registration
- NUMBER stat value with Dynamic Tags support
- Dynamic Tags for both stat value/label and both fixed title lines
- ACF/Elementor Pro integration through native control support
- reference title/default rows
- frontend thousands/Persian digit formatter
- Swiper wrapper/slides markup
- responsive carousel defaults 6×1 / 3×2 / 2×3
- separator behavior
- style controls
- scoped CSS
- Elementor/style registration

Static source validation for this change is tracked by the updated regression contract. PHP lint and live WordPress/Elementor acceptance must be reported separately according to actual execution.

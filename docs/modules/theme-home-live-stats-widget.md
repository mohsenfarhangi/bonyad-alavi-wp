# MODULE-HOME-LIVE-STATS — Homepage Live Stats Elementor Widget

**Status:** implemented / live acceptance pending  
**Path:** `wp-content/themes/ostadsho-child/inc/elementor/widgets/class-bonyad-alavi-home-live-stats-widget.php`  
**Elementor name:** `bonyad_alavi_home_live_stats`  
**Reference:** `mohsenfarhangi/bonyad-alavi-redesign/redesign/index.html` → `ba-live-stats`

## Purpose

بخش «گزارش برخط اقدامات» صفحه اصلی را بدون redesign به Elementor widget مستقل تبدیل می‌کند. HTML و final CSS reference authoritative هستند؛ تنها تغییرات معماری، scope قالب و داینامیک‌شدن متن/آمارها است.

Asset:
- `assets/css/bonyad-alavi-home-live-stats-widget.css`

JavaScript وجود ندارد چون block مرجع JS ندارد.

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

`ba-home-live-stats-widget` فقط root scope است.

## Content Contract

Title controls:
- line 1 default: `گزارش برخط`
- line 2 default: `اقدامات`

Stats Repeater:
- `value`: Elementor NUMBER، min=0، step=1
- `label`: Text

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

این formatting فقط presentation است و مقدار ذخیره‌شده Elementor raw number باقی می‌ماند.

## Visual Contract

Desktop:
- section top padding: 24px
- panel: `158px minmax(0,1fr)`
- min-height: 96px
- green shell: background + 2px border
- radius: 22px
- shell padding/gap: 4px/4px
- title lines: 16px / 700 / 1.45
- stats grid: white, radius18, 6 columns
- value: `clamp(19px,1.55vw,24px)`, weight800
- label: 12px, margin-top7
- separator: top18%, height64%, #dfe8e3

Tablet 761–1080:
- section top padding: 20px
- title column: 148px
- stats grid: 3 columns
- item min-height: 78px
- third separator hidden per row

Mobile <=760:
- section top padding: 18px
- panel: one column, radius18
- title: horizontal, centered, gap8, font15
- stats grid: 2 columns, radius14
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

## JavaScript Contract

No JavaScript file, no `get_script_depends()`, and no script registration for this widget.

## QA

Regression:
`wp-content/themes/ostadsho-child/tests/home-live-stats-widget-contract.php`

Guards:
- widget slug
- no JS dependency
- NUMBER-only stat value
- reference title/default rows
- frontend thousands/Persian digit formatter
- reference markup
- desktop/tablet/mobile geometry
- separator behavior
- style controls
- scoped CSS
- Elementor/style registration

Static verification روی source commit‌شده انجام شده است. PHP lint و live WordPress/Elementor visual acceptance هنوز باز هستند.

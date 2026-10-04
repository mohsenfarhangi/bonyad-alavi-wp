# MODULE-SECTION-HEADING — Reusable Section Heading Elementor Widget

**Status:** implemented / live acceptance pending  
**Path:** `wp-content/themes/ostadsho-child/inc/elementor/widgets/class-bonyad-alavi-section-heading-widget.php`  
**Elementor name:** `bonyad_alavi_section_heading`  
**Reference:** `mohsenfarhangi/bonyad-alavi-redesign/redesign/assets/css/components.css` → `.ba-section-heading`

## Purpose

یک ویجت مستقل Elementor برای استفاده عمومی از الگوی عنوان سکشن بنیاد علوی است. این ویجت می‌تواند در هر Container/Section صفحه استفاده شود و وابستگی به ویجت News، About یا صفحه اصلی ندارد.

طبق تصمیم پروژه، وجود این ویجت باعث refactor شدن Heading داخلی ویجت‌های موجود نمی‌شود؛ News/About و سایر widgetها contract خودشان را حفظ می‌کنند.

Assets:
- `assets/css/bonyad-alavi-section-heading-widget.css`
- JavaScript اختصاصی ندارد.

Registration:
- `inc/elementor/elementor-widgets.php`
- CSS با `get_style_depends()` فقط هنگام استفاده ویجت load می‌شود.

## Reference DOM Contract

Root:
- `ba-section-heading`
- `ba-section-heading-widget` فقط scope مستقل این widget است.

Elements:
- `ba-section-heading__copy`
- `ba-section-heading__eyebrow`
- `ba-section-heading__title`
- `ba-section-heading__lead`
- `ba-section-heading__action`

## Layout Variants

### Default
کلاس اضافه ندارد:
`ba-section-heading ba-section-heading-widget`

Reference behavior:
- flex
- `align-items:flex-end`
- `justify-content:space-between`
- gap = 18px
- bottom margin = 20px

در `<=760px`:
- column
- align-items = flex-end
- bottom margin = 16px

### Stack
کلاس:
`ba-section-heading--stack`

همیشه block است و baseline bottom margin آن صفر است.

### Card
کلاس‌ها:
- `ba-section-heading--stack`
- `ba-section-heading--card`

این ترکیب دقیقاً با reference کارت‌های `ba-links` همخوان است. title baseline در این حالت 26px است.

## Content Controls

- Layout: Default / Stack / Card
- Eyebrow
- Title
- Title HTML Tag: H1-H6 / DIV؛ default = H2
- Lead
- Action Text
- Action URL

Rendering rules:
- هر بخش محتوایی خالی باشد markup آن تولید نمی‌شود.
- action فقط وقتی render می‌شود که **هم متن و هم URL** وجود داشته باشند.
- action از SVG فلش ثابت reference استفاده می‌کند.
- Icon Control برای action عمداً وجود ندارد تا هویت بصری ثابت بماند.
- اگر تمام محتوا خالی باشد، frontend خروجی نمی‌دهد؛ در Elementor Editor فقط notice نمایش داده می‌شود.

## Visual Baseline

Eyebrow:
- green = `#06783a`
- 12px / 800
- gold line = `#b79254`
- line = 22×2
- gap = 9px

Title:
- margin = `5px 0 8px`
- default size = `clamp(25px, 2.45vw, 34px)`
- line-height = 1.42
- letter-spacing = -.45px

Lead:
- max-width = 680px
- 14px
- line-height = 1.85
- muted = `#67766f`

Action:
- 14px / 800
- gap = 7px
- fixed 18px arrow
- hover arrow translateX(-3px)
- reduced-motion disables transition/transform

## Style Controls

هیچ visual default از Elementor تزریق نمی‌شود؛ baseline CSS تا زمان override صریح کاربر authority است.

Layout:
- responsive bottom margin
- responsive root gap
- responsive vertical alignment
- responsive text alignment
- responsive copy max-width

Eyebrow:
- typography
- text color
- line color
- line width
- line thickness
- line/text gap

Title:
- typography
- color
- responsive margin

Lead:
- typography
- color
- responsive max-width

Action:
- typography
- normal color
- hover color
- text/icon gap
- icon size

## QA

Regression:
`wp-content/themes/ostadsho-child/tests/section-heading-widget-contract.php`

Guards:
- Elementor slug
- no JS dependency
- content controls
- Default/Stack/Card variants
- action text+URL condition
- fixed SVG arrow/no Icon Manager
- reference BEM markup
- default/Card/mobile CSS
- style controls
- scoped CSS
- asset/widget registration

Static contract verification against committed GitHub sources passed. PHP lint and live WordPress/Elementor acceptance remain pending.

**Source commit:** `62090c810d12c849d6e588a30641534281f763af`

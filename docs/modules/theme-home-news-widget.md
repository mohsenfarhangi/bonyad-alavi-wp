# MODULE-HOME-NEWS — Homepage News Elementor Widget

**Status:** implemented / live acceptance pending  
**Path:** `wp-content/themes/ostadsho-child/inc/elementor/widgets/class-bonyad-alavi-home-news-widget.php`  
**Elementor name:** `bonyad_alavi_home_news`  
**Reference:** `mohsenfarhangi/bonyad-alavi-redesign/redesign/index.html` → `#news / .ba-news`

## Purpose

سکشن «آخرین اخبار» صفحه اصلی را به ویجت مستقل Elementor تبدیل می‌کند. ظاهر مرجع حفظ می‌شود اما آیتم‌ها به‌جای داده ثابت HTML مستقیماً از `WP_Query` و سرویس مشترک Query قالب خوانده می‌شوند.

Assets:
- `assets/css/bonyad-alavi-home-news-widget.css`
- JavaScript اختصاصی ندارد.

Registration:
- `inc/elementor/elementor-widgets.php`
- CSS فقط register می‌شود و از `get_style_depends()` هنگام استفاده ویجت load می‌شود.

## Reference DOM Contract

کلاس‌های اصلی reference حفظ شده‌اند:
- `ba-news`
- `ba-container`
- `ba-section-heading`
- `ba-section-heading__copy`
- `ba-section-heading__eyebrow`
- `ba-section-heading__title`
- `ba-section-heading__lead`
- `ba-section-heading__action`
- `ba-news__grid`
- `ba-news-card ba-card ba-card--news`
- `ba-news-card__media`
- `ba-news-card__body`
- `ba-news-card__category`
- `ba-news-card__title`
- `ba-news-card__meta`
- `ba-news-card__date`
- `ba-news-card__more`

`ba-home-news-widget` فقط root scope است. anchor مرجع `id="news"` حفظ شده است.

## Content Controls

Heading:
- eyebrow؛ default = `روایت اقدامات`
- title؛ default = `آخرین اخبار`
- title HTML tag؛ default = `h2`
- lead
- «همه اخبار» text
- «همه اخبار» URL

اکشن «همه اخبار» فقط وقتی رندر می‌شود که **هم متن و هم URL** غیرخالی باشند.

Card presentation:
- taxonomy برچسب کارت؛ default = `category`
- date format؛ default = `F Y`
- card more text؛ default = `مشاهده`

تاریخ از خود نوشته با `get_the_date( $date_format, $post )` می‌آید. ویجت هیچ تبدیل Gregorian/Jalali انجام نمی‌دهد؛ فیلتر/افزونه فارسی‌ساز سایت authority تاریخ شمسی است.

برچسب کارت اولین term از taxonomy انتخابی است. با انتخاب گزینه «بدون برچسب» این قسمت رندر نمی‌شود.

تصویر و عنوان هر دو clickable هستند. اگر نوشته featured image نداشته باشد، media box حذف نمی‌شود و placeholder SVG داخلی در همان نسبت تصویر رندر می‌شود.

## WP_Query Contract

Query از shared service ساخته می‌شود:

`BA_Content_Query_Service::create_query( $settings, 'news' )`

Default:
- post type = `post`
- posts per page = 4
- orderby = `date`
- order = `DESC`
- ignore sticky = yes
- post status در service ثابت = `publish`

Controls:
- Post Type
- Count
- Categories
- Tags
- Authors
- Search
- Include IDs
- Exclude IDs
- OrderBy
- Order
- Offset
- Ignore sticky
- Date after
- Date before
- generic Taxonomy Query relation = AND/OR
- Taxonomy Query repeater:
  - taxonomy
  - field = term_id / slug / name
  - terms
  - operator = IN / NOT IN / AND
  - include_children

Raw PHP args و `post_status` از UI قابل تزریق نیستند.

## Shared Query Service Extension

`class-ba-content-query-service.php` اکنون علاوه بر contract قبلی، این keyهای اختیاری را می‌شناسد:
- `{prefix}_search`
- `{prefix}_tax_relation`
- `{prefix}_tax_query`

نبود این keyها به معنی empty search/tax_query است؛ بنابراین Hero ticker و مصرف‌کننده‌های قبلی service بدون migration رفتار سابق را حفظ می‌کنند.

## Responsive / Visual Contract

Reference baseline:
- desktop: 4 columns
- <=1080px: 2 columns
- <=760px: 1 column
- <=430px: card title = 14px
- grid gap = 11px
- media aspect ratio = 16/9
- card radius = 16px
- card border = #dfe8e3
- hover = 3px lift + subtle shadow + image scale 1.04
- desktop section padding = 68 / 72
- <=1080 = 58 / 62
- <=760 = 48 / 50

`prefers-reduced-motion` حرکت و transitionهای hover را خاموش می‌کند.

## Style Controls

Style controls visual default جدا از reference ندارند؛ فقط مقدار صریح کاربر selector override تولید می‌کند.

Sections:
- section/layout: background، top border، responsive padding، container max-width، heading gap، grid columns/gap
- heading: eyebrow/title/lead/all-news typography and colors
- cards: background، border، radius، normal/hover shadow، hover border، image ratio/background/zoom، placeholder color، body padding
- card text: category/title/date/more typography and colors + hover states

## QA

Regression:
`wp-content/themes/ostadsho-child/tests/home-news-widget-contract.php`

Guards:
- widget slug / asset registration
- no JavaScript dependency
- shared Query service
- default 4 posts
- core + advanced Query controls
- service search/tax_query support
- default `F Y` date format
- first-term label
- clickable title
- missing-image placeholder
- conditional «همه اخبار»
- reference markup
- 4/2/1 grid and reference card geometry
- scoped CSS / style controls

Static contract checks on the committed GitHub source passed after implementation. PHP lint/full regression and live WordPress/Elementor visual/query acceptance remain pending.

**Source commits:** `bcf67bacac8cca89a91cc4fee3a32f9fc31dbae3`, `6ca7b8be560db0921f3655a10f4ec2ceb981d653`

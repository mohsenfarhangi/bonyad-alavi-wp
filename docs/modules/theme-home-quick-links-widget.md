# MODULE-HOME-QUICK-LINKS — Homepage Quick Links Elementor Widget

**Status:** implemented / live acceptance pending  
**Path:** `wp-content/themes/ostadsho-child/inc/elementor/widgets/class-bonyad-alavi-home-quick-links-widget.php`  
**Elementor name:** `bonyad_alavi_home_quick_links`  
**Reference:** `mohsenfarhangi/bonyad-alavi-redesign/redesign/index.html` → `ba-quick-links`

## Purpose

بخش «دسترسی‌های سریع» صفحه اصلی را بدون redesign به یک Elementor widget مستقل تبدیل می‌کند. baseline بصری و رفتار overflow از HTML/CSS/JS reference گرفته شده و فقط برای قواعد child theme، چند instance Elementor و مدیریت محتوا scope/parameterize شده است.

Assets:
- `assets/css/bonyad-alavi-home-quick-links-widget.css`
- `assets/js/bonyad-alavi-home-quick-links-widget.js`

Registration:
- `inc/elementor/elementor-widgets.php`
- style/script فقط register می‌شوند و با `get_style_depends()` / `get_script_depends()` load می‌شوند.

## Reference Markup Contract

کلاس‌های اصلی:
- `ba-quick-links`
- `ba-container`
- `ba-quick-links__inner`
- `ba-quick-links__list`
- `ba-quick-links__item`
- `ba-quick-links__media`
- `ba-quick-links__icon`
- `ba-quick-links__label`
- `ba-quick-links__item--more`

`ba-home-quick-links-widget` فقط root scope است و جای کلاس‌های reference را نمی‌گیرد.

`id="services"` روی inner برای navigation داخلی صفحه حفظ می‌شود. IDهای `quickLinks/moreQuick/moreText` به data attribute تبدیل شده‌اند چون چند instance Elementor نباید global ID collision داشته باشد.

## Repeater Contract

هر item:
- Label
- URL
- Elementor Icon override
- Media color
- internal `default_icon_key`

Defaultها همان ۹ آیتم reference هستند:

| Key | Label | Icon | Color |
|---|---|---|---|
| people | مشارکت مردمی | tabler:users-group | #0f8a57 |
| organization | مشارکت سازمانی | tabler:building-community | #2f6fa3 |
| auction | مزایده و مناقصه | tabler:gavel | #b7791f |
| jihadi | گروه های جهادی | tabler:heart-handshake | #b44c5e |
| studies | مطالعات راهبردی | tabler:report-analytics | #6658a6 |
| habib | طرح حبیب | tabler:mosque | #247a70 |
| award | جایزه ملی علوی | tabler:award | #b57b0d |
| hamgam | طرح همگام | tabler:route | #3f7eaf |
| camp | مجتمع اردوگاهی | tabler:tent | #678a43 |

Default SVG داخل widget map نگهداری می‌شود. اگر Elementor Icon انتخاب شود، همان آیکون جای SVG reference را می‌گیرد.

رنگ semantic در CSS reference باقی می‌ماند. `media_color` فقط وقتی با رنگ reference فرق کند inline `background-color/border-color` ایجاد می‌کند.

## More / Overflow Behavior

Reference algorithm حفظ شده:
1. همه itemها موقتاً visible می‌شوند.
2. More مخفی می‌شود و مجموع width + gap اندازه‌گیری می‌شود.
3. اگر همه جا شوند، More مخفی می‌ماند.
4. اگر overflow باشد، More نمایش داده و width خودش رزرو می‌شود.
5. itemها تا اولین overflow شمرده می‌شوند؛ بقیه `is-hidden-overflow` می‌گیرند.
6. click روی More → `is-expanded` + همه itemها visible + متن «جمع کردن».
7. click دوباره یا resize → collapse + متن «بیشتر».

تغییر معماری نسبت به source فقط scope است:
- queryها از root همان widget instance انجام می‌شوند.
- `WeakMap` برای cleanup/re-init Elementor استفاده می‌شود.
- Elementor hook: `frontend/element_ready/bonyad_alavi_home_quick_links.default`.

## Responsive Contract

### Desktop
- section padding-top = 28px
- list gap = 4px
- item = 116px
- media = 58px
- icon = 29px
- label = 11px
- min list height = 100px
- centered list

### <= 1080px
- section padding-top = 24px

### <= 760px
- section padding-top = 20px
- container side space equivalent reference `100% - 24px`
- list justify-content = flex-start
- item = 98px
- media = 57px

### <= 430px
- item = 86px
- label = 10px

## Style Tab Contract

کنترل‌ها وجود دارند ولی **هیچ visual default Elementor** ندارند؛ تا وقتی مدیر چیزی تغییر ندهد CSS reference authoritative است.

Controls:
- section top spacing
- list gap
- item width/gap/padding/radius
- item hover vertical shift
- label typography/color
- media size/radius/shadow
- icon size
- More normal/expanded colors

رنگ اختصاصی هر item در Content Repeater قرار دارد چون بخشی از semantic identity همان item است.

## CSS Scope

Reference selectors زیر `.ba-home-quick-links-widget` scope شده‌اند تا با template و سایر Elementor widgets تداخل نداشته باشند. global body/html/a/button override وجود ندارد.

## QA

Regression:
`wp-content/themes/ostadsho-child/tests/home-quick-links-widget-contract.php`

Guards:
- Elementor slug
- Repeater fields
- 9 reference defaults
- 9 SVGs + 9 semantic colors
- reference classes + #services anchor
- desktop/mobile dimensions
- More labels
- overflow algorithm
- root-scoped JS + Elementor hook
- custom icon/color override
- asset registration
- scoped CSS

در session پیاده‌سازی، source commit‌شده با static contract checks بررسی و JavaScript توسط parser V8 parse شد. PHP lint و live WordPress/Elementor acceptance هنوز باز است.

## Live Acceptance Checklist

1. Widget در دسته «بنیاد علوی» دیده شود.
2. ۹ default item دقیقاً با ترتیب/reference icon/color نمایش داده شوند.
3. desktop وقتی همه itemها جا می‌شوند More مخفی باشد.
4. در عرض کمتر، More فقط به‌اندازه لازم itemها را مخفی کند.
5. «بیشتر» همه itemها را wrap کند و متن «جمع کردن» شود.
6. resize حالت را دوباره collapse و محاسبه کند.
7. breakpointهای 1080/760/430 با reference مقایسه شوند.
8. تغییر Color در Repeater فقط همان item را تغییر دهد.
9. انتخاب Elementor Icon فقط SVG همان item را جایگزین کند.
10. چند instance در Editor listener/state مشترک نداشته باشند.

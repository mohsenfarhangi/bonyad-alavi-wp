# MODULE-HOME-QUICK-LINKS — Homepage Quick Links Elementor Widget

**Status:** implemented / live acceptance pending  
**Path:** `wp-content/themes/ostadsho-child/inc/elementor/widgets/class-bonyad-alavi-home-quick-links-widget.php`  
**Elementor name:** `bonyad_alavi_home_quick_links`  
**Reference:** `mohsenfarhangi/bonyad-alavi-redesign/redesign/index.html` → `ba-quick-links`

## Purpose

بخش «دسترسی‌های سریع» صفحه اصلی را به یک Elementor widget مستقل تبدیل می‌کند؛ چیدمان موبایل طبق تأیید کاربر در 2026-10-08 به شش ستون و دکمه زیر لینک‌ها اصلاح شده است. baseline بصری و رفتار overflow از HTML/CSS/JS reference گرفته شده و فقط برای قواعد child theme، چند instance Elementor و مدیریت محتوا scope/parameterize شده است.

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

Desktop overflow algorithm remains adaptive: measure all item widths plus gaps, reserve More only when required, and hide trailing items that do not fit. Widths from bounding rectangles are normalized to the container's CSS-pixel scale so page zoom does not mix measurement units.

Mobile <=760px uses six real links in the collapsed state, or all available links when fewer than six exist. More appears only when there are additional links, spans the row below the links, and toggles «بیشتر» / «جمع کردن».

Expansion is the user's choice: scrolling, height-only resize, width resize, font loading and Elementor re-init do not collapse it. Width changes and breakpoint changes recompute layout while preserving the choice. `ResizeObserver` monitors the list width; height-only changes do not trigger unnecessary recalculation.

- Queries and listeners are scoped to each widget root.
- `WeakMap` stores expansion plus cleanup; re-init removes old listeners, observer and pending animation frame before restoring the choice.
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
- desktop container retains its original centered width: min(1280px, 100% - 40px)
- list uses `justify-content: space-between`; first/last item boxes align to the container edges and remaining space is shared equally between items

### <= 1080px
- section padding-top = 24px

### <= 760px
- section padding-top = 20px
- container/inner use the full available width without their own side gutters
- list uses six equal grid columns with zero column gap and 10px row gap
- items fill their column; labels wrap at 10px / line-height 1.6
- media size = clamp(36px, 10vw, 44px); icons = 22px
- More spans all six columns below the links, with at least 36px height
- expanded state shows all items on additional grid rows
- the fixed six-column mobile geometry overrides Elementor item width, icon size and label font-size settings at this breakpoint

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

2026-10-08 verification:
- User correction: full-section container width was reverted; equal spacing stays inside the original desktop container. Live preview at 1440px measured a 1280px container, zero item gaps at its edges and eight equal 47.5px interior gaps. Mobile rules and JS unchanged.
- Follow-up equal-spacing CSS preview: at 1369px, container and outer item gaps were 0px with eight equal 56.75px interior gaps; at 900px, seven links plus More had equal ~12.14px gaps and zero outer gaps. At 390px, six 62.5px columns and More below remained intact with no horizontal overflow. All 18 contract checks PASS after the CSS-only follow-up.
- PHP lint and all 18 existing contract checks PASS. The existing malformed PHP string for the custom color markup was escaped so the test could execute; the old 98/86px mobile assertion now checks the approved six-column layout.
- JavaScript parsed successfully using Chrome V8 `Runtime.compileScript`.
- Saved CSS/JS were injected as a temporary preview in the live Chrome page, using a clone of the existing widget to isolate the original script's listeners.
- At actual viewport widths 320 and 390: six collapsed links, full-width More below, no internal horizontal overflow. At 900: adaptive desktop layout showed seven links plus More; at 1366: all nine links with More hidden.
- Expanded nine-link state survived simulated mobile height changes, scrolling and width changes. Browser fixtures with 0/3/6/9 real items verified visibility, More availability, single-click toggling and re-init state preservation.
- No server deployment, physical-phone toolbar test or full Elementor editor acceptance performed.

## Live Acceptance Checklist

1. Widget در دسته «بنیاد علوی» دیده شود.
2. ۹ default item دقیقاً با ترتیب/reference icon/color نمایش داده شوند.
3. desktop وقتی همه itemها جا می‌شوند More مخفی باشد.
4. در عرض کمتر، More فقط به‌اندازه لازم itemها را مخفی کند.
5. «بیشتر» همه itemها را wrap کند و متن «جمع کردن» شود.
6. اسکرول و resize وضعیت باز را حفظ کنند؛ فقط کلیک «جمع کردن» آن را ببندد.
7. موبایل <=760 شش لینک و دکمه تمام‌عرض زیر آن‌ها داشته باشد؛ desktop adaptive overflow حفظ شود.
8. تغییر Color در Repeater فقط همان item را تغییر دهد.
9. انتخاب Elementor Icon فقط SVG همان item را جایگزین کند.
10. چند instance در Editor listener/state مشترک نداشته باشند.

# MODULE-JIHADI-CENTER — Jihadi Center Page

**Path:** `wp-content/themes/ostadsho-child/`  
**Status:** implemented

## Purpose

صفحه «مرکز حرکت‌های مردمی و جهادی» با Dashboard settings + Elementor widget مدیریت می‌شود.

Sections:
1. Hero
2. Stats
3. Intro / System Cards
4. News
5. Media
6. Partners
7. FAQ

## Main Components

- `inc/services/class-ba-center-settings-service.php`
- `inc/services/class-ba-content-query-service.php`
- `inc/admin/settings/class-ba-center-settings-tab.php`
- `inc/elementor/widgets/class-bonyad-alavi-jihadi-center-widget.php`
- `inc/helpers/class-ba-media-helper.php`
- related frontend CSS/JS

Decision: [ADR-004](../decisions/ADR-004-jihadi-center-source-precedence.md)

## Source Resolution

Effective settings فقط از:
`BA_Center_Settings_Service::get_effective_settings()`

Current contract:
- Dashboard و Elementor هر دو content/data/query source دارند.
- بعد از schema جدید، Dashboard key ذخیره‌شده بر Elementor اولویت دارد.
- empty Dashboard value در schema جدید معتبر است و الزاماً fallback نیست.
- Hero image: Dashboard valid image → Elementor → theme default.
- pre-0.4.0 Partners/FAQ legacy compatibility حفظ می‌شود.
- Style controls همچنان Elementor-owned هستند.

Section switches:
`hero_enabled`, `stats_enabled`, `intro_enabled`, `news_enabled`, `media_enabled`, `partners_enabled`, `faq_enabled`.

## Queries

News/Media query config می‌تواند از هر source بیاید، اما `WP_Query` args فقط توسط `BA_Content_Query_Service` ساخته شوند.

Supported historical controls شامل post type، limit، category/tag/author، include/exclude IDs، order/order-by، offset، sticky behavior و date range است.

## System Cards

Field-level precedence:
- non-empty Dashboard title/link/valid media می‌تواند override کند.
- field خالی Dashboard برای compatibility می‌تواند Elementor field متناظر را نگه دارد.

Media modes:
- `image` → normal image / `<img>`
- `svg/icon` → Elementor Icons یا sanitized inline SVG

Dashboard storage جدید:
`media_type + image_id + svg_id`; `icon_id` فقط legacy migration fallback.

Inline SVG:
- فقط SVG attachment
- XML declaration/DOCTYPE remove
- allowlist sanitize با `wp_kses()`
- output non-focusable/aria-hidden برای decorative icon

Base SVG selector forced `fill` ندارد؛ color/currentColor authority است. Hover/focus icon color کنترل مستقل دارد.

## Partners

Partner item فقط وقتی معتبر است که حداقل image، SVG/icon یا title داشته باشد. URL تنها نباید card خالی ایجاد کند.

## Performance / Reveal

Behavior hook:
`data-ba-jc-reveal`

- Hero eager باقی می‌ماند و برای LCP lazy/content-visibility نمی‌شود.
- sections پایین Modifier lazy دارند.
- `content-visibility:auto` + `contain-intrinsic-size`.
- IntersectionObserver one-shot reveal.
- Web Animations API fade-in در صورت availability.
- بدون APIها content visible می‌ماند.
- `prefers-reduced-motion` animation را disable می‌کند.
- Elementor Editor lazy/reveal restrictions را برای editability غیرفعال می‌کند.

## Hero / Stats Responsive Controls

Stats:
- responsive four-side margin
- legacy `stats_margin_top` برای compatibility؛ new margin در صورت set اولویت CSS دارد.

Hero:
- responsive height
- image object-fit
- object-position
- opacity
- CSS filters
- responsive image width/height
- media container explicit 100% dimensions و overflow hidden
- image absolute/inset 0 و max-size constraints neutralized تا object-fit قابل اعتماد باشد.

## News / Media Image Ratios

Responsive aspect-ratio controls مستقل برای:
- featured news
- secondary news
- featured media
- secondary media

options legacy:
1:1، 6:5، 4:3، 3:2، 16:9، 21:9، 3:4، 2:3، 9:16.

Fixed heights حذف شده‌اند؛ inner images `object-fit:cover`.

Shared Elementor helper برای aspect controls استفاده شود تا options/selectors duplicate نشوند.

## News / Media Text Styles

Featured/secondary News و Media هر کدام:
- title typography
- date typography
- normal title/date colors
- hover/focus-visible title/date colors

featured media date بخشی از markup استاندارد است.

Shared helper برای text style controls استفاده شود.

## Admin Integration

Center tab settings save/asset contract تابع [MODULE-THEME-ADMIN](theme-admin-settings.md) و [ADR-003](../decisions/ADR-003-shared-theme-settings-persistence.md) است.

Dashboard repeaters باید shared admin Repeater را مصرف کنند؛ Elementor repeater مستقل است.

## Regression Areas

- source precedence + empty semantics
- legacy partners/FAQ compatibility
- system-card field fallback
- image vs SVG mode/sanitize
- section enable switches
- query service usage
- no forced SVG fill
- hover/focus parity
- Hero eager/LCP
- reduced motion/Elementor Editor
- responsive hero/stats/image ratio/text controls

## History

v0.3.0 تا v0.5.9 و v0.5.7 handoff-only milestone در [state-v002](../state/state-v002.md) خلاصه شده‌اند.

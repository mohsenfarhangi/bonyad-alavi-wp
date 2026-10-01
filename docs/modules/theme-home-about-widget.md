# MODULE-HOME-ABOUT — Homepage About/Impact Elementor Widget

**Status:** implemented / live acceptance pending  
**Path:** `wp-content/themes/ostadsho-child/inc/elementor/widgets/class-bonyad-alavi-home-about-widget.php`  
**Elementor name:** `bonyad_alavi_home_about`  
**Reference:** `mohsenfarhangi/bonyad-alavi-redesign/redesign/index.html` → `#about / .ba-impact`

## Purpose

سکشن معرفی بنیاد را بدون تغییر UI/UX به Elementor widget تبدیل می‌کند. ساختار final reference شامل content pane و funding pane یکپارچه حفظ شده، اما داده‌های منابع و Summary از حالت hard-coded خارج شده‌اند.

Assets:
- `assets/css/bonyad-alavi-home-about-widget.css`
- `assets/js/bonyad-alavi-home-about-widget.js`

## Reference Structure

کلاس‌های اصلی:
- `ba-impact`
- `ba-container ba-impact__grid`
- `ba-impact__content`
- `ba-section-heading ba-section-heading--stack`
- `ba-impact__actions`
- `ba-impact__funding`
- `ba-impact__funding-head`
- `ba-impact__funding-visual`
- `ba-impact__chart-wrap / chart / donut-track / donut-segment`
- `ba-impact__donut-center`
- `ba-impact__source-card`
- `ba-impact__summary`

`ba-home-about-widget` فقط root scope است.

## Content Controls

Intro:
- eyebrow
- title
- description

Actions Repeater:
- label
- URL
- type = primary / secondary

سه action reference default هستند.

Funding header:
- kicker
- note
- unit
- display decimals
- donut center caption

## Funding Sources Repeater

هر source:
- label
- numeric value
- color
- internal reference key فقط برای چهار default

چهار default:
- Foundation: 14.8 / #06783a
- Banks: 9.6 / #128a70
- Organizations: 5.4 / #487e72
- Stakeholders: 3.2 / #b28a42

تعداد source محدود نیست؛ add/remove/reorder مجاز است.

## Dynamic Donut Geometry

برای هر source:
1. `share = round(value / total * 100, 2)`
2. `dash = max(0, round(share - 1, 2))`
3. `offset = -cumulativeShare`
4. `angle = round((cumulativeShare + dash/2) * 3.6, 2)`
5. cumulative share بعد از هر source به دو رقم اعشار round می‌شود.

این دقیقاً geometry default reference را بازتولید می‌کند:

| Source | Dash | Offset | Angle |
|---|---:|---:|---:|
| Foundation | 43.85 | 0 | 78.93 |
| Banks | 28.09 | -44.85 | 212.02 |
| Organizations | 15.36 | -73.94 | 293.83 |
| Stakeholders | 8.70 | -90.30 | 340.74 |

CSS variables per segment:
- `--ba-impact-segment-dash`
- `--ba-impact-segment-offset`
- `--ba-impact-segment-color`
- `--ba-impact-segment-delay`

Source card variables:
- `--ba-impact-source-color`
- `--ba-impact-card-delay`

اگر total صفر باشد، segmentها صفر و card angles به‌صورت یکنواخت پخش می‌شوند تا layout قابل استفاده بماند.

## Summary Contract

Global switch:
- show/hide whole Summary

Summary Repeater:
- per-item visible switch
- label
- Elementor icon override
- default SVG key
- value type:
  - `funding_total`: مجموع خودکار source values
  - `manual`: عدد دستی
- decimals
- suffix

Default items:
1. مجموع منابع (همت) → auto total، 1 decimal، chart SVG
2. تعداد خدمات‌گیرندگان مستقیم → manual 3250000، 0 decimal، users SVG

آیتم‌ها قابل add/remove/reorder هستند.

## Number Formatting

Initial HTML با formatter PHP از ارقام فارسی، decimal separator `٫` و thousands separator `٬` استفاده می‌کند.

Animation JS از:
`Intl.NumberFormat('fa-IR')`

استفاده می‌کند؛ بنابراین static و animated state از نظر locale هماهنگ‌اند.

## JavaScript Contract

رفتار reference حفظ شده:
- counter animation با duration 1150ms
- stagger = 55ms
- hover بین source card و donut segment با `data-impact-source`
- dynamic source-card positioning از angle
- connector geometry
- resize با requestAnimationFrame
- reveal با IntersectionObserver threshold=.3
- reduced-motion → مقدار نهایی بدون animation

Theme adaptation:
- root-scoped query
- WeakMap state/cleanup
- Elementor frontend hook
- no global selector assumptions

## Responsive Contract

Desktop:
- section 68px/64px
- frame: `1.12fr + minmax(420px,.88fr)`
- content padding 42/42/38
- funding padding 26/24/22
- donut width min(68%,310px)
- source card 154x min68

<=1080:
- section 58/56
- frame one column
- content padding 32/30/30
- funding top border + 24/22/22
- visual min-height430
- donut min(58%,320px)

<=760:
- section 46/44
- frame radius18
- content 24/20/22
- actions two-per-row
- funding 16/14/14
- source cards become 2-column normal-flow grid
- donut min(82%,320px)

<=430:
- content 20/15/18
- buttons full width
- funding 12/10/10
- source cards one column
- donut min(88%,300px)
- summary one column

## Style Controls

Style controls exist without visual defaults, so untouched instances keep reference CSS:
- section/frame
- intro typography/colors/content padding
- action gap/button typography and primary/secondary colors
- funding background/padding/kicker/note/track/center/total
- source card background/radius/typography
- summary background/radius/typography/icon color

## QA

Regression:
`wp-content/themes/ostadsho-child/tests/home-about-widget-contract.php`

Static checks cover:
- widget/asset registration
- reference structure/default copy
- actions/source/summary Repeaters
- dynamic geometry formula
- exact default geometry
- dynamic CSS variables
- default Summary SVGs
- Persian formatting
- final framing/responsive CSS
- reference JS behaviors
- multi-instance/Elementor hook

در session پیاده‌سازی JavaScript با parser V8 بدون خطا parse شد. PHP lint و live WordPress/Elementor visual/interaction acceptance هنوز باز هستند.

# MODULE-HOME-ABOUT — Homepage About/Impact Elementor Widget

**Status:** implemented / live acceptance pending  
**Path:** `wp-content/themes/ostadsho-child/inc/elementor/widgets/class-bonyad-alavi-home-about-widget.php`  
**Elementor name:** `bonyad_alavi_home_about`  
**Reference:** `mohsenfarhangi/bonyad-alavi-redesign/redesign/index.html` → `#about / .ba-impact`

## Purpose

سکشن معرفی بنیاد را بدون تغییر ساختار محتوایی به یک Elementor widget مستقل تبدیل می‌کند. Intro، CTAها، Funding Sources و Summary از Elementor مدیریت می‌شوند. نمودار Donut و layout لیبل‌های بیرونی از 2026-10-02 توسط Apache ECharts مدیریت می‌شوند و الگوریتم custom قبلی برای arc geometry / source-card positioning / connector / collision حذف شده است.

## Assets

Widget assets:
- `assets/css/bonyad-alavi-home-about-widget.css`
- `assets/js/bonyad-alavi-home-about-widget.js`

Local third-party dependency:
- `assets/vendor/echarts/echarts.min.js`
- `assets/vendor/echarts/LICENSE`
- `assets/vendor/echarts/NOTICE`
- `assets/vendor/echarts/README.md`

Vendored version: **Apache ECharts 6.1.0**.

WordPress handle:
- `apache-echarts`

Widget script depends on:
- `elementor-frontend`
- `apache-echarts`

The library is registered locally and is loaded through the widget dependency chain; there is no runtime CDN dependency.

## Reference Structure

Project-owned DOM:
- `ba-impact`
- `ba-container ba-impact__grid`
- `ba-impact__content`
- `ba-section-heading ba-section-heading--stack`
- `ba-impact__actions`
- `ba-impact__funding`
- `ba-impact__funding-head`
- `ba-impact__funding-visual`
- `ba-impact__chart-wrap`
- `ba-impact__chart`
- `ba-impact__donut-center`
- `ba-impact__summary`

`ba-home-about-widget` فقط root scope است.

Old project-owned nodes that no longer exist:
- manual SVG donut circles
- `ba-impact__donut-segment`
- `ba-impact__donut-track`
- `ba-impact__source-card`
- connector pseudo-elements
- `data-js-impact-source`
- `data-js-impact-segment`

ECharts creates its own SVG chart internals inside `ba-impact__chart`.

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

Funding:
- kicker
- note
- unit
- display decimals
- donut center caption

Funding Sources Repeater:
- label
- numeric value
- color

چهار default:
- Foundation: 14.8 / #06783a
- Banks: 9.6 / #128a70
- Organizations: 5.4 / #487e72
- Stakeholders: 3.2 / #b28a42

تعداد source محدود نیست؛ add/remove/reorder مجاز است.

## ECharts Data Contract

PHP داده‌های Repeater را sanitize می‌کند و یک JSON payload داخل:

`<script type="application/json" data-js-impact-chart-data>`

رندر می‌کند.

Payload:
- `unit`
- `decimals`
- `sources[]`
  - `name`
  - `value`
  - `color`

Total funding همچنان server-side از همان source values محاسبه می‌شود و برای donut center و Summary auto-total استفاده می‌شود.

JavaScript:
1. JSON payload را parse می‌کند.
2. ECharts را روی `[data-js-impact-chart]` با SVG renderer init می‌کند.
3. track و data donut را با دو Pie series می‌سازد.
4. label و labelLine را به ECharts می‌سپارد.
5. فقط در resize واقعی chart، `chart.resize()` اجرا می‌شود.

## Chart Contract

Main data series:
- `type: 'pie'`
- `radius: ['42%', '58%']`
- `center: ['50%', '50%']`
- `startAngle: 90`
- dynamic `padAngle`
- `avoidLabelOverlap: true`
- external labels
- managed `labelLine`
- `labelLayout.moveOverlap = 'shiftY'`
- `hideOverlap = false`

Track:
- separate silent Pie series
- same inner/outer radius
- color from `--ba-impact-chart-track`

Rendering:
- `renderer: 'svg'`
- reduced-motion disables ECharts animation
- accessibility description is generated from current sources.

The project does **not** calculate arc dash/offset, segment midpoint, card positions, collision resolution, or connector geometry itself anymore.

## Label Visual Contract

ECharts rich labels reproduce the existing card-like visual language:
- source name
- source-colored value
- source-colored dot
- white/near-white background
- subtle border/radius/shadow
- short source-colored guide line

Default CSS variables:
- `--ba-impact-label-bg`
- `--ba-impact-label-border`
- `--ba-impact-label-name`
- `--ba-impact-label-width`
- `--ba-impact-label-radius`
- `--ba-impact-label-name-size`
- `--ba-impact-label-value-size`
- `--ba-impact-label-line-length`

Elementor Style controls write these variables on `.ba-impact__funding`. JS reads computed values from the same funding pane before building the ECharts option.

## Summary Contract

Global switch:
- show/hide whole Summary

Summary Repeater:
- per-item visible switch
- label
- Elementor icon override
- default SVG key
- value type:
  - `funding_total`: مجموع خودکار منابع
  - `manual`: عدد دستی
- decimals
- suffix

Default items:
1. مجموع منابع (همت) → auto total
2. تعداد خدمات‌گیرندگان مستقیم → manual 3250000

Summary values are server-rendered; no client-side counter synchronization is needed.

## Number Formatting

PHP uses Persian digits with decimal separator `٫` and thousands separator `٬` for the center total and Summary.

ECharts labels use `Intl.NumberFormat('fa-IR')`.

## Responsive Contract

Desktop:
- shared About surface retained
- funding visual min-height 405px
- label width default 132px

<=1080:
- About frame stacks
- funding visual/chart min-height 430px

<=760:
- funding visual/chart min-height 380px
- label width default 116px
- label font sizes reduced

<=430:
- funding visual/chart min-height 350px
- label width default 104px
- Summary becomes one column

ECharts owns label overlap/layout at all breakpoints; there is no separate mobile source-card grid anymore.

## Style Controls

Controls remain opt-in; reference CSS is authoritative until explicitly changed:
- section/frame
- intro
- actions
- funding panel/header
- donut track/center/total
- ECharts label background/border/name color/width/radius/font sizes/guide-line length
- Summary

## Third-party / License

Apache ECharts 6.1.0 is vendored locally as an unmodified browser distribution. Its upstream `LICENSE` and `NOTICE` files are stored beside the vendor file.

No CDN is used at runtime.

## QA

Regression:
`wp-content/themes/ostadsho-child/tests/home-about-widget-contract.php`

Current guards include:
- widget/source/summary Repeater contracts
- JSON chart payload
- local ECharts registration and dependency order
- absence of runtime CDN URLs
- ECharts 6.1.0 vendor/license marker
- Pie + external label + labelLine/overlap configuration
- SVG renderer
- minimal ResizeObserver
- removal of old PHP geometry helpers
- removal of custom collision/connector/midpoint JS
- removal of source-card/donut-segment CSS
- Elementor frontend hook
- multi-instance chart disposal/re-init

Static source checks and JavaScript parsing passed during the migration session. PHP lint and live WordPress/Elementor visual acceptance remain pending.

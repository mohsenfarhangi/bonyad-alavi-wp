# MODULE-HOME-ABOUT — Homepage About/Impact Elementor Widget

**Status:** implemented / live acceptance pending  
**Path:** `wp-content/themes/ostadsho-child/inc/elementor/widgets/class-bonyad-alavi-home-about-widget.php`  
**Elementor name:** `bonyad_alavi_home_about`  
**Reference:** `mohsenfarhangi/bonyad-alavi-redesign/redesign/index.html` → `#about / .ba-impact`

## Purpose

سکشن معرفی بنیاد را به Elementor widget مستقل تبدیل می‌کند. Intro، CTAها، Funding Sources، مقدار مرکز Donut و KPI «تعداد خدمات‌گیرندگان مستقیم» از Elementor مدیریت می‌شوند. Donut و لیبل‌های بیرونی توسط Apache ECharts 6.1.0 محلی رندر می‌شوند.

## Current DOM Contract

Project-owned DOM:
- `ba-impact`
- `ba-container ba-impact__grid`
- `ba-impact__content`
- `ba-section-heading ba-section-heading--stack`
- `ba-impact__actions`
- `ba-impact__funding`
- `ba-impact__funding-visual`
- `ba-impact__chart-wrap`
- `ba-impact__chart`
- `ba-impact__donut-center`
- `ba-impact__funding-sources` / `ba-impact__funding-source` (mobile source list)
- `ba-impact__beneficiaries`
- `ba-impact__beneficiaries-label`
- `ba-impact__beneficiaries-value`

Removed from the current contract:
- `ba-impact__funding-head`
- `ba-impact__funding-kicker`
- `ba-impact__funding-note`
- `ba-impact__summary`
- Summary item/icon/copy DOM
- manual donut SVG/source-card DOM from the pre-ECharts implementation

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

Action hover contract:
- 200ms transition
- maximum 2px lift
- hover background/border = `var(--green-700)`
- hover text = white
- subtle green shadow
- no underline/gold pseudo-element effect
- reduced-motion disables movement/transition

Funding:
- unit
- display decimals
- donut center caption
- Funding Sources Repeater: label / numeric value / color
- beneficiaries label
- beneficiaries numeric value

Defaults:
- Foundation: 14.8 / #06783a
- Banks: 9.6 / #128a70
- Organizations: 5.4 / #487e72
- Stakeholders: 3.2 / #b28a42
- beneficiaries: 3,250,000

There is no Funding heading/note control and no Summary Repeater anymore.

## ECharts Contract

Local dependency:
- `assets/vendor/echarts/echarts.min.js`
- version: 6.1.0
- license: Apache-2.0
- no runtime CDN

Widget script depends on:
- `elementor-frontend`
- `apache-echarts`

PHP emits:
`<script type="application/json" data-js-impact-chart-data>`

Payload:
- `unit`
- `decimals`
- `sources[]`: name/value/color

Main data series:
- `type: 'pie'`
- desktop/tablet `radius: ['42%', '58%']`; mobile <=760 `['60%', '82%']`
- `center: ['50%', '50%']`
- `startAngle: 90`
- dynamic `padAngle`
- `avoidLabelOverlap: true`
- external rich labels only above 760px; mobile uses a server-rendered source list
- `alignTo: 'edge'`
- responsive `edgeDistance`
- `labelLayout.moveOverlap = 'shiftY'`
- ECharts `labelLine`

Track:
- separate silent Pie series
- same radius as data series
- color from `--ba-impact-chart-track`

Rendering:
- SVG renderer
- chart container is `direction:ltr; unicode-bidi:isolate`
- Persian text remains right-aligned inside rich tokens
- لیبل desktop/tablet مقدار رنگی را در خط بالا و عنوان را در خط پایین نمایش می‌دهد؛ نقطه رنگی حذف شده است.
- source names wrap instead of truncate
- reduced-motion disables chart animation
- ResizeObserver plus window resize batch updates with requestAnimationFrame. CSS style signature and mobile breakpoint determine whether to rebuild chart options; geometry-only changes call chart.resize(). Font loading forces a style refresh. Re-init/dispose cleans listeners, observer, pending frame and chart instance.

The project does not calculate arc geometry, midpoint, collision or connector positions itself.

## Beneficiaries KPI

Only one KPI is rendered below the chart:
- label: `تعداد خدمات‌گیرندگان مستقیم`
- numeric value

It is:
- server-rendered
- centered under the chart
- unboxed
- without background, border, icon or card wrapper
- formatted with Persian digits/thousands separator

This replaces the former Summary block entirely.

## Compact Density Contract

Funding responsive contract approved on 2026-10-08:
- desktop chart min-height: 405px (existing styles/Elementor controls retained)
- tablet 761–1080: visual centered at max-width 640px, chart min-height 380px
- mobile <=760: visual max-width 360px, height clamp(240px, 70vw, 280px), chart min-height reset to 0; mobile radius ['60%', '82%']
- desktop/tablet ECharts radius remains ['42%', '58%']; both track and data use the same responsive radius
- mobile external ECharts labels/lines and the legacy hidden chart-values overlay are hidden
- mobile sources are an RTL two-column HTML list between the visual and beneficiaries KPI; only the numeric value uses the corresponding source color, with no dot
- source label 12px, value 16px, unit 11px; long names wrap; no new Elementor controls
- list is rendered from normalized funding_sources with existing decimals/unit formatting and escaping, omitted for no sources and CSS-hidden above 760px
- beneficiaries remain an unboxed KPI below the source list

Compaction comes from removing header/Summary and reducing surrounding spacing:
- desktop section: 56px top / 52px bottom
- <=1080: 50px / 48px
- <=760: 40px / 38px
- <=430: 36px / 34px
- content/funding padding is reduced at all breakpoints

## Style Controls

Controls remain opt-in:
- section/frame
- intro
- actions
- funding panel
- donut track/center/total
- ECharts label visual controls
- beneficiaries label/value typography and colors
- beneficiaries label/value gap

Removed Style controls:
- funding heading/note typography/colors
- Summary background/radius/icon/typography controls

## QA

Regression:
`wp-content/themes/ostadsho-child/tests/home-about-widget-contract.php`

Current guards cover:
- funding/actions Repeaters
- local ECharts contract
- label clipping/RTL configuration
- absence of funding-head controls/DOM/CSS
- absence of Summary controls/render/CSS
- simple beneficiaries controls/render
- compact responsive spacing
- responsive ECharts radius and existing desktop/tablet label behavior
- no custom old donut geometry
- multi-instance chart cleanup

2026-10-08 responsive verification:
- Widget/test PHP lint and all 29 existing contract checks PASS. The stale label-value-first assertion was updated from its removed dot formatter to the current no-dot formatter; baseline failed this assertion before the change.
- Production JS parsed with Chrome V8 Runtime.compileScript.
- Temporary preview injected saved CSS/JS plus equivalent new legend markup into a clone of the live widget using its real JSON payload. No server deployment performed.
- Actual widths 320/390/425 showed two source columns, 12px names, colored 16px amounts, hidden external labels, correct source colors and no funding horizontal overflow.
- At 900, chart measured 640x380 and external label font returned to 11px after resizing from mobile. At 1366, external labels and radius 42/58 returned correctly; mobile list was hidden. Returning to 390 restored the mobile layout.
- A second-instance fixture verified re-init disposes the old chart, leaves exactly one SVG, keeps the main instance alive and disposes cleanly.
- Physical phone and full Elementor editor acceptance remain pending. PHP legend markup lint/contract were checked locally; browser preview is not a deployed PHP-rendered acceptance test.

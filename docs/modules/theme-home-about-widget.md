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
- `radius: ['42%', '58%']`
- `center: ['50%', '50%']`
- `startAngle: 90`
- dynamic `padAngle`
- `avoidLabelOverlap: true`
- external rich labels
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
- source names wrap instead of truncate
- reduced-motion disables chart animation
- ResizeObserver only triggers `chart.resize()`

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

Donut radius and chart heights are intentionally unchanged:
- desktop chart min-height: 405px
- tablet: 430px
- mobile: 380px
- <=430: 350px
- ECharts radius remains `['42%', '58%']`

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
- unchanged chart heights / ECharts radius
- no custom old donut geometry
- multi-instance chart cleanup

Static source checks and JavaScript parsing passed in the implementation session. PHP lint could not be executed because the shell environment could not resolve GitHub raw content; live Elementor acceptance remains pending.

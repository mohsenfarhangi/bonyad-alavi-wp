# Current Project State

آخرین بازبینی: **2026-09-29**

## Project Phase

پروژه در توسعه فعال است. دو خط اصلی کد سفارشی وجود دارد:

- **Alavi Form Engine:** `1.0.28-dev`، در وضعیت feature-complete/pre-acceptance طبق مستندات داخلی؛ stable production baseline هنوز `1.0.27` است.
- **Ostadsho child theme customizations:** آخرین feature patch مستند `v0.6.5` است؛ Theme header فایل `style.css` نسخه `2.8` دارد و version scheme داخلی featureها جداست.

Baseline code HEAD قبل از bootstrap مستندات:
`2b7cc8fecb8be39d40c2562349ea8c0b6adce12e` روی `main`.

## Current Goal

هدف جاری repository-level این است که توسعه theme/plugin بدون وابستگی به history گفتگو قابل ادامه باشد. سیستم persistent context راه‌اندازی شده و task محصولی بعدی باید از این snapshot و module routerها شروع شود.

برای AFE، هدف release همچنان تکمیل live acceptance نسخه 1.0.28 و رفع هر blocker واقعی قبل از promotion به stable است. برای theme، آخرین مسئله مستند Quick Checkout در v0.6.5 اصلاح JavaScript country/state initialization بوده است.

## Recently Completed

### Alavi Form Engine — latest repository commit

Commit `2b7cc8f` تغییرات زیر را ثبت کرده است:

- نمایش/ویرایش admin برای داده‌های maskدار به raw normalized value نزدیک شده و mask فقط روی presentation فرانت اعمال می‌شود.
- preset شبا 24 رقمی بدون فاصله شده است.
- Preview فرانت می‌تواند mask را صریحاً برای نمایش اعمال کند.
- per-form custom CSS مستقیماً همراه render form/locked preview به `<style>` خروجی داده می‌شود تا به style handle نامرتبط وابسته نباشد.
- regression tests مربوط به input mask به‌روزرسانی شده‌اند.
- `docs/PROJECT_STATE.md` ماژول AFE به repository اضافه شده است.

### Child Theme

Feature patch `v0.6.5` Trigger دستی eventهای داخلی WooCommerce برای country/state را حذف و initialization را به `change` واقعی billing country داخل Quick Checkout محدود کرده است.

## Currently In Progress

هیچ feature branch یا task نیمه‌تمام قابل اثبات از وضعیت repository دیده نشد. کار باز اصلی AFE طبق مستندات داخلی **live acceptance** نسخه 1.0.28 است، نه توسعه یک feature مشخص جدید.

## Blocked / Known Issues

- AFE production promotion به `1.0.28` تا PASS شدن acceptance واقعی WordPress + MySQL/MariaDB + browser + Elementor انجام نشود.
- مستند `PROJECT_STATE.md` ماژول AFE هنوز live checks مهمی را برای Excel collision در حضور pluginهای دیگر، PDF فارسی و export template overrides در اولویت می‌داند.
- `vendor/` AFE در Git track نمی‌شود؛ deployment/export به build صحیح Composer و PDF font metadata وابسته است.
- نتیجه QA checkpoint 11.3 برای آخرین HEAD پس از commit `2b7cc8f` دوباره در این bootstrap اجرا نشده است. PASS جدید برای HEAD فعلی ادعا نشود.
- WordPress core، parent theme و third-party plugins در این repo موجود نیستند؛ برای integration testing محیط کامل لازم است.

## Important Active Decisions

- [ADR-001 — Code-defined forms remain source of truth](../decisions/ADR-001-code-defined-forms.md)
- [ADR-002 — Isolated participation quick checkout](../decisions/ADR-002-participation-quick-checkout.md)

Constraints مهم:
- AFE runtime assets از CDN ثبت نشوند.
- admin UI فرم نباید raw PHP/JS executable را به‌عنوان schema extension بپذیرد.
- gateway id مشارکت case-sensitive است و persist آن نباید با `sanitize_key()` case را تغییر دهد.
- Quick Checkout فعلی فقط billing fields را نمایش/validate می‌کند و CSS force width باید scope شده بماند.

## Relevant Files

### Repository context
- [../../AGENTS.md](../../AGENTS.md)
- [../INDEX.md](../INDEX.md)
- [../ARCHITECTURE.md](../ARCHITECTURE.md)

### AFE
- `../../wp-content/plugins/alavi-form-engine/alavi-form-engine.php`
- `../../wp-content/plugins/alavi-form-engine/docs/PROJECT_STATE.md`
- `../../wp-content/plugins/alavi-form-engine/docs/ARCHITECTURE.md`
- `../../wp-content/plugins/alavi-form-engine/handoff.md`
- `../../wp-content/plugins/alavi-form-engine/tools/qa.sh`

### Theme
- `../../wp-content/themes/ostadsho-child/functions.php`
- `../../wp-content/themes/ostadsho-child/docs/handoff.md`
- `../../wp-content/themes/ostadsho-child/docs/versions/v0.6.5.md`

## Tests Status

### AFE

Repository documentation for checkpoint 11.3 records:

- 57/57 regression tests PASS
- 168 PHP files lint PASS
- JavaScript syntax PASS
- composer.json PASS
- runtime CDN check PASS
- extracted ZIP QA/integrity PASS

این نتایج checkpoint 11.3 هستند. آخرین repository commit بعداً input-mask/custom-CSS code را تغییر داده؛ این bootstrap تست‌ها را execute نکرده است، بنابراین وضعیت full QA برای HEAD فعلی **unverified** است.

### Theme

مستند v0.6.5 اجرای `node --check` روی JavaScriptها، `php -l` روی PHPها و ZIP checks را به‌عنوان تست آن patch ثبت می‌کند. در bootstrap حاضر دوباره اجرا نشده‌اند.

## Next Actions

1. برای هر task جدید ابتدا domain را از [modules/INDEX.md](../modules/INDEX.md) انتخاب کن.
2. اگر task AFE است، قبل از patch علاوه بر module guide، `docs/PROJECT_STATE.md` و فقط مستند تخصصی مرتبط را بخوان.
3. پس از تغییر AFE، `tools/qa.sh` و تست‌های domain مرتبط را اجرا کن؛ اگر export در scope است build/vendor checks را هم لحاظ کن.
4. اگر task theme است، `docs/handoff.md` و version/component مرتبط را بخوان و syntax/integration checks مناسب را اجرا کن.
5. بعد از کار معنی‌دار CURRENT، state history و module/ADR مرتبط را به‌روزرسانی کن.

## Next Agent Handoff

**Current task:** هیچ task محصولی باز به‌طور قابل اثبات در repository ثبت نشده؛ persistent context آماده است تا task بعدی از روی repo ادامه یابد.

**Start here:** [../INDEX.md](../INDEX.md) سپس [../modules/INDEX.md](../modules/INDEX.md).

**Already decided:** قرارداد Source Definition/Overrides در AFE و cart isolation پرداخت مشارکت را بدون شواهد یا requirement جدید redesign نکن.

**Do not assume:** PASS بودن QA روی آخرین HEAD، availability بودن vendor در deployment، یا PASS شدن acceptance زنده.

**Immediate handoff rule:** فقط context مربوط به subsystem درخواستی را load کن؛ تاریخچه کامل AFE/theme یا همه stateها را به‌صورت پیش‌فرض نخوان.

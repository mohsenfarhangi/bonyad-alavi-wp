# MODULE-AFE — Alavi Form Engine

**Status:** active development / pre-acceptance  
**Path:** `wp-content/plugins/alavi-form-engine/`  
**Namespace:** `BonyadAlavi\FormEngine`  
**Development version:** `1.0.28-dev`  
**Stable version documented by module:** `1.0.27`  
**PHP:** `>=8.3`

## Purpose

Code-first extensible WordPress form engine با form rendering، submission persistence، validation، file handling، duplicate detection، actions/events/tokens، exports، data sources، admin/reporting و Elementor integration.

## Structure

Top-level source domains:

`Actions, Admin, Core, DataSource, Database, Duplicate, Elementor, Events, Export, Form, Forms, InputMask, Localization, Repository, Rest, Security, Style, Submission, Template, Validation`.

Bootstrap:
`alavi-form-engine.php -> Core\Plugin::boot()`

Critical bootstrap classes قبل از boot preflight می‌شوند. plugin PSR-4 fallback داخلی را حتی در حضور Composer نگه می‌دارد تا vendor ناقص مانع load کلاس‌های `src/` نشود.

## Core Contracts

- source form definition + allowed overrides؛ [ADR-001](../decisions/ADR-001-code-defined-forms.md)
- server-side validation/normalization authority
- file ownership با submission/field relationship
- trashed submissions از active scopes خارج
- per-form capabilities در کنار global capabilities
- action/event/token registries و persisted execution/once guards
- browser runtime assets local؛ CDN registration مجاز نیست
- Input Mask presentation/UX است؛ normalized value باید قبل از validation/duplicate/actions/storage استفاده شود.
- export dependencies از Composer build می‌آیند، ولی `vendor/` در Git track نمی‌شود.

## Current Focus / Risks

مستند داخلی AFE نسخه 1.0.28 را feature-complete/pre-acceptance توصیف می‌کند. promotion به production تا live acceptance مجاز نیست.

اولویت‌های live acceptance ثبت‌شده:
- Excel export در حضور Composer/vendor افزونه‌های دیگر
- PDF فارسی/RTL و font assets
- export template overrides
- live duplicate behavior

Latest baseline commit `2b7cc8f` admin mask presentation را raw نگه می‌دارد، frontend preview را برای mask presentation opt-in می‌کند، IBAN 24 رقمی را بدون فاصله می‌کند و custom CSS هر form را همراه markup render می‌کند.

## Tests

`tools/qa.sh` unified entrypoint است و regression PHP scripts، PHP lint، JS syntax، composer JSON، local assets، CDN registration و version consistency را بررسی می‌کند.

در repository فعلی 57 فایل regression test وجود دارد. مستند checkpoint 11.3 نتیجه 57/57 PASS را ثبت می‌کند، ولی bootstrap حاضر QA را روی latest HEAD اجرا نکرده است.

## Read Next Only When Needed

Primary module state:
- `../../wp-content/plugins/alavi-form-engine/docs/PROJECT_STATE.md`

Architecture:
- `../../wp-content/plugins/alavi-form-engine/docs/ARCHITECTURE.md`

Developer details:
- `../../wp-content/plugins/alavi-form-engine/docs/DEVELOPER.md`

Release/acceptance:
- `../../wp-content/plugins/alavi-form-engine/docs/ACCEPTANCE-1.0.28.md`
- `../../wp-content/plugins/alavi-form-engine/BUILD-REPORT.md`

Historical handoff:
- `../../wp-content/plugins/alavi-form-engine/handoff.md`

برای task مشخص، بعد از این router فقط فایل‌های source/test همان domain را باز کن.

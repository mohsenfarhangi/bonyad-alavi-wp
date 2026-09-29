# State v001 — Persistent Context Bootstrap

Period: **2026-09-29**

## STATE-001-001 — Repository context system initialized

Date: 2026-09-29

Summary:
- repository-level `AGENTS.md` و ساختار `docs/` برای progressive context loading ایجاد شد.
- `CURRENT.md` به‌عنوان operational snapshot و این فایل به‌عنوان state history اولیه تفکیک شدند.
- indexes برای state، decisions و modules ساخته شدند.
- `.gitignore` طوری تغییر کرد که `AGENTS.md` و `docs/**` در کنار custom WordPress code track شوند.

Reason for state entry:
این تغییر نحوه ادامه پروژه توسط agentهای بعدی را به‌طور معنی‌دار تغییر می‌دهد و باید recoverable باشد.

## STATE-001-002 — Repository baseline identified

Date: 2026-09-29

Branch:
`main`

Baseline commit inspected:
`2b7cc8fecb8be39d40c2562349ea8c0b6adce12e`

Repository scope observed:
- `wp-content/plugins/alavi-form-engine`
- `wp-content/themes/ostadsho-child`
- `wp-content/themes/PATCH-MANIFEST.md`

WordPress core و سایر افزونه‌ها/قالب‌ها در repository root tracking مشاهده نشدند. `.gitignore` عمداً repository را به custom areas محدود می‌کند.

## STATE-001-003 — AFE development state captured

Date: 2026-09-29

Facts verified from repository:
- Plugin header: `1.0.28-dev`
- PHP requirement: `>=8.3`
- DB constant: `1.0.5-dev.2`
- Composer packages: PhpSpreadsheet 5.9.0، ZipStream 3.2.2، tc-lib-pdf 8.73.6
- 20 top-level source domains زیر `src/`
- 57 regression test scripts موجود در `tests/`
- unified QA entrypoint: `tools/qa.sh`

Internal project state says stable remains 1.0.27 and 1.0.28 must not be promoted before live acceptance.

Latest baseline commit `2b7cc8f` modified admin mask presentation, frontend preview formatting, IBAN mask, per-form custom CSS rendering and related tests.

Related:
- [MODULE-AFE](../modules/alavi-form-engine.md)
- [ADR-001](../decisions/ADR-001-code-defined-forms.md)

## STATE-001-004 — Child theme state captured

Date: 2026-09-29

Facts verified:
- Child theme loads admin/settings, Elementor, services, shortcodes and WooCommerce subsystems from `functions.php`.
- theme header reports version 2.8.
- internal feature documentation contains versions through `v0.6.5`.
- v0.6.5 fixes WooCommerce country/state initialization in inline participation checkout.
- theme docs include a shared handoff, version history and an admin repeater component guide.

Related:
- [MODULE-THEME](../modules/ostadsho-child-theme.md)
- [MODULE-PARTICIPATION](../modules/participation-payments.md)
- [MODULE-JIHADI-CENTER](../modules/jihadi-center.md)
- [ADR-002](../decisions/ADR-002-participation-quick-checkout.md)

## STATE-001-005 — Test evidence classified

Date: 2026-09-29

AFE `docs/PROJECT_STATE.md` records checkpoint 11.3 QA as 57/57 regression tests PASS plus lint/JS/composer/CDN/ZIP checks. This is historical evidence for that checkpoint, not a newly executed test of baseline HEAD.

Theme `docs/versions/v0.6.5.md` records syntax and ZIP checks for that patch. They were not rerun during documentation bootstrap.

Operational consequence:
future agents must distinguish **documented prior PASS** from **tests executed on the current HEAD** and must not upgrade the former into the latter without evidence.

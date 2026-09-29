# Current Project State

آخرین بازبینی: **2026-09-29**

## Project Phase

پروژه در توسعه فعال است.

- **Alavi Form Engine:** `1.0.28-dev`، DB `1.0.5-dev.2`، stable baseline/tag `1.0.27`; production bump هنوز به live acceptance وابسته است.
- **Ostadsho child theme:** custom integration layer روی Theme header `2.8`; version docs legacy v0.x به state history مهاجرت کرده‌اند.

## Current Goal

ساختار مستندات project-level canonical شده است. task بعدی باید فقط از root `docs/` و module مربوط شروع شود؛ handoff/build/version docs قدیمی دیگر source of truth نیستند و حذف شده‌اند.

AFE release goal همچنان live acceptance نسخه 1.0.28 و رفع blocker واقعی پیش از production promotion است.

## Recently Completed

### Persistent documentation migration
- دانش AFE legacy از Project State، Architecture، Developer، Acceptance، Feature Completeness، Build Report و Handoff به module/ADR/state canonical منتقل شد.
- دانش theme legacy از Handoff، Admin Repeater guide، v0.1.0→v0.6.5 version docs و Patch Manifests منتقل شد.
- legacy duplicate docs حذف شدند.
- package-facing AFE `README.md`, `readme.txt`, `THIRD-PARTY-NOTICES.md` حفظ شدند و README references به docs canonical هدایت شدند.

### Latest product baseline before documentation-only commits
`2b7cc8fecb8be39d40c2562349ea8c0b6adce12e`

AFE changes در آن baseline:
- admin masked data به normalized/raw presentation نزدیک شد.
- frontend preview می‌تواند mask presentation را اعمال کند.
- 24-digit IBAN preset بدون فاصله شد.
- per-form custom CSS همراه render form/locked preview output می‌شود.
- mask regression tests به‌روزرسانی شدند.

## Currently In Progress

هیچ feature task نیمه‌تمام قابل اثبات در repository ثبت نشده است.

Open release work برای AFE:
- live acceptance WordPress/MySQL/Elementor
- Excel collision verification در سایت واقعی با foreign Composer vendor
- PDF فارسی/font runtime verification
- export override/reset verification
- live duplicate verification روی legacy submission

## Blocked / Known Issues

- AFE به `1.0.28` / DB `1.0.5` / Stable `1.0.28` تا PASS acceptance زنده bump نشود.
- `vendor/` AFE در Git نیست؛ export deployment به Composer build و PDF font metadata وابسته است.
- documented checkpoint 11.3 QA یک PASS تاریخی است؛ بعد از product commit `2b7cc8f` full QA در این documentation migration اجرا نشده، پس HEAD فعلی **unverified** است.
- WordPress core، parent theme و third-party plugins در repo نیستند؛ integration tests محیط کامل می‌خواهند.

## Important Active Decisions

- [ADR-001 — Code-defined forms](../decisions/ADR-001-code-defined-forms.md)
- [ADR-002 — Isolated participation checkout](../decisions/ADR-002-participation-quick-checkout.md)
- [ADR-003 — Shared theme settings persistence](../decisions/ADR-003-shared-theme-settings-persistence.md)
- [ADR-004 — Jihadi Center source precedence](../decisions/ADR-004-jihadi-center-source-precedence.md)
- [ADR-005 — Shared admin Repeater](../decisions/ADR-005-shared-admin-repeater.md)
- [ADR-006 — AFE export dependency isolation](../decisions/ADR-006-afe-export-dependency-isolation.md)

## Relevant Modules

- [MODULE-AFE](../modules/alavi-form-engine.md)
- [MODULE-AFE-EXTENSION](../modules/alavi-form-engine-extension-api.md)
- [MODULE-THEME](../modules/ostadsho-child-theme.md)
- [MODULE-THEME-ADMIN](../modules/theme-admin-settings.md)
- [MODULE-THEME-MEDIA](../modules/theme-product-media.md)
- [MODULE-PARTICIPATION](../modules/participation-payments.md)
- [MODULE-JIHADI-CENTER](../modules/jihadi-center.md)

## Tests Status

Historical AFE checkpoint 11.3:
- 57/57 regression PASS
- 168 PHP lint PASS
- JS syntax PASS
- composer JSON PASS
- runtime CDN check PASS
- extracted ZIP QA/integrity PASS

Real MeliPayamak test: PASS reported by project administrator on 2026-09-04.

Theme legacy v0.6.5 docs recorded PHP/JS syntax + ZIP checks for that patch.

**No product test suite was rerun during this documentation-only consolidation.**

## Next Actions

1. task domain را از [modules/INDEX.md](../modules/INDEX.md) انتخاب کن.
2. فقط module + ADR مرتبط + source/test files همان subsystem را load کن.
3. AFE code change → domain tests + `tools/qa.sh`; export scope → vendor/font checks.
4. Theme code change → PHP/JS syntax + integration-specific checks.
5. بعد از تغییر مهم، CURRENT + state active + module/ADR/index متاثر را update کن.
6. parallel handoff/version documentation tree دوباره ایجاد نکن.

## Next Agent Handoff

**Current task:** documentation consolidation complete؛ feature task بازی وجود ندارد.

**Start here:** [../INDEX.md](../INDEX.md) → [../modules/INDEX.md](../modules/INDEX.md).

**Do not reconsider without new evidence:** AFE source-definition override model، participation cart isolation، theme shared settings persistence، Jihadi source precedence، shared admin Repeater، export autoload isolation.

**Do not assume:** current HEAD QA PASS، live export acceptance، vendor availability یا production readiness.

**History lookup:** [INDEX.md](INDEX.md) → `state-v002.md` برای legacy milestones.

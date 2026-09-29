# Current Project State

آخرین بازبینی: **2026-09-29**

## Project Phase

پروژه در توسعه فعال است.

- **Alavi Form Engine:** `1.0.28-dev`، DB `1.0.5-dev.2`، stable baseline/tag `1.0.27`; production bump هنوز به live acceptance وابسته است.
- **Ostadsho child theme:** custom integration layer روی Theme header `2.8`; version docs legacy v0.x به state history مهاجرت کرده‌اند.

## Current Goal

Hero بازطراحی‌شده صفحه اصلی به یک ویجت مستقل Elementor در child theme تبدیل شده است. مسیر ادامه برای این بخش `MODULE-HOME-HERO` است؛ پذیرش بصری/رفتاری نهایی باید در Elementor و صفحه اصلی واقعی انجام شود.

AFE release goal همچنان live acceptance نسخه 1.0.28 و رفع blocker واقعی پیش از production promotion است.

## Recently Completed

### Homepage Hero Elementor widget
- بخش `ba-hero__grid` از reference repository `bonyad-alavi-redesign/redesign/index.html` به ویجت `bonyad_alavi_home_hero` در child theme تبدیل شد.
- Slider و Mission Nav با Elementor Repeater مدیریت می‌شوند؛ تصویر اسلاید فقط Media Control دارد و هیچ تصویر reference به قالب کپی نشده است.
- Ticker از `BA_Content_Query_Service` استفاده می‌کند و Query کامل Elementor دارد؛ پیش‌فرض آخرین 3 نوشته است و فقط عنوان + permalink رندر می‌شود.
- قرارداد لینک اسلاید: دکمه فقط با متن+لینک نمایش داده می‌شود؛ لینک بدون متن دکمه کل اسلاید را clickable می‌کند.
- Style Tab شامل Typography/Color/Background و stateهای Normal/Hover برای نقاط تعاملی است؛ CSS با block `ba-home-hero` scope شده است.
- JS برای چند instance اسکوپ شده، با Elementor frontend hook، keyboard navigation، autoplay/pause و reduced-motion سازگار است.
- source commit: `dc1eb832c1a3582f722dde7085bb7d945e31e537`.

### Jihadi form ownership transfer
- Source Definition فرم `jihadi-group-registration` از `alavi-form-engine/src/Forms` به `ostadsho-child/inc/forms` منتقل شد.
- ثبت مستقیم فرم از `Core\Plugin` حذف و registration به `afe_register_forms` در child theme منتقل شد.
- AFE boot از `plugins_loaded` به `after_setup_theme` priority 20 منتقل شد تا theme registration window معتبر باشد.
- slug فرم تغییر نکرد؛ migration دیتابیس لازم نیست.
- تست‌های اختصاصی فرم از suite افزونه خارج و regression theme جایگزین شد؛ یک regression برای bootstrap registration window نیز به AFE اضافه شد.

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
- [ADR-007 — Project form ownership](../decisions/ADR-007-project-form-ownership.md)

## Relevant Modules

- [MODULE-AFE](../modules/alavi-form-engine.md)
- [MODULE-AFE-EXTENSION](../modules/alavi-form-engine-extension-api.md)
- [MODULE-THEME](../modules/ostadsho-child-theme.md)
- [MODULE-THEME-ADMIN](../modules/theme-admin-settings.md)
- [MODULE-THEME-MEDIA](../modules/theme-product-media.md)
- [MODULE-PARTICIPATION](../modules/participation-payments.md)
- [MODULE-JIHADI-CENTER](../modules/jihadi-center.md)
- [MODULE-JIHADI-FORM](../modules/jihadi-group-registration-form.md)
- [MODULE-HOME-HERO](../modules/theme-home-hero-widget.md)

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

برای Hero صفحه اصلی فایل regression contract جدید `tests/home-hero-widget-contract.php` اضافه شده است. روی draft محلی معادل این implementation، PHP lint، JavaScript syntax و contract checks PASS شدند؛ نسخه commit‌شده نیز از GitHub برای registration، Query reuse، link contract، نبود تصاویر پیش‌فرض، scope CSS و Elementor JS hook بازبینی استاتیک شد. **اجرای live WordPress/Elementor روی HEAD جدید هنوز تأیید نشده است.**

Historical PASSهای AFE/Theme به HEAD جدید تعمیم داده نمی‌شوند.

## Next Actions

1. task domain را از [modules/INDEX.md](../modules/INDEX.md) انتخاب کن.
2. فقط module + ADR مرتبط + source/test files همان subsystem را load کن.
3. AFE code change → domain tests + `tools/qa.sh`; export scope → vendor/font checks.
4. Theme code change → PHP/JS syntax + integration-specific checks.
5. بعد از تغییر مهم، CURRENT + state active + module/ADR/index متاثر را update کن.
6. parallel handoff/version documentation tree دوباره ایجاد نکن.

## Next Agent Handoff

**Current task:** ویجت Hero صفحه اصلی پیاده‌سازی و ثبت شده است؛ مرحله باز بعدی، پذیرش بصری/رفتاری در Elementor واقعی و انتخاب تصاویر توسط مدیر محتوا است.

**Start here:** [../INDEX.md](../INDEX.md) → [../modules/INDEX.md](../modules/INDEX.md).

**Do not reconsider without new evidence:** AFE source-definition override model، ownership فرم پروژه‌ای در theme، participation cart isolation، theme shared settings persistence، Jihadi source precedence، shared admin Repeater، export autoload isolation.

**Do not assume:** current HEAD QA PASS، live export acceptance، vendor availability یا production readiness.

**History lookup:** [INDEX.md](INDEX.md) → `state-v002.md` برای legacy milestones.

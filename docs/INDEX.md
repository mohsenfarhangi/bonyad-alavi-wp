# Documentation Index

این فایل router اصلی مستندات canonical پروژه است. ابتدا [state/CURRENT.md](state/CURRENT.md) را بخوان و بعد فقط مسیر مرتبط با task را باز کن.

| Document | موضوع / Keywords | کاربرد |
|---|---|---|
| [PROJECT.md](PROJECT.md) | scope, WordPress, repository conventions | پروژه چیست و چه چیزی در repo نگهداری می‌شود |
| [ARCHITECTURE.md](ARCHITECTURE.md) | architecture, plugin, theme, data flow | معماری بین ماژول‌ها |
| [DECISIONS.md](DECISIONS.md) | ADR, decisions | مرور کوتاه تصمیم‌های مهم |
| [state/CURRENT.md](state/CURRENT.md) | current, handoff, blockers, tests | snapshot عملیاتی جاری |
| [state/INDEX.md](state/INDEX.md) | history, versions | پیدا کردن state تاریخی لازم |
| [decisions/INDEX.md](decisions/INDEX.md) | ADR index | پیدا کردن تصمیم معماری/فنی |
| [modules/INDEX.md](modules/INDEX.md) | modules, subsystems | پیدا کردن مستند subsystem |

## Progressive Loading

- AFE عمومی → [modules/INDEX.md](modules/INDEX.md) → `MODULE-AFE`
- توسعه hook/form/action در AFE → `MODULE-AFE-EXTENSION`
- تنظیمات مدیریت قالب → `MODULE-THEME-ADMIN`
- media/gallery محصول → `MODULE-THEME-MEDIA`
- پرداخت مشارکت → `MODULE-PARTICIPATION`
- مرکز جهادی → `MODULE-JIHADI-CENTER`
- چرایی یک contract → [decisions/INDEX.md](decisions/INDEX.md)
- سابقه تغییر → [state/INDEX.md](state/INDEX.md)

## Legacy Documentation

در 2026-09-29 مستندات legacy پراکنده plugin/theme با این ساختار ادغام و سپس حذف شدند. برای continuation به pathهای قدیمی `handoff.md`، `BUILD-REPORT.md`، plugin `docs/*` یا theme `docs/versions/*` وابسته نباش.

README/readme/third-party notice افزونه package-facing هستند و مستقل از persistent context باقی مانده‌اند.

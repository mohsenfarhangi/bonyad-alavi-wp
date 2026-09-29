# AGENTS.md

این repository مستندات داخل `docs/` را حافظه پایدار و مرجع ادامه پروژه می‌داند. به تاریخچه Chat وابسته نباش.

## بارگذاری اولیه Context

در شروع هر task فقط این سه فایل را بخوان:

1. [docs/state/CURRENT.md](docs/state/CURRENT.md)
2. [docs/INDEX.md](docs/INDEX.md)
3. همین فایل

سپس domain کار را مشخص کن و فقط context لازم را به‌صورت progressive بخوان.

- تعریف پروژه: [docs/PROJECT.md](docs/PROJECT.md)
- معماری جاری: [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)
- ماژول‌ها: ابتدا [docs/modules/INDEX.md](docs/modules/INDEX.md)، سپس فقط module مرتبط
- تصمیم‌ها: ابتدا [docs/decisions/INDEX.md](docs/decisions/INDEX.md)، سپس فقط ADR مرتبط
- تاریخچه: ابتدا [docs/state/INDEX.md](docs/state/INDEX.md)، سپس فقط state version مرتبط
- مرور کوتاه تصمیم‌ها: [docs/DECISIONS.md](docs/DECISIONS.md)

کل `docs/`، همه ADRها یا همه stateهای تاریخی را به‌صورت پیش‌فرض recursively نخوان.

## Source of Truth

از 2026-09-29 مستندات قدیمی پراکنده داخل plugin/theme به ساختار root-level مهاجرت و منابع تکراری حذف شده‌اند. برای دانش پروژه و handoff، فقط `docs/` مرجع است.

سه فایل داخل Alavi Form Engine مستندات package-facing هستند و جزو سیستم handoff محسوب نمی‌شوند:

- `wp-content/plugins/alavi-form-engine/README.md`
- `wp-content/plugins/alavi-form-engine/readme.txt`
- `wp-content/plugins/alavi-form-engine/THIRD-PARTY-NOTICES.md`

اگر مستند با کد جاری تناقض داشت، implementation و regression tests همان HEAD را بررسی کن و سپس مستند canonical را اصلاح کن. دلیل تصمیمی را که از repository قابل اثبات نیست اختراع نکن.

## مسیرهای سریع

- Form Engine عمومی → `MODULE-AFE`
- API/extensionهای Form Engine → `MODULE-AFE-EXTENSION`
- فرم ثبت‌نام گروه‌های مردمی و جهادی → `MODULE-JIHADI-FORM`
- تنظیمات مدیریت قالب → `MODULE-THEME-ADMIN`
- رسانه/گالری محصول → `MODULE-THEME-MEDIA`
- پرداخت مشارکت → `MODULE-PARTICIPATION`
- مرکز حرکت‌های مردمی و جهادی → `MODULE-JIHADI-CENTER`
- Hero صفحه اصلی / Elementor homepage hero → `MODULE-HOME-HERO`

شناسه و لینک دقیق همه آن‌ها در [docs/modules/INDEX.md](docs/modules/INDEX.md) است.

## بعد از کار مهم

Documentation maintenance بخشی از Definition of Done است:

- بررسی و در صورت نیاز به‌روزرسانی `docs/state/CURRENT.md`.
- ثبت تغییر تاریخی معنی‌دار در state version فعال.
- به‌روزرسانی module doc در صورت تغییر implementation/contract جاری.
- ایجاد/به‌روزرسانی ADR برای تصمیم معماری ماندگار.
- به‌روزرسانی indexهای متاثر.
- تازه نگه داشتن `Next Agent Handoff`.
- ثبت PASS تست فقط در صورت وجود شواهد اجرای واقعی.
- جلوگیری از duplication بزرگ بین CURRENT، module docs، ADR و state history.

## Git / Repository

شاخه پیش‌فرض `main` است. WordPress core و اکثر افزونه‌ها/قالب‌های ثالث عمداً track نمی‌شوند. کد سفارشی اصلی:

- `wp-content/plugins/alavi-form-engine`
- `wp-content/themes/ostadsho-child`

`vendor/` افزونه AFE و فایل‌های ZIP در Git track نمی‌شوند.

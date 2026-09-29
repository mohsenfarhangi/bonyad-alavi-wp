# AGENTS.md

این ریپازیتوری مستندات داخل `docs/` را حافظه پایدار پروژه می‌داند. برای ادامه کار به تاریخچه Chat وابسته نباش.

## بارگذاری اولیه Context

در شروع هر task فقط این سه فایل را بخوان:

1. [docs/state/CURRENT.md](docs/state/CURRENT.md)
2. [docs/INDEX.md](docs/INDEX.md)
3. همین فایل

سپس domain کار را مشخص کن و فقط context لازم را به‌صورت progressive بخوان.

- معماری فعلی: [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md)
- تعریف پروژه و scope: [docs/PROJECT.md](docs/PROJECT.md)
- ماژول‌ها: ابتدا [docs/modules/INDEX.md](docs/modules/INDEX.md)، سپس فقط module مرتبط
- تصمیم‌ها: ابتدا [docs/decisions/INDEX.md](docs/decisions/INDEX.md)، سپس فقط ADR مرتبط
- تاریخچه: ابتدا [docs/state/INDEX.md](docs/state/INDEX.md)، سپس فقط state version مرتبط
- خلاصه تصمیم‌ها: [docs/DECISIONS.md](docs/DECISIONS.md)

کل `docs/`، همه ADRها یا همه stateهای تاریخی را به‌صورت پیش‌فرض recursively نخوان.

## Source of Truth

مستندات root-level نقش router و snapshot بین ماژول‌ها را دارند. مستندات تخصصی موجود داخل هر ماژول همچنان مرجع جزئیات همان ماژول‌اند؛ برای مثال:

- Alavi Form Engine: `wp-content/plugins/alavi-form-engine/docs/` و `handoff.md`
- Child theme: `wp-content/themes/ostadsho-child/docs/`

اگر مستند قدیمی با کد جاری تناقض داشت، implementation و regression tests همان HEAD را بررسی کن و سپس مستند را اصلاح کن. دلیل تصمیمی را که از repository قابل اثبات نیست اختراع نکن.

## بعد از کار مهم

Documentation maintenance بخشی از Definition of Done است. بر اساس اثر task فقط فایل‌های مرتبط را به‌روزرسانی کن:

- همیشه بررسی کن آیا [docs/state/CURRENT.md](docs/state/CURRENT.md) نیاز به تغییر دارد.
- تغییر تاریخی مهم را در state version فعال ثبت کن.
- module doc مربوط را در صورت تغییر implementation فعلی به‌روزرسانی کن.
- برای تصمیم معماری مهم ADR بساز/به‌روزرسانی کن و index را تغییر بده.
- `Next Agent Handoff` را تازه نگه دار.
- نتیجه تست را فقط وقتی PASS بنویس که شواهد اجرای تست وجود داشته باشد.
- از duplication بزرگ بین CURRENT، module docs، ADR و state history خودداری کن.

## Git / Repository

شاخه پیش‌فرض `main` است. WordPress core و اکثر افزونه‌ها/قالب‌های ثالث intentionally در این repository track نمی‌شوند؛ کد سفارشی اصلی در `wp-content/plugins/alavi-form-engine` و `wp-content/themes/ostadsho-child` قرار دارد.

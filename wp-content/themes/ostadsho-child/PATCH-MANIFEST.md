# Patch Manifest — v0.5.5

مبدا Patch: `v0.5.4`  
مقصد Patch: `v0.5.5`

## هدف

تکمیل کنترل‌های ریسپانسیو استایل برای پنل آمار و Hero ویجت «مرکز حرکت‌های مردمی و جهادی».

## فایل‌های تغییرکرده

### `inc/elementor/widgets/class-bonyad-alavi-jihadi-center-widget.php`

علت ورود به Patch:

- افزودن Margin چهارجهته و ریسپانسیو برای `.ba-jihadi-center__stats-wrap`.
- تکمیل کنترل ارتفاع ریسپانسیو Hero.
- افزودن کنترل `object-fit` ریسپانسیو تصویر Hero.
- تبدیل موقعیت تصویر Hero به کنترل ریسپانسیو با ۹ موقعیت.
- افزودن Opacity ریسپانسیو و CSS Filterهای استاندارد Elementor برای تصویر Hero.

### `assets/css/bonyad-alavi-jihadi-center-widget.css`

علت ورود به Patch:

- حذف Min Height ثابت موبایل Hero که کنترل ریسپانسیو Elementor را خنثی می‌کرد.
- تعریف fallback ریسپانسیو `--ba-jc-hero-height` برای Tablet و Mobile.

### `docs/handoff.md`

علت ورود به Patch:

- ثبت قرارداد جدید کنترل‌های استایل Hero و آمار برای توسعه‌های آینده.
- افزودن نسخه `v0.5.5` به تاریخچه پروژه.

### `docs/versions/v0.5.5.md`

علت ورود به Patch:

- مستندسازی کامل تغییرات، سازگاری و تست‌های نسخه.

## فایل حذف‌شده

ندارد.

## روش اعمال

محتوای این ZIP را روی ریشه قالب `ostadsho-child` نسخه `v0.5.4` کپی و Replace کنید.

## بررسی‌های انجام‌شده

- `php -l` برای تمام فایل‌های PHP قالب: موفق
- `node --check` برای تمام فایل‌های JavaScript قالب: موفق
- بررسی حذف Rule ثابت `min-height:480px` روی Hero موبایل: موفق
- بررسی وجود کنترل‌های جدید Elementor: موفق
- تست سلامت ZIP کامل و Patch: موفق

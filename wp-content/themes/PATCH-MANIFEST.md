# Patch Manifest — ostadsho-child v0.3.0

## مبنا

این Patch باید روی نسخه کامل `v0.2.1` قالب `ostadsho-child` اعمال شود.

## نوع نسخه

`MINOR` — قابلیت جدید سازگار با نسخه قبل.

## قابلیت اصلی

افزودن ویجت Elementor صفحه «مرکز هماهنگی حرکت‌های مردمی و جهادی»، تب اختصاصی تنظیمات مرکز، Query Builder مشترک اخبار/چندرسانه‌ای، همراهان و FAQ با اولویت داشبورد و کنترل‌های گسترده استایل.

## فایل‌های جدید

- `ostadsho-child/assets/images/jihadi-center/hero.jpg`
- `ostadsho-child/assets/css/ba-center-settings.css`
- `ostadsho-child/assets/js/ba-center-settings.js`
- `ostadsho-child/assets/css/bonyad-alavi-jihadi-center-widget.css`
- `ostadsho-child/assets/js/bonyad-alavi-jihadi-center-widget.js`
- `ostadsho-child/inc/services/class-ba-center-settings-service.php`
- `ostadsho-child/inc/services/class-ba-content-query-service.php`
- `ostadsho-child/inc/admin/settings/class-ba-center-settings-tab.php`
- `ostadsho-child/inc/elementor/widgets/class-bonyad-alavi-jihadi-center-widget.php`
- `ostadsho-child/docs/versions/v0.3.0.md`

## فایل‌های جایگزین‌شونده

- `ostadsho-child/functions.php`
- `ostadsho-child/inc/elementor/elementor-widgets.php`
- `ostadsho-child/docs/handoff.md`

## نکته اعمال Patch

محتویات پوشه `ostadsho-child` این Patch را روی پوشه قالب موجود با حفظ ساختار مسیرها کپی و جایگزین کنید. فایل‌های دیگر قالب حذف نمی‌شوند.

## تست‌های تحویل

- PHP lint کل قالب: بدون خطا
- JavaScript syntax check کل `assets/js`: بدون خطا
- بررسی Scope CSS جدید: فقط `ba-jihadi-center` و `ba-center-settings`
- تست سلامت آرشیو ZIP: بدون خطا

## Commit پیشنهادی

```text
feat: افزودن ویجت و تنظیمات مرکز حرکت‌های مردمی و جهادی

- افزودن تب اختصاصی مرکز به تنظیمات بنیاد علوی
- افزودن تنظیمات Hero، آمار، معرفی مرکز و کارت‌های سامانه
- افزودن مدیریت همراهان و FAQ با اولویت تنظیمات داشبورد
- ساخت ویجت کامل Elementor بر اساس طرح تأییدشده
- افزودن Query Builder مشترک برای اخبار و چندرسانه‌ای
- افزودن کنترل‌های گسترده استایل و Responsive در Elementor
- پیاده‌سازی UI با BEM و سرویس‌های مشترک OOP
- حذف enqueue تکراری استایل Checkout
```

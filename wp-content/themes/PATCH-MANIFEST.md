# Patch Manifest — v0.4.1

## مبدا و مقصد

- نسخه مبدا: `v0.4.0`
- نسخه مقصد: `v0.4.1`
- نوع تغییر: Patch / Bug Fix

## هدف

رفع مشکل اعمال‌نشدن لینک و نمایش‌ندادن آیکون‌های Elementor در کارت‌های سامانه بخش معرفی صفحه «مرکز هماهنگی حرکت‌های مردمی و جهادی».

## تغییرات

- اصلاح Resolve کارت‌های `system_cards` از حالت Override کامل Repeater به اولویت فیلدبه‌فیلد.
- لینک معتبر داشبورد همچنان اولویت دارد؛ اگر لینک داشبورد خالی باشد، لینک Elementor استفاده می‌شود.
- آیکون معتبر داشبورد همچنان اولویت دارد؛ اگر آیکون داشبورد خالی باشد، آیکون Elementor استفاده می‌شود.
- تغییر کنترل آیکون Elementor از `MEDIA` به `ICONS`.
- رندر آیکون‌های Font/SVG با `Elementor\Icons_Manager`.
- حفظ fallback برای داده‌های قدیمی Media در Elementor.
- افزودن کنترل اندازه خود آیکون و رنگ آیکون در تب Style.
- به‌روزرسانی Handoff و مستند نسخه.

## فایل‌های تغییرکرده

- `ostadsho-child/assets/css/bonyad-alavi-jihadi-center-widget.css`
- `ostadsho-child/docs/handoff.md`
- `ostadsho-child/inc/elementor/widgets/class-bonyad-alavi-jihadi-center-widget.php`
- `ostadsho-child/inc/services/class-ba-center-settings-service.php`

## فایل جدید

- `ostadsho-child/docs/versions/v0.4.1.md`

## فایل حذف‌شده

ندارد. فایل `DELETED-FILES.txt` خالی است.

## روش اعمال Patch

محتویات پوشه `ostadsho-child` داخل Patch را روی پوشه قالب Child موجود جایگزین/کپی کنید. این Patch برای مبدا `v0.4.0` تهیه شده است.

## تست‌ها

- PHP lint روی تمام ۲۳ فایل PHP قالب.
- JavaScript syntax check روی تمام ۹ فایل JS قالب.
- تست Resolver برای fallback لینک و آیکون Elementor.
- تست اولویت لینک و آیکون معتبر داشبورد.
- تست سلامت آرشیو ZIP کامل و Patch.

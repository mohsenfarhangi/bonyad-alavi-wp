# Patch v0.5.1 — کارت‌های سامانه با رسانه دوحالته

## مبدا و مقصد

- مبدا: `ostadsho-child v0.5.0`
- مقصد: `ostadsho-child v0.5.1`
- نوع نسخه: PATCH

## هدف

افزودن انتخاب انحصاری «تصویر» یا «آیکون/SVG» برای `ba-jihadi-center__system-icon` در تنظیمات بنیاد علوی و Elementor، با خروجی `<img>` برای تصویر و Inline `<svg>` برای فایل SVG.

## فایل‌های تغییرکرده

- `assets/css/ba-center-settings.css`
- `assets/css/bonyad-alavi-jihadi-center-widget.css`
- `assets/js/ba-center-settings.js`
- `docs/handoff.md`
- `docs/versions/v0.5.1.md`
- `inc/admin/settings/class-ba-center-settings-tab.php`
- `inc/elementor/widgets/class-bonyad-alavi-jihadi-center-widget.php`
- `inc/helpers/class-ba-media-helper.php`
- `inc/services/class-ba-center-settings-service.php`

## فایل حذف‌شده

ندارد. `DELETED-FILES.txt` عمداً خالی است.

## نکات اعمال Patch

محتویات پوشه `ostadsho-child` را روی قالب فرزند نسخه `v0.5.0` جایگزین کنید. ساختار مسیرها حفظ شده است.

داده قدیمی `icon_id` نیاز به Migration دستی ندارد و هنگام خواندن بر اساس MIME فایل به ساختار جدید `image_id/svg_id` تبدیل می‌شود.

## تست‌های انجام‌شده

- Syntax تمام PHPها با `php -l`.
- Syntax تمام JavaScriptها با `node --check`.
- تست Resolver برای Icon/SVG خام Elementor بدون override داشبورد.
- تست Migration `icon_id` قدیمی برای SVG و تصویر Raster.
- بررسی ساختار BEM و Modifierهای `system-icon--image` و `system-icon--svg`.
- تست سلامت ZIP کامل و Patch با `unzip -t`.

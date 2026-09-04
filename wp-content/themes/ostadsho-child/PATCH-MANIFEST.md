# Patch Manifest — v0.5.3

## مبدا و مقصد

- نسخه مبدا: `v0.5.2`
- نسخه مقصد: `v0.5.3`
- نوع نسخه: PATCH

## هدف

افزودن Lazy Rendering برای سکشن‌های پایین صفحه «مرکز حرکت‌های مردمی و جهادی» و اجرای Fade-in یک‌باره هنگام ورود هر سکشن به viewport.

## فایل‌های تغییرکرده

- `assets/css/bonyad-alavi-jihadi-center-widget.css`
- `assets/js/bonyad-alavi-jihadi-center-widget.js`
- `inc/elementor/widgets/class-bonyad-alavi-jihadi-center-widget.php`
- `docs/handoff.md`

## فایل‌های جدید

- `docs/versions/v0.5.3.md`

## فایل حذف‌شده

ندارد.

## نکات اعمال Patch

محتویات ZIP را در ریشه قالب `ostadsho-child` جایگزین کنید. ساختار مسیرها حفظ شده است.

Hero عمداً Lazy Render نشده است تا LCP و اولویت تصویر اصلی صفحه آسیب نبیند؛ فقط Fade-in روی آن اجرا می‌شود.

# Patch Manifest — v0.6.3

## مبدا و مقصد

- مبدا: `v0.6.2`
- مقصد: `v0.6.3`

## هدف Patch

رفع Fatal صفحه تنظیمات مشارکت در نصب‌های ترکیبی که `tab-participation.php` جدید اجرا می‌شود ولی نسخه قدیمی `BA_Participation_Payment_Gateway_Service` هنوز در حافظه/سرور فعال است. همچنین مسیر ذخیره و پرداخت درگاه SEP از نسخه Service مستقل شده تا شناسه `WC_Sep_Payment_Gateway` lowercase نشود.

## فایل‌های Patch

- `inc/admin/settings/tab-participation.php` — افزودن Compatibility Guard، Resolver مستقل تنظیمات و Sanitize Case-sensitive مستقل از Service.
- `inc/woocommerce/class-ba-participation-payment-gateway-service.php` — ثبت نسخه Service `0.6.3` برای تشخیص هماهنگی فایل‌ها و حفظ پیاده‌سازی Case-sensitive.
- `inc/woocommerce/class-ba-participation-quick-checkout-service.php` — Resolve مستقیم Gateway بر اساس `$gateway->id` واقعی برای جلوگیری از اثر Service قدیمی روی پرداخت SEP.
- `docs/handoff.md` — ثبت قرارداد سازگاری Gateway و ممنوعیت وابستگی مستقیم UI به متد نسخه‌پذیر Service.
- `docs/versions/v0.6.3.md` — شرح علت Fatal، راهکار و تست‌های نسخه.

## فایل حذف‌شده

هیچ فایلی حذف نشده است.

## نکته استقرار

Patch باید با حفظ مسیرها روی قالب جایگزین شود. اگر PHP بعد از جایگزینی کامل فایل‌ها همچنان نسخه قدیمی کلاس را اجرا می‌کند، OPcache/PHP-FPM سرور باید پاک یا reload شود. Guard این نسخه از Fatal صفحه تنظیمات جلوگیری می‌کند، اما برای اطمینان از اجرای تمام کد جدید بهتر است Cache PHP نیز refresh شود.

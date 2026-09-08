# Patch Manifest — v0.6.1

مبدأ این Patch: `ostadsho-child v0.6.0`

مقصد: `ostadsho-child v0.6.1`

## هدف

اصلاح ذخیره نشدن مقدار «درگاه پرداخت مشارکت» در تب تنظیمات مشارکت مردمی هنگام ذخیره AJAX.

## علت مشکل

Sanitize مقدار `payment_gateway` هنگام Persist دوباره به `get_active_gateway_choices()` وابسته بود. برخی افزونه‌های درگاه در درخواست `admin-ajax.php` Registry کامل Gatewayها را initialize نمی‌کنند و در نتیجه مقدار انتخاب‌شده می‌توانست به رشته خالی تبدیل شود.

## فایل‌های Patch

- `inc/woocommerce/class-ba-participation-payment-gateway-service.php`
  - حذف وابستگی `sanitize_gateway_id()` به لیست Runtime Gatewayها.
  - ذخیره شناسه با `sanitize_key()`.
  - حفظ بررسی وجود، فعال بودن و availability در زمان واقعی پرداخت.

- `docs/handoff.md`
  - ثبت قرارداد جدید Sanitize و Validation درگاه.

- `docs/versions/v0.6.1.md`
  - مستندات کامل اصلاح این نسخه.

## فایل حذف‌شده

ندارد.

## اعمال Patch

محتویات این ZIP باید روی ریشه قالب `ostadsho-child` کپی و فایل‌های هم‌نام جایگزین شوند.

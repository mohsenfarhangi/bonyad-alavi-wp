# Patch Manifest — v0.6.2

## مبدا و مقصد

- مبدا: `v0.6.1`
- مقصد: `v0.6.2`

## هدف Patch

اصلاح ذخیره و بازیابی درگاه‌هایی که شناسه آن‌ها حروف بزرگ دارد، به‌خصوص `WC_Sep_Payment_Gateway`. در نسخه قبل استفاده از `sanitize_key()` باعث lowercase شدن ID و عدم تطبیق با Gateway واقعی WooCommerce می‌شد.

## فایل‌های Patch

- `inc/woocommerce/class-ba-participation-payment-gateway-service.php` — حفظ case شناسه Gateway، lookup بر اساس `$gateway->id` واقعی و Migration مقدار lowercase نسخه قبل.
- `inc/admin/settings/tab-participation.php` — Resolve کردن مقدار ذخیره‌شده قبل از انتخاب گزینه Select تا SEP صحیح در UI نمایش داده شود.
- `docs/handoff.md` — ثبت قرارداد دائمی عدم استفاده از `sanitize_key()` برای Persist شناسه Gateway.
- `docs/versions/v0.6.2.md` — شرح مشکل، اصلاح، Migration و تست‌های این نسخه.

## فایل حذف‌شده

هیچ فایلی حذف نشده است.

## نکته Migration

اگر `v0.6.1` مقدار `wc_sep_payment_gateway` را ذخیره کرده باشد، نسخه جدید آن را هنگام خواندن به `WC_Sep_Payment_Gateway` نگاشت می‌کند. با ذخیره مجدد تنظیمات، مقدار صحیح و Case-sensitive در دیتابیس Persist می‌شود.

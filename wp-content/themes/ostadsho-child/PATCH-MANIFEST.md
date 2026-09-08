# Patch Manifest — v0.6.4

مبدأ: `ostadsho-child v0.6.3`  
مقصد: `ostadsho-child v0.6.4`

## هدف

محدودکردن پرداخت سریع صفحه مشارکت مردمی به فیلدهای صورتحساب WooCommerce و اجباری‌کردن عرض کامل فیلدهای فرم بدون اثرگذاری روی فرم‌های دیگر سایت.

## فایل‌های Patch و علت حضور

### `inc/woocommerce/class-ba-participation-quick-checkout-service.php`
- رندر Quick Checkout فقط از گروه `billing` در `WC_Checkout::get_checkout_fields()` انجام می‌شود.
- Shipping، Account و Order Fields دیگر در فرم Inline نمایش داده نمی‌شوند.
- Validation فقط روی Billing Fields اجرا می‌شود تا فیلد مخفی Shipping خطای Required ایجاد نکند.
- Terms/Privacy استاندارد WooCommerce حفظ شده است.

### `assets/css/bonyad-alavi-participation-widget.css`
- Grid فیلدهای Quick Checkout تک‌ستونه شده است.
- `.form-row`های داخل `bap__donation-form` با `width: 100% !important` و `max-width: 100% !important` اجباری شده‌اند.
- Float پیش‌فرض WooCommerce با `float: none !important` خنثی شده است.
- Override فقط در Scope فرم مشارکت اعمال می‌شود و به سایر فرم‌های WooCommerce نشت نمی‌کند.

### `docs/handoff.md`
- قرارداد Billing-only و Force Width برای Quick Checkout به مستندات اصلی پروژه اضافه شده است.
- نسخه‌های `v0.6.3` و `v0.6.4` در تاریخچه ثبت شده‌اند.

### `docs/versions/v0.6.4.md`
- جزئیات فنی، تست‌ها، مهاجرت و Commit پیشنهادی این نسخه.

## فایل حذف‌شده

ندارد.

## تست‌ها

- Syntax تمام ۲۷ فایل PHP با `php -l` بررسی شد.
- Syntax تمام ۹ فایل JavaScript با `node --check` بررسی شد.
- قرارداد Billing-only برای Render و Validation به‌صورت استاتیک بررسی شد.
- وجود `width: 100% !important` و Scope فرم مشارکت بررسی شد.
- سلامت ZIP کامل و Patch با `unzip -t` بررسی شده است.

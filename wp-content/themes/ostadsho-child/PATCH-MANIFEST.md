# Patch v0.6.0 — پرداخت سریع Inline مشارکت مردمی

## مبدا و مقصد

- نسخه مبدا: `v0.5.9`
- نسخه مقصد: `v0.6.0`
- فایل حذف‌شده: ندارد
- Migration دیتابیس: ندارد

این Patch فقط فایل‌هایی را شامل می‌شود که برای قابلیت پرداخت سریع Inline، انتخاب درگاه و مستندات آن تغییر کرده یا اضافه شده‌اند.

## فایل‌ها و علت حضور در Patch

### `functions.php`
دو سرویس جدید پرداخت سریع را قبل از کلاس اصلی مشارکت بارگذاری می‌کند.

### `inc/admin/settings/tab-participation.php`
فیلد «درگاه پرداخت مشارکت» را به تب مشارکت اضافه می‌کند، فقط Gatewayهای فعال WooCommerce را نمایش می‌دهد و مقدار انتخاب‌شده را Sanitize می‌کند.

### `inc/elementor/elementor-widgets.php`
وابستگی‌های استاندارد WooCommerce برای Country/State و Address i18n را به Asset ویجت مشارکت اضافه می‌کند تا Checkout Fields تزریق‌شده با AJAX درست کار کنند.

### `inc/elementor/widgets/class-bonyad-alavi-participation-widget.php`
Markup مرحله دوم Checkout را بین مبلغ و دکمه اصلی قرار می‌دهد و Endpointهای Prepare/Payment را به Handler فرانت منتقل می‌کند. لینک قدیمی مشاهده سبد از جریان اصلی حذف شده است.

### `inc/woocommerce/class-ba-woocommerce-participation.php`
دو Endpoint AJAX جدید را ثبت می‌کند و اعتبارسنجی Product/Amount را در یک متد مشترک برای Cart Legacy و Quick Checkout متمرکز می‌کند.

### `inc/woocommerce/class-ba-participation-payment-gateway-service.php`
**فایل جدید.** انتخاب Gateway، بررسی Availability و اجرای Gateway در Cart موقت ایزوله را مدیریت می‌کند و Cart Session/Persistent Cart واقعی کاربر را Snapshot/Restore می‌کند.

### `inc/woocommerce/class-ba-participation-quick-checkout-service.php`
**فایل جدید.** فاکتور Inline، Checkout Fields، Validation، ساخت Order مستقل و اجرای مستقیم `process_payment()` را مدیریت می‌کند.

### `assets/js/bonyad-alavi-participation-widget.js`
فرم مشارکت را به جریان دو مرحله‌ای «آماده‌سازی فاکتور → پرداخت» تبدیل می‌کند، مبلغ را در مرحله دوم قفل می‌کند و Redirect درگاه را اجرا می‌کند.

### `assets/css/bonyad-alavi-participation-widget.css`
استایل BEM مربوط به فاکتور، Checkout Fields، Terms و دکمه ویرایش مبلغ را اضافه می‌کند و CSS بلااستفاده لینک Cart قدیمی را حذف می‌کند.

### `docs/handoff.md`
قرارداد معماری Quick Checkout، Gateway و ایزوله‌سازی Cart را به Handoff اصلی پروژه اضافه می‌کند.

### `docs/versions/v0.6.0.md`
جزئیات کامل قابلیت، تصمیم‌های معماری، فایل‌ها و تست‌های نسخه `v0.6.0` را ثبت می‌کند.

## روش اعمال Patch

محتویات ZIP را روی ریشه قالب `ostadsho-child` کپی و فایل‌های موجود را جایگزین کنید. چون فایل حذفی وجود ندارد، نیاز به اجرای `DELETED-FILES.txt` نیست.

## کنترل‌های انجام‌شده

- `php -l` روی تمام فایل‌های PHP قالب
- `node --check` روی تمام فایل‌های JavaScript قالب
- بررسی وجود Endpointهای Prepare و Payment در Widget Config
- بررسی حذف وابستگی ویجت به Cart Endpoint قدیمی
- بررسی تنظیم و Sanitize درگاه پرداخت
- بررسی Snapshot/Restore Cart Session و Persistent Cart
- بررسی Syntax و سلامت ZIP کامل و Patch

> تست تراکنش واقعی بانکی فقط در محیط Staging با WooCommerce، Gateway و Merchant فعال قابل انجام است.

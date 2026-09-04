# Patch Manifest — ostadsho-child v0.4.0

## مبدا و مقصد

- نسخه مبدا: `v0.3.1`
- نسخه مقصد: `v0.4.0`
- نوع نسخه: `MINOR`

## هدف

یکسان‌سازی تنظیمات محتوایی، داده‌ای و Query صفحه «مرکز هماهنگی حرکت‌های مردمی و جهادی» بین تنظیمات بنیاد علوی و Elementor، با اولویت قطعی داشبورد پس از ذخیره Schema جدید، و افزودن امکان فعال/غیرفعال‌کردن تمام سکشن‌های اصلی در هر دو منبع.

## فایل جدید

- `ostadsho-child/docs/versions/v0.4.0.md`

## فایل‌های تغییرکرده

- `ostadsho-child/inc/services/class-ba-center-settings-service.php`
- `ostadsho-child/inc/admin/settings/class-ba-center-settings-tab.php`
- `ostadsho-child/inc/elementor/widgets/class-bonyad-alavi-jihadi-center-widget.php`
- `ostadsho-child/assets/css/ba-center-settings.css`
- `ostadsho-child/docs/handoff.md`

## فایل حذف‌شده

ندارد. فایل `DELETED-FILES.txt` عمداً خالی است.

## تغییرات کلیدی

- افزودن Resolver مرکزی تنظیمات با اولویت داشبورد.
- افزودن `settings_schema_version = 0.4.0` برای سازگاری با تنظیمات Legacy.
- افزودن Switcher نمایش برای Hero، Stats، Intro، News، Media، Partners و FAQ در داشبورد و Elementor.
- افزودن Hero کامل به Elementor و تکمیل Hero داشبورد با Eyebrow.
- افزودن Repeater آمار به Elementor.
- افزودن عنوان، توضیحات و Repeater کارت‌های مرکز به Elementor.
- افزودن تنظیمات محتوا و Query کامل اخبار به داشبورد.
- افزودن تنظیمات محتوا و Query کامل چندرسانه‌ای به داشبورد.
- حفظ `BA_Content_Query_Service` به‌عنوان تنها محل ساخت WP_Query اخبار و چندرسانه‌ای.
- حفظ `BA_Admin_Repeater_Component` برای Repeaterهای سفارشی wp-admin و `Elementor\Repeater` برای Elementor.

## روش اعمال Patch

1. محتوای پوشه `ostadsho-child/` داخل Patch را روی پوشه قالب فرزند `ostadsho-child/` کپی کنید.
2. فایل‌های هم‌نام را Replace کنید.
3. چون فایل حذفی وجود ندارد، اقدام دیگری برای حذف فایل لازم نیست.
4. یک‌بار تب «مرکز حرکت‌های مردمی و جهادی» را بازبینی و ذخیره کنید تا Schema جدید ثبت شود.
5. Cache سایت/Elementor را در صورت وجود پاک کنید.

## نکته اولویت

بعد از اولین ذخیره تب مرکز با نسخه جدید، مقادیر ذخیره‌شده داشبورد مرجع فیلدهای متناظر هستند؛ حتی مقدار خالی و Switcher خاموش نیز انتخاب معتبر محسوب می‌شوند. کنترل‌های Style همچنان توسط Elementor مدیریت می‌شوند.

## تست‌های انجام‌شده

- `php -l` روی تمام ۲۳ فایل PHP قالب.
- `node --check` روی تمام ۹ فایل JavaScript قالب.
- تست واحد سبک Resolver و Sanitize تنظیمات با داده Stub.
- بررسی وجود هفت Switcher سکشن در داشبورد و هفت Switcher متناظر در Elementor.
- بررسی عدم ساخت `WP_Query` خارج از `BA_Content_Query_Service` برای قابلیت مرکز.
- بررسی سلامت ZIP کامل و Patch با `unzip -t`.

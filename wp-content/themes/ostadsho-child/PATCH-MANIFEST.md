# Patch Manifest — v0.5.4

## مبدا و مقصد

- نسخه مبدا: `v0.5.3`
- نسخه مقصد: `v0.5.4`
- نوع نسخه: PATCH

## هدف

اصلاح بارگذاری Assetهای اختصاصی تب‌های «تنظیمات بنیاد علوی» برای کاربران جدید یا نقش‌هایی با دسترسی محدود، به‌خصوص زمانی که صفحه بدون پارامتر `tab` باز می‌شود.

## علت

تب مرکز برای enqueue کردن Assetها مستقیماً `$_GET['tab']` را بررسی می‌کرد، در حالی که صفحه مرکزی می‌توانست در نبود این پارامتر، تب مرکز را بر اساس Capability کاربر به‌عنوان تب فعال Resolve و Render کند. در این حالت UI نمایش داده می‌شد ولی `ba-admin-repeater.css/js` و `ba-center-settings.css/js` لود نمی‌شدند.

## فایل‌های تغییرکرده

- `inc/admin/settings/class-ba-settings-page.php`
- `inc/admin/settings/class-ba-center-settings-tab.php`
- `docs/handoff.md`

## فایل‌های جدید

- `docs/versions/v0.5.4.md`

## فایل حذف‌شده

ندارد.

## نکات اعمال Patch

محتویات ZIP را در ریشه قالب `ostadsho-child` جایگزین کنید. ساختار مسیرها حفظ شده است.

از این نسخه Asset اختصاصی هر تب از طریق `assets_callback` در Registry همان تب ثبت می‌شود و `BA_Settings_Page` پس از Resolve تب فعال واقعی، Callback را اجرا می‌کند. Featureها نباید برای بارگذاری Asset به وجود `?tab=` در URL وابسته باشند.

# Patch Manifest — v0.5.0

## مبدا و مقصد

- نسخه مبدا: `v0.4.1`
- نسخه مقصد: `v0.5.0`
- نوع تغییر: Minor / Feature

## هدف

بهبود UX صفحه «تنظیمات بنیاد علوی» با ذخیره AJAX بدون Refresh و افزودن نوار ذخیره شناور در پایین Viewport برای تمام تب‌های قابل ذخیره.

## تغییرات

- افزودن `BA_Settings_Ajax_Controller` به‌عنوان Endpoint مشترک ذخیره تب‌ها.
- بررسی nonce AJAX، nonce فرم Settings API و Capability اختصاصی هر تب پیش از ذخیره.
- حفظ Sanitize Callbackهای ثبت‌شده در Settings API و جلوگیری از منطق ذخیره تکراری.
- افزودن `BA_Settings_Access_Service` برای اشتراک منطق ذخیره Role Capability بین AJAX و fallback کلاسیک.
- افزودن Block BEM جدید `ba-settings-savebar` با وضعیت‌های pristine، dirty، saving، success و error.
- حذف دکمه‌های ذخیره انتهای فرم و اتصال یک دکمه شناور مشترک به فرم فعال.
- حفظ `options.php` و `admin-post.php` به‌عنوان Progressive Enhancement در صورت غیرفعال بودن JavaScript.
- افزودن تشخیص تغییرات فرم، TinyMCE، Repeater عمومی و Media Picker.
- جلوگیری از ازبین‌رفتن فرمت نمایشی اعداد هنگام ذخیره AJAX.
- به‌روزرسانی Handoff و مستند نسخه.

## فایل‌های تغییرکرده

- `ostadsho-child/assets/css/ba-admin-settings.css`
- `ostadsho-child/assets/js/ba-admin-settings.js`
- `ostadsho-child/assets/js/ba-center-settings.js`
- `ostadsho-child/docs/handoff.md`
- `ostadsho-child/functions.php`
- `ostadsho-child/inc/admin/settings/class-ba-center-settings-tab.php`
- `ostadsho-child/inc/admin/settings/class-ba-settings-page.php`
- `ostadsho-child/inc/admin/settings/tab-access-management.php`
- `ostadsho-child/inc/admin/settings/tab-participation.php`

## فایل‌های جدید

- `ostadsho-child/docs/versions/v0.5.0.md`
- `ostadsho-child/inc/admin/settings/class-ba-settings-access-service.php`
- `ostadsho-child/inc/admin/settings/class-ba-settings-ajax-controller.php`

## فایل حذف‌شده

ندارد. فایل `DELETED-FILES.txt` خالی است.

## روش اعمال Patch

محتویات پوشه `ostadsho-child` داخل Patch را روی پوشه قالب Child موجود در نسخه `v0.4.1` کپی/جایگزین کنید. فایل جدیدی نیاز به Migration دیتابیس ندارد.

## تست‌ها

- PHP lint روی تمام ۲۵ فایل PHP قالب.
- JavaScript syntax check روی تمام ۹ فایل JS قالب.
- بررسی nonce و Capability در Endpoint AJAX.
- بررسی مسیر Settings API و Callback اختصاصی مدیریت دسترسی.
- بررسی حذف Submitهای انتهای فرم و وجود یک Save Bar مشترک.
- بررسی حفظ Form Action کلاسیک برای fallback بدون JavaScript.
- بررسی سلامت ZIP کامل و Patch.

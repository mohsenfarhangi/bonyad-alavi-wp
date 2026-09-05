# Patch Manifest — v0.5.6

مبدا Patch: `v0.5.5`  
مقصد Patch: `v0.5.6`

## هدف

اصلاح ابعاد تصویر Hero برای عملکرد قطعی کنترل‌های `object-fit` و `object-position` در Elementor.

## فایل‌های تغییرکرده

### `assets/css/bonyad-alavi-jihadi-center-widget.css`

علت ورود به Patch:

- تعیین صریح `width: 100%` و `height: 100%` برای `.ba-jihadi-center__hero-media`.
- افزودن `overflow: hidden` به کانتینر رسانه Hero.
- قراردادن `.ba-jihadi-center__hero-image` به‌صورت absolute با `inset: 0`.
- تعیین صریح `width: 100%` و `height: 100%` روی تصویر Hero.
- حذف محدودیت‌های عمومی تصویر با `max-width: none` و `max-height: none` تا `object-fit` روی ابعاد واقعی Hero اعمال شود.

### `docs/handoff.md`

علت ورود به Patch:

- ثبت قرارداد دائمی ابعاد تصویر Hero برای توسعه‌های آینده.
- افزودن نسخه `v0.5.6` به تاریخچه پروژه.

### `docs/versions/v0.5.6.md`

علت ورود به Patch:

- مستندسازی اصلاح Object Fit تصویر Hero و تست‌های نسخه.

## فایل حذف‌شده

ندارد.

## روش اعمال

محتوای این ZIP را روی ریشه قالب `ostadsho-child` نسخه `v0.5.5` کپی و Replace کنید.

## بررسی‌های انجام‌شده

- `php -l` برای تمام 25 فایل PHP قالب: موفق
- `node --check` برای تمام 9 فایل JavaScript قالب: موفق
- بررسی `width:100%` و `height:100%` روی Hero Media و Hero Image: موفق
- بررسی باقی‌ماندن کنترل‌های `object-fit` و `object-position` Elementor: موفق
- تست سلامت ZIP کامل و Patch: موفق

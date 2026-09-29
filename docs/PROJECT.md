# Project Overview

## Purpose

این repository کد سفارشی WordPress پروژه بنیاد علوی را نگهداری می‌کند. WordPress core و اکثر dependencyهای سایت در repository نیستند؛ تمرکز فعلی روی Alavi Form Engine و child theme سفارشی است.

## Tracked Product Areas

### Alavi Form Engine

مسیر: `wp-content/plugins/alavi-form-engine/`

WordPress plugin از نوع code-first form engine با submission lifecycle، validation، input mask، duplicate detection، actions/events/tokens، PDF/XLSX export، data sources، REST/Admin و Elementor integration.

وضعیت جاری:
- Development version: `1.0.28-dev`
- DB checkpoint: `1.0.5-dev.2`
- Stable baseline/tag مستند: `1.0.27`
- PHP: `>=8.3`
- production promotion به live acceptance وابسته است.

مرجع: [MODULE-AFE](modules/alavi-form-engine.md)

### Ostadsho Child Theme

مسیر: `wp-content/themes/ostadsho-child/`

Integration layer پروژه برای Elementor، تنظیمات بنیاد علوی، مرکز حرکت‌های مردمی و جهادی، WooCommerce participation، رسانه محصول، project form definitions، shortcodes و admin components.

Theme header فایل `style.css` نسخه `2.8` را نشان می‌دهد. تاریخچه featureهای سفارشی قدیمی با versionهای `v0.x` جدا از Theme header بوده و اکنون در [state-v002](state/state-v002.md) ثبت شده است.

مرجع: [MODULE-THEME](modules/ostadsho-child-theme.md)

## Documentation Model

از 2026-09-29 تنها ساختار `docs/` مرجع persistent project context است. handoffها، build reportها، acceptance/version docs و patch manifestهای legacy پس از migration حذف شدند.

فایل‌های زیر در plugin حفظ شده‌اند چون نقش package-facing دارند، نه handoff:
- `README.md` — معرفی/نصب/ویژگی‌های package
- `readme.txt` — WordPress-style package metadata/changelog
- `THIRD-PARTY-NOTICES.md` — مجوزها و third-party notices

## Repository Conventions

- default branch: `main`
- WordPress core track نمی‌شود.
- plugin اصلی track‌شده: `alavi-form-engine`
- theme اصلی track‌شده: `ostadsho-child`
- `vendor/` AFE در Git track نمی‌شود.
- ZIPها track نمی‌شوند.
- تغییر معنی‌دار code باید همراه update مستندات canonical باشد.

## Important Constraints

- رفتار production را از history قدیمی حدس نزن؛ HEAD و tests را بررسی کن.
- AFE: code-defined form schema منبع پایه است؛ [ADR-001](decisions/ADR-001-code-defined-forms.md).
- فرم‌های business-specific سایت در integration/theme مالکیت دارند و از public AFE hook ثبت می‌شوند؛ [ADR-007](decisions/ADR-007-project-form-ownership.md).
- Participation: cart/order isolation و gateway contract را حفظ کن؛ [ADR-002](decisions/ADR-002-participation-quick-checkout.md).
- Theme settings: shared registry/AJAX persistence را حفظ کن؛ [ADR-003](decisions/ADR-003-shared-theme-settings-persistence.md).
- Jihadi center: dual-source precedence را فقط از resolver مرکزی تغییر بده؛ [ADR-004](decisions/ADR-004-jihadi-center-source-precedence.md).
- Admin repeaters: UI مشترک است و persistence بیرون component می‌ماند؛ [ADR-005](decisions/ADR-005-shared-admin-repeater.md).
- AFE export: Composer dependency isolation/build contract را حفظ کن؛ [ADR-006](decisions/ADR-006-afe-export-dependency-isolation.md).
- live WordPress/MySQL/Elementor acceptance را بدون اجرای واقعی یا گزارش مستند معتبر PASS اعلام نکن.

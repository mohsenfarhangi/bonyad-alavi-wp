# Project Overview

## Purpose

این repository کد سفارشی WordPress مورد استفاده در پروژه بنیاد علوی را نگهداری می‌کند. WordPress core و اکثر dependencyهای سایت در repository نیستند؛ تمرکز فعلی روی یک افزونه اختصاصی Form Engine و یک child theme سفارشی است.

## Tracked Product Areas

### Alavi Form Engine

مسیر: `wp-content/plugins/alavi-form-engine/`

یک WordPress plugin از نوع code-first form engine با submission lifecycle، validation، input mask، duplicate detection، actions/events/tokens، export PDF/XLSX، data sources، REST/Admin و Elementor integration.

نسخه development در HEAD مبنا: `1.0.28-dev` با PHP `>=8.3`. نسخه production ثبت‌شده در مستندات ماژول هنوز `1.0.27` است و promotion به 1.0.28 به live acceptance وابسته است.

### Ostadsho Child Theme customizations

مسیر: `wp-content/themes/ostadsho-child/`

Child theme پروژه شامل Elementor widgets/dynamic tags، صفحه و تنظیمات بنیاد علوی، مرکز حرکت‌های مردمی و جهادی، WooCommerce participation flow، shortcodes و admin components است. خط نسخه مستند featureهای سفارشی تا `v0.6.5` ادامه دارد؛ header فایل `style.css` همچنان Theme Version `2.8` را نشان می‌دهد و این دو version scheme را نباید یکی فرض کرد.

## Users / Operational Roles

کد repository برای بازدیدکننده/متقاضی فرم، مدیران WordPress، نقش‌های دارای capabilityهای AFE، مدیر محتوای Elementor و کاربران پرداخت مشارکت طراحی شده است. جزئیات authorization هر subsystem در مستند همان module قرار دارد.

## Repository Conventions

- شاخه پیش‌فرض: `main`
- WordPress core track نمی‌شود.
- plugin اصلی track‌شده: `alavi-form-engine`
- theme اصلی track‌شده: `ostadsho-child`
- `vendor/` افزونه AFE در Git track نمی‌شود؛ Composer/build contract ماژول را رعایت کن.
- ZIPها track نمی‌شوند.
- مستندات root-level این پوشه، persistent context بین sessionها هستند.
- مستندات تاریخی/جزئی موجود داخل plugin و theme حذف یا جایگزین نشده‌اند؛ root docs به آن‌ها route می‌کنند.

## Important Constraints

- رفتار production را از test/document قدیمی حدس نزن؛ HEAD را بررسی کن.
- برای AFE، code-defined form schema منبع پایه است و admin فقط override است؛ [ADR-001](decisions/ADR-001-code-defined-forms.md).
- برای Quick Checkout مشارکت، cart/order isolation و gateway contract را حفظ کن؛ [ADR-002](decisions/ADR-002-participation-quick-checkout.md).
- نتیجه تست‌های live WordPress/MySQL/Elementor را بدون اجرای واقعی یا گزارش مستند معتبر PASS اعلام نکن.

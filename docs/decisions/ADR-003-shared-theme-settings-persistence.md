# ADR-003 — Shared registry-driven persistence for Bonyad Alavi settings

**Status:** Accepted  
**Date recorded:** 2026-09-29  
**Decision originated:** theme feature line v0.5.0/v0.5.4

## Context

صفحه «تنظیمات بنیاد علوی» چند tab با capability و persistence متفاوت دارد. پیاده‌سازی AJAX/asset logic جدا برای هر feature باعث duplication، اختلاف sanitize و خطا برای کاربران با دسترسی محدود می‌شد.

## Decision

- `BA_Settings_Page::get_tabs()` registry مرجع تعریف tabها است.
- `BA_Settings_Ajax_Controller` مسیر مشترک AJAX save است.
- capability tab قبل از persistence بررسی می‌شود.
- tab استاندارد با `option_name` و `option_group` از Settings API و همان sanitize callback استفاده می‌کند.
- tab با persistence خاص، `ajax_save_callback` ثبت می‌کند؛ callback فقط `true` یا `WP_Error` برمی‌گرداند و JSON/UI را خودش ارسال نمی‌کند.
- مسیرهای کلاسیک `options.php` یا `admin-post.php` برای Progressive Enhancement باقی می‌مانند.
- UI save از block مشترک `ba-settings-savebar` استفاده می‌کند.
- TinyMCE، Media Picker، Repeater و controls باید dirty state فرم را به shared save UI منتقل کنند.
- assetهای tab از `assets_callback` روی tab resolve‌شده enqueue می‌شوند؛ feature به وجود raw `$_GET['tab']` وابسته نباشد.
- user با capability محدود باید asset همان tab قابل دسترس خود را دریافت کند.

## Consequences

- feature جدید endpoint AJAX ذخیره مستقل ایجاد نکند مگر نیاز جدید contract را تغییر دهد.
- logic business/persistence در service/callback بماند؛ controller مسئول authorization/transport است.
- متاباکس مستقل خارج settings page می‌تواند بر اساس `get_current_screen()` asset خود را load کند.

## Related Modules

- [MODULE-THEME](../modules/ostadsho-child-theme.md)
- [MODULE-THEME-ADMIN](../modules/theme-admin-settings.md)

## Relevant Files

- `../../wp-content/themes/ostadsho-child/inc/admin/settings/class-ba-settings-page.php`
- `../../wp-content/themes/ostadsho-child/inc/admin/settings/class-ba-settings-ajax-controller.php`
- `../../wp-content/themes/ostadsho-child/inc/admin/settings/class-ba-settings-access-service.php`

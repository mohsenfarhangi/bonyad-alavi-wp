# MODULE-THEME — Ostadsho Child Theme

**Status:** active custom integration layer  
**Path:** `wp-content/themes/ostadsho-child/`  
**Theme header version:** `2.8`

## Purpose

Child theme بنیاد علوی روی parent `ostadsho` integrationهای اختصاصی سایت را نگه می‌دارد: Elementor widgets/dynamic tags، admin settings، WooCommerce participation، product media، center content، shortcodes و product FAQ.

## Composition

`functions.php`:
- parent/child styles و context-specific WooCommerce assets را enqueue می‌کند.
- shortcodeها را load می‌کند.
- admin repeater component و product FAQ را load می‌کند.
- Elementor widgets/dynamic tags را load می‌کند.
- participation gateway/quick-checkout services را load می‌کند.
- center settings service/tab را load می‌کند.
- Source Definition فرم جهادی را از `inc/forms/` load و با `afe_register_forms` ثبت می‌کند.
- settings access/page/AJAX controller را initialize می‌کند.

Subdirectories:
`inc/admin`, `inc/elementor`, `inc/forms`, `inc/helpers`, `inc/services`, `inc/shortcodes`, `inc/woocommerce`.

## Development Conventions Migrated from Legacy Handoff

### UI / CSS
- UI جدید با BEM و block اختصاصی، ترجیحاً prefix `ba-`.
- selector عمومی و override سراسری بدون scope ممنوع.
- feature CSS زیر block همان feature scope شود.
- modifier: `block--modifier`; element: `block__element`.

### PHP
- logic جدید تا حد عملی OOP و دارای responsibility روشن باشد.
- Service/Helper/Strategy/Adapter فقط برای مسئله واقعی استفاده شود.
- قبل از افزودن utility جدید، helper/service موجود بررسی شود.
- logic مشترک duplicate نشود.

### Comments
- کلاس/متد/تابع جدید توضیح فارسی مفید داشته باشد.
- کامنت «چرایی/contract» را توضیح دهد، نه syntax بدیهی.

## Subsystems

- [MODULE-THEME-ADMIN](theme-admin-settings.md) — settings page، AJAX، access و Repeater
- [MODULE-THEME-MEDIA](theme-product-media.md) — product media/gallery
- [MODULE-PARTICIPATION](participation-payments.md) — WooCommerce participation
- [MODULE-JIHADI-CENTER](jihadi-center.md) — صفحه مرکز
- [MODULE-JIHADI-FORM](jihadi-group-registration-form.md) — Source Definition و registration فرم ثبت‌نام گروه‌های مردمی و جهادی

## Version History

version docs legacy v0.1.0 تا v0.6.5 پس از migration حذف شده‌اند. خلاصه معنی‌دار آن‌ها در [state-v002](../state/state-v002.md) نگهداری می‌شود.

## Validation

مستند legacy آخرین participation patch، PHP lint و JavaScript syntax/ZIP checks را ثبت می‌کرد. این‌ها historical evidence هستند؛ برای HEAD جدید باید checks مرتبط دوباره اجرا شوند.

# MODULE-THEME — Ostadsho Child Theme

**Status:** active custom integration layer  
**Path:** `wp-content/themes/ostadsho-child/`  
**Theme header version:** `2.8`  
**Latest internal feature documentation:** `v0.6.5`

## Purpose

Child theme بنیاد علوی روی parent theme `ostadsho`، integrationهای اختصاصی سایت را نگه می‌دارد: Elementor widgets/dynamic tags، admin settings، WooCommerce participation flow، center settings/content، shortcodes و product FAQ.

## Composition

`functions.php`:

- parent/child styles را enqueue می‌کند.
- assetهای cart/checkout/thankyou مشارکت را بر اساس page context load می‌کند.
- shortcodeها و admin repeater component را load می‌کند.
- Elementor widgets/dynamic tags را register/load می‌کند.
- participation payment/quick checkout services را load می‌کند.
- center settings service و settings tabها را load می‌کند.
- shared settings page/AJAX controller را initialize می‌کند.

Subdirectories:
- `inc/admin`
- `inc/elementor`
- `inc/helpers`
- `inc/services`
- `inc/shortcodes`
- `inc/woocommerce`

## Important Contracts

- صفحه تنظیمات بنیاد علوی از settings registry + shared AJAX controller استفاده می‌کند؛ featureها endpoint ذخیره مستقل نسازند مگر contract tab صریحاً callback مخصوص داشته باشد.
- assetهای tab باید از resolved active tab/capability path load شوند، نه صرفاً raw `$_GET['tab']`.
- reusable admin repeater یک component مشترک دارد.
- participation flow قرارداد جدا و حساس دارد؛ [MODULE-PARTICIPATION](participation-payments.md).
- مرکز جهادی source precedence/query contracts جدا دارد؛ [MODULE-JIHADI-CENTER](jihadi-center.md).

## Documentation

Module handoff:
`../../wp-content/themes/ostadsho-child/docs/handoff.md`

Version history:
`../../wp-content/themes/ostadsho-child/docs/versions/`

Component docs:
`../../wp-content/themes/ostadsho-child/docs/components/admin-repeater.md`

Latest patch manifest:
`../../wp-content/themes/PATCH-MANIFEST.md`

## Validation

Theme v0.6.5 documentation records PHP lint، JS syntax و ZIP checks برای آن patch. این bootstrap آن‌ها را rerun نکرده است. برای task theme، syntax checks را در کنار integration-specific checks اجرا کن.

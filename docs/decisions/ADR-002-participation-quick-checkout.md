# ADR-002 — Participation Quick Checkout uses an isolated cart/order flow

**Status:** Accepted  
**Date recorded:** 2026-09-29  
**Decision observed in implementation/docs:** existing since theme participation v0.6.x

## Context

صفحه مشارکت مردمی باید مبلغ یک پروژه را inline دریافت و به gateway انتخاب‌شده ارسال کند، در حالی که user ممکن است Cart دیگری در WooCommerce داشته باشد. استفاده مستقیم از Cart واقعی می‌تواند order مشارکت را آلوده یا اقلام دیگر را توسط gateway تغییر/حذف کند.

## Decision

- Quick Checkout برای پروژه order مستقل می‌سازد.
- product/amount در prepare و process دوباره validate می‌شود.
- Cart واقعی user وارد order مشارکت نمی‌شود و در اجرای gateway mutate نمی‌شود.
- availability/process gateway در cart context ایزوله اجرا و session/persistent cart restore می‌شود.
- gateway از تنظیمات participation انتخاب می‌شود؛ silent fallback به gateway دیگر ممنوع.
- gateway ID case-sensitive است؛ persist نباید با `sanitize_key()` case را تغییر دهد.
- inline checkout فقط Billing fields را نمایش/validate می‌کند.
- standard Terms/Privacy و WooCommerce checkout validation hooks حفظ می‌شوند.
- WooCommerce country/state lifecycle باید از context استاندارد خودش استفاده کند؛ internal eventهای بدون آرگومان دستی trigger نشوند.

## Consequences

- checkout تغییرات باید داخل scope `bap`/Quick Checkout بماند.
- regression باید one-project order و حفظ real cart را هر دو پوشش دهد.
- gatewayهای دارای payment fields داخلی target contract فعلی نیستند؛ Hosted/Redirect مسیر اصلی است.
- تغییر sanitizer یا country/state initialization ممکن است compatibility gateway/WooCommerce را بشکند.

## Related Modules

- [MODULE-PARTICIPATION](../modules/participation-payments.md)
- [MODULE-THEME](../modules/ostadsho-child-theme.md)

## Relevant Source Files

- `../../wp-content/themes/ostadsho-child/inc/woocommerce/class-ba-participation-payment-gateway-service.php`
- `../../wp-content/themes/ostadsho-child/inc/woocommerce/class-ba-participation-quick-checkout-service.php`
- `../../wp-content/themes/ostadsho-child/inc/woocommerce/class-ba-woocommerce-participation.php`
- `../../wp-content/themes/ostadsho-child/inc/admin/settings/tab-participation.php`

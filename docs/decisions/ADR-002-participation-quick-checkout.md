# ADR-002 — Participation Quick Checkout uses an isolated cart/order flow

**Status:** Accepted  
**Date recorded:** 2026-09-29  
**Decision observed in implementation/docs:** existing since theme participation v0.6.x

## Context

صفحه مشارکت مردمی باید مبلغ یک پروژه را به‌صورت inline دریافت و به gateway انتخاب‌شده ارسال کند، در حالی که کاربر ممکن است Cart دیگری در WooCommerce داشته باشد. وارد کردن cart واقعی به این flow می‌تواند سفارش را آلوده کند یا باعث حذف/تغییر اقلام دیگر توسط gateway شود.

## Decision

- Quick Checkout برای پروژه مشارکت order مستقل می‌سازد.
- product/amount در prepare و process دوباره validate می‌شود.
- cart واقعی کاربر نباید وارد order مشارکت شود و نباید در اجرای gateway از بین برود.
- availability/process gateway در cart context ایزوله اجرا می‌شود و session/persistent cart واقعی restore می‌شود.
- gateway از تنظیمات participation انتخاب می‌شود؛ silent fallback به gateway دیگری مجاز نیست.
- gateway ID case-sensitive است؛ persist آن نباید case را با `sanitize_key()` تغییر دهد.
- inline checkout فعلی فقط billing fields را نمایش و validate می‌کند.
- WooCommerce country/state lifecycle باید از event/context استاندارد خود ووکامرس استفاده کند؛ eventهای داخلی بدون آرگومان دستی trigger نشوند.

## Consequences

- تغییر WooCommerce checkout باید داخل scope `bap`/Quick Checkout باقی بماند.
- تست regression باید هم order line item و هم حفظ cart واقعی را پوشش دهد.
- gatewayهای دارای payment fields داخلی ممکن است با این flow سازگار نباشند؛ contract فعلی Hosted/Redirect gateway را هدف می‌گیرد.
- تغییر sanitizer یا event initialization ممکن است compatibility gateway/country-select را بشکند.

## Related Modules

- [MODULE-PARTICIPATION](../modules/participation-payments.md)
- [MODULE-THEME](../modules/ostadsho-child-theme.md)

## Relevant Files / Docs

- `../../wp-content/themes/ostadsho-child/inc/woocommerce/class-ba-participation-payment-gateway-service.php`
- `../../wp-content/themes/ostadsho-child/inc/woocommerce/class-ba-participation-quick-checkout-service.php`
- `../../wp-content/themes/ostadsho-child/inc/woocommerce/class-ba-woocommerce-participation.php`
- `../../wp-content/themes/ostadsho-child/docs/handoff.md`
- `../../wp-content/themes/ostadsho-child/docs/versions/v0.6.5.md`

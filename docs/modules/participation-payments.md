# MODULE-PARTICIPATION — WooCommerce Participation Payments

**Path:** `wp-content/themes/ostadsho-child/`  
**Status:** implemented; latest documented hotfix v0.6.5

## Purpose

پرداخت مبلغ انتخابی برای پروژه مشارکت مردمی از داخل صفحه/widget، بدون مخلوط شدن با اقلام عادی Cart کاربر.

## Main Components

- `inc/woocommerce/class-ba-participation-payment-gateway-service.php`
- `inc/woocommerce/class-ba-participation-quick-checkout-service.php`
- `inc/woocommerce/class-ba-woocommerce-participation.php`
- `inc/admin/settings/tab-participation.php`
- `assets/js/bonyad-alavi-participation-widget.js`
- CSSهای participation cart/checkout/thankyou و Quick Checkout styles

## Flow

```text
select project amount
 -> validate product/amount
 -> prepare inline billing checkout
 -> validate billing + WooCommerce hooks
 -> create isolated order with project line item
 -> resolve configured gateway
 -> execute gateway inside isolated cart context
 -> redirect/complete payment
```

## Contracts

شرح تصمیم اصلی: [ADR-002](../decisions/ADR-002-participation-quick-checkout.md)

- cart واقعی snapshot/restore می‌شود و نباید جزئی از order مشارکت شود.
- gateway setting case-sensitive است.
- عدم وجود/availability gateway باید error بدهد، نه fallback خاموش.
- Quick Checkout فقط billing fields را render/validate می‌کند.
- scoped `.form-row` width force باید فقط داخل participation form اعمال شود.
- WooCommerce standard field/hook APIs استفاده شوند.
- eventهای country/state داخلی بدون context مناسب دستی trigger نشوند.
- v0.6.5 به‌جای trigger دستی eventهای داخلی، `change` واقعی billing country را داخل Quick Checkout اجرا می‌کند.

## Read Next

- `../../wp-content/themes/ostadsho-child/docs/handoff.md` — بخش معماری پرداخت سریع
- `../../wp-content/themes/ostadsho-child/docs/versions/v0.6.0.md` تا `v0.6.5.md` فقط در صورت نیاز تاریخی
- `../../wp-content/themes/PATCH-MANIFEST.md` برای آخرین patch summary

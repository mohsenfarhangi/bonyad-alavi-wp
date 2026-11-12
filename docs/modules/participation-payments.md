# MODULE-PARTICIPATION — WooCommerce Participation Payments

**Path:** `wp-content/themes/ostadsho-child/`  
**Status:** implemented

## Purpose

پرداخت مبلغ پروژه مشارکت مردمی به‌صورت inline، بدون مخلوط شدن با اقلام Cart عادی user.

## Main Components

- `inc/woocommerce/class-ba-participation-payment-gateway-service.php`
- `inc/woocommerce/class-ba-participation-quick-checkout-service.php`
- `inc/woocommerce/class-ba-woocommerce-participation.php`
- `inc/admin/settings/tab-participation.php`
- participation Elementor widget
- `assets/js/bonyad-alavi-participation-widget.js`
- scoped participation CSS

Decision: [ADR-002](../decisions/ADR-002-participation-quick-checkout.md)

## Flow

```text
select amount
 -> validate project/product amount
 -> prepare invoice + inline billing fields
 -> validate WooCommerce billing/hooks
 -> create isolated order with one project line item
 -> resolve exact configured gateway
 -> run is_available/process_payment in isolated cart
 -> restore real cart
 -> redirect/payment result
```

## Gateway Settings

- selected ID: `ba_participation_settings[payment_gateway]`
- settings list فقط WooCommerce gateways با `enabled=yes`
- Merchant/API config متعلق به خود WooCommerce gateway است.
- silent fallback به gateway دیگر ممنوع.
- ID case-sensitive است؛ `sanitize_key()` برای persist ممنوع.
- legacy lowercase value می‌تواند به `$gateway->id` واقعی resolve شود.
- settings compatibility helper: `ba_resolve_participation_gateway_id_for_settings()`
- independent sanitizer: `ba_sanitize_participation_gateway_id()`
- UI/compat path قبل از methodهای versioned service guard دارد.
- Quick Checkout resolver قدیمی/جدید را با ID واقعی تطبیق می‌دهد.

## Isolated Cart

`BA_Participation_Payment_Gateway_Service::with_isolated_cart()` باید Session/Persistent Cart واقعی user را snapshot/restore کند.

Order:
- فقط project جاری line item
- meta `_bap_participation_amount`
- meta `_bap_goal_amount`

سایر Cart items نباید وارد order یا توسط gateway حذف شوند.

Gatewayهای دارای `has_fields()` فعلاً target این flow نیستند؛ contract Hosted/Redirect gateway است.

## Billing-only Inline Checkout

Fields فقط از:
`WC_Checkout::get_checkout_fields()['billing']`

Render:
`woocommerce_form_field()`

گروه‌های shipping/account/order inline render یا required-validate نمی‌شوند.

Third-party custom field داخل Billing همچنان می‌تواند خودکار نمایش داده شود.

Terms/Privacy و standard checkout validation hooks حفظ شوند.

Scoped CSS:
داخل `bap__donation-form`، form rows width/max-width 100% با force لازم و float reset؛ override نباید به فرم‌های دیگر WooCommerce leak کند.

## Amount Validation

Prepare و Process هر دو از:
`Bonyad_Alavi_WooCommerce_Participation::validate_project_amount()`

عبور کنند. Client state authority نیست.

## Country / State Initialization

پس از fix legacy v0.6.5:
- eventهای internal `country_to_state_changing`, `country_to_state_changed`, `updated_checkout` بدون context دستی trigger نشوند.
- `wc-enhanced-select-init` برای fields injected حفظ شود.
- روی `billing_country` / `.country_to_state` داخل همان `bap__quick-checkout` change واقعی اجرا شود.
- WooCommerce `country-select.js` eventهای داخلی را با wrapper/context صحیح تولید کند.
- initialization به DOM Quick Checkout scope بماند.

این contract خطای `Cannot read properties of undefined (reading 'find')` را هدف گرفته است.

## UI Namespace

مرحله inline زیر block `bap` و elements `bap__quick-*` بماند. selector عمومی checkout جدید نساز.

## Regression Areas

در تغییرات این subsystem بررسی شود:
- amount validation هر دو مرحله
- one-line-item order
- real cart snapshot/restore
- case-sensitive gateway ID
- legacy lowercase compatibility
- unavailable gateway explicit error
- billing-only validation
- terms/privacy/hooks
- form-row scope
- country/state initialization
- no unrelated form side effects

## Historical Evolution

v0.6.0 تا v0.6.5 به‌ترتیب inline checkout، sanitizer independence، case-sensitive ID، mixed-patch compatibility، billing-only layout و country/state fix را اضافه کردند. جزئیات historical در [state-v002](../state/state-v002.md) است.


## Quick Support category payments — Phase 1 in PR #34

پیاده‌سازی فاز اول در [PR #34](https://github.com/mohsenfarhangi/bonyad-alavi-wp/pull/34) قرار دارد؛ هنوز merge/deploy و live acceptance نشده است.

- AFE فرم مشترک `quick-support-payment` و Generic Form Context را فراهم می‌کند؛ Child Theme دسته‌ها، Context Provider، Action و Payment/Order service را مدیریت می‌کند.
- `BA_Participation_Payment_Gateway_Service` برای resolve کردن Gateway و isolated cart قابل استفاده مجدد است؛ متد `get_gateway_by_id()` و ورودی اختیاری cart item data به آن اضافه شده است.
- Quick Checkout service فعلی project-centric است و `validate_project_amount()` به محصول پروژه و goal/minimum amount آن وابسته است؛ این validator برای دسته‌بندی Quick Support مستقیماً استفاده نشود.
- سرویس Quick Support مبلغ را براساس category min/max اعتبارسنجی و با WooCommerce CRUD سفارش مستقل ایجاد می‌کند.
- یک محصول فنی مشترک Simple/Virtual/Hidden برای line item و Cart موقت Gateway استفاده می‌شود؛ خرید مستقیم آن مسدود است.
- کاربر مستقیماً به Hosted/Redirect Gateway می‌رود؛ Cart/Checkout عادی استفاده نمی‌شود.
- WooCommerce Orders منبع مالی گزارش‌ها و Refund خواهند بود؛ نسخه اول جدول مالی موازی ندارد.

**Staging acceptance لازم:** Gateway واقعی، callback/return، حفظ Cart/Session/Persistent Cart و HPOS. داشبورد و گزارش‌ها Phase 2 هستند.

طرح کامل: [MODULE-QUICK-SUPPORT](quick-support-payments.md)، تصمیم: [ADR-009](../decisions/ADR-009-quick-support-payments.md).

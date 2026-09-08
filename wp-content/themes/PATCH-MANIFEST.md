# Patch v0.6.5

مبدأ: `ostadsho-child v0.6.4`  
مقصد: `ostadsho-child v0.6.5`

## هدف

رفع خطای JavaScript زیر هنگام کلیک اول برای آماده‌سازی پرداخت سریع:

`Cannot read properties of undefined (reading 'find')`

## علت

Handler ویجت مشارکت Eventهای داخلی WooCommerce یعنی `country_to_state_changing`، `country_to_state_changed` و `updated_checkout` را بدون آرگومان‌های لازم دستی Trigger می‌کرد. Country/State Handler ووکامرس برای این Eventها wrapper/context لازم دارد و در نبود آن `.find()` روی `undefined` اجرا می‌شد.

## فایل‌های Patch

- `assets/js/bonyad-alavi-participation-widget.js` — حذف Trigger دستی Eventهای داخلی و راه‌اندازی Country/State از طریق `change` واقعی فیلد Billing Country داخل Quick Checkout.
- `docs/handoff.md` — ثبت قرارداد توسعه برای Eventهای Checkout ووکامرس.
- `docs/versions/v0.6.5.md` — جزئیات نسخه و تست‌ها.

## اعمال Patch

محتویات پوشه `ostadsho-child` داخل این Patch را روی پوشه قالب فرزند موجود جایگزین کنید.

هیچ فایل حذفی در این نسخه وجود ندارد.

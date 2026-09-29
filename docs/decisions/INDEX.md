# Decision Index

قبل از باز کردن ADRها، از این جدول تصمیم مرتبط را پیدا کن.

| ID | Topic / Keywords | Decision | Status | File |
|---|---|---|---|---|
| ADR-001 | AFE, form schema, admin overrides, source of truth | PHP code-defined form remains the base definition; admin can apply only supported overrides. | Accepted | [ADR-001-code-defined-forms.md](ADR-001-code-defined-forms.md) |
| ADR-002 | WooCommerce, participation, quick checkout, cart isolation, gateway | Inline participation payment creates an isolated order/payment flow and must not consume or mutate the user's real cart. | Accepted | [ADR-002-participation-quick-checkout.md](ADR-002-participation-quick-checkout.md) |

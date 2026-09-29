# Decision Index

| ID | Topic / Keywords | Decision | Status | File |
|---|---|---|---|---|
| ADR-001 | AFE, form schema, overrides | Code-defined form base remains authoritative; Admin applies supported overrides only. | Accepted | [ADR-001-code-defined-forms.md](ADR-001-code-defined-forms.md) |
| ADR-002 | WooCommerce, participation, cart, gateway | Participation Quick Checkout isolates order/cart from the user's real cart. | Accepted | [ADR-002-participation-quick-checkout.md](ADR-002-participation-quick-checkout.md) |
| ADR-003 | theme settings, AJAX, registry, capability | Theme settings use one registry-driven persistence path with progressive fallback. | Accepted | [ADR-003-shared-theme-settings-persistence.md](ADR-003-shared-theme-settings-persistence.md) |
| ADR-004 | jihadi center, dashboard, Elementor, precedence | Effective center settings are resolved centrally from two sources with explicit precedence. | Accepted | [ADR-004-jihadi-center-source-precedence.md](ADR-004-jihadi-center-source-precedence.md) |
| ADR-005 | admin repeater, shared component, BEM | Custom wp-admin repeaters share one UI component; feature owns sanitization/storage. | Accepted | [ADR-005-shared-admin-repeater.md](ADR-005-shared-admin-repeater.md) |
| ADR-006 | AFE, export, Composer, PhpSpreadsheet, ZipStream, PDF | Export packages are build-time dependencies and Excel autoload is isolated from foreign plugin vendors. | Accepted | [ADR-006-afe-export-dependency-isolation.md](ADR-006-afe-export-dependency-isolation.md) |

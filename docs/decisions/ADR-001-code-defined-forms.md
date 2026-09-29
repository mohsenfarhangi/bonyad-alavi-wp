# ADR-001 — Code-defined forms remain the base source of truth

**Status:** Accepted  
**Date recorded:** 2026-09-29  
**Decision observed in implementation/docs:** existing before this ADR

## Context

Alavi Form Engine supports code-defined forms while administrators can customize layout/field behavior. A direct mutable copy of schema would make later code updates unsafe and could allow admin configuration to alter behavior beyond supported contracts.

Current architecture:

`Code Definition -> allowed Admin Overrides -> Runtime Form`

Validation/security authority remains server-side.

## Decision

- PHP form definitions are the base source of truth.
- Admin configuration is an override layer, not replacement executable schema.
- only whitelisted properties/registries may be overridden.
- ordering override cannot create fields or move them across unsupported scopes.
- raw PHP/callback execution is not accepted from Admin configuration.
- new code-defined items absent from saved ordering are resolved/appended safely inside their original scope.

## Reasoning

این مدل ownership رفتار فرم را در deployable code نگه می‌دارد، در حالی که customization عملیاتی Admin را بدون تخریب در update بعدی ممکن می‌کند.

## Consequences

- task فرم باید source definition و resolver/override path را هر دو بررسی کند.
- تغییر override schema ممکن است migration/backward compatibility بخواهد.
- Admin UI proof of server authority نیست؛ normalization/validation سمت PHP نهایی است.
- module docs باید به این ADR ارجاع دهند و reasoning را duplicate نکنند.

## Related Modules

- [MODULE-AFE](../modules/alavi-form-engine.md)
- [MODULE-AFE-EXTENSION](../modules/alavi-form-engine-extension-api.md)

## Relevant Source Files

- `../../wp-content/plugins/alavi-form-engine/src/Form/`
- `../../wp-content/plugins/alavi-form-engine/src/Admin/`

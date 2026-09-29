# ADR-001 — Code-defined forms remain the base source of truth

**Status:** Accepted  
**Date recorded:** 2026-09-29  
**Decision observed in implementation/docs:** existing before this ADR

## Context

Alavi Form Engine supports code-defined forms while administrators can customize layout/field behavior. A direct mutable copy of schema would make later code updates unsafe and could allow admin configuration to alter behavior beyond supported contracts.

Existing AFE architecture explicitly describes:

`Code Definition -> allowed Admin Overrides -> Runtime Form`

and keeps validation/security behavior server-side.

## Decision

- PHP form definitions are the base source of truth.
- Admin configuration is stored/applied as an override layer, not as a replacement executable schema.
- Only whitelisted properties/registries may be overridden.
- Ordering override cannot create fields or move them across unsupported scopes.
- raw PHP/callback execution is not accepted from admin configuration.
- when code introduces a new item absent from saved ordering, runtime resolution must preserve the code definition and append/resolve it safely.

## Reasoning

This preserves deployable code ownership of form behavior while allowing operational customization, and reduces the risk that upgrades destroy or silently replace admin changes.

## Consequences

- Form tasks must inspect both source definition and resolver/override path.
- migrations/backward compatibility may be required when override schema changes.
- admin UI is not proof of server authority; normalization/validation remain server-side.
- module docs should reference this ADR rather than duplicate full reasoning.

## Related Modules

- [MODULE-AFE](../modules/alavi-form-engine.md)

## Relevant Files / Docs

- `../../wp-content/plugins/alavi-form-engine/docs/ARCHITECTURE.md`
- `../../wp-content/plugins/alavi-form-engine/docs/PROJECT_STATE.md`
- `../../wp-content/plugins/alavi-form-engine/src/Form/`
- `../../wp-content/plugins/alavi-form-engine/src/Forms/`

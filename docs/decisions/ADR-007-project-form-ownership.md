# ADR-007 — Project-specific form definitions are owned by the site integration

**Status:** Accepted  
**Date recorded:** 2026-09-29  
**Decision source:** explicit project decision to move the Jihadi registration form from Alavi Form Engine to the Bonyad Alavi child theme

## Context

Alavi Form Engine is a reusable form engine, while `jihadi-group-registration` contains project-specific content, fields, workflow defaults and actions for Bonyad Alavi. Keeping this Source Definition inside the engine coupled a generic plugin release to one site's business form.

A WordPress theme's `functions.php` is loaded after `plugins_loaded` and before `after_setup_theme`. Therefore the engine must not finalize its Form Registry on `plugins_loaded` if the active theme is expected to register forms through `afe_register_forms`.

## Decision

- Alavi Form Engine no longer directly registers `JihadiGroupRegistrationForm` and does not ship a project-specific built-in form definition.
- The Bonyad Alavi child theme owns the Source Definition at `inc/forms/class-ba-jihadi-group-registration-form.php`.
- The theme registers the form through the existing public hook `afe_register_forms`.
- AFE boot is scheduled on `after_setup_theme` priority `20`, after active theme `functions.php` has loaded.
- The form slug remains exactly `jihadi-group-registration`.
- Existing Admin overrides, form row, submissions, capabilities and duplicate/export/action settings continue to resolve by the same slug; no data migration is introduced by this move.
- Engine-level validators, fields, actions, rendering, storage, admin and export behavior remain inside the plugin.

## Consequences

- Activating a different theme without an equivalent registration integration means the Jihadi form will not be present in the runtime registry, although stored submissions/data are not deleted.
- Plugin tests must remain engine-generic; project-form regression coverage belongs with the child theme.
- Future project-specific forms should be registered from the site integration layer rather than added to `src/Forms` in AFE.
- Any future AFE lifecycle change must preserve a registration window for the active site integration before `afe_register_forms` fires.

## Related Modules

- [MODULE-AFE](../modules/alavi-form-engine.md)
- [MODULE-AFE-EXTENSION](../modules/alavi-form-engine-extension-api.md)
- [MODULE-JIHADI-FORM](../modules/jihadi-group-registration-form.md)

## Relevant Source Files

- `../../wp-content/plugins/alavi-form-engine/alavi-form-engine.php`
- `../../wp-content/plugins/alavi-form-engine/src/Core/Plugin.php`
- `../../wp-content/themes/ostadsho-child/functions.php`
- `../../wp-content/themes/ostadsho-child/inc/forms/class-ba-jihadi-group-registration-form.php`

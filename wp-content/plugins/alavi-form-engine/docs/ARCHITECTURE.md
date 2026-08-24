# Architecture

## Core principles

Alavi Form Engine treats PHP definitions as the source of truth and stores admin changes as overlays. This prevents an update of a form module from destroying runtime customization.

The core follows separation of concerns:

- **Definition layer**: `Form`, `Step`, field value objects.
- **Registry**: discovers code-defined forms.
- **Rendering layer**: converts resolved definitions to HTML without owning persistence.
- **Validation layer**: reusable server-side rules.
- **Data-source layer**: Strategy-style source resolution.
- **Submission layer**: application service coordinating validation, persistence, files, actions and events.
- **Repository layer**: database persistence.
- **Action pipeline**: post-submit commands/adapters.
- **Event dispatcher**: listeners and WordPress-compatible hooks.
- **Infrastructure**: migrations, dedicated storage, REST and Elementor adapters.
- **Admin UI**: form overrides, submissions, reports, database health and role capabilities.

## Patterns

- Registry
- Strategy
- Adapter
- Repository
- Application Service
- Command/Action Pipeline
- Observer/Event Dispatcher
- Dependency Injection through the plugin composition root

## Data model

Shared tables:

- `wp_afe_forms`
- `wp_afe_submissions`
- `wp_afe_submission_values`
- `wp_afe_files`
- `wp_afe_notes`
- `wp_afe_audit_log`
- `wp_afe_geo_provinces`
- `wp_afe_geo_counties`
- `wp_afe_geo_districts`

Forms can opt into a dedicated mirror table named `wp_afe_form_{slug}` while shared tables remain authoritative for workflow, audit and cross-form reporting.

## Override resolution

Runtime definition:

`Code Definition -> Field/Step/Admin Overrides -> Runtime Form`

Only whitelisted field properties are overrideable in version 1. This protects validators and server-side behavior from accidental template changes.

## Template system

A developer can use `HtmlBlock` anywhere between fields.

A Step can define an HTML template and place fields using:

- `{{items}}`
- `{{field:field_name}}`

Admin Step templates use the same tokens. The overall form template supports:

- `{{title}}`
- `{{description}}`
- `{{steps}}`

## Extensibility

Use the WordPress actions:

- `afe_register_forms`
- `afe_register_data_sources`
- `afe_register_actions`
- `afe_register_events`
- `afe_booted`

The engine can therefore be extended by a site plugin or theme without editing the Alavi Form Engine plugin itself.

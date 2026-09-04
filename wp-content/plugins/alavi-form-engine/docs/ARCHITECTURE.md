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
- **Action registry + pipeline**: registry-driven action metadata/handlers, conditional execution, once guards and post-submit/lifecycle commands.
- **Event registry + dispatcher**: canonical event definitions with Persian UI labels, listeners and WordPress-compatible hooks.
- **Token registry/resolver**: whitelisted submission/form/field tokens shared by text-based actions.
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
- `wp_afe_action_log`
- `wp_afe_action_once`
- `wp_afe_submission_fingerprints`
- `wp_afe_geo_provinces`
- `wp_afe_geo_counties`
- `wp_afe_geo_districts`

Forms can opt into a dedicated mirror table named `wp_afe_form_{slug}` while shared tables remain authoritative for workflow, audit and cross-form reporting.

## Action runtime and retry

`ActionManager` executes a configured Event chain with one `ActionRuntime`. Runtime values are transient chain outputs rather than submitted field data: they carry values such as the effective `user_id`, `post_id`, status, first effective redirect and follow-up canonical Events. This allows Actions to compose without adding ad-hoc coupling to `SubmissionService`.

Action execution is persisted separately in `afe_action_log`, while `afe_action_once` is the atomic guard for once-per-submission policies. Administrative retry resolves the **current** Action definition/configuration by stable `action_key`, rechecks the Event and Conditional Logic, and only then releases a once guard. Follow-up Events are processed through the same Action Engine with cycle bounds.

## Override resolution

Runtime definition:

`Code Definition -> Field/Step/Admin Overrides -> Runtime Form`

Only whitelisted field properties are overrideable in version 1. This protects validators and server-side behavior from accidental template changes.

## Template system

AFE 1.0.27 uses a shared `TemplateDefinition -> TemplateRegistry -> TemplateResolver` pipeline. The renderer and the admin editor therefore read the same default source instead of maintaining separate hard-coded markup.

Core template definitions are:

- `form` — overall form shell/header/progress/steps.
- `preview` — preview/read-only data layout.
- `step` — content layout inside a Step grid.

A code-defined Form/Step/Preview template becomes that form's default. If code does not provide one, the registry's AFE core default is used. Admin HTML is only stored when it differs from that resolved default. Resetting an editor and saving removes the override, allowing future code-default changes to flow through.

A developer can use `HtmlBlock` anywhere between fields.

Step templates support:

- `{{items}}`
- `{{field:field_name}}`

The overall form template supports:

- `{{title}}`
- `{{description}}`
- `{{brand_mark}}`
- `{{slogan}}`
- `{{progress}}`
- `{{steps}}`

Preview templates support:

- `{{title}}`
- `{{description}}`
- `{{preview_title}}`
- `{{preview_description}}`
- `{{preview_fields}}`
- `{{field:field_name}}`

New template definitions can be registered through `afe_register_templates`. Admin template editors use one generic reset/status behavior for all registered/current template surfaces.

## Extensibility

Use the WordPress actions:

- `afe_register_forms`
- `afe_register_data_sources`
- `afe_register_action_definitions`
- `afe_register_actions` (legacy/runtime handler compatibility)
- `afe_register_event_definitions`
- `afe_register_events`
- `afe_register_templates`
- `afe_booted`

The engine can therefore be extended by a site plugin or theme without editing the Alavi Form Engine plugin itself.

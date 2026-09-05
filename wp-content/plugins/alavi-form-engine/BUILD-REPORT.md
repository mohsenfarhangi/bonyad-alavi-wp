# Build / QA Report — Alavi Form Engine 1.0.28-dev Feature-complete Pre-acceptance Checkpoint

## Status

**Feature-complete against the 1.0.28 handoff, not yet production-tagged.** Stable production baseline remains **1.0.27**. Plugin version remains **1.0.28-dev**, Stable tag remains **1.0.27**, and DB development checkpoint remains **1.0.5-dev.2**.

The project administrator reported the real MeliPayamak account test **PASS** on 2026-09-04. Per project direction, browser-driven acceptance will be run after feature work is complete. No production version/stable-tag bump is performed before that acceptance.

## Changes in this checkpoint

- Numeric/identifier controls (`tel`, `number`, `date`, and numeric/decimal/tel input modes) now render with semantic `dir="ltr"` and left-aligned values while labels/form layout remain RTL.
- Added the same LTR/left-aligned behavior to wp-admin Submission editing and admin Repeater child controls.
- Hardened Strong Isolation defense with an explicit `data-afe-ltr="1"` marker and higher-specificity `input.afe-control[data-afe-ltr="1"]` override so the generic `.afe-control { text-align:right !important; }` rule cannot win in staging/theme cascade; no reliance on `:is()` remains for the guaranteed override.
- Added `tests/numeric-input-direction.php` and expanded Input Mask Renderer/Admin assertions.
- Added `SmsProviderRegistry` routing with a global default provider and per-SMS-Action override.
- Added `PersianWooCommerceSmsProvider`, delegating through the external plugin public `PWSMS()->send_sms()` API and active gateway without copying credentials into AFE.
- Added runtime capability detection for free/pattern modes, known Pattern payload adapters for MeliPayamak service/combined and Kavenegar Lookup gateways, plus extension filters for additional gateways.
- Added SMS settings/provider status UI and Action Builder capability-aware mode selection; unavailable saved providers are preserved rather than silently rewritten.
- Decoupled the SMS master switch from AFE SecretStore availability so an external provider can operate without AFE storing credentials.
- Extended bootstrap preflight and regression coverage for the new SMS router/integration.
- Simplified the «فیلدها و چیدمان» tab to layout-only Drag & Drop; Field Override editors now open contextually in `afe-admin-side` when a Field/Repeater child is selected.
- Kept HtmlBlock items reorderable but non-configurable, added selected-field/close/session UX, and moved per-Step template editors to the Templates tab.
- Moved the settings form boundary around the full admin layout so sidebar override controls submit through the existing save/sanitization path.
- Added responsive/sticky sidebar styling for field configuration and `tests/admin-field-layout-sidebar.php` regression coverage.
- Added `UserActionGuard` and runtime privileged-target checks for Login/Update/Assign Role/Update User Meta and existing-user branches of Create User. Administrator accounts and custom accounts with `manage_options` require an explicit protected opt-in controlled by `afe_manage_settings`.
- Blocked WordPress capability/session/application-password meta keys (`*_capabilities`, `*_user_level`, `session_tokens`, `_application_passwords`) from Create User mappings and Update User Meta at runtime.
- Prevalidates the complete User Meta mapping before user creation/update or the first meta write, preventing rejected protected keys from leaving partial user side effects.
- Assign Role now treats custom roles that grant `manage_options` as privileged, not just the literal `administrator` role.
- Tokenized URL settings now preserve valid `{{...}}` templates through admin sanitization; runtime Webhook/URL consumers still sanitize the fully-resolved URL.
- `ActionDefinition::supportsExecutionPolicy=false` is now honored by the Action Builder save path and UI.
- Added renderer-level regression proving Jalali/Gregorian `combined`, `picker`, and `manual` output modes.
- Added `tools/qa.sh` as a unified preflight for PHP version, all regression tests, PHP lint, JS syntax, composer JSON, local Jalali assets, external runtime asset registration, and plugin version consistency.
- Added `docs/FEATURE-COMPLETENESS-1.0.28.md` mapping the handoff feature set to implementation/test coverage.

## QA performed

- Unified `tools/qa.sh`: **PASS**.
- Standalone regression suite: **PASS (46 test scripts)**.
- PHP syntax lint across `src/`, `tests/`, plugin bootstrap and uninstall: **PASS (148 PHP files)**.
- `assets/js/admin.js` and `assets/js/frontend.js`: **PASS**.
- `composer.json`: **PASS**.
- Local JalaliDatePicker JS/CSS presence: **PASS**.
- Runtime JS/CSS external-CDN registration check: **PASS**.
- Plugin header / `AFE_VERSION` consistency: **PASS (`1.0.28-dev`)**.
- New/expanded hardening tests include:
  - `tests/user-action-security.php`
  - `tests/user-meta-preflight.php`
  - `tests/date-renderer-modes.php`
  - `tests/action-execution-policy-metadata.php`
  - tokenized URL coverage in `tests/action-config-sanitizer.php`
  - `tests/input-mask-registry.php`
  - `tests/input-mask-renderer.php`
  - `tests/input-mask-submission-normalization.php`
  - `tests/input-mask-admin-presenter.php`
  - `tests/sms-provider-registry.php`
  - `tests/persian-woocommerce-sms-provider.php`
  - `tests/sms-action-provider-routing.php`

## Release blocker / live acceptance

This build environment has no complete WordPress + MySQL/MariaDB + browser + Elementor runtime. The project owner will run the practical acceptance after all features are in place. The exact matrix remains in `docs/ACCEPTANCE-1.0.28.md`.

Do **not** bump to `1.0.28` / DB `1.0.5` / Stable tag `1.0.28` until that live matrix passes.


## Input Mask checkpoint

- Added `InputMaskDefinition`, `InputMaskRegistry` and non-executable `InputMaskPattern`.
- Core presets: Iranian mobile, landline, national ID, postal code, bank card and 24-digit IBAN.
- Added Field Override UI with inherit / none / preset / custom modes.
- Custom syntax: `9` digit, `A` letter, `*` alphanumeric, backslash escape; no raw JS/regex/PHP execution.
- Renderer applies mask metadata and validates normalized legacy HTML pattern/length constraints rather than formatted string length.
- Submission normalization removes mask literals before validation, duplicate fingerprints, Actions/Tokens/SMS and persistence.
- Admin Submission detail/edit/export presentation and dynamically-added admin Repeater rows render the same masks; admin saves normalize values again in PHP.
- Jihadi source defaults now apply appropriate masks to mobile, landline, national-ID and legal-IBAN fields; admin overrides can replace/disable them.
- Regression coverage added for registry/pattern behavior, Renderer integration and Submission normalization.

## Preserved 1.0.28-dev feature set

- Registry-driven Event/Action Builder, TokenResolver/Palette, ActionRuntime, once guards, Action logs/retry, Redirect/User/Status/Note/PDF/Post actions.
- MeliPayamak legacy and console API-token modes, free-text and Pattern sends.
- Duplicate Policy with Draft matching, trash exclusion, current-submission exclusion, allow+mark and transaction-safe owner promotion.
- Per-Step/Repeater field ordering, Date input modes, character/length overrides, Validator Registry/UI and safe Custom Regex.

## Automated checks performed for this package

- PHP syntax lint across `src/` and `tests/`: PASS (148 PHP files including bootstrap/uninstall at this checkpoint; code target remains PHP >= 8.3).
- Built-in validator tests: PASS (Iran IBAN checksum, National ID valid/invalid checksum, Persian mobile digits, digit normalization).
- Built-in Jihadi Group Registration form construction: PASS (13 steps, 62 top-level non-HTML items, 3 repeaters, 5 file fields).
- JavaScript syntax check for front-end/admin assets: PASS.
- `composer.json` JSON validation: PASS.
- ZIP integrity test: performed after packaging.

## Runtime note

This build environment does not contain a complete browser-driven WordPress installation, so the package has not been end-to-end clicked through against a live WordPress + MySQL + Elementor instance here. The plugin therefore includes a Database Health/Repair screen, defensive migration-on-boot, and runtime feature detection for optional Elementor/Dompdf integrations.

## Geography data

The plugin seeds all 31 Iranian provinces offline. County/district data is downloaded only when an authorized administrator explicitly runs the geography update from the database screen. See `THIRD-PARTY-NOTICES.md`.

## PDF

Browser print / Save as PDF is always available from a submission. Direct server-side PDF is used when `Dompdf\Dompdf` is already available through the site's Composer/autoload environment; Dompdf is not bundled in this ZIP.

## 1.0.5 Geography Chunk Import
- Manual geography JSON import uses authenticated WordPress Ajax.
- Files are sliced client-side into 32 KiB chunks.
- Complete JSON rows are parsed per request and bulk-upserted immediately; only an incomplete trailing object is retained for the next chunk.
- Upload sessions are retry-safe/idempotent.
- UI exposes per-file upload, upload-all sequencing, progress, chunk count, database row count, processing state and retry.
- QA: 54 PHP files linted, admin/frontend JS syntax checked, validator suite passed, incremental JSON parser test passed, 37-chunk simulated DB import + retry test passed.

## 1.0.6 - Isolated Custom Select
- All AFE SelectField controls default to the AFE custom select UI.
- Native select remains submission/validation source of truth.
- Theme-level Select2 containers are suppressed and cleaned only inside `.afe-form`.
- `SelectField::custom()`, `native()`, `searchable()` and `searchThreshold()` added.
- Admin overrides can select Custom/Native UI and search behavior per field.
- Custom select supports keyboard navigation, search, multiple selection, dependent Ajax sources, repeater-created selects, disabled options and responsive up/down opening.
- Existing Elementor/autoload/geography chunk-import fixes retained.


## 1.0.11 - Scoped Style Isolation
- Design tokens moved from global `:root` to `.afe-shell`.
- Added `StyleIsolationManager` with `strong`, `default`, and `disabled` modes; default is `strong`.
- Global admin setting and per-form admin override added.
- Renderer emits isolation mode as both a scoped class and `data-afe-isolation`.
- Strong mode contains a scoped defensive reset plus limited component-level `!important` rules for hostile theme form CSS.
- Per-form code API: `Form::styleIsolation()`.


## 1.0.12 - Upload UX / Theme Script Isolation

- File input remains native for FormData/server compatibility but is visually isolated behind AFE dropzone UI.
- `input`/`change` events are stopped at AFE file controls to prevent delegated theme handlers from assigning prohibited file values.
- Selection UI exposes file name, type, size, total size and per-file removal.
- Client-side file constraints are enforced during step validation; server validation remains authoritative.


## 1.0.14 - Existing File Ownership / Safe Replacement

- Exact `submission_id + field_key` repository query for every upload field.
- Explicit keep/remove manifest validated server-side against the same field ownership tuple.
- Single-file replace semantics and rollback protection when a new upload fails.
- Existing upload cards contain no anchors, preventing Elementor/theme lightbox capture.

## 1.0.14 cross-step validation UX
- Added a grouped validation summary for final submission.
- Errors are grouped by step and show field label + exact message.
- Clicking an error navigates to the owning step, scrolls to the field and focuses it.
- Progress dots display error state and per-step error count.
- Server-side validation errors use the same summary and are no longer overwritten by the generic error notice.
- Final client validation checks every step before submission instead of stopping at the first invalid step.

## 1.0.15 upload validation hotfix
- Normalizes both scalar and array-shaped nested PHP uploads.
- Adds a client upload manifest so server-side validation can distinguish "not selected" from "selected but rejected/dropped by PHP".
- Maps PHP upload transport error codes to field-level Persian messages instead of incorrectly returning "required".
- Keeps existing-file ownership/replace semantics from 1.0.13+ intact.

## 1.0.17 regression fix
- Fixed duplicate required validation for FileField. Generic Form Validator now excludes file inputs; FileUploader is the single source of truth for upload/existing-file validation.
- Added tests/file-generic-validator-regression.php.

## 1.0.18 admin presentation & editing

- Submission list resolves and displays the form title instead of the internal slug.
- Submission detail, print/PDF and Excel resolve field labels from the resolved form schema (including admin overrides).
- Repeater values render as readable nested tables instead of raw JSON.
- Admin users with edit capability can edit data inline in the same submission table; repeaters support row add/remove UI.
- Submission Excel exports human-readable labelled values instead of raw data JSON.
- File rows use the FileField title rather than field_key.
- Field override editor is grouped by form step and uses the field title as the primary identifier, with the technical path only as secondary metadata.

## 1.0.19 locale dates / per-form authorization

- Added `Localization\LocaleDateService` with Gregorian↔Jalali conversion, WordPress timezone handling and UTC filter boundaries.
- Submission list/detail, internal notes, applicant recent submissions, database geography update timestamp, Print/PDF and Excel dates use the locale date service.
- Date field presentation/editing converts between the field storage calendar and the WordPress site calendar.
- Submission and Reports date filters switch to JalaliDatePicker for `fa_*` locales and native Gregorian controls otherwise.
- Added `Core\FormAccess` with six generated capabilities per registered form and secure first-migration behavior.
- Enforced form scope in submission admin, reports, exports, form configuration, frontend admin editing and REST management endpoints.
- Added admin UI matrix to assign per-form permission levels by WordPress role.
- Added regression tests for Persian/English date conversion, UTC date boundaries and per-form capability names.

## 1.0.19 final QA

- 65 PHP files linted successfully.
- Admin and front-end JavaScript syntax checks passed.
- Validator, upload ownership, style isolation, locale-date and per-form capability regression suites passed.
- Form-level `manage` now gates internal note visibility and uploaded-file metadata/access in admin submission detail.
- Existing legacy role permissions are migrated once; forms registered after migration are Administrator-only until explicitly granted.


## 1.0.20 preview / lock workflow

- 13 data steps + optional system preview step: PASS
- Jihadi preview_enabled / lock_after_submit / edit-request defaults: PASS
- PHP syntax: PASS
- Frontend/admin JavaScript syntax: PASS
- Existing validator/date/style regression tests: PASS
- Locked POST enforcement, admin unlock workflow and request-edit endpoints implemented.


## 1.0.21 trash / lock / captcha
- Soft-delete lifecycle with restore/permanent purge.
- Locked submissions are excluded from active frontend lookup only when trashed; lock workflow remains persisted.
- Lock-after-submit redirects to persisted locked preview to avoid stale editable DOM.
- Custom CAPTCHA supports challenge refresh without page reload.

### 1.0.21 QA
- 66 PHP files linted: PASS.
- frontend.js syntax: PASS.
- admin.js syntax: PASS.
- Existing validator, file ownership, form-access, locale-date and style-isolation tests: PASS.
- Submission repository excludes trashed rows from active list/tracking/token/recent/report scopes.
- Permanent deletion requires a trashed row and purges values, notes, file relations, dedicated mirror row and submission audit rows before final global audit.
- Lock-after-submit front-end redirects to persisted locked view; public renderer no longer bypasses lock based on admin capabilities.
- Custom CAPTCHA exposes AJAX refresh and auto-refreshes after captcha validation errors.


## 1.0.22 admin menu gateway capabilities

- Fixed WordPress admin menu visibility when a role has only per-form AFE permissions.
- Added automatic gateway-capability synchronization with ownership tracking so AFE only removes gateway caps it added itself.
- Reordered settings save synchronization so `afe_access_admin` is recalculated after gateway caps are updated.

## 1.0.23 local assets / Jihadi form updates

- Browser runtime script/style registrations use only local plugin URLs.
- JalaliDatePicker dist files are intentionally not bundled; runtime expects them under `assets/vendor/jalalidatepicker/`.
- Desktop Jalali picker positioning is recalculated from the active input and refreshed on scroll/resize.
- Jihadi form regression coverage includes 09-prefixed 11-digit group mobile, optional 24-digit IBAN UI, 10-digit National IDs, renamed central council and repeater-based coverage areas.
- Legacy target-area fields are mapped to the first coverage-area repeater row for backward compatibility.

## 1.0.24 per-form brand mark

- Added per-form resolved settings for `brand_mark_mode`, attachment ID/URL and alt text.
- Admin Forms screen uses the native WordPress Media Library picker for custom brand images.
- Renderer uses one brand-mark resolver for normal and locked-preview headers.
- Custom form templates can render the same resolved mark through `{{brand_mark}}`.
- `none` mode emits no brand-mark DOM; missing custom images safely fall back to the default AFE mark.
- Added regression test for Form fluent/default brand-mark settings.
- README.md and WordPress readme.txt updated for 1.0.24.


### 1.0.24 QA

- 69 PHP files linted successfully.
- Admin and frontend JavaScript syntax checks passed.
- All bundled regression tests passed, including brand-mark defaults/API, uploads, form access, locale dates, Jihadi form schema and style isolation.

## 1.0.25 Jalali first-open positioning

- Frontend JalaliDatePicker opening is now explicitly controlled with `jalaliDatepicker.show(input)`.
- The library `autoShow` option is disabled for AFE frontend fields to remove the focus-listener ordering race.
- Positioning retries for up to 30 animation frames until the picker exists and has layout dimensions.
- Picker width/height use `offsetWidth` / `offsetHeight`, avoiding scale-animation distortion on the first frame.
- Scroll is retained only as a reposition event, not as a prerequisite for opening.
- README.md and readme.txt updated for 1.0.25.


## 1.0.26 Jalali standard behavior rollback

- Removed all AFE frontend manual JalaliDatePicker opening/positioning code introduced after the stable baseline.
- Frontend now uses only `data-jdp` and the standard `jalaliDatepicker.startWatch()` initialization used by AFE before 1.0.23.
- Removed custom frontend `jdp-container`/`jdp-overlay` positioning CSS.
- README.md and readme.txt updated for 1.0.26.

## 1.0.27 Template Registry / Default Source Editors

- Added shared `TemplateDefinition`, `TemplateRegistry` and `TemplateResolver`.
- Admin editors for form, preview and step templates now display the resolved code/AFE default HTML instead of blank override fields.
- Generic reset-to-default behavior and live Default/Custom status implemented once for all current template editors.
- Default-equivalent submitted HTML is normalized to an empty stored override, preserving code defaults as the source of truth.
- One-time legacy normalizer removes stored form/preview/step template copies that are byte-equivalent after canonicalization to the current resolved default.
- Overall form template now exposes `{{progress}}`; renderer and editor use the same default source.
- Added `afe_register_templates` for future template definitions.
- Added `tests/template-registry.php` regression coverage.



## 1.0.28-dev deployment preflight patch

- Investigated staging fatal: `DuplicateRepository` class not found during plugin boot.
- Verified the class file, namespace and Composer PSR-4 mapping are correct in the checkpoint package.
- Added bootstrap preflight for 12 boot-critical classes/files so incomplete deployments stop safely with an admin notice and error-log diagnostic instead of a raw fatal.
- Added package autoload regression coverage and an incomplete-deployment simulation.
- Upgrade requirement: replace/install the complete plugin package; do not copy only changed files between checkpoints.

### QA

- 37 regression tests: PASS.
- 134 PHP files linted: PASS.
- JavaScript syntax, composer.json, local JalaliDatePicker, no-runtime-CDN and version consistency: PASS.

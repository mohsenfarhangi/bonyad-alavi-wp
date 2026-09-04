# Build / QA Report — Alavi Form Engine 1.0.28-dev Field / Date / Validator Checkpoint

## Status

**Development checkpoint / not production-ready.** Stable production baseline remains **1.0.27**. Plugin version remains **1.0.28-dev**, Stable tag remains **1.0.27**, and the DB development checkpoint remains **1.0.5-dev.2**. No new database migration was required in this checkpoint.

## Implemented in this checkpoint

### Field ordering

- Admin drag/drop ordering for every item inside the same Step, including `HtmlBlock`.
- Repeater child ordering is scoped to the same Repeater; no cross-Step move is stored or resolved.
- Runtime order is applied to the resolved form definition, so renderer, validation, preview and downstream consumers see the same ordered schema.
- Missing/new code-defined items are appended safely when an older order override does not know about them.

### Date input modes

- `DateField::inputMode()` with `combined`, `picker`, and `manual`, plus fluent helpers `combined()`, `pickerOnly()`, and `manualOnly()`.
- Default remains `combined` for compatibility.
- Jalali UX mask `YYYY/MM/DD`; Gregorian UX mask `YYYY-MM-DD`.
- Jalali manual-only fields do not activate/load the Jalali picker when no picker-enabled Jalali field exists.
- PHP calendar validation remains authoritative regardless of input mode.

### Character / length overrides

- Admin override modes: normal, digits-only, Persian letters, English letters, letters+digits.
- Independent additional-allowed and additional-forbidden character strings.
- Minimum, maximum, and exact length; exact length has validation priority.
- Digits-only identifiers remain text controls; renderer only applies numeric input hints and constraint metadata.
- Unicode-safe server-side length counting works with or without `mbstring`.

### Validator Registry / UI

- Added `Validation\ValidatorDefinition` and `Validation\ValidatorRegistry`.
- Added `afe_register_validator_definitions` extension point and composition-root registration.
- Core validators: Iranian National ID, mobile, IBAN, email, URL, 10-digit postal code, 16-digit bank card checksum, Iranian landline, calendar-aware date, and Custom Regex.
- Admin validator multi-select is filtered by Field type; each selected validator can define a custom error message.
- Existing legacy `rules` validation remains active for backward compatibility.
- Custom Regex requires `afe_manage_settings`, message is mandatory, pattern length/flags are restricted, save performs compile-test, and runtime PCRE adds `LIMIT_MATCH` / `LIMIT_RECURSION`.
- Frontend custom-regex validation is UX-only and gracefully skips PCRE syntax unsupported by JavaScript; PHP is final authority.

### Preserved earlier 1.0.28-dev work

- Registry-driven Event/Action Builder, TokenResolver/Palette, ActionRuntime, once guards, Action logs/retry, Redirect/User/Status/Note/PDF/Post actions.
- SMS/MeliPayamak legacy + console API implementations remain unchanged pending the project administrator's live account test.
- Duplicate Policy and transaction-safe fingerprint owner promotion remain active.

## QA performed

- Full standalone regression suite under `tests/*.php`: **PASS (28 test scripts)**.
- PHP syntax lint across `src/` and `tests/`: **PASS (122 PHP files)**.
- `assets/js/admin.js`: **PASS**.
- `assets/js/frontend.js`: **PASS**.
- `composer.json` parse validation: **PASS**.
- New coverage: `validator-registry.php`, `field-validation-overrides.php`, `date-field-modes.php`, `field-ordering.php`.
- Existing Action/Retry, SMS, Duplicate, upload ownership, form-access, locale-date, templates, style-isolation and Jihadi schema regressions remain green.

## Deliberately deferred

- Administrator live MeliPayamak integration result and any provider correction based on that concrete result.
- Final default Jihadi SMS Action text/pattern selection.
- Browser-driven acceptance against a real WordPress + MySQL/MariaDB + Elementor environment, including admin drag/drop UX and datepicker behavior.
- Final production version / DB / Stable tag bump.

Prepared for PHP 8.3+ / WordPress 6.4+.

## Automated checks performed for this package

- PHP syntax lint across `src/` and `tests/`: PASS (122 PHP files across src/tests at this checkpoint; code target remains PHP >= 8.3).
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


=== Alavi Form Engine ===
Contributors: bonyadalavi
Tags: forms, form-engine, submissions, workflow, elementor
Requires at least: 6.4
Requires PHP: 8.3
Stable tag: 1.0.25
License: GPLv3 or later

Code-first extensible form engine for WordPress.

== Description ==

Alavi Form Engine provides code-defined forms with admin overrides, workflows, submissions, repeaters, conditional logic, flexible data sources, secure uploads, reports, optional REST API and Elementor integration.

The package includes the "ثبت نام گروه‌های مردمی و جهادی" form as its first built-in form.

== Installation ==

1. Upload and activate the plugin.
2. Open Forms > Database and verify database health.
3. Optionally update the Iran geography dataset.
4. Download JalaliDatePicker assets and place them locally in:
   assets/vendor/jalalidatepicker/jalalidatepicker.min.js
   assets/vendor/jalalidatepicker/jalalidatepicker.min.css
5. Use [alavi_form id="jihadi-group-registration"].

== Changelog ==

= 1.0.25 =
* Fixed JalaliDatePicker requiring a small page scroll before becoming visible on frontend forms.
* Frontend Jalali fields now open explicitly through the official jalaliDatepicker.show(input) API on focus/click.
* Disabled the library autoShow path for AFE frontend fields to remove the open/position listener race condition.
* Added frame-based positioning retries so delayed picker rendering is positioned immediately without waiting for scroll.
* Picker dimensions now use layout size instead of the animated transformed rectangle for stable first-open placement.
* Updated README.md, readme.txt and BUILD-REPORT.md for the release.

= 1.0.24 =
* Added per-form afe-brand-mark settings with three modes: default AFE mark, custom Media Library image, or hidden.
* Added WordPress Media Library picker UI for choosing a custom brand image per form.
* Added optional brand image alt text and responsive contain-fit rendering for custom marks.
* Added {{brand_mark}} token for custom form templates so per-form brand-mark settings also work with custom layouts.
* Locked preview headers now respect the same per-form brand-mark configuration.
* Added Form fluent APIs: brandMark(), brandMarkImage() and hideBrandMark().
* Updated README.md, readme.txt and build documentation for the release.

= 1.0.23 =
* Removed runtime CDN dependency for JalaliDatePicker and switched AFE frontend/admin date assets to local plugin files.
* Added admin warning when the required local JalaliDatePicker JS/CSS files are missing.
* Fixed JalaliDatePicker positioning on large screens so the calendar opens next to the active date field even inside transformed/scrolled Elementor layouts.
* Fixed form container overflow that could clip custom select dropdowns and horizontal controls.
* Updated the Jihadi group registration title, subtitle, campaign title and slogan.
* Group mobile now requires exactly 11 numeric digits beginning with 09.
* Legal IBAN is now optional and accepts exactly 24 numeric digits while showing IR as a visual prefix only.
* Leader and deputy national ID fields now accept exactly 10 numeric digits and retain server-side checksum validation.
* Renamed "اعضای شورای مرکزی" to "اعضای شورای مرکزی (هسته اصلی)".
* Converted covered regions into a repeater so multiple geographic areas can be registered.
* County and district are now optional in each covered-region row, allowing province-level coverage.
* Added backward-compatible mapping for legacy target_province / target_county / target_district submission data.
* Added clearer client-side validation messages for mobile, national ID and IBAN constraints.
* Runtime form/admin scripts and styles no longer depend on externally hosted JS/CSS resources.

= 1.0.22 =
* Fixed admin menu visibility for roles that only have per-form submission/report capabilities.
* Per-form permissions now automatically synchronize the static WordPress gateway capabilities required by admin menus without granting access to other forms.

= 1.0.21 =
* Added submission Trash, Restore and Permanent Delete with capability + per-form access checks.
* Permanent delete cleans submission values, notes, managed files/attachments, dedicated storage rows and per-submission audit rows.
* Fixed lock-after-submit frontend state so live preview edit/back controls cannot remain after successful submit.
* Added custom CAPTCHA refresh control and public refresh endpoint.
* CAPTCHA automatically refreshes after an invalid/consumed challenge.

= 1.0.20 =
* Optional system preview step before final submission with editable HTML template.
* Per-form lock-after-submit policy and read-only locked rendering.
* Edit-request workflow with applicant request, admin approve/reject and manual lock/unlock.
* Jihadi group registration now uses preview, locks after final submit and shows edit-request control.
* Added one-time lock backfill for previously finalized submissions on forms configured to lock after submit.
* Locked submissions enforce server-side write protection and cannot be modified through direct POST requests.

= 1.0.19 =
* Added locale-aware date presentation across submissions, reports, exports, print/PDF and admin screens.
* Persian WordPress locales now display AFE dates in Jalali format; other locales use WordPress Gregorian date formatting.
* Submission and report date filters now use JalaliDatePicker on Persian sites and Gregorian date inputs on other locales.
* Jalali filter dates are converted to the correct WordPress-timezone/UTC query boundaries before database filtering.
* Added per-form capabilities for view, edit, manage, export, reports and configure access levels.
* Added role-based per-form access management UI.
* Existing role permissions are migrated to per-form capabilities for existing forms, while newly registered forms default to Administrator-only access.
* Per-form access checks are enforced on admin pages, direct submission actions, exports, reports, REST endpoints and frontend editing.

= 1.0.18 =
* Admin submission lists now show form titles instead of form slugs.
* Submission detail tables, print/PDF and Excel exports now show field labels instead of field slugs.
* Select values are presented using their human-readable labels, including geography labels where available.
* Repeater values are rendered as readable nested tables instead of raw JSON.
* Removed raw JSON editing from the submission admin UI.
* Authorized users can edit submission values inline using field-aware controls, including repeater row add/remove actions.
* Redesigned field override UI to group fields by form step and emphasize field titles while keeping slugs as secondary technical metadata.
* Added a shared form-data presenter so admin, print/PDF and export outputs use consistent resolved form metadata.

= 1.0.17 =
* Fixed required upload fields being incorrectly validated twice.
* Generic form validation now skips FileField values and delegates file-required, MIME, size, count and existing-file checks exclusively to FileUploader.
* Added regression coverage to prevent valid uploaded files from being reported as missing.

= 1.0.16 =
* Reworked multipart upload field naming to use independent per-field upload keys.
* Added backward compatibility for legacy nested afe_files[field][] upload payloads.
* Frontend FormData normalization now rewrites legacy cached upload inputs to the new per-field format before submission.
* Improved upload parsing reliability across PHP hosts, security layers and multipart normalizers.

= 1.0.15 =
* Improved upload parser normalization for scalar and array-shaped PHP $_FILES structures.
* Added selected-file manifests so the server can distinguish "no file selected" from PHP-level upload rejection.
* PHP upload errors now return their real cause instead of incorrectly falling back to a required-field message.
* Improved handling for upload size, partial upload, temp directory and disk write errors.

= 1.0.14 =
* Added a full validation summary for final submission.
* Validation errors are grouped by form step and display the field title and exact error message.
* Clicking an error navigates directly to the related step and highlights/focuses the field.
* Steps containing errors are visually marked in the progress navigation.
* Server-side validation errors are integrated into the same validation summary UI.

= 1.0.13 =
* Existing upload files are strictly scoped by submission + field key.
* Existing files can be kept/restored or marked for deletion per upload field.
* Single-file fields now use safe replacement semantics.
* Existing-file UI no longer uses anchors, preventing Elementor/theme lightbox hijacking.
* Required upload validation counts only kept existing files plus newly selected files.
* Existing file ownership is revalidated server-side to prevent cross-field file reuse.
* Successfully saved new files are synchronized back into the existing-file UI to prevent duplicate uploads on repeated saves.

= 1.0.12 =
* Upload UX: dedicated dropzone, selected-file list, sizes, remove actions and drag & drop.
* Prevent document-level theme scripts from mutating file input values and throwing InvalidStateError.
* Client-side file count, size and MIME checks now participate in step validation.

= 1.0.11 =
* Added scoped CSS style isolation with Strong, Default and Disabled modes.
* Strong isolation is the default and is configurable globally and per form.
* Moved AFE design tokens from global :root to .afe-shell.
* Added defensive component rules for theme-conflicted controls and the AFE custom select.

= 1.0.10 =
* Fixed custom select search-result selection when filtered option indexes became stale.
* Custom option items now select native options by value before synchronizing UI and dispatching input/change events.

= 1.0.9 =
* Fixed custom select dropdown layering and stacking-context conflicts.
* Removed isolated stacking contexts that allowed later field triggers to cover an open dropdown panel.

= 1.0.8 =
* Fixed numeric option values being converted to labels in geography selects.
* Geography option IDs are now preserved correctly in rendered select values.
* Geography endpoint accepts legacy parent names for backward compatibility.

= 1.0.7 =
* Fixed province/county/district cascading select updates on the frontend.
* Geography dependency events now use form-level delegated listeners.
* Improved geography request error/loading states and removed cached nonce dependency from the public read-only endpoint.

= 1.0.6 =
* Added AFE custom select component isolated from theme-level Select2 initialization.
* Added searchable select support, keyboard navigation, native/custom modes and per-field search configuration.
* Added compatibility with dependent geography selects, repeaters and conditional logic.

= 1.0.5 =
* Fixed Iran geography import on restricted/slow shared hosts with multiple download mirrors, GitHub API fallback, bulk inserts, transaction rollback, verification and manual JSON upload fallback.
* Added fallback PSR-4 autoload registration even when a Composer autoloader exists.

= 1.0.4 =
* Added AJAX chunked manual JSON importer for province, county and district datasets.
* Uploaded JSON files are processed incrementally and inserted directly into the database with progress, retry and idempotent chunk handling.

= 1.0.3 =
* Improved geography import reliability, source fallbacks and database verification.

= 1.0.2 =
* Added Jalali/Gregorian date modes and database-backed province/county/district cascading fields.

= 1.0.1 =
* Fixed Elementor widget lifecycle compatibility: widgets and dynamic tags no longer use dependency-injected constructors.

= 1.0.0 =
* Initial release.

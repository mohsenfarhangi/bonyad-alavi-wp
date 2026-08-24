=== Alavi Form Engine ===
Contributors: bonyadalavi
Tags: forms, form-engine, submissions, workflow, elementor
Requires at least: 6.4
Requires PHP: 8.3
Stable tag: 1.0.21
License: GPLv3 or later

Code-first extensible form engine for WordPress.

== Description ==

Alavi Form Engine provides code-defined forms with admin overrides, workflows, submissions, repeaters, conditional logic, flexible data sources, secure uploads, reports, optional REST API and Elementor integration.

The package includes the "ثبت نام گروه‌های مردمی و جهادی" form as its first built-in form.

== Installation ==

1. Upload and activate the plugin.
2. Open Forms > Database and verify database health.
3. Optionally update the Iran geography dataset.
4. Use [alavi_form id="jihadi-group-registration"].

== Changelog ==

= 1.0.21 =
* Added submission Trash, Restore and Permanent Delete with capability + per-form access checks.
* Permanent delete cleans submission values, notes, managed files/attachments, dedicated storage rows and per-submission audit rows.
* Fixed lock-after-submit frontend state so live preview edit/back controls cannot remain after successful submit.
* Added custom CAPTCHA refresh control and public refresh endpoint.


= 1.0.20 =
* Optional system preview step before final submission with editable HTML template.
* Per-form lock-after-submit policy and read-only locked rendering.
* Edit-request workflow with applicant request, admin approve/reject and manual lock/unlock.
* Jihadi group registration now uses preview, locks after final submit and shows edit-request control.

= 1.0.14 =
* Existing upload files are strictly scoped by submission + field key.
* Existing files can be kept/restored or marked for deletion per upload field.
* Single-file fields now use safe replacement semantics.
* Existing-file UI no longer uses anchors, preventing Elementor/theme lightbox hijacking.
* Required upload validation counts only kept existing files plus newly selected files.

= 1.0.12 =
* Upload UX: dedicated dropzone, selected-file list, sizes, remove actions and drag & drop.
* Prevent document-level theme scripts from mutating file input values and throwing InvalidStateError.
* Client-side file count, size and MIME checks now participate in step validation.

= 1.0.11 =
* Added scoped CSS style isolation with Strong, Default and Disabled modes.
* Strong isolation is the default and is configurable globally and per form.
* Moved AFE design tokens from global :root to .afe-shell.
* Added defensive component rules for theme-conflicted controls and the AFE custom select.

= 1.0.5 =
* Fixed Iran geography import on restricted/slow shared hosts with multiple download mirrors, GitHub API fallback, bulk inserts, transaction rollback, verification and manual JSON upload fallback.

= 1.0.2 =
* Added Jalali/Gregorian date modes and database-backed province/county/district cascading fields.

= 1.0.1 =
* Fixed Elementor widget lifecycle compatibility: widgets and dynamic tags no longer use dependency-injected constructors.

= 1.0.0 =
* Initial release.

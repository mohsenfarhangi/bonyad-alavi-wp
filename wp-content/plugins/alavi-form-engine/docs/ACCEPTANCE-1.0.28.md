# Acceptance Matrix — Alavi Form Engine 1.0.28

## Current gate status

- Development package: `1.0.28-dev`
- DB checkpoint: `1.0.5-dev.2`
- Stable baseline/tag: `1.0.27`
- Real MeliPayamak account test: **PASS reported by project administrator on 2026-09-04**.
- Standalone regression / lint / asset checks: automated in this repository.
- Browser-driven WordPress + MySQL/MariaDB + Elementor acceptance: **still required before production bump**.

## Automated acceptance covered in this package

| Area | Automated evidence | Expected |
|---|---|---|
| Form definition | `tests/jihadi-form-1-0-23.php` | Jihadi schema, strict mobile/NID/IBAN and repeater shape remain compatible |
| Default applicant SMS template | `tests/jihadi-default-sms-action.php` | `submission.submitted`, `leader_mobile`, `once_per_submission`, disabled until content is configured |
| SMS provider/action | `tests/melipayamak-provider.php`, `tests/sms-action.php` | Legacy/API-token, free/pattern contracts and recipient/token handling |
| Action runtime/retry | `tests/action-manager.php`, `tests/action-manager-retry.php`, `tests/extended-action-registry.php`, `tests/redirect-action.php` | execution policies, runtime outputs, retry, redirect and extended actions |
| Duplicate policy | `tests/duplicate-policy.php`, `tests/duplicate-repository.php` | normalization, owner promotion, trash/restore safety |
| Field ordering | `tests/field-ordering.php` | same-scope reordering and safe append of newly code-defined fields |
| Date modes | `tests/date-field-modes.php` | combined/picker/manual definitions and calendars |
| Validation | `tests/validator-registry.php`, `tests/field-validation-overrides.php`, `tests/validators.php` | registry, overrides, safe regex and Iranian validators |
| Elementor registration | `tests/elementor-registration.php` | Widget/Dynamic Tag instantiate through Elementor-native constructor contract |
| Runtime assets | `tests/runtime-assets.php` | local JalaliDatePicker/frontend assets and no Jalali runtime CDN registration |
| Access/security/upload | existing form-access, file ownership, sensitive action and secret-store tests | capability boundaries, file ownership, protected config and encrypted secrets |

## Required live WordPress/Elementor acceptance before release

Run on a staging clone using the same PHP/WordPress/Elementor major versions intended for production.

1. **Upgrade/install and database health**
   - Install the ZIP over `1.0.27` or current checkpoint.
   - Confirm activation/upgrade has no PHP fatal or admin notice except intentional notices.
   - Open «فرم‌های علوی → دیتابیس» and confirm every required table/column/index is healthy.
   - Confirm existing submissions, files, notes and form overrides are still visible.

2. **Elementor and shortcode rendering**
   - Render `[alavi_form id="jihadi-group-registration"]` on a normal page.
   - Render the same form with the Elementor Form Widget.
   - Confirm no constructor error, duplicate widget registration, missing CSS/JS or console error.
   - Check desktop/mobile widths and hostile-theme style isolation.

3. **Draft / final submit / lock**
   - Save a draft and verify `submission.draft_saved` actions/logs.
   - Resume the same draft and submit it finally.
   - Confirm final submit emits `submission.submitted`, tracking code remains stable and the form locks according to policy.
   - Confirm a locked form cannot be modified through a crafted request.

4. **Edit request lifecycle**
   - Request edit from the locked submission.
   - Approve/reject from admin and verify corresponding canonical events/logs.
   - After approval, edit and submit again; confirm self-duplicate exclusion works.

5. **Duplicate policy**
   - Test a configured multi-field fingerprint against an existing draft and final submission.
   - Exercise block, custom message, secure reference and allow+mark modes.
   - Trash the canonical submission and confirm an active duplicate is promoted safely.
   - Restore and confirm no inconsistent fingerprint ownership is created.

6. **Actions**
   - Verify admin Email and the configured applicant SMS run only once for the same submission where policy is `once_per_submission`.
   - Verify a deliberately failed retryable Action is logged and can be retried from the Submission screen.
   - Verify `on_error=continue` vs `stop` ordering.
   - Verify Redirect occurs only after server-side Actions complete and the first effective Redirect wins.

7. **Jihadi SMS default/template**
   - If no admin override exists, confirm the source SMS template is visible but disabled.
   - Enter the approved free-text or Pattern configuration in Action Builder and enable it.
   - Confirm recipient resolves from `leader_mobile` and repeat submit/retry paths do not send unintended duplicates.
   - The provider/account connectivity itself is already reported PASS by the project administrator.

8. **Field ordering / Repeater ordering**
   - Drag several fields and an HtmlBlock within one Step, save, reload admin and frontend, and confirm the same order.
   - Reorder children inside a Repeater and confirm new rows follow the saved order.
   - Confirm a field cannot be dragged to another Step in this version.

9. **Date modes**
   - Test Jalali `combined`, `picker`, and `manual` fields.
   - Confirm picker-only prevents direct typing UX, manual-only does not open the picker, and backend rejects invalid calendar dates.
   - Confirm bundled JalaliDatePicker assets load locally without CDN requests.

10. **Field constraints / validators**
    - Test digits, Persian letters, English letters, alnum, allowed-extra and forbidden-extra modes.
    - Test min/max/exact length including Persian Unicode input.
    - Test each enabled validator and its custom message.
    - Test an invalid/expensive Custom Regex save and confirm it is rejected/limited server-side.

11. **Admin UX**
    - Open the Jihadi form with all Steps/Repeaters and inspect tabs at common desktop widths.
    - Verify Action Builder, Token Palette, Duplicate tab and Field Ordering do not overflow or become unusable.
    - Check drag handles, select controls and save feedback.

12. **Release gate**
    - Browser console: no uncaught JS errors during the above flows.
    - PHP error log: no new warnings/notices/fatals from AFE under `WP_DEBUG` staging.
    - After all live cases PASS, bump plugin version to `1.0.28`, DB version to `1.0.5`, Stable tag to `1.0.28`, update final changelog/docs, rerun all automated checks, then build the production ZIP.

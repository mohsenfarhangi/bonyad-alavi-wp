# MODULE-CAMP-ADMISSION — Imam Khomeini Camp Admission Form

**Status:** implemented in theme / live acceptance pending  
**Owner:** Ostadsho child theme  
**Slug:** `imam-khomeini-camp-admission`  
**Source:** `wp-content/themes/ostadsho-child/inc/forms/class-ba-camp-admission-form.php`  
**Shortcode:** `[alavi_form id="imam-khomeini-camp-admission"]`

## Architecture

The child theme owns the form Source Definition, project-specific validators, scoped CSS and browser-side stay-duration calculation. Alavi Form Engine owns rendering, Jalali datepicker integration, field validation pipeline, submission persistence, Admin and Action Builder.

Registration:
- `afe_register_forms` registers the form.
- `afe_register_validator_definitions` registers the form's positive-integer and cross-field validators.
- Theme assets are enqueued separately and all selectors are scoped to the form slug.

No default Actions are defined. Admin can add and configure the required Actions through the Alavi Form Engine form settings. No email is sent unless the administrator explicitly adds an email Action.

## Fields

All seven fields are required:

| Key | Label | Type | Contract |
|---|---|---|---|
| `organization_name` | نام سازمان | Text | Required |
| `representative_name` | نام و نام خانوادگی نماینده | Text | Required |
| `representative_mobile` | شماره تماس نماینده | Tel | Required, `mobile_09`, `mobile_ir` mask |
| `guest_count` | تعداد مهمانان | Number | Positive integer, minimum 1 |
| `from_date` | از تاریخ | Date | Jalali calendar |
| `to_date` | تا تاریخ | Date | Jalali calendar, strictly after `from_date` |
| `stay_duration` | مدت اقامت (شب) | Number | Read-only in frontend, automatically calculated and required |

## Stay Duration Contract

- Duration is the difference between the Jalali start and end dates in **nights**. The start date is not counted as an additional night.
- The frontend listens to date input/change events, calculates the date difference and fills `stay_duration`.
- The calculated field is read-only in the browser; it remains part of the submitted AFE data and is saved with the submission.
- Server-side validators independently validate the end date and compare the submitted duration with the actual Jalali date difference. A tampered or stale duration is rejected.
- End date must be later than start date. Same-day or reversed ranges are invalid.
- Persian and Arabic digits are normalized by AFE and the form validator.
- Invalid or incomplete dates clear the calculated value; the required-field check prevents submission until a valid range is supplied.

The reusable AFE validator callback now receives the complete sanitized submission data as its fourth argument. Existing three-argument validator callbacks remain compatible.

## Form Settings

- One step; wizard/progress disabled.
- Draft saving, applicant editing and preview disabled.
- Shared AFE storage.
- Strong style isolation.
- Custom CAPTCHA and rate limit = 5.
- No default Actions or email notification.
- Applicant success state hides the tracking code; the code is still generated and stored for Admin/internal follow-up.
- The success message is: «درخواست شما با موفقیت ثبت شد. از اینکه مجتمع اردوگاهی امام خمینی (ره) را انتخاب کرده‌اید سپاسگزاریم. به‌زودی با شما تماس گرفته خواهد شد.»
- Other AFE forms continue to show tracking codes by default; the behavior is controlled by per-form `show_tracking_code` and `success_message` settings.
- No duplicate policy configured.

## Presentation

- Minimal formal visual language aligned with the child theme's green/gold identity.
- Scoped BEM block: `ba-camp-admission`.
- Responsive 2-column field layout on desktop and 1-column layout on mobile.
- The duration field is visually distinguished as a calculated/read-only value.
- Submit button label: «ثبت درخواست پذیرش».

Assets:
- `assets/css/ba-camp-admission-form.css`
- `assets/js/ba-camp-admission-form.js`

## Regression Tests

- Engine cross-field validator context: `wp-content/plugins/alavi-form-engine/tests/cross-field-validator-context.php`
- Form definition and date/duration validation: `wp-content/themes/ostadsho-child/tests/camp-admission-form-definition.php`
- CI: `.github/workflows/camp-admission-form-checks.yml`

Run with PHP 8.3+ and Node:

```sh
php wp-content/plugins/alavi-form-engine/tests/cross-field-validator-context.php
php wp-content/plugins/alavi-form-engine/tests/validator-registry.php
php wp-content/themes/ostadsho-child/tests/camp-admission-form-definition.php
node --check wp-content/plugins/alavi-form-engine/assets/js/frontend.js
node --check wp-content/themes/ostadsho-child/assets/js/ba-camp-admission-form.js
```

## Live Acceptance Checklist

1. Render shortcode in a WordPress page/Elementor and confirm the title, brand mark, RTL and responsive layout.
2. Verify both date fields open the built-in Jalali datepicker.
3. Select a valid date range and confirm the duration changes immediately (e.g. 15 to 18 = 3 nights).
4. Change either date and confirm duration is recalculated.
5. Try same-day, reversed and invalid Jalali dates; submission must be blocked with a field-level error.
6. Submit with an empty/zero/fractional guest count; the server must reject it.
7. Submit a valid request and verify all seven values, including stay duration, are stored in AFE Admin.
8. Confirm the applicant sees the configured thank-you message and no tracking code, while the tracking code remains available in AFE Admin.
9. Confirm no email/action runs by default; add the desired Actions from the Admin Action Builder.
10. Verify other AFE forms still show tracking codes by default and are unaffected by this form's settings.

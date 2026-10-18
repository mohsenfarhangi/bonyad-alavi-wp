# MODULE-FACILITATOR-FORM — Facilitator Recruitment Form

**Status:** implemented in theme / live acceptance pending  
**Owner:** Ostadsho child theme  
**Slug:** `facilitator-recruitment`  
**Source:** `wp-content/themes/ostadsho-child/inc/forms/class-ba-facilitator-recruitment-form.php`  
**Shortcode:** `[alavi_form id="facilitator-recruitment"]`

## Architecture

The theme owns the form's project-specific Source Definition and its scoped styling/presentation. Alavi Form Engine owns field rendering, client/server validation, Geo Data Source, duplicate detection, file validation and uploads, submission records, Admin capabilities and export. Registration is through `afe_register_forms` from `functions.php` (the same mechanism as the Jihadi and Contact forms). No project form source code is added to the plugin. See [ADR-007](../decisions/ADR-007-project-form-ownership.md).

## Fields

| Field key | Label | Required | Width | Contract |
|---|---|---:|---:|---|
| `full_name` | نام و نام خانوادگی | Yes | 6 | text |
| `national_id` | کد ملی | Yes | 6 | 10 digits, `national_id` rule, `national_id_ir` mask |
| `gender` | جنسیت | Yes | 6 | Male/Female select |
| `mobile` | شماره تماس همراه | Yes | 6 | `mobile_09` validator, `mobile_ir` mask |
| `landline` | شماره تماس ثابت | Yes | 6 | Registered `landline` validator, `landline_ir` mask |
| `education` | تحصیلات | Yes | 6 | 6 options, under-diploma through PhD+ |
| `province` | استان مورد تقاضا | Yes | 6 | AFE Geo province source |
| `county` | شهرستان مورد تقاضا | Yes | 6 | AFE Geo county source, depends on province |
| `residential_address` | آدرس محل سکونت | Yes | 12 | text |
| `description` | توضیحات | No | 12 | textarea |
| `resume` | فایل رزومه | No | 12 | PDF/DOC/DOCX, 5 MB, max 1 file |

No redundant province/county dataset or lookup code is kept in the theme. Persian and Arabic digits are handled by the AFE input normalization/validators.

## Submission and Duplicate Contract

- Single-step form with wizard, save draft, preview, applicant editing, post-submit edit request and progress disabled.
- Custom CAPTCHA on; rate limit = 5; shared AFE storage.
- AFE duplicate policy: enabled; fingerprint fields = `national_id`; behavior = `block`; custom Persian warning; applies on live preflight and on final submit.
- Form-specific actions are intentionally empty. Admin can manage records/exports through AFE as for existing forms.
- Internal AFE tracking and submission ID remain for management, but tracking code and edit link are *not presented to applicants*. AFE's public success span is hidden in the scoped stylesheet and stripped from the success DOM by the theme JS, leaving a single Persian confirmation message.

## Presentation

- Default form template is supplied by `Form::template()` with a project-specific class for a clean card header and no redundant one-step heading.
- `assets/css/ba-facilitator-recruitment-form.css` styles only `form[data-form="facilitator-recruitment"]`. Responsive two-column grid on larger screens and single column at <=640px.
- `assets/js/ba-facilitator-recruitment-form.js` only adapts this form's confirmation markup; it does not submit, validate, query Geo data or store any data.

## QA

Theme regression script: `wp-content/themes/ostadsho-child/tests/facilitator-form-definition.php`.

Run with PHP >=8.3:

```sh
php wp-content/themes/ostadsho-child/tests/facilitator-form-definition.php
php -l wp-content/themes/ostadsho-child/inc/forms/class-ba-facilitator-recruitment-form.php
node --check wp-content/themes/ostadsho-child/assets/js/ba-facilitator-recruitment-form.js
```

Live acceptance pending:
1. Render shortcode in Elementor and verify RTL layout/responsive styling.
2. Submit valid/invalid national ID, mobile, and landline values using Persian and Latin digits. Confirm Persian errors under fields.
3. Select a province; confirm county options and dependency, and reject missing values.
4. Upload allowed/unsupported MIME and over-5-MB files.
5. Submit once successfully; confirm Admin record and exports and **no tracking code/edit link** in public confirmation.
6. Attempt registration again with the same national ID: verify live warning and server block (also under concurrent requests).
7. Test CAPTCHA, rate limiting, non-JS fallback/error messaging and RTL accessibility.

Avoid changing the form slug after submissions have been stored.

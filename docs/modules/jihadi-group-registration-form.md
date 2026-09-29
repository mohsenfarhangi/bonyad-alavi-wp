# MODULE-JIHADI-FORM — Jihadi Group Registration Form

**Status:** active project form  
**Owner:** Bonyad Alavi child theme  
**Source:** `wp-content/themes/ostadsho-child/inc/forms/class-ba-jihadi-group-registration-form.php`  
**Slug:** `jihadi-group-registration`

## Purpose

Source Definition فرم «شناسنامه گروه‌های مردمی و جهادی | طرح جهادگر شهید رسول عالم باقری» را نگهداری می‌کند. Engine عمومی، field types، validation، persistence، Admin، Action Engine و Export را از Alavi Form Engine دریافت می‌کند.

Decision: [ADR-007](../decisions/ADR-007-project-form-ownership.md)

## Registration

`functions.php` فایل فرم را require و callback زیر را روی hook عمومی AFE ثبت می‌کند:

`BA_Jihadi_Group_Registration_Form::register()`

AFE پس از load شدن theme و روی `after_setup_theme` priority 20 boot می‌شود و سپس `afe_register_forms` را dispatch می‌کند.

این ترتیب dependency را یک‌طرفه نگه می‌دارد:
`Theme Form Definition -> AFE public Form API`

و AFE هیچ require/reference مستقیمی به child theme ندارد.

## Compatibility Contract

slug `jihadi-group-registration` تغییر نکرده است. بنابراین:
- row فرم و Admin overrides موجود با همان slug resolve می‌شوند.
- Submissionهای قبلی به همان form slug متصل می‌مانند.
- per-form capabilities و access mapping موجود با همان slug ادامه پیدا می‌کنند.
- shortcode فعلی `[alavi_form id="jihadi-group-registration"]` تغییر نمی‌کند.
- migration دیتابیس برای انتقال ownership لازم نیست.

کلاس قدیمی plugin `BonyadAlavi\FormEngine\Forms\JihadiGroupRegistrationForm` حذف شده و نباید دوباره به Engine برگردد.

## Form Structure

13 مرحله اصلی:
1. اطلاعات هویتی
2. مسئولین
3. مأموریت و هویت
4. ساختار
5. اعضا
6. جامعه هدف
7. سوابق
8. ظرفیت عملیاتی
9. تجهیزات
10. منابع مالی
11. همکاری‌ها
12. رسانه و ارتباطات
13. مستندات

Form definition از DSL عمومی AFE شامل `Form`, `Step`, Text/Number/Textarea/Select/Date/Tel/File/Radio/URL/Repeater/HtmlBlock استفاده می‌کند.

## Important Field Contracts

- `group_mobile`: دقیقاً 11 رقم، prefix `09`، validator `mobile_09`، mask `mobile_ir`.
- `leader_national_id` و `deputy_national_id`: 10 رقم + validator national ID + mask.
- `legal_iban`: optional، 24 digit raw value، mask `iban_digits_ir`، prefix نمایشی/normalize `IR`.
- `coverage_areas`: Repeater با province/county/district؛ county/district optional و dependency row-local.
- legacy coverage keys از `legacy_row_map` به ساختار repeater جدید map می‌شوند.
- file fields limits/MIMEها همان Source Definition فعلی حفظ شده‌اند.

## Workflow / Runtime Settings

Workflow:
- draft
- new
- review
- revision
- approved
- rejected

Key settings:
- wizard on
- save draft on
- progress on
- editing enabled
- preview enabled
- lock after submit
- edit-request button enabled
- custom CAPTCHA
- rate limit 20
- shared storage

## Default Actions

### Admin email
`notify_admin_email_on_submit`
- event: `submission.submitted`
- once per submission
- continue on error
- destination: current WordPress admin email

### Leader SMS template
`notify_leader_sms_on_submit`
- type: SMS
- default disabled
- event: `submission.submitted`
- once per submission
- recipient: field `leader_mobile`
- body/pattern intentionally empty; final production content is configured from Action Builder

## Ownership Boundary

Inside theme:
- business labels/options
- project field composition
- form workflow defaults
- form-specific settings/actions

Inside AFE:
- Form/Field classes
- validation/masks
- renderer and preview
- submission storage/lifecycle
- duplicate engine
- Action/SMS provider engine
- Admin overrides/access
- Export engine

## Regression Test

Theme regression file:
`wp-content/themes/ostadsho-child/tests/jihadi-form-definition.php`

It verifies registration, stable slug, key validation/mask constraints, coverage repeater dependencies and default SMS action contract.

Plugin regression:
`tests/bootstrap-theme-registration-window.php` verifies AFE boot occurs after theme load so external theme registration remains possible.

## Change Safety

- slug را بدون explicit data migration تغییر نده.
- field keyهای persisted را بدون migration/legacy mapping rename نکن.
- project-specific logic را دوباره داخل AFE hard-code نکن.
- Engine API تغییر کرد، این module و regression test theme را هم بررسی کن.

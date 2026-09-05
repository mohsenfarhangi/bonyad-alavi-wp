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
| SMS provider routing / Persian WooCommerce SMS | `tests/sms-provider-registry.php`, `tests/persian-woocommerce-sms-provider.php`, `tests/sms-action-provider-routing.php` | global default, per-Action override, external gateway delegation, known Pattern strategies |
| Action runtime/retry | `tests/action-manager.php`, `tests/action-manager-retry.php`, `tests/extended-action-registry.php`, `tests/redirect-action.php` | execution policies, runtime outputs, retry, redirect and extended actions |
| Duplicate policy | `tests/duplicate-policy.php`, `tests/duplicate-repository.php` | normalization, owner promotion, trash/restore safety |
| Field ordering | `tests/field-ordering.php` | same-scope reordering and safe append of newly code-defined fields |
| Date modes | `tests/date-field-modes.php`, `tests/date-renderer-modes.php` | combined/picker/manual definitions plus actual Jalali/Gregorian renderer output |
| Validation | `tests/validator-registry.php`, `tests/field-validation-overrides.php`, `tests/validators.php` | registry, overrides, safe regex and Iranian validators |
| Input masks | `tests/input-mask-registry.php`, `tests/input-mask-renderer.php`, `tests/input-mask-submission-normalization.php` | presets/custom syntax, masked renderer constraints and server-side clean persistence |
| Elementor registration | `tests/elementor-registration.php` | Widget/Dynamic Tag instantiate through Elementor-native constructor contract |
| Runtime assets | `tests/runtime-assets.php` | local JalaliDatePicker/frontend assets and no Jalali runtime CDN registration |
| Action metadata / URL templates | `tests/action-execution-policy-metadata.php`, `tests/action-config-sanitizer.php` | supportsExecutionPolicy enforcement and token-preserving URL sanitization |
| Access/security/upload | form-access/file ownership/sensitive config tests + `tests/user-action-security.php`, `tests/user-meta-preflight.php` | capability boundaries, privileged-user guards, protected meta preflight, file ownership and encrypted secrets |

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

8. **Persian WooCommerce SMS Integration**
   - Persian WooCommerce SMS را فعال کنید و یک Gateway واقعی در تنظیمات همان افزونه انتخاب/تنظیم کنید.
   - در تنظیمات AFE، Provider پیش‌فرض را روی Persian WooCommerce SMS بگذارید و تأیید کنید نام Gateway فعال و modeهای قابل استفاده نمایش داده می‌شوند.
   - یک SMS Action با Provider=`پیش‌فرض سراسری` و یک Action دیگر با Override=`Persian WooCommerce SMS` اجرا کنید؛ هر دو باید از همان Credential/Gateway خارجی استفاده کنند و AFE نباید درخواست Credential جدید بدهد.
   - ارسال آزاد را تست کنید. اگر Gateway فعال Pattern-capable شناخته می‌شود، Pattern/Lookup را نیز با Pattern ID و پارامترهای واقعی تست کنید.
   - Gateway را داخل Persian WooCommerce SMS تغییر دهید، سپس بدون تغییر Credential در AFE دوباره Action را اجرا کنید و استفاده از Gateway جدید را تأیید کنید.
   - افزونه Persian WooCommerce SMS را موقتاً غیرفعال کنید؛ Action قبلی باید config خود را حفظ کند، Submission موفق بماند و Action Log خطای Provider unavailable ثبت کند.
   - Provider پیش‌فرض را به ملی پیامک داخلی برگردانید و تأیید کنید Action دارای Override خارجی همچنان از Provider خارجی استفاده می‌کند.

9. **Field ordering / Repeater ordering**
   - در تب «فیلدها و چیدمان» تأیید کنید فقط بخش چیدمان دیده می‌شود و Editorهای Override در محتوای اصلی تب وجود ندارند.
   - روی یک Field کلیک کنید و تأیید کنید تنظیمات Override همان Field داخل `afe-admin-side` باز می‌شود؛ سپس یک Child Field داخل Repeater را نیز بررسی کنید.
   - روی HtmlBlock کلیک کنید/بررسی کنید که فقط Drag & Drop دارد و پنل Override فیلدی برای آن باز نمی‌شود.
   - Drag several fields and an HtmlBlock within one Step, save, reload admin and frontend, and confirm the same order.
   - Reorder children inside a Repeater and confirm new rows follow the saved order.
   - Confirm a field cannot be dragged to another Step in this version.

10. **Date modes**
   - Test Jalali `combined`, `picker`, and `manual` fields.
   - Confirm picker-only prevents direct typing UX, manual-only does not open the picker, and backend rejects invalid calendar dates.
   - Confirm bundled JalaliDatePicker assets load locally without CDN requests.

11. **Input Mask**
    - روی موبایل preset «شماره موبایل ایران» را بررسی کنید؛ مقدار باید هنگام تایپ مانند `0912 345 6789` نمایش داده شود.
    - Submit/Edit را انجام دهید و تأیید کنید Token/SMS/ذخیره‌سازی مقدار تمیز `09123456789` را دریافت می‌کنند.
    - همان Submission را از wp-admin ویرایش کنید؛ Mask باید در فیلد مدیریت نیز دیده شود و ذخیره مجدد مقدار تمیز را حفظ کند.
    - کد ملی، کارت بانکی و شبای ۲۴ رقمی را با presetهای خود بررسی کنید.
    - یک Mask سفارشی مثل `AA-9999` بسازید و نمایش/ویرایش/ذخیره مجدد را تست کنید.
    - Override را روی «بدون Mask» بگذارید و تأیید کنید preset کدنویسی‌شده همان فیلد غیرفعال می‌شود.
    - تاریخ را جداگانه بررسی کنید؛ DateField باید همچنان از mask خودکار calendar استفاده کند.

12. **Field constraints / validators**
    - Test digits, Persian letters, English letters, alnum, allowed-extra and forbidden-extra modes.
    - Test min/max/exact length including Persian Unicode input.
    - Test each enabled validator and its custom message.
    - Test an invalid/expensive Custom Regex save and confirm it is rejected/limited server-side.

13. **Admin UX**
    - Open the Jihadi form with all Steps/Repeaters and inspect tabs at common desktop widths.
    - Verify Action Builder, Token Palette, Duplicate tab and Field Ordering do not overflow or become unusable.
    - Check drag handles, select controls and save feedback.

14. **Release gate**
    - Browser console: no uncaught JS errors during the above flows.
    - PHP error log: no new warnings/notices/fatals from AFE under `WP_DEBUG` staging.
    - After all live cases PASS, bump plugin version to `1.0.28`, DB version to `1.0.5`, Stable tag to `1.0.28`, update final changelog/docs, rerun all automated checks, then build the production ZIP.


## جهت ورودی‌های شماره‌ای

- [ ] موبایل، تلفن، کد ملی، شبا و سایر ورودی‌های عددی از سمت چپ تایپ شوند و مقدار داخل کنترل LTR/چپ‌چین باشد؛ Label و چیدمان کلی فرم RTL بماند.
- [ ] همین رفتار در ویرایش Submission و Repeaterهای مدیریتی نیز بررسی شود.

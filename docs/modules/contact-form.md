# MODULE-CONTACT-FORM — Contact Form

**Status:** implemented / live acceptance pending  
**Owner:** Bonyad Alavi child theme  
**Source:** `wp-content/themes/ostadsho-child/inc/forms/class-ba-contact-form.php`  
**Slug:** `contact-us`

## Purpose

فرم عمومی «تماس با ما» برای دریافت پیام کاربران سایت بنیاد علوی. Source Definition در child theme نگهداری می‌شود و Engine، validation، Geo Data Source، storage، Admin و Actionها از Alavi Form Engine تأمین می‌شوند.

Decision: [ADR-007](../decisions/ADR-007-project-form-ownership.md)

## Registration

`functions.php` فایل فرم را require و callback زیر را روی hook عمومی AFE ثبت می‌کند:

`BA_Contact_Form::register()`

Shortcode:

`[alavi_form id="contact-us"]`

## Fields

فرم یک Step با key برابر `message` دارد:

| Key | Type | Label | Required | Width |
|---|---|---|---|---|
| `full_name` | text | نام و نام خانوادگی | yes | 6 |
| `mobile` | tel | شماره همراه | yes | 6 |
| `email` | email | ایمیل | no | 6 |
| `province` | select | استان | yes | 6 |
| `message_category` | select | دسته‌بندی پیام | yes | 12 |
| `subject` | text | موضوع پیام | yes | 12 |
| `message` | textarea | متن پیام | yes | 12 |

### Mobile contract

- rule: `mobile_09`
- mask: `mobile_ir`
- exactly 11 digits
- fixed prefix `09`
- numeric input mode

### Province contract

فهرست استان‌ها داخل theme hard-code نمی‌شود:

```php
->source([
    'type'  => 'geo',
    'level' => 'province',
])
```

بنابراین منبع استان همان Geo Data Source عمومی AFE است.

### Message category contract

- فیلد `message_category` درست پیش از `subject` قرار دارد؛ اجباری و تمام‌عرض (`width=12`) است.
- تعریف فرم عمداً `options([])` دارد: هیچ گزینه اولیه‌ای در PHP تنظیم نشده و placeholder «انتخاب دسته‌بندی» است. **تا زمانی که مدیر حداقل یک گزینه تعیین نکند، فرم قابل تکمیل نیست.**
- مدیر در **Alavi Form Engine ← فرم‌ها ← تماس با ما ← فیلدها و چیدمان** روی «دسته‌بندی پیام» کلیک می‌کند و در «گزینه‌ها»، هر مورد را در یک خط به‌شکل `value|label` وارد و ذخیره می‌کند؛ مثال: `پرسش|پرسش`.
- فیلد Select از قابلیت Override موجود افزونه استفاده می‌کند؛ ویرایش، افزودن، حذف و مرتب‌سازی گزینه‌ها در پنل انجام می‌شود.
- ایمیل پیش‌فرض مدیر توکن `{{field:message_category}}` را پیش از موضوع دارد و مقدار `value` را نمایش می‌دهد. برای خوانایی ایمیل، مقدارهای قابل‌فهم انتخاب شوند.
- در صورت وجود **Override ذخیره‌شده برای Action ایمیل**، متن PHP به‌جای آن استفاده نمی‌شود؛ مدیر باید متن همان Action را به‌روزرسانی کند یا Override را غیرفعال کند.
- **Override ذخیره‌شده ترتیب فیلدها** ممکن است باعث شود فیلد جدید در انتهای ترتیب ثبت‌شده قرار گیرد؛ در این صورت مدیر باید آن را پیش از موضوع جابه‌جا کند یا ترتیب را بازنشانی کند.

## Runtime Settings

این فرم عمداً ساده و تک‌مرحله‌ای است:

- wizard: off
- save draft: off
- progress: off
- editing: off
- preview: off
- edit request: off
- CAPTCHA: custom
- rate limit: 5
- storage: shared

Workflow سفارشی تعریف نشده و workflow پیش‌فرض Engine استفاده می‌شود.

## Default Action

`notify_admin_email_on_submit`

- type: email
- event: `submission.submitted`
- execution policy: `once_per_submission`
- on error: continue
- recipient: `get_option('admin_email')`
- subject contains `{{field:subject}}`
- body includes name/mobile/email/message_category/subject/message + `{{tracking_code}}`

ارسال ایمیل کاربر یا Reply-To پویا به‌صورت پیش‌فرض تعریف نشده است.

## Page widgets

بخش‌های غیرفرم صفحه «ارتباط با ما» (اطلاعات تماس، جدول داخلی‌ها، مراکز وابسته، نقشه) ویجت دارند: [MODULE-CONTACT-PAGE](contact-page-widgets.md). فرم در آن تغییری نکرده است.

## Ownership Boundary

Inside theme:
- field composition
- labels/placeholders
- form-specific settings
- default admin notification

Inside AFE:
- field types
- `mobile_09` validation and `mobile_ir` mask
- Geo Data Source
- rendering/submission storage
- CAPTCHA/rate limit
- Action token resolution/email delivery
- Admin/Export

## Regression Test

`wp-content/themes/ostadsho-child/tests/contact-form-definition.php`

Guards:
- registration + stable slug
- single-step structure
- required fields
- mobile validator/mask
- Geo province source
- simple form settings
- shared storage
- Admin Email Action
- required message_category select, no initial options and placed before subject
- admin options override support
- email includes message_category token
- field/tracking tokens
- theme bootstrap registration

CI: `.github/workflows/contact-form-checks.yml` runs PHP lint and contact form regression tests on relevant pull requests.

## Live Acceptance Checklist

1. فرم با shortcode روی frontend نمایش داده شود.
2. فیلد استان از Geo Data Source مقدار بگیرد.
3. mobile mask/validator در frontend و submit نهایی درست باشد.
4. دسته‌بندی قبل از موضوع باشد، بدون گزینه اولیه ارسال معتبر ممکن نباشد و پس از ثبت Override گزینه‌ها قابل انتخاب شوند؛ ایمیل optional و سایر required fields بررسی شوند.
5. CAPTCHA و rate limit در محیط واقعی تست شوند.
6. Submission در Admin با slug `contact-us` دیده شود.
7. ایمیل مدیر دسته‌بندی انتخاب‌شده را همراه موضوع و متن پیام دریافت کند (در صورت وجود Action Override، آن تنظیمات جدا بررسی شود).

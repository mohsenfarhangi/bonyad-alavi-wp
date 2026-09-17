# PROJECT_STATE — Alavi Form Engine

> **آخرین به‌روزرسانی:** 2026-09-15  
> **هدف این فایل:** این سند handoff اصلی پروژه برای ادامه توسعه در گفت‌وگوهای بعدی است. اگر یک AI/Developer جدید وارد پروژه شد، قبل از هر تغییر باید این فایل را کامل بخواند و سپس `handoff.md`، `BUILD-REPORT.md` و کد checkpoint فعلی را بررسی کند.

---

## 1) شناسنامه پروژه

- **نام افزونه:** Alavi Form Engine
- **Namespace:** `BonyadAlavi\FormEngine`
- **نوع پروژه:** WordPress Plugin، Code-first Form Engine
- **حداقل PHP:** `8.3`
- **نسخه پایدار فعلی:** `1.0.27`
- **نسخه توسعه فعلی:** `1.0.28-dev`
- **DB checkpoint فعلی:** `1.0.5-dev.2`
- **Stable tag فعلی:** `1.0.27`
- **Production-ready:** هنوز خیر؛ نسخه 1.0.28 در مرحله pre-acceptance است و تا پایان تست عملی مدیر پروژه نباید stable tag / production version بالا برود.
- **آخرین checkpoint مبنا:** `alavi-form-engine-1.0.28-dev-checkpoint-11.3.zip`
- **SHA-256 آخرین checkpoint:** `ad1c87b92985f23266a252e5457ba647b5bfe44675accf6f6d46b8599b179ca1`
- **تست واقعی SMS ملی‌پیامک:** PASS توسط مدیر پروژه گزارش شده است.

### وابستگی‌های Composer فعلی

```json
{
  "php": ">=8.3",
  "phpoffice/phpspreadsheet": "5.9.0",
  "maennchen/zipstream-php": "3.2.2",
  "tecnickcom/tc-lib-pdf": "8.73.6"
}
```

> **مهم:** پکیج‌های Composer در ZIP توسعه‌ای تولیدشده توسط محیط ChatGPT الزاماً داخل `vendor/` باندل نشده‌اند. روی محیط واقعی باید `composer install/update` اجرا شود. برای PDF مرحله ساخت font metadata نیز لازم است.

---

## 2) دستورالعمل فوری برای AI/Developer بعدی

قبل از هر patch:

1. از **checkpoint 11.3** به‌عنوان baseline استفاده کن؛ اگر فایل در دسترس نیست از کاربر بخواه آخرین ZIP را آپلود کند.
2. فایل‌های زیر را بخوان:
   - `PROJECT_STATE.md`
   - `handoff.md`
   - `BUILD-REPORT.md`
   - `composer.json`
   - در صورت ارتباط با release/export: `THIRD-PARTY-NOTICES.md`
3. هیچ قابلیت قبلی را با بازنویسی عجولانه خراب نکن؛ تغییرات باید regression-safe باشند.
4. اگر patch چندفایلی است، بعد از تغییر حتماً full QA اجرا شود.
5. بعد از **هر patch** موارد زیر الزامی است:
   - آپدیت `handoff.md`
   - آپدیت مستندات مرتبط (`README.md`, `readme.txt`, `BUILD-REPORT.md`, Acceptance در صورت نیاز)
   - اجرای regression / lint / JS syntax / package checks
   - ساخت checkpoint ZIP جدید + فایل SHA256
   - اعلام **لیست دقیق فایل‌های تغییرکرده** با فرمت `M / A / D`
   - ارائه متن پیشنهادی **Git commit به فارسی**
6. تست عملی نهایی WordPress/MySQL/Elementor توسط مدیر پروژه بعد از تکمیل همه قابلیت‌ها انجام می‌شود؛ توسعه را منتظر acceptance دستی متوقف نکن مگر کاربر صریحاً بخواهد.
7. تا وقتی مدیر پروژه acceptance نهایی را PASS اعلام نکرده، نسخه production را به `1.0.28` و DB را به `1.0.5` و Stable tag را به `1.0.28` تغییر نده.

---

## 3) قواعد معماری که نباید شکسته شوند

### Form / schema

- فرم تعریف‌شده در کد **Source of Truth** است.
- Admin فقط Override ایجاد می‌کند؛ UI نباید PHP خام ذخیره یا اجرا کند.
- `resolvedForm` باید Source Definition + Admin Override را با backward compatibility بسازد.
- فرم جهادی (`jihadi-group-registration`) یکی از فرم‌های اصلی و دارای defaults اختصاصی است.

### DI / Elementor

- constructor DI برای Elementor Widget دوباره وارد نشود؛ قبلاً منبع خطا بوده است.

### File ownership

- مالکیت فایل با tuple زیر enforce می‌شود:
  - `submission_id`
  - `field_key`
- FileField باید فقط از مسیر `FileUploader` و ownership checks عبور کند.
- generic validator نباید دوباره validation فایل را duplicate کند.

### Lock / submission lifecycle

- Lock باید server-side enforce شود.
- Trashed submission از active scopes خارج است.
- Permanent delete فقط بعد از trash مجاز است و relations/audit مربوطه purge می‌شوند.

### Access control

- capabilityهای global + per-form باید حفظ شوند.
- هر فرم capabilityهای مجزای خودش را دارد.
- Legacy capability migration انجام شده و فرم‌های جدید به‌صورت پیش‌فرض Administrator-only هستند تا explicit grant شوند.

### Token / template security

- Token UI whitelist دارد.
- `{{field:*}}` فقط باید برای فیلدهای واقعی همان فرم resolve شود.
- raw PHP / arbitrary shortcode / remote CSS/JS در templateها مجاز نیست.
- Custom Regex محدود و safe است؛ backend مرجع نهایی validation است.

### Actions

- Action failure نباید submission موفق را rollback کند، مگر آن بخشی که transaction-specific است.
- execution policy و `action_key` باید حفظ شوند.
- Retry باید event/condition/enabled state را دوباره بررسی کند.

### Runtime assets

- JS/CSS runtime از CDN لود نشود.
- assetهای browser افزونه باید local باشند.

---

## 4) وضعیت کلی قابلیت‌های تکمیل‌شده

### 4.1 Form Engine / Layout / Fields

- Step-based form engine پیاده شده است.
- ترتیب Field و HtmlBlock فقط داخل Step خودش قابل جابه‌جایی است.
- Repeater child فقط داخل همان Repeater قابل مرتب‌سازی است.
- cross-step drag ممنوع است.
- فیلدهای جدیدی که بعداً در Source Definition اضافه شوند و در order ذخیره‌شده نباشند، به‌صورت امن append می‌شوند.
- تب «فیلدها و چیدمان» فقط layout/ordering را نمایش می‌دهد.
- کلیک روی Field یا Repeater child، editor override را در sidebar باز می‌کند.
- HtmlBlock فقط draggable است و field override ندارد.
- انتخاب Field در همان browser session حفظ می‌شود.
- step templates به تب Templates منتقل شده‌اند.

### 4.2 DateField

- modeها:
  - combined
  - picker
  - manual
- default = combined
- Jalali storage/display: `YYYY/MM/DD`
- Gregorian: `YYYY-MM-DD`
- فرم جهادی همچنان تاریخ‌های جلالی دارد.
- تاریخ‌ها در Admin/Reports/Export براساس locale و site timezone مدیریت می‌شوند.

### 4.3 Validation

Registry-based validator architecture پیاده شده است.

Validatorهای Core:

- National ID
- Iranian mobile
- IBAN
- Email
- URL
- 10-digit postal code
- 16-digit bank card checksum
- landline
- date by calendar
- custom regex

Custom Regex:

- فقط capability مناسب (`afe_manage_settings`)
- طول/flags محدود
- compile test
- custom error message الزامی
- frontend برای UX، PHP authority نهایی
- PCRE match / recursion limits

Backward compatibility ruleهای قدیمی مثل `national_id`, `mobile_09`, `iban_digits` حفظ شده است.

### 4.4 Character rules

حالت‌های فیلد متنی:

- normal
- digits
- Persian letters
- English letters
- letters+digits
- extra allowed / forbidden chars
- min/max/exact length
- Unicode-safe حتی بدون mbstring در بخش‌های قدیمی طراحی‌شده

### 4.5 Input Mask

Generic registry-based mask system پیاده شده:

- Iran mobile
- landline
- national ID
- postal code
- bank card
- 24-digit IBAN digits
- custom mask

Syntax:

- `9` = digit
- `A` = letter
- `*` = alphanumeric
- `\` = escape

Mask فقط UX است؛ قبل از Validation/Duplicate/Token/Action/SMS/Storage، مقدار سمت PHP normalize می‌شود.

DateField از generic mask جداست.

فرم جهادی defaults مناسب موبایل، تلفن، کدملی و شبا دارد.

### 4.6 Numeric / identifier direction

- ورودی‌های `tel`, `number`, `date` و inputmodeهای `numeric|decimal|tel` LTR هستند.
- Renderer marker زیر را اضافه می‌کند:
  - `dir="ltr"`
  - `data-afe-ltr="1"`
- CSS قوی‌تر از Strong Isolation برای این فیلدها وجود دارد:
  - `direction:ltr !important`
  - `text-align:left !important`
- `unicode-bidi: plaintext` برای رفتار بهتر caret/عدد اضافه شده است.
- rule عمومی `.afe-form .afe-control` دیگر `text-align:right` اجباری ندارد.
- Preview زنده و server-rendered نیز تمام عدد/شناسه‌های عددی را LTR و چپ‌چین نمایش می‌دهد.
- سلول‌های عددی Repeater و شماره ردیف‌ها نیز LTR هستند.

### 4.7 FileField / Upload UX

- file input native برای FormData/Server باقی مانده ولی UI dropzone اختصاصی دارد.
- eventهای file input داخل AFE isolate شده‌اند تا theme scriptها نتوانند مقدار file را دستکاری کنند.
- نمایش نام/نوع/حجم/مجموع حجم و حذف per-file وجود دارد.
- keep/remove manifest برای existing files server-side validate می‌شود.
- replace single-file semantics + rollback protection وجود دارد.
- Preview فایل‌ها:
  - فایل ذخیره‌شده لینک قابل کلیک دارد.
  - `target="_blank"`
  - `rel="noopener noreferrer"`
  - URL از attachment متعلق به همان submission یا URL امن file record resolve می‌شود.
  - فایل تازه انتخاب‌شده قبل از submit با `URL.createObjectURL()` قابل مشاهده است.
  - schemeهای مجاز Preview: `http`, `https`, `blob`.

### 4.8 Custom Select

- AFE custom select پیش‌فرض است.
- native select همچنان source of truth submission/validation است.
- theme Select2 داخل `.afe-form` suppress/cleanup می‌شود.
- keyboard navigation، search، multiple، dependent Ajax، repeater، disabled option، responsive opening پشتیبانی می‌شود.
- Admin override برای custom/native/search behavior وجود دارد.

### 4.9 Style Isolation

- design tokenها از `:root` به `.afe-shell` منتقل شده‌اند.
- modeها:
  - strong
  - default
  - disabled
- default = strong
- global + per-form override وجود دارد.
- strong mode reset scoped دارد و فقط rules لازم `!important` هستند.

### 4.10 Preview / Lock

- فرم جهادی 13 data step + preview اختیاری دارد.
- `preview_enabled`, `lock_after_submit`, edit request flow پیاده شده است.
- lock after submit به persisted locked preview redirect می‌کند.
- public renderer lock را با admin capability bypass نمی‌کند.
- preview فیلدهای عددی و فایل‌ها اصلاح شده است.

### 4.11 Trash / Restore / Purge / CAPTCHA

- soft-delete + restore + permanent purge وجود دارد.
- permanent purge فقط برای row trashed انجام می‌شود.
- Custom CAPTCHA refresh Ajax دارد و پس از captcha validation error auto-refresh می‌شود.

---

## 5) Duplicate Detection — وضعیت نهایی فعلی

Duplicate system شامل:

- multi-field fingerprint
- normalize Persian/Arabic digits
- lowercase/whitespace normalization
- drafts در duplicate detection حساب می‌شوند
- current submission در edit از خودش exclude می‌شود
- trash excluded است
- behaviorها:
  - block
  - reference
  - custom message
  - allow + mark duplicate

DB metadata:

- `is_duplicate`
- `duplicate_of_submission_id`

### Canonical ownership / promotion

اگر canonical submission edit/trash شود:

- duplicate فعال بعدی می‌تواند promote شود.
- dependents retarget می‌شوند.
- عملیات transaction-safe است و DB failure rollback می‌شود.

### Live Duplicate Check قبل از Submit

این قابلیت از checkpoint 10.5 اضافه و در 10.6 اصلاح شد.

رفتار نهایی مورد انتظار:

- فقط فیلدهای انتخاب‌شده برای fingerprint مانیتور می‌شوند.
- تا وقتی **همه فیلدهای انتخاب‌شده کامل** نشده‌اند هیچ query انجام نمی‌شود.
- mask/date نیمه‌کاره کامل محسوب نمی‌شود.
- debounce حدود `450ms`.
- request قبلی با تایپ جدید cancel می‌شود.
- file bytes در live request ارسال نمی‌شوند.
- Repeater path مثل `members.national_id` پشتیبانی می‌شود.
- add/remove row باعث recheck می‌شود.
- current edit submission با authorization معتبر exclude می‌شود.
- Final submit guard همچنان authority نهایی و race-safe است.

### UX نهایی Duplicate

> **خیلی مهم:** در نسخه نهایی فعلی **فقط اگر Duplicate پیدا شود پیام نمایش داده می‌شود**.

- اگر تکراری نباشد: هیچ پیام سبز/موفقیتی نمایش داده نشود.
- اگر duplicate باشد: پیام policy/custom message نشان داده شود.
- در `allow`: warning نمایش داده می‌شود ولی ثبت مجاز است.
- در `reference`: لینک ثبت قبلی فقط اگر کاربر access معتبر داشته باشد برگردد.
- خطای موقت live-check نباید پیام مزاحم success/error عمومی به کاربر نشان دهد.

### Index rebuild / self-heal

مشکل ثبت‌های قدیمی حل شده است:

- هنگام Save تنظیمات Duplicate، fingerprint submissionهای فعال همان فرم rebuild می‌شود.
- signature تنظیمات fingerprint ذخیره/مقایسه می‌شود.
- اگر signature وجود نداشته باشد یا تنظیمات عوض شده باشند، قبل از اولین live check/submit ایندکس همان فرم self-heal می‌شود.
- بنابراین submissionهای قبل از فعال‌کردن duplicate policy نیز باید شناسایی شوند.

---

## 6) Event / Action / Token / Execution Engine

Registry-based architecture تکمیل شده:

- `ActionDefinition / ActionRegistry`
- `EventRegistry`
- `TokenRegistry / TokenResolver`
- `ActionExecutionRepository`
- `ActionRuntime / ActionRunResult`

Execution policies:

- `always`
- `once_per_submission`
- `first_in_cycle`

سایر قابلیت‌ها:

- stable `action_key`
- conditional logic operators
- continue/stop on action error
- atomic once guard
- action logs
- retry failed action
- Retry باید enabled/event/condition را دوباره چک کند.
- outputs اکشن‌ها می‌توانند بدون mutate کردن form data به actionهای بعدی پاس داده شوند.

### Actionهای پیاده‌شده

- SMS
- Email
- Webhook
- Redirect
- Create User
- Login User
- Update User
- Assign Role
- Update User Meta
- Change Submission Status
- Internal Note
- Generate PDF
- Email PDF
- Create / Update / Upsert Post/CPT
- custom developer registry action

### Security guards

- external redirect نیاز به permission صریح دارد.
- direct post publishing نیاز به permission صریح دارد.
- Administrator / user دارای `manage_options` در User Actionها محافظت می‌شود مگر opt-in مجاز.
- protected user meta block می‌شود:
  - `*_capabilities`
  - `*_user_level`
  - `session_tokens`
  - `_application_passwords`
- user-meta actionها قبل از write prevalidate می‌شوند تا partial side effects ایجاد نشود.
- tokenized URL هنگام admin sanitize token را حفظ می‌کند ولی URL resolve‌شده runtime validate می‌شود.
- `supportsExecutionPolicy=false` در UI و sanitizer enforce می‌شود.

---

## 7) SMS — وضعیت نهایی

### Provider داخلی MeliPayamak

پشتیبانی:

- Legacy username/password
- Console/API token
- free text
- pattern/shared
- SecretStore encrypted credentials

recipient sourceها:

- form field
- manual
- WP user/admin
- token

تست واقعی ملی‌پیامک توسط مدیر پروژه PASS گزارش شده است.

### فرم جهادی

Default SMS Action برای `jihadi-group-registration`:

- event: `submission.submitted`
- recipient field: `leader_mobile`
- execution: `once_per_submission`
- action_key: `notify_leader_sms_on_submit`
- `on_error=continue`
- **به‌صورت پیش‌فرض disabled** است.
- متن/Pattern hard-coded نیست و مدیر از Action Builder تنظیم می‌کند.

### Persian WooCommerce SMS integration

`SmsProviderRegistry` و `PersianWooCommerceSmsProvider` پیاده شده‌اند.

اصول:

- credentials افزونه Persian WooCommerce SMS داخل AFE کپی نمی‌شود.
- از `PWSMS()->send_sms()` و gateway/settings فعال همان افزونه استفاده می‌شود.
- اگر gateway خارجی تغییر کند AFE در اجرای بعدی همان را دنبال می‌کند.
- free-text supported.
- Pattern فقط برای strategy شناخته‌شده یا extension ثبت‌شده.
- built-in pattern strategy برای:
  - MeliPayamakPattern / service/combined
  - KaveNegar Lookup
- gateway ناشناخته در Pattern fail-closed است.
- اگر افزونه خارجی غیرفعال شود config حفظ می‌شود ولی runtime خطای واضح می‌دهد؛ silent fallback ندارد.

Hooks:

- `afe_register_sms_providers`
- `afe_pwsms_supported_modes`
- `afe_pwsms_pattern_strategy`
- `afe_pwsms_pattern_payload`
- `afe_pwsms_send_data`

### Master Switch SMS fix

مشکل «با وجود فعال بودن UI، runtime سرویس را غیرفعال تشخیص می‌دهد» اصلاح شده:

- `SmsSettings` ساختار جدید و legacy را normalize می‌کند:
  - `afe_settings['sms']['enabled']`
  - `afe_settings['sms_enabled']`
- Save هر دو کلید را sync می‌کند.
- Registry هنگام اجرای هر SMS action وضعیت فعلی settings را دوباره resolve می‌کند و فقط snapshot زمان boot نیست.

---

## 8) Export PDF / Excel — وضعیت فعلی

این بخش در checkpointهای 11 تا 11.3 توسعه داده شده است.

### تصمیم معماری تأییدشده توسط مدیر پروژه

1. خروجی لیست Submissionها باید قالب اختصاصی هر فرم را رعایت کند.
2. اگر چند فرم export شوند، هر فرم Sheet جدا داشته باشد.
3. Excel template از UI ساختاری ویرایش شود، نه upload فایل XLSX template.
4. PDF template با HTML امن + CSS + Token Palette قابل ویرایش باشد.
5. Source Definition هر فرم export profile پیش‌فرض دارد و Admin فقط Override ایجاد می‌کند.

### Excel

پکیج:

- `phpoffice/phpspreadsheet: 5.9.0`
- خروجی واقعی `.xlsx`

قابلیت‌ها:

- per-form export profile
- Sheet name
- RTL
- Freeze Header
- AutoFilter
- Header style
- visible/hidden columns
- custom column title
- custom width
- column ordering
- identifier fields به‌صورت Text برای حفظ صفر اول
- file URLs به‌صورت hyperlink
- multi-form export = Sheet جدا برای هر فرم

### PDF

پکیج:

- `tecnickcom/tc-lib-pdf: 8.73.6`

قابلیت‌ها:

- Unicode / RTL
- HTML/CSS template
- Header / Body / Footer
- page format: A4/A5/Letter
- portrait/landscape
- Token Palette
- safe sanitizer؛ remote CSS/resources/raw PHP مجاز نیست.
- file linkها قابل کلیک.

### Export Profile

کلاس‌های اصلی:

- `src/Export/ExportProfile.php`
- `src/Export/ExportConfigSanitizer.php`
- `src/Export/PdfTemplateRenderer.php`
- `src/Export/PdfExporter.php`
- `src/Export/ExcelExporter.php`
- `src/Export/ExportPackageStatus.php`
- `src/Export/BinaryDownload.php`
- `src/Export/ExportAutoloadScope.php`

### فرم جهادی — قالب خروجی

فرم `jihadi-group-registration` export profile اختصاصی دارد.

PDF با عنوان/هویت مربوط به:

- «شناسنامه گروه‌های مردمی و جهادی»
- «طرح جهادگر شهید رسول عالم باقری»

و اطلاعات به‌شکل بخش‌بندی‌شده فرم نمایش داده می‌شود.

Excel نیز Sheet اختصاصی `گروه‌های جهادی` و ستون‌های پیش‌فرض مناسب فرم دارد.

Admin می‌تواند profile را Override کند و به Source Definition برگرداند.

---

## 9) PDF Font Build — مشکل و Fix فعلی

### مشکل اولیه

پس از `composer install` پیام زیر دیده شد:

> فونت یونیکد PDF آماده نیست...

در script قبلی، failure ساخت font با `|| true` نادیده گرفته می‌شد؛ این اصلاح شد.

### مسیر صحیح DejaVu

ساختار واقعی `tc-lib-pdf-font` فونت‌ها را داخل family folder می‌نویسد.

مسیر صحیح فعلی:

```text
vendor/tecnickcom/tc-lib-pdf-font/target/fonts/dejavu/dejavusans.json
```

نه:

```text
vendor/tecnickcom/tc-lib-pdf-font/target/fonts/dejavusans.json
```

checkpoint 11.2:

- detector runtime مسیر `target/fonts/dejavu/` را اصلی می‌داند.
- مسیر مستقیم قدیمی فقط fallback است.
- `K_PATH_FONTS` روی پوشه واقعی family تنظیم می‌شود.
- `tools/build-pdf-fonts.sh` مسیر درست را verify می‌کند.
- اگر artifact پیدا نشود فایل‌های تولیدشده را برای debugging فهرست می‌کند.

### دستورات محیط واقعی

پس از Composer:

```bash
bash tools/build-pdf-fonts.sh
```

برای verify:

```bash
ls -lh vendor/tecnickcom/tc-lib-pdf-font/target/fonts/dejavu/dejavusans.json
```

> **وضعیت تست عملی:** fix مسیر font در checkpoint 11.2 پیاده شده، اما در این conversation هنوز تأیید نهایی کاربر درباره تولید PDF موفق پس از 11.2/11.3 ثبت نشده است. در ادامه باید تست واقعی PDF انجام شود.

---

## 10) Excel / Composer Collision — آخرین مشکل و Fix checkpoint 11.3

### خطای گزارش‌شده روی سایت

```text
ZipStream\ZipStream::__construct(): Argument #1 ($operationMode)
must be of type ZipStream\OperationMode, null given
```

Stack trace نشان داد `PhpSpreadsheet` از vendor افزونه دیگری یعنی:

```text
/wp-content/plugins/pinova/vendor/phpoffice/phpspreadsheet/...
```

لود شده بود، نه از:

```text
/wp-content/plugins/alavi-form-engine/vendor/...
```

### علت

WordPress چند افزونه با Composer autoloaders دارد. اگر PhpSpreadsheet یک افزونه و ZipStream نسخه دیگری با همان namespace global در یک request مخلوط شوند، class collision رخ می‌دهد.

PhpSpreadsheet 5.9.0 هم ZipStream v2 و هم v3 را پشتیبانی می‌کند و از marker `ZipStream\Option\Archive` برای تشخیص نسل استفاده می‌کند؛ mix شدن classهای دو vendor می‌تواند مسیر اشتباه `ZipStream2` را انتخاب کند.

### Fix checkpoint 11.3

- `maennchen/zipstream-php` در AFE روی **3.2.2** pin شد.
- `ExportAutoloadScope` اضافه شد.
- هنگام XLSX export، Composer autoloaderهای خارجی موقتاً از resolve path کنار گذاشته می‌شوند.
- autoloader خود AFE در اولویت قرار می‌گیرد.
- بعد با Reflection verify می‌شود کلاس‌های زیر واقعاً زیر `alavi-form-engine/vendor` هستند:
  - PhpSpreadsheet Spreadsheet
  - Xlsx Writer
  - ZipStream0
  - ZipStream
  - OperationMode
- اگر یک کلاس قبل از شروع Export توسط افزونه دیگری preload شده باشد، چون PHP نمی‌تواند همان class name را replace کند، AFE باید به‌جای Fatal/TypeError یک خطای دقیق با **مسیر فایل متداخل** نمایش دهد و در debug.log ثبت کند.
- `ExportPackageStatus::excelReady()` دیگر صرفاً برای status UI با `class_exists()` third-party classها را preload نمی‌کند؛ فایل‌های vendor خود AFE را مستقیم بررسی می‌کند.
- regression `tests/excel-composer-isolation.php` یک vendor خارجی مشابه Pinova را شبیه‌سازی می‌کند و isolation را تست می‌کند.

### دستورات پیشنهادی محیط واقعی بعد از checkpoint 11.3

از ریشه افزونه:

```bash
composer update phpoffice/phpspreadsheet maennchen/zipstream-php tecnickcom/tc-lib-pdf --with-all-dependencies --no-dev
bash tools/build-pdf-fonts.sh
composer show maennchen/zipstream-php
```

انتظار:

```text
maennchen/zipstream-php 3.2.2
```

### وضعیت تست عملی

> **خیلی مهم:** checkpoint 11.3 برای رفع collision ساخته شده ولی در این conversation هنوز کاربر نتیجه تست واقعی Excel با Pinova فعال بعد از نصب 11.3 را گزارش نکرده است. این اولین موردی است که در ادامه باید verify شود.

---

## 11) Binary Download hardening

در checkpoint 11.1 برای PDF/Excel اضافه شد:

- فایل XLSX ابتدا کامل در temp file ساخته می‌شود.
- فایل قبل از response validate می‌شود.
- اندازه > 0 چک می‌شود.
- signature ZIP/XLSX (`PK`) چک می‌شود.
- فقط بعد از موفقیت headerها ارسال می‌شوند.
- output bufferها پاک می‌شوند.
- zlib output compression برای binary download خاموش می‌شود.
- `Content-Length` ارسال می‌شود.
- `Throwable`ها catch/log می‌شوند.
- PDF نیز از BinaryDownload مشترک استفاده می‌کند.

هدف این بود که به‌جای browser error مبهم مثل `ERR_INVALID_RESPONSE` علت واقعی در مدیریت/debug.log مشخص شود.

---

## 12) Submission Admin / Reports / Localization

- submission list عنوان واقعی فرم را نمایش می‌دهد، نه فقط slug.
- submission detail/print/export labelها را از resolved schema می‌گیرد.
- Repeater به‌صورت جدول خوانا render می‌شود.
- Admin با capability مناسب inline data edit دارد.
- Repeater row add/remove در Admin وجود دارد.
- LocaleDateService برای Gregorian↔Jalali و WordPress timezone وجود دارد.
- list/detail/notes/recent/report/export dates از locale service استفاده می‌کنند.
- report/list date filter برای `fa_*` از JalaliDatePicker استفاده می‌کند.

---

## 13) Geography / Database tools

- Geography JSON import authenticated Ajax است.
- فایل client-side به chunkهای حدود 32 KiB تقسیم می‌شود.
- rows کامل در هر request bulk-upsert می‌شوند.
- trailing incomplete object برای chunk بعد نگه داشته می‌شود.
- retry/idempotent design دارد.
- progress/chunk count/row count/state/retry در UI نمایش داده می‌شود.

---

## 14) Brand Mark

per-form brand mark اضافه شده:

- mode
- attachment ID / URL
- alt text
- WordPress Media Library picker در Admin
- Renderer normal و locked preview از resolver مشترک استفاده می‌کنند.
- custom template token `{{brand_mark}}` دارد.

---

## 15) Deployment / Packaging rules

### تجربه خطای deployment قبلی

قبلاً staging این Fatal را داد:

```text
Class "BonyadAlavi\FormEngine\Duplicate\DuplicateRepository" not found
```

کد/ZIP صحیح بود ولی deployment ناقص انجام شده بود (Plugin.php جدید بدون فایل‌های جدید دیگر).

برای جلوگیری:

- Bootstrap preflight برای critical classes/files اضافه شده.
- incomplete deployment به‌جای raw fatal باید admin notice / error log بدهد.
- regression package-autoload و simulation حذف فایل critical وجود دارد.

### قاعده deployment

به‌طور عمومی **کل پوشه/پکیج افزونه باید با checkpoint جدید sync شود**، نه فقط چند فایل پراکنده؛ بعد Composer/vendor مطابق همان checkpoint آماده شود.

برای checkpointهای Export:

- ZIP ChatGPT ممکن است `vendor` دانلودشده را نداشته باشد.
- اگر کل پوشه را جایگزین می‌کنی، سپس Composer را دوباره اجرا کن.
- `vendor` سایت باید دقیقاً با `composer.json/lock` همان checkpoint سازگار باشد.
- برای PDF بعد از Composer، font build را اجرا کن.

---

## 16) QA و Regression — وضعیت checkpoint 11.3

آخرین گزارش QA checkpoint 11.3:

- **57/57 regression tests: PASS**
- **168 PHP files lint: PASS** در pipeline checkpoint
- JavaScript syntax: PASS
- `composer.json`: PASS
- No runtime CDN: PASS
- ZIP extracted QA: PASS
- ZIP integrity: PASS

در tree استخراج‌شده checkpoint 11.3:

- حدود 57 test file در `tests/`
- Export tests مهم:
  - `excel-composer-isolation.php`
  - `export-config-sanitizer.php`
  - `export-package-integration.php`
  - `export-profile.php`
  - `export-runtime-hardening.php`
  - `pdf-font-layout.php`

### Acceptance document

مسیر:

```text
docs/ACCEPTANCE-1.0.28.md
```

Checklist شامل:

- DB health
- Elementor shortcode/widget
- draft/resume
- submit/tracking
- duplicate همه behaviorها + live check
- field ordering/repeater
- date modes
- validators/custom regex
- input masks
- numeric LTR / Strong Isolation
- file preview links
- action retry
- redirect
- user actions
- PDF/post actions
- SMS داخلی و Persian WooCommerce SMS
- PDF/Excel export
- console/debug.log

مدیر پروژه acceptance عملی کامل را در انتهای توسعه انجام می‌دهد.

---

## 17) آخرین فایل‌های تغییرکرده در checkpoint 11.3

نسبت به checkpoint 11.2:

```text
M  BUILD-REPORT.md
M  README.md
M  THIRD-PARTY-NOTICES.md
M  alavi-form-engine.php
M  composer.json
M  docs/ACCEPTANCE-1.0.28.md
M  handoff.md
M  readme.txt
M  src/Export/ExcelExporter.php
A  src/Export/ExportAutoloadScope.php
M  src/Export/ExportPackageStatus.php
M  tests/bootstrap-autoload.php
M  tests/bootstrap-preflight.php
A  tests/excel-composer-isolation.php
M  tests/export-package-integration.php
M  tests/export-runtime-hardening.php
M  tools/build-vendor.sh
M  tools/qa.sh
```

فایل حذف‌شده در checkpoint 11.3 وجود نداشت.

---

## 18) فایل‌های کلیدی برای ادامه توسعه

### Core / Boot

- `alavi-form-engine.php`
- `src/Core/Plugin.php`

### Form / Rendering

- `src/Form/Form.php`
- `src/Form/Renderer.php`
- `src/Forms/JihadiGroupRegistrationForm.php`

### Admin

- `src/Admin/FormsPage.php`
- `src/Admin/SubmissionsPage.php`

### Submission / Repository

- `src/Submission/SubmissionService.php`
- `src/Repository/SubmissionRepository.php`

### Duplicate

- `src/Duplicate/DuplicatePolicy.php`
- `src/Duplicate/DuplicateRepository.php`

### Export

- `src/Export/ExportProfile.php`
- `src/Export/ExportConfigSanitizer.php`
- `src/Export/PdfTemplateRenderer.php`
- `src/Export/PdfExporter.php`
- `src/Export/ExcelExporter.php`
- `src/Export/ExportPackageStatus.php`
- `src/Export/BinaryDownload.php`
- `src/Export/ExportAutoloadScope.php`

### SMS

کلاس‌های Provider/Registry/Settings مربوطه را قبل از هر تغییر SMS پیدا و با تست‌های SMS بررسی کن؛ routing داخلی و Persian WooCommerce SMS نباید به هم وابسته شوند.

### Assets

- `assets/js/frontend.js`
- `assets/js/admin.js`
- `assets/css/frontend.css`
- `assets/css/admin.css`

### Build / QA

- `tools/qa.sh`
- `tools/build-vendor.sh`
- `tools/build-pdf-fonts.sh`

---

## 19) تست‌های عملی که باید در ادامه در اولویت باشند

### اولویت 1 — Excel collision روی سایت واقعی

بعد از نصب checkpoint 11.3 با Pinova فعال:

- Export Excel یک submission را اجرا کن.
- باید فایل `.xlsx` سالم دانلود شود.
- اگر failure داشت:
  - متن خطای Admin
  - مسیر Reflection/class conflict
  - بخش `[Alavi Form Engine]` در `wp-content/debug.log`
  را بررسی کن.
- `composer show maennchen/zipstream-php` در AFE باید `3.2.2` باشد.

### اولویت 2 — PDF فارسی

- وجود:

```text
vendor/tecnickcom/tc-lib-pdf-font/target/fonts/dejavu/dejavusans.json
```

- PDF یک submission جهادی را تولید کن.
- فارسی/RTL، table layout، لینک فایل‌ها، header/footer و tokens بررسی شوند.

### اولویت 3 — Export template overrides

- PDF override را در Admin تغییر بده و export مجدد را بررسی کن.
- Excel column reorder/title/width/visibility را تغییر بده و XLSX را بررسی کن.
- reset to source definition را تست کن.
- multi-form export باید sheet جدا برای هر فرم داشته باشد.

### اولویت 4 — Live Duplicate

- یک submission قدیمی قبل از فعال‌شدن duplicate config را با مقادیر تکراری تست کن.
- بعد از تکمیل همه fingerprint fields باید Duplicate تشخیص داده شود.
- برای مقدار غیرتکراری **هیچ پیام success** نمایش داده نشود.

---

## 20) Release plan نهایی

فقط بعد از PASS شدن acceptance عملی:

- Plugin version: `1.0.28`
- DB version: `1.0.5`
- Stable tag: `1.0.28`
- docs/release notes/build report نهایی شوند.
- production ZIP ساخته شود.
- vendor production dependencies باید داخل package نهایی یا طبق deployment contract قطعی و verify شده باشند.
- SHA256 نهایی تولید شود.
- full package QA روی ZIP extracted اجرا شود.

---

## 21) سبک همکاری مورد انتظار مدیر پروژه

- پاسخ‌ها و commit messageها ترجیحاً فارسی.
- تغییر طراحی/UX به‌صورت رندوم انجام نشود.
- اگر ambiguity طراحی واقعاً مهم است، قبل از implementation سؤال شود؛ ولی برای bugfix فنی روشن، best-effort مستقیم انجام شود.
- کاربر می‌خواهد بعد از هر patch **لیست فایل‌های تغییرکرده** را ببیند.
- فرمت پیشنهادی:

```text
M  src/...
A  tests/...
D  ...
```

- بعد از هر patch همیشه:
  1. خلاصه تغییر
  2. QA results
  3. ZIP link
  4. SHA256 link/value
  5. لیست فایل‌های تغییرکرده
  6. Git commit فارسی

---

## 22) هشدار درباره اطلاعات قدیمی در handoff

در طول توسعه چند patch پشت‌سرهم انجام شده و ممکن است برخی پاراگراف‌های تاریخی `handoff.md` رفتار یک checkpoint قدیمی را توضیح دهند. **برای رفتار نهایی فعلی، این `PROJECT_STATE.md` را در کنار کد checkpoint 11.3 مرجع بالاتر بدان.**

مثال مهم:

- در نسخه اولیه Live Duplicate برای حالت non-duplicate پیام سبز نمایش داده می‌شد.
- بعداً بنا به درخواست مدیر پروژه این رفتار حذف شد.
- رفتار فعلی: **فقط Duplicate واقعی پیام دارد.**

هرجا بین توضیح تاریخی و کد فعلی تناقضی دیدی، ابتدا تست regression و implementation checkpoint جاری را بررسی کن و سپس `handoff.md` را هم اصلاح کن.

---

## 23) متن کوتاه پیشنهادی برای شروع گفت‌وگوی بعدی

کاربر می‌تواند در گفت‌وگوی جدید این متن را بفرستد:

```text
فایل PROJECT_STATE.md وضعیت کامل پروژه Alavi Form Engine تا checkpoint 11.3 است.
ابتدا آن را کامل بخوان، سپس آخرین checkpoint پروژه و handoff.md را بررسی کن و ادامه کار را بر همان مبنا انجام بده.
قواعد QA، ساخت checkpoint، بروزرسانی handoff، لیست فایل‌های تغییرکرده و Git commit فارسی که در PROJECT_STATE آمده الزامی است.
```

---

**پایان PROJECT_STATE**

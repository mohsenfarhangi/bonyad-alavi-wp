# Alavi Form Engine — Handoff Update (Extended Actions / Retry Checkpoint)

## وضعیت checkpoint

- **مبنای پایدار:** 1.0.27
- **نسخه توسعه:** `1.0.28-dev`
- **DB checkpoint:** `1.0.5-dev.2`
- **Production-ready:** خیر
- **Stable tag:** عمداً `1.0.27`

## انجام‌شده در این ادامه

### Action / Event / Token

- `ActionDefinition` و `ActionRegistry` به runtime و Container وصل‌اند.
- ActionManager از `action_key` پایدار، Conditional Logic، `always`، `once_per_submission`، `first_in_cycle` و `continue/stop` پشتیبانی می‌کند.
- `ActionRuntime` / `ActionRunResult` خروجی زنجیره، اولین Redirect و follow-up Eventها را نگه می‌دارند؛ API قدیمی `ActionManager::run()` برای سازگاری حفظ شده است.
- Action Log و once-guard اتمیک فعال است؛ failure اکشن Submission موفق را rollback نمی‌کند.
- Eventهای canonical frontend/REST/admin برای create/draft/submit/update/status/lock/unlock/edit-request/trash/restore emit می‌شوند.
- Email و Webhook از TokenResolver مشترک استفاده می‌کنند؛ `{{status}}` وضعیت runtime جدید را در همان chain می‌بیند.
- Token Palette UI با Tokenهای واقعی هر فرم و click-to-copy ساخته شده است.
- تب «رویدادها و اکشن‌ها» Registry-based است؛ Event label فارسی، چند Action، Drag & Drop داخل همان Event، enable/disable، schema config و Conditional Logic دارد.

### Actionهای Extended

- Redirect: ابتدا تمام Actionهای server-side اجرا می‌شوند؛ URL در Ajax response برمی‌گردد و frontend redirect می‌کند؛ اولین Redirect مؤثر برنده است. External URL فقط با تنظیم صریح و Capability مدیریتی.
- Create User: mapping فیلدهای استاندارد و user_meta؛ رفتار conflict برابر fail/use/update/skip.
- Login User: user target runtime/submission/current/manual؛ Retry برای این Action غیرفعال است تا session مدیر هنگام Retry تغییر نکند.
- Update User، Assign Role، Update User Meta. Assign نقش Administrator بدون allow صریح مجاز نیست.
- Change Submission Status: target باید در Workflow همان فرم باشد؛ `submission.status_changed` فقط در تغییر واقعی emit می‌شود.
- Add Internal Note.
- Generate PDF و Email PDF: به `Dompdf\Dompdf` موجود در autoload سایت وابسته‌اند و Dompdf داخل ZIP باندل نشده است.
- Save Post/CPT: create/update/upsert، post meta و author؛ publish مستقیم نیازمند allow صریح و Capability مدیریتی است.

### Retry مدیریتی Action Log

- جزئیات Submission لاگ‌های اخیر Action را با Event/Action فارسی، status، attempts، action_key، error و timestamp نمایش می‌دهد.
- failed Actionهای retryable برای کاربر مجاز دکمه Retry دارند.
- Retry Action فعلی را با همان `action_key` پیدا می‌کند و enabled/event/condition را دوباره validate می‌کند؛ بعد guard once را آزاد می‌کند.
- follow-up Eventهای حاصل از Retry نیز از Action Engine عبور می‌کنند.

### SMS / ملی‌پیامک

- `SmsAction` و هر دو مسیر Legacy username/password و Console/API token موجودند.
- Free SMS و Pattern/Shared پشتیبانی می‌شوند.
- گیرنده: Field فرم، شماره دستی، WordPress User/Admin یا Token.
- Credentialهای password/API token با `SecretStore` رمز می‌شوند.
- regression mock پاس است.
- **تست integration واقعی ملی‌پیامک بر عهده مدیر پروژه است؛ در این مرحله Provider تغییر داده نشود مگر نتیجه تست واقعی ایراد مشخصی نشان دهد.**

### Duplicate Policy

- DuplicatePolicy به frontend submit، REST submit، admin edit، trash و restore وصل است.
- Draft فعال Duplicate محسوب می‌شود؛ Submission جاری هنگام edit exclude می‌شود؛ Trash در matching نیست.
- رفتارها: block / reference امن / custom message / allow+mark.
- allow-mode با `is_duplicate` و `duplicate_of_submission_id` ذخیره می‌شود.
- canonical owner promotion و dependant retarget داخل transaction انجام می‌شوند تا fingerprint فعال گم نشود.

### QA

- PHP lint روی `src/` و `tests/`: PASS
- JavaScript syntax check: PASS
- کل `tests/*.php`: PASS — 24 test scripts
- تست‌های جدید این مرحله:
  - `tests/action-manager-retry.php`
  - `tests/extended-action-registry.php`
  - `tests/redirect-action.php`
  - `tests/sensitive-action-config.php`

## اولویت ادامه توسعه

1. Field ordering واقعی داخل همان Step + Repeater child ordering و renderer support.
2. Date input modes: انتخاب+دستی / فقط انتخاب / فقط دستی + mask Jalali/Gregorian.
3. Character/Input Mode + allowed/forbidden chars + min/max/exact length.
4. Validator Registry/UI + Multi Select + پیام سفارشی + Custom Regex امن.
5. پس از نتیجه تست واقعی ملی‌پیامک: تنظیم Action پیش‌فرض فرم جهادی روی `leader_mobile` با `once_per_submission` و متن/Pattern انتخاب‌شده از UI.
6. regression نهایی، docs نهایی و bump Production به `1.0.28` / DB نهایی `1.0.5` / Stable tag `1.0.28`.

---

# Handoff قبلی (برای حفظ تمام تصمیم‌ها)

# Alavi Form Engine — Handoff

## وضعیت بسته

- **نسخه پایدار مبنا:** 1.0.27
- **Checkpoint توسعه:** `1.0.28-dev`
- **وضعیت:** ناتمام / فقط برای ادامه توسعه
- **قابل انتشار روی Production:** خیر
- **DB checkpoint:** `1.0.5-dev`
- **Stable tag در readme.txt:** عمداً روی `1.0.27` باقی مانده است.

این بسته از `alavi-form-engine-1.0.27.zip` ساخته شده و ادامه کار قابلیت‌های Event/Action/Duplicate/Field UI است.

---

## کاری که تا این checkpoint انجام شده

### 1) Event Registry اولیه
فایل‌های جدید:

- `src/Events/EventDefinition.php`
- `src/Events/EventRegistry.php`

Eventهای اولیه با **Label فارسی برای UI** تعریف شده‌اند:

- `submission.created` → ایجاد ثبت جدید
- `submission.draft_saved` → ذخیره پیش‌نویس
- `submission.submitted` → ثبت نهایی فرم
- `submission.updated` → ویرایش اطلاعات فرم
- `submission.status_changed` → تغییر وضعیت ثبت
- `submission.locked` → قفل شدن فرم
- `submission.unlocked` → باز شدن قفل فرم
- `edit_request.created` → ثبت درخواست ویرایش
- `edit_request.approved` → تأیید درخواست ویرایش
- `edit_request.rejected` → رد درخواست ویرایش
- `submission.trashed` → انتقال به زباله‌دان
- `submission.restored` → بازیابی از زباله‌دان

**قاعده قطعی:** در UI مدیر باید Label فارسی Event نمایش داده شود؛ slug فقط برای Developer/reference فنی است.

> این Registry هنوز به FormsPage و Action Builder وصل نشده است.

---

### 2) Token Registry / Resolver اولیه
فایل‌های جدید:

- `src/Actions/Tokens/TokenDefinition.php`
- `src/Actions/Tokens/TokenRegistry.php`
- `src/Actions/Tokens/TokenResolver.php`

Tokenهای پایه:

- `{{tracking_code}}`
- `{{submission_id}}`
- `{{form_title}}`
- `{{form_slug}}`
- `{{status}}`
- `{{field:*}}` مثل `{{field:leader_mobile}}`

**تصمیم قطعی UX:** یک Token Palette مستقل و دم‌دست در UI Actionها ساخته شود؛ هر Token:

- Label فارسی داشته باشد.
- Token فنی را نمایش دهد.
- با کلیک در Clipboard کپی شود.
- Feedback واضح مثل «کپی شد» داشته باشد.
- برای SMS، Email، Redirect text/body، Webhook payload و هر Template متنی قابل استفاده باشد.
- Field Tokenها باید از Form Definition همان فرم ساخته شوند تا مثلاً «شماره موبایل مسئول → `{{field:leader_mobile}}`» دیده شود.

> Token Palette UI هنوز پیاده نشده است.

---

### 3) Action execution log / once guard اولیه
فایل جدید:

- `src/Actions/ActionExecutionRepository.php`

Schema اولیه به `Migrator.php` اضافه شده:

- `afe_action_log`
- `afe_action_once`

هدف:

- ثبت اجرای Actionها و خطاها
- سیاست اجرای «فقط یک بار برای هر Submission»
- Retry مدیریتی در آینده
- جلوگیری از اجرای تکراری Action در درخواست‌های هم‌زمان

> هنوز به `ActionManager` و `SubmissionService` وصل نشده است. API کلاس فعلی scaffold است و قبل از production باید lifecycle دقیق success/failed/retry نهایی شود.

---

### 4) Duplicate Fingerprint اولیه
فایل‌های جدید:

- `src/Duplicate/DuplicateFingerprint.php`
- `src/Duplicate/DuplicateRepository.php`

Schema اولیه:

- `afe_submission_fingerprints`

Fingerprint با ترکیب فیلدهای انتخاب‌شده ساخته می‌شود و normalization اولیه شامل:

- تبدیل اعداد فارسی/عربی به انگلیسی
- trim
- نرمال‌سازی whitespace
- lowercase Unicode

**تصمیم‌های قطعی Duplicate:**

- مدیر می‌تواند «جلوگیری از ثبت تکراری» را برای هر فرم فعال/غیرفعال کند.
- مدیر یک یا چند Field را انتخاب می‌کند.
- تکراری بودن وقتی است که **ترکیب همه Fieldهای انتخاب‌شده** برابر باشد.
- Draftهای دیگر هم Duplicate محسوب شوند؛ همان Submission هنگام edit نباید خودش را Duplicate ببیند.
- Trash شده‌ها Duplicate محسوب نشوند.
- UI باید رفتار Duplicate را قابل انتخاب کند:
  1. جلوگیری کامل از ثبت
  2. هدایت/ارجاع به Submission قبلی
  3. پیام سفارشی
  4. اجازه ثبت ولی علامت‌گذاری به عنوان Duplicate
- پیشنهاد برای فرم جهادی: جلوگیری کامل + پیام سفارشی + در صورت داشتن دسترسی معتبر، لینک مشاهده/ادامه ثبت قبلی.

> Duplicate Policy هنوز به SubmissionService و UI وصل نشده است.

---

### 5) SMS abstraction و MeliPayamak scaffold
فایل‌های جدید:

- `src/Actions/Sms/SmsMessage.php`
- `src/Actions/Sms/SmsProviderInterface.php`
- `src/Actions/Sms/MeliPayamakProvider.php`

تصمیم‌های قطعی:

- Provider فعلی: **ملی پیامک**
- هر دو روش اتصال پشتیبانی شوند:
  - Legacy username/password
  - API key / روش جدید
- حساب فعلی کاربر **username/password** دارد.
- هر SMS Action دقیقاً **یک گیرنده** داشته باشد، چون متن مدیر و متقاضی ممکن است متفاوت باشد.
- گیرنده قابل انتخاب از:
  - Field فرم
  - شماره دستی
  - User/Admin وردپرس
  - Token داینامیک
- SMS دو Mode داشته باشد:
  - ارسال آزاد
  - Pattern
- در Pattern مدیر Pattern/Body ID و mapping پارامترها را با Token مشخص کند.
- Credentials در Settings سراسری ذخیره شوند؛ تنظیمات پیام داخل هر فرم/Action ذخیره شود.

### فرم جهادی

برای `jihadi-group-registration` بعداً Action پیش‌فرض UI ایجاد شود:

- Event: `submission.submitted` / «ثبت نهایی فرم»
- Action: SMS
- Recipient: `leader_mobile`
- Execution policy: فقط یک بار برای هر Submission
- متن/Pattern از UI مدیر تعیین شود؛ hard-code نشود.

**مهم:** Provider موجود در checkpoint فقط scaffold است. Endpoint و request contract ملی پیامک عمداً configurable گذاشته شده و قبل از Production باید براساس مستندات واقعی حساب کاربر برای هر دو روش قدیم/جدید نهایی و تست شود.

---

## تصمیم‌های قطعی Action Engine برای ادامه

### Action Builder هر فرم
در UI هر فرم بخشی با عنوان «رویدادها و اکشن‌ها» ساخته شود.

ساختار:

1. انتخاب Event با Label فارسی
2. افزودن چند Action
3. Drag & Drop ترتیب Actionها
4. فعال/غیرفعال کردن Action
5. هر Action یک `action_key` پایدار داشته باشد
6. هر Action schema تنظیمات خودش را از Registry بدهد؛ FormsPage نباید برای هر Action hard-code شود.

### Actionهای تأییدشده

- ارسال SMS
- ارسال Email
- Webhook
- Redirect
- Create WordPress User
- Login User
- Update User
- Assign Role
- Update User Meta
- تغییر Submission Status
- افزودن یادداشت داخلی
- تولید PDF
- ارسال PDF با Email
- ایجاد/بروزرسانی Post/CPT
- Custom Developer Action ثبت‌شده از کد

**PHP/callback خام از UI ممنوع است.**

### Conditional Logic برای Actionها
در همین نسخه توسعه اضافه شود.

نمونه:

- اگر `activity_scope = national` → SMS خاص اجرا شود.

حداقل operatorها از منطق فعلی ActionManager قابل reuse هستند:

- `=` / `!=`
- `>` / `>=` / `<` / `<=`
- `in`
- `contains`
- `empty`
- `not_empty`

### Execution Policy
مدیر باید کنترل کند Action:

- هر بار Event اجرا شود
- فقط یک بار برای هر Submission اجرا شود
- در صورت نیاز فقط اولین بار در چرخه اجرا شود
- همراه Conditional Logic اجرا شود

SMS فرم جهادی: **فقط یک بار برای هر Submission**.

### رفتار خطا
تصمیم قطعی:

- شکست Action نباید Submission موفق را Rollback کند.
- خطا در Action Log ذخیره شود.
- مدیر بتواند Retry کند.
- هر Action گزینه داشته باشد:
  - ادامه زنجیره در صورت خطا
  - توقف Actionهای بعدی در صورت خطا

### Redirect

- ابتدا Actionهای Server-side اجرا شوند.
- Redirect URL در پاسخ Ajax برگردد و frontend redirect کند.
- اگر چند Redirect فعال باشند، اولین Redirect مؤثر برنده باشد.
- URL داخلی پیش‌فرض مجاز باشد.
- URL خارجی فقط با گزینه صریح و capability مدیریتی مجاز شود.

### WordPress User Actions
Create / Login / Update / Assign Role جدا باشند.

Create User mapping:

- `user_login`
- `user_email`
- `display_name`
- `first_name`
- `last_name`
- `user_meta`

اگر username/email موجود بود، رفتار قابل انتخاب:

- Fail action
- Use existing user
- Update existing user
- Skip action

---

## تصمیم‌های قطعی Field / Date / Validation برای ادامه

### DateField
سه Input Mode:

1. انتخاب + ورود دستی (پیش‌فرض)
2. فقط انتخاب از تقویم
3. فقط ورود دستی

Mask:

- Jalali: `YYYY/MM/DD`
- Gregorian: `YYYY-MM-DD`

Mask فقط UX است؛ اعتبارسنجی واقعی تاریخ سمت PHP انجام شود.

**فرم جهادی:** DateFieldها جلالی بمانند.

---

### Character/Input Mode در Field Override
گزینه‌های نهایی:

- Normal
- فقط اعداد
- فقط حروف فارسی
- فقط حروف انگلیسی
- حروف و اعداد

همراه دو تنظیم مستقل:

- کاراکترهای مجاز اضافی
- کاراکترهای ممنوع اضافی

برای identifierهایی مثل کد ملی از `type=text + inputmode=numeric` استفاده شود؛ `type=number` ممنوع چون صفر اول را حذف می‌کند.

### Length در Override

- حداقل طول
- حداکثر طول
- طول دقیق

کد ملی فرم جهادی همچنان دقیقاً **۱۰ رقم** است.

---

### Validator Registry/UI
Validationها به صورت **Multi Select** براساس نوع Field نمایش داده شوند.

Validatorهای تأییدشده:

- کد ملی ایران
- شماره موبایل ایران
- شبا ایران
- Email
- URL
- کد پستی ۱۰ رقمی ایران
- شماره کارت بانکی ۱۶ رقمی با checksum
- تلفن ثابت ایران
- Date validation براساس Calendar
- Custom Regex

برای هر Validator:

- پیام خطای سفارشی قابل تنظیم باشد.
- Registry توسعه‌پذیر باشد تا Developer Validator جدید ثبت کند.

### Custom Regex

- فقط کاربر دارای `afe_manage_settings` بتواند تعریف کند.
- قبل از Save compile/test شود.
- محدودیت طول داشته باشد.
- Flagهای مجاز محدود شوند.
- پیام خطای سفارشی اجباری باشد.
- Frontend برای UX اجرا شود.
- Backend مرجع نهایی validation باشد.

---

## Field Ordering / UI Layout

- Drag & Drop فقط **داخل همان Step**.
- Field بین Stepها در این نسخه منتقل نشود.
- `HtmlBlock`ها نیز همراه Fieldها قابل Drag & Drop باشند.
- Child Fieldهای Repeater داخل همان Repeater قابل مرتب‌سازی باشند.
- هدف تغییر UI واقعی فرم است، نه فقط ترتیب جدول Admin.

---

## بازطراحی UI تنظیمات فرم

FormsPage فعلی شلوغ است. Tabهای تأییدشده:

1. عمومی
2. فیلدها و چیدمان
3. جلوگیری از تکرار
4. رویدادها و اکشن‌ها
5. Workflow
6. Preview و Lock
7. قالب‌ها
8. CSS/JS
9. دسترسی

Eventها در UI فقط با Label فارسی اصلی دیده شوند؛ slug در Developer metadata کوچک باشد.

---

## بخش‌هایی که هنوز باید پیاده شوند

به ترتیب پیشنهادی:

1. **ActionRegistry / ActionDefinition** با schema عمومی تنظیمات هر Action.
2. اتصال `EventRegistry` به Container و FormsPage.
3. اتصال `TokenRegistry/Resolver` به EmailAction/Webhook/SMS و Action UI.
4. نهایی‌سازی `ActionExecutionRepository` برای once/retry/race-safety.
5. نهایی‌سازی MeliPayamak legacy + API-key براساس مستندات واقعی و تست روی Account.
6. ساخت `SmsAction` و ثبت در ActionManager.
7. refactor `EmailAction` و `WebhookAction` برای TokenResolver مشترک.
8. اضافه کردن Actionهای Redirect/User/Status/Note/PDF/Post.
9. Event emission استاندارد از SubmissionService و مسیرهای admin lock/status/edit request/trash/restore.
10. DuplicatePolicy service و اتصال fingerprint به create/update/trash/restore.
11. UI کامل «رویدادها و اکشن‌ها» با Action repeater و drag/drop.
12. Token Palette با click-to-copy و toast.
13. UI «جلوگیری از تکرار».
14. Tabbed FormsPage.
15. Field ordering override و renderer support.
16. Date input modes + mask.
17. Field Override character mode/length/additional allowed/forbidden characters.
18. Validator Registry + Multi Select + custom messages + safe Regex.
19. تنظیم پیش‌فرض SMS فرم جهادی به `leader_mobile`، فقط یک بار.
20. تست‌های regression و migration.
21. تکمیل `README.md`, `readme.txt`, `BUILD-REPORT.md`, docs و bump نهایی به 1.0.28 production.

---

## فایل‌های تغییرکرده در این checkpoint نسبت به 1.0.27

### تغییرکرده

- `alavi-form-engine.php`
- `src/Database/Migrator.php`
- `README.md`
- `readme.txt`

### جدید

- `src/Events/EventDefinition.php`
- `src/Events/EventRegistry.php`
- `src/Actions/ActionExecutionRepository.php`
- `src/Actions/Tokens/TokenDefinition.php`
- `src/Actions/Tokens/TokenRegistry.php`
- `src/Actions/Tokens/TokenResolver.php`
- `src/Actions/Sms/SmsMessage.php`
- `src/Actions/Sms/SmsProviderInterface.php`
- `src/Actions/Sms/MeliPayamakProvider.php`
- `src/Duplicate/DuplicateFingerprint.php`
- `src/Duplicate/DuplicateRepository.php`

---

## نکات امنیتی/معماری که نباید در ادامه شکسته شوند

- PHP 8.3 + `strict_types=1`
- Namespace: `BonyadAlavi\FormEngine`
- PSR-4
- Code definition فرم Source of Truth؛ Admin فقط Override
- هیچ PHP خام از UI اجرا نشود.
- هیچ runtime JS/CSS خارجی/CDN اضافه نشود.
- Action/Validator/Event باید Registry-based باشد، نه hard-code پراکنده در FormsPage.
- Elementor Widget constructor DI دوباره اضافه نشود.
- FileField فقط توسط FileUploader validate شود.
- File ownership = `submission_id + field_key`.
- Form lock سمت PHP enforce شود.
- Capability = global + per-form.
- Tokenهای UI whitelist شوند؛ `{{field:*}}` فقط field واقعی فرم را resolve کند.
- Regex UI محدود و validate شود؛ Backend مرجع نهایی باشد.
- Action failure Submission را rollback نکند.
- SMS credentials در تنظیمات سراسری و امن نگهداری شوند.
- در هر نسخه `README.md` و `readme.txt` و `BUILD-REPORT.md` کامل شوند.

---

## هشدار مهم برای چت بعدی

این ZIP **Production-ready نیست**. نسخه 1.0.28-dev فقط checkpoint توسعه است.

در شروع چت جدید:

1. همین ZIP را مبنا قرار بده.
2. ابتدا `handoff.md` را بخوان.
3. وضعیت فایل‌های جدید و schema `1.0.5-dev` را inspect کن.
4. قبل از اضافه کردن UI، Action Registry و wiring سرویس‌ها را کامل کن.
5. Provider ملی پیامک را بدون بررسی مستندات واقعی endpoint/request contract فعال نکن.
6. در پایان توسعه، نسخه Production باید `1.0.28` و DB version نهایی (احتمالاً `1.0.5`) شود و `Stable tag` از 1.0.27 به 1.0.28 تغییر کند.

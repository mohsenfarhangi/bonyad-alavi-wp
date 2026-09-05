# Alavi Form Engine — Handoff Update (Feature-complete / Pre-acceptance)

## وضعیت checkpoint

- **مبنای پایدار:** `1.0.27`
- **نسخه توسعه:** `1.0.28-dev`
- **DB checkpoint:** `1.0.5-dev.2`
- **Feature status:** تمام آیتم‌های برنامه‌ریزی‌شده handoff نسخه 1.0.28 در سورس پیاده شده‌اند.
- **Production-ready:** هنوز خیر؛ acceptance عملی WordPress/MySQL/Elementor طبق تصمیم مدیر پروژه در انتهای توسعه انجام می‌شود.
- **Stable tag:** فعلاً `1.0.27`
- **تست واقعی ملی‌پیامک:** PASS گزارش‌شده توسط مدیر پروژه در 2026-09-04

## Patch بررسی زنده Duplicate قبل از Submit

- وقتی Duplicate Policy فعال باشد، Renderer مسیر فیلدهای Fingerprint را با `data-afe-duplicate-fields` در فرم قرار می‌دهد.
- Frontend فقط تغییر همان فیلدها را دنبال می‌کند و به‌محض اینکه همه مسیرهای انتخاب‌شده مقدار معنی‌دار داشته باشند، با debounce 450ms درخواست `afe_check_duplicate` می‌فرستد.
- درخواست Live فقط `afe_data` و credentialهای لازم برای edit را می‌فرستد؛ File bytes در این preflight ارسال نمی‌شوند.
- درخواست‌های قبلی هنگام تایپ جدید با `AbortController` لغو می‌شوند و پاسخ stale به UI اعمال نمی‌شود.
- Endpoint عمومی با همان nonce فرم محافظت می‌شود و قبل از Query، داده را با schema/Mask فعلی sanitize و normalize می‌کند.
- در ویرایش معتبر، Submission جاری از مقایسه Duplicate مستثنا می‌شود؛ spoof کردن `submission_id` بدون دسترسی باعث exclusion نمی‌شود.
- نتیجه Live سه حالت UI دارد: سبز برای «تکراری پیدا نشد»، قرمز برای policyهای blocking و زرد برای `allow`.
- در behavior=`reference` لینک ثبت قبلی فقط وقتی برگردانده می‌شود که `duplicateReferenceUrl()` دسترسی معتبر کاربر را تأیید کند.
- بررسی نهایی Duplicate در `handleHttp()` و guard تراکنشی fingerprint بدون تغییر باقی مانده و مرجع نهایی است؛ Live check فقط UX زودهنگام است.
- Repeater pathها مثل `members.national_id` نیز پشتیبانی می‌شوند و add/remove ردیف باعث بازبینی state Duplicate می‌شود.
- Regressionها: `tests/live-duplicate-check.php` و assertionهای تکمیلی `tests/duplicate-policy.php`.

## Patch اصلاح Master Switch پیامک

- مشکل گزارش‌شده در staging: با وجود فعال‌بودن SMS در UI، Runtime می‌توانست پیام «سرویس پیامک در تنظیمات Alavi Form Engine غیرفعال است» بدهد.
- `SmsSettings` اضافه شد تا ساختار فعلی `afe_settings['sms']['enabled']` و کلید legacy `afe_settings['sms_enabled']` به‌صورت مرکزی normalize شوند.
- Settings Save از این checkpoint هر دو کلید current/legacy را با مقدار یکسان ذخیره می‌کند تا ارتقا/بازگشت بین checkpointها state متناقض نسازد.
- `SmsProviderRegistry` اکنون `enabledResolver` runtime دارد؛ درست هنگام resolve هر SMS Action وضعیت فعلی `afe_settings` دوباره خوانده می‌شود و فقط به snapshot زمان Plugin boot وابسته نیست.
- Provider routing داخلی و Persian WooCommerce SMS هر دو از همین Master Switch مشترک استفاده می‌کنند.
- Bootstrap preflight کلاس `SmsSettings` را نیز بررسی می‌کند.
- Regression اختصاصی: `tests/sms-settings-switch.php`.

## Patch لینک مشاهده فایل‌ها در Preview

- FileField در Preview دیگر فقط نام فایل را نمایش نمی‌دهد؛ نام هر فایل به لینک مشاهده تبدیل شده است.
- فایل‌های ذخیره‌شده با `target="_blank"` و `rel="noopener noreferrer"` در تب جدید باز می‌شوند.
- URL فایل ذخیره‌شده ابتدا از Attachment متعلق به همان Submission resolve می‌شود و در صورت نبود Attachment از URL امن رکورد فایل استفاده می‌شود.
- Live Preview برای فایل تازه‌ای که هنوز Upload نشده با `URL.createObjectURL()` لینک موقت مرورگر می‌سازد؛ بنابراین فایل قبل از Submit نیز از Preview قابل بازکردن است.
- Live Preview فایل‌های موجود از `data-file-url` همان فایل استفاده می‌کند و فقط schemeهای `http/https/blob` را می‌پذیرد.
- Regression اختصاصی: `tests/preview-file-links.php`.

## Patch جهت اعداد در Preview و حذف text-align عمومی کنترل‌ها

- `text-align:right !important` از rule عمومی `.afe-shell.afe-isolation-strong .afe-form .afe-control` حذف شد؛ جهت متن هر کنترل دیگر به‌صورت اجباری از rule عمومی تعیین نمی‌شود.
- کنترل‌های عددی/شماره‌ای همچنان با `dir="ltr"` و `data-afe-ltr="1"` جهت صریح خود را دارند.
- در Preview زنده، مقدارهای عددی/شماره‌ای براساس نوع کنترل یا عددی‌بودن مقدار با `dir="ltr"` و `data-afe-ltr="1"` علامت‌گذاری می‌شوند.
- در Preview قفل‌شده/سروررندر نیز فیلدهای عددی، تاریخ، موبایل و مقدارهای عددی‌مانند LTR/چپ‌چین هستند.
- سلول‌های عددی Repeater و ستون شماره ردیف Preview نیز LTR و `text-align:left` هستند.
- Regression اختصاصی: `tests/preview-numeric-direction.php`.
- QA script دیگر به `/tmp` سیستم وابسته نیست و فایل‌های موقت را داخل پوشه موقت محلی پروژه می‌سازد و در پایان پاک می‌کند.

## Patch اصلاح Strong Isolation برای فیلدهای LTR

- مشکل گزارش‌شده در staging: rule عمومی `.afe-shell.afe-isolation-strong .afe-form .afe-control { text-align:right !important; }` می‌توانست در cascade واقعی سایت روی ورودی‌های عددی غالب بماند.
- Renderer اکنون برای هر کنترل شماره‌ای/عددی علاوه بر `dir="ltr"`، marker صریح `data-afe-ltr="1"` تولید می‌کند.
- CSS نهایی از selector مستقل و قوی‌تر `input.afe-control[data-afe-ltr="1"]` استفاده می‌کند و `direction:ltr !important` + `text-align:left !important` را بعد از Strong Isolation اعمال می‌کند؛ وابستگی به `:is()` برای این override حذف شده است.
- `unicode-bidi:plaintext` برای رفتار پایدار caret/نمایش رشته‌های عددی ترکیبی اضافه شده است.
- همین marker و override در ویرایش Submission و Repeaterهای wp-admin نیز اعمال می‌شود.
- Regression `tests/numeric-input-direction.php` اکنون وجود marker و selector Strong Isolation جدید را الزام می‌کند.

## Patch جهت نمایش فیلدهای عددی / شماره‌ای

- ورودی‌های `tel`، `number`، `date` و هر input با `inputmode=numeric|decimal|tel` در Renderer به‌صورت semantic با `dir="ltr"` خروجی می‌شوند.
- متن داخل این کنترل‌ها در Frontend از سمت چپ شروع و `text-align:left` است؛ Strong Isolation نیز با rule صریح `!important` اجازه بازگشت قالب سایت به راست‌چین را نمی‌دهد.
- همین رفتار در ویرایش Submission داخل wp-admin و Child Fieldهای Repeater مدیریتی اعمال شده است.
- این تغییر روی موبایل، تلفن ثابت، کد ملی، شبا، کد پستی، کارت بانکی، DateField و سایر ورودی‌های عددی قابل اعمال است؛ Label و Layout کلی فرم همچنان RTL باقی می‌مانند.
- Regression اختصاصی: `tests/numeric-input-direction.php` و assertionهای تکمیلی در تست‌های Input Mask Renderer/Admin.

## Patch یکپارچه‌سازی SMS با Persian WooCommerce SMS

- `SmsProviderRegistry` اضافه شد؛ Provider پیش‌فرض سراسری و Override مستقل در هر SMS Action پشتیبانی می‌شود.
- Provider داخلی `melipayamak` بدون تغییر قراردادهای Legacy/API-token حفظ شده است.
- Adapter جدید `PersianWooCommerceSmsProvider` از API عمومی `PWSMS()->send_sms()` و Gateway فعال خود افزونه Persian WooCommerce SMS استفاده می‌کند؛ Credential، API Key، Password و Sender آن افزونه داخل AFE کپی/ذخیره نمی‌شود.
- اگر Gateway در Persian WooCommerce SMS تغییر کند، AFE در اجرای بعدی همان Gateway جدید را تشخیص می‌دهد.
- ارسال آزاد برای Gateway فعال قابل استفاده است. Pattern فقط وقتی در UI فعال می‌شود که strategy شناخته‌شده/ثبت‌شده وجود داشته باشد؛ built-in strategy فعلی شامل `MeliPayamakPattern`/ملی‌پیامک خدماتی/ترکیبی و `KaveNegarLookUp` است.
- Gateway ناشناخته در Pattern به‌جای ارسال payload حدسی با خطای روشن Fail می‌شود؛ Submission rollback نمی‌شود و خطا در Action Log ثبت می‌شود.
- Hookهای توسعه‌دهنده: `afe_register_sms_providers`, `afe_pwsms_supported_modes`, `afe_pwsms_pattern_strategy`, `afe_pwsms_pattern_payload`, `afe_pwsms_send_data`.
- در تنظیمات سراسری AFE، Provider پیش‌فرض قابل انتخاب است؛ وضعیت افزونه خارجی، Gateway فعال و modeهای قابل استفاده نمایش داده می‌شوند.
- در Action Builder، Provider=`پیش‌فرض سراسری / ملی پیامک داخلی / Persian WooCommerce SMS` قابل انتخاب است و modeهای نامعتبر براساس capability فعلی Gateway غیرفعال می‌شوند.
- اگر Persian WooCommerce SMS بعداً غیرفعال شود، config قبلی حفظ می‌شود و silently به Provider دیگر تغییر نمی‌کند؛ Runtime خطای واضح می‌دهد.
- `sms_enabled` Master Switch کل SMS است. نبود OpenSSL فقط ذخیره Credential جدید Provider داخلی را محدود می‌کند و Integration خارجی را غیرفعال نمی‌کند.
- Bootstrap preflight کلاس‌های جدید SMS Router/Adapter را نیز بررسی می‌کند.
- Regressionها: `sms-provider-registry.php`, `persian-woocommerce-sms-provider.php`, `sms-action-provider-routing.php`.

## افزوده‌شده پس از feature-complete اولیه: Input Mask عمومی

- `InputMaskDefinition`, `InputMaskRegistry`, `InputMaskPattern` اضافه شدند.
- presetهای Core: موبایل ایران، تلفن ثابت، کد ملی، کد پستی، کارت بانکی و شبای ۲۴ رقمی.
- UI هر Field متنی/تلفن: ارث‌بری، بدون Mask، preset و Custom Mask.
- Syntax Custom: `9` رقم، `A` حرف، `*` حرف/رقم و escape با `\`.
- Mask فقط لایه UX است؛ `SubmissionService` قبل از Validation، Duplicate، Token/Action/SMS و Storage مقدار را normalize می‌کند.
- Renderer محدودیت‌های length/pattern قدیمی را روی مقدار normalize‌شده اعمال می‌کند تا separatorهای Mask باعث validation اشتباه نشوند.
- نمایش/ویرایش Submission در wp-admin و ردیف‌های جدید Repeater مدیریتی نیز همان Mask را نمایش می‌دهند و هنگام Save دوباره سمت PHP normalize می‌شوند.
- DateField از generic mask جدا می‌ماند و mask آن بر اساس calendar همان `YYYY/MM/DD` یا `YYYY-MM-DD` است.
- فرم جهادی برای موبایل‌ها، تلفن ثابت گروه، کدهای ملی و شبای حقوقی preset پیش‌فرض دارد؛ Admin Override همچنان اولویت دارد.
- Hook توسعه‌دهنده: `afe_register_input_mask_definitions`.

## Patch UI فیلدها و چیدمان

- تب «فیلدها و چیدمان» اکنون فقط لیست چیدمان Drag & Drop را نمایش می‌دهد.
- Editorهای Override هر Field از محتوای اصلی تب حذف و به `afe-admin-side` منتقل شدند.
- با کلیک روی Field یا Child Field داخل Repeater، پنل تنظیمات همان Field در سایدبار باز می‌شود؛ HtmlBlock فقط قابل مرتب‌سازی است و Override فیلدی ندارد.
- انتخاب Field در UI مشخص می‌ماند، پنل قابل بستن است و انتخاب آخر در همان session مرورگر حفظ می‌شود.
- برای اینکه inputهای سایدبار همراه سایر تنظیمات ذخیره شوند، کل `afe-admin-layout` داخل فرم تنظیمات واحد قرار گرفت؛ مسیر ذخیره و sanitizerهای قبلی بدون تغییر باقی ماندند.
- «قالب اختصاصی هر مرحله» از تب فیلدها به تب «قالب‌ها» منتقل شد تا تب فیلدها واقعاً فقط چیدمان داشته باشد.
- در حالت تب فیلدها، ستون سایدبار در دسکتاپ عریض‌تر و قابل اسکرول می‌شود و در عرض‌های کوچک به layout تک‌ستونه برمی‌گردد.
- Regression اختصاصی: `tests/admin-field-layout-sidebar.php`.

## انجام‌شده در checkpoint جدید

### Release hardening اکشن‌های WordPress User

- `UserActionGuard` اضافه شد.
- Login / Update / Assign Role / Update User Meta برای Administrator یا هر کاربر دارای `manage_options` بدون opt-in صریح محافظت‌شده اجرا نمی‌شوند.
- opt-in فقط از تنظیم دارای capability=`afe_manage_settings` قابل ذخیره است و Runtime نیز آن را enforce می‌کند.
- شاخه existing در Create User (`use/update/skip`) نیز برای حساب مدیریتی همین guard را دارد.
- meta keyهای امنیتی `*_capabilities`, `*_user_level`, `session_tokens`, `_application_passwords` از Action Builder قابل نوشتن نیستند.
- تمام User Metaها قبل از ایجاد/ویرایش کاربر یا اولین write اعتبارسنجی می‌شوند تا failure نیمه‌کاره باقی نماند.
- Assign Role نقش سفارشی دارای `manage_options` را نیز privileged می‌شناسد.

### Action schema / URL / Date QA

- URLهای Token‌دار در sanitizer با placeholder امن پردازش می‌شوند تا `{{field:*}}` و Tokenهای معتبر هنگام Save از بین نروند؛ URL resolve‌شده همچنان در Runtime sanitize می‌شود.
- `supportsExecutionPolicy=false` در `ActionDefinition` اکنون توسط UI و sanitizer رعایت می‌شود.
- تست Renderer واقعی برای Jalali/Gregorian و هر سه mode `combined/picker/manual` اضافه شد.
- `tools/qa.sh` برای اجرای یک‌جای regression/lint/JS/composer/assets/no-CDN/version-consistency اضافه شد.
- `docs/FEATURE-COMPLETENESS-1.0.28.md` وضعیت یک‌به‌یک فیچرهای handoff را ثبت می‌کند.

## QA این checkpoint

- `tools/qa.sh`: PASS
- Regression: **49/49 PASS**
- PHP lint: **152 فایل PASS**
- JavaScript syntax: PASS
- composer.json: PASS
- local JalaliDatePicker: PASS
- runtime external CDN registration: PASS
- Version consistency: PASS (`1.0.28-dev`)

## Patch استقرار / Bootstrap preflight

- گزارش staging در 2026-09-04 نشان داد اگر فایل‌های جدید checkpoint به‌صورت ناقص روی نسخه قدیمی کپی شوند، `DuplicateRepository` می‌تواند هنگام boot با `Class not found` متوقف شود.
- فایل `DuplicateRepository.php` و PSR-4 صحیح داخل بسته موجود بودند؛ ریشه خطا deployment ناقص/ناخوانا تشخیص داده شد.
- bootstrap اکنون ۱۷ کلاس حیاتی boot را قبل از ثبت hook اصلی بررسی می‌کند و در نصب ناقص به‌جای Fatal خام، Admin Notice + error log واضح ثبت می‌کند.
- تست‌های `bootstrap-autoload.php` و `bootstrap-preflight.php` اضافه شدند تا هم autoload بسته و هم رفتار امن در نصب ناقص regression داشته باشند.
- برای ارتقا بین checkpointها باید **کل پوشه افزونه** جایگزین شود؛ کپی انتخابی فایل‌های تغییرکرده پشتیبانی نمی‌شود.

## مرحله بعد / Release gate

از فهرست فیچرهای اصلی 1.0.28 مورد برنامه‌ریزی‌شده دیگری باقی نمانده است. طبق تصمیم مدیر پروژه، تست‌های عملی پس از اتمام همه فیچرها انجام می‌شوند.

1. checkpoint نهایی dev روی staging واقعی نصب شود.
2. ماتریس `docs/ACCEPTANCE-1.0.28.md` کامل اجرا شود.
3. پس از PASS کامل:
   - Plugin version: `1.0.28`
   - DB version: `1.0.5`
   - Stable tag: `1.0.28`
   - changelog/docs نهایی و Production ZIP ساخته شود.

**قاعده:** تا قبل از live acceptance، نسخه با برچسب Production منتشر نشود.

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

## Checkpoint 10.6 — Live Duplicate fixes

- Live Duplicate فقط هنگام پیدا شدن ثبت تکراری پیام نمایش می‌دهد؛ حالت «تکراری نیست» و خطای موقت preflight در UI بی‌صدا هستند.
- `DuplicatePolicy::evaluate()` دیگر ترکیب ناقص را Duplicate محسوب نمی‌کند؛ تمام فیلدهای Fingerprint باید مقدار معنادار داشته باشند.
- مشکل مهم ثبت‌های قدیمی رفع شد: تغییر/فعال‌سازی فیلدهای Duplicate اکنون باعث rebuild ایندکس fingerprint تمام Submissionهای فعال همان فرم می‌شود.
- rebuild از قدیمی‌ترین Submission شروع می‌شود تا canonical owner پایدار باشد؛ Trashها وارد ایندکس نمی‌شوند.
- برای نصب‌های موجود یک signature per-form در option `afe_duplicate_index_signatures` نگهداری می‌شود. اگر signature وجود نداشته باشد یا enabled/fields/behavior تغییر کند، اولین Live Check یا Submit ایندکس را self-heal می‌کند.
- در رفتار `allow`، metadataهای `is_duplicate` و `duplicate_of_submission_id` نیز هنگام rebuild بازسازی می‌شوند.
- Save تنظیمات فرم پس از ذخیره موفق، rebuild را اجرا می‌کند؛ در صورت خطا پیام مدیریتی واضح نمایش داده می‌شود.
- تست جدید `tests/duplicate-index-rebuild.php` اضافه و `tests/live-duplicate-check.php` به semantics جدید به‌روزرسانی شد.
- QA این checkpoint: 51 regression PASS، 154 PHP lint PASS، JS/composer/assets/version checks PASS.

### نکته acceptance

پس از نصب این checkpoint، برای تست Duplicate حتماً یک ترکیب از اطلاعات یک Submission قدیمی را وارد کنید. نیازی به Submit نهایی نیست؛ پس از تکمیل تمام فیلدهای Fingerprint باید فقط در حالت تکراری پیام ظاهر شود.


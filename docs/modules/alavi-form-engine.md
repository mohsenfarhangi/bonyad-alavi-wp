# MODULE-AFE — Alavi Form Engine

**Status:** active development / pre-acceptance  
**Path:** `wp-content/plugins/alavi-form-engine/`  
**Namespace:** `BonyadAlavi\FormEngine`  
**Development version:** `1.0.28-dev.6`  
**DB checkpoint:** `1.0.5-dev.2`  
**Stable baseline/tag documented:** `1.0.27`  
**PHP:** `>=8.3`

## Purpose

Code-first extensible WordPress form engine با rendering، submission persistence، validation، uploads، duplicate detection، events/actions/tokens، PDF/XLSX export، data sources، Admin/Reports/REST و Elementor integration.

## Bootstrap and Structure

Entry point: `alavi-form-engine.php`.

Plugin:
- PSR-4 fallback داخلی را مستقل از Composer ثبت می‌کند.
- در صورت وجود `vendor/autoload.php` loader اختصاصی را نگه می‌دارد.
- critical bootstrap files/classes را قبل از boot validate می‌کند.
- boot اصلی را در `after_setup_theme` priority 20 اجرا می‌کند تا theme/plugin integrationها بتوانند پیش از `afe_register_forms` ثبت شوند.
- Engine هیچ Source Definition پروژه‌ای built-in ثبت نمی‌کند؛ فرم‌های سایت از hook `afe_register_forms` وارد `FormRegistry` می‌شوند.

Source domains:
`Actions, Admin, Core, DataSource, Database, Duplicate, Elementor, Events, Export, Form, InputMask, Localization, Repository, Rest, Security, Style, Submission, Template, Validation`.

## Form Definition and Overrides

Contract: [ADR-001](../decisions/ADR-001-code-defined-forms.md).

- PHP Source Definition authority عضویت field/step و default behavior است.
- Admin فقط overrideهای whitelisted اعمال می‌کند.
- field/html block فقط داخل Step خود reorder می‌شود.
- Repeater children فقط داخل همان Repeater reorder می‌شوند.
- cross-step move پشتیبانی نمی‌شود.
- item جدید code که در saved order قدیمی نیست به scope خودش append می‌شود.
- raw PHP/callback از admin پذیرفته نمی‌شود.

## Fields, Dates, Validation and Masks

DateField modes:
- `combined` (default)
- `picker`
- `manual`

Storage/display:
- Jalali: `YYYY/MM/DD`
- Gregorian: `YYYY-MM-DD`

Validator Registry core coverage:
- Iranian National ID
- Iranian mobile
- IBAN
- Email
- URL
- postal code
- bank card checksum
- landline
- date/calendar
- custom regex

Custom Regex:
- نیازمند `afe_manage_settings`
- max 500 chars
- flags محدود `i,m,s,u,x`
- compile test قبل save
- custom message الزامی
- runtime PCRE limits
- frontend فقط UX؛ PHP authority

Character/length overrides:
`normal, digits, persian, english, alnum` + allowed/forbidden chars + min/max/exact length.

Input Mask syntax:
- `9` digit
- `A` Unicode letter
- `*` alphanumeric
- `\` escape

Core presets شامل mobile، landline، national ID، postal code، bank card و 24-digit IBAN است. Mask فقط presentation/UX است؛ PHP قبل validation/duplicate/action/storage normalize می‌کند. در HEAD فعلی preset IBAN بدون فاصله است.

## Numeric Direction / Presentation

- numeric/tel/date-like controls LTR و left-aligned هستند.
- label/layout کلی RTL می‌ماند.
- Preview و Repeater numeric values نیز LTR هستند.
- Admin submission presentation/editing normalized raw value را حفظ می‌کند؛ mask presentation در frontend/preview opt-in است.
- generic `.afe-form .afe-control` نباید forced right alignment داشته باشد.

## Uploads

- native file input برای FormData/server باقی است ولی UX dropzone اختصاصی دارد.
- file events داخل AFE isolate می‌شوند.
- existing file ownership با submission + field relationship enforce می‌شود.
- keep/remove manifest server-side validate می‌شود.
- single-file replacement rollback protection دارد.
- generic validator نباید FileField validation را duplicate کند.
- Preview saved file را با attachment متعلق به همان submission/field یا safe URL resolve می‌کند؛ new selection می‌تواند blob preview داشته باشد.

## Select UI and Style Isolation

Custom Select default است؛ native select همچنان submission/validation source است. Search، keyboard، multiple، dependent Ajax، repeater و disabled options پشتیبانی می‌شوند. Theme Select2 داخل `.afe-form` isolate/cleanup می‌شود.

Style modes:
- `strong` (default)
- `default`
- `disabled`

tokens زیر `.afe-shell` scope شده‌اند و strong mode reset محدود/defensive دارد.

## Submission Lifecycle

- draft/resume
- final submit + tracking
- optional preview
- lock after submit
- admin edit-request/unlock flow
- soft trash / restore
- permanent purge فقط بعد trash
- custom CAPTCHA refresh Ajax

Lock server-side enforce می‌شود و public renderer با admin capability آن را bypass نمی‌کند.

## Duplicate Detection

**راهنمای مدیر و کاربر:** [بررسی تکراری بودن فرم، ارجاع به ثبت قبلی و ادامه پیش‌نویس](afe-duplicate-detection-user-guide.md).

Features:
- multi-field fingerprint
- Persian/Arabic digit normalization
- whitespace/case normalization
- drafts included
- Opt-in `duplicate.resume_draft` permits continuing an existing, unlocked `draft` after the duplicate fingerprint matches, **only with verified ownership** through the existing WordPress account, private edit link, or tracking code. Fingerprint-only knowledge is never an authentication method.
- The admin Duplicate tab exposes the per-form `duplicate_resume_draft` toggle. Forms require enabled duplicate detection with fingerprint fields, `save_draft`, and `editing_enabled`. When disabled, existing duplicate behavior is unchanged.
- A verified draft returns an `edit_url` and `resume_draft=true`; anonymous/unauthorized visitors get only a generic recovery hint (no submission ID, draft state, URL, saved data or credential).
- current edited submission excluded
- trash excluded
- behaviors: block/reference/custom/allow+mark
- canonical owner promotion و dependent retarget transaction-safe

Live check:
- فقط پس از complete شدن همه fingerprint fields
- incomplete mask/date query نمی‌زند
- debounce حدود 450ms
- stale request cancel
- file bytes ارسال نمی‌شوند
- Repeater paths پشتیبانی می‌شوند
- submit guard authority نهایی است
- non-duplicate **هیچ success message** نشان نمی‌دهد
- duplicate واقعی policy message/warning نشان می‌دهد
- index signature/rebuild باعث self-heal submissionهای قدیمی می‌شود.

## Events, Actions and Tokens

Core:
- `ActionDefinition / ActionRegistry`
- `EventRegistry`
- `TokenRegistry / TokenResolver`
- `ActionRuntime`
- persisted action logs و atomic once guards

Policies:
`always`, `once_per_submission`, `first_in_cycle`.

Implemented actions:
SMS، Email، Webhook، Redirect، Create/Login/Update User، Assign Role، Update User Meta، Change Status، Internal Note، Generate/Email PDF، Create/Update/Upsert Post/CPT و custom registry action.

Security:
- external redirect و direct publish opt-in دارند.
- Administrator یا user دارای `manage_options` برای User Actions protected target است.
- capability/session/application-password meta keys block می‌شوند.
- user-meta mappings قبل از write کامل prevalidate می‌شوند.
- Retry current enabled/event/condition را دوباره resolve می‌کند.

## Global settings tabs & SMS diagnostics (2026-10-22)

- صفحه «تنظیمات و مدیریت دسترسی» از صفحه عمودی طولانی به **۶ تب مجزا** تقسیم شد: «عمومی و API»، «پنل‌های پیامک»، «دیباگ پیامک»، «استایل فرم»، «دسترسی نقش‌ها»، «دسترسی فرم‌ها». این‌ها فقط UI هستند: همه کنترل‌ها در همان form اصلی باقی می‌مانند و **همه تنظیمات همچنان با یک بار ذخیره ارسال می‌شوند**. تب فعال بعد از Post/Redirect/Get باز می‌ماند، جابه‌جایی تب‌ها فیلدها را reset یا disabled نمی‌کند و دکمه ذخیره در پایین viewport به‌صورت sticky دیده می‌شود.
- گزینه مستقل **«ثبت گزارش دیباگ پیامک»** در تب «دیباگ پیامک» با کلید `afe_settings['sms']['debug_enabled']` به‌طور پیش‌فرض خاموش است. بعد از فعال‌سازی و ذخیره، خروجی‌های `SMS Action`، `OTP` و «تست اتصال» در یک فهرست محدود **۴۰ رکورد اخیر** در گزینه غیرautoload `afe_sms_debug_events` ذخیره می‌شوند. خواندن/پاک‌کردن فقط توسط مدیر دارای `Capabilities::MANAGE_SETTINGS` و nonce اختصاصی `afe_sms_debug` از AJAX `afe_sms_debug` انجام می‌شود. تغییر نام/کلید Provider، فرم یا event در logger داده خام نمی‌نویسد.
- داده مجاز گزارش: تاریخ UTC، مسیر ارسال، شناسه Provider، success/failed، مرحله امن خطا، `http_status`، `ret_status`، `value_code` فقط به‌شکل عددی کوچک، و شناسه عددی پیام در صورت موفقیت. **شماره همراه، متن پیام، رمز OTP، username/password، API key، raw response و exception message ذخیره یا نمایش داده نمی‌شوند**. تشخیص خطای Farapayamak از کدهای عددی مستقل از پاسخ خام استفاده می‌کند؛ سایر providerها فقط خلاصهٔ امن ارائه می‌کنند.
- در «تست اتصال پنل پیامک» نتیجهٔ خلاصهٔ فنی و شناسه پیام به‌صورت فوری در همان بخش نمایش داده می‌شود، حتی اگر ثبت تاریخچه خاموش باشد. سابقه SMS Action و OTP فقط پس از روشن کردن Debug جمع می‌شود. پاک‌کردن تاریخچه روی تنظیمات حساب/پیامک یا Action logهای قبلی اثر ندارد.
- مسیر کد: `src/Actions/Sms/SmsDebugLog.php`، `src/Admin/SettingsPage.php`، `src/Actions/Sms/SmsAction.php`، `src/Security/SmsDraftRecovery.php`، `src/Actions/Sms/FarapayamakProvider.php`، `assets/js/admin-sms-test.js`، `assets/css/admin.css`. تست‌ها: `sms-debug-log.php`، `admin-sms-debug-settings.php`، `admin-settings-tabs.js`.
- محدودیت تست ارسال واقعی **۳ بار در ۱۰ دقیقه** تغییر نکرده است. موفقیت پنل به معنی تحویل به گوشی نیست. نسخه `1.0.28-dev.6` فقط توسعه‌ای است؛ DB/stable بدون تغییر، تست واقعی WordPress/admin و پنل پیامک همچنان pending هستند.

## SMS OTP recovery for forgotten tracking codes (2026-10-22)

- **عمومی و اختیاری به‌ازای هر فرم:** تنظیمات `settings.duplicate.otp_enabled`، `otp_mobile_field` و `otp_provider`. پیش‌فرض سایر فرم‌ها خاموش است؛ Source Definition فرم جهادی `group_mobile` را انتخاب و OTP را فعال می‌کند (Admin override ذخیره‌شده می‌تواند تقدم داشته باشد).
- بازیابی فقط برای ثبت **`draft`، غیرقفل و از همان فرم** که fingerprint آن با ورودی فرم تطابق دارد مجاز است؛ شماره گیرنده **فقط از داده ذخیره‌شده رکورد** خوانده می‌شود و باید با شماره واردشده مطابقت داشته باشد.
- درخواست `afe_otp_start` با nonce فرم، ورودی‌های نرمال‌شده مشابه Submit و شماره واردشده انجام می‌شود. برای فرم/شماره نامعتبر، پاسخ عمومی یکسان است؛ هیچ وضعیت/شناسه/داده ثبت قبلی بازگردانده نمی‌شود.
- کد ۶ رقمی تصادفی با `hash_hmac` و WordPress salt به‌صورت Transient برای حداکثر **۵ دقیقه** ذخیره می‌شود؛ حداکثر **۵ تلاش تأیید** و **۳ درخواست برای فرم/IP و شماره در ۱۵ دقیقه**. بدون کلید واقعی سرویس پیامک، ارسال موفق آزمایش نشده است.
- `afe_otp_verify` پس از تأیید کد، challenge را باطل و یک grant تصادفی ۲۵۶بیتی با `afe_recovery` و عمر **۱ ساعت** صادر می‌کند. این grant فقط برای همان فرم و همان پیش‌نویس اعتبار دارد و در `Renderer::resolveEditing` / `SubmissionService::canApplicantAccess` / `canEdit` بررسی می‌شود؛ هنگام Submit نیز `_afe_recovery` همراه فرم است. کد رهگیری اصلی برای بازیابی افشا نمی‌شود.
- پیامک از `SmsProviderRegistry` با mode `free` و provider پیش‌فرض یا انتخاب‌شده ارسال می‌شود؛ به‌شکل ارسال عادی و بدون نیاز به bodyId. مدیر باید وضعیت ارسال واقعی سرویس/شماره مجاز را بررسی کند.
- **تست اتصال پنل:** بخش «تست اتصال پنل پیامک» در تنظیمات سراسری AFE، ارسال واقعی به شماره تعیین‌شده مدیر از طریق Provider ذخیره‌شده را با endpoint ادمین `afe_sms_test` اجرا می‌کند؛ فقط با capability `afe_manage_settings`، nonce و سقف ۳ تلاش در ۱۰ دقیقه. پاسخ خام یا اطلاعات محرمانه سرویس به کاربر برگردانده نمی‌شود. موفقیت API فقط تأیید پذیرش درخواست است و تحویل به گوشی باید جداگانه کنترل شود. ممکن است هزینه داشته باشد.
- تست‌های `tests/sms-draft-recovery.php` و `tests/sms-recovery-integration.php` و regression‌های قبلی؛ پذیرش زنده هنوز لازم است.

## Duplicate reference and secure inline recovery (2026-10-22)

- **علت UX قبلی:** گزینه `duplicate.behavior=reference` بدون دسترسی معتبر به ثبت قبلی فقط پیام تکراری می‌داد؛ fingerprint به‌تنهایی حق نمایش `edit_url` ندارد و نباید به‌عنوان احراز هویت استفاده شود.
- **اصلاح:** وقتی رفتار `reference` (یا `duplicate.resume_draft` فعال) و دسترسی ورود با کد رهگیری در فرم فعال باشد، روی همان پیام تشخیص تکراری یک فیلد «کد رهگیری ثبت قبلی» و دکمه «تأیید و ورود به ثبت قبلی» نمایش داده می‌شود. در همان `afe_check_duplicate`، اعتبار کد به‌صورت POST با nonce فرم و با تطبیق submission تکراری بررسی می‌شود. نتیجه فقط برای مالک تأیید‌شده شامل `edit_url` است؛ لینک مستقیم برای WordPress account/link/token معتبر همچنان حفظ می‌شود.
- اگر پیوند خصوصی یا حساب کاربری موجود باشد می‌توان از مسیر قبلی استفاده کرد؛ بدون کد رهگیری/لینک خصوصی/حساب مالک **اجازه بازیابی و مشاهده اطلاعات داده نمی‌شود**. هیچ شناسه/وضعیت/محتوای فرم برای کاربر بی‌مجوز افشا نمی‌شود. موارد نهایی و قفل‌شده فقط طبق قواعد اصلی دسترسی/ویرایش قابل مشاهده‌اند.
- کنترل HTML `data-afe-duplicate-tracking` از تنظیمات فرم می‌آید. تلاش‌های بازیابی غیرمتصل به ویرایش مجاز با حد ۱۲ بار در ۱۰ دقیقه در سطح فرم/IP محدود می‌شوند؛ preflight عادی و بررسی‌های هنگام ویرایش معتبر مصرف محدودیت نمی‌کنند.
- پیام submit نیز `behavior` مؤثر را برمی‌گرداند تا همان UI بازیابی در خطای ثبت نهایی ظاهر شود. کش CSS/JS با توسعه `AFE_VERSION=1.0.28-dev.4` باطل می‌شود؛ هیچ مهاجرت DB لازم نیست.
- تست‌ها: `tests/duplicate-draft-resume.php` (دسترسی PHP) و `tests/duplicate-reference-recovery.js` (اجرای JS در sandbox، redirect فقط هم‌مبدأ و فقط با تأیید سرور). اعتبارسنجی زنده WordPress هنوز باز است.

## SMS

Provider داخلی MeliPayamak:
- legacy username/password
- Console/API token
- free text
- pattern/shared
- encrypted secrets

Provider مستقل فراپیامک (`farapayamak`, REST JSON):
- قابل انتخاب به‌عنوان Provider سراسری و per-Action؛ تنظیمات حساب جدا از ملی پیامک در `afe_settings['sms']['farapayamak']`.
- ارسال آزاد: `POST https://rest.payamak-panel.com/api/SendSMS/SendSMS` با JSON `username,password,to,from,text,isflash` (`isflash=false`).
- ارسال خدماتی: `POST https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber` با JSON `username,password,text,to,bodyId`. متن از مقادیر مرتب Pattern با جداکننده `;` ساخته می‌شود؛ قالب جای‌گذاری باید با متن تأییدشده حساب واقعی آزمایش شود.
- کلید API در فیلد `password` JSON ارسال و در WordPress با `SecretStore` رمز می‌شود؛ خروجی خام/خطای HTTP نباید secret را وارد لاگ کند. Endpoint ثابت HTTPS، بدون redirect.
- موفقیت فقط با `RetStatus=1` و `Value` معتبر؛ خطاهای کاربردی حتی با HTTP 200 رد می‌شوند.
- تست‌های `tests/farapayamak-provider.php` و `tests/farapayamak-integration.php` قرارداد و امنیت را بررسی می‌کنند؛ پذیرش ارسال واقعی با خط و الگوی تأیید‌شده هنوز باز است.

Persian WooCommerce SMS adapter:
- credential را داخل AFE کپی نمی‌کند.
- از `PWSMS()->send_sms()` و gateway جاری plugin خارجی استفاده می‌کند.
- free text پشتیبانی می‌شود.
- pattern فقط strategy شناخته‌شده/extension.
- unknown pattern fail-closed.
- external provider unavailable → config حفظ، runtime error واضح، بدون silent fallback.

SMS master setting current/legacy keys را normalize/sync می‌کند و runtime هر action وضعیت فعلی را resolve می‌کند.

Default Actionهای پروژه‌ای متعلق به Source Definition همان integration هستند و در Engine hard-code نمی‌شوند.

## Export

Contract: [ADR-006](../decisions/ADR-006-afe-export-dependency-isolation.md).

PDF:
- tc-lib-pdf 8.73.6
- Unicode/RTL
- HTML/CSS sanitized
- Header/Body/Footer
- A4/A5/Letter
- Portrait/Landscape
- Token Palette
- clickable file links

Excel:
- PhpSpreadsheet 5.9.0
- ZipStream 3.2.2
- real XLSX
- per-form structured profile
- RTL/freeze/autofilter/header style
- visible/title/width/order columns
- identifier cells as text
- file hyperlinks
- multi-form = separate sheet per form

Source Definition export profile default است و Admin override merge می‌شود.

## Admin / Reports / Localization

- list title واقعی form را نمایش می‌دهد.
- labels از resolved schema می‌آیند.
- Repeater readable table است.
- authorized admin inline edit دارد.
- `LocaleDateService` Jalali/Gregorian + WordPress timezone/UTC boundaries را مدیریت می‌کند.
- report/list filters برای `fa_*` Jalali picker دارند.
- per-form capabilities: view/edit/manage/export/reports/configure.

## Geography / Database Tools

Geography importer:
- authenticated Ajax
- client chunks حدود 32 KiB
- complete rows bulk-upsert
- incomplete trailing JSON برای chunk بعد
- retry/idempotent
- progress/chunk/row/state/retry UI

## Brand Mark and Templates

Per-form brand mark:
- default/custom image/hidden
- Media Library picker
- common resolver normal/locked preview
- token `{{brand_mark}}`

Template Registry:
- form/preview/step default templates از code
- Admin فقط override متفاوت را persist می‌کند
- reset override باعث برگشت به future code default می‌شود
- whitelisted tokens؛ remote CSS/resources/raw PHP ممنوع.

## Extension API

برای registration hooks، lifecycle ثبت فرم‌های بیرونی، REST endpoints، DSL examples و provider/validator/template extensions:
[MODULE-AFE-EXTENSION](alavi-form-engine-extension-api.md).

## Build / Deployment

Composer dependencies:
- `phpoffice/phpspreadsheet 5.9.0`
- `maennchen/zipstream-php 3.2.2`
- `tecnickcom/tc-lib-pdf 8.73.6`

`vendor/` Git-tracked نیست. Deployment باید vendor مطابق `composer.lock` بسازد. PDF font metadata نیز باید تولید شود.

Incomplete deployment preflight باید admin notice/log بدهد، نه class-not-found raw fatal.

Runtime browser JS/CSS از CDN load نشود.

## QA / Release Gate

`tools/qa.sh` checks:
- PHP >= 8.3
- regression scripts
- PHP lint
- JS syntax در صورت Node
- composer JSON
- local Jalali assets
- no runtime CDN registration
- version consistency
- optional export vendor/font checks با `AFE_REQUIRE_EXPORT_VENDOR=1`

Historical checkpoint 11.3 evidence:
- 57/57 regression PASS
- 168 PHP lint PASS
- JS syntax PASS
- composer JSON PASS
- no runtime CDN PASS
- extracted ZIP QA/integrity PASS

**این evidence برای checkpoint 11.3 است، نه PASS جدید برای HEAD فعلی.**

Live acceptance قبل از production bump:
1. install/upgrade + DB health
2. Elementor/shortcode
3. draft/submit/tracking/lock/edit request
4. duplicate behaviors + live check
5. action retry/redirect/user/post/PDF actions
6. Jihadi SMS + Persian WooCommerce SMS
7. field/repeater ordering
8. date modes, validators, masks, numeric direction
9. file preview/admin UX
10. XLSX با foreign Composer vendor فعال
11. PDF فارسی/RTL/font
12. export profile overrides/reset/multi-form
13. console/debug.log sanity

تا PASS کامل:
- Plugin = `1.0.28-dev.2`
- DB = `1.0.5-dev.2`
- Stable = `1.0.27`

## Current Unverified Live Priorities

- Excel collision fix checkpoint 11.3 با pluginهایی مثل Pinova
- PDF فارسی پس از font build
- PDF/Excel profile override/reset
- live duplicate روی submission قدیمی

## Package Documentation

برای install/end-user package details:
- `../../wp-content/plugins/alavi-form-engine/README.md`
- `../../wp-content/plugins/alavi-form-engine/readme.txt`
- `../../wp-content/plugins/alavi-form-engine/THIRD-PARTY-NOTICES.md`


## 2026-10-19 — Frontend CSS isolation hardening

- Honeypot inside `.afe-form` is visually clipped at `top:0/right:0` with `left:auto`; the old negative horizontal offset was removed to prevent RTL overflow.
- Enhanced native `select` remains available for validation and `FormData` but has 1px dimensions, clipping and pointer-events disabled. The high-specificity rule is placed after strong-isolation `.afe-control` sizing rules.
- Frontend stylesheet remains registered with `AFE_VERSION`; this development asset revision increments the header and constant to `1.0.28-dev.1` to invalidate browser URL caches. DB version and stable release tag were not promoted.
- GitHub Actions [run 39222851678](https://github.com/mohsenfarhangi/bonyad-alavi-wp/actions/runs/39222851678): CSS contract and PHP lint PASS; Chromium PASS at 360/780/782/1024 for no RTL overflow, 1px hidden native select, user selection and mocked form payload. **This is not a live WordPress submission test.**
- Per-form Additional CSS overrides are stored in WordPress (not tracked in this repository); remove temporary site CSS from the admin editor and purge any site/CDN caches separately.
- Full `tools/qa.sh` still reports baseline failures in `tests/export-profile.php` and `tests/export-package-integration.php` due to stale references to the removed plugin-level Jihadi form; unrelated to the stylesheet fix. CI preserves this failure evidence but does not block the focused CSS job.

## وضعیت توسعه 1.0.28-dev (Feature-complete / Pre-acceptance Checkpoint)

> **نکته ارتقا بین checkpointهای توسعه:** بسته را به‌صورت کامل نصب/جایگزین کنید. کپی‌کردن فقط فایل‌های تغییرکرده می‌تواند پوشه‌ها/کلاس‌های جدید را جا بیندازد و از checkpoint 6.1 به بعد bootstrap این وضعیت را قبل از boot تشخیص می‌دهد.

## اصلاح جهت اعداد در پیش‌نمایش

- مقدار `text-align` از Rule عمومی `.afe-form .afe-control` در حالت Strong Isolation حذف شد تا جهت متن هر کنترل به‌صورت سراسری تحمیل نشود.
- ورودی‌های عددی/شماره‌ای همچنان با marker اختصاصی LTR و چپ‌چین هستند.
- در مرحله پیش‌نمایش، شماره موبایل، تلفن، کد ملی، شبا، تاریخ، مقادیر عددی و سلول‌های عددی Repeater از چپ نمایش داده می‌شوند.
- شماره ردیف‌های Repeater در Preview نیز LTR و چپ‌چین است.
- فایل‌های FileField در Preview به‌صورت لینک قابل مشاهده هستند؛ فایل‌های ذخیره‌شده و فایل تازه انتخاب‌شده در تب جدید باز می‌شوند.


> فیچرهای برنامه‌ریزی‌شده handoff نسخه 1.0.28 در سورس پیاده شده‌اند. طبق تصمیم مدیر پروژه، acceptance عملی WordPress/Elementor پس از پایان افزودن فیچرها انجام می‌شود؛ بنابراین تا آن زمان این بسته Production-tagged نیست. مبنای پایدار `1.0.27`، Stable tag=`1.0.27` و DB checkpoint=`1.0.5-dev.2` باقی مانده‌اند.

وضعیت این checkpoint:

- تست واقعی ملی‌پیامک توسط مدیر پروژه **PASS** گزارش شده است.
- SMS Engine اکنون Provider Router دارد: Provider پیش‌فرض سراسری + Override در هر SMS Action.
- Integration اختیاری `Persian WooCommerce SMS` از Gateway/Credential همان افزونه استفاده می‌کند و هیچ Credential تکراری در AFE ذخیره نمی‌کند؛ capability Pattern براساس Gateway فعال تشخیص داده می‌شود.
- Master Switch پیامک در ساختار current/legacy سازگار است و Runtime قبل از هر ارسال state فعلی تنظیمات را دوباره resolve می‌کند تا ارتقا یا snapshot قدیمی باعث خطای کاذب «غیرفعال است» نشود.
- فرم پروژه‌ای `jihadi-group-registration` از 1.0.28-dev در خود Engine تعریف نمی‌شود و توسط child theme بنیاد علوی از hook `afe_register_forms` ثبت می‌شود؛ slug و قراردادهای ذخیره‌شده آن ثابت مانده‌اند.
- Release hardening روی WordPress User Actions اضافه شده است: هدف‌های Administrator/`manage_options` بدون opt-in سطح `afe_manage_settings` قابل Login/Update/Role/Meta نیستند و metaهای امنیتی capability/session/application-password در Runtime مسدودند.
- Assign Role نقش‌های سفارشی دارای `manage_options` را نیز privileged در نظر می‌گیرد.
- URLهای Token‌دار Action schema هنگام ذخیره Tokenهای `{{...}}` را حفظ می‌کنند و URL نهایی همچنان در Runtime sanitize می‌شود.
- metadata رجیستری `supportsExecutionPolicy=false` اکنون واقعاً در sanitizer/UI enforce می‌شود.
- پوشش Renderer برای هر سه mode تاریخ در هر دو تقویم اضافه شده است.
- `tools/qa.sh` یک preflight یک‌دست برای regression، PHP lint، JS syntax، composer، local assets، no-runtime-CDN و consistency نسخه فراهم می‌کند.
- وضعیت فعلی، release gate و acceptance در `../../../docs/modules/alavi-form-engine.md` نگهداری می‌شود.

موارد باقی‌مانده پیش از bump نهایی فقط acceptance عملی روی WordPress + MySQL/MariaDB + Elementor و سپس release finalization است. بعد از PASS، Plugin به `1.0.28`، DB به `1.0.5` و Stable tag به `1.0.28` تغییر خواهد کرد.

# Alavi Form Engine

موتور فرم Code-first برای وردپرس با معماری قابل توسعه، مدیریت Submission، Workflow، Data Source، Conditional Logic، Repeater و اتصال اختیاری Elementor.

## نیازمندی‌ها

- WordPress 6.4+
- PHP 8.3+
- MySQL/MariaDB سازگار با WordPress
- Elementor اختیاری


### بررسی زنده ثبت تکراری

در فرم‌هایی که «جلوگیری از تکرار» فعال است، AFE دیگر برای تشخیص Duplicate منتظر Submit نهایی نمی‌ماند. به‌محض تکمیل همه فیلدهای انتخاب‌شده برای Fingerprint، Frontend با debounce یک preflight سمت سرور اجرا می‌کند. فقط در صورت پیدا شدن ثبت تکراری پیام به کاربر نمایش داده می‌شود؛ نتیجه غیرتکراری یا خطای موقت preflight هیچ پیام مزاحمی ایجاد نمی‌کند. داده قبل از مقایسه همانند Submit نهایی با schema و Input Mask normalize می‌شود. برای `reference`، لینک ثبت قبلی فقط با احراز دسترسی معتبر بازگردانده می‌شود. هنگام تغییر تنظیمات Duplicate، fingerprint ثبت‌های فعال قبلی بازسازی می‌شود و نصب‌های موجود نیز با signature تنظیمات در اولین بررسی self-heal می‌شوند. بررسی نهایی و race-safe در Submit همچنان مرجع اصلی است.

## نصب

1. فایل ZIP را از بخش افزونه‌های وردپرس نصب کنید.
2. افزونه را فعال کنید.
3. جداول با `dbDelta()` ایجاد/بروزرسانی می‌شوند.
4. از منوی «فرم‌های علوی → دیتابیس» دیتاست کامل تقسیمات کشوری را دریافت کنید.
5. فرم‌های پروژه‌ای را از یک plugin/theme integration با hook `afe_register_forms` ثبت کنید. در سایت بنیاد علوی، child theme فرم `jihadi-group-registration` را ثبت می‌کند و Shortcode آن `[alavi_form id="jihadi-group-registration"]` است.

## ویژگی‌های نسخه 1.0.27

- Fluent DSL برای Form / Step / Field / Repeater
- HTML Block بین فیلدها
- Template سطح Form و Step
- Template Registry مشترک برای Form / Preview / Step با Default HTML واقعی و Tokenهای پویا
- نمایش قالب پیش‌فرض داخل Editor، وضعیت Default/Custom و بازگردانی یک‌کلیکی به Default
- Field Override مدیریتی بدون تغییر Source Definition
- UI چیدمان متمرکز: تب «فیلدها و چیدمان» فقط Drag & Drop را نمایش می‌دهد و Override هر Field با کلیک روی همان Field در `afe-admin-side` باز می‌شود
- Input Mask Registry عمومی برای text/tel با presetهای موبایل، تلفن ثابت، کد ملی، کد پستی، کارت بانکی، شبا و Mask سفارشی
- ورودی‌های شماره‌ای/عددی در Frontend و ویرایش ادمین به‌صورت LTR و چپ‌چین نمایش داده می‌شوند؛ marker صریح `data-afe-ltr="1"` و override قوی‌تر Strong Isolation مانع بازگشت `text-align:right !important` قالب/ایزولیشن می‌شود، بدون تغییر RTL بودن Label و ساختار فرم
- SMS Provider Registry با Provider پیش‌فرض سراسری، Override هر Action و Integration اختیاری Persian WooCommerce SMS بدون کپی Credential
- Conditional Logic با show/hide/required/optional
- Data Source: static, callback, database, posts, taxonomy, users, JSON, REST, geography, custom
- Action Pipeline و Event Dispatcher
- Submission workflow، note، files، audit log
- Draft، tracking code، private edit link و WordPress account editing
- Custom tables و Dedicated Storage اختیاری
- File upload محدودشونده و MIME validation
- اعتبارسنجی کد ملی و IBAN ایران
- Rate Limit، CSRF/Nonce، Honeypot، Sanitization، Escaping و Prepared SQL
- کپچای سمت سرور، Google reCAPTCHA یا بدون کپچا
- مدیریت Capability بر اساس نقش
- REST API اختیاری، شامل ثبت امن با Capability اختصاصی
- Elementor Widget و Dynamic Tag
- گزارش وضعیت‌ها و داده‌های ایندکس‌شده
- نشان هدر مستقل برای هر فرم با حالت پیش‌فرض، تصویر سفارشی یا عدم نمایش

## PDF و Excel

خروجی‌ها در 1.0.28-dev به‌صورت package-based طراحی شده‌اند:

- Excel: `phpoffice/phpspreadsheet` نسخه `5.9.0` و خروجی واقعی `.xlsx`. برای جلوگیری از برخورد Composer بین افزونه‌های WordPress، `maennchen/zipstream-php` روی `3.2.2` قفل شده و Export داخل autoload scope اختصاصی AFE اجرا می‌شود.
- PDF: `tecnickcom/tc-lib-pdf` نسخه `8.73.6` با فونت Unicode/RTL از `tc-lib-pdf-font`.
- هر فرم یک Output Profile پیش‌فرض در Source Definition دارد و Admin فقط Override می‌سازد.
- PDF از HTML/CSS امن + Token Palette استفاده می‌کند؛ PHP، Shortcode، `url()` و `@import` از UI اجرا نمی‌شوند.
- Excel ساختاری است: Sheet name، RTL، Freeze Header، AutoFilter، رنگ Header، ترتیب/عنوان/عرض/نمایش ستون‌ها.
- شماره موبایل، کد ملی، شبا، کارت و سایر شناسه‌ها با `TYPE_STRING` نوشته می‌شوند تا صفر اول حذف نشود.
- Export چندفرمی، برای هر فرم Sheet جدا با قالب همان فرم می‌سازد.
- Output Profile هر فرم از Source Definition ثبت‌شده توسط همان integration resolve می‌شود؛ Engine به فرم پروژه‌ای خاصی وابسته نیست.
- فایل‌ها در خروجی‌ها در صورت وجود URL معتبر به‌صورت hyperlink ارائه می‌شوند.

Dependencyها باید در build نهایی داخل `vendor` نصب شوند. برای ساخت vendor:

```bash
./tools/build-vendor.sh
AFE_REQUIRE_EXPORT_VENDOR=1 ./tools/qa.sh
```

`tc-lib-pdf` برای فارسی به font metadata تولیدشده نیاز دارد. Composer script پروژه اکنون `tools/build-pdf-fonts.sh` را بدون نادیده‌گرفتن خطا اجرا می‌کند. اگر dependencyها قبلاً نصب شده‌اند و فقط فونت آماده نیست، از ریشه افزونه `bash tools/build-pdf-fonts.sh` را اجرا کنید و وجود `vendor/tecnickcom/tc-lib-pdf-font/target/fonts/dejavu/dejavusans.json` را بررسی کنید. خروجی XLSX نیز ابتدا در فایل موقت کامل ساخته و اعتبارسنجی می‌شود و سپس با HTTP headers تمیز stream می‌شود تا خطاهای Writer به‌صورت `ERR_INVALID_RESPONSE` مخفی نشوند. AFE هنگام ساخت XLSX، Composer loader افزونه‌های دیگر را موقتاً از resolution خارج می‌کند و با Reflection تأیید می‌کند که PhpSpreadsheet/ZipStream واقعاً از `alavi-form-engine/vendor` لود شده‌اند؛ اگر کلاس متداخل از قبل توسط افزونه دیگری preload شده باشد، مسیر دقیق آن به‌صورت خطای مدیریتی/log گزارش می‌شود.

## امنیت Template

PHP خام از پنل ادمین اجرا نمی‌شود. مدیر دارای Capability تنظیمات می‌تواند CSS/JavaScript اختصاصی فرم را تغییر دهد؛ بنابراین این Capability فقط باید به نقش‌های قابل اعتماد داده شود.

## توسعه

راهنمای توسعه در `../../../docs/modules/alavi-form-engine-extension-api.md` و معماری repository در `../../../docs/ARCHITECTURE.md` قرار دارد.


## تغییرات 1.0.1

- رفع ناسازگاری constructor ویجت Elementor با lifecycle داخلی Elementor.
- رفع همان الگوی ناسازگار در Dynamic Tag برای جلوگیری از خطای مشابه هنگام instantiate مجدد.


## تغییرات 1.0.5

- Import تقسیمات کشوری از چند منبع با fallback به GitHub API.
- درج گروهی شهرستان و بخش برای جلوگیری از timeout هاست اشتراکی.
- Transaction و rollback در صورت خطای دیتابیس.
- اعتبارسنجی تعداد/ساختار دیتاست قبل از پاکسازی داده قبلی.
- نمایش آخرین خطای Import و منبع آخرین Import موفق.
- امکان Import دستی فایل‌های JSON در هاست‌های بدون دسترسی outbound.

## Select isolation and AFE Custom Select

All `SelectField` instances render with the AFE custom select UI by default. The native `<select>` remains the source of truth for validation and submission, while theme-level Select2 containers are isolated from `.afe-form`.

```php
SelectField::make('province')->custom();       // default
SelectField::make('country')->native();        // browser native UI, still no theme Select2
SelectField::make('organization')->searchable();
SelectField::make('small_list')->searchable(false);
```

The admin field override screen can switch each select between Custom and Native and can control search behavior.


## تغییرات 1.0.11 — Style Isolation

- انتقال Design Tokenهای AFE از `:root` به `.afe-shell` برای جلوگیری از نشت متغیرهای CSS به کل سایت.
- سه حالت ایزوله‌سازی `Strong / Default / Disabled` در تنظیمات افزونه.
- حالت پیش‌فرض نصب‌های فعلی و جدید: `Strong`.
- Scoped reset فقط داخل AFE و دفاع محدود در برابر CSSهای عمومی/`!important` قالب.
- Override ایزوله‌سازی برای هر فرم از پنل مدیریت.
- API کدنویسی `Form::styleIsolation('strong')`.
- استایل‌های Select اختصاصی، Repeater، Wizard، Upload و کنترل‌ها در Strong Mode تقویت شده‌اند.


## تغییرات 1.0.12 — File Upload UX

- Dropzone اختصاصی AFE با نمایش نام، حجم و نوع فایل انتخاب‌شده.
- حذف تکی فایل قبل از ارسال و پشتیبانی Drag & Drop.
- جلوگیری از bubble شدن eventهای file input به handlerهای عمومی قالب که `input.value` را بازنویسی می‌کنند.
- اعتبارسنجی سمت مرورگر برای تعداد، حجم و MIME فایل در Wizard.


## تغییرات 1.0.14 — Existing File Ownership / Replace UX

- فایل‌های موجود با ترکیب `submission_id + field_key` به فیلد خودشان محدود می‌شوند.
- هر فایل موجود قابلیت نگه‌داشتن، حذف/بازگردانی و جایگزینی دارد.
- فیلد تک‌فایلی هنگام انتخاب فایل جدید، فایل قبلی را برای جایگزینی علامت می‌زند.
- هیچ لینک `<a>` برای فایل موجود تولید نمی‌شود تا Lightbox قالب/Elementor دخالت نکند.
- اعتبارسنجی Required و max_files فقط فایل‌های موجود انتخاب‌شده + فایل‌های جدید را می‌شمارد.

## تغییرات 1.0.19 — تاریخ محلی و دسترسی سطح فرم

- تاریخ‌های AFE از locale سایت وردپرس پیروی می‌کنند. برای localeهای `fa_*` نمایش تاریخ مدیریتی جلالی است و برای سایر زبان‌ها از تقویم میلادی و `wp_date()` استفاده می‌شود.
- فیلتر تاریخ «اطلاعات ارسالی» و «گزارش و آمار» در سایت فارسی با JalaliDatePicker نمایش داده می‌شود؛ بازه انتخابی قبل از Query به مرز UTC متناظر با timezone وردپرس تبدیل می‌شود.
- تاریخ فیلدهای Date در مشاهده، ویرایش مدیریتی، Print/PDF و Excel بین calendar ذخیره‌شده فیلد و calendar سایت تبدیل می‌شود.
- برای هر فرم Capabilityهای مستقل `view`, `edit`, `manage`, `export`, `reports`, `configure` ایجاد می‌شوند.
- فرم‌های موجود در اولین migration دسترسی legacy را حفظ می‌کنند؛ فرم‌های جدید بعدی برای نقش‌های غیر Administrator به‌صورت پیش‌فرض بسته هستند تا مدیر صراحتاً دسترسی بدهد.


## تغییرات 1.0.20 — Preview و قفل ثبت

- مرحله سیستمی و اختیاری پیش‌نمایش قبل از ثبت نهایی.
- قالب Preview قابل ویرایش با `{{preview_fields}}` و `{{field:field_name}}`.
- قفل خودکار Submission بعد از ثبت نهایی.
- نمایش فقط Preview برای ثبت قفل‌شده در فرم‌های دارای Preview.
- حالت Readonly بدون Save/Submit برای فرم‌های قفل‌شده بدون Preview.
- دکمه درخواست ویرایش قابل فعال/غیرفعال‌سازی برای هر فرم.
- تأیید/رد درخواست و باز/بسته‌کردن دستی قفل در مدیریت Submission.
- Preview و Lock Policy قابلیت‌های عمومی Engine هستند؛ child theme بنیاد علوی این قابلیت‌ها را برای فرم جهادی فعال می‌کند.


## تغییرات 1.0.21 — زباله‌دان، قفل و کپچا

- انتقال Submission به زباله‌دان، بازیابی و حذف دائمی امن.
- حذف دائمی داده‌های وابسته، فایل‌های مدیریت‌شده AFE و رکورد Dedicated Storage.
- اصلاح نمایش فرم بلافاصله بعد از قفل شدن؛ کاربر به نمای قفل‌شده هدایت می‌شود.
- نمایش درست درخواست ویرایش در نمای قفل‌شده.
- دکمه تولید کپچای جدید برای کپچای داخلی.


## تغییرات 1.0.22 — دسترسی منوی ادمین

- اصلاح نمایش منوی «اطلاعات ارسالی» و «گزارش‌ها» برای نقش‌هایی که فقط روی فرم مشخص دسترسی دارند.
- Capabilityهای سراسری لازم برای ورود به صفحات مدیریت اکنون به‌عنوان gateway به‌صورت خودکار از روی Capabilityهای فرم همگام می‌شوند.
- این همگام‌سازی دسترسی به فرم‌های دیگر ایجاد نمی‌کند؛ فیلتر فرم‌به‌فرم همچنان در `FormAccess` اعمال می‌شود.

## تغییرات 1.0.23 — دارایی‌های لوکال، اصلاحات تقویم و فرم جهادی

- تمام JavaScript/CSSهای مرورگری AFE به مسیرهای لوکال افزونه منتقل شدند و وابستگی Runtime به CDN برای JalaliDatePicker حذف شد.
- JalaliDatePicker به‌صورت pinned و self-hosted داخل `assets/vendor/jalalidatepicker/` بسته قرار دارد؛ Runtime هیچ وابستگی CDN ندارد.
- Google reCAPTCHA خارجی از Runtime فرم حذف شد؛ در تنظیمات قدیمی Google، کپچای داخلی Server-side AFE به‌عنوان fallback استفاده می‌شود.
- موقعیت JalaliDatePicker در دسکتاپ بر اساس مختصات واقعی input بازتنظیم می‌شود و هنگام `scroll` و `resize` دوباره همگام می‌شود تا داخل Elementor/قالب با فاصله از فیلد نمایش داده نشود.
- محدودیت `overflow` کادر اصلی فرم برای Dropdownها و کنترل‌های بازشونده اصلاح شد تا محتوای مرحله ۴ بریده نشود.
- عنوان فرم جهادی به «شناسنامه گروه‌های مردمی و جهادی | طرح جهادگر شهید رسول عالم باقری» و توضیح آن به «برای همکاری با بنیاد علوی در محرومیت‌زدایی» تغییر کرد.
- متن شعار فرم جهادی در Header فرم اضافه/به‌روزرسانی شد.
- تلفن همراه گروه دقیقاً ۱۱ رقم است، فقط عدد می‌پذیرد و با پیشوند ثابت `09` کار می‌کند؛ Validation سمت سرور نیز همین قرارداد را کنترل می‌کند.
- شماره شبای حقوقی اختیاری شد؛ کاربر فقط ۲۴ رقم وارد می‌کند و `IR` صرفاً به‌صورت Prefix نمایشی نشان داده می‌شود. ورودی‌های قدیمی دارای `IR` نیز هنگام نمایش/ویرایش normalize می‌شوند.
- کد ملی مسئول و جانشین فقط ۱۰ رقم عددی می‌پذیرد و اعتبارسنجی checksum قبلی حفظ شده است.
- عنوان «اعضای شورای مرکزی» به «اعضای شورای مرکزی (هسته اصلی)» تغییر کرد.
- «مناطق تحت پوشش» در مرحله ۶ به Repeater تبدیل شد تا چند نقطه جغرافیایی ثبت شود؛ استان الزامی و شهرستان/بخش اختیاری هستند و dependency استان → شهرستان → بخش در هر ردیف مستقل عمل می‌کند.
- داده‌های قدیمی `target_province`, `target_county`, `target_district`, `target_area_type`, `target_area_detail` به اولین ردیف Repeater جدید نگاشت می‌شوند تا سازگاری Submissionهای قبلی حفظ شود.
- پیام‌های Validation مرورگر برای موبایل، کد ملی و شبا دقیق‌تر و فارسی شدند و همان محدودیت‌ها در سمت PHP نیز enforce می‌شوند.

### JalaliDatePicker لوکال

نسخه pinned فایل‌های `dist` همراه بسته افزونه در مسیرهای زیر قرار دارد:

```text
wp-content/plugins/alavi-form-engine/assets/vendor/jalalidatepicker/jalalidatepicker.min.js
wp-content/plugins/alavi-form-engine/assets/vendor/jalalidatepicker/jalalidatepicker.min.css
```

AFE هیچ‌کدام از این دو فایل را از CDN در Runtime درخواست نمی‌کند. اگر deployment ناقص باشد و فایل‌ها حذف شده باشند، پنل مدیریت هشدار می‌دهد.

## تغییرات 1.0.24 — نشان اختصاصی هر فرم

- برای `afe-brand-mark` تنظیم مستقل به‌ازای هر فرم اضافه شد.
- سه حالت در مدیریت فرم در دسترس است: «نشان پیش‌فرض AFE»، «تصویر سفارشی» و «عدم نمایش».
- در حالت تصویر سفارشی، تصویر مستقیماً از Media Library وردپرس انتخاب می‌شود و شناسه/URL آن در تنظیمات همان فرم ذخیره می‌شود.
- متن جایگزین (`alt`) برای تصویر نشان قابل تنظیم است و تصویر با `object-fit: contain` داخل فضای نشان نمایش داده می‌شود.
- حالت «عدم نمایش» هیچ عنصر یا فضای خالی برای `afe-brand-mark` تولید نمی‌کند.
- اگر تصویر سفارشی حذف یا نامعتبر شود، Renderer به نشان پیش‌فرض AFE برمی‌گردد تا Header شکسته نشود.
- Header نمای قفل‌شده Submission نیز همان تنظیم نشان فرم را رعایت می‌کند.
- توکن `{{brand_mark}}` به قالب HTML سفارشی سطح فرم اضافه شد؛ بنابراین قالب‌های سفارشی نیز می‌توانند نشان resolved همان فرم را در محل دلخواه قرار دهند.
- API توسعه‌دهنده اضافه شد:

```php
Form::make('my-form')->brandMark('default');
Form::make('my-form')->brandMarkImage('https://example.test/logo.png', 'نشان فرم');
Form::make('my-form')->hideBrandMark();
```

- `README.md`، `readme.txt` و مستندات canonical پروژه هم‌زمان با نسخه به‌روزرسانی شدند.


## تغییرات 1.0.25 — بازشدن فوری تقویم جلالی

- مشکل نمایش JalaliDatePicker فقط بعد از یک Scroll کوچک در فرم برطرف شد.
- AFE دیگر برای بازشدن تقویم به ترتیب اجرای listener داخلی `autoShow` کتابخانه وابسته نیست و با API رسمی `jalaliDatepicker.show(input)` تقویم را همان لحظه روی `focus/click` باز می‌کند.
- `autoShow` داخلی کتابخانه برای فیلدهای فرم AFE غیرفعال شد تا race condition بین بازشدن کتابخانه و محاسبه موقعیت AFE ایجاد نشود.
- موقعیت تقویم تا ۳۰ frame کوتاه بررسی می‌شود تا اگر `<jdp-container>` کمی دیرتر ساخته/نمایش داده شد، بدون نیاز به Scroll در اولین فرصت کنار همان فیلد قرار بگیرد.
- برای محاسبه ابعاد تقویم از `offsetWidth/offsetHeight` استفاده می‌شود تا animation اولیه `scale()` باعث محاسبه اشتباه مختصات نشود.
- Scroll همچنان فقط برای reposition کردن تقویم باز استفاده می‌شود و دیگر trigger لازم برای ظاهرشدن آن نیست.
- `README.md`، `readme.txt` و مستندات پروژه هم‌زمان با نسخه 1.0.25 به‌روزرسانی شدند.


## تغییرات 1.0.26 — بازگشت تقویم جلالی به رفتار استاندارد

- تمام منطق سفارشی بازکردن و جایگذاری `JalaliDatePicker` در فرم حذف شد.
- فراخوانی دستی `jalaliDatepicker.show()` حذف شد.
- محاسبه دستی `top/left`، retry با `requestAnimationFrame` و listenerهای اختصاصی `scroll/resize` حذف شدند.
- CSS اختصاصی برای `jdp-container` و `jdp-overlay` که در تغییرات جایگذاری اضافه شده بود حذف شد.
- فیلد جلالی اکنون مانند نسخه‌های پایدار اولیه فقط با `data-jdp` و `jalaliDatepicker.startWatch()` کار می‌کند و بازشدن/جایگذاری را کاملاً به خود کتابخانه می‌سپارد.
- `README.md` و `readme.txt` هم‌زمان با نسخه 1.0.26 به‌روزرسانی شدند.

## تغییرات 1.0.27 — Template Registry و ویرایش Default HTML

- یک لایه مشترک `TemplateDefinition`، `TemplateRegistry` و `TemplateResolver` اضافه شد تا Renderer و پنل مدیریت از یک منبع واحد برای قالب‌های پیش‌فرض استفاده کنند.
- Editor قالب کل فرم، قالب Preview و قالب هر Step دیگر خالی نمایش داده نمی‌شود؛ HTML پیش‌فرض واقعی همان بخش همراه با Tokenهای داینامیک نمایش داده می‌شود.
- قالب پیش‌فرض کل فرم شامل `{{brand_mark}}`، `{{title}}`، `{{description}}`، `{{slogan}}`، `{{progress}}` و `{{steps}}` است.
- Preview از Template تعریف‌شده در کد فرم به‌عنوان Default استفاده می‌کند و در نبود آن، Template استاندارد AFE نمایش داده می‌شود.
- Step نیز Template تعریف‌شده در کد را به‌عنوان Default می‌گیرد و در حالت استاندارد `{{items}}` را نشان می‌دهد.
- برای تمام Template Editorها دکمه «بازگردانی به قالب پیش‌فرض» اضافه شد و وضعیت «قالب پیش‌فرض / قالب سفارشی» به‌صورت زنده نمایش داده می‌شود.
- اگر کاربر Default را بدون تغییر ذخیره کند، Override جدیدی در دیتابیس ایجاد نمی‌شود. اگر قالب سفارشی را Reset و ذخیره کند، Override پاک می‌شود و از آن پس تغییرات Default کد در نسخه‌های بعدی خودکار اعمال خواهند شد.
- یک Normalizer یک‌باره برای 1.0.27 اضافه شد تا Overrideهای قدیمی که صرفاً کپی دقیق Default فعلی هستند پاک شوند و به‌اشتباه Defaultهای آینده را Freeze نکنند.
- Token جدید `{{progress}}` به قالب کل فرم اضافه شد تا Source نمایش‌داده‌شده در Editor دقیقاً ساختار استاندارد AFE را بازنمایی کند.
- Hook جدید `afe_register_templates` برای ثبت Template Definitionهای آینده اضافه شد.
- `README.md`، `readme.txt` و مستندات توسعه هم‌زمان با نسخه 1.0.27 به‌روزرسانی شدند.


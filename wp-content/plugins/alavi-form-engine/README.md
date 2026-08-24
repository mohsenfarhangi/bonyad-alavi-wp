# Alavi Form Engine

موتور فرم Code-first برای وردپرس با معماری قابل توسعه، مدیریت Submission، Workflow، Data Source، Conditional Logic، Repeater و اتصال اختیاری Elementor.

## نیازمندی‌ها

- WordPress 6.4+
- PHP 8.3+
- MySQL/MariaDB سازگار با WordPress
- Elementor اختیاری

## نصب

1. فایل ZIP را از بخش افزونه‌های وردپرس نصب کنید.
2. افزونه را فعال کنید.
3. جداول با `dbDelta()` ایجاد/بروزرسانی می‌شوند.
4. از منوی «فرم‌های علوی → دیتابیس» دیتاست کامل تقسیمات کشوری را دریافت کنید.
5. فرم پیش‌فرض با Shortcode زیر قابل نمایش است:

`[alavi_form id="jihadi-group-registration"]`

## ویژگی‌های نسخه 1.0.20

- Fluent DSL برای Form / Step / Field / Repeater
- HTML Block بین فیلدها
- Template سطح Form و Step
- Field Override مدیریتی بدون تغییر Source Definition
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

## PDF و Excel

Excel بدون dependency خارجی با SpreadsheetML خروجی داده می‌شود. دکمه PDF/چاپ همیشه صفحه چاپ‌بهینه باز می‌کند و مرورگر می‌تواند آن را Save as PDF کند. اگر کلاس `Dompdf\Dompdf` در autoload پروژه در دسترس باشد، خروجی مستقیم PDF نیز به‌صورت خودکار استفاده می‌شود؛ `dompdf/dompdf` در این ZIP اجباری/باندل نشده است.

## امنیت Template

PHP خام از پنل ادمین اجرا نمی‌شود. مدیر دارای Capability تنظیمات می‌تواند CSS/JavaScript اختصاصی فرم را تغییر دهد؛ بنابراین این Capability فقط باید به نقش‌های قابل اعتماد داده شود.

## توسعه

راهنمای کامل در `docs/DEVELOPER.md` و معماری در `docs/ARCHITECTURE.md` قرار دارد.


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
- فرم ثبت‌نام گروه‌های مردمی و جهادی با Preview و Lock Policy فعال.


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

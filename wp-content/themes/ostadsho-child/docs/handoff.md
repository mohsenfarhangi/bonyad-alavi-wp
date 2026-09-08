# Handoff اصلی قالب فرزند «استادشو»

این سند مرجع اصلی توسعه و تحویل تغییرات قالب `ostadsho-child` است. جزئیات هر مرحله در فایل‌های نسخه‌بندی‌شده داخل مسیر `docs/versions/` ثبت می‌شود.

## هدف

توسعه‌ی قالب فرزند بنیاد علوی با ساختاری قابل نگهداری، قابل توسعه و کم‌ریسک؛ به‌گونه‌ای که تغییرات رابط کاربری با سایر بخش‌های وردپرس/المنتور/ووکامرس تداخل نداشته باشد و منطق PHP بر پایه‌ی اصول شی‌گرایی، SOLID و الگوهای طراحی مناسب سازمان‌دهی شود.

## قوانین ثابت توسعه

### ۱. رابط کاربری و CSS

- تمام UIهای جدید با استاندارد **BEM** نام‌گذاری می‌شوند.
- هر قابلیت یا صفحه باید Block اختصاصی و namespace مشخص داشته باشد؛ ترجیحاً با پیشوند `ba-`.
- از selectorهای عمومی و پرریسک مانند `div`, `h2`, `.button` یا override سراسری بدون scope خودداری می‌شود.
- Modifierها با الگوی `block--modifier` و Elementها با الگوی `block__element` نوشته می‌شوند.
- استایل وابسته به افزونه‌ها تا حد ممکن زیر Block اختصاصی همان قابلیت scope می‌شود.

نمونه:

```css
.ba-participation-card {}
.ba-participation-card__title {}
.ba-participation-card__action {}
.ba-participation-card--featured {}
```

### ۲. معماری PHP

- منطق‌های جدید قالب به‌صورت **OOP** پیاده‌سازی می‌شوند.
- اصول **SOLID** در طراحی کلاس‌ها رعایت می‌شود؛ به‌خصوص Single Responsibility و Dependency Inversion در بخش‌هایی که وابستگی خارجی دارند.
- در صورت وجود مسئله‌ی تکرارشونده، از Design Pattern مناسب مانند Service، Factory، Strategy، Repository یا Adapter استفاده می‌شود؛ Pattern بدون نیاز واقعی تحمیل نمی‌شود.
- فایل `functions.php` تا حد امکان نقش Bootstrap/Composition Root را دارد و منطق اصلی قابلیت‌ها به کلاس‌ها یا سرویس‌های مستقل منتقل می‌شود.

### ۳. جلوگیری از کد تکراری

- منطق مشترک در Helper یا Service قابل استفاده‌ی مجدد قرار می‌گیرد.
- قبل از اضافه‌کردن تابع جدید، قابلیت‌های موجود بررسی می‌شوند تا منطق مشابه دوباره نوشته نشود.
- Helperها باید مسئولیت مشخص داشته باشند و به محل نگهداری کدهای نامرتبط تبدیل نشوند.

### ۴. مستندسازی فارسی کد

- تمام کلاس‌ها، متدها و توابع جدید دارای توضیح فارسی هستند.
- برای PHP از DocBlock/PHPDoc استفاده می‌شود.
- برای بخش‌های پیچیده‌ی JavaScript نیز توضیح فارسی کوتاه در محل مناسب اضافه می‌شود.
- کامنت باید «چرایی» یا قرارداد کد را توضیح دهد و از توضیح بدیهیات پرهیز شود.

### ۵. Handoff و نسخه‌بندی

- این فایل (`docs/handoff.md`) نمای کلی، استانداردها و فهرست نسخه‌ها را نگه می‌دارد.
- جزئیات هر مرحله در `docs/versions/vX.Y.Z.md` ثبت می‌شود.
- نسخه‌بندی بر اساس Semantic Versioning انجام می‌شود:
  - `PATCH`: اصلاح داخلی، رفع باگ یا تغییر کوچک بدون تغییر قرارداد اصلی.
  - `MINOR`: قابلیت جدید سازگار با ساختار موجود.
  - `MAJOR`: تغییر معماری یا قرارداد ناسازگار با نسخه قبل.
- هر فایل نسخه شامل هدف، فایل‌های تغییرکرده، تصمیمات فنی، تست‌ها، نکات مهاجرت و متن Commit است.

## سیاست تحویل

در هر مرحله دو خروجی قابل ساخت است:

1. **ZIP کامل قالب**: شامل کل قالب و تمام تغییرات انجام‌شده تا همان نسخه.
2. **Patch ZIP**: شامل فقط فایل‌های جدید/تغییرکرده‌ی همان مرحله، با حفظ مسیر اصلی فایل‌ها و یک Manifest برای اعمال Patch.

Patch نباید تنها محل نگهداری تغییرات باشد؛ نسخه‌ی کامل قالب نیز باید تمام تغییرات را در خود داشته باشد.


## معماری رسانه محصولات

از نسخه `0.2.0` خواندن تصویر شاخص و Product Gallery ووکامرس از طریق `BA_Product_Media_Service` انجام می‌شود. ویجت‌ها نباید مستقیماً منطق ترکیب تصویر شاخص و Gallery را دوباره پیاده‌سازی کنند.

رندر HTML تصاویر و آیکون‌های رسانه مشترک در `BA_Media_Helper` قرار دارد. رفتار کاروسل نیز از `assets/js/ba-product-carousel-core.js` استفاده می‌کند تا منطق حرکت اسلاید بین ویجت‌های مختلف تکرار نشود.

برای UI مستقل تصویر محصول در Loop، Block رزروشده `ba-product-gallery` است و تمام Elementها/Modifierهای آن باید زیر همین Block باقی بمانند.


## کامپوننت عمومی Repeater مدیریت

از نسخه `v0.3.1` تمام Repeaterهای سفارشی در `wp-admin` باید از `BA_Admin_Repeater_Component` استفاده کنند. این Component مدیریت Template، Index، افزودن، حذف، جابه‌جایی، Empty State و Assetهای مشترک را متمرکز می‌کند.

مسیر کلاس:

`inc/admin/components/class-ba-admin-repeater-component.php`

Block عمومی BEM:

`ba-admin-repeater`

قواعد ثابت:

- برای Add/Remove/Move یک Repeater سفارشی نباید JavaScript جداگانه در Feature نوشته شود.
- فیلدهای داخل ردیف باید BEM همان Feature را حفظ کنند.
- Sanitize و ذخیره‌سازی مسئولیت Component نیست و باید در Service/Handler قابلیت انجام شود.
- نام فیلدهای تو در تو ترجیحاً با `BA_Admin_Repeater_Component::field_name()` ساخته شود.
- این Component مخصوص UI سفارشی مدیریت است؛ Repeaterهای Elementor همچنان باید از `Elementor\Repeater` استفاده کنند.

راهنمای کامل:

[راهنمای کامپوننت Repeater مدیریت](components/admin-repeater.md)

## وضعیت اولیه‌ی کد

در زمان ایجاد این Handoff، قالب از قبل شامل بخش‌های مشارکت مردمی، فرم‌ها، ووکامرس، تنظیمات مدیریتی و ویجت/داینامیک‌تگ‌های المنتور است.

در نسخه `v0.1.0` یک enqueue تکراری برای استایل Checkout در `functions.php` شناسایی و مستند شد. این duplication در `v0.3.0` بدون تغییر قرارداد Checkout حذف شد. بخشی از کد Legacy قالب همچنان procedural است و فقط در زمان نیاز قابلیت یا Refactor مستقل بازآرایی می‌شود.

## تاریخچه نسخه‌ها

- [v0.1.0 — ایجاد استاندارد توسعه و Handoff اولیه](versions/v0.1.0.md)
- [v0.2.0 — کاروسل تصاویر محصول و ویجت Loop](versions/v0.2.0.md)
- [v0.2.1 — بهینه‌سازی Lazy Load کاروسل تصویر محصول](versions/v0.2.1.md)
- [v0.3.0 — ویجت و تنظیمات مرکز حرکت‌های مردمی و جهادی](versions/v0.3.0.md)
- [v0.3.1 — کامپوننت عمومی Repeater مدیریت](versions/v0.3.1.md)
- [v0.4.0 — تنظیمات دو منبع و کنترل نمایش سکشن‌های مرکز](versions/v0.4.0.md)
- [v0.4.1 — اصلاح لینک و آیکون کارت‌های سامانه](versions/v0.4.1.md)
- [v0.5.0 — ذخیره AJAX و نوار شناور تنظیمات بنیاد علوی](versions/v0.5.0.md)
- [v0.5.1 — انتخاب دوحالته رسانه کارت‌های سامانه](versions/v0.5.1.md)
- [v0.5.2 — اصلاح Hover آیکون و چیدمان معرفی مرکز](versions/v0.5.2.md)
- [v0.5.3 — Lazy Rendering و Fade-in سکشن‌های مرکز](versions/v0.5.3.md)
- [v0.5.4 — اصلاح بارگذاری Asset تب‌های تنظیمات بر اساس دسترسی کاربر](versions/v0.5.4.md)
- [v0.5.5 — کنترل‌های ریسپانسیو آمار و Hero](versions/v0.5.5.md)
- [v0.5.6 — اصلاح ابعاد تصویر Hero برای عملکرد صحیح Object Fit](versions/v0.5.6.md)
- [v0.5.7 — کنترل ریسپانسیو عرض و ارتفاع تصویر Hero](versions/v0.5.7.md)
- [v0.5.8 — نسبت تصویر ریسپانسیو اخبار و چندرسانه‌ای](versions/v0.5.8.md)
- [v0.5.9 — کنترل استایل عنوان و تاریخ اخبار و چندرسانه‌ای](versions/v0.5.9.md)
- [v0.6.0 — پرداخت سریع Inline در صفحه مشارکت مردمی](versions/v0.6.0.md)
- [v0.6.1 — اصلاح ذخیره درگاه پرداخت مشارکت](versions/v0.6.1.md)
- [v0.6.2 — حفظ شناسه Case-sensitive درگاه‌های پرداخت](versions/v0.6.2.md)
- [v0.6.3 — مقاوم‌سازی درگاه در نصب‌های ترکیبی Patch](versions/v0.6.3.md)
- [v0.6.4 — محدودسازی Quick Checkout به صورتحساب و یکسان‌سازی عرض فیلدها](versions/v0.6.4.md)

## معماری ذخیره تنظیمات بنیاد علوی

از نسخه `v0.5.0` ذخیره تب‌های صفحه «تنظیمات بنیاد علوی» از یک مسیر AJAX مشترک انجام می‌شود. کنترلر این جریان `BA_Settings_Ajax_Controller` است و تب‌ها نباید JavaScript یا Endpoint AJAX مستقل برای ذخیره تنظیمات عمومی خود بسازند.

قرارداد ثابت:

- تب استاندارد دارای `option_name` و `option_group` به‌صورت خودکار با Settings API و همان Sanitize Callback ثبت‌شده ذخیره می‌شود.
- تب دارای منطق Persist اختصاصی باید `ajax_save_callback` را در Registry تعریف کند و Callback فقط `true` یا `WP_Error` برگرداند.
- Capability هر تب قبل از Persist توسط کنترلر مشترک بررسی می‌شود.
- فرم‌ها باید مسیر کلاسیک `options.php` یا `admin-post.php` را برای Progressive Enhancement حفظ کنند.
- UI ذخیره فقط از Block عمومی `ba-settings-savebar` استفاده می‌کند؛ Featureها نباید دکمه ذخیره جدا در انتهای فرم ایجاد کنند.
- متن دکمه هر تب در صورت نیاز از `save_label` تأمین می‌شود.
- Repeater، Media Picker و Editor باید تغییر خود را به فرم منتقل کنند تا dirty-state نوار ذخیره دقیق بماند.

مدیریت دسترسی Roleها از `BA_Settings_Access_Service` استفاده می‌کند تا منطق اعمال Capability بین AJAX و fallback کلاسیک تکرار نشود.

### قرارداد Asset تب‌های تنظیمات

از نسخه `v0.5.4` Assetهای اختصاصی هر تب نباید با بررسی مستقیم `$_GET['tab']` بارگذاری شوند. تب باید در Registry کلید اختیاری `assets_callback` تعریف کند. `BA_Settings_Page` ابتدا تب‌های قابل دسترس کاربر و تب فعال واقعی را Resolve می‌کند و سپس Callback همان تب را در `admin_enqueue_scripts` اجرا می‌کند.

قواعد ثابت:

- Feature نباید برای تشخیص تب فعال مستقیماً به وجود پارامتر `tab` در URL وابسته باشد.
- حالت ورود به `admin.php?page=ba-settings` بدون `tab` باید دقیقاً مانند تب Resolve‌شده Assetهای صحیح را دریافت کند.
- کاربران با Capability محدود باید همان CSS/JS تب قابل دسترس خود را دریافت کنند.
- Assetهای Component مانند `BA_Admin_Repeater_Component` باید از `assets_callback` Feature مصرف‌کننده enqueue شوند.
- متاباکس‌های مستقل از صفحه تنظیمات، مانند FAQ محصول، همچنان می‌توانند بر اساس `get_current_screen()` Asset Component را enqueue کنند.

جزئیات کامل این قرارداد در [مستند نسخه v0.5.4](versions/v0.5.4.md) ثبت شده است.

جزئیات ذخیره AJAX در [مستند نسخه v0.5.0](versions/v0.5.0.md) ثبت شده است.


## معماری پرداخت سریع مشارکت مردمی

از نسخه `v0.6.0` ویجت `bonyad_alavi_participation` برای پرداخت اصلی از Cart واقعی کاربر استفاده نمی‌کند. کلیک اول فقط Product/Amount را اعتبارسنجی و فاکتور + Checkout Fields را به‌صورت Inline آماده می‌کند؛ کلیک دوم Order مستقل همان پروژه را ایجاد و Gateway انتخاب‌شده را اجرا می‌کند.

قرارداد ثابت:

- درگاه فعال از `ba_participation_settings[payment_gateway]` خوانده می‌شود و هیچ fallback خودکاری به Gateway دیگر وجود ندارد.
- فهرست Gateway در داشبورد فقط شامل Gatewayهای `enabled` WooCommerce است؛ تنظیم Merchant/API همچنان متعلق به WooCommerce است.
- Sanitize شناسه Gateway هنگام ذخیره نباید به Runtime Gateway Registry درخواست AJAX وابسته باشد و **نباید case شناسه را تغییر دهد**. شناسه‌هایی مانند `WC_Sep_Payment_Gateway` باید دقیقاً با همان حروف بزرگ/کوچک ذخیره شوند؛ استفاده از `sanitize_key()` برای Persist شناسه Gateway ممنوع است. فعال/موجود بودن Gateway هنگام اجرای پرداخت با `get_selected_gateway()` و `validate_gateway_availability()` کنترل می‌شود.
- برای سازگاری با داده‌های `v0.6.1`، مقدار lowercase ذخیره‌شده باید به شناسه واقعی `$gateway->id` نگاشت شود. UI تنظیمات نباید بدون `method_exists()` به Resolver یک Service نسخه‌پذیر وابسته باشد؛ `ba_resolve_participation_gateway_id_for_settings()` مسیر Compatibility مدیریت است.
- Sanitize شناسه Gateway در تب مشارکت از `ba_sanitize_participation_gateway_id()` عبور می‌کند و عمداً مستقل از Service است تا نصب ترکیبی Patch یا OPcache قدیمی باعث lowercase شدن دوباره شناسه نشود.
- Quick Checkout برای یافتن درگاه انتخاب‌شده از Resolver سازگار داخلی خود استفاده می‌کند تا حتی در صورت اجرای نسخه قدیمی Service، شناسه Case-sensitive به درگاه واقعی تطبیق داده شود.
- خواندن فیلدهای Checkout فقط از `WC_Checkout::get_checkout_fields()` و رندر آن‌ها از `woocommerce_form_field()` انجام می‌شود؛ Feature نباید Schema موازی بسازد. از نسخه `v0.6.4` Quick Checkout فقط گروه `billing` را نمایش و اعتبارسنجی می‌کند و گروه‌های `shipping`، `account` و `order` در فرم Inline رندر نمی‌شوند.
- تمام `.form-row`های Quick Checkout باید داخل Scope فرم `bap__donation-form` با `width: 100% !important` و `max-width: 100% !important` رندر شوند تا Float/Grid پیش‌فرض WooCommerce عرض فیلدها را محدود نکند؛ این Force Override نباید به فرم‌های دیگر سایت نشت کند.
- Product/Amount در مرحله Prepare و Process هر دو باید با `Bonyad_Alavi_WooCommerce_Participation::validate_project_amount()` اعتبارسنجی شوند.
- Order پرداخت سریع فقط یک line item از پروژه جاری دارد و Metaهای `_bap_participation_amount` و `_bap_goal_amount` را نگه می‌دارد.
- سایر اقلام Cart نباید وارد Order شوند و نباید توسط Gateway حذف شوند. اجرای `is_available()` و `process_payment()` در Cart موقت از `BA_Participation_Payment_Gateway_Service::with_isolated_cart()` عبور می‌کند و Session/Persistent Cart واقعی snapshot/restore می‌شود.
- Gatewayهای دارای Payment Fields داخلی (`has_fields()`) در Quick Checkout پشتیبانی نمی‌شوند؛ مسیر هدف Hosted/Redirect Gateway است.
- Terms/Privacy و Hookهای Checkout Validation ووکامرس باید در مسیر Inline حفظ شوند.
- UI مرحله دوم زیر Block `bap` و Elementهای `bap__quick-*` باقی می‌ماند و هیچ selector عمومی جدیدی برای Checkout ساخته نمی‌شود.

جزئیات در [مستند نسخه v0.6.0](versions/v0.6.0.md) ثبت شده است.

## معماری صفحه مرکز حرکت‌های مردمی و جهادی

از نسخه `v0.3.0` صفحه مرکز با ویجت `bonyad_alavi_jihadi_center` مدیریت می‌شود. Block فرانت این قابلیت `ba-jihadi-center` و Block داشبورد آن `ba-center-settings` است.

محتوای ثابت و سازمانی در `BA_Center_Settings_Service` متمرکز شده و تب داشبورد توسط `BA_Center_Settings_Tab` مدیریت می‌شود. Query اخبار و چندرسانه‌ای باید از `BA_Content_Query_Service` عبور کند و نباید آرگومان‌های مشترک `WP_Query` در ویجت یا قابلیت دیگری دوباره‌نویسی شوند.

قرارداد اولویت داده از نسخه `v0.4.0`:

- تمام تنظیمات محتوایی و داده‌ای Hero، Stats، Intro/System Cards، News، Media، Partners و FAQ هم در داشبورد و هم در Elementor وجود دارند.
- تنظیمات مؤثر فقط از طریق `BA_Center_Settings_Service::get_effective_settings()` ساخته می‌شوند.
- پس از ذخیره تب مرکز با Schema جدید، مقدار ذخیره‌شده داشبورد بر مقدار متناظر Elementor اولویت دارد؛ مقدار خالی نیز یک انتخاب معتبر است.
- Hero Background استثنای رسانه‌ای است: تصویر معتبر داشبورد ← تصویر Elementor ← فایل پیش‌فرض قالب.
- Query اخبار و چندرسانه‌ای در هر دو منبع قابل تنظیم است، ولی ساخت `WP_Query` همیشه باید فقط از `BA_Content_Query_Service` عبور کند.
- هر هفت سکشن اصلی دارای کلید `<section>_enabled` در داشبورد و Switcher متناظر در Elementor هستند.
- کنترل‌های Style همچنان توسط Elementor و API استایل خود ویجت مدیریت می‌شوند؛ قرارداد دو منبع مربوط به محتوا، داده، Query و وضعیت نمایش سکشن‌ها است.
- داده‌های نسخه‌های قبل از Schema `0.4.0` با مسیر سازگاری Legacy خوانده می‌شوند تا ارتقا موجب حذف ناگهانی Partners/FAQ موجود نشود.
- از نسخه `v0.4.1`، `system_cards` استثنای Repeaterمحور دارد: Resolve آن فیلدبه‌فیلد است؛ مقدار معتبر داشبورد اولویت دارد و فیلد خالی داشبورد از Elementor fallback می‌گیرد. این قرارداد برای جلوگیری از حذف لینک/آیکون Elementor توسط عنوان‌های پیش‌فرض داشبورد است.
- از نسخه `v0.5.1` رسانه هر System Card دوحالت انحصاری دارد: `image` یا `svg`. ساختار داشبورد با `media_type + image_id + svg_id` ذخیره می‌شود و `icon_id` فقط برای Migration نسخه‌های قدیمی خوانده می‌شود.
- System Card در Elementor نیز باید Switcher مشترک Image/Icon داشته باشد؛ حالت Image از `Controls_Manager::MEDIA` و حالت Icon/SVG از `Controls_Manager::ICONS` استفاده می‌کند. کنترل‌های تکراری Repeater باید از Helper مشترک ویجت ساخته شوند.
- فایل SVG داشبورد در `ba-jihadi-center__system-icon` باید Inline رندر شود و خواندن/پاک‌سازی آن فقط از `BA_Media_Helper::get_inline_svg_attachment()` عبور کند. تصویر معمولی باید با عنصر `<img>` خروجی داده شود. داده Legacy از نوع Media همچنان به‌عنوان fallback سازگاری خوانده می‌شود.
- از نسخه `v0.5.2` روی selector پایه‌ی `.ba-jihadi-center__system-icon svg` هیچ `fill` اجباری تعریف نمی‌شود؛ کنترل رنگ عادی و Hover فقط از `color` استفاده می‌کند تا SVGهای مبتنی بر `currentColor` رفتار استاندارد داشته باشند. رنگ Hover آیکون باید کنترل مستقل Elementor داشته باشد و با Hover/Focus کارت تغییر کند.
- از نسخه `v0.5.3` تمام بخش‌های اصلی ویجت از Behavior Hook مشترک `data-ba-jc-reveal` برای Fade-in یک‌باره استفاده می‌کنند. سکشن‌های پایین صفحه علاوه بر آن Modifier `ba-jihadi-center__viewport-section--lazy` دارند و با `content-visibility: auto` Lazy Render می‌شوند. Hero به دلیل LCP eager باقی می‌ماند. این رفتار در `prefers-reduced-motion` و Elementor Editor نباید انیمیشن/محدودیت ویرایشی ایجاد کند.
- از نسخه `v0.5.5` کنترل Margin چهارجهته ریسپانسیو برای `.ba-jihadi-center__stats-wrap` و کنترل‌های ارتفاع، `object-fit`، `object-position`، opacity و CSS Filter برای تصویر Hero در Elementor در دسترس هستند. Min Height موبایل Hero نباید در CSS ثابت شود و باید از `--ba-jc-hero-height` پیروی کند.
- از نسخه `v0.5.6` کانتینر `.ba-jihadi-center__hero-media` و تصویر `.ba-jihadi-center__hero-image` باید ابعاد صریح `width: 100%` و `height: 100%` داشته باشند. خود تصویر به‌صورت absolute داخل Media Container قرار می‌گیرد تا کنترل‌های `object-fit` و `object-position` Elementor همیشه روی جعبه‌ای با ابعاد مشخص اعمال شوند.
- از نسخه `v0.5.7` عرض و ارتفاع خود `.ba-jihadi-center__hero-image` از طریق کنترل‌های ریسپانسیو Elementor قابل Override است. مقدار پیش‌فرض هر دو `100%` باقی می‌ماند و CSS پایه همچنان fallback ایمن را فراهم می‌کند.
- از نسخه `v0.5.8` تصاویر اخبار/مقالات و چندرسانه‌ای باید نسبت تصویر مشخص داشته باشند. نسبت آیتم شاخص و آیتم‌های ثانویه در هر سکشن با کنترل Responsive مستقل Elementor مدیریت می‌شود. برای جلوگیری از تداخل، ارتفاع ثابت قبلی قاب‌های رسانه حذف شده و تصویر داخلی با `object-fit: cover` قاب دارای `aspect-ratio` را پر می‌کند. کنترل‌های نسبت تصویر مشترک باید از Helper `register_image_aspect_ratio_control()` ساخته شوند تا گزینه‌ها و رفتار Responsive تکرار نشود.
- از نسخه `v0.5.9` استایل متن آیتم‌های Query در اخبار و چندرسانه‌ای باید برای آیتم شاخص و آیتم‌های ثانویه مستقل باشد. عنوان و تاریخ هر گروه Typography مستقل و رنگ‌های Normal/Hover دارند و ثبت این کنترل‌ها باید از Helper `register_post_item_text_style_controls()` عبور کند. رنگ Hover برای `focus-visible` نیز اعمال می‌شود. تاریخ آیتم شاخص چندرسانه‌ای بخشی از Markup استاندارد این سکشن است.

آیتم Partner فقط وقتی معتبر است که حداقل تصویر، SVG/Icon یا عنوان داشته باشد. URL به‌تنهایی نباید یک کارت خالی ایجاد کند.

## وضعیت Refactor قدیمی

مورد enqueue تکراری استایل Checkout که در Handoff اولیه ثبت شده بود، در نسخه `v0.3.0` حذف شد. سایر Refactorهای کد Legacy فقط هنگام نیاز قابلیت یا نسخه مستقل انجام می‌شوند.

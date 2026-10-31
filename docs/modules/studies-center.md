# MODULE-STUDIES-CENTER — Studies Center Page (مرکز مطالعات)

**Status:** implemented / live acceptance pending
**Path:** `wp-content/themes/ostadsho-child/`
**Decision:** [ADR-008](../decisions/ADR-008-studies-center-data-model.md)

## Purpose

صفحه «مرکز مطالعات راهبردی و پیشرفت مناطق محروم» و صفحه داخلی نشست‌ها، طبق طرح تأییدشده کارفرما (دسکتاپ و موبایل). صفحه سکشن به سکشن با Elementor ساخته می‌شود. ویجت‌های اختصاصی فقط برای Hero، فیلتر حوزه، کاروسل‌ها، آرشیو و صفحه داخلی نشست ساخته شده‌اند.

صفحه زنده فعلی «مطالعات و آبادانی» (page 7930) هنوز تغییر نکرده است. جایگزینی آن با تأیید مدیر پروژه انجام می‌شود.

## Data Model

| بخش | منبع |
|---|---|
| کتاب‌ها | `publications` (Post Type موجود، ثبت‌شده بیرون از ریپو). دسته‌های `publications-cat` انتخاب‌شده در تب تنظیمات؛ خالی = همه انتشارات. **دسته‌های پژوهش همیشه exclude می‌شوند.** صفحه داخلی و flipbook دست نخورده‌اند. |
| پژوهش‌ها | همان `publications` در دسته‌های «پژوهش» انتخاب‌شده در تب تنظیمات. نام/slug دسته در کد ثابت نیست. بدون انتخاب، سکشن خالی است. |
| نوع پژوهش | Taxonomy `ba_research_type` (hierarchical، غیرعمومی) روی `publications`؛ badge کارت = اولین term. |
| نشست‌ها | CPT `ba_study_session`، آدرس `/studies-center/sessions/<slug>/`، بدون archive خودکار. supports: title, editor (گزارش), excerpt (خلاصه کارت), thumbnail (پوستر/کارت), revisions. |
| حوزه | Taxonomy مشترک `ba_study_area` روی `publications` و `ba_study_session`؛ فیلتر هر سه سکشن روی همین است. |

### Session meta

یک meta آرایه‌ای `_ba_session` که با متاباکس «اطلاعات نشست، ویدئو و فایل‌ها» ذخیره می‌شود:

- `video_id` (attachment) یا `video_url` (آدرس مستقیم)، `video_duration` (خالی = `length_formatted` متادیتای فایل)
- `lead`، `event_type`، `event_date` (Y-m-d میلادی؛ نمایش با `date_i18n` و افزونه فارسی‌ساز)، `time_start`/`time_end` (H:i)، `speakers`، `location`
- `files[]`: `title`، `file_id`، `meta` اختیاری. نوع/آیکون از MIME؛ متای خودکار = مدت یا حجم + پسوند. ردیف بدون فایل ذخیره نمی‌شود.

Repeater فایل‌ها از `BA_Admin_Repeater_Component` استفاده می‌کند (ADR-005)؛ sanitize و persistence در `BA_Studies_Session_Meta` است.

### Default areas

۸ حوزه طرح با slug انگلیسی، فقط یک‌بار روی `admin_init` ساخته می‌شوند: `term_exists` + option `ba_studies_areas_seeded`. بعد از ثبت flag، ویرایش/حذف مدیر دوباره بازنویسی نمی‌شود.

## Access

- Capability واحد: `ba_manage_studies_center` (`BA_Studies_Center_Settings_Service::CAPABILITY`).
- همین Capability تب «مرکز مطالعات» را باز می‌کند و از رجیستری تب‌ها خودکار در «مدیریت دسترسی‌ها» دیده می‌شود. ادمین با `sync_administrator_capabilities` همیشه آن را دارد.
- CPT نشست: `capability_type = ba_study_session(s)`، `map_meta_cap = true` و همه primitiveها (`edit_posts`, `edit_others_posts`, `publish_posts`, `delete_*`, `create_posts`, …) به همین Capability نگاشت شده‌اند.
- Taxonomyها: manage/edit/delete = همان Capability؛ assign = `ba_assign_study_terms` که با filter `user_has_cap` به دارندگان `edit_posts` (ویرایشگران فعلی publications) یا Capability مرکز داده می‌شود.
- دسترسی ویرایش `publications` تغییر نکرده است.
- **نکته:** آپلود ویدئو/فایل به `upload_files` وردپرس نیاز دارد که این Capability نمی‌دهد. کاربری که فقط این دسترسی را دارد باید نقشی با `upload_files` (مثلاً نویسنده) هم داشته باشد.

## Settings Tab

`inc/admin/settings/class-ba-studies-center-settings-tab.php`، option `ba_studies_center_settings` (ADR-003، ذخیره از savebar مشترک):

- `research_cats`، `books_cats` (checkbox دسته‌های publications؛ هم‌پوشانی هنگام sanitize از کتاب‌ها حذف می‌شود)
- `stats_books|sessions|research`: خالی = شمارش خودکار؛ عدد = مقدار ثابت
- `books_url|sessions_url|research_url`: پیش‌فرض لینک «بیشتر» و کارت‌های آمار وقتی Elementor لینکی ندارد. کتاب/پژوهش خالی → آرشیو اولین دسته انتخاب‌شده → آرشیو publications.

متن‌ها و ظاهر در Elementor هستند و در تب تکرار نمی‌شوند.

## Query / REST

- `BA_Studies_Query_Service` تنها سازنده `WP_Query` و شمارش است (`build_args`، `get_items`، `count`، `stat`). در کاروسل انتخاب چندگانه و قابل جست‌وجوی term از **تمام taxonomyهای ثبت‌شده سایت** (با برچسب taxonomy) برای هر نوع محتوا وجود دارد و شناسه term برای سازگاری با Elementor حفظ می‌شود. شروط یک taxonomy با `IN` (OR) و taxonomyهای مختلف با `AND` ترکیب می‌شوند؛ انتخاب taxonomy نامرتبط با Post Type ممکن است خروجی خالی بدهد. ترتیب اولویت برای کتاب و پژوهش: **دسته‌های غیرخالی تب مدیریت ← دسته‌های انتخابی ویجت ← پیش‌فرض**. پژوهش بدون هیچ دسته‌ای خالی است؛ کتاب بدون دسته، همه انتشارات به‌جز دسته‌های پژوهش تنظیمات مدیریت را نشان می‌دهد.
- `GET /wp-json/ba/v1/studies/items?type=&area=&categories=&limit=…` فقط‌خواندنی و عمومی (فقط `publish`)، HTML کارت‌ها را از `BA_Studies_Card_Renderer` برمی‌گرداند. `categories` فهرست CSV شناسه‌های term (حداکثر ۵۰ شناسه از taxonomyهای سایت) است؛ taxonomy واقعی از term در سرور استخراج می‌شود و همان اولویت تنظیمات مدیریت در REST نیز سمت سرور اعمال می‌شود. پارامترهای نمایشی whitelist/sanitize می‌شوند؛ `Cache-Control: public, max-age=300`.
- `?area=<slug>` در آدرس صفحه، رندر اولیه کاروسل‌ها/آرشیو و انتخاب dropdown را تعیین می‌کند.

## Elementor Widgets (category «بنیاد علوی»)

| Widget | Name | توضیح |
|---|---|---|
| Hero | `bonyad_alavi_studies_hero` | اسلایدر Swiper داخلی Elementor: پیش‌فرض خودکار ۴ نشست آخر، قابل سوئیچ به repeater دستی؛ برچسب، عنوان، متا «تاریخ · متن»، فلش، نقطه، لینک کل اسلاید. سه کارت آمار repeater با منبع books/sessions/research/manual، آیکون پوشه/دوربین/سند، ارقام فارسی با `٬`، لینک پیش‌فرض `#studies-*`. |
| فیلتر حوزه | `bonyad_alavi_studies_area_filter` | dropdown «همه حوزه‌ها». حالت `live` رویداد `ba:studies-area-change` منتشر و `?area=` را با `replaceState` همگام می‌کند؛ حالت `url` صفحه مقصد را با `?area=` باز می‌کند. |
| کاروسل | `bonyad_alavi_studies_carousel` | `content_type` = books/sessions/research. در Query فهرست termهای تمام taxonomyهای ثبت‌شده سایت برای هر سه نوع محتوا قابل جست‌وجو است؛ انتخاب‌های ذخیره‌شده قبلی معتبر می‌مانند و fallback و تقدم تنظیمات مدیریت حفظ می‌شود. هدر داخل ویجت (عنوان، «بیشتر ←»، قبلی/بعدی). کارت‌در‌نما responsive (پیش‌فرض کتاب ۴/۳/۲٫۳، نشست ۳/۲/۱٫۲۵، پژوهش ۳/۲/۱٫۱۲)، نقطه‌ها پیش‌فرض فقط موبایل، «موردی یافت نشد». anchor پیش‌فرض `studies-<type>`. |
| آرشیو | `bonyad_alavi_studies_grid` | گرید همان کارت‌ها با صفحه‌بندی `?ba_page=` و فیلتر `?area=`؛ پیش‌فرض نشست‌ها. |
| ویدئوی نشست | `bonyad_alavi_studies_session_video` | پوستر + play وسط + برچسب «کلیپ کوتاه نشست · مدت». بدون ویدئو فقط پوستر. |
| لید و گزارش | `bonyad_alavi_studies_session_content` | لید پررنگ + `the_content` با guard ضد بازگشت. |
| کارت اطلاعات | `bonyad_alavi_studies_session_info` | کارت حاشیه سبز: عنوان (پیش‌فرض H1)، جدول نوع/تاریخ/ساعت/سخنرانان/حوزه/مکان (ردیف خالی حذف)، «فایل‌های نشست» با دکمه دانلود. برچسب‌ها قابل ویرایش. |
| فایل‌ها | `bonyad_alavi_studies_session_files` | فهرست مستقل فایل‌ها برای چیدمان‌های دیگر. |

ویجت‌های نشست در ویرایشگر Theme Builder (که پست جاری template است) آخرین نشست منتشرشده را برای پیش‌نمایش استفاده می‌کنند.

عنوان باکس معرفی با ویجت موجود `bonyad_alavi_section_heading` (layout=stack) و متن با Text Editor ساخته می‌شود.

Assets (فقط هنگام استفاده ویجت):
- `assets/css/bonyad-alavi-studies-center.css` + `assets/js/bonyad-alavi-studies-center.js` (deps: `elementor-frontend`, `swiper`)
- `assets/css/bonyad-alavi-studies-session.css` + `assets/js/bonyad-alavi-studies-session.js`
- admin: `assets/css|js/ba-studies-session-admin.*` فقط روی صفحه ویرایش نشست

CSS با `.ba-studies-widget` / `.ba-session-*` scope شده و توکن‌های ویجت‌های home (سبز `#069043`، طلایی `#b79254`، peyda، radius 14–18) را تکرار می‌کند. نکته: CSS خود Swiper روی `.swiper` مقدار `margin:auto` دارد و آیتم grid را به عرض صفر می‌برد؛ اسلایدر Hero صریحاً `width:100%; margin:0` دارد.

## Elementor Templates (export در ریپو)

`wp-content/themes/ostadsho-child/elementor-templates/`:

| فایل | نوع | کاربرد |
|---|---|---|
| `studies-session-single.json` | `single-post` (Theme Builder) | صفحه داخلی نشست: container دوستونه (اصلی ۶۱٪: ویدئو + لید/گزارش؛ کناری ۳۷٪: کارت اطلاعات)، پس‌زمینه `#F4F4F4`، موبایل ستونی. فقط ویجت‌های اختصاصی. |
| `studies-center-page.json` | `page` | صفحه اصلی: Hero، باکس معرفی (section heading + text editor + فیلتر)، سه کاروسل. |
| `studies-sessions-archive-page.json` | `page` | آرشیو نشست‌ها: عنوان، فیلتر حالت url، گرید. |

این فایل‌ها با `tests/studies-center-harness/generate-elementor-templates.py` بازتولید می‌شوند.

### مراحل import (پنل وردپرس)

**پیش‌شرط:** Elementor Pro فعال با Theme Builder. از این session (بدون دسترسی سرور) قابل تأیید نبود. اگر «قالب‌ها ← Theme Builder» در دسترس نیست، کار را متوقف و گزارش کنید و قالب PHP جایگزین نسازید.

1. قالب‌ها ← قالب‌های ذخیره‌شده ← «درون‌ریزی قالب‌ها» ← هر سه JSON.
2. Theme Builder ← Single ← «مرکز مطالعات — صفحه داخلی نشست» ← Publish ← Display Conditions: Include ← نشست‌ها و رویدادها (همه). در صورت نیاز «Preview Settings» را روی یک نشست بگذارید.
3. یک برگه جدید (پیش‌نویس) بسازید ← «ویرایش با المنتور» ← آیکون پوشه ← My Templates ← «مرکز مطالعات — صفحه اصلی» ← Insert. برای آرشیو نشست‌ها هم یک برگه جدا با template آرشیو بسازید و آدرسش را در تب «مرکز مطالعات ← صفحه آرشیو نشست‌ها» وارد کنید.
4. پس از پذیرش، جایگزینی یا redirect صفحه 7930 فقط با تأیید مدیر پروژه.

## Tests / Harness

- `tests/studies-center-contract.php`: قرارداد استاتیک (functions.php تک‌خطی، CPT/caps، taxonomy مشترک، seed یک‌باره، exclude پژوهش از کتاب، REST فقط‌خواندنی، رجیستری تب، ۸ ویجت، assetها، CSS scoped، templateها).
- `tests/studies-center-runtime.php`: منطق واقعی با stubهای WP (sanitize تنظیمات و متا، `build_args`، caps، seed، renderer escaping، sanitizerهای REST).
- `tests/studies-center-harness/`: رندر ایزوله فقط CLI (`PHP_SAPI` guard) با stubهای Elementor/WP و داده fixture طرح؛ از هیچ فایل production لود نمی‌شود. اجرا:
  `php wp-content/themes/ostadsho-child/tests/studies-center-harness/render.php <out> <deps>` که `<deps>/node_modules` شامل `swiper@8` و `vazirmatn` است (peyda در دسترس نبود و Vazirmatn جایگزین شد).
- CI: `.github/workflows/studies-center-checks.yml`.

## Out of Scope / Panel Tasks

- منوی اصلی: افزودن «مرکز مطالعات» بین «چندرسانه‌ای» و «انتشارات» از نمایش ← فهرست‌ها (کد تغییر نکرده است).
- هدر/فوتر سراسری دست نخورده‌اند.

## Regression Areas

- تفکیک کتاب/پژوهش و شمارش جدا
- فیلتر حوزه live/url و `?area=`
- caps نشست و انتساب حوزه توسط ویرایشگران publications
- seed بدون بازسازی
- Swiper Elementor (بدون bundle اضافه)، RTL، reduced motion
- ویجت‌های نشست در Theme Builder editor (پیش‌نمایش آخرین نشست)

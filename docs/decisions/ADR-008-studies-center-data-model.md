# ADR-008 — Studies Center data model: reuse publications, shared area taxonomy, single capability

**Status:** Accepted
**Date recorded:** 2026-10-31
**Decision originated:** task `feat/studies-center-page` (approved by project manager)

## Context

صفحه مرکز مطالعات سه خروجی دارد: کتاب‌ها، نشست‌ها و پژوهش‌ها، با یک فیلتر «حوزه» مشترک. کتاب‌ها از قبل در Post Type `publications` (بیرون از ریپو) با صفحه داخلی و flipbook وجود دارند. نشست‌ها صفحه داخلی جدید با ویدئو، اطلاعات و فایل دارند. مدیر پروژه خواست دسترسی کامل این بخش به کاربران مشخص قابل واگذاری باشد، بدون استفاده از Code Snippets.

## Decision

- **کتاب و پژوهش هر دو در `publications` می‌مانند.** پژوهش با دسته‌های `publications-cat` که در تب «مرکز مطالعات» انتخاب می‌شوند تفکیک می‌شود و نام/slug آن‌ها در کد ثابت نیست. Query کتاب‌ها همیشه دسته‌های پژوهش را `NOT IN` می‌کند. صفحه داخلی پژوهش همان صفحه داخلی publications است.
- **نوع پژوهش** Taxonomy قابل مدیریت `ba_research_type` روی `publications` است، نه متن ثابت.
- **نشست** CPT جدید `ba_study_session` در child theme است و متای خود را در یک کلید آرایه‌ای `_ba_session` نگه می‌دارد (بدون وابستگی به ACF).
- **حوزه** Taxonomy مشترک `ba_study_area` روی `publications` و `ba_study_session` است. ۸ حوزه پیش‌فرض فقط یک‌بار (option flag + `term_exists`) ساخته می‌شوند.
- **یک Capability** (`ba_manage_studies_center`): تب تنظیمات + همه primitiveهای CPT نشست (`map_meta_cap`) + مدیریت termها. انتساب term با Capability مشتق `ba_assign_study_terms` به دارندگان `edit_posts` یا Capability مرکز داده می‌شود. دسترسی `publications` تغییر نمی‌کند.
- **صفحه داخلی نشست** با Theme Builder Elementor و فقط ویجت‌های اختصاصی child ساخته می‌شود. export JSON در `elementor-templates/` نسخه‌پذیر است. قالب PHP single ساخته نمی‌شود.
- **بارگذاری**: یک خط `require_once` در `functions.php` برای `inc/studies-center/class-ba-studies-center-bootstrap.php`. همه منطق زیر `inc/` است.
- **فیلتر بدون reload** از REST فقط‌خواندنی `ba/v1/studies/items` استفاده می‌کند تا نتیجه به تعداد آیتم‌های رندر اولیه محدود نباشد.

## Consequences

- تفکیک کتاب/پژوهش تا انتخاب دسته‌ها در تب تنظیمات کامل نیست: سکشن پژوهش خالی و کتاب‌ها شامل همه publications است.
- اگر Post Type یا Taxonomy والد publications تغییر نام دهد، ثابت‌های `BA_Studies_Content_Types` باید همگام شوند.
- کاربر فقط با Capability مرکز برای آپلود رسانه به `upload_files` نیاز دارد. این ADR آن را به‌طور سراسری اعطا نمی‌کند.
- اتکای صفحه داخلی به Elementor Pro Theme Builder یک وابستگی عملیاتی است.

## Related Modules

- [MODULE-STUDIES-CENTER](../modules/studies-center.md)
- [MODULE-THEME-ADMIN](../modules/theme-admin-settings.md)

## Relevant Files

- `../../wp-content/themes/ostadsho-child/inc/studies-center/class-ba-studies-content-types.php`
- `../../wp-content/themes/ostadsho-child/inc/studies-center/class-ba-studies-query-service.php`
- `../../wp-content/themes/ostadsho-child/inc/services/class-ba-studies-center-settings-service.php`

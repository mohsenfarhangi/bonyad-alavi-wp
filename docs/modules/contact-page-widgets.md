# MODULE-CONTACT-PAGE — Contact Page Elementor Widgets

**Status:** implemented (branch `feat/contact-page-widgets-20261105`) / not deployed, live acceptance pending  
**Owner:** Bonyad Alavi child theme  
**Source:** `wp-content/themes/ostadsho-child/inc/elementor/widgets/*contact*`, `assets/css/bonyad-alavi-contact-page.css`  
**Test:** `wp-content/themes/ostadsho-child/tests/contact-page-widgets.php` (62 check)

## Purpose

تبدیل بخش‌های HTML خام صفحه «ارتباط با ما» (page `28817`) به ویجت‌های Elementor قابل تنظیم **بدون تغییر ظاهر**. طراحی جدیدی نیست؛ پیش‌فرض‌ها برابر محتوا و CSS فعلی (`alavi-pages/assets/css/style.css`) هستند ولی CSS ویجت‌ها مستقل است و به کلاس‌های `alavi-*` وابسته نیست. افزونه `alavi-pages` دست نخورده است.

**فرم داخل این module نیست.** شورت‌کد `[alavi_form id="contact-us"]` و فرم HTML قدیمی (پنهان) عیناً در صفحه می‌مانند؛ ببین [MODULE-CONTACT-FORM](contact-form.md).

## Widgets (category «بنیاد علوی»)

| Widget | Name | Content |
|---|---|---|
| اطلاعات تماس | `bonyad_alavi_contact_info` | Repeater: آیکون/ایموجی، برچسب، متن، لینک اختیاری، جهت متن. پیش‌فرض: آدرس، کد پستی، تلفن، ایمیل |
| جدول داخلی‌ها | `bonyad_alavi_contact_table` | دو سرستون + Repeater (عنوان، مقدار، جهت). پیش‌فرض ۱۰ ردیف |
| کارت مراکز وابسته | `bonyad_alavi_contact_cards` | Repeater: عنوان، لینک عنوان، تلفن (چند شماره با `|`)، آدرس، ایمیل؛ آیکون‌ها جدا. پیش‌فرض ۳ کارت |
| نقشه | `bonyad_alavi_contact_map` | فقط آدرس embed گوگل؛ ارتفاع responsive (پیش‌فرض ۳۵۰)، `title`، `loading="lazy"` |

عنوان‌های صفحه («ارتباط با ما»، «داخلی‌های واحدها»، «مراکز وابسته»، «ارسال پیام») و متن توضیح فرم از قبل ویجت بومی Elementor (`heading`، `text-editor`) هستند و عوض نشدند. چیدمان دو ستونی اطلاعات تماس/نقشه ساختار صفحه است نه ویجت.

## Contracts

- `Bonyad_Alavi_Contact_Page_Widget::safe_url()` فقط `http`, `https`, `tel`, `mailto` را می‌پذیرد (`javascript:` و `data:` حذف می‌شود).
- نقشه: اعتبارسنجی سمت سرور؛ فقط `https://www.google.com/maps/embed?` یا `/embed/`. HTML خام، `http`، دامنه‌های شبیه (`google.com.evil`) و مسیر غیر embed خروجی خالی دارند.
- آیتم/ردیف/کارت کاملاً خالی رندر نمی‌شود؛ ویجتی که هیچ چیزی ندارد خروجی خالی می‌دهد.
- CSS: یک فایل، handle `bonyad-alavi-contact-page`؛ سلکتور المان‌ها (`h4`, `a`, `strong`, `th`, `td`) همیشه دو کلاسه (کیت Elementor 157). کنترل‌های Style بدون default ظاهری (فقط override صریح).
- هیچ JS ندارد.

## Visual verification (2026-11-05)

- هارنس خارج از ریپو: مرجع (HTML فعلی + CSS افزونه) در برابر ویجت‌ها، ۱۲۸۰ و ۳۹۰ → **۰ پیکسل تفاوت**.
- سندباکس محلی (Elementor ۴٫۳٫۵ رایگان): هر چهار ویجت با Container رندر و CSS بارگذاری شد؛ صفحه موقت حذف شد.
- تفاوت قطعی با Production فقط وقتی دیده می‌شود که روی سایت واقعی (کیت 157، فونت IRSans) پیش‌نویس ساخته شود؛ این هنوز انجام نشده.

## Rollout plan (not done)

1. merge PR، deploy فایل‌های زمان‌اجرا با اسکریپت امن (CSS، `inc/elementor/**`).
2. صفحه **جدید** پیش‌نویس (noindex) با ویجت‌ها؛ فرم‌ها دست‌نخورده؛ تست نمایش، ارسال و ایمیل مدیر، عکس دسکتاپ/موبایل در برابر مرجع.
3. جایگزینی 28817 فقط با تأیید جدا و پشتیبان تازه.

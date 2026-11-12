# ADR-009 — Quick Support uses a shared AFE form and direct isolated WooCommerce orders

**Status:** Accepted  
**Date recorded:** 2026-11-12  
**Implementation status:** Pending; this ADR records the approved design, not deployed code.

## Context

صفحه مشارکت مردمی به یک مسیر «پرداخت سریع» نیاز دارد که پرداخت‌کننده بدون انتخاب پروژه، یکی از دسته‌های موضوعی بنیاد علوی را انتخاب کند، اطلاعات پایه خود را در یک فرم مشترک ثبت کند و مستقیماً به درگاه برود. صفحه هر دسته و Popup باید با Elementor قابل طراحی باشد. پرداخت باید از زیرساخت WooCommerce استفاده کند، اما نباید کاربر را وارد Cart یا Checkout عادی کند. گزارش مالی نیز باید قابل تفکیک بر اساس دسته و درگاه باشد.

زیرساخت فعلی مشارکت، سفارش مستقل و Gateway isolation دارد؛ اما مسیر Quick Checkout فعلی product/project-centric است و `validate_project_amount()` به محصول پروژه، `goal_amount` و `minimum_amount` وابسته است. بنابراین نباید آن مسیر بدون تفکیک مسئولیت برای پرداخت دسته‌ای استفاده شود.

## Decision

### Ownership

- راهکار Hybrid است: AFE فرم عمومی و Context contract را ارائه می‌دهد؛ Child Theme مالک Category، Context Provider، Action پرداخت و گزارش Quick Support است؛ WooCommerce مرجع سفارش و تراکنش باقی می‌ماند.
- منطق Quick Support یا دسته‌های بنیاد علوی وارد هسته AFE نمی‌شود.
- AFE باید Context Provider عمومی و immutable context در ActionContext داشته باشد؛ پیاده‌سازی provider دسته‌بندی متعلق به Child Theme است.

### Categories and Elementor

- Post Type کلید `ba_quick_support` و عنوان مدیریتی «دسته‌بندی‌های پرداخت سریع» است.
- دسته‌ها title/slug، تصویر، آیکون، توضیح، ترتیب، active state، min/max، suggested amounts و gateway allowlist دارند.
- دسته‌های اولیه: `economic-empowerment`، `education-skills`، `health-treatment` و `infrastructure`.
- URL هر دسته `/participation/quick-support/{slug}/` است.
- Popup و دکمه آن با Elementor ساخته می‌شوند؛ کارت‌ها از Loop Grid و Post Type دسته‌ها می‌آیند. صفحه هر دسته نیز با Elementor قابل طراحی است؛ ویجت کارت اختصاصی در نسخه اول لازم نیست.

### Shared AFE Form and Context

- فرم واحد با slug ثابت `quick-support-payment` برای همه دسته‌ها استفاده می‌شود.
- ویجت موجود AFE به انتخاب فرم و Context source مجهز می‌شود: Current Page، Fixed Category یا Query Parameter.
- Query Parameter پیش‌فرض `support_category` است و Slug یا ID را می‌پذیرد.
- Context Provider پیشنهادی با hook `afe_register_form_context_providers` و provider ID برابر `ba_quick_support_category` ثبت می‌شود.
- Context در زمان render resolve و به‌صورت token امضاشده، محدود به form slug و category ID، به فرم متصل می‌شود. در Submit سمت سرور verify می‌شود و به‌صورت immutable و جدا از داده قابل ویرایش متقاضی در اختیار ActionContext قرار می‌گیرد.
- مقدار خام URL/hidden field هرگز authority نیست؛ دسته باید در زمان پرداخت نیز فعال و منتشرشده باشد.

### Form Fields

| Field | Required |
|---|---|
| شماره همراه، نام، نام خانوادگی، مبلغ | بله |
| استان، شهرستان، کد ملی، ایمیل | خیر |
| درگاه پرداخت | فقط وقتی انتخاب توسط کاربر فعال باشد |

- `county` از Geo Source با level برابر `county` و وابسته به `province` است؛ در صورت انتخاب شهرستان، استان الزامی می‌شود.
- کد ملی و ایمیل در صورت ورود اعتبارسنجی می‌شوند.
- واحد، نماد و دقت مبلغ از تنظیمات فعال WooCommerce گرفته می‌شود.

### Direct Payment

- Action اختصاصی `ba_quick_support_payment` پس از ثبت Submission اجرا می‌شود.
- از `BA_Participation_Payment_Gateway_Service` برای Gateway resolution و isolated cart context استفاده می‌شود.
- از `BA_Participation_Quick_Checkout_Service` و validator پروژه‌ای آن بدون تغییر استفاده نمی‌شود؛ سرویس Quick Support اعتبارسنجی category min/max و order creation مستقل دارد.
- یک محصول فنی Simple/Virtual، منتشرشده، In Stock و Hidden به‌عنوان backing product مشترک برای Cart موقت Gateway و Order Item استفاده می‌شود. خرید مستقیم آن خارج از مسیر داخلی Quick Support مسدود است.
- سفارش با WooCommerce CRUD ساخته می‌شود؛ هیچ Cart/Checkout عادی برای کاربر استفاده نمی‌شود.
- یک Order به هر AFE Submission متصل است؛ retry همان Order را بازیابی می‌کند و سفارش تکراری نمی‌سازد.
- نتیجه پرداخت فقط با callback/verification معتبر Gateway قطعی می‌شود.
- نسخه اول Hosted/Redirect Gatewayها را هدف می‌گیرد؛ Gateway دارای payment fields داخلی خارج از scope است.

### Gateway Selection

- درگاه پیش‌فرض از تنظیم فعلی `ba_participation_settings[payment_gateway]` گرفته می‌شود.
- قابلیت انتخاب کاربر با `allow_customer_gateway_selection` قابل فعال/غیرفعال است و default آن خاموش است.
- allowlist عمومی و override اختیاری per-category وجود دارد؛ allowlist خالی دسته از مقدار عمومی ارث می‌برد.
- Gateway انتخاب‌شده باید enabled، مجاز و سازگار باشد؛ default نامعتبر یا نبود گزینه مجاز خطای روشن می‌دهد و silent fallback ممنوع است.
- Gateway ID حساس به حروف باقی می‌ماند.

### Reporting

- WooCommerce Orders مرجع مبلغ، currency، Gateway، payment state و refunds است؛ AFE Submission مرجع داده کامل فرم.
- سفارش با marker `_ba_quick_support` و metadata دسته، Submission و Gateway قابل شناسایی است.
- Dashboard مستقل، فیلتر و Excel/CSV export دارد.
- مبلغ خالص پس از کسر refund محاسبه می‌شود؛ partial refund نیز لحاظ می‌شود.
- از WooCommerce CRUD و HPOS استفاده می‌شود؛ در نسخه اول جدول تراکنش موازی ساخته نمی‌شود.
- دسترسی گزارش و داده شخصی با capabilityهای اختصاصی کنترل می‌شود.

## Consequences

- قابلیت با یک فرم مشترک و بدون تکثیر فرم برای هر دسته توسعه می‌یابد.
- افزودن دسته جدید نیازمند ساخت فرم جدید یا کپی کردن کد پرداخت نیست.
- AFE فقط Context را به‌صورت generic منتقل و expose می‌کند؛ business validation و mapping دسته در Child Theme می‌ماند.
- مسیر پرداخت فعلی مشارکت باید بدون Regression باقی بماند.
- اجرای مستقیم Gateway همچنان باید Cart/Session/Persistent Cart واقعی را restore کند.
- طراحی/تصویب این ADR به معنی پیاده‌سازی یا پذیرش زنده نیست.

## Related Decisions and Modules

- [ADR-002 — Participation Quick Checkout isolation](ADR-002-participation-quick-checkout.md)
- [MODULE-QUICK-SUPPORT](../modules/quick-support-payments.md)
- [MODULE-PARTICIPATION](../modules/participation-payments.md)
- [MODULE-AFE](../modules/alavi-form-engine.md)
- [MODULE-AFE-EXTENSION](../modules/alavi-form-engine-extension-api.md)

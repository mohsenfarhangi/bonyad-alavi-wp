# MODULE-QUICK-SUPPORT — Quick Support Payments

**Status:** Phase 1, granular category permissions, Phase 2 reports, report-source isolation, dedicated form layout and same-page payment return/result templates are merged in [PRs #34–#39](https://github.com/mohsenfarhangi/bonyad-alavi-wp/pulls?q=is%3Apr+is%3Amerged+quick+support). PR #39 squash merge / `main` HEAD at this snapshot: `27ae5780fe245a6d71cd5af279284f328bcf5992`. Latest Quick Support CI [run 43413527210](https://github.com/mohsenfarhangi/bonyad-alavi-wp/actions/runs/43413527210) passed. Deployment and live gateway acceptance remain pending.  
**Decision:** [ADR-009](../decisions/ADR-009-quick-support-payments.md)  
**Parent modules:** [MODULE-PARTICIPATION](participation-payments.md), [MODULE-AFE](alavi-form-engine.md), [MODULE-AFE-EXTENSION](alavi-form-engine-extension-api.md)

## Purpose and Scope

پرداخت سریع مشارکت مردمی برای حمایت از یکی از دسته‌بندی‌های موضوعی بنیاد علوی، بدون انتخاب پروژه یا محصول توسط پرداخت‌کننده. یک فرم مشترک اطلاعات پرداخت‌کننده را می‌گیرد و پرداخت را مستقیماً از طریق درگاه WooCommerce انجام می‌دهد.

این سند مرجع طراحی و پیاده‌سازی است. فاز اول و اصلاح مجوزهای دسته‌بندی به main ادغام شده‌اند. دسترسی مدیر کل روی سایت تأیید شده است؛ پذیرش عملیاتی درگاه و مسیر بازگشت پرداخت همچنان باید در محیط آزمایشی انجام شود.

## Phase 1 Implementation Record — PR #34

**Implementation status:** PRs #34–#39 are merged. Generic Context, category CPT, shared form, direct WooCommerce payment, financial reporting dashboard, strict report-source isolation, dedicated form layout and status-based same-page return are in main. The project owner deployed report isolation and confirmed it works; live gateway/payment-flow acceptance remains a separate pending item.

Implemented and merged in main:

- AFE generic Context Provider interface, registry and manager; signed form-bound token with 12-hour expiry and server-side provider revalidation.
- Elementor Form Widget controls for Current Page, Fixed Category and Query Parameter (Slug/ID).
- Context is carried separately from applicant data through ActionContext and is available to context-aware validators and dynamic data sources.
- Generic single-option dynamic Select rendering and visible downstream Action errors.
- `ba_quick_support` CPT, four seeded categories, category meta settings, Elementor CPT support and five independent category capabilities. Only the built-in Administrator receives them automatically; other roles are configured manually in PublishPress Capabilities.
- Shared AFE form `quick-support-payment`, required/optional field rules, province/county dependency and server-side amount-range validation.
- Dedicated payment Action and WooCommerce order service, shared technical product, per-submission lock/idempotency, retry recovery and gateway allowlists.
- Existing participation Gateway service extended with exact gateway resolution and optional internal isolated-cart item data.
- Suggested amount/limit UI and customer gateway-selection setting.

**Focused CI:** [run 43309991432](https://github.com/mohsenfarhangi/bonyad-alavi-wp/actions/runs/43311051009) passed PHP syntax, AFE Context/validator tests, Quick Support category/form/payment-rule tests, existing theme form regressions and JavaScript syntax on the PR branch.

**Live acceptance still pending:** Hosted/Redirect gateway initiation and callback/return, preservation of the real Cart/Session/Persistent Cart, HPOS order creation, and confirmation of the Elementor Single/Popup/Loop Grid layouts on the target site. The project owner reported that the updated theme files were uploaded and confirmed Administrator access; this does not count as payment-flow acceptance.

**Phase 2 implementation — PR #37 (merged):** adds the dedicated report submenu, financial aggregation, filters, paginated order list and CSV/XLSX exports. Report queries are restricted by both `created_via = ba_quick_support` and `_ba_quick_support = 1`, excluding product-based participation orders (`bap_quick_checkout`) from the dashboard, list and exports. Final focused CI [run 43395846166](https://github.com/mohsenfarhangi/bonyad-alavi-wp/actions/runs/43395846166) passed. Strict product-participation exclusion is additionally covered by [PR #38](https://github.com/mohsenfarhangi/bonyad-alavi-wp/pull/38), CI [run 43398110222](https://github.com/mohsenfarhangi/bonyad-alavi-wp/actions/runs/43398110222) passed; it has since been deployed and verified by the project owner.

## Category Administration Capabilities

Implemented and merged in PR #36. Administrator category-list access was subsequently confirmed on the live site.

| Operation | Capability |
|---|---|
| View category list | `view_ba_quick_support_categories` |
| Create category | `create_ba_quick_support_categories` |
| Edit category | `edit_ba_quick_support_category` |
| Publish category | `publish_ba_quick_support_categories` |
| Delete category | `delete_ba_quick_support_category` |

WordPress's CPT list-screen primitive `edit_posts` maps to the independent view permission. Object-level edit/read/delete meta capabilities and create/publish primitives are mapped independently. Administrator receives all five permissions once through a versioned migration. No other role receives them automatically; assign each permission per role in PublishPress Capabilities. A view-only role can open the list, but editing an individual category requires the edit permission.

## Architecture and Ownership

- **Elementor / Child Theme:** نمایش دسته‌ها، Popup، صفحه هر دسته و اتصال فرم به Context.
- **Alavi Form Engine:** فرم مشترک، اعتبارسنجی، ثبت Submission و قرارداد عمومی Form Context.
- **Quick Support integration در Child Theme:** مدیریت دسته‌ها، resolve کردن Context، Action پرداخت، ساخت سفارش و گزارش.
- **WooCommerce:** سفارش، درگاه، وضعیت تراکنش و Refund؛ مرجع مالی سیستم.

AFE عمومی نباید نام دسته‌های بنیاد علوی، شناسه‌های Quick Support یا منطق پرداخت WooCommerce را hard-code کند.

## Category Content Model

یک Custom Post Type با کلید کوتاه و معتبر WordPress زیر ایجاد شود:

`ba_quick_support`

عنوان مدیریتی: «دسته‌بندی‌های پرداخت سریع».

Post Type باید برای permalink عمومی، REST و ویرایش محتوا با Elementor آماده باشد. کلید Post Type حداکثر 20 کاراکتر است؛ بنابراین از نام طولانی `ba_quick_support_category` به‌عنوان post type key استفاده نشود.

هر دسته‌بندی شامل این داده‌هاست:

- عنوان و Slug یکتا
- تصویر شاخص و آیکون
- توضیح کوتاه برای کارت Popup
- وضعیت انتشار و فعال/غیرفعال
- ترتیب نمایش
- حداقل و حداکثر مبلغ
- مبالغ پیشنهادی
- فهرست درگاه‌های مجاز یا ارث‌بری از تنظیمات عمومی
- محتوای صفحه قابل طراحی با Elementor

دسته غیرفعال یا منتشرنشده نباید در Popup دیده شود و Context آن نیز نباید برای پرداخت پذیرفته شود.

### Initial Categories and Permalinks

| عنوان | Slug | مسیر |
|---|---|---|
| توانمندسازی اقتصادی | `economic-empowerment` | `/participation/quick-support/economic-empowerment/` |
| آموزش، مهارت و پرورش | `education-skills` | `/participation/quick-support/education-skills/` |
| بهداشت و درمان | `health-treatment` | `/participation/quick-support/health-treatment/` |
| عمرانی و زیرساختی | `infrastructure` | `/participation/quick-support/infrastructure/` |

Slug شناسه عمومی و پایدار URL است؛ شناسه عددی Post فقط شناسه داخلی WordPress باقی می‌ماند.

## Elementor Experience

- دکمه Popup در Header یا صفحه اصلی توسط مدیر سایت با Elementor ساخته می‌شود.
- خود Popup نیز توسط مدیر طراحی می‌شود.
- نمایش دسته‌های فعال با Elementor Loop Grid و منبع Post Type برابر `ba_quick_support` انجام می‌شود؛ Loop Item ظاهر کارت را کنترل می‌کند.
- هر کارت به permalink همان دسته می‌رود.
- هر دسته یک صفحه مستقل دارد و محتوای آن با Elementor قابل طراحی است. قالب Single مشترک نیز در صورت نیاز قابل استفاده است.
- در نسخه اول برای کارت دسته‌ها ویجت اختصاصی جدا لازم نیست؛ Loop Grid سطح نمایش را پوشش می‌دهد.

## Shared AFE Form

یک Source Definition در Child Theme با slug ثابت زیر ثبت شود:

`quick-support-payment`

فرم برای همه دسته‌ها مشترک است و در صفحه هر دسته فقط یک ویجت «فرم علوی» قرار می‌گیرد.

### Dedicated form layout

- Source Definition uses `Form::template('{{steps}}')`; therefore the Quick Support form has no AFE form header, brand mark or progress bar.
- `show_step_heading=false` removes the `.afe-step-heading` block from this form only. AFE defaults this setting to `true` for all other forms.
- `captcha_before_submit=true` renders the CAPTCHA after the final Step fields and immediately before the submit-action area/button. The existing CAPTCHA validation and payment Action remain unchanged.
- These settings are part of the `quick-support-payment` code definition; no global AFE template or other form definition is changed.
- Merged in [PR #39](https://github.com/mohsenfarhangi/bonyad-alavi-wp/pull/39); Quick Support CI [run 43403744041](https://github.com/mohsenfarhangi/bonyad-alavi-wp/actions/runs/43403744041) and AFE regression CI [run 43403744021](https://github.com/mohsenfarhangi/bonyad-alavi-wp/actions/runs/43403744021) passed. Live form acceptance in Elementor remains pending.

### Same-page payment return and editable result templates

- The payment service stores the validated same-site URL of the page hosting the form on the WooCommerce order, along with a random return token. The AJAX Referer is preferred; the Quick Support category permalink is the fallback.
- WooCommerce return, order-received and cancellation URL filters are applied only to orders whose `created_via` and Quick Support marker both match. The WooCommerce order-payment URL is not overridden, preserving its normal payment/retry behavior. Cancellation still passes through WooCommerce's handler so the order status is changed before the browser is returned to the source page.
- On return, AFE's generic `afe_form_render_override` hook lets the theme replace the form at the exact widget/shortcode location. The handler requires matching `created_via`, the Quick Support order marker and a constant-time comparison of the random return token; invalid credentials leave the normal form visible. Legacy orders from the first main-branch draft retain their order-key return path.
- The result state is selected from the current WooCommerce order status: paid, failed, cancelled, pending or unknown. The query string is only a locator and never proof of successful payment.
- The AFE «قالب‌ها» tab provides five independent HTML editors and one optional CSS editor for the Quick Support form. Available tokens: `{{order_number}}`, `{{amount}}`, `{{category}}`, `{{transaction_id}}`, `{{status}}`, `{{date}}`, `{{message}}` and `{{retry_url}}`.
- Failed/cancelled templates can link to the clean original page to retry. Pending/unknown templates avoid duplicate payment attempts while the gateway result is unresolved.
- Squash-merged in [PR #39](https://github.com/mohsenfarhangi/bonyad-alavi-wp/pull/39), commit `27ae5780fe245a6d71cd5af279284f328bcf5992`. Latest Quick Support CI [run 43413527210](https://github.com/mohsenfarhangi/bonyad-alavi-wp/actions/runs/43413527210) passed PHP syntax and dedicated form/payment-return tests. Latest AFE frontend CSS/browser workflow [run 43413527323](https://github.com/mohsenfarhangi/bonyad-alavi-wp/actions/runs/43413527323) passed at workflow level; Playwright checks passed at 360, 780, 782 and 1024px. Its `tools/qa.sh` sub-step still reports three baseline Export/Jihadi fixture failures and is configured `continue-on-error`; this is not a clean full-suite QA pass. No live gateway acceptance is claimed.

### Form Fields

| Field ID | Label | Type / Source | Required |
|---|---|---|---|
| `mobile` | شماره همراه | Tel + Iran mobile validator | بله |
| `first_name` | نام | Text | بله |
| `last_name` | نام خانوادگی | Text | بله |
| `province` | استان | Geo Select، level=`province` | خیر |
| `county` | شهر | Geo Select، level=`county`، وابسته به `province` | خیر |
| `national_id` | کد ملی | Text + national ID validator | خیر |
| `email` | ایمیل | Email | خیر |
| `amount` | مبلغ | Number / amount input | بله |
| `payment_gateway` | درگاه پرداخت | Dynamic Select | فقط در صورت فعال بودن انتخاب درگاه |

قواعد تکمیلی:

- اگر `county` انتخاب شود، `province` نیز باید انتخاب شده باشد.
- شهرستان باید متعلق به استان انتخاب‌شده باشد.
- کد ملی و ایمیل در صورت ورود باید معتبر باشند؛ خالی بودن آن‌ها مجاز است.
- حداقل/حداکثر مبلغ و مبالغ پیشنهادی از دسته فعال خوانده شوند.
- مبلغ در سمت سرور دوباره نرمال‌سازی و اعتبارسنجی شود.
- مبلغ و نماد/تعداد اعشار از تنظیمات WooCommerce گرفته شود؛ واحد پول hard-code نشود.
- توابع نمایشی/واحد پول شامل `get_woocommerce_currency()`، `get_woocommerce_currency_symbol()`، `wc_get_price_decimals()` و `wc_price()` هستند.

## Generic AFE Form Context Contract

این نیاز باید به‌شکل قابلیت عمومی و قابل استفاده مجدد در AFE پیاده شود؛ نه با افزودن فیلد مخفی پروژه‌ای به فرم.

### Elementor Widget Controls

ویجت موجود AFE توسعه یابد و این تنظیمات را ارائه کند:

- انتخاب فرم
- انتخاب Context Provider
- منبع Context: صفحه جاری، دسته ثابت یا Query Parameter
- نوع شناسه ورودی: Slug یا ID
- نام پارامتر URL؛ پیش‌فرض Quick Support برابر `support_category`
- انتخاب دسته ثابت در حالت Fixed

برای Quick Support سه حالت باید پشتیبانی شود:

1. **Current Page:** دریافت دسته از Post جاری با Post Type برابر `ba_quick_support`.
2. **Fixed Category:** انتخاب یک دسته از تنظیمات ویجت.
3. **URL Parameter:** دریافت Slug یا ID از Query String، مثلاً `?support_category=health-treatment`.

### Provider and Security Contract

- AFE یک قرارداد عمومی برای ثبت Form Context Providerها اضافه کند؛ نام hook پیشنهادی `afe_register_form_context_providers` و Provider پروژه `ba_quick_support_category` است.
- Provider متعلق به Child Theme است و فقط دسته منتشرشده و فعال را resolve می‌کند.
- مقدار خام URL یا hidden input به‌تنهایی قابل اعتماد نیست.
- Provider در زمان render یک Context token امضاشده و محدود به همان form slug و category ID صادر می‌کند.
- در زمان Submit، token در سمت سرور اعتبارسنجی و انقضا/تطبیق آن بررسی شود؛ Context نامعتبر یا دسته غیرفعال باید fail-closed شود.
- AFE مقدار resolve‌شده را به‌صورت Context تغییرناپذیر و جدا از فیلدهای متقاضی به ActionContext بدهد.
- Context نباید به‌عنوان فیلد قابل مشاهده/ویرایش کاربر یا مقدار عادی `data_json` تلقی شود.
- نام دسته، مبلغ یا درگاه از مرورگر مرجع نهایی نیست؛ Action پرداخت همه موارد را مجدداً از تنظیمات دسته و WooCommerce بررسی می‌کند.

این قرارداد عمومی در AFE پیاده‌سازی شده است: Provider registry/manager، توکن امضاشده محدود به فرم با انقضای ۱۲ ساعته، revalidation سمت سرور و انتقال Context تغییرناپذیر و جدا از داده متقاضی در ActionContext. پیاده‌سازی در PR #34 ادغام شده است.

## Direct WooCommerce Payment

Action اختصاصی AFE:

`ba_quick_support_payment`

Action پس از ثبت موفق Submission اجرا می‌شود و با Context معتبر دسته، اطلاعات فرم، مبلغ و درگاه را به سرویس Quick Support می‌دهد.

### Reuse Boundary with Existing Participation Code

زیرساخت موجود در Child Theme شامل این سرویس‌هاست:

- `BA_Participation_Payment_Gateway_Service`
- `BA_Participation_Quick_Checkout_Service`

از `BA_Participation_Payment_Gateway_Service` برای resolve کردن درگاه، بررسی availability و اجرای gateway در Context سبد ایزوله استفاده شود؛ به‌ویژه `with_isolated_cart()` که سبد موقت می‌سازد و Cart/Session/Persistent Cart واقعی کاربر را restore می‌کند.

**نباید متد اعتبارسنجی پروژه‌ای `Bonyad_Alavi_WooCommerce_Participation::validate_project_amount()` یا کل مسیر `BA_Participation_Quick_Checkout_Service` بدون تغییر برای Quick Support استفاده شود.** آن مسیر به محصول پروژه، `goal_amount` و `minimum_amount` پروژه وابسته است؛ در حالی که Quick Support محدودیت مبلغ را از دسته‌بندی می‌گیرد.

سرویس مستقل Quick Support باید:

1. مبلغ را براساس حداقل/حداکثر دسته اعتبارسنجی کند.
2. محصول فنی پرداخت را resolve کند.
3. سفارش را با WooCommerce CRUD بسازد.
4. Gateway انتخاب‌شده را از سرویس مشترک درگاه resolve کند.
5. availability و `process_payment()` را در Context ایزوله اجرا کند.
6. URL برگشتی درگاه را از طریق AFE Action Runtime به مرورگر redirect کند.

### Technical WooCommerce Product

برای سازگاری با مسیر Gateway isolation که به `WC_Product` نیاز دارد، یک محصول فنی مشترک برای همه دسته‌های Quick Support در نظر گرفته شود:

- Simple و Virtual
- Published و In stock برای پذیرش داخلی در Cart موقت
- Catalog visibility برابر Hidden
- بدون حمل‌ونقل
- فقط یک محصول فنی برای کل Quick Support؛ دسته‌بندی با Order/Line Item meta مشخص می‌شود.
- افزودن مستقیم این محصول به Cart واقعی یا خرید عادی باید با guard سمت سرور مسدود باشد؛ فقط Context داخلی Quick Support مجاز به افزودن آن به Cart موقت است.

این محصول یک کالای قابل عرضه در فروشگاه نیست. قیمت واقعی پرداخت از مبلغ اعتبارسنجی‌شده دسته می‌آید و در Order Item override می‌شود.

### Order and Idempotency

- Order با `wc_create_order()` و WooCommerce CRUD ساخته شود؛ از `add_to_cart()` کاربر و صفحه Checkout عادی استفاده نشود.
- Order شامل یک Line Item از محصول فنی با نام نمایشی حمایت سریع و عنوان دسته باشد.
- شناسه Submission در Order ذخیره شود و برای هر Submission فقط یک Order ساخته شود.
- اجرای مجدد Action یا retry نباید Order تکراری بسازد؛ Order واجد شرایط قبلی باید بازیابی شود.
- Order ابتدا در وضعیت مناسب انتظار پرداخت قرار می‌گیرد.
- پرداخت ناموفق Submission را حذف نمی‌کند؛ امکان تلاش مجدد روی همان Order فراهم باشد.
- Order دارای پرداخت تأییدشده نباید دوباره به درگاه ارسال شود.
- نتیجه Redirect به‌تنهایی پرداخت موفق نیست؛ فقط callback/verification معتبر Gateway وضعیت مالی را نهایی می‌کند.

### Order Metadata

حداقل metadataهای موردنیاز:

| Meta Key | Meaning |
|---|---|
| `_ba_quick_support` | marker سفارش Quick Support |

علاوه بر metadata بالا، فیلد استاندارد WooCommerce یعنی `created_via` برای سفارش‌های این ماژول باید `ba_quick_support` باشد. این مقدار با `bap_quick_checkout` در سفارش‌های پرداخت محصول‌محور مشارکت متفاوت است و به‌عنوان شرط دوم جداسازی گزارش استفاده می‌شود.
| `_ba_quick_support_category_id` | شناسه داخلی دسته |
| `_ba_quick_support_category_slug` | Slug دسته |
| `_ba_quick_support_category_title` | snapshot عنوان دسته |
| `_ba_afe_submission_id` | شناسه Submission |
| `_ba_quick_support_amount` | مبلغ مشارکت |
| `_ba_quick_support_gateway_id` | شناسه دقیق درگاه |

نام، نام خانوادگی، موبایل و ایمیل در Billing استاندارد سفارش قرار می‌گیرند. استان، شهرستان و کد ملی در Submission AFE باقی می‌مانند و بی‌دلیل در چند محل تکثیر نمی‌شوند.

## Gateway Selection

- درگاه پیش‌فرض از تنظیمات فعلی مشارکت `ba_participation_settings[payment_gateway]` خوانده شود.
- تنظیم جدید `allow_customer_gateway_selection` امکان انتخاب توسط پرداخت‌کننده را کنترل کند؛ پیش‌فرض آن خاموش باشد تا رفتار پرداخت فعلی تغییر نکند.
- فهرست درگاه‌های مجاز در تنظیمات عمومی تعریف شود؛ هر دسته می‌تواند allowlist اختصاصی داشته باشد. allowlist خالی دسته یعنی ارث‌بری از allowlist عمومی.
- گزینه‌های قابل نمایش، اشتراک درگاه‌های مجاز با Gatewayهای فعال و سازگار WooCommerce هستند.
- اگر انتخاب کاربر غیرفعال باشد، درگاه پیش‌فرض استفاده می‌شود.
- اگر انتخاب روشن و بیش از یک درگاه مجاز باشد، Select نمایش داده شود؛ اگر فقط یک درگاه مجاز است، Select پنهان و همان درگاه استفاده شود.
- انتخاب کاربر باید سمت سرور با allowlist و وضعیت فعال Gateway تطبیق داده شود.
- درگاه پیش‌فرض نامعتبر/غیرفعال یا نبود Gateway مجاز باید خطای پیکربندی روشن بدهد؛ silent fallback به درگاه دیگر ممنوع است.
- شناسه Gateway case-sensitive است و نباید با `sanitize_key()` برای ذخیره‌سازی lowercase شود.
- نسخه اول فقط Hosted/Redirect Gatewayها را پشتیبانی می‌کند؛ Gatewayهایی که به payment fields داخلی (`has_fields()`) نیاز دارند خارج از قرارداد نسخه اول هستند.

## Admin and Reporting

منوی مستقل مدیریت: «پرداخت سریع».

WooCommerce Order منبع قطعی مبلغ، ارز، Gateway، وضعیت پرداخت و Refund است. AFE Submission منبع اطلاعات کامل فرم است. ارتباط از طریق `_ba_afe_submission_id` برقرار می‌شود.

### Dashboard

- مجموع ناخالص پرداخت‌های تأییدشده
- مجموع خالص پس از کسر Refund
- تعداد پرداخت‌های موفق
- میانگین مبلغ
- سفارش‌های در انتظار/ناموفق
- گزارش روزانه، ماهانه و بازه دلخواه
- مقایسه دسته‌بندی‌ها و Gatewayها

### Filters and Exports

- بازه تاریخ
- دسته
- Gateway
- وضعیت Order
- حداقل/حداکثر مبلغ
- شماره Order
- نام و شماره همراه
- خروجی Excel و CSV
- لینک جزئیات Order و Submission مرتبط

### Financial Rules

- فقط Orderهایی وارد گزارش شوند که هم `created_via = ba_quick_support` و هم marker `_ba_quick_support = 1` را داشته باشند.
- سفارش‌های پرداخت مشارکت محصول‌محور از مسیر مستقل `created_via = bap_quick_checkout` ساخته می‌شوند و نباید در آمار، جدول سفارش‌ها یا خروجی‌های Quick Support نمایش داده شوند.
- پرداخت‌های تأییدشده با تاریخ پرداخت معتبر در محاسبه تعداد تراکنش موفق لحاظ شوند؛ وضعیت‌های pending/failed/cancelled پرداخت موفق نیستند.
- مبلغ خالص از مبلغ Order منهای Refundهای ثبت‌شده محاسبه شود؛ Refund جزئی باید اثر خود را در گزارش بگذارد.
- مبلغ صفر خالص پس از Refund نباید به‌عنوان دریافتی خالص نمایش داده شود.
- Orderها و Refundها فقط از WooCommerce CRUD خوانده شوند تا HPOS پشتیبانی شود.
- در نسخه اول جدول مالی/تراکنش موازی ایجاد نشود؛ Orderهای WooCommerce منبع گزارش هستند.

### Capabilities

گزارش و مشاهده اطلاعات شخصی با `view_ba_quick_support_reports` و خروجی با `export_ba_quick_support_reports` کنترل می‌شوند. هر دو مجوز به‌صورت پیش‌فرض فقط به نقش `administrator` داده می‌شوند. سایر نقش‌ها هیچ دسترسی خودکاری ندارند و مدیر سایت در صورت نیاز می‌تواند هر مجوز را مستقلاً در PublishPress Capabilities اختصاص دهد. مجوزهای دسته‌بندی نیز پنج capability مستقل view/create/edit/publish/delete دارند؛ جزئیات در بخش Category Administration Capabilities ثبت شده است.

## Security and Reliability

- nonce/CSRF protection و Rate Limit برای Submit/Payment initiation
- Context token امضاشده و اعتبارسنجی مجدد دسته در سرور
- بررسی active/published بودن دسته در زمان render و payment
- اعتبارسنجی مبلغ، ارز و Gateway در سرور
- جلوگیری از دست‌کاری محصول فنی و add-to-cart مستقیم
- Idempotency براساس Submission ID
- عدم ثبت کد ملی، ایمیل، موبایل یا token در URL و log
- عدم اعتماد به redirect کاربر برای تأیید پرداخت
- حفظ Cart/Session/Persistent Cart واقعی در تمام مسیرهای Gateway
- کنترل capability برای گزارش‌ها و خروجی‌های حاوی اطلاعات شخصی

## Implementation Plan

### Phase 1 — Context, Categories, Form and Payment

- افزودن Generic Form Context Provider contract به AFE و توسعه Elementor Form Widget
- پیاده‌سازی Quick Support Category Post Type و چهار دسته اولیه
- فراهم کردن Popup Loop Grid و صفحات Elementor دسته‌ها
- ثبت فرم مشترک `quick-support-payment`
- ساخت Action و Payment/Order services اختصاصی
- اتصال مستقیم به Gateway و مدیریت retry/callback
- تست حفظ Cart و regression مسیر مشارکت فعلی

### Phase 2 — Dashboard and Reports

- [x] داشبورد اختصاصی و کارت‌های شاخص مالی
- [x] گزارش دسته/Gateway/بازه زمانی روزانه و ماهانه
- [x] فیلتر و جستجو با ارسال POST تا اطلاعات شخصی وارد URL نشوند
- [x] خروجی Excel/CSV با مجوز مستقل Export
- [x] لینک Order ↔ Submission و صفحه‌بندی سفارش‌ها
- [x] محاسبه خالص پس از Refund جزئی/کامل با WooCommerce CRUD و سازگار با HPOS

پیاده‌سازی در [PR #37](https://github.com/mohsenfarhangi/bonyad-alavi-wp/pull/37) به `main` ادغام شده است؛ تا زمان Deploy و پذیرش زنده، آن را روی سایت فعال فرض نکن.

## Acceptance Tests

- سه منبع Context (صفحه جاری، دسته ثابت، Query Parameter) و هر دو نوع ID/Slug
- رد Context دست‌کاری‌شده، منقضی، ناشناخته یا دسته غیرفعال
- نمایش خودکار دسته‌های فعال در Loop Grid و حذف دسته غیرفعال
- رندر فرم مشترک در چهار صفحه
- required/optional fields مطابق این سند
- وابستگی county به province و اعتبارسنجی سمت سرور
- اعتبارسنجی کد ملی و ایمیل فقط در صورت ورود
- دریافت صحیح currency/symbol/decimals از WooCommerce
- اعتبارسنجی min/max و suggested amounts دسته
- انتخاب درگاه خاموش/روشن، allowlist عمومی و override دسته
- رد Gateway غیرفعال/غیرمجاز و عدم silent fallback
- انتقال مستقیم به Hosted/Redirect Gateway بدون Cart/Checkout عادی
- حفظ Cart/Session/Persistent Cart قبل و بعد از پرداخت
- عدم ایجاد Order تکراری در Submit/Retry
- callback موفق/ناموفق و retry همان Order
- محاسبه Refund جزئی/کامل و گزارش خالص
- سازگاری HPOS
- عدم Regression در پرداخت مشارکت فعلی

## Non-goals for Version 1

- پرداخت دوره‌ای/اشتراک
- تقسیم یک پرداخت بین چند دسته
- انتخاب چند دسته در یک تراکنش
- درگاه‌های نیازمند فرم پرداخت داخلی در سایت
- چندارزی بودن مستقل از ارز فعال WooCommerce
- ایجاد سامانه مالی جدا از WooCommerce

# Current Architecture

## Repository Boundary

```text
.
├── AGENTS.md
├── docs/
└── wp-content/
    ├── plugins/
    │   └── alavi-form-engine/
    └── themes/
        └── ostadsho-child/
```

مستندات canonical فقط در root `docs/` هستند. package-facing README/readme/third-party notice داخل plugin باقی مانده‌اند.

## 1. Alavi Form Engine

ورودی: `wp-content/plugins/alavi-form-engine/alavi-form-engine.php`  
Namespace: `BonyadAlavi\FormEngine`

Bootstrap:
- PSR-4 fallback داخلی همیشه ثبت می‌شود.
- Composer autoloader اختصاصی plugin در صورت وجود load می‌شود.
- critical bootstrap files/classes preflight می‌شوند.
- `Core\Plugin::instance()->boot()` در `plugins_loaded` اجرا می‌شود.

Top-level domains:
`Actions, Admin, Core, DataSource, Database, Duplicate, Elementor, Events, Export, Form, InputMask, Localization, Repository, Rest, Security, Style, Submission, Template, Validation`.

Flow اصلی:

```text
PHP Form Definition
 -> allowed Admin Overrides
 -> resolved runtime form
 -> Renderer / Elementor
 -> normalization + validation
 -> SubmissionService
 -> repositories/files/duplicate guard
 -> event/action/token pipeline
 -> admin/report/export
```

Shared submission tables برای workflow/reporting authority باقی می‌مانند؛ form می‌تواند mirror table اختصاصی داشته باشد.

مرجع فعلی: [MODULE-AFE](modules/alavi-form-engine.md)  
Extension API: [MODULE-AFE-EXTENSION](modules/alavi-form-engine-extension-api.md)

## 2. Child Theme

ورودی: `wp-content/themes/ostadsho-child/functions.php`

Child theme مالک integrationهای اختصاصی سایت است. از 2026-09-29 Source Definition فرم `jihadi-group-registration` نیز اینجا نگهداری و از hook عمومی `afe_register_forms` به Engine تزریق می‌شود؛ Engine دیگر این فرم پروژه‌ای را built-in ثبت نمی‌کند.

Composition شامل:
- front/cart/checkout/thank-you assets
- admin components و settings page
- Elementor widgets/dynamic tags
- participation WooCommerce services
- center settings/content services
- product media helpers/services
- project form definitions (`inc/forms/`)
- shortcodes و product FAQ

Subdirectories اصلی:
`inc/admin`, `inc/elementor`, `inc/helpers`, `inc/services`, `inc/shortcodes`, `inc/woocommerce`.

مرجع: [MODULE-THEME](modules/ostadsho-child-theme.md) و [MODULE-JIHADI-FORM](modules/jihadi-group-registration-form.md)

## 3. Theme Admin Settings

`BA_Settings_Page` registry تب‌ها را resolve می‌کند؛ `BA_Settings_Ajax_Controller` مسیر مشترک AJAX save است و `BA_Settings_Access_Service` access persistence را یکپارچه می‌کند.

Assetهای تب بر اساس active tab resolve‌شده و capability load می‌شوند، نه صرفاً raw URL parameter.

Admin Repeater یک component مشترک UI است و sanitize/persistence را مالک نمی‌شود.

مرجع: [MODULE-THEME-ADMIN](modules/theme-admin-settings.md) و [ADR-003](decisions/ADR-003-shared-theme-settings-persistence.md).

## 4. Product Media

`BA_Product_Media_Service` و `BA_Media_Helper` منبع/رندر رسانه محصول را مشترک می‌کنند. Participation widget و product-gallery widget منطق انتخاب تصویر را duplicate نمی‌کنند.

مرجع: [MODULE-THEME-MEDIA](modules/theme-product-media.md).

## 5. Participation Quick Checkout

Flow:

```text
select project amount
 -> validate
 -> inline WooCommerce billing fields
 -> validate standard hooks
 -> isolated project order
 -> configured gateway in isolated cart context
 -> redirect/payment result
```

Cart واقعی user نباید در order مشارکت وارد یا توسط gateway mutate شود.

مرجع: [MODULE-PARTICIPATION](modules/participation-payments.md) و [ADR-002](decisions/ADR-002-participation-quick-checkout.md).

## 6. Jihadi Center

Dashboard و Elementor هر دو content/query inputs دارند، اما `BA_Center_Settings_Service::get_effective_settings()` تنها resolver معتبر است و queryها از `BA_Content_Query_Service` می‌گذرند.

مرجع: [MODULE-JIHADI-CENTER](modules/jihadi-center.md) و [ADR-004](decisions/ADR-004-jihadi-center-source-precedence.md).

## External / Runtime Dependencies

- WordPress
- WooCommerce برای participation
- Elementor برای widget integrations
- Persian WooCommerce SMS به‌صورت optional AFE provider integration
- AFE Composer: PhpSpreadsheet 5.9.0، ZipStream 3.2.2، tc-lib-pdf 8.73.6
- JalaliDatePicker browser assets به‌صورت local

## Architecture History

تاریخچه legacy که قبلاً در build/handoff/version docs پراکنده بود در [state-v002](state/state-v002.md) خلاصه و index شده است.


## Mobile menu styling

Child theme asset `wp-content/themes/ostadsho-child/assets/css/alavi-mobile-menu.css` contains the responsive `.alavi-mobile-redesign` rules and is globally enqueued by `functions.php` using `filemtime` versioning.

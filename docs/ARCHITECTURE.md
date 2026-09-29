# Current Architecture

## Repository Boundary

Repository یک WordPress installation کامل نیست. ساختار اصلی track‌شده:

```text
.
├── AGENTS.md
├── docs/
└── wp-content/
    ├── plugins/
    │   └── alavi-form-engine/
    └── themes/
        ├── PATCH-MANIFEST.md
        └── ostadsho-child/
```

## Application Components

### 1. Alavi Form Engine (AFE)

ورودی plugin: `wp-content/plugins/alavi-form-engine/alavi-form-engine.php`

Namespace: `BonyadAlavi\FormEngine`

Composition root از PSR-4 fallback داخلی و Composer autoloader استفاده می‌کند، bootstrap preflight کلاس‌های حیاتی را چک می‌کند و سپس `Core\Plugin` را در `plugins_loaded` boot می‌کند.

لایه‌های اصلی در `src/` شامل این domainها هستند: Actions، Admin، Core، DataSource، Database، Duplicate، Elementor، Events، Export، Form/Forms، InputMask، Localization، Repository، Rest، Security، Style، Submission، Template و Validation.

Flow عمومی فرم:

```text
Code Form Definition
  -> resolved form + allowed admin overrides
  -> Renderer / Elementor adapter
  -> server-side validation + normalization
  -> SubmissionService
  -> repositories / files / duplicate guard
  -> actions + events + tokens
  -> admin/report/export surfaces
```

Shared AFE tables در مستند معماری داخلی شامل forms/submissions/values/files/notes/audit/action logs/action once/fingerprints و جداول geography هستند. فرم می‌تواند dedicated mirror table داشته باشد ولی shared submission data برای workflow و reporting نقش authority را حفظ می‌کند.

Export subsystem در `src/Export` از PhpSpreadsheet/ZipStream و tc-lib-pdf استفاده می‌کند. Composer vendor در repository commit نشده و export runtime برای جلوگیری از collision با vendor افزونه‌های دیگر hardening اختصاصی دارد.

جزئیات: [MODULE-AFE](modules/alavi-form-engine.md)

### 2. Ostadsho Child Theme

ورودی اصلی: `wp-content/themes/ostadsho-child/functions.php`

این فایل assetهای child theme/WooCommerce را enqueue و subsystemهای زیر را load می‌کند:

- admin components و settings page/AJAX
- Elementor widgets و dynamic tags
- participation WooCommerce services/quick checkout
- center settings service
- shortcodes و product FAQ

کد theme در `inc/admin`, `inc/elementor`, `inc/helpers`, `inc/services`, `inc/shortcodes`, `inc/woocommerce` تفکیک شده است.

### 3. Participation Quick Checkout

ویجت مشارکت، product/amount را validate می‌کند، billing fields ووکامرس را inline آماده می‌کند، order مستقل پروژه را می‌سازد و gateway انتخاب‌شده را اجرا می‌کند. Cart واقعی کاربر نباید وارد این order یا توسط gateway تغییر کند. جزئیات: [MODULE-PARTICIPATION](modules/participation-payments.md) و [ADR-002](decisions/ADR-002-participation-quick-checkout.md).

### 4. Jihadi Center

صفحه مرکز حرکت‌های مردمی و جهادی توسط Elementor widget و service/settings مشترک theme مدیریت می‌شود. Content/query settings از sourceهای Dashboard/Elementor resolve می‌شوند و queryهای news/media از service مشترک عبور می‌کنند. جزئیات: [MODULE-JIHADI-CENTER](modules/jihadi-center.md).

## External / Runtime Dependencies

- WordPress
- WooCommerce برای participation flow
- Elementor برای widget integration
- Persian WooCommerce SMS به‌صورت optional integration در AFE
- Composer packages AFE: PhpSpreadsheet 5.9.0، ZipStream 3.2.2، tc-lib-pdf 8.73.6
- JalaliDatePicker assets باید local باشند؛ AFE runtime CDN registration را ممنوع می‌کند.

## Documentation Boundaries

- این فایل فقط architecture جاری بین ماژول‌ها را نگه می‌دارد.
- جزئیات AFE: `wp-content/plugins/alavi-form-engine/docs/ARCHITECTURE.md` و `docs/PROJECT_STATE.md`
- جزئیات theme: `wp-content/themes/ostadsho-child/docs/handoff.md` و `docs/versions/`
- چرایی تصمیم‌های مهم: ADRها
- evolution زمانی: state history

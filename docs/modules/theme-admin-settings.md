# MODULE-THEME-ADMIN — Theme Admin Settings and Repeater Infrastructure

**Path:** `wp-content/themes/ostadsho-child/inc/admin/`  
**Status:** active

## Main Components

- `settings/class-ba-settings-page.php`
- `settings/class-ba-settings-ajax-controller.php`
- `settings/class-ba-settings-access-service.php`
- `settings/class-ba-center-settings-tab.php`
- `settings/tab-participation.php`
- `settings/tab-access-management.php`
- `components/class-ba-admin-repeater-component.php`

## Settings Registry and Persistence

Contract: [ADR-003](../decisions/ADR-003-shared-theme-settings-persistence.md).

Tab registry می‌تواند این metadata را داشته باشد:
- `option_name`
- `option_group`
- `ajax_save_callback` برای persist اختصاصی
- `assets_callback`
- `save_label`

Shared AJAX controller:
1. nonce را validate می‌کند.
2. tab را از registry resolve می‌کند.
3. capability همان tab را بررسی می‌کند.
4. برای Settings API همان sanitize callback ثبت‌شده را مصرف می‌کند، یا callback اختصاصی را اجرا می‌کند.
5. JSON success/error یکسان برمی‌گرداند.

Classic `options.php` / `admin-post.php` fallback حفظ شود.

### Save Bar / Dirty State

UI مشترک `ba-settings-savebar` وضعیت‌های pristine/dirty/saving/success/error را مدیریت می‌کند. Feature نباید save button مستقل در انتهای tab بسازد.

قبل از FormData، editorهایی مثل TinyMCE sync شوند. Repeater، Media Picker و controls باید تغییر را به dirty-state مشترک منتقل کنند.

## Tab Assets

از v0.5.4 legacy:
- asset tab بر اساس tab resolve‌شده/capability load شود.
- code به raw `$_GET['tab']` وابسته نباشد.
- ورود بدون query `tab` نیز asset tab پیش‌فرض قابل دسترس user را load کند.
- `assets_callback` feature مصرف‌کننده، component assets را enqueue کند.
- metaboxهای مستقل مانند Product FAQ می‌توانند بر اساس current screen عمل کنند.

## Shared Admin Repeater

Contract: [ADR-005](../decisions/ADR-005-shared-admin-repeater.md).

Responsibilities:
- BEM wrapper
- row template/index
- add/remove
- move up/down
- empty state
- focus first new control
- common CSS/JS
- event `ba:admin-repeater:change`
- helper `field_name()` برای nested names

Not responsibilities:
- sanitize
- business validation
- persistence

این موارد در feature/service مالک داده انجام می‌شوند.

Current consumers:
- Product FAQ metabox
- Jihadi Center stats
- System Cards
- Partners
- Center FAQ

Elementor از `Elementor\Repeater` خودش استفاده می‌کند.

## Change Safety

هر تغییر component/shared settings باید حداقل مصرف‌کننده‌های بالا، capability محدود، URL بدون `tab` و progressive fallback را در نظر بگیرد.

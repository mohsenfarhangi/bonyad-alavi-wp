# MODULE-JIHADI-CENTER — Jihadi Center Page

**Path:** `wp-content/themes/ostadsho-child/`  
**Status:** implemented and evolved through v0.5.x line

## Purpose

مدیریت صفحه «مرکز حرکت‌های مردمی و جهادی» با Elementor widget و تنظیمات dashboard بنیاد علوی، شامل Hero، آمار، معرفی/system cards، اخبار، چندرسانه‌ای، partners و FAQ.

## Main Components

- Elementor widgetهای theme زیر `inc/elementor/`
- `inc/services/class-ba-center-settings-service.php`
- `inc/admin/settings/class-ba-center-settings-tab.php`
- content query service/helperهای مرتبط
- front-end assets widget

## Data Resolution Contract

- settings مؤثر از service مشترک ساخته می‌شوند.
- Dashboard و Elementor هر دو source دارند؛ بعد از ذخیره schema جدید، مقدار dashboard برای key متناظر اولویت دارد و مقدار خالی می‌تواند انتخاب معتبر باشد.
- Hero background fallback رسانه‌ای خاص خود را دارد.
- query اخبار/چندرسانه‌ای از service مشترک query عبور می‌کند.
- style controls متعلق به Elementor هستند؛ data/content precedence نباید با style ownership مخلوط شود.
- section enable/disable در هر دو source پشتیبانی می‌شود.
- system cardها compatibility/field-level resolution ویژه دارند.
- SVG inline فقط از helper sanitizeشده theme عبور می‌کند.
- performance behavior شامل reveal و content-visibility برای بخش‌های پایین است؛ Hero eager می‌ماند.
- responsive Hero/stats/image aspect/text style controls در v0.5.x مستند شده‌اند.

## Related Docs

مرجع اصلی:
`../../wp-content/themes/ostadsho-child/docs/handoff.md`

Version details در صورت نیاز:
`../../wp-content/themes/ostadsho-child/docs/versions/v0.3.0.md` تا `v0.5.9.md`.

برای task جدید، همه version files را یکجا نخوان؛ ابتدا handoff و سپس فقط version مرتبط را باز کن.

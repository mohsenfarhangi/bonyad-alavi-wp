# ADR-004 — Central two-source resolution for Jihadi Center settings

**Status:** Accepted  
**Date recorded:** 2026-09-29  
**Decision originated:** theme feature line v0.3.0 → v0.4.1

## Context

محتوا و Query صفحه مرکز هم از Dashboard و هم Elementor قابل تنظیم است. Merge پراکنده داخل widget باعث رفتار مبهم، شکستن داده legacy و اختلاف بین بخش‌ها می‌شد.

## Decision

- `BA_Center_Settings_Service::get_effective_settings()` تنها مسیر معتبر ساخت تنظیمات مؤثر است.
- تا قبل از ذخیره key در schema جدید، Elementor می‌تواند منبع باشد.
- بعد از ذخیره Dashboard schema، مقدار Dashboard برای key متناظر اولویت دارد؛ مقدار خالی هم انتخاب معتبر است.
- Hero background یک fallback رسانه‌ای خاص دارد: Dashboard image معتبر → Elementor image → default theme image.
- داده legacy قبل از schema 0.4.0 برای Partners/FAQ با compatibility path خوانده می‌شود.
- System Cards به دلیل migration تاریخی field-by-field resolve می‌شوند؛ مقدار معتبر Dashboard اولویت دارد و field خالی می‌تواند از Elementor fallback بگیرد.
- Query اخبار/چندرسانه‌ای فارغ از source فقط توسط `BA_Content_Query_Service` ساخته می‌شود.
- Style controls متعلق به Elementor هستند؛ content/data/query precedence نباید با style ownership merge شود.
- هر هفت section اصلی enable switch مستقل دارند.

## Consequences

- widget نباید precedence موازی یا مستقیم برای settingها بسازد.
- تغییر schema باید backward compatibility را صریحاً تعریف کند.
- empty value در schema جدید لزوماً «fallback کن» معنی نمی‌دهد.
- query args مشترک خارج `BA_Content_Query_Service` تکرار نشوند.

## Related Module

- [MODULE-JIHADI-CENTER](../modules/jihadi-center.md)

## Relevant Files

- `../../wp-content/themes/ostadsho-child/inc/services/class-ba-center-settings-service.php`
- `../../wp-content/themes/ostadsho-child/inc/services/class-ba-content-query-service.php`
- `../../wp-content/themes/ostadsho-child/inc/elementor/widgets/class-bonyad-alavi-jihadi-center-widget.php`

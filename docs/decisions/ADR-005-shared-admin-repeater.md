# ADR-005 — Shared wp-admin Repeater component

**Status:** Accepted  
**Date recorded:** 2026-09-29  
**Decision originated:** theme feature line v0.3.1

## Context

FAQ محصول و تنظیمات مرکز چند Repeater سفارشی داشتند و Add/Remove/Move/template/index logic در JavaScript و markup تکرار می‌شد.

## Decision

- custom wp-admin repeaters از `BA_Admin_Repeater_Component` و asset مشترک استفاده کنند.
- component مسئول wrapper/BEM، template row، index، Add/Remove/Move، empty state، focus و event عمومی است.
- نام nested field ترجیحاً با `BA_Admin_Repeater_Component::field_name()` ساخته شود.
- پس از تغییر، event عمومی `ba:admin-repeater:change` منتشر می‌شود.
- component مالک sanitize یا persistence نیست؛ feature/service مربوطه data را validate/store می‌کند.
- fieldهای داخل row کلاس BEM خود feature را حفظ می‌کنند.
- Elementor repeaters از `Elementor\Repeater` استفاده می‌کنند و نباید به این component wp-admin تبدیل شوند.

## Consequences

- feature جدید JavaScript جدا برای Add/Remove/Move Repeater نسازد.
- behavior feature-specific مثل Media Picker می‌تواند روی event/component سوار شود.
- تغییر component باید مصرف‌کننده‌های فعلی FAQ محصول، stats، system cards، partners و FAQ مرکز را در نظر بگیرد.

## Related Module

- [MODULE-THEME-ADMIN](../modules/theme-admin-settings.md)

## Relevant File

- `../../wp-content/themes/ostadsho-child/inc/admin/components/class-ba-admin-repeater-component.php`

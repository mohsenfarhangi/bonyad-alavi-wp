# Alavi Form Engine 1.0.28 — Feature Completeness Matrix

این سند وضعیت تصمیم‌های اصلی handoff نسخه 1.0.28 را قبل از acceptance مرورگری ثبت می‌کند. وضعیت **Implemented** یعنی فیچر در سورس وجود دارد و regression خودکار متناظر دارد؛ جایگزین تست عملی WordPress/Elementor نیست.

| حوزه | وضعیت | پوشش اصلی |
|---|---|---|
| Event Registry + Label فارسی | Implemented | `EventRegistry`, Action Builder |
| Action Registry / schema-driven UI | Implemented | `ActionRegistry`, `ActionConfigSanitizer` |
| Token Registry/Resolver + Palette | Implemented | Email/Webhook/SMS/UI |
| Action log / once / admin retry | Implemented | `ActionExecutionRepository`, `ActionManager` |
| SMS + MeliPayamak legacy/API | Implemented + real provider PASS | free/pattern, recipient sources |
| SMS Provider Router + Persian WooCommerce SMS | Implemented / pending live gateway acceptance | global default + per-Action override; reuses external gateway/settings without credential copy |
| Redirect | Implemented | first effective redirect, external opt-in |
| WordPress User Actions | Implemented + hardened | create/login/update/role/meta, privileged-target guards |
| Status / internal note | Implemented | follow-up status event |
| PDF / Email PDF | Implemented | Dompdf optional |
| Post/CPT create/update/upsert | Implemented | safe post type/status controls |
| Conditional Logic / execution policy | Implemented | required operators + always/once/first-event-cycle |
| Duplicate Policy | Implemented | block/reference/message/allow+mark + promotion |
| Tabbed FormsPage | Implemented | approved tab structure |
| Field ordering | Implemented | same Step + Repeater child scope |
| Date input modes | Implemented | Jalali/Gregorian combined/picker/manual |
| Character/length overrides | Implemented | backend authoritative + frontend UX |
| Input Mask Registry/UI | Implemented | presets + custom syntax + PHP normalization + Jihadi defaults |
| Field layout / override sidebar | Implemented | layout-only tab + click-to-edit Field Override in `afe-admin-side` + Repeater child support |
| Validator Registry/UI | Implemented | Iran validators + custom regex safeguards |
| Jihadi default SMS template | Implemented | `leader_mobile`, once, disabled/no hard-coded body |
| Elementor constructor compatibility | Implemented | native constructor regression |
| Local runtime assets | Implemented | JalaliDatePicker bundled, no runtime CDN registration |

## Release blockers intentionally remaining

1. Browser-driven acceptance on a real WordPress + MySQL/MariaDB + Elementor environment.
2. Verification of the actual Jihadi admin UX with the project configuration and saved overrides.
3. After acceptance PASS only: bump Plugin to `1.0.28`, DB to `1.0.5`, Stable tag to `1.0.28`, finalize changelog and build Production ZIP.

## Security hardening added before acceptance

- User actions cannot login/mutate an Administrator or another account with `manage_options` unless a user with `afe_manage_settings` explicitly enables the privileged-target option.
- `Update User Meta` and `Create User -> user_meta` reject WordPress capability/session/application-password meta keys at runtime.
- Assign Role treats custom roles with `manage_options` as privileged, not only the literal `administrator` role.
- Tokenized URL settings preserve valid `{{...}}` tokens during admin sanitization; final resolved URLs are still sanitized by their runtime action.


## Numeric input direction

Number-like controls (`tel`, `number`, `date`, and numeric/decimal/tel input modes) are rendered LTR and left-aligned in both frontend and admin editing while the surrounding form remains RTL.

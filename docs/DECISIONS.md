# Decision Overview

شرح کامل هر تصمیم در [decisions/INDEX.md](decisions/INDEX.md) است.

| ID | موضوع | خلاصه |
|---|---|---|
| [ADR-001](decisions/ADR-001-code-defined-forms.md) | AFE form schema | PHP definition منبع پایه است و Admin فقط override مجاز اعمال می‌کند. |
| [ADR-002](decisions/ADR-002-participation-quick-checkout.md) | Participation payment | Quick Checkout order/cart از Cart واقعی user جدا می‌ماند. |
| [ADR-003](decisions/ADR-003-shared-theme-settings-persistence.md) | Theme settings | ثبت/ذخیره تب‌های تنظیمات از registry و shared AJAX controller عبور می‌کند و fallback کلاسیک حفظ می‌شود. |
| [ADR-004](decisions/ADR-004-jihadi-center-source-precedence.md) | Jihadi center | Dashboard/Elementor فقط از resolver مرکزی با precedence تعریف‌شده merge می‌شوند. |
| [ADR-005](decisions/ADR-005-shared-admin-repeater.md) | Admin Repeater | UI Repeater مشترک است؛ sanitize/persist متعلق به feature است. |
| [ADR-006](decisions/ADR-006-afe-export-dependency-isolation.md) | AFE export | PDF/XLSX package-managed هستند و Excel dependencies در برابر Composer collision ایزوله می‌شوند. |
| [ADR-007](decisions/ADR-007-project-form-ownership.md) | Project form ownership | Engine عمومی فرم پروژه‌ای built-in ندارد؛ Source Definition فرم جهادی در child theme ثبت می‌شود. |

ADR جدید فقط برای تصمیم معماری/contract ماندگار ایجاد شود؛ تغییر ظاهری جزئی وارد ADR نشود.

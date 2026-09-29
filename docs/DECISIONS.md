# Decision Overview

این فایل فقط router کوتاه تصمیم‌هاست. شرح کامل را از [decisions/INDEX.md](decisions/INDEX.md) و ADR مربوط بخوان.

| ID | موضوع | خلاصه |
|---|---|---|
| [ADR-001](decisions/ADR-001-code-defined-forms.md) | AFE form definitions | تعریف PHP فرم منبع پایه است؛ admin تغییرات مجاز را به‌صورت override اعمال می‌کند. |
| [ADR-002](decisions/ADR-002-participation-quick-checkout.md) | Participation payment | Quick Checkout order/cart را از Cart واقعی کاربر جدا نگه می‌دارد و gateway انتخاب‌شده را بدون fallback خاموش اجرا می‌کند. |

تصمیم‌های جدید فقط وقتی ADR می‌خواهند که معماری، contract بین subsystemها یا constraint مهم و ماندگار را تغییر دهند.

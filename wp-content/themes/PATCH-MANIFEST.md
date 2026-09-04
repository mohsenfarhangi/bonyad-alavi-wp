# Patch Manifest — ostadsho-child v0.2.1

## مبنا

این Patch باید روی نسخه `v0.2.0` قالب `ostadsho-child` اعمال شود.

## هدف

بهینه‌سازی Lazy Load در ویجت Elementor «تصویر محصول (کاروسل)».

## فایل‌های موجود در Patch

### ویرایش‌شده

- `ostadsho-child/inc/elementor/widgets/class-bonyad-alavi-product-gallery-widget.php`
- `ostadsho-child/docs/handoff.md`

### جدید

- `ostadsho-child/docs/versions/v0.2.1.md`

## رفتار جدید

- در کاروسل چندتصویری، تصویر اول با `loading="eager"` رندر می‌شود.
- از تصویر دوم به بعد `loading="lazy"` اعمال می‌شود.
- در حالت غیرکاروسل/تک‌تصویر رفتار قبلی `loading="lazy"` حفظ می‌شود.

## روش اعمال

محتویات پوشه `ostadsho-child` داخل این Patch را روی پوشه قالب `ostadsho-child` نسخه `v0.2.0` کپی و فایل‌های موجود را جایگزین کنید.

## Commit پیشنهادی

```text
perf: بهینه‌سازی بارگذاری تصاویر کاروسل محصول

- بارگذاری eager برای تصویر اول کاروسل
- فعال‌سازی lazy load از تصویر دوم به بعد
- حفظ رفتار قبلی برای حالت تک‌تصویر و کاروسل غیرفعال
```

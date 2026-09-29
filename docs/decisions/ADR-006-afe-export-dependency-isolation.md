# ADR-006 — Package-managed and isolated AFE export dependencies

**Status:** Accepted  
**Date recorded:** 2026-09-29  
**Decision originated:** AFE checkpoint 11 → 11.3

## Context

AFE باید PDF فارسی/RTL و XLSX واقعی تولید کند. روی سایت واقعی، pluginهای دیگر می‌توانند Composer autoloader و نسخه‌های متفاوت PhpSpreadsheet/ZipStream داشته باشند. یک collision واقعی باعث resolve شدن PhpSpreadsheet یک plugin با ZipStream plugin دیگر و TypeError در `ZipStream2` شد.

## Decision

- Excel از `phpoffice/phpspreadsheet 5.9.0` و `maennchen/zipstream-php 3.2.2` استفاده می‌کند.
- PDF از `tecnickcom/tc-lib-pdf 8.73.6` استفاده می‌کند.
- هیچ package downloader/CDN runtime برای export وجود ندارد؛ dependencies در build/deployment داخل `vendor/` نصب می‌شوند.
- `ExportAutoloadScope` هنگام XLSX export autoloaderهای Composer خارجی را موقتاً از resolve path کنار می‌گذارد و loader اختصاصی AFE را اولویت می‌دهد.
- Reflection باید verify کند کلاس‌های export واقعاً از `alavi-form-engine/vendor` آمده‌اند.
- اگر class متداخل قبلاً توسط plugin دیگر preload شده باشد، AFE class را replace نمی‌کند؛ export باید fail واضح با مسیر class متداخل کند، نه Fatal مبهم.
- status check نباید صرفاً با `class_exists()` باعث preload third-party class شود؛ فایل/package خود AFE بررسی شود.
- PDF font metadata برای DejaVu باید در build تولید شود؛ مسیر اصلی `vendor/tecnickcom/tc-lib-pdf-font/target/fonts/dejavu/dejavusans.json` است.
- binary download ابتدا temp file را کامل و معتبر می‌کند، سپس header/stream را ارسال می‌کند.

## Consequences

- `vendor/` در Git track نمی‌شود، اما release/deployment بدون Composer build معتبر نیست.
- برای export-related release از `AFE_REQUIRE_EXPORT_VENDOR=1 ./tools/qa.sh` و `tools/build-pdf-fonts.sh` استفاده شود.
- live acceptance باید XLSX را در حضور plugin دارای vendor مستقل و PDF فارسی را در runtime واقعی تست کند.

## Related Module

- [MODULE-AFE](../modules/alavi-form-engine.md)

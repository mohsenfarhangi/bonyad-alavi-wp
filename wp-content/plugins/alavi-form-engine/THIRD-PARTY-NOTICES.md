# Third-party notices

## Iran administrative divisions data

The optional remote geography importer reads the JSON distributions from:

- Project: `sajaddp/list-of-cities-in-Iran`
- Repository: `https://github.com/sajaddp/list-of-cities-in-Iran`
- Data files used: `provinces.json`, `counties.json`, `districts.json`
- Project license at the time this package was prepared: GNU GPL v3.0

The plugin contains an offline seed of the 31 province names/IDs so activation does not depend on network availability. County and district data can be refreshed from the WordPress admin database page.

The external dataset itself states that its data reflects official Iranian administrative division information through 1402/2023 and should not be used as a legal authority.


## JalaliDatePicker

Jalali date fields use `majidh1/JalaliDatePicker`:

- Project: `majidh1/JalaliDatePicker`
- Repository: `https://github.com/majidh1/JalaliDatePicker`
- Package: `@majidh1/jalalidatepicker` version `1.0.0`
- License: MIT
- The pinned front-end dist assets are bundled locally under `assets/vendor/jalalidatepicker/`; no runtime CDN request is required.

## Optional Persian WooCommerce SMS integration

Alavi Form Engine does **not** bundle or redistribute Persian WooCommerce SMS. If that plugin is independently installed and active, AFE can delegate SMS delivery through its public runtime API and currently selected gateway. No Persian WooCommerce SMS credential is copied into the AFE package or database settings.


## Export packages (1.0.28-dev)

- **PhpSpreadsheet 5.9.0** — `phpoffice/phpspreadsheet` — MIT License. Used for `.xlsx` generation.
- **tc-lib-pdf 8.73.6** — `tecnickcom/tc-lib-pdf` — GNU LGPL-3.0-or-later. Used for server-side PDF generation.
- `tc-lib-pdf` uses its companion package family, including `tc-lib-pdf-font`, for Unicode font metadata. Font assets retain their upstream licenses and are generated during the Composer build step; see the upstream font notices.

No export package is loaded from a CDN at runtime. Production/release builds must install Composer dependencies into the plugin `vendor` directory and generate the tc-lib PDF font metadata before packaging.

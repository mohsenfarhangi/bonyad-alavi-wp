# MODULE-AFE-EXTENSION — Alavi Form Engine Extension API

**Parent module:** [MODULE-AFE](alavi-form-engine.md)  
**Purpose:** canonical developer guide after removal of legacy `docs/DEVELOPER.md`.

## Register Forms

Hook:
`afe_register_forms`

Code-defined form DSL از `Form`, `Step` و field objects استفاده می‌کند. Source Definition authority باقی می‌ماند؛ Admin فقط supported override است.

نمونه الگو:

```php
add_action('afe_register_forms', function ($registry) {
    $registry->register(
        Form::make('example-form')
            ->title('فرم نمونه')
            ->steps([
                Step::make('identity', 'اطلاعات')
                    ->fields([
                        TextField::make('name')->label('نام')->required(),
                    ]),
            ])
    );
});
```

## Repeater and Conditions

`RepeaterField` child fields، min/max و nested validation دارد. Admin ordering فقط در همان Repeater است.

Conditional fields از condition metadata استفاده می‌کنند؛ server-side validation باید field active state را رعایت کند.

## Data Sources

Built-in source styles شامل static، posts، taxonomy، users، REST و geo هستند. dependent sourceها با parent field قابل تعریف‌اند.

Custom source hook:
`afe_register_data_sources`

Source callback باید data را resolve کند؛ UI نباید مستقیم business query duplicate بسازد.

## Actions

ترجیح جدید:
- `afe_register_action_definitions` برای handler + UI metadata
- `afe_register_actions` برای legacy/runtime compatibility

Custom action باید `ActionInterface` را implement کند. برای chain-scoped output از `$context->runtime` استفاده شود؛ submitted data را برای انتقال transient state mutate نکن.

`ActionDefinition` قابلیت‌هایی مثل retry و execution policy support را مشخص می‌کند. اگر `supportsExecutionPolicy=false` باشد، Admin builder مقدار `always` را enforce می‌کند.

User-related custom behavior نباید guardهای protected users/meta را دور بزند.

## Events

Developer listener:
`afe_register_events`

Event definitions نیز از registry canonical استفاده می‌کنند. Core event/action pipeline WordPress-compatible hooks را dispatch می‌کند.

## Templates

Hook:
`afe_register_templates`

Core template surfaces:
- form
- preview
- step

Code default با `Form::template()`, `Form::previewTemplate()`, `Step::template()` تعریف می‌شود. Admin فقط override متفاوت را persist می‌کند.

Core tokens:
- `{{title}}`
- `{{description}}`
- `{{brand_mark}}`
- `{{slogan}}`
- `{{progress}}`
- `{{steps}}`
- preview tokens
- `{{field:field_name}}`

Token field فقط برای field واقعی همان form resolve شود.

## Validators

Hook:
`afe_register_validator_definitions`

Reusable validator جدید به registry اضافه شود؛ branch hard-coded در Admin page نساز.

Legacy `->rule(...)` برای backward compatibility باقی است.

Custom Regex admin path:
- capability `afe_manage_settings`
- بدون delimiters
- max 500 chars
- flags فقط `i,m,s,u,x`
- compile test
- error message mandatory
- runtime PCRE limits

## Input Masks

Hook:
`afe_register_input_mask_definitions`

DSL:
- `inputMask('preset')`
- `customInputMask('AA-9999')`

Syntax:
- 9 digit
- A Unicode letter
- * letter/digit
- backslash escape

Mask validator نیست. Save path باید دوباره normalize کند.

## Date Modes

Examples:
- `jalali()` + combined default
- `gregorian()->pickerOnly()`
- `jalali()->manualOnly()`

Jalali format `YYYY/MM/DD`; Gregorian `YYYY-MM-DD`.

## Select UI

- `custom()`
- `native()`
- `searchable()`
- `searchThreshold(n)`

Native select source of truth submission/validation باقی می‌ماند.

## Style Isolation

`Form::styleIsolation('strong|default|disabled')`

Strong برای themeهای دارای global form CSS مناسب است. Global + per-form override وجود دارد.

## Per-form Capabilities

Generated operation scopes:
- view
- edit
- manage
- export
- reports
- configure

بعد از `afe_register_forms` sync می‌شوند. فرم جدید بعد از migration پیش‌فرض Administrator-only است تا grant صریح انجام شود.

## Brand Mark

- `brandMark('default')`
- custom image API
- hide brand mark

Template token: `{{brand_mark}}`.

## Custom Storage

Form می‌تواند storage setting اختصاصی مثل dedicated mirror table داشته باشد. Shared tables همچنان workflow/audit/report authority را حفظ می‌کنند؛ custom storage نباید lifecycle مرکزی را دور بزند.

## REST API

Known routes:
- `GET /forms`
- `GET /forms/{slug}`
- `POST /forms/{slug}/submit` — `afe_api_submit`
- `GET /submissions`
- `GET /submissions/{id}`
- `PATCH /submissions/{id}`

برای external integration از WordPress-compatible auth مثل Application Passwords استفاده شود.

## Elementor

Integration surfaces:
- «فرم علوی» widget
- «Alavi Form» text dynamic tag

در Elementor Widget constructor DI جدید اضافه نکن مگر lifecycle Elementor کاملاً بررسی شده باشد؛ legacy project state این الگو را source یک failure قبلی ثبت کرده است.

## SMS Provider Extensions

Hook:
`afe_register_sms_providers`

Persian WooCommerce SMS extension filters:
- `afe_pwsms_supported_modes`
- `afe_pwsms_pattern_strategy`
- `afe_pwsms_pattern_payload`
- `afe_pwsms_send_data`

Unknown pattern strategy باید fail-closed باشد. Hookها فقط code-level هستند؛ raw executable admin config مجاز نیست.

## Export Profiles

`Form::exports()` Source Definition default می‌دهد. `ExportProfile::resolve()` code default + Admin override را merge می‌کند.

PDF:
- sanitized HTML/CSS/tokens
- no remote resources/raw PHP

Excel:
- structured columns/settings
- no uploaded executable/template logic

Release build باید Composer vendor و PDF font metadata را قبل package آماده کند.

## Security Rules

- `afe_manage_settings` را به role غیرقابل اعتماد نده؛ per-form custom JS executable frontend دارد.
- raw PHP از Admin اجرا نشود.
- upload MIME/file checks دور زده نشوند.
- frontend validation فقط UX است؛ PHP final authority.
- tokenized URL پس از runtime resolution validate شود.

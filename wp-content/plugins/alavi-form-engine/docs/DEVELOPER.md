# Developer Guide

## 1. Register a form

Create the form in a separate site plugin:

```php
use BonyadAlavi\FormEngine\Form\Form;
use BonyadAlavi\FormEngine\Form\Step;
use BonyadAlavi\FormEngine\Form\Fields\TextField;
use BonyadAlavi\FormEngine\Form\Fields\HtmlBlock;

add_action('afe_register_forms', function ($registry) {
    $registry->register(
        Form::make('example-form')
            ->title('فرم نمونه')
            ->steps([
                Step::make('identity', 'اطلاعات')
                    ->fields([
                        HtmlBlock::make('<div class="my-card">HTML آزاد بین فیلدها</div>'),
                        TextField::make('name')->label('نام')->required(),
                    ])
                    ->template('<div class="custom-grid">{{field:name}}</div>'),
            ])
    );
});
```

The admin can change allowed labels/options/required values and templates without mutating this definition.

## 2. Repeater

```php
RepeaterField::make('people')
    ->label('افراد')
    ->min(1)
    ->max(20)
    ->fields([
        TextField::make('full_name')->label('نام')->required(),
        TelField::make('mobile')->label('موبایل'),
    ]);
```

Admin override path for the nested field is `people.full_name`.

## 3. Conditional logic

```php
TextField::make('other')
    ->label('سایر')
    ->condition([
        ['field' => 'type', 'operator' => '=', 'value' => 'other'],
    ], 'show')
    ->required();
```

Supported comparison operators in v1:

`=`, `!=`, `>`, `>=`, `<`, `<=`, `in`, `contains`, `empty`, `not_empty`.

Effects:

`show`, `hide`, `required`, `optional`.

Server validation uses the same visibility conditions for show/hide.

## 4. Data Sources

Static:

```php
SelectField::make('type')->source([
    'type' => 'static',
    'options' => ['a' => 'A', 'b' => 'B'],
]);
```

WordPress posts:

```php
->source(['type' => 'posts', 'post_type' => 'project', 'limit' => 100])
```

Taxonomy:

```php
->source(['type' => 'taxonomy', 'taxonomy' => 'project_category'])
```

Users:

```php
->source(['type' => 'users', 'limit' => 200])
```

Database:

```php
->source([
    'type' => 'database',
    'table' => '{prefix}my_table',
    'value_column' => 'id',
    'label_column' => 'title',
])
```

REST:

```php
->source([
    'type' => 'rest',
    'url' => 'https://example.com/api/items',
    'value_key' => 'id',
    'label_key' => 'name',
])
```

Custom source:

```php
add_action('afe_register_data_sources', function ($sources) {
    $sources->register('my_source', function (array $context) {
        return ['1' => 'Option one'];
    });
});

SelectField::make('item')->source('my_source');
```

Iran cascading geography:

```php
SelectField::make('province')
    ->source(['type' => 'geo', 'level' => 'province']);

SelectField::make('county')
    ->source(['type' => 'geo', 'level' => 'county', 'parent' => 'province'])
    ->dependsOn('province');

SelectField::make('district')
    ->source(['type' => 'geo', 'level' => 'district', 'parent' => 'county'])
    ->dependsOn('county');
```

## 5. Custom action

AFE 1.0.28-dev introduces `ActionRegistry` / `ActionDefinition`. New developer actions should register both their handler and UI metadata so the Action Builder can render the action without hard-coded `FormsPage` logic.

```php
use BonyadAlavi\FormEngine\Actions\ActionDefinition;
use BonyadAlavi\FormEngine\Actions\ActionInterface;
use BonyadAlavi\FormEngine\Actions\ActionContext;

final class CrmAction implements ActionInterface
{
    public function handle(ActionContext $context, array $config = []): void
    {
        // Send to CRM. Throw an exception on failure; AFE logs it and does not
        // roll back an already successful Submission.
    }
}

add_action('afe_register_action_definitions', function ($registry) {
    $registry->register(
        new ActionDefinition(
            'crm',
            'ارسال به CRM',
            'ارسال ثبت فرم به CRM سازمان.',
            'integration',
            [
                'pipeline' => [
                    'type' => 'text',
                    'label' => 'Pipeline',
                    'required' => true,
                    'tokens' => false,
                ],
            ]
        ),
        new CrmAction()
    );
});
```

Actions executed by the manager receive an `ActionRuntime` in their context. Core actions use this runtime for chain-scoped outputs such as resolved/created `user_id`, `post_id`, updated status and first-wins redirect. A developer action may read or write runtime values through `$context->runtime`; do not rewrite `$context->data` to pass transient values between actions. Failed registered actions are logged and can participate in the administrative retry workflow unless their `ActionDefinition` sets `supportsRetry` to `false`.

The older runtime-only hook remains compatible:

```php
add_action('afe_register_actions', function ($actions) {
    $actions->register('crm', new CrmAction());
});
```

The legacy hook can execute the action, but it does not provide rich UI schema metadata unless a matching `ActionDefinition` was registered.

In the form definition, use a stable `action_key` and canonical event key:

```php
->actions([
    [
        'action_key' => 'crm_high_priority_submit',
        'type' => 'crm',
        'on' => ['submission.submitted'],
        'execution_policy' => 'once_per_submission',
        'on_error' => 'continue',
        'when' => [
            ['field' => 'priority', 'operator' => '=', 'value' => 'high'],
        ],
        'config' => ['pipeline' => 'groups'],
    ],
]);
```

Execution policies currently supported by the backend are `always`, `once_per_submission`, and `first_in_cycle`. Conditional operators are `=`, `!=`, `>`, `>=`, `<`, `<=`, `in`, `contains`, `empty`, and `not_empty`.

`ActionDefinition::supportsExecutionPolicy=false` is honored by the admin builder: UI-saved definitions are forced to `always`. Trusted code definitions may still be authored explicitly in PHP.

Core WordPress User actions contain an additional runtime privilege boundary. Login/update/role/meta operations targeting an Administrator or any account with `manage_options` require the protected opt-in saved by a user with `afe_manage_settings`. Capability/session/application-password user-meta keys are rejected even if a request bypasses the admin UI. If a developer genuinely needs to manipulate those internals, implement a reviewed code-level custom action rather than exposing them through Action Builder.

Text actions should use the shared TokenResolver. Core tokens include `{{tracking_code}}`, `{{submission_id}}`, `{{form_title}}`, `{{form_slug}}`, `{{status}}`, and concrete `{{field:field_name}}` tokens. Field tokens are whitelisted against the resolved form definition.

## 6. Events and listeners

Canonical registry event keys currently include:

```text
submission.created
submission.draft_saved
submission.submitted
submission.updated
submission.status_changed
submission.locked
submission.unlocked
edit_request.created
edit_request.approved
edit_request.rejected
submission.trashed
submission.restored
```

The original typed `SubmissionCreated` / `SubmissionUpdated` events remain dispatched for compatibility. New action definitions and integrations should prefer canonical string keys.

```php
add_action('afe_register_events', function ($events) {
    $events->listen('submission.submitted', function (int $submissionId, string $formSlug, array $data, array $row) {
        // Listener logic.
    });
});
```

Canonical string events are bridged to WordPress hooks such as `afe_event_submission_submitted`.

## 7. Custom storage

```php
Form::make('large-form')
    ->settings(['storage' => 'dedicated']);
```

The engine keeps the shared submission record for workflow/audit and mirrors the data JSON to a dedicated form table.

## 8. REST API

The API is registered only after enabling it in admin settings.

Namespace:

`/wp-json/alavi-form-engine/v1`

Routes in v1:

- `GET /forms`
- `GET /forms/{slug}`
- `POST /forms/{slug}/submit` — requires `afe_api_submit`
- `GET /submissions`
- `GET /submissions/{id}`
- `PATCH /submissions/{id}`

Submission endpoints require WordPress authentication and matching capabilities. Public form schemas are separately configurable. For multipart submissions, current AFE front-end upload controls are normalized to independent keys such as `afe_upload_leader_photo[]`. The server also accepts the legacy nested `afe_files[field][]` shape for backward compatibility. JSON-only submissions to forms with required uploads correctly fail file validation.

## 9. Elementor

When Elementor is active, the engine registers:

- **فرم علوی** widget
- **Alavi Form** text dynamic tag

The widget renders the same server-side renderer used by the shortcode.

## 10. Security notes

- Do not grant `afe_manage_settings` to untrusted roles because per-form custom JavaScript is executable on the front-end.
- Raw PHP is intentionally not executable from the admin.
- Never disable MIME/file checks in custom upload adapters.
- Prefer authenticated REST integrations using WordPress Application Passwords or another WordPress-compatible authentication layer.


## Select UI

`SelectField` is isolated from global theme Select2 initializers. AFE uses its own custom combobox UI by default, scoped to `.afe-form`; the underlying native select remains the submitted control.

```php
SelectField::make('city')->custom();
SelectField::make('simple')->native();
SelectField::make('large_source')->searchable()->searchThreshold(5);
```

## Style isolation

AFE scopes its design tokens and form reset to `.afe-shell`. The global mode is configurable from **فرم‌های علوی → تنظیمات و دسترسی → ایزوله‌سازی استایل فرم**.

Modes:

- `strong` — recommended when the active theme applies global form/input/button rules; adds a defensive scoped reset and a limited `!important` component defense layer.
- `default` — safe normalization (box sizing, control typography, label/form normalization) without the stronger defense rules.
- `disabled` — skips AFE reset/isolation while keeping normal AFE component styles.

A form can override the global setting from the form admin screen or in code:

```php
Form::make('example')
    ->title('Example')
    ->styleIsolation('strong');
```

Per-form custom CSS is still emitted after the AFE stylesheet. In `strong` mode, properties protected with defensive `!important` rules should be overridden intentionally with equally-important form-specific rules when required.

## 11. Locale-aware admin dates

AFE keeps submission timestamps in UTC Gregorian MySQL format. `LocaleDateService` is responsible for presentation and filter boundaries:

- site locale starts with `fa` → Jalali admin display and JalaliDatePicker filter inputs;
- other locales → Gregorian display via WordPress `wp_date()` and native Gregorian date inputs;
- filter dates are interpreted in `wp_timezone()` and converted to UTC before database queries.

Explicit `DateField::jalali()` / `gregorian()` still defines the field's storage calendar. Admin presentation converts between that storage calendar and the site calendar without changing stored data unless an authorized admin edits and saves the field.

## 12. Per-form capabilities

Every registered form receives six dynamic capabilities. For a form slug `example-form` they are:

```text
afe_form_example_form_view
afe_form_example_form_edit
afe_form_example_form_manage
afe_form_example_form_export
afe_form_example_form_reports
afe_form_example_form_configure
```

The static/global AFE capability is still required for the corresponding operation. The per-form capability narrows that permission to specific forms.

Permission levels:

- `view` — list/read submissions for the form;
- `edit` — edit submission data;
- `manage` — form-level gate for status, notes and file-management operations (the matching global operation capability is also checked);
- `export` — Excel / print / PDF access;
- `reports` — inclusion in Reports & Analytics queries;
- `configure` — form definition overrides/admin configuration.

Dynamic capabilities are synchronized after all `afe_register_forms` callbacks have run. On the first migration, existing forms inherit legacy role access to avoid a breaking permission change. Forms registered later default to Administrator-only and must be granted explicitly from **فرم‌های علوی → تنظیمات و دسترسی**.

## Per-form brand mark

The standard AFE header can use the built-in mark, a custom image, or no mark. Admin overrides are available on the Forms screen and take precedence over code defaults.

```php
Form::make('example')
    ->brandMark('default');

Form::make('example-image')
    ->brandMarkImage('https://example.test/wp-content/uploads/form-mark.png', 'Form mark');

Form::make('example-clean')
    ->hideBrandMark();
```

Custom form templates can place the resolved markup with `{{brand_mark}}`.

## Template registry and defaults

AFE 1.0.27 centralizes editable HTML templates. The code definition remains the source of truth: a template defined through `Form::template()`, `Form::previewTemplate()` or `Step::template()` is treated as that form/step default. The admin editor displays that default source with tokens and only persists an override when the submitted HTML is different.

Core form template tokens:

```text
{{title}}
{{description}}
{{brand_mark}}
{{slogan}}
{{progress}}
{{steps}}
```

Core preview tokens:

```text
{{title}}
{{description}}
{{preview_title}}
{{preview_description}}
{{preview_fields}}
{{field:field_name}}
```

Step tokens:

```text
{{items}}
{{field:field_name}}
```

Extensions may register future definitions without modifying the core registry:

```php
use BonyadAlavi\FormEngine\Template\TemplateDefinition;

add_action('afe_register_templates', function ($templates) {
    $templates->register(new TemplateDefinition(
        'my-template',
        'قالب سفارشی من',
        'توضیح قالب',
        '<div>{{content}}</div>',
        ['{{content}}']
    ));
});
```

Do not execute PHP from template HTML. Dynamic values must be exposed through explicitly supported tokens.


## Field ordering overrides (1.0.28-dev)

Field ordering is an **admin override**, not a mutation of the PHP source definition. The code definition remains authoritative for membership of each Step/Repeater:

- top-level Fields and `HtmlBlock` items can be reordered only inside their original Step;
- Repeater children can be reordered only inside the same Repeater;
- moving a Field between Steps is intentionally unsupported in this version;
- if a later code release adds a new item that is absent from an old saved order, the resolver appends it to the end of that same scope.

Do not use ordering overrides to model conditional Step membership. Put structural membership in the PHP definition and use Conditions for runtime visibility.

## Date input modes

`DateField` supports three input modes while `calendar()` continues to define the authoritative storage/validation calendar:

```php
DateField::make('birth_date')->jalali();                    // combined: picker + manual
DateField::make('appointment')->gregorian()->pickerOnly();  // picker only
DateField::make('legacy_date')->jalali()->manualOnly();     // manual only
```

Masks are UX hints:

- Jalali: `YYYY/MM/DD`
- Gregorian: `YYYY-MM-DD`

Server-side validation is always authoritative. Do not rely on `pattern`, input masks, or the datepicker as a security boundary.

## Input Mask Registry

Input masks are Registry-based and apply to `text` / `tel` fields. The value shown to the applicant may contain separators, but `SubmissionService` normalizes it before validators, duplicate fingerprints, tokens/actions and persistence. Therefore a mobile displayed as `0912 345 6789` is stored as `09123456789`.

Code-defined presets:

```php
TelField::make('mobile')->inputMask('mobile_ir');
TextField::make('national_id')->inputMask('national_id_ir');
TextField::make('card')->inputMask('bank_card_ir');
TextField::make('custom_code')->customInputMask('AA-9999');
```

Core keys:

```text
mobile_ir
landline_ir
national_id_ir
postal_code_ir
bank_card_ir
iban_digits_ir
```

Custom mask syntax is deliberately non-executable:

- `9` = digit
- `A` = Unicode letter
- `*` = Unicode letter or digit
- all other characters are display literals/separators
- `\` escapes the next token character when a literal `9`, `A` or `*` is needed

Developers can register presets without editing `FormsPage`:

```php
use BonyadAlavi\FormEngine\InputMask\InputMaskDefinition;

add_action('afe_register_input_mask_definitions', function ($masks) {
    $masks->register(new InputMaskDefinition(
        'organization_code',
        'کد سازمانی',
        'AA-999999',
        ['text'],
        'AB-123456',
        'نمایش کد سازمانی',
        'text'
    ));
});
```

Mask is not a validator. Use `ValidatorRegistry`, legacy rules and/or length settings for correctness. Frontend and wp-admin submission editing display the same mask, while both applicant and admin save paths normalize again in PHP. The frontend mirrors normalized length/pattern checks for UX, but PHP remains authoritative. Date fields keep their dedicated `YYYY/MM/DD` / `YYYY-MM-DD` handling and are not routed through generic input masks.

## Validator Registry

Register a developer validator through `afe_register_validator_definitions`; do not hard-code new validator branches into `FormsPage`.

```php
use BonyadAlavi\FormEngine\Validation\ValidatorDefinition;

add_action('afe_register_validator_definitions', function ($validators) {
    $validators->register(new ValidatorDefinition(
        'organization_code',
        'کد سازمانی',
        ['text'],
        'کد سازمانی معتبر نیست.',
        static fn(string $value, array $field, array $config): bool =>
            (bool) preg_match('/^ORG-[0-9]{6}$/', $value)
    ));
});
```

The Field Override UI automatically lists registered validators whose `fieldTypes` support that Field type. Each selected validator may receive a custom error message.

Core validator keys are:

```text
national_id
mobile
iban
email
url
postal_code
bank_card
landline
date
custom_regex
```

Legacy DSL rules such as `->rule('national_id')` remain supported for backwards compatibility. New reusable admin-selectable validators should use the registry.

### Custom Regex safety

Admin-defined Custom Regex is intentionally constrained:

- requires `afe_manage_settings`;
- pattern body is entered without delimiters;
- maximum 500 characters;
- flags are restricted to `i`, `m`, `s`, `u`, `x`;
- compile-test runs before save;
- a custom error message is mandatory;
- server runtime uses PCRE match/recursion limits;
- frontend regex validation is only a convenience layer and may skip patterns whose PCRE syntax is not supported by JavaScript.

PHP remains the final validator.

## Character and length overrides

Text-like Fields can receive admin overrides for:

- `normal`
- `digits`
- `persian`
- `english`
- `alnum`
- extra allowed characters
- extra forbidden characters
- minimum / maximum / exact length

For identifiers such as National ID, use a text Field with numeric input hints. Do **not** switch identifiers to `type=number`, because leading zeroes are meaningful data.

## SMS Provider Registry و Persian WooCommerce SMS

از 1.0.28-dev، `SmsAction` به یک Provider ثابت قفل نیست. `SmsProviderRegistry` یک Provider پیش‌فرض سراسری را resolve می‌کند و هر Action می‌تواند با کلید `provider` آن را Override کند. مقدار `default` یعنی استفاده از Provider سراسری.

Integration داخلی Persian WooCommerce SMS فقط از API عمومی `PWSMS()` استفاده می‌کند. AFE نام کاربری، Password، API Key یا Sender آن افزونه را نمی‌خواند/کپی نمی‌کند؛ `send_sms()` و Gateway فعال همان افزونه مسئول Credential و ارسال هستند.

برای ثبت Provider جدید:

```php
add_action('afe_register_sms_providers', function ($providers) {
    $providers->register(
        'my_provider',
        'Provider اختصاصی',
        new MySmsProvider(),
        ['free', 'pattern'],
        true
    );
});
```

برای Gatewayهای Pattern افزونه Persian WooCommerce SMS که AFE به‌صورت built-in فرمت آن‌ها را نمی‌شناسد، integration را بدون ویرایش Core توسعه دهید:

```php
add_filter('afe_pwsms_supported_modes', function (array $modes, array $gateway) {
    if (($gateway['id'] ?? '') === 'my_pattern_gateway') $modes[] = 'pattern';
    return $modes;
}, 10, 2);

add_filter('afe_pwsms_pattern_strategy', function (string $strategy, array $gateway) {
    return ($gateway['id'] ?? '') === 'my_pattern_gateway' ? 'my_gateway' : $strategy;
}, 10, 2);

add_filter('afe_pwsms_pattern_payload', function (string $payload, $message, array $gateway, $gatewayObject, string $strategy) {
    if ($strategy !== 'my_gateway') return $payload;
    return $message->patternCode . ':' . implode('|', $message->patternValues);
}, 10, 5);
```

Hook `afe_pwsms_send_data` نیز آخرین payload آرایه‌ای قبل از `PWSMS()->send_sms()` را در اختیار Integration قرار می‌دهد. Raw PHP از Admin UI پذیرفته نمی‌شود؛ این Hookها فقط برای کد توسعه‌دهنده هستند.

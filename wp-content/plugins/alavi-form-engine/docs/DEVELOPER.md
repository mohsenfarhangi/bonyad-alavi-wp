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

Implement `ActionInterface`:

```php
final class CrmAction implements ActionInterface
{
    public function handle(ActionContext $context, array $config = []): void
    {
        // Send to CRM.
    }
}

add_action('afe_register_actions', function ($actions) {
    $actions->register('crm', new CrmAction());
});
```

In the form definition:

```php
->actions([
    [
        'type' => 'crm',
        'on' => ['created', 'updated'],
        'when' => [
            ['field' => 'priority', 'operator' => '=', 'value' => 'high'],
        ],
        'config' => ['pipeline' => 'groups'],
    ],
]);
```

## 6. Events and listeners

```php
use BonyadAlavi\FormEngine\Events\SubmissionCreated;

add_action('afe_register_events', function ($events) {
    $events->listen(SubmissionCreated::class, function (SubmissionCreated $event) {
        // Listener logic.
    });
});
```

Every dispatched event is also bridged to a WordPress action named from its class.

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

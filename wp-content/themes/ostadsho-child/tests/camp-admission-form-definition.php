<?php
declare(strict_types=1);

$pluginRoot = dirname(__DIR__, 3) . '/plugins/alavi-form-engine';

spl_autoload_register(static function (string $class) use ($pluginRoot): void {
    $prefix = 'BonyadAlavi\FormEngine\\';
    if (!str_starts_with($class, $prefix)) return;
    $file = $pluginRoot . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_readable($file)) require_once $file;
});

require_once dirname(__DIR__) . '/inc/forms/class-ba-camp-admission-form.php';

use BonyadAlavi\FormEngine\Form\FormRegistry;
use BonyadAlavi\FormEngine\Form\Validator;
use BonyadAlavi\FormEngine\Security\Validators;
use BonyadAlavi\FormEngine\Validation\ValidatorRegistry;

$formRegistry = new FormRegistry();
BA_Camp_Admission_Form::register($formRegistry);
$form = $formRegistry->get(BA_Camp_Admission_Form::SLUG)->toArray();

$fields = [];
$order = [];
foreach ($form['steps'] as $step) {
    foreach ($step['items'] as $field) {
        if (($field['type'] ?? '') === 'html' || empty($field['name'])) continue;
        $fields[$field['name']] = $field;
        $order[] = $field['name'];
    }
}

$validatorRegistry = new ValidatorRegistry();
BA_Camp_Admission_Form::registerValidators($validatorRegistry);
$validator = new Validator($validatorRegistry);

$validData = [
    'organization_name' => 'سازمان نمونه',
    'representative_name' => 'علی رضایی',
    'representative_mobile' => '09123456789',
    'guest_count' => '24',
    'from_date' => '1405/08/15',
    'to_date' => '1405/08/18',
    'stay_duration' => '3',
];
$validErrors = $validator->validate($form, $validData);

$invalidDuration = $validData;
$invalidDuration['stay_duration'] = '4';
$invalidDurationErrors = $validator->validate($form, $invalidDuration);

$invalidRange = $validData;
$invalidRange['to_date'] = '1405/08/14';
$invalidRangeErrors = $validator->validate($form, $invalidRange);

$sameDay = $validData;
$sameDay['to_date'] = '1405/08/15';
$sameDay['stay_duration'] = '0';
$sameDayErrors = $validator->validate($form, $sameDay);

$invalidGuestCount = $validData;
$invalidGuestCount['guest_count'] = '1.5';
$invalidGuestCountErrors = $validator->validate($form, $invalidGuestCount);

$persianDigits = $validData;
$persianDigits['from_date'] = '۱۴۰۵/۰۸/۱۵';
$persianDigits['to_date'] = '۱۴۰۵/۰۸/۱۸';
$persianDigits['stay_duration'] = '۳';
$persianDigitErrors = $validator->validate($form, $persianDigits);

$missingErrors = $validator->validate($form, []);

$checks = [
    'slug-and-title' =>
        ($form['slug'] ?? '') === 'imam-khomeini-camp-admission'
        && ($form['title'] ?? '') === 'فرم پذیرش مجتمع اردوگاهی امام خمینی (ره)',
    'exactly-seven-data-fields' => $order === [
        'organization_name',
        'representative_name',
        'representative_mobile',
        'guest_count',
        'from_date',
        'to_date',
        'stay_duration',
    ],
    'all-seven-fields-required' =>
        count(array_filter($fields, static fn(array $field): bool => !empty($field['required']))) === 7,
    'organization-and-representative-fields' =>
        ($fields['organization_name']['type'] ?? '') === 'text'
        && ($fields['representative_name']['type'] ?? '') === 'text',
    'mobile-validator-and-mask' =>
        ($fields['representative_mobile']['type'] ?? '') === 'tel'
        && !empty($fields['representative_mobile']['rules']['mobile_09'])
        && ($fields['representative_mobile']['input_mask']['key'] ?? '') === 'mobile_ir',
    'guest-count-positive-integer' =>
        ($fields['guest_count']['type'] ?? '') === 'number'
        && ($fields['guest_count']['attributes']['min'] ?? 0) === 1
        && ($fields['guest_count']['attributes']['step'] ?? 0) === 1
        && ($fields['guest_count']['validators'][0]['key'] ?? '') === 'ba_camp_positive_integer',
    'jalali-date-fields' =>
        ($fields['from_date']['calendar'] ?? '') === 'jalali'
        && ($fields['to_date']['calendar'] ?? '') === 'jalali'
        && ($fields['from_date']['date_input_mode'] ?? '') === 'combined'
        && ($fields['to_date']['date_input_mode'] ?? '') === 'combined',
    'end-date-cross-field-validator' =>
        ($fields['to_date']['validators'][0]['key'] ?? '') === 'ba_camp_end_date_after_start'
        && ($fields['to_date']['validators'][0]['start_field'] ?? '') === 'from_date',
    'duration-is-readonly-calculated-field' =>
        ($fields['stay_duration']['type'] ?? '') === 'number'
        && ($fields['stay_duration']['attributes']['data-afe-camp-duration'] ?? '') === '1'
        && ($fields['stay_duration']['validators'][0]['key'] ?? '') === 'ba_camp_duration_matches_dates',
    'three-night-date-calculation' => BA_Camp_Admission_Form::durationBetweenJalaliDates('1405/08/15', '1405/08/18') === 3,
    'persian-digit-date-calculation' => BA_Camp_Admission_Form::durationBetweenJalaliDates('۱۴۰۵/۰۸/۱۵', '۱۴۰۵/۰۸/۱۸') === 3,
    'invalid-jalali-date-rejected' => BA_Camp_Admission_Form::durationBetweenJalaliDates('1405/13/01', '1405/13/02') === null,
    'valid-data-accepted' => $validErrors === [],
    'tampered-duration-rejected' => isset($invalidDurationErrors['stay_duration']),
    'end-before-start-rejected' => isset($invalidRangeErrors['to_date']),
    'same-day-stay-rejected' => isset($sameDayErrors['to_date'], $sameDayErrors['stay_duration']),
    'fractional-guest-count-rejected' => isset($invalidGuestCountErrors['guest_count']),
    'persian-digits-accepted' => $persianDigitErrors === [],
    'missing-required-data-rejected' => count($missingErrors) === 7,
    'no-default-actions' => ($form['actions'] ?? null) === [],
    'simple-shared-form' =>
        ($form['settings']['wizard'] ?? true) === false
        && ($form['settings']['save_draft'] ?? true) === false
        && ($form['settings']['editing_enabled'] ?? true) === false
        && ($form['settings']['captcha'] ?? '') === 'custom'
        && ($form['storage'] ?? '') === 'shared',
];

foreach ($checks as $name => $ok) {
    echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
    if (!$ok) exit(1);
}

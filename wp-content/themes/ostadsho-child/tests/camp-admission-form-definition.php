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

use BonyadAlavi\FormEngine\Form\Form;
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

$stepItems = $form['steps'][0]['items'] ?? [];
$sectionTwoIndex = null;
$facilitiesIndex = null;
$guestCountIndex = null;
foreach ($stepItems as $index => $item) {
    if (($item['type'] ?? '') === 'html' && str_contains((string)($item['html'] ?? ''), 'مشخصات اقامت')) {
        $sectionTwoIndex = $index;
    }
    if (($item['name'] ?? '') === 'requested_facilities') $facilitiesIndex = $index;
    if (($item['name'] ?? '') === 'guest_count') $guestCountIndex = $index;
}

$validatorRegistry = new ValidatorRegistry();
BA_Camp_Admission_Form::registerValidators($validatorRegistry);
$validator = new Validator($validatorRegistry);

$validData = [
    'organization_name' => 'سازمان نمونه',
    'representative_name' => 'علی رضایی',
    'representative_mobile' => '09123456789',
    'requested_facilities' => ['سالن همایش شهید بروجردی', 'استخر'],
    'guest_count' => '24',
    'from_date' => '1405/08/15',
    'to_date' => '1405/08/18',
    'stay_duration' => '3',
    'terms_accepted' => '1',
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

$leadingZeros = $validData;
$leadingZeros['guest_count'] = '024';
$leadingZeros['stay_duration'] = '03';
$leadingZeroErrors = $validator->validate($form, $leadingZeros);

$invalidFacilities = $validData;
$invalidFacilities['requested_facilities'] = ['ساختمان ناموجود'];
$invalidFacilitiesErrors = $validator->validate($form, $invalidFacilities);

$missingFacilities = $validData;
$missingFacilities['requested_facilities'] = [];
$missingFacilitiesErrors = $validator->validate($form, $missingFacilities);

$duplicatedFacilities = $validData;
$duplicatedFacilities['requested_facilities'] = ['استخر', 'استخر'];
$duplicatedFacilitiesErrors = $validator->validate($form, $duplicatedFacilities);

$uncheckedTerms = $validData;
unset($uncheckedTerms['terms_accepted']);
$uncheckedTermsErrors = $validator->validate($form, $uncheckedTerms);

$forgedTerms = $validData;
$forgedTerms['terms_accepted'] = '0';
$forgedTermsErrors = $validator->validate($form, $forgedTerms);

$missingErrors = $validator->validate($form, []);

$themeRoot = dirname(__DIR__);
$campCss = file_get_contents($themeRoot . '/assets/css/ba-camp-admission-form.css');
$termsSelector = '.afe-form[data-form="imam-khomeini-camp-admission"] .afe-field[data-field="terms_accepted"]';
$termsStart = is_string($campCss) ? strpos($campCss, $termsSelector . ' {') : false;
$termsEnd = $termsStart !== false ? strpos($campCss, '}', $termsStart) : false;
$termsRule = $termsStart !== false && $termsEnd !== false ? substr($campCss, $termsStart, $termsEnd - $termsStart) : '';

$frontendScript = file_get_contents($themeRoot . '/assets/js/ba-camp-admission-form.js');
$engineFrontend = file_get_contents($pluginRoot . '/assets/js/frontend.js');
$rendererSource = file_get_contents($pluginRoot . '/src/Form/Renderer.php');
$submissionSource = file_get_contents($pluginRoot . '/src/Submission/SubmissionService.php');
$defaultFormSettings = Form::make('default-settings-test')->toArray()['settings'];
$expectedSuccessMessage = 'درخواست شما با موفقیت ثبت شد. از اینکه مجتمع اردوگاهی امام خمینی (ره) را انتخاب کرده‌اید سپاسگزاریم. به‌زودی با شما تماس گرفته خواهد شد.';

$checks = [
    'slug-and-title' =>
        ($form['slug'] ?? '') === 'imam-khomeini-camp-admission'
        && ($form['title'] ?? '') === 'فرم پذیرش مجتمع اردوگاهی امام خمینی (ره)',
    'exactly-nine-data-fields' => $order === [
        'organization_name',
        'representative_name',
        'representative_mobile',
        'requested_facilities',
        'guest_count',
        'from_date',
        'to_date',
        'stay_duration',
        'terms_accepted',
    ],
    'facilities-belong-to-accommodation-section' =>
        $sectionTwoIndex !== null && $facilitiesIndex !== null && $guestCountIndex !== null
        && $sectionTwoIndex + 1 === $facilitiesIndex
        && $facilitiesIndex + 1 === $guestCountIndex,
    'all-nine-fields-required' =>
        count(array_filter($fields, static fn(array $field): bool => !empty($field['required']))) === 9,
    'organization-and-representative-fields' =>
        ($fields['organization_name']['type'] ?? '') === 'text'
        && ($fields['representative_name']['type'] ?? '') === 'text',
    'facilities-multi-select-and-14-options' =>
        ($fields['requested_facilities']['type'] ?? '') === 'select'
        && ($fields['requested_facilities']['multiple'] ?? false) === true
        && ($fields['requested_facilities']['select_mode'] ?? '') === 'custom'
        && ($fields['requested_facilities']['searchable'] ?? false) === true
        && count($fields['requested_facilities']['options'] ?? []) === 14
        && ($fields['requested_facilities']['validators'][0]['key'] ?? '') === 'ba_camp_facilities_selection'
        && in_array('سالن غذاخوری', $fields['requested_facilities']['options'] ?? [], true)
        && in_array('استخر', $fields['requested_facilities']['options'] ?? [], true),
    'terms-clean-flex-layout' =>
        str_contains($termsRule, 'display: flex;')
        && str_contains($termsRule, 'flex-direction: row;')
        && str_contains($termsRule, 'flex-wrap: wrap;')
        && str_contains($termsRule, 'border: none;')
        && str_contains($termsRule, 'background: transparent;')
        && !str_contains($termsRule, 'grid-template-columns')
        && is_string($campCss)
        && str_contains($campCss, $termsSelector . ' .afe-checkbox {')
        && str_contains($campCss, 'order: -1;'),
    'terms-is-required-checkbox' =>
        ($fields['terms_accepted']['type'] ?? '') === 'checkbox'
        && ($fields['terms_accepted']['required'] ?? false) === true
        && ($fields['terms_accepted']['default'] ?? '') !== '1',
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
    'invalid-facility-rejected' => isset($invalidFacilitiesErrors['requested_facilities']),
    'missing-facility-rejected' => isset($missingFacilitiesErrors['requested_facilities']),
    'duplicate-facility-rejected' => isset($duplicatedFacilitiesErrors['requested_facilities']),
    'unchecked-terms-rejected' => isset($uncheckedTermsErrors['terms_accepted']),
    'forged-terms-rejected' => isset($forgedTermsErrors['terms_accepted']),
    'valid-data-accepted' => $validErrors === [],
    'tampered-duration-rejected' => isset($invalidDurationErrors['stay_duration']),
    'end-before-start-rejected' => isset($invalidRangeErrors['to_date']),
    'same-day-stay-rejected' => isset($sameDayErrors['to_date'], $sameDayErrors['stay_duration']),
    'fractional-guest-count-rejected' => isset($invalidGuestCountErrors['guest_count']),
    'persian-digits-accepted' => $persianDigitErrors === [],
    'leading-zero-numeric-values-accepted' => $leadingZeroErrors === [],
    'frontend-duration-is-readonly-and-reactive' =>
        is_string($frontendScript)
        && str_contains($frontendScript, 'durationInput.readOnly = true')
        && str_contains($frontendScript, "startInput.addEventListener(eventName, scheduleUpdate)")
        && str_contains($frontendScript, 'jalaliDayNumber'),
    'missing-required-data-rejected' => count($missingErrors) === 9,
    'tracking-code-hidden-after-submit' =>
        ($form['settings']['show_tracking_code'] ?? true) === false
        && is_string($engineFrontend)
        && str_contains($engineFrontend, "form.dataset.afeShowTrackingCode === '0'")
        && is_string($rendererSource)
        && str_contains($rendererSource, 'data-afe-show-tracking-code'),
    'custom-thank-you-message' =>
        ($form['settings']['success_message'] ?? '') === $expectedSuccessMessage
        && is_string($submissionSource)
        && str_contains($submissionSource, "['settings']['success_message'] ?? ''"),
    'tracking-code-visible-by-default-for-other-forms' =>
        ($defaultFormSettings['show_tracking_code'] ?? false) === true,
    'success-message-default-preserves-existing-forms' =>
        ($defaultFormSettings['success_message'] ?? null) === '',
    'captcha-located-before-final-submit' =>
        ($form['settings']['captcha_before_submit'] ?? false) === true
        && is_string($rendererSource)
        && str_contains($rendererSource, '$inlineCaptcha && $index===$dataSteps-1')
        && str_contains($rendererSource, '$locked || $inlineCaptcha')
        && str_contains($rendererSource, 'captchaMarkup'),
    'frontend-checkbox-required-by-check-state' =>
        is_string($engineFrontend)
        && str_contains($engineFrontend, "controls[0].type === 'checkbox'"),
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

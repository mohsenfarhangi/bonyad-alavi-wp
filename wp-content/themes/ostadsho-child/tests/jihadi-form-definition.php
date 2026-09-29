<?php
declare(strict_types=1);

function get_option(string $name, mixed $default = null): mixed {
    return $name === 'admin_email' ? 'admin@example.test' : $default;
}

$pluginRoot = dirname(__DIR__, 3) . '/plugins/alavi-form-engine';

spl_autoload_register(static function (string $class) use ($pluginRoot): void {
    $prefix = 'BonyadAlavi\\FormEngine\\';
    if (!str_starts_with($class, $prefix)) return;
    $file = $pluginRoot . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_readable($file)) require_once $file;
});

require_once dirname(__DIR__) . '/inc/forms/class-ba-jihadi-group-registration-form.php';

use BonyadAlavi\FormEngine\Form\FormRegistry;
use BonyadAlavi\FormEngine\Security\Validators;

$registry = new FormRegistry();
BA_Jihadi_Group_Registration_Form::register($registry);

if (!$registry->has('jihadi-group-registration')) {
    fwrite(STDERR, "FAIL form registration\n");
    exit(1);
}

$form = $registry->get('jihadi-group-registration')->toArray();
$fields = [];
foreach ($form['steps'] as $step) {
    foreach ($step['items'] as $field) {
        if (!empty($field['name'])) $fields[$field['name']] = $field;
    }
}

$coverage = $fields['coverage_areas'] ?? null;
$children = [];
foreach (($coverage['fields'] ?? []) as $child) $children[$child['name']] = $child;

$actions = [];
foreach ((array)($form['actions'] ?? []) as $action) {
    if (is_array($action)) $actions[(string)($action['action_key'] ?? '')] = $action;
}
$sms = $actions['notify_leader_sms_on_submit'] ?? [];
$smsConfig = is_array($sms['config'] ?? null) ? $sms['config'] : [];

$checks = [
    'slug-stable' => ($form['slug'] ?? '') === 'jihadi-group-registration',
    'title' => str_contains((string)($form['title'] ?? ''), 'شناسنامه گروه‌های مردمی و جهادی') && str_contains((string)($form['title'] ?? ''), 'شهید رسول عالم باقری'),
    'slogan' => !empty($form['settings']['header_slogan']),
    'mobile-strict' => ($fields['group_mobile']['rules']['mobile_09'] ?? false) === true && ($fields['group_mobile']['attributes']['maxlength'] ?? 0) === 11,
    'mobile-mask-default' => ($fields['group_mobile']['input_mask']['key'] ?? '') === 'mobile_ir' && ($fields['leader_mobile']['input_mask']['key'] ?? '') === 'mobile_ir',
    'national-id-mask-default' => ($fields['leader_national_id']['input_mask']['key'] ?? '') === 'national_id_ir',
    'iban-mask-default' => ($fields['legal_iban']['input_mask']['key'] ?? '') === 'iban_digits_ir',
    'iban-optional' => empty($fields['legal_iban']['required']) && ($fields['legal_iban']['rules']['iban_digits'] ?? false) === true && ($fields['legal_iban']['input_prefix'] ?? '') === 'IR',
    'national-id-10' => ($fields['leader_national_id']['attributes']['maxlength'] ?? 0) === 10 && ($fields['deputy_national_id']['attributes']['maxlength'] ?? 0) === 10,
    'coverage-repeater' => ($coverage['type'] ?? '') === 'repeater' && ($coverage['min'] ?? 0) === 1,
    'coverage-row-deps' => ($children['county']['depends_on'] ?? '') === 'province' && ($children['district']['depends_on'] ?? '') === 'county',
    'national-reject-letters' => Validators::nationalId('abc2150338106') === false,
    'iban-digits-valid' => Validators::iranIbanDigits('290570077700008623889001') === true,
    'mobile09-valid' => Validators::mobile09('09360998864') === true,
    'sms-template-present' => ($sms['type'] ?? '') === 'sms',
    'sms-template-disabled' => array_key_exists('enabled', $sms) && $sms['enabled'] === false,
    'sms-submit-event' => ($sms['on'] ?? []) === ['submission.submitted'],
    'sms-once' => ($sms['execution_policy'] ?? '') === 'once_per_submission',
    'sms-recipient-field' => ($smsConfig['recipient_source'] ?? '') === 'field' && ($smsConfig['recipient_field'] ?? '') === 'leader_mobile',
    'sms-content-not-hardcoded' => ($smsConfig['body'] ?? null) === '' && ($smsConfig['pattern_code'] ?? null) === '' && ($smsConfig['pattern_values'] ?? null) === [],
];

foreach ($checks as $name => $ok) {
    echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
    if (!$ok) exit(1);
}

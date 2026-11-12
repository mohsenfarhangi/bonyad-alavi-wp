<?php
declare(strict_types=1);

if (!defined('ABSPATH')) define('ABSPATH', __DIR__ . '/');
$GLOBALS['ba_qs_test_settings'] = ['allow_customer_gateway_selection'=>false];

function get_option(string $name, mixed $default = null): mixed {
    if ($name === 'ba_participation_settings') return $GLOBALS['ba_qs_test_settings'];
    return $default;
}

function sanitize_key(string $key): string {
    return strtolower((string)preg_replace('/[^a-z0-9_\\-]/i', '', $key));
}

final class BA_Participation_Payment_Gateway_Service
{
    public function __construct() {}
}

$pluginRoot = dirname(__DIR__, 3) . '/plugins/alavi-form-engine';
spl_autoload_register(static function(string $class) use ($pluginRoot): void {
    $prefix = 'BonyadAlavi\\FormEngine\\';
    if (!str_starts_with($class, $prefix)) return;
    $file = $pluginRoot . '/src/' . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
    if (is_readable($file)) require_once $file;
});

require_once dirname(__DIR__) . '/inc/quick-support/class-ba-quick-support-category.php';
require_once dirname(__DIR__) . '/inc/quick-support/class-ba-quick-support-category-context-provider.php';
require_once dirname(__DIR__) . '/inc/woocommerce/class-ba-quick-support-payment-service.php';
require_once dirname(__DIR__) . '/inc/woocommerce/class-ba-quick-support-payment-action.php';
require_once dirname(__DIR__) . '/inc/forms/class-ba-quick-support-form.php';

use BonyadAlavi\FormEngine\Form\FormRegistry;
use BonyadAlavi\FormEngine\Form\Validator;
use BonyadAlavi\FormEngine\Validation\ValidatorRegistry;
use BonyadAlavi\FormEngine\Actions\ActionRegistry;
use BonyadAlavi\FormEngine\Context\FormContextRegistry;

function quick_support_fields(array $form): array {
    $fields = [];
    foreach ($form['steps'] as $step) {
        foreach ($step['items'] as $field) {
            $fields[$field['name']] = $field;
        }
    }
    return $fields;
}

$registry = new FormRegistry();
BA_Quick_Support_Form::register($registry);
$form = $registry->get(BA_Quick_Support_Form::SLUG)->toArray();
$fields = quick_support_fields($form);

$validatorRegistry = new ValidatorRegistry();
BA_Quick_Support_Form::registerValidators($validatorRegistry);
$validator = new Validator($validatorRegistry);

$actionRegistry = new ActionRegistry();
BA_Quick_Support_Form::registerActionDefinition($actionRegistry);
$contextRegistry = new FormContextRegistry();
BA_Quick_Support_Form::registerContextProvider($contextRegistry);

$validData = [
    'mobile'=>'09123456789',
    'first_name'=>'علی',
    'last_name'=>'رضایی',
    'province'=>'',
    'county'=>'',
    'national_id'=>'',
    'email'=>'',
    'amount'=>'50000',
];
$context = [
    'category_id'=>100,
    'category_slug'=>'education-skills',
    'minimum_amount'=>'10000',
    'maximum_amount'=>'100000',
];

$GLOBALS['ba_qs_test_settings'] = ['allow_customer_gateway_selection'=>true];
$withGatewayRegistry = new FormRegistry();
BA_Quick_Support_Form::register($withGatewayRegistry);
$gatewayForm = $withGatewayRegistry->get(BA_Quick_Support_Form::SLUG)->toArray();
$gatewayFields = quick_support_fields($gatewayForm);

$validErrors = $validator->validate($form, $validData, false, $context);
$lowAmount = $validData;
$lowAmount['amount'] = '5000';
$lowErrors = $validator->validate($form, $lowAmount, false, $context);
$highAmount = $validData;
$highAmount['amount'] = '150000';
$highErrors = $validator->validate($form, $highAmount, false, $context);
$missingContextErrors = $validator->validate($form, $validData);

$checks = [
    'stable-form-slug' => $form['slug'] === 'quick-support-payment',
    'shared-storage' => $form['storage'] === 'shared',
    'one-step-shared-form' => count($form['steps']) === 1,
    'required-mobile-name-lastname-amount' => !empty($fields['mobile']['required'])
        && !empty($fields['first_name']['required'])
        && !empty($fields['last_name']['required'])
        && !empty($fields['amount']['required']),
    'optional-province-county-national-id-email' => empty($fields['province']['required'])
        && empty($fields['county']['required'])
        && empty($fields['national_id']['required'])
        && empty($fields['email']['required']),
    'iran-mobile-09-rule' => !empty($fields['mobile']['rules']['mobile_09']),
    'national-id-rule' => !empty($fields['national_id']['rules']['national_id']),
    'email-rule' => !empty($fields['email']['rules']['email']),
    'county-geo-dependency' => ($fields['county']['source']['type'] ?? '') === 'geo'
        && ($fields['county']['source']['level'] ?? '') === 'county'
        && ($fields['county']['depends_on'] ?? '') === 'province'
        && ($fields['county']['validators'][0]['key'] ?? '') === 'ba_qs_county_in_province',
    'amount-context-validator' => ($fields['amount']['validators'][0]['key'] ?? '') === 'ba_qs_amount_limits',
    'payment-action-registered-in-form' => ($form['actions'][0]['type'] ?? '') === 'ba_quick_support_payment',
    'payment-action-handler-registered' => $actionRegistry->has('ba_quick_support_payment')
        && $actionRegistry->handler('ba_quick_support_payment') instanceof BA_Quick_Support_Payment_Action,
    'payment-action-retry-supported' => $actionRegistry->get('ba_quick_support_payment')?->supportsRetry === true,
    'category-context-provider-registered' => $contextRegistry->get('ba_quick_support_category') instanceof BA_Quick_Support_Category_Context_Provider,
    'gateway-select-disabled-by-default' => !isset($fields['payment_gateway']),
    'gateway-select-configurable' => isset($gatewayFields['payment_gateway'])
        && !empty($gatewayFields['payment_gateway']['required'])
        && ($gatewayFields['payment_gateway']['source'] ?? '') === 'ba_quick_support_gateways'
        && !empty($gatewayFields['payment_gateway']['hide_when_single_option']),
    'valid-data-and-context-accepted' => $validErrors === [],
    'minimum-amount-rejected-before-save' => isset($lowErrors['amount']),
    'maximum-amount-rejected-before-save' => isset($highErrors['amount']),
    'missing-context-rejected-before-save' => isset($missingContextErrors['amount']),
];

foreach ($checks as $name => $ok) {
    echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
    if (!$ok) exit(1);
}

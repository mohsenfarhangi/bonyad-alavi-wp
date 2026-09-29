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

require_once dirname(__DIR__) . '/inc/forms/class-ba-contact-form.php';

use BonyadAlavi\FormEngine\Form\FormRegistry;

$registry = new FormRegistry();
BA_Contact_Form::register($registry);

if (!$registry->has('contact-us')) {
    fwrite(STDERR, "FAIL contact form registration\n");
    exit(1);
}

$form = $registry->get('contact-us')->toArray();
$fields = [];
foreach ($form['steps'] as $step) {
    foreach ($step['items'] as $field) {
        if (!empty($field['name'])) $fields[$field['name']] = $field;
    }
}

$actions = [];
foreach ((array)($form['actions'] ?? []) as $action) {
    if (is_array($action)) $actions[(string)($action['action_key'] ?? '')] = $action;
}
$emailAction = $actions['notify_admin_email_on_submit'] ?? [];
$emailConfig = is_array($emailAction['config'] ?? null) ? $emailAction['config'] : [];

$functions = file_get_contents(dirname(__DIR__) . '/functions.php');

$checks = [
    'slug' => ($form['slug'] ?? '') === 'contact-us',
    'title' => ($form['title'] ?? '') === 'تماس با ما',
    'single-step' => count((array)($form['steps'] ?? [])) === 1,
    'required-name' => ($fields['full_name']['type'] ?? '') === 'text' && ($fields['full_name']['required'] ?? false) === true,
    'mobile-contract' => ($fields['mobile']['type'] ?? '') === 'tel' && ($fields['mobile']['rules']['mobile_09'] ?? false) === true && ($fields['mobile']['input_mask']['key'] ?? '') === 'mobile_ir',
    'email-field' => ($fields['email']['type'] ?? '') === 'email',
    'province-geo-source' => ($fields['province']['type'] ?? '') === 'select' && ($fields['province']['source']['type'] ?? '') === 'geo' && ($fields['province']['source']['level'] ?? '') === 'province' && ($fields['province']['required'] ?? false) === true,
    'subject-required' => ($fields['subject']['required'] ?? false) === true,
    'message-required' => ($fields['message']['type'] ?? '') === 'textarea' && ($fields['message']['required'] ?? false) === true,
    'simple-form-settings' => ($form['settings']['wizard'] ?? true) === false && ($form['settings']['save_draft'] ?? true) === false && ($form['settings']['show_progress'] ?? true) === false,
    'captcha-rate-limit' => ($form['settings']['captcha'] ?? '') === 'custom' && ($form['settings']['rate_limit'] ?? 0) === 5,
    'shared-storage' => ($form['storage'] ?? '') === 'shared',
    'admin-email-action' => ($emailAction['type'] ?? '') === 'email' && ($emailAction['on'] ?? []) === ['submission.submitted'] && ($emailAction['execution_policy'] ?? '') === 'once_per_submission',
    'admin-email-recipient' => ($emailConfig['to'] ?? '') === 'admin@example.test',
    'admin-email-field-tokens' => str_contains((string)($emailConfig['subject'] ?? ''), '{{field:subject}}') && str_contains((string)($emailConfig['body'] ?? ''), '{{field:message}}') && str_contains((string)($emailConfig['body'] ?? ''), '{{tracking_code}}'),
    'theme-loads-contact-form' => is_string($functions) && str_contains($functions, "inc/forms/class-ba-contact-form.php") && str_contains($functions, 'BA_Contact_Form::class'),
];

foreach ($checks as $name => $ok) {
    echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
    if (!$ok) exit(1);
}

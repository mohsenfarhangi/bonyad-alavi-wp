<?php
declare(strict_types=1);

function get_option(string $name, mixed $default=null): mixed { return $name === 'admin_email' ? 'admin@example.test' : $default; }

spl_autoload_register(static function(string $class): void {
    $prefix='BonyadAlavi\\FormEngine\\';
    if (!str_starts_with($class,$prefix)) return;
    $file=dirname(__DIR__).'/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';
    if (is_readable($file)) require_once $file;
});

use BonyadAlavi\FormEngine\Forms\JihadiGroupRegistrationForm;

$form=(new JihadiGroupRegistrationForm())->build()->toArray();
$actions=[];
foreach ((array)($form['actions']??[]) as $action) {
    if (!is_array($action)) continue;
    $actions[(string)($action['action_key']??'')]=$action;
}
$sms=$actions['notify_leader_sms_on_submit']??[];
$config=is_array($sms['config']??null)?$sms['config']:[];
$checks=[
    'sms-template-present'=>($sms['type']??'')==='sms',
    'sms-template-disabled'=>array_key_exists('enabled',$sms) && $sms['enabled']===false,
    'sms-submit-event'=>($sms['on']??[])===['submission.submitted'],
    'sms-once'=>($sms['execution_policy']??'')==='once_per_submission',
    'sms-recipient-field'=>($config['recipient_source']??'')==='field' && ($config['recipient_field']??'')==='leader_mobile',
    'sms-content-not-hardcoded'=>($config['body']??null)==='' && ($config['pattern_code']??null)==='' && ($config['pattern_values']??null)===[],
];
foreach($checks as $name=>$ok){ echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL; if(!$ok) exit(1); }

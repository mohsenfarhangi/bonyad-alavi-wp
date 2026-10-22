<?php
declare(strict_types=1);

spl_autoload_register(static function (string $class): void {
    $prefix='BonyadAlavi\\FormEngine\\';
    if (!str_starts_with($class,$prefix)) return;
    $file=dirname(__DIR__).'/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';
    if (is_file($file)) require_once $file;
});

use BonyadAlavi\FormEngine\Actions\Sms\SmsOtpMessageFactory as Factory;

function invalidOtpConfig(array $config): bool
{
    try { Factory::make($config,'09120000001','654321'); }
    catch (InvalidArgumentException) { return true; }
    return false;
}

$default=Factory::make([],'09120000001','654321');
$free=Factory::make(['otp_sms_mode'=>'free',
    'otp_sms_text'=>"کد دسترسی شما {{code}}\nاعتبار {{minutes}} دقیقه."
], '09120000001', '123456');
$pattern=Factory::make(['otp_sms_mode'=>'pattern','otp_sms_pattern_code'=>'12345',
    'otp_sms_pattern_values'=>['بنیاد علوی','{{code}}','{{minutes}} دقیقه']
], '09120000001', '654321');
$legacyPattern=Factory::make(['otp_sms_mode'=>'pattern','otp_sms_pattern_code'=>'67890',
    'otp_sms_pattern_values'=>"{{code}}\r\nسامانه بنیاد"
], '09120000001', '654321');

$checks=[
    'old-forms-default-to-free-with-otp'=>$default->mode==='free'
        && str_contains($default->body,'654321')
        && substr_count($default->body,'654321')===1
        && $default->recipient==='09120000001',
    'free-template-variable-expanded'=>$free->mode==='free'
        && $free->body==="کد دسترسی شما 123456\nاعتبار 5 دقیقه."
        && $free->patternCode==='',
    'pattern-body-id-and-ordered-values'=>$pattern->mode==='pattern'
        && $pattern->body==='' && $pattern->patternCode==='12345'
        && $pattern->patternValues===['بنیاد علوی','654321','5 دقیقه']
        && $pattern->recipient==='09120000001',
    'pattern-string-multiline-compatible'=>$legacyPattern->patternValues===['654321','سامانه بنیاد'],
    'reject-free-without-code'=>invalidOtpConfig(['otp_sms_mode'=>'free','otp_sms_text'=>'سلام']),
    'reject-repeated-code'=>invalidOtpConfig(['otp_sms_mode'=>'free','otp_sms_text'=>'{{code}} و {{code}}']),
    'reject-unknown-placeholders'=>invalidOtpConfig(['otp_sms_text'=>'{{code}} {{email}}']),
    'reject-invalid-otp'=>invalidOtpConfig(['otp_sms_text'=>'{{code}}','otp_sms_mode'=>'unsupported']),
    'reject-empty-pattern-id'=>invalidOtpConfig(['otp_sms_mode'=>'pattern','otp_sms_pattern_values'=>['{{code}}']]),
    'reject-pattern-id-with-leading-zero'=>invalidOtpConfig(['otp_sms_mode'=>'pattern','otp_sms_pattern_code'=>'000123','otp_sms_pattern_values'=>['{{code}}']]),
    'reject-pattern-without-otp'=>invalidOtpConfig(['otp_sms_mode'=>'pattern','otp_sms_pattern_code'=>'12','otp_sms_pattern_values'=>['متن']]),
    'reject-pattern-code-in-two-values'=>invalidOtpConfig(['otp_sms_mode'=>'pattern','otp_sms_pattern_code'=>'12','otp_sms_pattern_values'=>['{{code}}','{{code}}']]),
    'reject-too-many-values'=>invalidOtpConfig(['otp_sms_mode'=>'pattern','otp_sms_pattern_code'=>'12','otp_sms_pattern_values'=>array_fill(0,11,'{{code}}')]),
    'reject-pattern-empty-values'=>invalidOtpConfig(['otp_sms_mode'=>'pattern','otp_sms_pattern_code'=>'12','otp_sms_pattern_values'=>['{{code}}','']]),
    'reject-long-free-text'=>invalidOtpConfig(['otp_sms_mode'=>'free','otp_sms_text'=>'{{code}}'.str_repeat('x',2800)]),
];
foreach($checks as $name=>$ok) {
    echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL;
    if (!$ok) exit(1);
}

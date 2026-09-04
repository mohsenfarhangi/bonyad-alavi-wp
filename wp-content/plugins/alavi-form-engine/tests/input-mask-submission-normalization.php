<?php
declare(strict_types=1);

function sanitize_text_field(mixed $value): string { return trim(strip_tags((string)$value)); }
function sanitize_textarea_field(mixed $value): string { return trim(strip_tags((string)$value)); }
function sanitize_email(mixed $value): string { return filter_var((string)$value,FILTER_SANITIZE_EMAIL); }
function esc_url_raw(mixed $value): string { return (string)$value; }

spl_autoload_register(static function(string $class): void {
    $prefix='BonyadAlavi\\FormEngine\\';
    if (!str_starts_with($class,$prefix)) return;
    $file=dirname(__DIR__).'/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';
    if (is_readable($file)) require_once $file;
});

use BonyadAlavi\FormEngine\Submission\SubmissionService;
use BonyadAlavi\FormEngine\InputMask\InputMaskRegistry;

$reflection=new ReflectionClass(SubmissionService::class);
$service=$reflection->newInstanceWithoutConstructor();
$reflection->getProperty('inputMasks')->setValue($service,new InputMaskRegistry());
$resolve=new ReflectionMethod(SubmissionService::class,'resolveFieldInputMask');
$resolve->setAccessible(true);
$resolved=['type'=>'tel','input_mask'=>['key'=>'mobile_ir']];
$resolve->invokeArgs($service,[&$resolved]);
$method=new ReflectionMethod(SubmissionService::class,'sanitizeScalar');
$method->setAccessible(true);
$field=['type'=>'tel','input_mask'=>['key'=>'mobile_ir','pattern'=>'9999 999 9999']];
$clean=$method->invoke($service,$field,'۰۹۱۲ ۳۴۵ ۶۷۸۹');
$card=$method->invoke($service,['type'=>'text','input_mask'=>['key'=>'bank_card_ir','pattern'=>'9999 9999 9999 9999']],'6037 9912 3456 7890');
$checks=[
    'preset-resolved'=>($resolved['input_mask']['pattern']??'')==='9999 999 9999',
    'mobile-clean'=>$clean==='09123456789',
    'card-clean'=>$card==='6037991234567890',
];
foreach($checks as $name=>$ok){ echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL; if(!$ok) exit(1); }

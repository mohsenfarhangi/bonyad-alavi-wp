<?php
declare(strict_types=1);

function is_email(string $value): bool { return filter_var($value,FILTER_VALIDATE_EMAIL)!==false; }

spl_autoload_register(static function(string $class): void {
    $prefix='BonyadAlavi\\FormEngine\\';
    if (!str_starts_with($class,$prefix)) return;
    $file=dirname(__DIR__).'/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';
    if (is_readable($file)) require_once $file;
});

use BonyadAlavi\FormEngine\Form\Validator;
use BonyadAlavi\FormEngine\Validation\ValidatorRegistry;

$validator=new Validator(new ValidatorRegistry());
$form=['steps'=>[['items'=>[
    ['name'=>'identifier','type'=>'text','label'=>'شناسه','required'=>true,'character_mode'=>'digits','allowed_extra'=>'-','exact_length'=>5],
    ['name'=>'code','type'=>'text','label'=>'کد','validators'=>[['key'=>'custom_regex','pattern'=>'^A[0-9]{2}$','flags'=>'u','message'=>'کد سفارشی نامعتبر است.']]],
    ['name'=>'persian_name','type'=>'text','label'=>'نام','character_mode'=>'persian'],
]]]];

$ok=$validator->validate($form,['identifier'=>'۱۲-۳۴','code'=>'A12','persian_name'=>'علی رضایی']);
$badChars=$validator->validate($form,['identifier'=>'12A34','code'=>'A12','persian_name'=>'علی']);
$badRegex=$validator->validate($form,['identifier'=>'12-34','code'=>'B12','persian_name'=>'علی']);
$badLength=$validator->validate($form,['identifier'=>'1234','code'=>'A12','persian_name'=>'علی']);
$badPersian=$validator->validate($form,['identifier'=>'12-34','code'=>'A12','persian_name'=>'علی123']);
$checks=[
    'valid-overrides'=>$ok===[],
    'character-mode'=>isset($badChars['identifier']),
    'custom-message'=>($badRegex['code']??'')==='کد سفارشی نامعتبر است.',
    'exact-length'=>isset($badLength['identifier']) && str_contains($badLength['identifier'],'دقیقاً'),
    'persian-rejects-digits'=>isset($badPersian['persian_name']),
];
foreach($checks as $name=>$pass){ echo ($pass?'PASS ':'FAIL ').$name.PHP_EOL; if(!$pass) exit(1); }

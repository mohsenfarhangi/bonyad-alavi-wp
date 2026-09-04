<?php
declare(strict_types=1);

spl_autoload_register(static function(string $class): void {
    $prefix='BonyadAlavi\\FormEngine\\';
    if (!str_starts_with($class,$prefix)) return;
    $file=dirname(__DIR__).'/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';
    if (is_readable($file)) require_once $file;
});

use BonyadAlavi\FormEngine\InputMask\InputMaskDefinition;
use BonyadAlavi\FormEngine\InputMask\InputMaskPattern;
use BonyadAlavi\FormEngine\InputMask\InputMaskRegistry;

$registry=new InputMaskRegistry();
$mobile=$registry->get('mobile_ir');
$checks=[
    'mobile-preset'=>$mobile?->pattern==='9999 999 9999' && $mobile->supports('tel'),
    'mobile-normalize'=>InputMaskPattern::normalize('۰۹۱۲ ۳۴۵ ۶۷۸۹','9999 999 9999')==='09123456789',
    'mobile-format'=>InputMaskPattern::format('09123456789','9999 999 9999')==='0912 345 6789',
    'postal-normalize'=>InputMaskPattern::normalize('12345-67890','99999-99999')==='1234567890',
    'letter-mask'=>InputMaskPattern::format('AB1234','AA-9999')==='AB-1234',
    'custom-valid'=>InputMaskPattern::isValid('AA-9999/***'),
    'dangling-escape-invalid'=>!InputMaskPattern::isValid('999\\'),
];

$registry->register(new InputMaskDefinition('developer_mask','Developer','AA-999',['text'],'AB-123'));
$checks['developer-register']=$registry->get('developer_mask')?->example==='AB-123';

$failed=false;
foreach($checks as $name=>$ok){ echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL; if(!$ok)$failed=true; }
exit($failed?1:0);

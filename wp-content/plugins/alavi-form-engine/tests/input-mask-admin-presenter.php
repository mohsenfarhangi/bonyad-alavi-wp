<?php
declare(strict_types=1);

function esc_attr(string $value): string { return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function wp_unslash(string $value): string { return $value; }
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

use BonyadAlavi\FormEngine\Admin\FormDataPresenter;

$presenter=(new ReflectionClass(FormDataPresenter::class))->newInstanceWithoutConstructor();
$edit=new ReflectionMethod(FormDataPresenter::class,'editScalar');
$edit->setAccessible(true);
$plain=new ReflectionMethod(FormDataPresenter::class,'plainField');
$plain->setAccessible(true);
$sanitize=new ReflectionMethod(FormDataPresenter::class,'sanitizeScalar');
$sanitize->setAccessible(true);
$field=['type'=>'tel','input_mask'=>['key'=>'mobile_ir','pattern'=>'9999 999 9999','inputmode'=>'numeric']];
$html=$edit->invoke($presenter,$field,'09123456789',[],'afe_admin_data[mobile]');
$clean=$sanitize->invoke($presenter,$field,'0912 345 6789');
$display=$plain->invoke($presenter,$field,'09123456789',[]);
$frontendDisplay=$plain->invoke($presenter,$field,'09123456789',[],true);

$checks=[
    'admin-edit-raw'=>str_contains($html,'value="09123456789"'),
    'admin-mask-data-removed'=>!str_contains($html,'data-afe-input-mask='),
    'admin-raw-maxlength'=>str_contains($html,'maxlength="11"'),
    'admin-numeric-direction-ltr'=>str_contains($html,'dir="ltr"'),
    'admin-save-normalized'=>$clean==='09123456789',
    'admin-display-raw'=>$display==='09123456789',
    'frontend-display-formatted'=>$frontendDisplay==='0912 345 6789',
];
foreach($checks as $name=>$ok){ echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL; if(!$ok) exit(1); }

<?php
declare(strict_types=1);

function esc_attr(string $value): string { return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }

spl_autoload_register(static function(string $class): void {
    $prefix='BonyadAlavi\\FormEngine\\';
    if (!str_starts_with($class,$prefix)) return;
    $file=dirname(__DIR__).'/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';
    if (is_readable($file)) require_once $file;
});

use BonyadAlavi\FormEngine\Form\Renderer;

$renderer=(new ReflectionClass(Renderer::class))->newInstanceWithoutConstructor();
$attrsMethod=new ReflectionMethod(Renderer::class,'inputAttributes');
$attrsMethod->setAccessible(true);
$attrs=$attrsMethod->invoke($renderer,[
    'type'=>'tel',
    'input_mask'=>['key'=>'mobile_ir','pattern'=>'9999 999 9999','inputmode'=>'numeric','example'=>'0912 345 6789'],
    'attributes'=>['maxlength'=>11,'minlength'=>11,'pattern'=>'09[0-9]{9}','data-afe-invalid-message'=>'mobile invalid'],
]);

$checks=[
    'mask-data'=>str_contains($attrs,'data-afe-input-mask="9999 999 9999"'),
    'display-maxlength'=>str_contains($attrs,'maxlength="13"'),
    'logical-exact-length'=>str_contains($attrs,'data-afe-value-exact-length="11"'),
    'normalized-pattern'=>str_contains($attrs,'data-afe-normalized-pattern="09[0-9]{9}"'),
    'raw-pattern-removed'=>!preg_match('/\spattern=/', $attrs),
    'numeric-inputmode'=>str_contains($attrs,'inputmode="numeric"'),
    'numeric-direction-ltr'=>str_contains($attrs,'dir="ltr"'),
];

$failed=false;
foreach($checks as $name=>$ok){ echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL; if(!$ok)$failed=true; }
exit($failed?1:0);

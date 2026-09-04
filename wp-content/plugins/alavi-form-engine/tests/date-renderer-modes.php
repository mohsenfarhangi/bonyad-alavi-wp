<?php
declare(strict_types=1);

function esc_attr(string $value): string { return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }

spl_autoload_register(static function(string $class): void {
    $prefix='BonyadAlavi\\FormEngine\\';
    if(!str_starts_with($class,$prefix)) return;
    $file=dirname(__DIR__).'/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';
    if(is_readable($file)) require_once $file;
});

use BonyadAlavi\FormEngine\Form\Renderer;

$renderer=(new ReflectionClass(Renderer::class))->newInstanceWithoutConstructor();
$method=new ReflectionMethod(Renderer::class,'dateInput');
$method->setAccessible(true);
$render=static fn(array $field): string => $method->invoke($renderer,$field,'',' name="date"');

$jalaliCombined=$render(['calendar'=>'jalali','date_input_mode'=>'combined']);
$jalaliPicker=$render(['calendar'=>'jalali','date_input_mode'=>'picker']);
$jalaliManual=$render(['calendar'=>'jalali','date_input_mode'=>'manual']);
$gregorianCombined=$render(['calendar'=>'gregorian','date_input_mode'=>'combined']);
$gregorianPicker=$render(['calendar'=>'gregorian','date_input_mode'=>'picker']);
$gregorianManual=$render(['calendar'=>'gregorian','date_input_mode'=>'manual']);

$checks=[
    'jalali-combined-text-picker'=>str_contains($jalaliCombined,'type="text"') && str_contains($jalaliCombined,'data-jdp') && str_contains($jalaliCombined,'data-afe-date-mode="combined"'),
    'jalali-picker-only'=>str_contains($jalaliPicker,'data-jdp') && str_contains($jalaliPicker,'data-afe-date-picker-only="1"'),
    'jalali-manual-no-picker'=>str_contains($jalaliManual,'type="text"') && !str_contains($jalaliManual,'data-jdp') && str_contains($jalaliManual,'data-afe-date-mode="manual"'),
    'gregorian-combined-native'=>str_contains($gregorianCombined,'type="date"') && str_contains($gregorianCombined,'data-afe-date-mode="combined"') && !str_contains($gregorianCombined,'data-afe-date-picker-only="1"'),
    'gregorian-picker-native-only'=>str_contains($gregorianPicker,'type="date"') && str_contains($gregorianPicker,'data-afe-date-picker-only="1"'),
    'gregorian-manual-text-mask'=>str_contains($gregorianManual,'type="text"') && str_contains($gregorianManual,'data-afe-date-mask="YYYY-MM-DD"') && str_contains($gregorianManual,'data-afe-date-mode="manual"'),
];

$failed=false;
foreach($checks as $name=>$ok){echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL;if(!$ok)$failed=true;}
exit($failed?1:0);

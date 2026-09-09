<?php
declare(strict_types=1);

function esc_html(string $value): string { return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function get_option(string $key,mixed $default=''): mixed { return $key==='admin_email'?'admin@example.test':$default; }

spl_autoload_register(static function(string $class): void {
    $prefix='BonyadAlavi\\FormEngine\\';
    if (!str_starts_with($class,$prefix)) return;
    $file=dirname(__DIR__).'/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';
    if (is_readable($file)) require_once $file;
});

use BonyadAlavi\FormEngine\Export\ExportProfile;
use BonyadAlavi\FormEngine\Forms\JihadiGroupRegistrationForm;

$form=(new JihadiGroupRegistrationForm())->build()->toArray();
$profile=ExportProfile::resolve($form);

$checks=[
    'jihadi-pdf-title'=>str_contains((string)$profile['pdf']['header_html'],'شناسنامه گروه‌های مردمی و جهادی'),
    'jihadi-pdf-fields-table'=>str_contains((string)$profile['pdf']['body_html'],'{{fields_table}}'),
    'jihadi-excel-sheet'=>($profile['excel']['sheet_name']??'')==='گروه‌های جهادی',
    'jihadi-excel-columns-custom'=>count((array)($profile['excel']['columns']??[]))===11,
    'mobile-column-present'=>in_array('leader_mobile',array_column((array)$profile['excel']['columns'],'field'),true),
    'pdf-rtl-css'=>str_contains((string)$profile['pdf']['css'],'direction:rtl'),
];
foreach($checks as $name=>$ok){echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL;if(!$ok)exit(1);}

$override=['excel'=>['columns'=>[['field'=>'group_name','label'=>'نام اختصاصی','enabled'=>true,'width'=>30]]]];
$resolved=ExportProfile::resolve($form,$override);
if(count($resolved['excel']['columns'])!==1 || $resolved['excel']['columns'][0]['label']!=='نام اختصاصی'){
    fwrite(STDERR,"Excel override columns must replace code/default list\n");exit(1);
}

echo "export profile ok\n";

<?php
declare(strict_types=1);

function sanitize_text_field(string $text): string {return trim(strip_tags($text));}
function sanitize_textarea_field(string $text): string {return trim(strip_tags($text));}
function wp_unslash(string $text): string {return stripslashes($text);}

spl_autoload_register(static function(string $class): void {
    $prefix='BonyadAlavi\\FormEngine\\';
    if(!str_starts_with($class,$prefix)) return;
    $file=dirname(__DIR__).'/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';
    if(is_file($file)) require_once $file;
});

use BonyadAlavi\FormEngine\Admin\AdminFields;
use BonyadAlavi\FormEngine\Export\ExportProfile;

function mustReject(callable $fn): bool {
    try {$fn();}catch(InvalidArgumentException){return true;}
    return false;
}

$form=[
    'slug'=>'jihadi-group-registration',
    'steps'=>[['items'=>[['name'=>'group_name','type'=>'text','label'=>'نام گروه']]]],
    'settings'=>['admin_fields'=>[
        ['key'=>'group_level','label'=>'سطح گروه','type'=>'select',
            'options'=>['novpa'=>'نوپا','faal'=>'فعال','pishro'=>'پیشرو','olgoo'=>'الگو']]
    ]]
];
$defs=AdminFields::definitions($form);
$clean=AdminFields::sanitizeValues($form,['group_level'=>'pishro','another'=>'unauthorized']);
$defaults=ExportProfile::defaults($form);
$old=$form;
$old['settings']['exports']['excel']['columns']=[
    ['field'=>'group_name','label'=>'نام گروه','enabled'=>true,'width'=>24]
];
$merged=ExportProfile::resolve($old);
$disabled=$old;
$disabled['settings']['exports']['excel']['columns'][]=[
    'field'=>'admin:group_level','label'=>'طبقه','enabled'=>false,'width'=>15
];
$kept=ExportProfile::resolve($disabled);
$dynamic=AdminFields::sanitizeDefinitions([
    ['key'=>'internal_note','label'=>'یادداشت کارشناسی','type'=>'textarea','options'=>''],
    ['key'=>'group_level','label'=>'سطح گروه','type'=>'select','options'=>"novpa|نوپا\nfaal|فعال\npishro|پیشرو\nolgoo|الگو"],
],$form);

$checks=[
    'jihad-group-select-four-levels'=>count($defs)===1 && $defs[0]['options']===
        ['novpa'=>'نوپا','faal'=>'فعال','pishro'=>'پیشرو','olgoo'=>'الگو'],
    'admin-only-select-valid-value'=>$clean===['group_level'=>'pishro'],
    'reject-user-invented-option'=>mustReject(fn()=>AdminFields::sanitizeValues($form,['group_level'=>'admin'])),
    'reject-array-instead-of-scalar'=>mustReject(fn()=>AdminFields::sanitizeValues($form,['group_level'=>['faal']])),
    'blank-value-permitted-for-existing-rows'=>AdminFields::sanitizeValues($form,[])===['group_level'=>''],
    'missing-defined-admin-fields-is-empty'=>AdminFields::definitions(['settings'=>[]])===[],
    'excel-default-column-added'=>in_array('admin:group_level',array_column($defaults['excel']['columns'],'field'),true),
    'preexisting-excel-profile-keeps-new-column'=>in_array('admin:group_level',array_column($merged['excel']['columns'],'field'),true),
    'disabled-existing-column-remains-disabled'=>array_values(array_filter(
        $kept['excel']['columns'],static fn($x)=>($x['field']??'')==='admin:group_level'
    ))[0]['enabled']===false,
    'separate-form-definition-only'=>AdminFields::definitions(['settings'=>[], 'steps'=>$form['steps']])===[],
    'admin-definitions-from-manager'=>count($dynamic)===2 && $dynamic[1]['options']===$defs[0]['options'],
    'reject-duplicate-identifiers'=>mustReject(fn()=>AdminFields::sanitizeDefinitions([
        ['key'=>'same','label'=>'اول','type'=>'text'],['key'=>'same','label'=>'دوم','type'=>'text']
    ],$form)),
    'reject-name-collision-with-public-form'=>mustReject(fn()=>AdminFields::sanitizeDefinitions([
        ['key'=>'group_name','label'=>'جدید','type'=>'text']
    ],$form)),
    'reject-unvalidated-option-key'=>mustReject(fn()=>AdminFields::sanitizeDefinitions([
        ['key'=>'group_level','label'=>'سطح','type'=>'select','options'=>'<script>|خطر']
    ],$form)),
    'reject-invalid-type'=>mustReject(fn()=>AdminFields::sanitizeDefinitions([
        ['key'=>'secret','label'=>'فایل','type'=>'file']
    ],$form)),
    'reject-many-fields'=>mustReject(fn()=>AdminFields::sanitizeDefinitions(array_fill(0,31,[
        'key'=>'f','label'=>'F','type'=>'text',
    ]),$form)),
];
foreach($checks as $name=>$pass){echo ($pass?'PASS ':'FAIL ').$name.PHP_EOL;if(!$pass)exit(1);}

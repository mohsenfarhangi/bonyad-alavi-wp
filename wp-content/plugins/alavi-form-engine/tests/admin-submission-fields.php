<?php
declare(strict_types=1);

function sanitize_key($value): string {return preg_replace('/[^a-z0-9_\-]/','',strtolower((string)$value));}
function sanitize_text_field($value): string {return trim(strip_tags((string)$value));}
function sanitize_textarea_field($value): string {return trim(strip_tags((string)$value));}

spl_autoload_register(static function(string $class): void {
    $prefix='BonyadAlavi\\FormEngine\\';
    if(!str_starts_with($class,$prefix)) return;
    $file=dirname(__DIR__).'/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';
    if(is_file($file)) require_once $file;
});

use BonyadAlavi\FormEngine\Admin\AdminSubmissionFields;
use BonyadAlavi\FormEngine\Admin\FormDataPresenter;
use BonyadAlavi\FormEngine\Export\ExportProfile;
use BonyadAlavi\FormEngine\Export\ExportConfigSanitizer;

$form=[
    'slug'=>'jihadi-group-registration',
    'steps'=>[['title'=>'اطلاعات عمومی','items'=>[['name'=>'name','type'=>'text','label'=>'نام گروه']]]],
    'settings'=>['admin_fields'=>[
        ['name'=>'group_level','label'=>'سطح گروه','type'=>'select','options'=>[
            'new'=>'نوپا','active'=>'فعال','leading'=>'پیشرو','model'=>'الگو',
        ]],
    ]],
];

$admin=AdminSubmissionFields::fieldMap($form);
$presenter=(new ReflectionClass(FormDataPresenter::class))->newInstanceWithoutConstructor();
$sections=$presenter->sections($form);
$existing=['name'=>'گروه نمونه','__afe_admin_group_level'=>'active','__afe_admin_legacy'=>'old'];
$valid=AdminSubmissionFields::sanitizeInput($form,['__afe_admin_group_level'=>'leading'],$existing);
$invalid=AdminSubmissionFields::sanitizeInput($form,['__afe_admin_group_level'=>'<script>x</script>'],$existing);
$spoof=AdminSubmissionFields::sanitizeInput($form,['__afe_admin_other'=>'HACK'],[]);
$public=AdminSubmissionFields::preserve($existing,['name'=>'نام جدید','__afe_admin_group_level'=>'malicious']);
$profile=ExportProfile::resolve($form,['excel'=>['columns'=>[
    ['field'=>'name','label'=>'نام گروه','enabled'=>true,'width'=>25],
]]]);
$defaults=ExportProfile::defaults($form);
$fields=(new ReflectionClass(ExportConfigSanitizer::class))->newInstanceWithoutConstructor();
$ref=new ReflectionMethod(ExportConfigSanitizer::class,'fieldMap');
$allowed=$ref->invoke($fields,$form);
$duplicate=AdminSubmissionFields::sanitizeDefinitions([
    ['name'=>'group_level','label'=>'سطح','type'=>'select','options'=>"new|نوپا\nactive|فعال"],
    ['name'=>'group_level','label'=>'تکراری','type'=>'text'],
    ['name'=>'!!','label'=>'نامعتبر','type'=>'text'],
]);
$checks=[
    'valid-choices-preserved'=>($admin['__afe_admin_group_level']['options']??null)===[
        'new'=>'نوپا','active'=>'فعال','leading'=>'پیشرو','model'=>'الگو'],
    'separate-section-in-admin-presenter'=>count($sections)===2
        && ($sections[1]['step']['title']??'')==='اطلاعات اختصاصی مدیریت'
        && ($sections[1]['fields'][0]['name']??'')==='__afe_admin_group_level',
    'admin-edit-accepts-valid-choice'=>($valid['__afe_admin_group_level']??null)==='leading',
    'admin-edit-rejects-forged-value'=>($invalid['__afe_admin_group_level']??null)==='',
    'admin-edit-rejects-unknown-field'=>!isset($spoof['__afe_admin_other']),
    'public-resubmit-preserves-stored-admin-data'=>($public['__afe_admin_group_level']??null)==='active'
        && ($public['__afe_admin_legacy']??null)==='old',
    'excel-default-includes-admin-field'=>count(array_filter($defaults['excel']['columns'],fn($c)=>$c['field']==='__afe_admin_group_level'))===1,
    'existing-custom-excel-profile-adds-new-admin-field'=>count($profile['excel']['columns'])===2
        && ($profile['excel']['columns'][1]['enabled']??null)===true,
    'excel-config-accepts-admin-field'=>($allowed['__afe_admin_group_level']??'')==='سطح گروه',
    'duplicate-and-invalid-definition-ignored'=>count($duplicate)===1,
    'admin-only-value-is-not-frontend-schema'=>!in_array('__afe_admin_group_level',array_column($form['steps'][0]['items'],'name'),true),
];
foreach($checks as $name=>$ok) {
    echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL;
    if(!$ok)exit(1);
}

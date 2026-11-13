<?php
declare(strict_types=1);
$base=dirname(__DIR__);
$read=static fn(string $path):string=>(string)file_get_contents($base.'/'.$path);
$forms=$read('src/Admin/FormsPage.php');
$presenter=$read('src/Admin/FormDataPresenter.php');
$service=$read('src/Submission/SubmissionService.php');
$excel=$read('src/Export/ExcelExporter.php');
$pdf=$read('src/Export/PdfTemplateRenderer.php');
$profile=$read('src/Export/ExportProfile.php');
$adminJs=$read('assets/js/admin.js');
$entry=$read('alavi-form-engine.php');
$theme=(string)file_get_contents(dirname($base,2).'/themes/ostadsho-child/inc/forms/class-ba-jihadi-group-registration-form.php');

$checks=[
    'dedicated-admin-tab'=>str_contains($forms,"'admin_fields'=>'فیلدهای اختصاصی مدیریت'")
        && str_contains($forms,'data-afe-form-tab-panel="admin_fields"')
        && str_contains($forms,'renderAdminSubmissionFields($form)'),
    'admin-config-save'=>str_contains($forms,"'admin_fields'") && str_contains($forms,'AdminSubmissionFields::sanitizeDefinitions'),
    'frontend-does-not-render-admin-fields'=>str_contains($presenter,'AdminSubmissionFields::fieldMap($form)')
        && !str_contains($read('src/Form/Renderer.php'),'AdminSubmissionFields::fieldMap'),
    'admin-edit-validation-and-display'=>str_contains($presenter,'AdminSubmissionFields::fieldMap') && str_contains($presenter,'sections($form,false)')
        && str_contains($presenter,"'title'=>'اطلاعات اختصاصی مدیریت'"),
    'public-resubmit-preserves-admin-data'=>str_contains($service,'AdminSubmissionFields::preserve')
        && str_contains($service,'array_key_exists') && str_contains($service,'admin_fields'),
    'excel-list-and-single'=>str_contains($excel,'AdminSubmissionFields::fieldMap')
        && str_contains($excel,'$this->presenter->sections($form)')
        && str_contains($profile,'AdminSubmissionFields::fieldMap'),
    'pdf-token-and-fields-table'=>str_contains($pdf,'AdminSubmissionFields::fieldMap')
        && str_contains($pdf,'$this->presenter->sections($form)'),
    'admin-UI-dynamic-fields'=>str_contains($adminJs,'initAdminSubmissionFields')
        && str_contains($adminJs,'data-afe-admin-fields-add')
        && str_contains($adminJs,'data-afe-admin-fields-remove'),
    'jihadi-group-level-four-options'=>str_contains($theme,"'name' => 'group_level'")
        && str_contains($theme,"'new' => 'نوپا'")
        && str_contains($theme,"'active' => 'فعال'")
        && str_contains($theme,"'leading' => 'پیشرو'")
        && str_contains($theme,"'model' => 'الگو'"),
    'development-asset-version'=>str_contains($entry,'Version: 1.0.28-dev.11')
        && str_contains($entry,"define('AFE_VERSION', '1.0.28-dev.11')"),
];
foreach($checks as $name=>$ok) {
    echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL;
    if(!$ok)exit(1);
}

<?php
declare(strict_types=1);

$base=dirname(__DIR__);
$read=static fn($path)=>file_get_contents($base.'/'.$path) ?: '';
$theme=file_get_contents(dirname(__DIR__,3).'/themes/ostadsho-child/inc/forms/class-ba-jihadi-group-registration-form.php') ?: '';
$migrator=$read('src/Database/Migrator.php');
$repo=$read('src/Repository/SubmissionRepository.php');
$submission=$read('src/Admin/SubmissionsPage.php');
$admin=$read('src/Admin/FormsPage.php');
$service=$read('src/Submission/SubmissionService.php');
$profile=$read('src/Export/ExportProfile.php');
$excel=$read('src/Export/ExcelExporter.php');
$pdf=$read('src/Export/PdfTemplateRenderer.php');
$sanitizer=$read('src/Export/ExportConfigSanitizer.php');
$formJs=$read('assets/js/admin.js');
$entry=$read('alavi-form-engine.php');
$checks=[
    'jihad-level-select'=>$theme!=='' && str_contains($theme, "'key' => 'group_level'")
        && str_contains($theme,"'novpa' => 'نوپا'")
        && str_contains($theme,"'faal' => 'فعال'")
        && str_contains($theme,"'pishro' => 'پیشرو'")
        && str_contains($theme,"'olgoo' => 'الگو'"),
    'db-migration-and-health'=>str_contains($migrator,'CREATE TABLE {$p}afe_submission_admin_fields')
        && str_contains($migrator,"'afe_submission_admin_fields'"),
    'single-and-bulk-purge-cleanup'=>str_contains($repo,'function adminFields(')
        && str_contains($repo,'function saveAdminFields(')
        && str_contains($repo, "\$wpdb->delete(\$p.'afe_submission_admin_fields'"),
    'admin-only-per-form-tab'=>str_contains($admin,"'admin_fields'=>'فیلدهای مدیریتی'")
        && str_contains($admin,'data-afe-admin-meta-rows')
        && str_contains($admin,'AdminFields::sanitizeDefinitions('),
    'javascript-works-with-admin-rows'=>str_contains($formJs,'initAdminMetaFields();')
        && str_contains($formJs,'data-afe-admin-meta-add')
        && str_contains($formJs,'data-afe-admin-meta-remove'),
    'admin-detail-and-edit-permission'=>str_contains($submission,'AdminFields::definitions($form)')
        && str_contains($submission, "FormAccess::EDIT")
        && str_contains($submission,"'afe_admin_fields'")
        && str_contains($submission,'AdminFields::sanitizeValues($form,$inputAdmin)')
        && str_contains($submission,'saveAdminFields('),
    'no-submission-data-json-overwrite'=>!str_contains($service,"'data_json'=>wp_json_encode(\$adminValues")
        && str_contains($repo,"wp_afe_submission_admin_fields")===false,
    'admin-value-audit'=>str_contains($submission,'submission.admin_fields_updated'),
    'exports-add-column-to-old-profiles'=>str_contains($profile,'AdminFields::exportKey(')
        && str_contains($profile,'$resolved') && str_contains($profile,'isset($present[$key])'),
    'export-config-accepts-admin-column'=>str_contains($sanitizer,'AdminFields::exportKey('),
    'excel-list-admin-value'=>str_contains($excel, '$this->repo->adminFields(')
        && str_contains($excel, '$adminDefs[$field]')
        && str_contains($excel,'اطلاعات اختصاصی مدیر'),
    'pdf-export-requires-explicit-admin-context'=>str_contains($pdf,'bool $includeAdminFields=false')
        && str_contains($submission,'$this->pdfTemplates->render($form,$row,true)')
        && str_contains($pdf,'$includeAdminFields?AdminFields::definitions'),
    'pdf-admin-token-only'=>str_contains($pdf,'admin_field:'),
    'print-shows-admin-values'=>str_contains($submission,'اطلاعات اختصاصی مدیر')
        && str_contains($submission,'$this->presenter->displayField($field,$values['),
    'version-and-db-bump'=>str_contains($entry,'Version: 1.0.28-dev.8')
        && str_contains($entry,"define('AFE_DB_VERSION', '1.0.5-dev.3')"),
];
foreach($checks as $name=>$ok){echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL;if(!$ok)exit(1);}

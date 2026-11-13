<?php
declare(strict_types=1);

$base=dirname(__DIR__);
$submissions=(string)file_get_contents($base.'/src/Admin/SubmissionsPage.php');
$service=(string)file_get_contents($base.'/src/Submission/SubmissionService.php');
$presenter=(string)file_get_contents($base.'/src/Admin/FormDataPresenter.php');
$excel=(string)file_get_contents($base.'/src/Export/ExcelExporter.php');
$pdf=(string)file_get_contents($base.'/src/Export/PdfTemplateRenderer.php');
$css=(string)file_get_contents($base.'/assets/css/admin.css');
$entry=(string)file_get_contents($base.'/alavi-form-engine.php');

$extract=static function(string $source,string $from,string $to):string{
    $first=strpos($source,$from);$last=$first===false?false:strpos($source,$to,$first+strlen($from));
    if($first===false||$last===false)throw new RuntimeException('Missing method anchor '.$from);
    return substr($source,$first,$last-$first);
};
$detail=$extract($submissions,'private function detail(int $id): void','private function renderAdminFieldsSidebar');
$sidebar=$extract($submissions,'private function renderAdminFieldsSidebar','private function handleDetailPost');
$post=$extract($submissions,'private function handleDetailPost','private function renderActionLogs');
$save=$extract($service,'public function updateAdminSubmissionFields','public function updateSubmissionDataAdmin');
$sanitize=$extract($presenter,'public function sanitizeSubmitted','public function fieldMap');
$checks=[
    'main-detail-omits-metadata-section'=>str_contains($detail,'$this->presenter->sections($form,false)'),
    'sidebar-is-outside-main-edit-form'=>strpos($detail,"echo '</div><aside>';") < strpos($detail,'$this->renderAdminFieldsSidebar('),
    'sidebar-in-all-view-modes'=>str_contains($detail,'$this->renderAdminFieldsSidebar($form,$row,$trashed)')
        && str_contains($sidebar,'AdminSubmissionFields::fieldMap($form)')
        && !str_contains($detail,'if($editing) $this->renderAdminFieldsSidebar('),
    'sidebar-own-save-form-and-nonce'=>str_contains($sidebar,'class="afe-sidebar-admin-fields-form"')
        && str_contains($sidebar,"wp_nonce_field('afe_submission_edit_'")
        && str_contains($sidebar,'name="afe_detail_action" value="admin_fields"')
        && str_contains($sidebar, ",'afe_submission_meta')"),
    'sidebar-edit-capability-and-form-access'=>str_contains($sidebar,'Capabilities::EDIT_SUBMISSIONS')
        && str_contains($sidebar,'FormAccess::EDIT') && str_contains($sidebar,'!$trashed'),
    'read-only-view-without-edit-permission'=>str_contains($sidebar,'if($canEdit)')
        && str_contains($sidebar,'$this->presenter->displayField'),
    'sidebar-action-checks-nonce-and-permissions'=>str_contains($post,"check_admin_referer('afe_submission_edit_'")
        && str_contains($post, "==='admin_fields'")
        && str_contains($post,'Capabilities::EDIT_SUBMISSIONS')
        && str_contains($post, 'updateAdminSubmissionFields((int)'),
    'service-defence-in-depth'=>str_contains($save,"current_user_can('afe_edit_submissions')")
        && str_contains($save,'$this->formAccess->can($slug,FormAccess::EDIT)')
        && str_contains($save, "!empty(") && str_contains($save, "'trashed_at'"),
    'reject-injected-fields-and-forged-select'=>str_contains($save, '!array_key_exists($name,$fields)')
        && str_contains($save, '!array_key_exists((string)$value,(array)$field'),
    'metadata-update-without-editing-applicant-values'=>str_contains($save,'AdminSubmissionFields::sanitizeInput($form,$input,$existing)')
        && str_contains($save, "'data_json'=>wp_json_encode(")
        && str_contains($save,"'submission.admin_fields_updated'")
        && !str_contains($save,'duplicatePolicy->evaluate('),
    'main-editor-never-overwrites-metadata'=>str_contains($sanitize,'sections($form,false)')
        && !str_contains($sanitize,'AdminSubmissionFields::sanitizeInput'),
    'exports-still-use-admin-section'=>str_contains($pdf,'$this->presenter->sections($form)')
        && str_contains($excel,'$this->presenter->sections($form)'),
    'sidebar-css-scoped'=>str_contains($css,'.afe-submission-admin-fields .afe-sidebar-admin-field'),
    'bumped-assets-version'=>str_contains($entry,'Version: 1.0.28-dev.10')
        && str_contains($entry,"define('AFE_VERSION', '1.0.28-dev.10')"),
];
foreach($checks as $name=>$ok) {
    echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL;
    if(!$ok)exit(1);
}

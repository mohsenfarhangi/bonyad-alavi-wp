<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$plugin=file_get_contents($root.'/src/Core/Plugin.php') ?: '';
$service=file_get_contents($root.'/src/Submission/SubmissionService.php') ?: '';
$renderer=file_get_contents($root.'/src/Form/Renderer.php') ?: '';
$policy=file_get_contents($root.'/src/Duplicate/DuplicatePolicy.php') ?: '';
$js=file_get_contents($root.'/assets/js/frontend.js') ?: '';
$css=file_get_contents($root.'/assets/css/frontend.css') ?: '';

$checks=[
    'ajax-public-and-authenticated'=>str_contains($plugin,"wp_ajax_afe_check_duplicate")
        && str_contains($plugin,"wp_ajax_nopriv_afe_check_duplicate")
        && str_contains($plugin,'ajaxCheckDuplicate'),
    'endpoint-nonce-protected'=>str_contains($service,'checkDuplicateHttp')
        && str_contains($service, "wp_verify_nonce(\$nonce, 'afe_submit_'.\$slug)"),
    'endpoint-skips-incomplete'=>str_contains($service,'missingFields($form, $data)')
        && str_contains($service,"'ready'=>false"),
    'endpoint-excludes-authorized-current-submission'=>str_contains($service,'$this->canApplicantAccess($form, $existing)')
        && str_contains($service,'$excludeSubmissionId = (int)$existing[\'id\']'),
    'reference-url-remains-access-controlled'=>str_contains($service,'duplicateReferenceUrl($form, $row)'),
    'renderer-exposes-live-fields'=>str_contains($renderer,'data-afe-duplicate-fields')
        && str_contains($renderer,'data-afe-duplicate-live'),
    'frontend-only-watches-configured-fields'=>str_contains($js,'function initLiveDuplicateCheck')
        && str_contains($js,'fields.includes(path)')
        && str_contains($js,"action', 'afe_check_duplicate'"),
    'frontend-waits-for-complete-controls'=>str_contains($js,'function duplicatePathComplete')
        && str_contains($js,'valued.every(duplicateControlComplete)')
        && str_contains($js,'requiredSlots > 0')
        && str_contains($js,'dataset.afeDateMask'),
    'frontend-debounces-and-aborts-stale'=>str_contains($js,'window.setTimeout(run, 450)')
        && str_contains($js,'AbortController')
        && str_contains($js,"error?.name === 'AbortError'"),
    'frontend-avoids-file-upload-during-check'=>str_contains($js,"if (typeof value !== 'string') continue")
        && str_contains($js,"name.startsWith('afe_data[')"),
    'live-status-ui-only-duplicate'=>str_contains($css,'.afe-duplicate-live[data-state="error"]')
        && !str_contains($css,'.afe-duplicate-live[data-state="success"]')
        && str_contains($css,'.afe-duplicate-live[data-state="warning"]')
        && str_contains($js,'if (!duplicate)')
        && str_contains($js,'clearDuplicateNotice(form);'),
    'live-errors-remain-silent'=>str_contains($js,'Live duplicate checking is advisory UX only')
        && !str_contains($js,'بررسی تکراری بودن انجام نشد'),
    'final-submit-check-still-present'=>substr_count($service,'$this->duplicatePolicy->evaluate($form, $data') >= 2,
    'policy-completeness-api'=>str_contains($policy,'public function missingFields')
        && str_contains($policy,'hasMeaningfulValue'),
];

$failed=[];
foreach($checks as $name=>$ok){
    echo $name.': '.($ok?'PASS':'FAIL').PHP_EOL;
    if(!$ok) $failed[]=$name;
}
if($failed){
    fwrite(STDERR,'Failed: '.implode(', ',$failed).PHP_EOL);
    exit(1);
}

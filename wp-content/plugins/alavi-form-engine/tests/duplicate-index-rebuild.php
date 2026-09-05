<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$service=file_get_contents($root.'/src/Submission/SubmissionService.php') ?: '';
$forms=file_get_contents($root.'/src/Admin/FormsPage.php') ?: '';
$repository=file_get_contents($root.'/src/Duplicate/DuplicateRepository.php') ?: '';
$submissions=file_get_contents($root.'/src/Repository/SubmissionRepository.php') ?: '';
$policy=file_get_contents($root.'/src/Duplicate/DuplicatePolicy.php') ?: '';

$checks=[
    'runtime-self-heal-signature'=>str_contains($service,'afe_duplicate_index_signatures')
        && str_contains($service,'ensureDuplicateIndex($form)')
        && str_contains($service,"'version'=>2"),
    'live-and-submit-ensure-index'=>substr_count($service,'$this->ensureDuplicateIndex($form);') >= 2,
    'admin-save-rebuilds-history'=>str_contains($forms,'$this->service->rebuildDuplicateIndex($slug)'),
    'rebuild-clears-old-fingerprints'=>str_contains($repository,'public function clearForForm')
        && str_contains($service,'$this->duplicateRepository->clearForForm($slug)'),
    'rebuild-processes-active-history'=>str_contains($submissions,'public function activeForFormAfterId')
        && str_contains($submissions,'trashed_at IS NULL')
        && str_contains($service,'activeForFormAfterId($slug, $afterId, 250)'),
    'rebuild-oldest-first'=>str_contains($submissions,'ORDER BY id ASC'),
    'rebuild-resets-stale-duplicate-metadata'=>str_contains($submissions,'resetDuplicateMetadataForForm')
        && str_contains($service,'resetDuplicateMetadataForForm($slug)'),
    'incomplete-combinations-not-duplicates'=>str_contains($policy,'if ($this->missingFields($form, $data) !== [])')
        && str_contains($policy,"new DuplicateDecision(false, '', null"),
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

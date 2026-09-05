<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$renderer=file_get_contents($root.'/src/Form/PreviewRenderer.php') ?: '';
$formRenderer=file_get_contents($root.'/src/Form/Renderer.php') ?: '';
$js=file_get_contents($root.'/assets/js/frontend.js') ?: '';
$css=file_get_contents($root.'/assets/css/frontend.css') ?: '';

$checks=[
    'stored-preview-links'=>str_contains($renderer,'afe-preview-file-link')
        && str_contains($renderer,'target="_blank"')
        && str_contains($renderer,'rel="noopener noreferrer"')
        && str_contains($renderer,'previewFileUrl'),
    'attachment-ownership-check'=>str_contains($renderer,"_afe_submission_id")
        && str_contains($renderer,'wp_get_attachment_url'),
    'existing-file-url-marker'=>str_contains($formRenderer,'data-file-url='),
    'live-preview-file-links'=>str_contains($js,'function previewFileLinks')
        && str_contains($js,'URL.createObjectURL')
        && str_contains($js,'safePreviewFileUrl')
        && str_contains($js,"target=\"_blank\"")
        && str_contains($js,'rel="noopener noreferrer"'),
    'preview-file-link-style'=>str_contains($css,'.afe-preview-file-link'),
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

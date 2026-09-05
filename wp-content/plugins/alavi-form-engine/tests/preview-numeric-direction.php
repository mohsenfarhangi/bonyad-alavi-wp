<?php
declare(strict_types=1);

$root=dirname(__DIR__);
$frontend=(string)file_get_contents($root.'/assets/css/frontend.css');
$renderer=(string)file_get_contents($root.'/src/Form/PreviewRenderer.php');
$js=(string)file_get_contents($root.'/assets/js/frontend.js');

$genericBlock='';
if (preg_match('/\.afe-shell\.afe-isolation-strong\s+\.afe-form\s+\.afe-control\s*\{([^}]*)\}/s',$frontend,$m)===1) {
    $genericBlock=(string)$m[1];
}

$checks=[
    'generic-control-has-no-text-align'=>!str_contains($genericBlock,'text-align'),
    'preview-ltr-css'=>str_contains($frontend,'.afe-preview-value[data-afe-ltr="1"]')
        && str_contains($frontend,'.afe-preview-repeater td[data-afe-ltr="1"]')
        && str_contains($frontend,'text-align:left !important'),
    'preview-renderer-ltr'=>str_contains($renderer,'usesLeftToRightPreview')
        && str_contains($renderer,'data-afe-ltr="1"')
        && str_contains($renderer,'<td dir="ltr" data-afe-ltr="1">'),
    'preview-live-detection'=>str_contains($js,'function previewTextLooksNumeric')
        && str_contains($js,'function previewFieldUsesLtr')
        && str_contains($js,'function setPreviewDirection')
        && str_contains($js,'data-afe-ltr="1"'),
];

$failed=false;
foreach($checks as $name=>$ok){
    echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL;
    if(!$ok)$failed=true;
}
exit($failed?1:0);

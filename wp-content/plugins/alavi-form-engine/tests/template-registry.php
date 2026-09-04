<?php
declare(strict_types=1);

require_once __DIR__.'/../src/Template/TemplateDefinition.php';
require_once __DIR__.'/../src/Template/TemplateRegistry.php';
require_once __DIR__.'/../src/Template/TemplateResolver.php';

use BonyadAlavi\FormEngine\Template\TemplateRegistry;
use BonyadAlavi\FormEngine\Template\TemplateResolver;

$registry=new TemplateRegistry();
$resolver=new TemplateResolver($registry);

$form=['template'=>null,'settings'=>['preview_template'=>'']];
$codeForm=['template'=>'<main>{{steps}}</main>','settings'=>['preview_template'=>'<div>{{preview_fields}}</div>']];
$step=['template'=>null];
$codeStep=['template'=>'<section>{{items}}</section>'];
$defaultForm=$resolver->defaultForm($form);
$defaultPreview=$resolver->defaultPreview($form);

$checks=[
    'registry-core-count'=>count($registry->all())===3,
    'form-default-html'=>str_contains($defaultForm,'afe-form-header') && str_contains($defaultForm,'{{progress}}') && str_contains($defaultForm,'{{steps}}'),
    'preview-default-html'=>str_contains($defaultPreview,'afe-preview-layout') && str_contains($defaultPreview,'{{preview_fields}}'),
    'step-default-token'=>$resolver->defaultStep($step)==='{{items}}',
    'code-form-default'=>$resolver->defaultForm($codeForm)==='<main>{{steps}}</main>',
    'code-preview-default'=>$resolver->defaultPreview($codeForm)==='<div>{{preview_fields}}</div>',
    'code-step-default'=>$resolver->defaultStep($codeStep)==='<section>{{items}}</section>',
    'same-as-default-not-override'=>$resolver->normalizeOverride("\r\n".$defaultForm."\r\n",$defaultForm)==='',
    'custom-is-override'=>$resolver->normalizeOverride('<div>{{steps}}</div>',$defaultForm)==='<div>{{steps}}</div>',
    'editor-shows-default'=>$resolver->editorValue('',$defaultForm)===$defaultForm,
    'editor-shows-custom'=>$resolver->editorValue('<div>custom</div>',$defaultForm)==='<div>custom</div>',
];

$failed=false;
foreach($checks as $name=>$ok){ echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL; if(!$ok)$failed=true; }
exit($failed?1:0);

<?php
declare(strict_types=1);

function esc_html(string $value): string { return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function esc_attr(string $value): string { return htmlspecialchars($value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8'); }
function wp_strip_all_tags(string $value): string { return strip_tags($value); }

spl_autoload_register(static function(string $class): void {
    $prefix='BonyadAlavi\\FormEngine\\';
    if (!str_starts_with($class,$prefix)) return;
    $file=dirname(__DIR__).'/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';
    if (is_readable($file)) require_once $file;
});

use BonyadAlavi\FormEngine\Admin\FormsPage;

$source=(string)file_get_contents(dirname(__DIR__).'/src/Admin/FormsPage.php');
$js=(string)file_get_contents(dirname(__DIR__).'/assets/js/admin.js');

$fieldsStart=strpos($source,'data-afe-form-tab-panel="fields"');
$duplicateStart=strpos($source,'data-afe-form-tab-panel="duplicate"',$fieldsStart?:0);
$fieldsSection=($fieldsStart!==false && $duplicateStart!==false)?substr($source,$fieldsStart,$duplicateStart-$fieldsStart):'';
$templatesStart=strpos($source,'data-afe-form-tab-panel="templates"');
$assetsStart=strpos($source,'data-afe-form-tab-panel="assets"',$templatesStart?:0);
$templatesSection=($templatesStart!==false && $assetsStart!==false)?substr($source,$templatesStart,$assetsStart-$templatesStart):'';

$ref=new ReflectionClass(FormsPage::class);
$page=$ref->newInstanceWithoutConstructor();
$render=$ref->getMethod('renderOrderItem');
$render->setAccessible(true);

ob_start();
$render->invoke($page,['name'=>'leader_mobile','type'=>'tel','label'=>'شماره موبایل مسئول'],'field_order[identity][]','');
$fieldHtml=(string)ob_get_clean();

ob_start();
$render->invoke($page,['name'=>'intro','type'=>'html','html'=>'<strong>راهنمای فرم</strong>'],'field_order[identity][]','');
$htmlBlock=(string)ob_get_clean();

$checks=[
    'settings-form-wraps-sidebar'=>strpos($source,'<form method="post" id="afe-form-settings">') < strpos($source,'data-afe-admin-layout'),
    'sidebar-rendered-in-admin-side'=>str_contains($source,'$this->renderFieldOverrideSidebar($codeForm,$storedOverrides,$resolvedFlat);'),
    'fields-tab-ordering-only'=>str_contains($fieldsSection,'$this->renderFieldOrdering($form);') && !str_contains($fieldsSection,'field_override[') && !str_contains($fieldsSection,'قالب اختصاصی هر مرحله'),
    'step-templates-moved-to-templates'=>str_contains($templatesSection,'قالب اختصاصی هر مرحله'),
    'field-item-is-clickable'=>str_contains($fieldHtml,'data-afe-field-override-trigger') && str_contains($fieldHtml,'data-afe-field-path="leader_mobile"') && str_contains($fieldHtml,'aria-pressed="false"'),
    'html-block-not-configurable'=>!str_contains($htmlBlock,'data-afe-field-override-trigger') && str_contains($htmlBlock,'>HTML<'),
    'sidebar-js-controller'=>str_contains($js,'const initFieldOverrideSidebar = () =>') && str_contains($js,'initFieldOverrideSidebar();'),
    'sidebar-follows-fields-tab'=>str_contains($js,"fieldSidebar.hidden = key !== 'fields'") && str_contains($js,"adminLayout?.classList.toggle('is-fields-tab', key === 'fields')"),
];

foreach($checks as $name=>$ok){
    echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL;
    if(!$ok) exit(1);
}

<?php
declare(strict_types=1);

spl_autoload_register(static function(string $class): void {
    $prefix='BonyadAlavi\\FormEngine\\';
    if (!str_starts_with($class,$prefix)) return;
    $file=dirname(__DIR__).'/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';
    if (is_readable($file)) require_once $file;
});

use BonyadAlavi\FormEngine\Submission\SubmissionService;

$ref=new ReflectionClass(SubmissionService::class);
$service=$ref->newInstanceWithoutConstructor();
$method=$ref->getMethod('reorderItems');
$items=[
    ['name'=>'html_a','type'=>'html'],
    ['name'=>'first','type'=>'text'],
    ['name'=>'second','type'=>'text'],
];
$result=$method->invoke($service,$items,['second','html_a']);
$names=array_map(static fn($item)=>$item['name'],$result);
$checks=[
    'reorders-within-scope'=>$names===['second','html_a','first'],
    'new-code-item-appended'=>end($names)==='first',
];
foreach($checks as $name=>$ok){ echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL; if(!$ok) exit(1); }

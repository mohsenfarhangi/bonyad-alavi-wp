<?php
declare(strict_types=1);
function sanitize_key(string $key): string { return preg_replace('/[^a-z0-9_\-]/','',strtolower($key)) ?? ''; }
require_once __DIR__.'/../src/Core/FormAccess.php';
use BonyadAlavi\FormEngine\Core\FormAccess;
$cases=[
    ['view-cap',FormAccess::capability('jihadi-group-registration',FormAccess::VIEW)==='afe_form_jihadi_group_registration_view'],
    ['edit-cap',FormAccess::capability('example-form',FormAccess::EDIT)==='afe_form_example_form_edit'],
    ['levels',count(FormAccess::levels())===6],
];
$failed=array_filter($cases,static fn($case)=>!$case[1]);
foreach($cases as [$name,$ok]) echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL;
exit($failed?1:0);

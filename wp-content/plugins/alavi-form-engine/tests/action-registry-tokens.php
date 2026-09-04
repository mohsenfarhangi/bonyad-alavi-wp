<?php
declare(strict_types=1);

function wp_json_encode(mixed $value, int $flags = 0): string|false { return json_encode($value, $flags); }
function get_option(string $name, mixed $default=null): mixed { return $default; }

spl_autoload_register(static function(string $class): void {
    $prefix='BonyadAlavi\\FormEngine\\';
    if (!str_starts_with($class,$prefix)) return;
    $file=dirname(__DIR__).'/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';
    if (is_readable($file)) require_once $file;
});

use BonyadAlavi\FormEngine\Actions\ActionContext;
use BonyadAlavi\FormEngine\Actions\ActionRegistry;
use BonyadAlavi\FormEngine\Actions\Tokens\TokenRegistry;
use BonyadAlavi\FormEngine\Actions\Tokens\TokenResolver;

$resolver=new TokenResolver();
$registry=new ActionRegistry();
$registry->registerCore($resolver);
$tokens=new TokenRegistry();

$form=[
    'steps'=>[[
        'items'=>[
            ['type'=>'text','name'=>'leader_mobile','label'=>'شماره موبایل مسئول'],
            ['type'=>'repeater','name'=>'members','label'=>'اعضا','fields'=>[
                ['type'=>'text','name'=>'national_id','label'=>'کد ملی عضو'],
            ]],
            ['type'=>'html','name'=>'ignored','label'=>'نباید دیده شود'],
        ],
    ]],
];
$palette=$tokens->forForm($form);
$context=new ActionContext(
    7,
    'jihadi-group-registration',
    ['leader_mobile'=>'09121234567','members'=>[['national_id'=>'001'],['national_id'=>'002']],'not_in_schema'=>'secret'],
    ['tracking_code'=>'ABC123','status'=>'new'],
    'submission.submitted',
    'فرم جهادی',
    ['leader_mobile','members','members.national_id']
);

$checks=[
    'email-definition'=>$registry->get('email')?->label==='ارسال ایمیل',
    'webhook-schema'=>isset($registry->get('webhook')?->settingsSchema['payload']),
    'field-token'=>isset($palette['{{field:leader_mobile}}']),
    'repeater-child-token'=>isset($palette['{{field:members.national_id}}']),
    'html-not-token'=>!isset($palette['{{field:ignored}}']),
    'resolver-base'=>$resolver->resolve('{{form_title}} / {{tracking_code}}',$context)==='فرم جهادی / ABC123',
    'resolver-field'=>$resolver->resolve('{{field:leader_mobile}}',$context)==='09121234567',
    'resolver-repeater-child'=>$resolver->resolve('{{field:members.national_id}}',$context)==='["001","002"]',
    'resolver-whitelist'=>$resolver->resolve('{{field:not_in_schema}}',$context)==='',
];

$failed=false;
foreach($checks as $name=>$ok){
    echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL;
    if(!$ok) $failed=true;
}
exit($failed?1:0);

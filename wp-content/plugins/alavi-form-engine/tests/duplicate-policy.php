<?php
declare(strict_types=1);

function sanitize_key(string $key): string { return strtolower((string)preg_replace('/[^a-z0-9_\-]/i','',$key)); }
function sanitize_textarea_field(string $value): string { return trim(strip_tags($value)); }
function sanitize_text_field(string $value): string { return trim(strip_tags($value)); }
function wp_json_encode(mixed $value,int $flags=0): string|false { return json_encode($value,$flags); }

spl_autoload_register(static function(string $class): void {
    $prefix='BonyadAlavi\\FormEngine\\';
    if (!str_starts_with($class,$prefix)) return;
    $file=dirname(__DIR__).'/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';
    if (is_readable($file)) require_once $file;
});

use BonyadAlavi\FormEngine\Duplicate\DuplicateFingerprint;
use BonyadAlavi\FormEngine\Duplicate\DuplicatePolicy;
use BonyadAlavi\FormEngine\Duplicate\DuplicateRepository;

final class PolicyWpdb { public string $prefix='wp_'; }
$GLOBALS['wpdb']=new PolicyWpdb();

$fingerprints=new DuplicateFingerprint();
$repo=new DuplicateRepository();
$policy=new DuplicatePolicy($fingerprints,$repo);

$form=[
    'slug'=>'demo',
    'settings'=>[
        'duplicate'=>[
            'enabled'=>true,
            'fields'=>['mobile','members.national_id','unknown','upload'],
            'behavior'=>'allow',
            'message'=>'  پیام سفارشی  ',
        ],
    ],
    'steps'=>[[
        'items'=>[
            ['type'=>'text','name'=>'mobile','label'=>'موبایل'],
            ['type'=>'file','name'=>'upload','label'=>'فایل'],
            ['type'=>'html','name'=>'intro','label'=>'HTML'],
            ['type'=>'repeater','name'=>'members','label'=>'اعضا','fields'=>[
                ['type'=>'text','name'=>'national_id','label'=>'کد ملی'],
                ['type'=>'file','name'=>'resume','label'=>'رزومه'],
            ]],
        ],
    ]],
];

$config=$policy->config($form);
$one=$fingerprints->make('demo',['mobile','members.national_id'],[
    'mobile'=>' ۰۹۱۲  ۱۲۳۴۵۶۷ ',
    'members'=>[['national_id'=>'۰۰۱۲۳۴۵۶۷۸'],['national_id'=>'۱۲۳۴۵۶۷۸۹۰']],
]);
$two=$fingerprints->make('demo',['members.national_id','mobile'],[
    'mobile'=>'0912 1234567',
    'members'=>[['national_id'=>'0012345678'],['national_id'=>'1234567890']],
]);
$three=$fingerprints->make('demo',['mobile','members.national_id'],[
    'mobile'=>'0912 1234567',
    'members'=>[['national_id'=>'0012345678'],['national_id'=>'9999999999']],
]);

$checks=[
    'config-enabled'=>$config['enabled']===true,
    'config-field-whitelist'=>$config['fields']===['mobile','members.national_id'],
    'config-behavior'=>$config['behavior']==='allow',
    'config-message'=>$config['message']==='پیام سفارشی',
    'field-labels'=>$policy->fieldLabels($form)===['mobile'=>'موبایل','members.national_id'=>'کد ملی'],
    'fingerprint-normalization'=>$one===$two,
    'fingerprint-combination-change'=>$one!==$three,
];

$failed=false;
foreach($checks as $name=>$ok){ echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL; if(!$ok)$failed=true; }
exit($failed?1:0);

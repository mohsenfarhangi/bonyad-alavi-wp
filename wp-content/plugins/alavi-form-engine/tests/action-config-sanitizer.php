<?php
declare(strict_types=1);

function sanitize_key(string $key): string { return strtolower((string)preg_replace('/[^a-z0-9_\-\.]/i','',$key)); }
function sanitize_text_field(string $value): string { return trim(strip_tags($value)); }
function sanitize_textarea_field(string $value): string { return trim(strip_tags($value)); }
function wp_unslash(mixed $value): mixed { return $value; }
function esc_url_raw(string $value): string { return filter_var($value,FILTER_VALIDATE_URL)?$value:''; }
function wp_json_encode(mixed $value,int $flags=0): string|false { return json_encode($value,$flags); }
function apply_filters(string $hook,mixed $value,mixed ...$args): mixed { return $value; }

spl_autoload_register(static function(string $class): void {
    $prefix='BonyadAlavi\\FormEngine\\';
    if(!str_starts_with($class,$prefix)) return;
    $file=dirname(__DIR__).'/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';
    if(is_readable($file)) require_once $file;
});

use BonyadAlavi\FormEngine\Actions\ActionConfigSanitizer;
use BonyadAlavi\FormEngine\Actions\ActionDefinition;
use BonyadAlavi\FormEngine\Actions\ActionRegistry;
use BonyadAlavi\FormEngine\Actions\ActionContext;
use BonyadAlavi\FormEngine\Events\EventRegistry;

$registry=new ActionRegistry();
$registry->register(new ActionDefinition('demo','دمو','', 'developer',[
    'text'=>['type'=>'text'],
    'mode'=>['type'=>'select','options'=>['a'=>'A','b'=>'B'],'default'=>'a'],
    'field'=>['type'=>'field_select'],
    'payload'=>['type'=>'json'],
    'headers'=>['type'=>'key_value'],
    'args'=>['type'=>'repeater_text'],
    'url'=>['type'=>'url','tokens'=>true],
]), static function(ActionContext $context,array $config): void {});
$events=new EventRegistry();
$sanitizer=new ActionConfigSanitizer($registry,$events);
$form=['steps'=>[['items'=>[
    ['type'=>'text','name'=>'leader_mobile','label'=>'موبایل'],
    ['type'=>'html','name'=>'ignored'],
]]]];
$raw=[[
    'event'=>'submission.submitted',
    'actions'=>[[
        'action_key'=>'My Action!*',
        'type'=>'demo',
        'enabled'=>'1',
        'execution_policy'=>'once_per_submission',
        'on_error'=>'stop',
        'config'=>[
            'text'=>' {{tracking_code}} ',
            'mode'=>'b',
            'field'=>'leader_mobile',
            'payload'=>'{"mobile":"{{field:leader_mobile}}"}',
            'headers'=>"X-Test|{{tracking_code}}\nBadLine",
            'args'=>"{{field:leader_mobile}}\n{{tracking_code}}",
            'url'=>'https://example.test/hooks/{{field:leader_mobile}}?track={{tracking_code}}',
            'ignored'=>'should-not-save',
        ],
        'when'=>[[
            'field'=>'leader_mobile','operator'=>'in','value'=>'0912,0935'
        ]],
    ]],
],['event'=>'invalid.event','actions'=>[['type'=>'demo']]]];
$out=$sanitizer->sanitizeGroups($raw,$form);
$action=$out[0]['actions'][0]??[];
$checks=[
    'valid-group'=>count($out)===1 && ($out[0]['event']??'')==='submission.submitted',
    'key-sanitized'=>($action['action_key']??'')==='myaction',
    'policy'=>($action['execution_policy']??'')==='once_per_submission',
    'error-behavior'=>($action['on_error']??'')==='stop',
    'schema-only'=>!array_key_exists('ignored',(array)($action['config']??[])),
    'select'=>($action['config']['mode']??'')==='b',
    'field-whitelist'=>($action['config']['field']??'')==='leader_mobile',
    'json'=>($action['config']['payload']['mobile']??'')==='{{field:leader_mobile}}',
    'headers'=>($action['config']['headers']['X-Test']??'')==='{{tracking_code}}',
    'args'=>($action['config']['args']??[])===['{{field:leader_mobile}}','{{tracking_code}}'],
    'url-template-preserved'=>($action['config']['url']??'')==='https://example.test/hooks/{{field:leader_mobile}}?track={{tracking_code}}',
    'condition-in'=>($action['when'][0]['value']??[])===['0912','0935'],
];
$failed=false;
foreach($checks as $name=>$ok){echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL;if(!$ok)$failed=true;}
exit($failed?1:0);

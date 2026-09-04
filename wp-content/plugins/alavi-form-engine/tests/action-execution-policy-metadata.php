<?php
declare(strict_types=1);
function sanitize_key(string $key): string { return strtolower((string)preg_replace('/[^a-z0-9_\-\.]/i','',$key)); }
function sanitize_text_field(string $value): string { return trim(strip_tags($value)); }
function sanitize_textarea_field(string $value): string { return trim(strip_tags($value)); }
function wp_unslash(mixed $value): mixed { return $value; }
function esc_url_raw(string $value): string { return $value; }
function wp_json_encode(mixed $value,int $flags=0): string|false { return json_encode($value,$flags); }
function apply_filters(string $hook,mixed $value,mixed ...$args): mixed { return $value; }

spl_autoload_register(static function(string $class): void {
    $prefix='BonyadAlavi\\FormEngine\\'; if(!str_starts_with($class,$prefix)) return;
    $file=dirname(__DIR__).'/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php'; if(is_readable($file)) require_once $file;
});

use BonyadAlavi\FormEngine\Actions\ActionConfigSanitizer;
use BonyadAlavi\FormEngine\Actions\ActionContext;
use BonyadAlavi\FormEngine\Actions\ActionDefinition;
use BonyadAlavi\FormEngine\Actions\ActionRegistry;
use BonyadAlavi\FormEngine\Events\EventRegistry;

$registry=new ActionRegistry();
$registry->register(new ActionDefinition('always_only','Always only','','developer',[],true,false,true),static function(ActionContext $c,array $config):void{});
$out=(new ActionConfigSanitizer($registry,new EventRegistry()))->sanitizeGroups([[
    'event'=>'submission.submitted','actions'=>[[
        'type'=>'always_only','enabled'=>1,'execution_policy'=>'once_per_submission','config'=>[]
    ]]
]],['steps'=>[]]);
$policy=$out[0]['actions'][0]['execution_policy']??'';
$ok=$policy==='always';
echo ($ok?'PASS ':'FAIL ').'unsupported-execution-policy-forced-always'.PHP_EOL;
exit($ok?0:1);

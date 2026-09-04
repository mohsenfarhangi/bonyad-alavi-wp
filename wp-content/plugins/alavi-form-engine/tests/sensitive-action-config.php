<?php
declare(strict_types=1);
function sanitize_key(string $key): string { return strtolower((string)preg_replace('/[^a-z0-9_\-\.]/i','',$key)); }
function sanitize_text_field(string $v): string { return trim(strip_tags($v)); }
function sanitize_textarea_field(string $v): string { return trim(strip_tags($v)); }
function wp_unslash(mixed $v): mixed { return $v; }
function esc_url_raw(string $v): string { return filter_var($v,FILTER_VALIDATE_URL)?$v:''; }
function wp_json_encode(mixed $v,int $flags=0): string|false { return json_encode($v,$flags); }
function apply_filters(string $hook,mixed $value,mixed ...$args): mixed { return $value; }
function current_user_can(string $cap): bool { return false; }
function post_type_exists(string $type): bool { return in_array($type,['post','page'],true); }
function wp_roles(): object { return (object)['roles'=>['subscriber'=>['name'=>'Subscriber'],'administrator'=>['name'=>'Administrator']]]; }
spl_autoload_register(static function(string $class): void { $prefix='BonyadAlavi\\FormEngine\\'; if(!str_starts_with($class,$prefix)) return; $file=dirname(__DIR__).'/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php'; if(is_readable($file)) require_once $file; });
use BonyadAlavi\FormEngine\Actions\ActionConfigSanitizer;
use BonyadAlavi\FormEngine\Actions\ActionContext;
use BonyadAlavi\FormEngine\Actions\ActionDefinition;
use BonyadAlavi\FormEngine\Actions\ActionRegistry;
use BonyadAlavi\FormEngine\Events\EventRegistry;
$r=new ActionRegistry();
$r->register(new ActionDefinition('safe','Safe','', 'x',[
 'danger'=>['type'=>'boolean','default'=>false,'capability'=>'afe_manage_settings'],
 'role'=>['type'=>'role_select'], 'wf'=>['type'=>'workflow_select'], 'post_type'=>['type'=>'post_type_select'], 'post_status'=>['type'=>'post_status_select'],
]),static function(ActionContext $c,array $config):void{});
$form=['workflow'=>['new'=>'New','approved'=>'Approved'],'steps'=>[['items'=>[]]]];
$out=(new ActionConfigSanitizer($r,new EventRegistry()))->sanitizeGroups([['event'=>'submission.submitted','actions'=>[['type'=>'safe','enabled'=>1,'config'=>['danger'=>1,'role'=>'administrator','wf'=>'approved','post_type'=>'post','post_status'=>'publish']]]]],$form);
$c=$out[0]['actions'][0]['config']??[];
$checks=['capability-forces-false'=>($c['danger']??true)===false,'workflow-whitelist'=>($c['wf']??'')==='approved','post-type'=>($c['post_type']??'')==='post','post-status'=>($c['post_status']??'')==='publish','role-valid'=>($c['role']??'')==='administrator'];
$failed=false; foreach($checks as $name=>$ok){echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL;if(!$ok)$failed=true;} exit($failed?1:0);

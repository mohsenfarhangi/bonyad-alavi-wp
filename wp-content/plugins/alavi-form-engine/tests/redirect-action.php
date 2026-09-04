<?php
declare(strict_types=1);
function sanitize_key(string $key): string { return strtolower((string)preg_replace('/[^a-z0-9_\-\.]/i','',$key)); }
function wp_json_encode(mixed $value,int $flags=0): string|false { return json_encode($value,$flags); }
function home_url(string $path='/'): string { return 'https://example.test'.($path==='/'?'/':$path); }
function esc_url_raw(string $url): string { return filter_var($url,FILTER_VALIDATE_URL)?$url:''; }
function wp_parse_url(string $url,int $component=-1): mixed { return parse_url($url,$component); }
spl_autoload_register(static function(string $class): void { $prefix='BonyadAlavi\\FormEngine\\'; if(!str_starts_with($class,$prefix)) return; $file=dirname(__DIR__).'/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php'; if(is_readable($file)) require_once $file; });
use BonyadAlavi\FormEngine\Actions\ActionContext;
use BonyadAlavi\FormEngine\Actions\ActionRuntime;
use BonyadAlavi\FormEngine\Actions\RedirectAction;
use BonyadAlavi\FormEngine\Actions\Tokens\TokenResolver;
$runtime=new ActionRuntime();
$context=new ActionContext(1,'demo',['next'=>'done'],[],'submission.submitted','Demo',['next'],[],$runtime);
$action=new RedirectAction(new TokenResolver());
$action->handle($context,['url'=>'/thank-you']);
$action->handle($context,['url'=>'https://outside.test/end','allow_external'=>true]);
$blocked=false; try { $action->handle(new ActionContext(2,'demo',[],[],'submission.submitted','Demo',[],[],new ActionRuntime()),['url'=>'https://outside.test/end']); } catch(RuntimeException){ $blocked=true; }
$checks=['internal-resolved'=>$runtime->redirectUrl()==='https://example.test/thank-you','first-wins'=>$runtime->redirectUrl()!=='https://outside.test/end','external-blocked'=>$blocked];
$failed=false; foreach($checks as $name=>$ok){echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL;if(!$ok)$failed=true;} exit($failed?1:0);

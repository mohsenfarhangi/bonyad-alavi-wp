<?php
declare(strict_types=1);

function sanitize_key(string $key): string { return strtolower((string)preg_replace('/[^a-z0-9_\-\.]/i','',$key)); }
function wp_json_encode(mixed $value,int $flags=0): string|false { return json_encode($value,$flags); }
function get_userdata(int $id): object|false { return false; }
function get_user_meta(int $id,string $key,bool $single=true): mixed { return ''; }

spl_autoload_register(static function(string $class): void {
    $prefix='BonyadAlavi\\FormEngine\\';
    if (!str_starts_with($class,$prefix)) return;
    $file=dirname(__DIR__).'/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';
    if (is_readable($file)) require_once $file;
});

use BonyadAlavi\FormEngine\Actions\ActionContext;
use BonyadAlavi\FormEngine\Actions\Sms\SmsAction;
use BonyadAlavi\FormEngine\Actions\Sms\SmsMessage;
use BonyadAlavi\FormEngine\Actions\Sms\SmsProviderInterface;
use BonyadAlavi\FormEngine\Actions\Sms\SmsProviderRegistry;
use BonyadAlavi\FormEngine\Actions\Tokens\TokenResolver;

final class RoutedProvider implements SmsProviderInterface {
    public array $messages=[];
    public function __construct(public string $key) {}
    public function send(SmsMessage $message): array { $this->messages[]=$message; return ['success'=>true]; }
}
$a=new RoutedProvider('a');
$b=new RoutedProvider('b');
$providers=new SmsProviderRegistry('a',true);
$providers->register('a','A',$a,['free','pattern']);
$providers->register('b','B',$b,['free']);
$action=new SmsAction($providers,new TokenResolver());
$ctx=new ActionContext(1,'demo',['mobile'=>'09121234567'],[],'submission.submitted','Demo',['mobile']);
$base=['recipient_source'=>'field','recipient_field'=>'mobile','mode'=>'free','body'=>'سلام'];
$action->handle($ctx,$base+['provider'=>'default']);
$action->handle($ctx,$base+['provider'=>'b']);

$unsupported=false;
try { $action->handle($ctx,['recipient_source'=>'field','recipient_field'=>'mobile','provider'=>'b','mode'=>'pattern','pattern_code'=>'1','pattern_values'=>['x']]); }
catch(RuntimeException){ $unsupported=true; }

$checks=[
    'global-default-routed'=>count($a->messages)===1,
    'per-action-override-routed'=>count($b->messages)===1,
    'unsupported-pattern-blocked'=>$unsupported,
];
$failed=false; foreach($checks as $name=>$ok){ echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL; if(!$ok)$failed=true; }
exit($failed?1:0);

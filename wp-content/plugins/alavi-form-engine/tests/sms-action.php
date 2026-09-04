<?php
declare(strict_types=1);

function sanitize_key(string $key): string { return strtolower((string)preg_replace('/[^a-z0-9_\-\.]/i','',$key)); }
function wp_json_encode(mixed $value, int $flags=0): string|false { return json_encode($value,$flags); }
function get_userdata(int $id): object|false { return $id===9 ? (object)['ID'=>9] : false; }
function get_user_meta(int $id,string $key,bool $single=true): mixed { return $id===9 && $key==='mobile' ? '+98 912 123 4567' : ''; }

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
use BonyadAlavi\FormEngine\Actions\Tokens\TokenResolver;

final class FakeSmsProvider implements SmsProviderInterface {
    public array $messages=[];
    public function send(SmsMessage $message): array { $this->messages[]=$message; return ['success'=>true,'message_id'=>'123']; }
}

$provider=new FakeSmsProvider();
$action=new SmsAction($provider,new TokenResolver());
$context=new ActionContext(
    10,
    'demo',
    ['leader_mobile'=>'۰۹۱۲۱۲۳۴۵۶۷','name'=>'محسن'],
    ['tracking_code'=>'TRK-1'],
    'submission.submitted',
    'فرم تست',
    ['leader_mobile','name']
);

$action->handle($context,[
    'recipient_source'=>'field',
    'recipient_field'=>'leader_mobile',
    'mode'=>'free',
    'body'=>'سلام {{field:name}} - {{tracking_code}}',
]);
$action->handle($context,[
    'recipient_source'=>'manual',
    'recipient_value'=>'+98 912 222 3344',
    'mode'=>'pattern',
    'pattern_code'=>'254',
    'pattern_values'=>['{{field:name}}','{{tracking_code}}'],
]);
$action->handle($context,[
    'recipient_source'=>'user',
    'recipient_user_id'=>'9',
    'recipient_user_meta'=>'mobile',
    'mode'=>'free',
    'body'=>'User test',
]);

$checks=[
    'field-normalized'=>($provider->messages[0]->recipient??'')==='09121234567',
    'body-tokens'=>($provider->messages[0]->body??'')==='سلام محسن - TRK-1',
    'manual-normalized'=>($provider->messages[1]->recipient??'')==='09122223344',
    'pattern-code'=>($provider->messages[1]->patternCode??'')==='254',
    'pattern-args'=>($provider->messages[1]->patternValues??[])===['محسن','TRK-1'],
    'user-meta-recipient'=>($provider->messages[2]->recipient??'')==='09121234567',
];

$failed=false;
foreach($checks as $name=>$ok){ echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL; if(!$ok)$failed=true; }
exit($failed?1:0);

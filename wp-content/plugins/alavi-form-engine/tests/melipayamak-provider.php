<?php
declare(strict_types=1);

function sanitize_key(string $key): string { return strtolower((string)preg_replace('/[^a-z0-9_\-\.]/i','',$key)); }
function apply_filters(string $hook,mixed $value,mixed ...$args): mixed { return $value; }
function wp_parse_url(string $url): array|false { return parse_url($url); }
function wp_json_encode(mixed $value,int $flags=0): string|false { return json_encode($value,$flags); }
function is_wp_error(mixed $value): bool { return false; }
function wp_remote_retrieve_response_code(array $response): int { return (int)($response['response']['code']??0); }
function wp_remote_retrieve_body(array $response): string { return (string)($response['body']??''); }
function wp_remote_post(string $url,array $args=[]): array {
    $GLOBALS['http_calls'][]=['url'=>$url,'args'=>$args];
    $next=array_shift($GLOBALS['http_responses']);
    return is_array($next)?$next:['response'=>['code'=>500],'body'=>''];
}

spl_autoload_register(static function(string $class): void {
    $prefix='BonyadAlavi\\FormEngine\\';
    if (!str_starts_with($class,$prefix)) return;
    $file=dirname(__DIR__).'/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';
    if (is_readable($file)) require_once $file;
});

use BonyadAlavi\FormEngine\Actions\Sms\MeliPayamakProvider;
use BonyadAlavi\FormEngine\Actions\Sms\SmsMessage;

$GLOBALS['http_calls']=[];
$GLOBALS['http_responses']=[
    ['response'=>['code'=>200],'body'=>'{"Value":"1001","RetStatus":1,"StrRetStatus":""}'],
    ['response'=>['code'=>200],'body'=>'{"Value":"1002","RetStatus":1,"StrRetStatus":""}'],
    ['response'=>['code'=>200],'body'=>'{"recId":"2001","status":""}'],
    ['response'=>['code'=>200],'body'=>'{"recId":"2002","status":""}'],
];

$legacy=new MeliPayamakProvider([
    'enabled'=>true,'auth_mode'=>'legacy','username'=>'user','password'=>'pass','sender'=>'50001234',
]);
$r1=$legacy->send(new SmsMessage('09121234567','free','hello'));
$r2=$legacy->send(new SmsMessage('09121234567','pattern','','254',['Mohsen','1911']));

$console=new MeliPayamakProvider([
    'enabled'=>true,'auth_mode'=>'api_key','api_key'=>'abc-123_token','sender'=>'50001234',
]);
$r3=$console->send(new SmsMessage('09121234567','free','hello'));
$r4=$console->send(new SmsMessage('09121234567','pattern','','254',['Mohsen','1911']));

$calls=$GLOBALS['http_calls'];
$consoleFree=json_decode((string)($calls[2]['args']['body']??''),true);
$consolePattern=json_decode((string)($calls[3]['args']['body']??''),true);

$checks=[
    'legacy-free-success'=>!empty($r1['success']) && ($r1['message_id']??'')==='1001',
    'legacy-free-endpoint'=>($calls[0]['url']??'')==='https://rest.payamak-panel.com/api/SendSMS/SendSMS',
    'legacy-free-form-body'=>(($calls[0]['args']['body']['username']??'')==='user' && ($calls[0]['args']['body']['isFlash']??null)===false),
    'legacy-pattern-endpoint'=>($calls[1]['url']??'')==='https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber',
    'legacy-pattern-text'=>($calls[1]['args']['body']['text']??'')==='Mohsen;1911',
    'legacy-pattern-body-id'=>($calls[1]['args']['body']['bodyId']??0)===254,
    'console-free-success'=>!empty($r3['success']) && ($r3['message_id']??'')==='2001',
    'console-free-endpoint'=>($calls[2]['url']??'')==='https://console.melipayamak.com/api/send/simple/abc-123_token',
    'console-free-json'=>($consoleFree??[])===['from'=>'50001234','to'=>'09121234567','text'=>'hello'],
    'console-pattern-success'=>!empty($r4['success']) && ($r4['message_id']??'')==='2002',
    'console-pattern-endpoint'=>($calls[3]['url']??'')==='https://console.melipayamak.com/api/send/shared/abc-123_token',
    'console-pattern-json'=>($consolePattern??[])===['to'=>'09121234567','bodyId'=>254,'args'=>['Mohsen','1911']],
];

$failed=false;
foreach($checks as $name=>$ok){ echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL; if(!$ok)$failed=true; }
exit($failed?1:0);

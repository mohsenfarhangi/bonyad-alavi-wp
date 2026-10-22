<?php
declare(strict_types=1);

$GLOBALS['afe_debug_settings']=['sms'=>['debug_enabled'=>true]];
$GLOBALS['afe_debug_options']=[];
function sanitize_key($value): string { return preg_replace('/[^a-z0-9_-]/','',strtolower((string)$value)); }
function get_option($key,$fallback=[]) {
    if($key==='afe_settings') return $GLOBALS['afe_debug_settings'];
    return $GLOBALS['afe_debug_options'][$key]??$fallback;
}
function update_option($key,$value,$autoload=true): bool {
    if($autoload!==false) throw new RuntimeException('Diagnostics must not be autoloaded');
    $GLOBALS['afe_debug_options'][$key]=$value;
    return true;
}
function delete_option($key): bool { unset($GLOBALS['afe_debug_options'][$key]);return true; }
spl_autoload_register(static function($name): void {
    $prefix='BonyadAlavi\\FormEngine\\';
    if(!str_starts_with($name,$prefix)) return;
    $path=dirname(__DIR__).'/src/'.str_replace('\\','/',substr($name,strlen($prefix))).'.php';
    if(is_file($path)) require_once $path;
});
use BonyadAlavi\FormEngine\Actions\Sms\SmsDebugLog;

$maliciousResult=[
    'success'=>false, 'error'=>'API_KEY_NEVER_LOG',
    'raw'=>'{"password":"API_KEY_NEVER_LOG","to":"09129876543","text":"PRIVATE_OTP"}',
    'phone'=>'09129876543',
    'debug'=>[
        'http_status'=>400, 'ret_status'=>35, 'value_code'=>-111,
        'stage'=>'transport', 'payload'=>'API_KEY_NEVER_LOG',
        'message_id'=>'INVALID_PRIVATE_OTP'
    ],
];
$failure=SmsDebugLog::record('test','farapayamak',$maliciousResult);
$success=SmsDebugLog::record('action','farapayamak',[
    'success'=>true,'message_id'=>'1234567890123456',
    'debug'=>['http_status'=>200,'ret_status'=>1,'value_code'=>'1234567890123456']
]);
$otp=SmsDebugLog::record('otp','melipayamak',['success'=>false],'exception');
for($i=0;$i<48;$i++) SmsDebugLog::record('test','farapayamak',['success'=>true]);
$items=SmsDebugLog::all();
$encoded=json_encode($items,JSON_UNESCAPED_UNICODE);
$GLOBALS['afe_debug_settings']['sms']['debug_enabled']=false;
$before=count(SmsDebugLog::all());
SmsDebugLog::record('action','farapayamak',['success'=>false]);
$after=count(SmsDebugLog::all());
SmsDebugLog::clear();

$checks=[
    'safe-numeric-http-status'=>($failure['http_status']??null)===400,
    'safe-numeric-provider-codes'=>($failure['ret_status']??null)===35 && ($failure['value_code']??null)===-111,
    'safe-internal-stage'=>($failure['stage']??null)==='transport',
    'accepted-message-id-only'=>($success['message_id']??'')==='1234567890123456',
    'untrusted-code-is-not-message-id'=>!isset($failure['message_id']) && !isset($success['value_code']),
    'fail-does-not-store-error-strings'=>!str_contains($encoded,'API_KEY_NEVER_LOG')
        && !str_contains($encoded,'PRIVATE_OTP')
        && !str_contains($encoded,'09129876543'),
    'bounded-to-forty'=>count($items)===40,
    'toggle-stops-recording'=>$before===$after,
    'clear-deletes-option'=>SmsDebugLog::all()===[],
    'known-channel'=>$otp['channel']==='otp',
];
foreach($checks as $name=>$ok) {
    echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL;
    if(!$ok) exit(1);
}

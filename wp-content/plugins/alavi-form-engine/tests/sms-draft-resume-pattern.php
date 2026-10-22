<?php
declare(strict_types=1);

define('ARRAY_A','ARRAY_A');
define('MINUTE_IN_SECONDS',60);
define('HOUR_IN_SECONDS',3600);
$GLOBALS['transients']=[];
function sanitize_key($s): string { return preg_replace('/[^a-z0-9_\-]/','',strtolower((string)$s)); }
function sanitize_key_path($s): string { return preg_replace('/[^a-z0-9_.\-]/','',strtolower((string)$s)); }
function sanitize_text_field($s): string { return trim((string)$s); }
function sanitize_textarea_field($s): string { return trim((string)$s); }
function wp_json_encode($value,$flags=0): string {return json_encode($value,$flags);}
function wp_salt($scheme='auth'): string { return 'private-salt-for-testing'; }
function get_transient($key) { return $GLOBALS['transients'][$key]??false; }
function set_transient($key,$value,$ttl) { $GLOBALS['transients'][$key]=$value; return true; }
function delete_transient($key) { unset($GLOBALS['transients'][$key]); return true; }
function wp_get_referer(): string { return 'https://example.test/form'; }
function home_url($path): string { return 'https://example.test/'; }
function remove_query_arg($names,$url): string { return $url; }
function add_query_arg($key,$value,$url): string { return $url.'?'.$key.'='.$value; }

spl_autoload_register(static function($name) {
    $prefix='BonyadAlavi\\FormEngine\\';
    if (!str_starts_with($name,$prefix)) return;
    $file=dirname(__DIR__).'/src/'.str_replace('\\','/',substr($name,strlen($prefix))).'.php';
    if(is_file($file)) require_once $file;
});

use BonyadAlavi\FormEngine\Actions\Sms\SmsMessage;
use BonyadAlavi\FormEngine\Actions\Sms\SmsProviderInterface;
use BonyadAlavi\FormEngine\Actions\Sms\SmsProviderRegistry;
use BonyadAlavi\FormEngine\Duplicate\DuplicatePolicy;
use BonyadAlavi\FormEngine\Duplicate\DuplicateFingerprint;
use BonyadAlavi\FormEngine\Duplicate\DuplicateRepository;
use BonyadAlavi\FormEngine\Repository\SubmissionRepository;
use BonyadAlavi\FormEngine\Security\SmsDraftRecovery;

final class RecoveryDb {
    public string $prefix='wp_';
    public function prepare(string $query,...$values): string { return $query; }
    public function get_var(string $query) { return $GLOBALS['test_duplicate_id'] ?? 0; }
    public function get_row(string $query,$mode) { return $GLOBALS['test_record'] ?? null; }
}
final class RecoverySmsProvider implements SmsProviderInterface {
    public array $sent=[];
    public function send(SmsMessage $message): array {
        $this->sent[]=$message;
        return ['success'=>true,'message_id'=>'1234567890123456'];
    }
}

$GLOBALS['wpdb']=new RecoveryDb();
$GLOBALS['test_duplicate_id']=31;
$GLOBALS['test_record']=[
    'id'=>31,'form_slug'=>'form-a','status'=>'draft','is_locked'=>0,
    'tracking_code'=>'SECRET-TRACKING', 'data_json'=>'{"group_mobile":"09120000001"}'
];
$fake=new RecoverySmsProvider();
$sms=new SmsProviderRegistry('provider',true);
$sms->register('provider','Provider',$fake,['free','pattern']);
$repo=new SubmissionRepository();
$policy=new DuplicatePolicy(new DuplicateFingerprint(),new DuplicateRepository());
$otp=new SmsDraftRecovery($repo,$policy,$sms);
$form=['slug'=>'form-a','settings'=>['save_draft'=>true,'editing_enabled'=>true,
    'duplicate'=>['enabled'=>true,'fields'=>['registration_code','group_mobile'],
        'otp_enabled'=>true,'otp_mobile_field'=>'group_mobile','otp_provider'=>'default']
],'steps'=>[['items'=>[['name'=>'registration_code','label'=>'ثبت','type'=>'text'],
    ['name'=>'group_mobile','label'=>'موبایل','type'=>'tel']]]]];
$data=['registration_code'=>'GROUP01','group_mobile'=>'09120000001'];

/* Prove real recovery flow selects pattern mode and delivers only to stored phone. */
$GLOBALS['transients']=[];
$GLOBALS['test_record']['status']='draft';
$sms->register('provider','Provider',$fake,['free','pattern']);
$patternForm=$form;
$patternForm['settings']['duplicate']['otp_sms_mode']='pattern';
$patternForm['settings']['duplicate']['otp_sms_pattern_code']='7252';
$patternForm['settings']['duplicate']['otp_sms_pattern_values']=['{{code}}','بنیاد علوی'];
$patternBefore=count($fake->sent);
$patternStart=$otp->start($patternForm,$data,'09120000001');
$patternMessage=$fake->sent[$patternBefore]??null;
$patternCode=$patternMessage?->patternValues[0]??'';
$patternGood=$patternStart['request_id']??'';
$patternVerified=$patternGood!==''?$otp->verify($patternForm,$patternGood,$patternCode):[];
$patternChecks=[
    'pattern-OTP-to-persisted-phone'=>$patternMessage?->recipient==='09120000001',
    'pattern-uses-approved-body-id'=>$patternMessage?->mode==='pattern' && $patternMessage?->patternCode==='7252',
    'pattern-ordered-code-substitution'=>$patternMessage?->patternValues===[$patternCode,'بنیاد علوی']
        && (bool)preg_match('/^[0-9]{6}$/D',(string)$patternCode),
    'pattern-verified-same-draft'=>!empty($patternVerified['success']) && str_contains((string)($patternVerified['edit_url']??''),'afe_recovery='),
    'start-does-not-leak-pattern-values'=>!str_contains(json_encode($patternStart),$patternCode),
];
foreach($patternChecks as $name=>$ok){echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL; if(!$ok)exit(1);}


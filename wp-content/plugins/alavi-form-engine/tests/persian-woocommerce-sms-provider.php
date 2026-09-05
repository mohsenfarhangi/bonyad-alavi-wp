<?php
declare(strict_types=1);

function sanitize_key(string $key): string { return strtolower((string)preg_replace('/[^a-z0-9_\-\.]/i','',$key)); }
function wp_json_encode(mixed $value,int $flags=0): string|false { return json_encode($value,$flags); }
function apply_filters(string $hook,mixed $value,mixed ...$args): mixed { return $value; }

define('PWSMS_VERSION','7.2.2-test');

final class StubMeliPatternGateway {
    public static function id(): string { return 'melipayamakpattern'; }
    public static function name(): string { return 'melipayamak.com خدماتی'; }
}
final class StubKaveLookupGateway {
    public static function id(): string { return 'kavenegar_lookUp'; }
    public static function name(): string { return 'kavenegar.com(lookup)'; }
}
final class StubFreeGateway {
    public static function id(): string { return 'simple'; }
    public static function name(): string { return 'Simple Gateway'; }
}
final class StubPwsmsHelper {
    public object $gateway;
    public array $calls=[];
    public mixed $result=true;
    public function __construct(){ $this->gateway=new StubMeliPatternGateway(); }
    public function get_sms_gateway(): object { return $this->gateway; }
    public function send_sms(array $data): mixed { $this->calls[]=$data; return $this->result; }
}
$GLOBALS['stub_pwsms']=new StubPwsmsHelper();
function PWSMS(): object { return $GLOBALS['stub_pwsms']; }

spl_autoload_register(static function(string $class): void {
    $prefix='BonyadAlavi\\FormEngine\\';
    if (!str_starts_with($class,$prefix)) return;
    $file=dirname(__DIR__).'/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';
    if (is_readable($file)) require_once $file;
});

use BonyadAlavi\FormEngine\Actions\Sms\PersianWooCommerceSmsProvider;
use BonyadAlavi\FormEngine\Actions\Sms\SmsMessage;

$provider=new PersianWooCommerceSmsProvider();
$helper=$GLOBALS['stub_pwsms'];

$free=$provider->send(new SmsMessage('09121234567','free','سلام'));
$freeCall=$helper->calls[0]??[];
$helper->gateway=new StubMeliPatternGateway();
$meli=$provider->send(new SmsMessage('09121234567','pattern','','254',['محسن','TRK-1']));
$meliCall=$helper->calls[1]??[];
$helper->gateway=new StubKaveLookupGateway();
$kave=$provider->send(new SmsMessage('09121234567','pattern','','verify',['محسن','TRK-1']));
$kaveCall=$helper->calls[2]??[];
$helper->gateway=new StubFreeGateway();
$freeOnlyModes=$provider->supportedModes();
$unsupported=$provider->send(new SmsMessage('09121234567','pattern','','100',['X']));

$checks=[
    'plugin-available'=>$provider->isAvailable(),
    'free-success'=>!empty($free['success']) && ($freeCall['message']??'')==='سلام',
    'free-rich-metadata'=>($freeCall['afe_mode']??'')==='free' && ($freeCall['afe_source']??'')==='alavi_form_engine',
    'meli-pattern-success'=>!empty($meli['success']),
    'meli-pattern-format'=>($meliCall['message']??'')==='254@محسن##TRK-1##shared',
    'kave-pattern-success'=>!empty($kave['success']),
    'kave-pattern-format'=>($kaveCall['message']??'')==='template=verifytoken=محسنtoken2=TRK-1',
    'unknown-gateway-free-only'=>$freeOnlyModes===['free'],
    'unknown-pattern-rejected'=>empty($unsupported['success']) && str_contains((string)($unsupported['error']??''),'پشتیبانی'),
    'no-credentials-copied'=>!isset($freeCall['username'],$freeCall['password'],$freeCall['api_key']),
];
$failed=false;
foreach($checks as $name=>$ok){ echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL; if(!$ok)$failed=true; }
exit($failed?1:0);

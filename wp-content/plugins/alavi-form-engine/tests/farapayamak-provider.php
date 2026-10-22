<?php
declare(strict_types=1);

function wp_json_encode(mixed $value, int $flags = 0): string|false { return json_encode($value, $flags); }
function wp_remote_post(string $url, array $args): array|WP_Error {
    $GLOBALS['farapayamak_calls'][] = ['url'=>$url, 'args'=>$args];
    return array_shift($GLOBALS['farapayamak_responses']) ?? ['response'=>['code'=>500], 'body'=>''];
}
function wp_remote_retrieve_response_code(array $response): int { return (int)($response['response']['code'] ?? 0); }
function wp_remote_retrieve_body(array $response): string { return (string)($response['body'] ?? ''); }
function is_wp_error(mixed $response): bool { return $response instanceof WP_Error; }

final class WP_Error {
    public function __construct(private string $message) {}
    public function get_error_message(): string { return $this->message; }
}

spl_autoload_register(static function (string $class): void {
    $prefix = 'BonyadAlavi\\FormEngine\\';
    if (!str_starts_with($class, $prefix)) return;
    $file = dirname(__DIR__).'/src/'.str_replace('\\', '/', substr($class, strlen($prefix))).'.php';
    if (is_readable($file)) require_once $file;
});

use BonyadAlavi\FormEngine\Actions\Sms\FarapayamakProvider;
use BonyadAlavi\FormEngine\Actions\Sms\SmsMessage;

$GLOBALS['farapayamak_calls'] = [];
$GLOBALS['farapayamak_responses'] = [
    ['response'=>['code'=>200], 'body'=>'{"Value":"1234567890123456","RetStatus":1,"StrRetStatus":"Ok"}'],
    ['response'=>['code'=>200], 'body'=>'{"Value":"1234567890123457","RetStatus":1,"StrRetStatus":"Ok"}'],
    ['response'=>['code'=>200], 'body'=>'{"Value":"-1","RetStatus":1,"StrRetStatus":"InvalidData API_SECRET"}'],
    ['response'=>['code'=>200], 'body'=>'{"Value":"2","RetStatus":1,"StrRetStatus":"Ok"}'],
    ['response'=>['code'=>200], 'body'=>'{"Value":"9999999999999999","RetStatus":35,"StrRetStatus":"InvalidData"}'],
    ['response'=>['code'=>200], 'body'=>'{"Value":"1","RetStatus":1,"StrRetStatus":"Ok"}'],
    ['response'=>['code'=>200], 'body'=>'{"Value":false,"RetStatus":1,"StrRetStatus":"Ok"}'],
    ['response'=>['code'=>200], 'body'=>'not json'],
    ['response'=>['code'=>503], 'body'=>'API_SECRET in server error'],
    new WP_Error('API_SECRET in error detail'),
];

$provider = new FarapayamakProvider([
    'username'=>'test-account',
    'api_key'=>'API_SECRET',
    'sender'=>'50001234',
]);
$free = $provider->send(new SmsMessage('09121234567', 'free', 'سلام؛ پیام نمونه'));
$pattern = $provider->send(new SmsMessage('09121234567', 'pattern', '', '254', ['نام', 'کد۱۲۳']));
$valueError = $provider->send(new SmsMessage('09121234567', 'free', 'send'));
$creditError = $provider->send(new SmsMessage('09121234567', 'free', 'send'));
$statusError = $provider->send(new SmsMessage('09121234567', 'free', 'send'));
$genericSuccess = $provider->send(new SmsMessage('09121234567', 'free', 'send'));
$invalidValue = $provider->send(new SmsMessage('09121234567', 'free', 'send'));
$malformed = $provider->send(new SmsMessage('09121234567', 'free', 'send'));
$httpError = $provider->send(new SmsMessage('09121234567', 'free', 'send'));
$transportError = $provider->send(new SmsMessage('09121234567', 'free', 'send'));

$beforeInvalid = count($GLOBALS['farapayamak_calls']);
$missingCredentials = (new FarapayamakProvider(['username'=>'test-account']))->send(new SmsMessage('09121234567', 'free', 'send'));
$badMobile = $provider->send(new SmsMessage('123', 'free', 'send'));
$badSender = (new FarapayamakProvider(['username'=>'test-account','api_key'=>'API_SECRET']))->send(new SmsMessage('09121234567', 'free', 'send'));
$blankText = $provider->send(new SmsMessage('09121234567', 'free', ''));
$badId = $provider->send(new SmsMessage('09121234567', 'pattern', '', 'abc', ['value']));
$missingPatternValues = $provider->send(new SmsMessage('09121234567', 'pattern', '', '234', []));
$unknownMode = $provider->send(new SmsMessage('09121234567', 'schedule', 'send'));

$calls = $GLOBALS['farapayamak_calls'];
$freePayload = json_decode($calls[0]['args']['body'] ?? '', true);
$patternPayload = json_decode($calls[1]['args']['body'] ?? '', true);
$results = [$free, $pattern, $valueError, $creditError, $statusError, $genericSuccess, $invalidValue, $malformed, $httpError, $transportError];
$jsonErrors = json_encode($results, JSON_UNESCAPED_UNICODE);

$checks = [
    'provider-key'=>FarapayamakProvider::KEY==='farapayamak',
    'free-returns-message-id'=>($free['success'] ?? false)===true && ($free['message_id'] ?? '')==='1234567890123456',
    'free-fixed-endpoint'=>($calls[0]['url'] ?? '')==='https://rest.payamak-panel.com/api/SendSMS/SendSMS',
    'free-post-json-header'=>($calls[0]['args']['headers']['Content-Type'] ?? '')==='application/json; charset=utf-8'
        && ($calls[0]['args']['redirection'] ?? 1)===0 && ($calls[0]['args']['sslverify'] ?? false)===true,
    'free-case-sensitive-json'=>$freePayload === [
        'username'=>'test-account', 'password'=>'API_SECRET', 'to'=>'09121234567',
        'from'=>'50001234', 'text'=>'سلام؛ پیام نمونه', 'isflash'=>false,
    ],
    'free-json-unicode'=>str_contains((string)$calls[0]['args']['body'], 'سلام'),
    'pattern-returns-message-id'=>($pattern['success'] ?? false)===true && ($pattern['message_id'] ?? '')==='1234567890123457',
    'pattern-fixed-endpoint'=>($calls[1]['url'] ?? '')==='https://rest.payamak-panel.com/api/SendSMS/BaseServiceNumber',
    'pattern-case-sensitive-json'=>$patternPayload === [
        'username'=>'test-account', 'password'=>'API_SECRET',
        'text'=>'نام;کد۱۲۳', 'to'=>'09121234567', 'bodyId'=>254,
    ],
    'retstatus-one-with-value-negative-is-error'=>empty($valueError['success'])
        && str_contains((string)($valueError['error'] ?? ''), 'فراپیامک'),
    'retstatus-one-with-business-error-is-error'=>empty($creditError['success'])
        && str_contains((string)($creditError['error'] ?? ''), 'اعتبار'),
    'retstatus-other-than-one-is-error'=>empty($statusError['success']),
    'value-one-success-without-fake-message-id'=>!empty($genericSuccess['success']) && !isset($genericSuccess['message_id']),
    'invalid-value-is-error'=>empty($invalidValue['success']),
    'invalid-json-is-error'=>empty($malformed['success']),
    'http-503-is-error'=>empty($httpError['success']),
    'transport-error-is-redacted'=>empty($transportError['success'])
        && !str_contains((string)($transportError['error'] ?? ''), 'API_SECRET'),
    'untrusted-response-and-credentials-are-not-returned'=>!str_contains((string)$jsonErrors, 'API_SECRET')
        && !str_contains((string)$jsonErrors, 'InvalidData') && !str_contains((string)$jsonErrors, 'test-account'),
    'missing-credential-short-circuits'=>empty($missingCredentials['success']),
    'bad-recipient-short-circuits'=>empty($badMobile['success']),
    'sender-required-only-on-free'=>empty($badSender['success']),
    'empty-body-short-circuits'=>empty($blankText['success']),
    'pattern-body-id-validates'=>empty($badId['success']),
    'pattern-values-required'=>empty($missingPatternValues['success']),
    'rejects-unknown-mode'=>empty($unknownMode['success']),
    'no-network-on-local-validation-failure'=>count($GLOBALS['farapayamak_calls'])===$beforeInvalid,
];

foreach ($checks as $name=>$ok) {
    echo ($ok ? 'PASS ' : 'FAIL ') . $name . PHP_EOL;
    if (!$ok) exit(1);
}

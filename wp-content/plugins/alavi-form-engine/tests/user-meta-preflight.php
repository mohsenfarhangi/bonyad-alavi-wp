<?php
declare(strict_types=1);

$GLOBALS['insert_user_calls']=0;
$GLOBALS['update_meta_calls']=0;

function sanitize_key(string $key): string { return strtolower((string)preg_replace('/[^a-z0-9_\-\.]/i','',$key)); }
function sanitize_user(string $value,bool $strict=false): string { return preg_replace('/[^a-z0-9_.-]/i','',$value) ?: ''; }
function sanitize_email(string $value): string { return filter_var($value,FILTER_VALIDATE_EMAIL)?$value:''; }
function username_exists(string $login): int|false { return false; }
function email_exists(string $email): int|false { return false; }
function wp_generate_password(int $length=24,bool $special=true,bool $extra=true): string { return str_repeat('x',$length); }
function wp_insert_user(array $data): int { $GLOBALS['insert_user_calls']++; return 99; }
function wp_update_user(array $data): int { return (int)($data['ID']??0); }
function update_user_meta(int $id,string $key,mixed $value): bool { $GLOBALS['update_meta_calls']++; return true; }
function is_wp_error(mixed $value): bool { return false; }
function get_userdata(int $id): object|false { return $id===2 ? (object)['ID'=>2,'roles'=>['subscriber']] : false; }
function user_can(int $id,string $cap): bool { return false; }
function wp_json_encode(mixed $value,int $flags=0): string|false { return json_encode($value,$flags); }

spl_autoload_register(static function(string $class): void {
    $prefix='BonyadAlavi\\FormEngine\\';
    if(!str_starts_with($class,$prefix)) return;
    $file=dirname(__DIR__).'/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';
    if(is_readable($file)) require_once $file;
});

use BonyadAlavi\FormEngine\Actions\ActionContext;
use BonyadAlavi\FormEngine\Actions\ActionRuntime;
use BonyadAlavi\FormEngine\Actions\Tokens\TokenResolver;
use BonyadAlavi\FormEngine\Actions\User\CreateUserAction;
use BonyadAlavi\FormEngine\Actions\User\UpdateUserMetaAction;
use BonyadAlavi\FormEngine\Actions\User\UserActionGuard;
use BonyadAlavi\FormEngine\Actions\User\UserTargetResolver;

$tokens=new TokenResolver();
$guard=new UserActionGuard();
$context=new ActionContext(1,'demo',[],['user_id'=>2],'submission.submitted','Demo',[],[],new ActionRuntime());

$createFailed=false;
try {
    (new CreateUserAction($tokens,$guard))->handle($context,[
        'user_login'=>'new-user',
        'user_meta'=>['billing_phone'=>'09120000000','wp_capabilities'=>'danger'],
    ]);
} catch (RuntimeException) { $createFailed=true; }

$metaFailed=false;
try {
    (new UpdateUserMetaAction(new UserTargetResolver($tokens),$tokens,$guard))->handle($context,[
        'user_source'=>'submission',
        'meta'=>['billing_phone'=>'09120000000','session_tokens'=>'danger'],
    ]);
} catch (RuntimeException) { $metaFailed=true; }

$checks=[
    'create-user-rejects-protected-meta'=>$createFailed,
    'create-user-no-partial-create'=>(int)$GLOBALS['insert_user_calls']===0,
    'create-user-no-meta-write'=>(int)$GLOBALS['update_meta_calls']===0,
    'update-meta-rejects-protected-key'=>$metaFailed,
    'update-meta-no-partial-write'=>(int)$GLOBALS['update_meta_calls']===0,
];
$failed=false;
foreach($checks as $name=>$ok){echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL;if(!$ok)$failed=true;}
exit($failed?1:0);

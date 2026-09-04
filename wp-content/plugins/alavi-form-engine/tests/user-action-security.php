<?php
declare(strict_types=1);

function sanitize_key(string $key): string { return strtolower((string)preg_replace('/[^a-z0-9_\-\.]/i','',$key)); }
class FakeUser {
    public function __construct(public int $ID, public array $roles) {}
    public function set_role(string $role): void { $this->roles=[$role]; $GLOBALS['afe_test_role']=$role; }
}
function get_userdata(int $id): object|false {
    return match ($id) {
        1 => new FakeUser(1,['administrator']),
        2 => new FakeUser(2,['editor']),
        3 => new FakeUser(3,['custom_manager']),
        default => false,
    };
}
function user_can(int $id,string $cap): bool { return $id===3 && $cap==='manage_options'; }
function wp_json_encode(mixed $value,int $flags=0): string|false { return json_encode($value,$flags); }
function get_current_user_id(): int { return 2; }
function wp_roles(): object { return (object)['roles'=>[
    'subscriber'=>['name'=>'Subscriber','capabilities'=>['read'=>true]],
    'editor'=>['name'=>'Editor','capabilities'=>['edit_posts'=>true]],
    'custom_manager'=>['name'=>'Custom Manager','capabilities'=>['manage_options'=>true]],
    'administrator'=>['name'=>'Administrator','capabilities'=>['manage_options'=>true]],
]]; }

spl_autoload_register(static function(string $class): void {
    $prefix='BonyadAlavi\\FormEngine\\';
    if(!str_starts_with($class,$prefix)) return;
    $file=dirname(__DIR__).'/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';
    if(is_readable($file)) require_once $file;
});

use BonyadAlavi\FormEngine\Actions\ActionRegistry;
use BonyadAlavi\FormEngine\Actions\Pdf\PdfGenerator;
use BonyadAlavi\FormEngine\Actions\Tokens\TokenResolver;
use BonyadAlavi\FormEngine\Actions\User\UserActionGuard;
use BonyadAlavi\FormEngine\Actions\User\UserTargetResolver;
use BonyadAlavi\FormEngine\Actions\User\AssignRoleAction;
use BonyadAlavi\FormEngine\Actions\ActionContext;
use BonyadAlavi\FormEngine\Actions\ActionRuntime;
use BonyadAlavi\FormEngine\Repository\SubmissionRepository;

$guard=new UserActionGuard();
$throws=static function(callable $fn): bool { try{$fn();return false;}catch(RuntimeException){return true;} };

$registry=new ActionRegistry();
$tokens=new TokenResolver();
$registry->registerExtended($tokens,new SubmissionRepository(),new PdfGenerator());
$assignRole=new AssignRoleAction(new UserTargetResolver($tokens),$guard);
$ctx=new ActionContext(10,'demo',[],['user_id'=>2],'submission.submitted','Demo',[],[],new ActionRuntime());
$customRoleBlocked=$throws(fn()=> $assignRole->handle($ctx,['user_source'=>'current','role'=>'custom_manager']));
$customRoleAllowed=(function() use($assignRole,$ctx): bool { try{$assignRole->handle($ctx,['user_source'=>'current','role'=>'custom_manager','allow_privileged_role'=>true]);return ($GLOBALS['afe_test_role']??'')==='custom_manager';}catch(Throwable){return false;} })();

$checks=[
    'administrator-blocked'=>$throws(fn()=> $guard->assertTargetAllowed(1,[],'test')),
    'capability-user-blocked'=>$throws(fn()=> $guard->assertTargetAllowed(3,[],'test')),
    'privileged-explicitly-allowed'=>(function() use($guard): bool { try{$guard->assertTargetAllowed(1,['allow_privileged_user'=>true],'test');return true;}catch(Throwable){return false;} })(),
    'normal-user-allowed'=>(function() use($guard): bool { try{$guard->assertTargetAllowed(2,[],'test');return true;}catch(Throwable){return false;} })(),
    'wp-capabilities-protected'=>$guard->isProtectedMetaKey('wp_capabilities'),
    'multisite-capabilities-protected'=>$guard->isProtectedMetaKey('wp_2_capabilities'),
    'user-level-protected'=>$guard->isProtectedMetaKey('wp_user_level'),
    'session-protected'=>$guard->isProtectedMetaKey('session_tokens'),
    'application-passwords-protected'=>$guard->isProtectedMetaKey('_application_passwords'),
    'normal-meta-allowed'=>!$guard->isProtectedMetaKey('billing_phone'),
    'custom-manage-options-role-blocked'=>$customRoleBlocked,
    'custom-manage-options-role-explicitly-allowed'=>$customRoleAllowed,
    'user-actions-have-privileged-gate'=>(($registry->get('login_user')?->settingsSchema['allow_privileged_user']['capability']??'')==='afe_manage_settings')
        && (($registry->get('update_user')?->settingsSchema['allow_privileged_user']['capability']??'')==='afe_manage_settings')
        && (($registry->get('assign_role')?->settingsSchema['allow_privileged_user']['capability']??'')==='afe_manage_settings')
        && (($registry->get('update_user_meta')?->settingsSchema['allow_privileged_user']['capability']??'')==='afe_manage_settings'),
    'create-existing-has-privileged-gate'=>(($registry->get('create_user')?->settingsSchema['allow_privileged_existing']['capability']??'')==='afe_manage_settings'),
];

$failed=false;
foreach($checks as $name=>$ok){echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL;if(!$ok)$failed=true;}
exit($failed?1:0);

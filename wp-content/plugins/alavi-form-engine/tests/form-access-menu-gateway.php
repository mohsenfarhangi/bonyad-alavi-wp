<?php
declare(strict_types=1);

$GLOBALS['opts']=[
    'afe_form_access_migrated'=>1,
    'afe_initialized_form_caps'=>[
        'limited-form'=>[
            'view'=>1,'edit'=>1,'manage'=>1,'export'=>1,'reports'=>1,'configure'=>1,
        ],
    ],
];
function sanitize_key(string $key): string { return preg_replace('/[^a-z0-9_\-]/','',strtolower($key)) ?? ''; }
function get_option(string $key,mixed $default=false): mixed { return $GLOBALS['opts'][$key]??$default; }
function update_option(string $key,mixed $value,mixed $autoload=null): bool { $GLOBALS['opts'][$key]=$value; return true; }
final class FakeRole {
    public function __construct(public array $caps=[]){}
    public function add_cap($c){$this->caps[$c]=true;}
    public function remove_cap($c){unset($this->caps[$c]);}
    public function has_cap($c){return !empty($this->caps[$c]);}
}
$GLOBALS['roles']=[
    'administrator'=>new FakeRole(),
    'limited'=>new FakeRole([
        'afe_form_limited_form_view'=>true,
        'afe_form_limited_form_reports'=>true,
    ]),
];
function get_role(string $key): ?FakeRole { return $GLOBALS['roles'][$key]??null; }
function wp_roles(): object { return (object)['roles'=>['administrator'=>['name'=>'Administrator'],'limited'=>['name'=>'Limited']]]; }
function current_user_can(string $cap): bool { return false; }

require_once __DIR__.'/../src/Core/Capabilities.php';
require_once __DIR__.'/../src/Form/Form.php';
require_once __DIR__.'/../src/Form/FormRegistry.php';
require_once __DIR__.'/../src/Core/FormAccess.php';

use BonyadAlavi\FormEngine\Core\Capabilities;
use BonyadAlavi\FormEngine\Core\FormAccess;
use BonyadAlavi\FormEngine\Form\Form;
use BonyadAlavi\FormEngine\Form\FormRegistry;

$registry=new FormRegistry();
$registry->register(Form::make('limited-form')->title('Limited Form'));
$access=new FormAccess();
$access->sync($registry);
Capabilities::syncAccess();

$role=$GLOBALS['roles']['limited'];
if(!$role->has_cap(Capabilities::VIEW_SUBMISSIONS)) exit(1);
if(!$role->has_cap(Capabilities::VIEW_REPORTS)) exit(2);
if(!$role->has_cap(Capabilities::ACCESS_ADMIN)) exit(3);
if($role->has_cap(Capabilities::EDIT_SUBMISSIONS)) exit(4);
if($role->has_cap(Capabilities::MANAGE_FORMS)) exit(5);

// Removing the per-form permissions must remove only gateways AFE auto-added.
$role->remove_cap(FormAccess::capability('limited-form',FormAccess::VIEW));
$role->remove_cap(FormAccess::capability('limited-form',FormAccess::REPORTS));
$access->sync($registry);
Capabilities::syncAccess();
if($role->has_cap(Capabilities::VIEW_SUBMISSIONS)) exit(6);
if($role->has_cap(Capabilities::VIEW_REPORTS)) exit(7);
if($role->has_cap(Capabilities::ACCESS_ADMIN)) exit(8);

echo "PASS form-access-menu-gateway\n";

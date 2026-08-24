<?php
declare(strict_types=1);

$GLOBALS['opts']=[];
function sanitize_key(string $key): string { return preg_replace('/[^a-z0-9_\-]/','',strtolower($key)) ?? ''; }
function get_option(string $key,mixed $default=false): mixed { return $GLOBALS['opts'][$key]??$default; }
function update_option(string $key,mixed $value,mixed $autoload=null): bool { $GLOBALS['opts'][$key]=$value; return true; }
final class FakeRole { public function __construct(public array $caps=[]){} public function add_cap($c){$this->caps[$c]=true;} public function remove_cap($c){unset($this->caps[$c]);} public function has_cap($c){return !empty($this->caps[$c]);} }
$GLOBALS['roles']=[
 'administrator'=>new FakeRole(),
 'reviewer'=>new FakeRole(['afe_view_submissions'=>true,'afe_edit_submissions'=>true]),
];
function get_role(string $key): ?FakeRole { return $GLOBALS['roles'][$key]??null; }
function wp_roles(): object { return (object)['roles'=>['administrator'=>['name'=>'Administrator'],'reviewer'=>['name'=>'Reviewer']]]; }
function current_user_can(string $cap): bool { return false; }

require_once __DIR__.'/../src/Core/Capabilities.php';
require_once __DIR__.'/../src/Form/Form.php';
require_once __DIR__.'/../src/Form/FormRegistry.php';
require_once __DIR__.'/../src/Core/FormAccess.php';

use BonyadAlavi\FormEngine\Core\FormAccess;
use BonyadAlavi\FormEngine\Form\Form;
use BonyadAlavi\FormEngine\Form\FormRegistry;

$registry=new FormRegistry();
$registry->register(Form::make('existing-form')->title('Existing'));
$access=new FormAccess();
$access->sync($registry);
if(!$GLOBALS['roles']['reviewer']->has_cap(FormAccess::capability('existing-form',FormAccess::VIEW))) exit(1);
if(!$GLOBALS['roles']['reviewer']->has_cap(FormAccess::capability('existing-form',FormAccess::EDIT))) exit(2);

// New forms after the first migration must be Administrator-only by default.
$registry->register(Form::make('new-form')->title('New'));
$access->sync($registry);
if($GLOBALS['roles']['reviewer']->has_cap(FormAccess::capability('new-form',FormAccess::VIEW))) exit(3);
if(!$GLOBALS['roles']['administrator']->has_cap(FormAccess::capability('new-form',FormAccess::VIEW))) exit(4);

echo "PASS form-access-sync\n";

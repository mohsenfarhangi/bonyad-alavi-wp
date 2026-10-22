<?php
declare(strict_types=1);

define('ARRAY_A', 'ARRAY_A');

final class WP_Error {
    public function __construct(private string $code, private string $message, private array $data=[]) {}
    public function get_error_data(): array { return $this->data; }
    public function get_error_message(): string { return $this->message; }
}


function sanitize_key($value): string { return preg_replace('/[^a-z0-9_\-]/', '', strtolower((string)$value)); }
function sanitize_text_field($value): string { return trim((string)$value); }
function sanitize_textarea_field($value): string { return trim((string)$value); }
function absint($value): int { return abs((int)$value); }
function wp_unslash($value) { return $value; }
function get_current_user_id(): int { return (int)($GLOBALS['afe_test_user_id'] ?? 0); }
function is_user_logged_in(): bool { return get_current_user_id() > 0; }
function current_user_can($capability): bool { return false; }
function wp_get_referer(): string { return 'https://example.test/registration'; }
function home_url($path): string { return 'https://example.test/'; }
function remove_query_arg($keys,$url): string { return (string)$url; }
function add_query_arg($key,$value,$url): string { return (string)$url.'?'.rawurlencode((string)$key).'='.rawurlencode((string)$value); }

spl_autoload_register(static function(string $class): void {
    $prefix='BonyadAlavi\\FormEngine\\';
    if (!str_starts_with($class,$prefix)) return;
    $file=dirname(__DIR__).'/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';
    if (is_readable($file)) require_once $file;
});

use BonyadAlavi\FormEngine\Duplicate\DuplicateDecision;
use BonyadAlavi\FormEngine\Duplicate\DuplicateFingerprint;
use BonyadAlavi\FormEngine\Duplicate\DuplicatePolicy;
use BonyadAlavi\FormEngine\Duplicate\DuplicateRepository;
use BonyadAlavi\FormEngine\Repository\SubmissionRepository;
use BonyadAlavi\FormEngine\Submission\SubmissionService;

final class DraftResumeWpdb {
    public string $prefix='wp_';
    public function prepare(string $sql, ...$values): string { return $sql; }
    public function get_row(string $sql, $output): ?array { return $GLOBALS['afe_test_row'] ?? null; }
}

$GLOBALS['wpdb']=new DraftResumeWpdb();
$GLOBALS['afe_test_user_id']=0;
$GLOBALS['afe_test_row']=[
    'id'=>14,'status'=>'draft','form_slug'=>'test-form','user_id'=>21,
    'is_locked'=>0,'tracking_code'=>'PRIVATE-CODE','edit_token_hash'=>hash('sha256','PRIVATE-TOKEN'),
    'data_json'=>'{}',
];

$policy=new DuplicatePolicy(new DuplicateFingerprint(),new DuplicateRepository());
$instance=(new ReflectionClass(SubmissionService::class))->newInstanceWithoutConstructor();
foreach(['duplicatePolicy'=>$policy,'submissions'=>new SubmissionRepository()] as $name=>$value) {
    (new ReflectionProperty(SubmissionService::class,$name))->setValue($instance,$value);
}
$method=new ReflectionMethod(SubmissionService::class,'duplicateDraftResumePayload');
$errorMethod=new ReflectionMethod(SubmissionService::class,'duplicateError');
$form=[
    'slug'=>'test-form',
    'settings'=>[
        'save_draft'=>true,'editing_enabled'=>true,
        'editing_modes'=>['wordpress','link','tracking'],
        'duplicate'=>['enabled'=>true,'fields'=>['group_mobile'],'behavior'=>'block','resume_draft'=>true],
    ],
    'steps'=>[['items'=>[['type'=>'tel','name'=>'group_mobile','label'=>'شماره همراه']]]],
];
$decision=new DuplicateDecision(true,'fingerprint',14,'block','این اطلاعات قبلاً ثبت شده است.');
$anonymous=$method->invoke($instance,$form,$decision);

$GLOBALS['afe_test_user_id']=21;
$owner=$method->invoke($instance,$form,$decision);
$GLOBALS['afe_test_user_id']=22;
$anotherUser=$method->invoke($instance,$form,$decision);

$GLOBALS['afe_test_user_id']=0;
$_POST['_afe_tracking']='PRIVATE-CODE';
$trackingOwner=$method->invoke($instance,$form,$decision);
$_POST['_afe_tracking']='WRONG';
$wrongTracking=$method->invoke($instance,$form,$decision);
unset($_POST['_afe_tracking']);

$_POST['_afe_edit_token']='PRIVATE-TOKEN';
$linkOwner=$method->invoke($instance,$form,$decision);
unset($_POST['_afe_edit_token']);

$GLOBALS['afe_test_row']['status']='new';
$GLOBALS['afe_test_user_id']=21;
$final=$method->invoke($instance,$form,$decision);
$GLOBALS['afe_test_row']['status']='draft';
$GLOBALS['afe_test_row']['is_locked']=1;
$locked=$method->invoke($instance,$form,$decision);
$GLOBALS['afe_test_row']['is_locked']=0;

$disabled=$form;
$disabled['settings']['duplicate']['resume_draft']=false;
$off=$method->invoke($instance,$disabled,$decision);
$saveDisabled=$form;
$saveDisabled['settings']['save_draft']=false;
$noDraft=$method->invoke($instance,$saveDisabled,$decision);
$editDisabled=$form;
$editDisabled['settings']['editing_enabled']=false;
$noEdit=$method->invoke($instance,$editDisabled,$decision);
$noModesForm=$form;
$noModesForm['settings']['editing_modes']=[];
$noModes=$method->invoke($instance,$noModesForm,$decision);
$nonduplicate=$method->invoke($instance,$form,new DuplicateDecision(true,'fingerprint',null,'block',''));

$GLOBALS['afe_test_user_id']=0;
$submitError=$errorMethod->invoke($instance,$form,$decision,true);
$GLOBALS['afe_test_user_id']=21;
$ownerError=$errorMethod->invoke($instance,$form,$decision,true);

$codeFile=file_get_contents(__DIR__.'/../src/Admin/FormsPage.php') ?: '';
$js=file_get_contents(__DIR__.'/../assets/js/frontend.js') ?: '';
$renderer=file_get_contents(__DIR__.'/../src/Form/Renderer.php') ?: '';

$checks=[
    'anonymous-does-not-see-owner-or-draft-status'=>$anonymous===['resume_help'=>true],
    'authenticated-owner-can-resume'=>$owner['resume_draft']===true
        && str_contains($owner['edit_url'],'afe_submission=14'),
    'different-user-cannot-resume'=>$anotherUser===['resume_help'=>true],
    'tracking-owner-can-resume'=>($trackingOwner['resume_draft']??false)===true
        && str_contains($trackingOwner['edit_url'],'afe_tracking=PRIVATE-CODE'),
    'wrong-tracking-stays-private'=>$wrongTracking===['resume_help'=>true],
    'token-owner-can-resume'=>($linkOwner['resume_draft']??false)===true
        && str_contains($linkOwner['edit_url'],'afe_edit=PRIVATE-TOKEN'),
    'final-submission-is-not-resumable'=>$final===['resume_help'=>true],
    'locked-draft-is-not-resumable'=>$locked===['resume_help'=>true],
    'opt-out-preserves-default-behavior'=>$off===[],
    'requires-draft-support'=>$noDraft===[],
    'requires-edit-support'=>$noEdit===[],
    'requires-auth-recovery-method'=>$noModes===[],
    'nonduplicate-no-resume-hint'=>$nonduplicate===[],
    'submit-error-recovery-hint-but-no-link'=>($submitError->get_error_data()['resume_help']??false)===true
        && !isset($submitError->get_error_data()['edit_url']),
    'authorized-submit-error-includes-draft-url'=>($ownerError->get_error_data()['resume_draft']??false)===true
        && isset($ownerError->get_error_data()['edit_url']),
    'admin-per-form-toggle'=>str_contains($codeFile,'name="duplicate_resume_draft"')
        && str_contains($codeFile,"'resume_draft'=>!empty(\$_POST['duplicate_resume_draft'])"),
    'frontend-uses-only-authorized-url'=>str_contains($js,'function duplicateResumeUi(form, payload)')
        && str_contains($js,'payload?.resume_draft')
        && str_contains($js,'afe-draft-resume-jump'),
    'renderer-still-has-private-tracking-resume'=>str_contains($renderer,'afe-resume-button')
        && str_contains($renderer,'afe_tracking'),
];

foreach($checks as $name=>$passed){
    echo ($passed?'PASS ':'FAIL ').$name.PHP_EOL;
    if (!$passed) exit(1);
}

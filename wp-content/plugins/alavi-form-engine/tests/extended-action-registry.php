<?php
declare(strict_types=1);
function wp_json_encode(mixed $value,int $flags=0): string|false { return json_encode($value,$flags); }
spl_autoload_register(static function(string $class): void { $prefix='BonyadAlavi\\FormEngine\\'; if(!str_starts_with($class,$prefix)) return; $file=dirname(__DIR__).'/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php'; if(is_readable($file)) require_once $file; });
use BonyadAlavi\FormEngine\Actions\ActionRegistry;
use BonyadAlavi\FormEngine\Actions\Pdf\PdfGenerator;
use BonyadAlavi\FormEngine\Actions\Tokens\TokenResolver;
use BonyadAlavi\FormEngine\Repository\SubmissionRepository;
$r=new ActionRegistry(); $tokens=new TokenResolver(); $r->registerCore($tokens); $r->registerExtended($tokens,new SubmissionRepository(),new PdfGenerator());
$expected=['redirect','create_user','login_user','update_user','assign_role','update_user_meta','change_status','add_note','generate_pdf','email_pdf','save_post'];
$checks=['all-registered'=>array_diff($expected,array_keys($r->all()))===[],'redirect-no-retry'=>$r->get('redirect')?->supportsRetry===false,'login-no-retry'=>$r->get('login_user')?->supportsRetry===false,'status-workflow-select'=>($r->get('change_status')?->settingsSchema['status']['type']??'')==='workflow_select','post-safe-default'=>($r->get('save_post')?->settingsSchema['post_status']['default']??'')==='draft'];
$failed=false; foreach($checks as $name=>$ok){echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL;if(!$ok)$failed=true;} exit($failed?1:0);

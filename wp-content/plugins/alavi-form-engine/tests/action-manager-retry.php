<?php
declare(strict_types=1);

function sanitize_key(string $key): string { return strtolower((string)preg_replace('/[^a-z0-9_\-\.]/i','',$key)); }
function wp_json_encode(mixed $value,int $flags=0): string|false { return json_encode($value,$flags); }
function current_time(string $type,bool $gmt=false): string { return '2026-09-04 12:00:00'; }
function do_action(string $hook,mixed ...$args): void {}

final class RetryWpdb {
    public string $prefix='wp_'; public int $insert_id=0; public array $guards=[]; public array $logs=[];
    public function prepare(string $query,mixed ...$args): array { return [$query,$args]; }
    public function query(mixed $prepared): int { [$q,$a]=$prepared; if(str_contains($q,'afe_action_once')){ $k=$a[0].'|'.$a[1].'|'.$a[2]; if(isset($this->guards[$k])) return 0; $this->guards[$k]='claimed'; return 1; } return 1; }
    public function get_var(mixed $prepared): int { [$q,$a]=$prepared; $n=0; if(str_contains($q,'afe_action_log')) foreach($this->logs as $log) if(($log['submission_id']??0)===$a[0]&&($log['event_key']??'')===$a[1]&&($log['action_key']??'')===$a[2]) $n++; return $n; }
    public function insert(string $table,array $data): bool { $this->insert_id++; $data['id']=$this->insert_id; $this->logs[$this->insert_id]=$data; return true; }
    public function update(string $table,array $data,array $where,array $formats=[],array $whereFormats=[]): int { if(str_contains($table,'afe_action_once')){ $k=$where['submission_id'].'|'.$where['event_key'].'|'.$where['action_key']; $this->guards[$k]=$data['status']; return 1; } if(isset($where['id'],$this->logs[$where['id']])){ $this->logs[$where['id']]=array_merge($this->logs[$where['id']],$data); return 1; } return 0; }
    public function delete(string $table,array $where,array $formats=[]): int { $k=$where['submission_id'].'|'.$where['event_key'].'|'.$where['action_key']; if(isset($this->guards[$k])) unset($this->guards[$k]); return 1; }
}
$GLOBALS['wpdb']=new RetryWpdb();

spl_autoload_register(static function(string $class): void { $prefix='BonyadAlavi\\FormEngine\\'; if(!str_starts_with($class,$prefix)) return; $file=dirname(__DIR__).'/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php'; if(is_readable($file)) require_once $file; });

use BonyadAlavi\FormEngine\Actions\ActionContext;
use BonyadAlavi\FormEngine\Actions\ActionDefinition;
use BonyadAlavi\FormEngine\Actions\ActionExecutionRepository;
use BonyadAlavi\FormEngine\Actions\ActionManager;
use BonyadAlavi\FormEngine\Actions\ActionRegistry;

$attempt=0;
$registry=new ActionRegistry();
$registry->register(new ActionDefinition('flaky','Flaky'),static function() use (&$attempt): void { $attempt++; if($attempt===1) throw new RuntimeException('first failure'); });
$manager=new ActionManager($registry,new ActionExecutionRepository());
$defs=[['event'=>'submission.submitted','actions'=>[[
    'action_key'=>'flaky_once','type'=>'flaky','enabled'=>true,'execution_policy'=>'once_per_submission','on_error'=>'continue','config'=>[],
]]]];
$context=new ActionContext(9,'demo',[],['status'=>'new'],'submission.submitted');
$first=$manager->run($defs,$context);
$retry=$manager->retry($defs,$context,'flaky_once');
$checks=[
    'first-failed'=>isset($first['flaky_once']),
    'retry-succeeded'=>$retry->errors===[],
    'two-attempts'=>$attempt===2,
    'guard-success'=>in_array('success',$GLOBALS['wpdb']->guards,true),
    'two-logs'=>count($GLOBALS['wpdb']->logs)===2,
];
$failed=false; foreach($checks as $name=>$ok){echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL;if(!$ok)$failed=true;} exit($failed?1:0);

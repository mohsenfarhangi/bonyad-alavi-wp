<?php
declare(strict_types=1);

function sanitize_key(string $key): string { return strtolower((string)preg_replace('/[^a-z0-9_\-\.]/i','',$key)); }
function wp_json_encode(mixed $value, int $flags=0): string|false { return json_encode($value,$flags); }
function current_time(string $type, bool $gmt=false): string { return '2026-09-04 12:00:00'; }
function do_action(string $hook, mixed ...$args): void {}

final class FakeWpdb {
    public string $prefix='wp_';
    public int $insert_id=0;
    public array $guards=[];
    public array $logs=[];
    public function prepare(string $query, mixed ...$args): array { return [$query,$args]; }
    public function query(mixed $prepared): int {
        [$query,$args]=$prepared;
        if(str_contains($query,'afe_action_once')){
            $key=$args[0].'|'.$args[1].'|'.$args[2];
            if(isset($this->guards[$key])) return 0;
            $this->guards[$key]='claimed';
            return 1;
        }
        return 1;
    }
    public function get_var(mixed $prepared): int {
        [$query,$args]=$prepared;
        if(str_contains($query,'afe_action_log')){
            $count=0;
            foreach($this->logs as $log){
                if(($log['submission_id']??0)===$args[0] && ($log['event_key']??'')===$args[1] && ($log['action_key']??'')===$args[2]) $count++;
            }
            return $count;
        }
        return 0;
    }
    public function insert(string $table,array $data): bool { $this->insert_id++; $data['id']=$this->insert_id; $this->logs[$this->insert_id]=$data; return true; }
    public function update(string $table,array $data,array $where,array $formats=[],array $whereFormats=[]): int {
        if(str_contains($table,'afe_action_once')){
            $key=$where['submission_id'].'|'.$where['event_key'].'|'.$where['action_key'];
            if(isset($this->guards[$key])) $this->guards[$key]=$data['status'];
            return 1;
        }
        if(str_contains($table,'afe_action_log') && isset($where['id'],$this->logs[$where['id']])){
            $this->logs[$where['id']]=array_merge($this->logs[$where['id']],$data);
            return 1;
        }
        return 0;
    }
    public function delete(string $table,array $where,array $formats=[]): int {
        $key=$where['submission_id'].'|'.$where['event_key'].'|'.$where['action_key'];
        if(isset($this->guards[$key])){ unset($this->guards[$key]); return 1; }
        return 0;
    }
}
$GLOBALS['wpdb']=new FakeWpdb();

spl_autoload_register(static function(string $class): void {
    $prefix='BonyadAlavi\\FormEngine\\';
    if (!str_starts_with($class,$prefix)) return;
    $file=dirname(__DIR__).'/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';
    if (is_readable($file)) require_once $file;
});

use BonyadAlavi\FormEngine\Actions\ActionContext;
use BonyadAlavi\FormEngine\Actions\ActionDefinition;
use BonyadAlavi\FormEngine\Actions\ActionExecutionRepository;
use BonyadAlavi\FormEngine\Actions\ActionManager;
use BonyadAlavi\FormEngine\Actions\ActionRegistry;

$count=0;
$registry=new ActionRegistry();
$registry->register(new ActionDefinition('counter','شمارنده'), static function(ActionContext $context,array $config) use (&$count): void { $count++; });
$manager=new ActionManager($registry,new ActionExecutionRepository());
$context=new ActionContext(15,'demo',['scope'=>'national'],['status'=>'new'],'submission.submitted','دمو',['scope']);
$defs=[[ 
    'action_key'=>'only_once',
    'type'=>'counter',
    'on'=>['submission.submitted'],
    'execution_policy'=>'once_per_submission',
    'when'=>[['field'=>'scope','operator'=>'=','value'=>'national']],
]];
$manager->run($defs,$context);
$manager->run($defs,$context);
$otherEvent=new ActionContext(15,'demo',['scope'=>'national'],['status'=>'new'],'submission.updated','دمو',['scope']);
$manager->run($defs,$otherEvent);

$checks=[
    'once-per-submission'=>$count===1,
    'success-log'=>count(array_filter($GLOBALS['wpdb']->logs,static fn($log)=>($log['status']??'')==='success'))===1,
    'guard-success'=>in_array('success',$GLOBALS['wpdb']->guards,true),
];
$failed=false;
foreach($checks as $name=>$ok){ echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL; if(!$ok)$failed=true; }
exit($failed?1:0);

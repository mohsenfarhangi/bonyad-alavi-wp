<?php
declare(strict_types=1);

define('ARRAY_A','ARRAY_A');
function absint($value):int {return abs((int)$value);}
function current_time($format,$gmt=false):string {return '2026-11-05 10:00:00';}
final class AdminFieldFakeDb {
    public string $prefix='wp_';
    public array $rows=[];
    public function prepare(string $sql,...$args):string {return vsprintf($sql,$args);}
    public function get_results(string $sql,string $format=ARRAY_A):array {
        if(preg_match('/submission_id IN \(([^)]+)\)/',$sql,$match)){
            $ids=array_map('intval',explode(',',$match[1]));
            return array_values(array_filter($this->rows,static fn($row)=>in_array($row['submission_id'],$ids,true)));
        }
        preg_match('/submission_id=([0-9]+)/',$sql,$m);$id=(int)($m[1]??0);
        return array_values(array_filter($this->rows,static fn($row)=>$row['submission_id']===$id));
    }
    public function replace(string $table,array $data,array $formats=[]):int|false {
        if ($table!=='wp_afe_submission_admin_fields') return false;
        $key=$data['submission_id'].'_'.$data['field_key'];
        $this->rows[$key]=$data;
        return 1;
    }
    public function delete(string $table,array $conditions,array $formats=[]):int|false {
        if ($table!=='wp_afe_submission_admin_fields') return false;
        $id=(int)$conditions['submission_id'];
        if (isset($conditions['field_key'])) {
            $key=$id.'_'.$conditions['field_key'];
            $exists=isset($this->rows[$key]);unset($this->rows[$key]);return $exists?1:0;
        }
        foreach ($this->rows as $key=>$row) if ($row['submission_id']===$id) unset($this->rows[$key]);
        return 1;
    }
}
$GLOBALS['wpdb']=new AdminFieldFakeDb();
spl_autoload_register(static function(string $class):void {
    $prefix='BonyadAlavi\\FormEngine\\';
    if(!str_starts_with($class,$prefix)) return;
    $path=dirname(__DIR__).'/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';
    if(is_file($path)) require_once $path;
});
$repo=new BonyadAlavi\FormEngine\Repository\SubmissionRepository();
$first=$repo->saveAdminFields(41,['group_level'=>'faal'],7);
$second=$repo->saveAdminFields(42,['group_level'=>'pishro'],8);
$one=$repo->adminFields(41);
$two=$repo->adminFields(42);
$bulk=$repo->adminFieldsForSubmissions([41,42,41]);
$cleared=$repo->saveAdminFields(41,['group_level'=>''],7);
$stays=$repo->adminFields(42);
$reject=$repo->saveAdminFields(41,['invalid-x'=>'value'],7);
$checks=[
    'stored-separately-without-main-data-json'=>$first && $second && $one===['group_level'=>'faal']
       && $two===['group_level'=>'pishro'] && !isset($GLOBALS['wpdb']->rows['data_json']),
    'per-submission-isolation'=>$one!==$two,
    'bulk-fetch-correct-mapping'=>$bulk===[41=>['group_level'=>'faal'],42=>['group_level'=>'pishro']],
    'clear-single-value'=>$cleared && $repo->adminFields(41)===[],
    'clearing-one-does-not-affect-another'=>$stays===['group_level'=>'pishro'],
    'invalid-name-rejected'=>$reject===false,
    'audit-editor-tracking'=>($GLOBALS['wpdb']->rows['42_group_level']['updated_by']??null)===8,
];
foreach($checks as $name=>$ok){echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL;if(!$ok)exit(1);}

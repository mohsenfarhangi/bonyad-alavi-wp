<?php
declare(strict_types=1);

function current_time(string $type,bool $gmt=false): string { return '2026-09-04 12:00:00'; }

final class FakeDuplicateWpdb {
    public string $prefix='wp_';
    /** @var array<int,array<string,mixed>> */
    public array $submissions=[];
    /** @var array<int,array<string,mixed>> */
    public array $fingerprints=[];
    private int $nextFingerprintId=1;

    public function prepare(string $query,mixed ...$args): array { return [$query,$args]; }

    public function get_var(mixed $prepared): mixed {
        [$query,$args]=$prepared;
        if(str_contains($query,'SELECT fingerprint FROM')){
            $submissionId=(int)$args[0];
            foreach($this->fingerprints as $row) if((int)$row['submission_id']===$submissionId) return $row['fingerprint'];
            return null;
        }
        if(str_contains($query,'SELECT id FROM') && str_contains($query,'duplicate_of_submission_id')){
            [$slug,$ownerId]=$args;
            $ids=[];
            foreach($this->submissions as $id=>$row){
                if($row['form_slug']!==$slug || empty($row['is_duplicate']) || (int)$row['duplicate_of_submission_id']!==(int)$ownerId || $row['trashed_at']!==null) continue;
                $ids[]=(int)$id;
            }
            sort($ids);
            return $ids[0]??null;
        }
        if(str_contains($query,'SELECT f.submission_id')){
            $slug=(string)$args[0]; $fingerprint=(string)$args[1]; $exclude=(int)($args[2]??0);
            foreach($this->fingerprints as $fp){
                $id=(int)$fp['submission_id'];
                if($fp['form_slug']!==$slug || $fp['fingerprint']!==$fingerprint || ($exclude>0 && $id===$exclude)) continue;
                $submission=$this->submissions[$id]??null;
                if($submission && $submission['trashed_at']===null) return $id;
            }
            return null;
        }
        return null;
    }

    public function query(mixed $prepared): int {
        [$query,$args]=$prepared;
        if(str_contains($query,'INSERT IGNORE INTO') && str_contains($query,'afe_submission_fingerprints')){
            [$slug,$submissionId,$fingerprint,$createdAt]=$args;
            foreach($this->fingerprints as $row){
                if(($row['form_slug']===$slug && $row['fingerprint']===$fingerprint) || (int)$row['submission_id']===(int)$submissionId) return 0;
            }
            $id=$this->nextFingerprintId++;
            $this->fingerprints[$id]=['id'=>$id,'form_slug'=>$slug,'submission_id'=>(int)$submissionId,'fingerprint'=>$fingerprint,'created_at'=>$createdAt];
            return 1;
        }
        if(str_starts_with(ltrim($query),'UPDATE') && str_contains($query,'duplicate_of_submission_id=%d')){
            [$newOwner,$slug,$oldOwner]=$args;
            $exclude=(int)($args[3]??0);
            $count=0;
            foreach($this->submissions as $id=>&$row){
                if($row['form_slug']!==$slug || empty($row['is_duplicate']) || (int)$row['duplicate_of_submission_id']!==(int)$oldOwner || $row['trashed_at']!==null || ($exclude>0 && (int)$id===$exclude)) continue;
                $row['duplicate_of_submission_id']=(int)$newOwner; $count++;
            }
            unset($row);
            return $count;
        }
        return 0;
    }

    public function delete(string $table,array $where,array $formats=[]): int {
        if(!str_contains($table,'afe_submission_fingerprints')) return 0;
        $submissionId=(int)$where['submission_id']; $count=0;
        foreach(array_keys($this->fingerprints) as $id){
            if((int)$this->fingerprints[$id]['submission_id']===$submissionId){ unset($this->fingerprints[$id]); $count++; }
        }
        return $count;
    }

    public function update(string $table,array $data,array $where,array $formats=[],array $whereFormats=[]): int|false {
        if(!str_contains($table,'afe_submissions')) return false;
        $id=(int)$where['id'];
        if(!isset($this->submissions[$id])) return 0;
        $this->submissions[$id]=array_merge($this->submissions[$id],$data);
        return 1;
    }
}

spl_autoload_register(static function(string $class): void {
    $prefix='BonyadAlavi\\FormEngine\\';
    if (!str_starts_with($class,$prefix)) return;
    $file=dirname(__DIR__).'/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';
    if (is_readable($file)) require_once $file;
});

use BonyadAlavi\FormEngine\Duplicate\DuplicateRepository;

$db=new FakeDuplicateWpdb();
$GLOBALS['wpdb']=$db;
$db->submissions=[
    1=>['form_slug'=>'demo','is_duplicate'=>0,'duplicate_of_submission_id'=>0,'trashed_at'=>'2026-09-04 12:00:00'],
    2=>['form_slug'=>'demo','is_duplicate'=>1,'duplicate_of_submission_id'=>1,'trashed_at'=>null],
    3=>['form_slug'=>'demo','is_duplicate'=>1,'duplicate_of_submission_id'=>1,'trashed_at'=>null],
    4=>['form_slug'=>'demo','is_duplicate'=>1,'duplicate_of_submission_id'=>1,'trashed_at'=>'2026-09-03 12:00:00'],
];
$db->fingerprints=[1=>['id'=>1,'form_slug'=>'demo','submission_id'=>1,'fingerprint'=>'fp-x','created_at'=>'2026-09-01 00:00:00']];

$repo=new DuplicateRepository();
$promoted=$repo->releaseAndPromote('demo',1,'fp-x');
$owner=$repo->find('demo','fp-x');

$checks=[
    'promoted-first-active'=>$promoted===2,
    'fingerprint-owner'=>$owner===2,
    'promoted-cleared-marker'=>($db->submissions[2]['is_duplicate']??1)===0 && ($db->submissions[2]['duplicate_of_submission_id']??1)===0,
    'active-dependent-retargeted'=>($db->submissions[3]['duplicate_of_submission_id']??0)===2,
    'trashed-dependent-untouched'=>($db->submissions[4]['duplicate_of_submission_id']??0)===1,
];

$failed=false;
foreach($checks as $name=>$ok){ echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL; if(!$ok)$failed=true; }
exit($failed?1:0);

<?php
declare(strict_types=1);
namespace BonyadAlavi\FormEngine\Export;
use BonyadAlavi\FormEngine\Admin\FormDataPresenter;
use BonyadAlavi\FormEngine\Repository\SubmissionRepository;
use BonyadAlavi\FormEngine\Localization\LocaleDateService;
use RuntimeException;

final class ExcelExporter
{
    public function __construct(private readonly FormDataPresenter $presenter,private readonly SubmissionRepository $repo,private readonly LocaleDateService $dates){}

    public function streamSingle(array $form,array $submission,string $filename): never
    {
        $spreadsheet=$this->newSpreadsheet();$sheet=$spreadsheet->getActiveSheet();$this->buildDetailSheet($sheet,$form,$submission);$this->stream($spreadsheet,$filename);
    }

    public function streamList(array $grouped,string $filename): never
    {
        $spreadsheet=$this->newSpreadsheet();$first=true;$used=[];foreach($grouped as $bundle){$form=$bundle['form'];$rows=$bundle['rows'];$sheet=$first?$spreadsheet->getActiveSheet():$spreadsheet->createSheet();$first=false;$profile=$this->profile($form);$wanted=$this->safeSheetName((string)($profile['excel']['sheet_name']??$form['title']??'ثبت‌ها'));$title=$this->uniqueSheetName($wanted,$used);$used[]=$title;$this->buildListSheet($sheet,$form,$rows,$title);}if($first){$sheet=$spreadsheet->getActiveSheet();$sheet->setCellValue('A1','هیچ داده‌ای برای خروجی وجود ندارد.');}$this->stream($spreadsheet,$filename);
    }

    private function newSpreadsheet(): object
    {
        if(!ExportPackageStatus::excelReady())throw new RuntimeException('پکیج Excel در دسترس نیست. وابستگی phpoffice/phpspreadsheet باید داخل vendor افزونه نصب و باندل شود.');
        $missing=ExportPackageStatus::excelMissingExtensions();if($missing)throw new RuntimeException('Extensionهای لازم Excel روی PHP فعال نیستند: '.implode(', ',$missing));
        return new \PhpOffice\PhpSpreadsheet\Spreadsheet();
    }

    private function profile(array $form): array{return ExportProfile::resolve($form);}

    private function buildDetailSheet(object $sheet,array $form,array $submission): void
    {
        $profile=$this->profile($form);$cfg=(array)$profile['excel'];$sheet->setTitle($this->safeSheetName((string)($cfg['sheet_name']??'ثبت')));$sheet->setRightToLeft(!empty($cfg['rtl']));
        $sheet->setCellValueExplicit('A1','فرم',\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);$sheet->setCellValueExplicit('B1',(string)$form['title'],\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('A2','کد رهگیری',\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);$sheet->setCellValueExplicit('B2',(string)$submission['tracking_code'],\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $status=(string)($submission['status']??'');$sheet->setCellValueExplicit('A3','وضعیت',\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);$sheet->setCellValueExplicit('B3',(string)($form['workflow'][$status]??$status),\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $sheet->setCellValueExplicit('A4','تاریخ ثبت',\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);$sheet->setCellValueExplicit('B4',$this->dates->formatUtc((string)$submission['created_at'],true),\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);
        $r=6;$data=(array)$submission['data'];foreach($this->presenter->sections($form) as $section){$sheet->mergeCells("A{$r}:B{$r}");$sheet->setCellValueExplicit("A{$r}",(string)($section['step']['title']??''),\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);$this->styleSection($sheet,"A{$r}:B{$r}",$cfg);$r++;
            foreach($section['fields'] as $field){$name=(string)$field['name'];$sheet->setCellValueExplicit("A{$r}",$this->presenter->fieldLabel($field),\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);if(($field['type']??'')==='file'){$files=$this->repo->filesForField((int)$submission['id'],$name);$this->setFilesCell($sheet,"B{$r}",$files);}else{$sheet->setCellValueExplicit("B{$r}",$this->presenter->plainField($field,$data[$name]??'',$data),\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);}$r++;}}
        $sheet->getColumnDimension('A')->setWidth(28);$sheet->getColumnDimension('B')->setWidth(60);$sheet->getStyle("A1:B".max(1,$r-1))->getAlignment()->setWrapText(true)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);$sheet->getStyle("A1:A".max(1,$r-1))->getFont()->setBold(true);
    }

    private function buildListSheet(object $sheet,array $form,array $rows,string $title=''): void
    {
        $profile=$this->profile($form);$cfg=(array)$profile['excel'];$sheet->setTitle($title!==''?$title:$this->safeSheetName((string)($cfg['sheet_name']??$form['title']??'ثبت‌ها')));$sheet->setRightToLeft(!empty($cfg['rtl']));$columns=[];foreach((array)($cfg['columns']??[]) as $column)if(!empty($column['enabled']))$columns[]=$column;
        array_unshift($columns,['field'=>'__tracking','label'=>'کد رهگیری','width'=>18],['field'=>'__status','label'=>'وضعیت','width'=>16],['field'=>'__date','label'=>'تاریخ ثبت','width'=>18]);
        foreach($columns as $i=>$column){$cell=$this->cell($i+1,1);$sheet->setCellValueExplicit($cell,(string)$column['label'],\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);$sheet->getColumnDimension(\PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($i+1))->setWidth((float)($column['width']??22));}
        $this->styleHeader($sheet,'A1:'.$this->cell(count($columns),1),$cfg);$r=2;foreach($rows as $row){$data=(array)$row['data'];foreach($columns as $i=>$column){$field=(string)$column['field'];$value='';if($field==='__tracking')$value=(string)$row['tracking_code'];elseif($field==='__status'){$status=(string)$row['status'];$value=(string)($form['workflow'][$status]??$status);}elseif($field==='__date')$value=$this->dates->formatUtc((string)$row['created_at'],true);else{$def=$this->fieldByPath($form,$field);if($def){$raw=$this->valueByPath($data,$field);if(($def['type']??'')==='file'&&!str_contains($field,'.')){$files=$this->repo->filesForField((int)$row['id'],$field);$this->setFilesCell($sheet,$this->cell($i+1,$r),$files);continue;}$value=$this->presenter->plainField($def,$raw,$data);}}$sheet->setCellValueExplicit($this->cell($i+1,$r),$value,\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);}$r++;}
        if(!empty($cfg['freeze_header']))$sheet->freezePane('A2');if(!empty($cfg['auto_filter'])&&count($columns)>0)$sheet->setAutoFilter('A1:'.$this->cell(count($columns),max(1,$r-1)));$sheet->getStyle('A1:'.$this->cell(max(1,count($columns)),max(1,$r-1)))->getAlignment()->setWrapText(true)->setVertical(\PhpOffice\PhpSpreadsheet\Style\Alignment::VERTICAL_TOP);
    }

    private function styleHeader(object $sheet,string $range,array $cfg): void{$style=$sheet->getStyle($range);$style->getFont()->setBold(!empty($cfg['header_bold']))->getColor()->setRGB((string)($cfg['header_text']??'0F6B4F'));$style->getFill()->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)->getStartColor()->setRGB((string)($cfg['header_fill']??'E7F1ED'));}
    private function styleSection(object $sheet,string $range,array $cfg): void{$this->styleHeader($sheet,$range,$cfg);}
    private function setFilesCell(object $sheet,string $cell,array $files): void{$names=[];$url='';foreach($files as $file){$names[]=(string)($file['original_name']??'فایل');if($url===''){if(!empty($file['attachment_id'])){$u=wp_get_attachment_url((int)$file['attachment_id']);if(is_string($u))$url=$u;}if($url===''&&!empty($file['url']))$url=(string)$file['url'];}}$sheet->setCellValueExplicit($cell,$names?implode("\n",$names):'—',\PhpOffice\PhpSpreadsheet\Cell\DataType::TYPE_STRING);if($url!==''&&wp_http_validate_url($url))$sheet->getCell($cell)->getHyperlink()->setUrl($url);}
    private function fieldByPath(array $form,string $path): ?array{$segments=explode('.',$path);foreach((array)$form['steps'] as $step)foreach((array)$step['items'] as $field){$x=$this->findField((array)$field,$segments);if($x)return$x;}return null;}
    private function findField(array $field,array $segments): ?array{if(($field['name']??'')!==($segments[0]??''))return null;if(count($segments)===1)return$field;if(($field['type']??'')!=='repeater')return null;$rest=array_slice($segments,1);foreach((array)($field['fields']??[]) as $child){$x=$this->findField((array)$child,$rest);if($x)return$x;}return null;}
    private function valueByPath(mixed $value,string $path): mixed{return $this->walk($value,explode('.',$path));}
    private function walk(mixed $value,array $segments): mixed{if($segments===[])return$value;if(!is_array($value))return null;$seg=(string)$segments[0];$rest=array_slice($segments,1);if(array_key_exists($seg,$value))return$this->walk($value[$seg],$rest);if(array_is_list($value)){$out=[];foreach($value as $row){$x=$this->walk($row,$segments);if($x!==null)$out[]=$x;}return$out;}return null;}
    private function cell(int $col,int $row): string{return \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($col).$row;}
    private function uniqueSheetName(string $name,array $used): string{if(!in_array($name,$used,true))return$name;$base=$name;for($i=2;$i<1000;$i++){$suffix='-'.$i;$max=31-strlen($suffix);$candidate=(function_exists('mb_substr')?mb_substr($base,0,$max):substr($base,0,$max)).$suffix;if(!in_array($candidate,$used,true))return$candidate;}return substr(hash('sha256',$name),0,12);}
    private function safeSheetName(string $name): string{$name=preg_replace('~[\\/?*\[\]:]+~u',' ',$name)??'Sheet';$name=trim($name);if($name==='')$name='Sheet';return function_exists('mb_substr')?mb_substr($name,0,31):substr($name,0,31);}
    private function stream(object $spreadsheet,string $filename): never
    {
        $tmp=function_exists('wp_tempnam')?wp_tempnam($filename):tempnam(sys_get_temp_dir(),'afe-xlsx-');
        if(!is_string($tmp)||$tmp===''){$spreadsheet->disconnectWorksheets();throw new RuntimeException('ساخت فایل موقت برای خروجی Excel ناموفق بود. مسیر موقت PHP/WordPress را بررسی کنید.');}
        try{
            $writer=new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save($tmp);
            $spreadsheet->disconnectWorksheets();
            clearstatcache(true,$tmp);
            $size=is_file($tmp)?filesize($tmp):false;
            if($size===false||$size<4)throw new RuntimeException('PhpSpreadsheet فایل XLSX معتبری تولید نکرد.');
            $head=file_get_contents($tmp,false,null,0,2);
            if($head!=='PK')throw new RuntimeException('خروجی تولیدشده ساختار معتبر XLSX/ZIP ندارد.');
            BinaryDownload::streamFile($tmp,'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',$filename,true);
        }catch(\Throwable $e){
            $spreadsheet->disconnectWorksheets();
            @unlink($tmp);
            if($e instanceof RuntimeException)throw $e;
            error_log('[Alavi Form Engine] Excel export failed: '.get_class($e).': '.$e->getMessage());
            throw new RuntimeException('تولید فایل Excel ناموفق بود: '.$e->getMessage(),0,$e);
        }
    }
}

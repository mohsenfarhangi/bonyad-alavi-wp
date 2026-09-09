<?php
declare(strict_types=1);
namespace BonyadAlavi\FormEngine\Export;
final class ExportProfile
{
    public const PDF_TOKENS=['{{form_title}}','{{form_slug}}','{{submission_id}}','{{tracking_code}}','{{status}}','{{submitted_at}}','{{fields_table}}','{{field:*}}','{{files:*}}'];
    public static function defaults(array $form): array
    {
        $columns=[];
        foreach((array)($form['steps']??[]) as $step) foreach((array)($step['items']??[]) as $field) self::collectColumn((array)$field,$columns,'');
        return ['pdf'=>['page_size'=>'A4','orientation'=>'portrait','header_html'=>'<div class="afe-export-header"><strong>{{form_title}}</strong><span>کد رهگیری: {{tracking_code}}</span></div>','body_html'=>'<div class="afe-export-meta">وضعیت: {{status}} · تاریخ ثبت: {{submitted_at}}</div>{{fields_table}}','footer_html'=>'<div class="afe-export-footer">Alavi Form Engine · {{tracking_code}}</div>','css'=>self::defaultPdfCss()],'excel'=>['sheet_name'=>'ثبت‌ها','rtl'=>true,'freeze_header'=>true,'auto_filter'=>true,'header_bold'=>true,'header_fill'=>'E7F1ED','header_text'=>'0F6B4F','columns'=>$columns]];
    }
    public static function resolve(array $form,array $override=[]): array
    {
        $base=self::defaults($form);
        $code=(array)($form['settings']['exports']??[]);
        $resolved=array_replace_recursive($base,$code,$override);
        if(isset($code['excel']['columns'])&&is_array($code['excel']['columns'])) $resolved['excel']['columns']=array_values($code['excel']['columns']);
        if(isset($override['excel']['columns'])&&is_array($override['excel']['columns'])) $resolved['excel']['columns']=array_values($override['excel']['columns']);
        return $resolved;
    }

    public static function defaultPdfCss(): string
    {
        return 'body{font-family:dejavusans;font-size:10pt;direction:rtl;color:#17231f}.afe-export-header{border-bottom:1px solid #0f6b4f;padding:0 0 8px;margin:0 0 12px}.afe-export-header strong{font-size:15pt;color:#0f6b4f}.afe-export-header span{float:left;font-size:9pt;color:#55635e}.afe-export-meta{margin-bottom:12px;color:#55635e}.afe-export-table{width:100%;border-collapse:collapse}.afe-export-table th,.afe-export-table td{border:1px solid #dce4e0;padding:7px;text-align:right;vertical-align:top}.afe-export-table th{width:30%;background:#f4f8f6}.afe-export-step th{width:auto;background:#e7f1ed;color:#0f6b4f;font-size:11pt}.afe-export-repeater{width:100%;border-collapse:collapse}.afe-export-repeater th,.afe-export-repeater td{font-size:8.5pt}.afe-export-footer{border-top:1px solid #dce4e0;margin-top:12px;padding-top:6px;color:#7b8782;font-size:8pt;text-align:center}.afe-export-file{color:#0f6b4f;text-decoration:underline}';
    }
    private static function collectColumn(array $field,array &$columns,string $prefix): void
    {
        $type=(string)($field['type']??'');$name=(string)($field['name']??''); if($name===''||$type==='html') return; $path=$prefix===''?$name:$prefix.'.'.$name;
        if($type==='repeater'){foreach((array)($field['fields']??[]) as $child) self::collectColumn((array)$child,$columns,$path);return;}
        $columns[]=['field'=>$path,'label'=>(string)($field['label']??$path),'enabled'=>true,'width'=>self::suggestWidth($field)];
    }
    private static function suggestWidth(array $field): int { return match((string)($field['type']??'')){'textarea'=>40,'file'=>28,'date'=>16,'tel'=>18,'email','url'=>28,'number'=>14,default=>22}; }
}

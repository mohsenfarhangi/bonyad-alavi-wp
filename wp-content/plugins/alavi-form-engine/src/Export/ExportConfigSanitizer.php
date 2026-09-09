<?php
declare(strict_types=1);
namespace BonyadAlavi\FormEngine\Export;
final class ExportConfigSanitizer
{
    public function sanitize(array $input,array $form): array
    {
        $defaults=ExportProfile::defaults($form);$pdf=(array)($input['pdf']??[]);$excel=(array)($input['excel']??[]);$out=['pdf'=>[],'excel'=>[]];
        $page=sanitize_key((string)($pdf['page_size']??$defaults['pdf']['page_size']));$out['pdf']['page_size']=in_array($page,['a4','a5','letter'],true)?strtoupper($page):'A4';
        $orientation=sanitize_key((string)($pdf['orientation']??'portrait'));$out['pdf']['orientation']=in_array($orientation,['portrait','landscape'],true)?$orientation:'portrait';
        foreach(['header_html','body_html','footer_html'] as $key)$out['pdf'][$key]=$this->sanitizeHtml(wp_unslash((string)($pdf[$key]??$defaults['pdf'][$key])));
        $out['pdf']['css']=$this->sanitizeCss(wp_unslash((string)($pdf['css']??$defaults['pdf']['css'])));
        $sheet=sanitize_text_field(wp_unslash((string)($excel['sheet_name']??'ثبت‌ها')));$sheet=preg_replace('~[\\/?*\[\]:]+~u',' ',$sheet)??'ثبت‌ها';$sheet=trim($sheet);$out['excel']['sheet_name']=function_exists('mb_substr')?mb_substr($sheet,0,31):substr($sheet,0,31);if($out['excel']['sheet_name']==='')$out['excel']['sheet_name']='ثبت‌ها';
        foreach(['rtl','freeze_header','auto_filter','header_bold'] as $key)$out['excel'][$key]=!empty($excel[$key]);
        foreach(['header_fill'=>'E7F1ED','header_text'=>'0F6B4F'] as $key=>$fallback){$value=strtoupper(preg_replace('/[^0-9A-F]/i','',(string)($excel[$key]??$fallback))??'');$out['excel'][$key]=strlen($value)===6?$value:$fallback;}
        $allowed=$this->fieldMap($form);$columns=[];foreach((array)($excel['columns']??[]) as $column){if(!is_array($column))continue;$field=sanitize_text_field(wp_unslash((string)($column['field']??'')));if(!isset($allowed[$field]))continue;$columns[]=['field'=>$field,'label'=>sanitize_text_field(wp_unslash((string)($column['label']??$allowed[$field]))),'enabled'=>!empty($column['enabled']),'width'=>max(8,min(80,(int)($column['width']??22)))];}if($columns===[])$columns=$defaults['excel']['columns'];$out['excel']['columns']=$columns;return $out;
    }
    private function sanitizeHtml(string $html): string{return wp_kses($html,['div'=>['class'=>true,'dir'=>true,'style'=>true],'span'=>['class'=>true,'dir'=>true,'style'=>true],'p'=>['class'=>true,'dir'=>true,'style'=>true],'strong'=>[],'b'=>[],'em'=>[],'i'=>[],'br'=>[],'h1'=>['class'=>true,'style'=>true],'h2'=>['class'=>true,'style'=>true],'h3'=>['class'=>true,'style'=>true],'table'=>['class'=>true,'dir'=>true,'style'=>true],'thead'=>[],'tbody'=>[],'tr'=>['class'=>true],'th'=>['class'=>true,'colspan'=>true,'rowspan'=>true,'style'=>true],'td'=>['class'=>true,'colspan'=>true,'rowspan'=>true,'style'=>true],'a'=>['href'=>true,'target'=>true,'rel'=>true,'class'=>true],'ul'=>[],'ol'=>[],'li'=>[]]);}
    private function sanitizeCss(string $css): string{$css=str_replace(["\0",'<?','?>'],'',$css);$css=preg_replace('/@(?:import|font-face|charset|namespace|supports|document)\b[^;{}]*(?:;|\{.*?\})/is','',$css)??'';$css=preg_replace('/(?:expression|javascript\s*:|behavior\s*:|-moz-binding|url\s*\()/i','',$css)??'';return trim(substr($css,0,20000));}
    private function fieldMap(array $form): array{$out=[];foreach((array)($form['steps']??[]) as $step)foreach((array)($step['items']??[]) as $field)$this->collect((array)$field,$out,'');return $out;}
    private function collect(array $field,array &$out,string $prefix): void{$name=(string)($field['name']??'');$type=(string)($field['type']??'');if($name===''||$type==='html')return;$path=$prefix===''?$name:$prefix.'.'.$name;if($type==='repeater'){foreach((array)($field['fields']??[]) as $child)$this->collect((array)$child,$out,$path);return;}$out[$path]=(string)($field['label']??$path);}
}

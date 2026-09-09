<?php
declare(strict_types=1);
namespace BonyadAlavi\FormEngine\Actions\Pdf;

use BonyadAlavi\FormEngine\Actions\ActionContext;
use BonyadAlavi\FormEngine\Export\ExportProfile;
use BonyadAlavi\FormEngine\Export\PdfExporter;
use BonyadAlavi\FormEngine\Repository\SubmissionRepository;
use RuntimeException;

final class PdfGenerator
{
    public function __construct(private readonly ?SubmissionRepository $submissions=null,private readonly ?PdfExporter $exporter=null){}

    /** @return array{path:string,url:string,filename:string} */
    public function generate(ActionContext $context,string $filenameTemplate=''): array
    {
        $uploads=wp_upload_dir();if(!empty($uploads['error']))throw new RuntimeException('مسیر Upload وردپرس در دسترس نیست.');$dir=trailingslashit((string)$uploads['basedir']).'alavi-form-engine/generated';if(!wp_mkdir_p($dir))throw new RuntimeException('ساخت پوشه PDFهای تولیدشده ناموفق بود.');
        $filename=sanitize_file_name($filenameTemplate);if($filename==='')$filename='submission-'.$context->submissionId.'.pdf';if(!str_ends_with(strtolower($filename),'.pdf'))$filename.='.pdf';$nonce=bin2hex(random_bytes(8));$filename=preg_replace('/\.pdf$/i','-'.gmdate('Ymd-His').'-'.$nonce.'.pdf',$filename)?:$filename;$path=trailingslashit($dir).$filename;
        $form=$context->form;$profile=ExportProfile::resolve($form);$html=$this->html($context,(array)$profile['pdf']);$bytes=($this->exporter??new PdfExporter())->bytes($html,(array)$profile['pdf']);if(file_put_contents($path,$bytes)===false)throw new RuntimeException('ذخیره فایل PDF تولیدشده ناموفق بود.');$url=trailingslashit((string)$uploads['baseurl']).'alavi-form-engine/generated/'.rawurlencode($filename);return['path'=>$path,'url'=>$url,'filename'=>$filename];
    }

    private function html(ActionContext $context,array $pdf): string
    {
        $status=(string)($context->submission['status']??'');$workflow=(array)($context->form['workflow']??[]);$tokens=['{{form_title}}'=>esc_html($context->formTitle?:$context->formSlug),'{{form_slug}}'=>esc_html($context->formSlug),'{{submission_id}}'=>(string)$context->submissionId,'{{tracking_code}}'=>esc_html((string)($context->submission['tracking_code']??'')),'{{status}}'=>esc_html((string)($workflow[$status]??$status)),'{{submitted_at}}'=>esc_html((string)($context->submission['created_at']??'')),'{{fields_table}}'=>$this->fieldsTable($context)];
        $header=$this->replace((string)($pdf['header_html']??''),$tokens,$context);$body=$this->replace((string)($pdf['body_html']??'{{fields_table}}'),$tokens,$context);$footer=$this->replace((string)($pdf['footer_html']??''),$tokens,$context);return '<style>'.(string)($pdf['css']??ExportProfile::defaultPdfCss()).'</style><div lang="fa" dir="rtl">'.$header.$body.$footer.'</div>';
    }

    private function replace(string $html,array $tokens,ActionContext $context): string
    {
        $out=strtr($html,$tokens);$out=preg_replace_callback('/\{\{field:([a-zA-Z0-9_.-]+)\}\}/',fn(array $m):string=>nl2br(esc_html($this->stringValue($this->valueByPath($context->data,$m[1])))),$out)??$out;
        $out=preg_replace_callback('/\{\{files:([a-zA-Z0-9_-]+)\}\}/',function(array $m)use($context):string{if(!$this->submissions)return'';$files=$this->submissions->filesForField($context->submissionId,$m[1]);$names=array_map(static fn(array $f):string=>(string)($f['original_name']??'فایل'),$files);return esc_html(implode('، ',$names));},$out)??$out;return$out;
    }

    private function fieldsTable(ActionContext $context): string
    {
        $html='<table class="afe-export-table"><tbody>';foreach((array)($context->form['steps']??[]) as $step){$title=(string)($step['title']??'');if($title!=='')$html.='<tr class="afe-export-step"><th colspan="2">'.esc_html($title).'</th></tr>';foreach((array)($step['items']??[]) as $field){if(($field['type']??'')==='html'||empty($field['name']))continue;$name=(string)$field['name'];$html.='<tr><th>'.esc_html((string)($field['label']??$name)).'</th><td>';$value=$context->data[$name]??'';if(($field['type']??'')==='file'&&$this->submissions){$files=$this->submissions->filesForField($context->submissionId,$name);$html.=esc_html(implode('، ',array_map(static fn(array $f):string=>(string)($f['original_name']??'فایل'),$files)));}else{$html.=nl2br(esc_html($this->stringValue($value)));}$html.='</td></tr>';}}return$html.'</tbody></table>';
    }

    private function stringValue(mixed $value): string{if(is_array($value))return wp_json_encode($value,JSON_UNESCAPED_UNICODE|JSON_PRETTY_PRINT)?:'';return(string)$value;}
    private function valueByPath(mixed $value,string $path): mixed{return$this->walk($value,explode('.',$path));}
    private function walk(mixed $value,array $segments): mixed{if($segments===[])return$value;if(!is_array($value))return null;$seg=(string)$segments[0];$rest=array_slice($segments,1);if(array_key_exists($seg,$value))return$this->walk($value[$seg],$rest);if(array_is_list($value)){$out=[];foreach($value as $row){$x=$this->walk($row,$segments);if($x!==null)$out[]=$x;}return$out;}return null;}
}

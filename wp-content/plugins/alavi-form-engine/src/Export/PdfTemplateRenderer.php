<?php
declare(strict_types=1);
namespace BonyadAlavi\FormEngine\Export;

use BonyadAlavi\FormEngine\Admin\FormDataPresenter;
use BonyadAlavi\FormEngine\Localization\LocaleDateService;
use BonyadAlavi\FormEngine\Repository\SubmissionRepository;

final class PdfTemplateRenderer
{
    public function __construct(private readonly FormDataPresenter $presenter,private readonly SubmissionRepository $repo,private readonly LocaleDateService $dates){}

    public function render(array $form,array $submission): string
    {
        $profile=$this->profile($form);$pdf=(array)$profile['pdf'];$tokens=$this->tokens($form,$submission);
        $header=$this->replace((string)$pdf['header_html'],$tokens,$form,$submission);
        $body=$this->replace((string)$pdf['body_html'],$tokens,$form,$submission);
        $footer=$this->replace((string)$pdf['footer_html'],$tokens,$form,$submission);
        $css=(string)$pdf['css'];
        return '<style>'.$css.'</style><div lang="fa" dir="rtl">'.$header.$body.$footer.'</div>';
    }

    public function profile(array $form): array
    {
        return ExportProfile::resolve($form);
    }

    private function tokens(array $form,array $submission): array
    {
        $status=(string)($submission['status']??'');
        return [
            '{{form_title}}'=>esc_html((string)($form['title']??$form['slug']??'')),
            '{{form_slug}}'=>esc_html((string)($form['slug']??'')),
            '{{submission_id}}'=>(string)(int)($submission['id']??0),
            '{{tracking_code}}'=>esc_html((string)($submission['tracking_code']??'')),
            '{{status}}'=>esc_html((string)($form['workflow'][$status]??$status)),
            '{{submitted_at}}'=>esc_html($this->dates->formatUtc((string)($submission['created_at']??''),true)),
            '{{fields_table}}'=>$this->fieldsTable($form,$submission),
        ];
    }

    private function replace(string $template,array $tokens,array $form,array $submission): string
    {
        $out=strtr($template,$tokens);
        $data=(array)($submission['data']??[]);
        $out=preg_replace_callback('/\{\{field:([a-zA-Z0-9_.-]+)\}\}/',function(array $m)use($form,$data):string{
            $field=$this->fieldByPath($form,$m[1]);if(!$field)return '';$value=$this->valueByPath($data,$m[1]);return $this->escapeValue($this->presenter->plainField($field,$value,$data));
        },$out)??$out;
        $out=preg_replace_callback('/\{\{files:([a-zA-Z0-9_.-]+)\}\}/',function(array $m)use($submission):string{
            if(str_contains($m[1],'.'))return '';$files=$this->repo->filesForField((int)($submission['id']??0),$m[1]);return $this->fileLinks($files);
        },$out)??$out;
        return $out;
    }

    private function fieldsTable(array $form,array $submission): string
    {
        $data=(array)($submission['data']??[]);$id=(int)($submission['id']??0);$html='<table class="afe-export-table"><tbody>';
        foreach($this->presenter->sections($form) as $section){$title=(string)($section['step']['title']??'');if($title!=='')$html.='<tr class="afe-export-step"><th colspan="2">'.esc_html($title).'</th></tr>';
            foreach($section['fields'] as $field){$name=(string)($field['name']??'');$html.='<tr><th>'.esc_html($this->presenter->fieldLabel($field)).'</th><td>';
                if(($field['type']??'')==='file'){$html.=$this->fileLinks($this->repo->filesForField($id,$name));}
                else{$html.=$this->presenter->displayField($field,$data[$name]??'',$data);} $html.='</td></tr>';
            }}
        return $html.'</tbody></table>';
    }

    private function fileLinks(array $files): string
    {
        if(!$files)return '—';$links=[];foreach($files as $file){$name=(string)($file['original_name']??'فایل');$url='';$attachment=(int)($file['attachment_id']??0);if($attachment>0){$resolved=wp_get_attachment_url($attachment);if(is_string($resolved))$url=$resolved;}if($url===''&&!empty($file['url']))$url=(string)$file['url'];if($url!==''&&wp_http_validate_url($url))$links[]='<a class="afe-export-file" href="'.esc_url($url).'">'.esc_html($name).'</a>';else$links[]=esc_html($name);}return implode('<br>',$links);
    }

    private function fieldByPath(array $form,string $path): ?array
    {
        $segments=explode('.',$path);foreach((array)($form['steps']??[]) as $step)foreach((array)($step['items']??[]) as $field){$found=$this->findField((array)$field,$segments);if($found)return $found;}return null;
    }
    private function findField(array $field,array $segments): ?array
    {
        if(($field['name']??'')!==($segments[0]??''))return null;if(count($segments)===1)return $field;if(($field['type']??'')!=='repeater')return null;$rest=array_slice($segments,1);foreach((array)($field['fields']??[]) as $child){$found=$this->findField((array)$child,$rest);if($found)return $found;}return null;
    }
    private function valueByPath(mixed $value,string $path): mixed{return $this->walk($value,explode('.',$path));}
    private function walk(mixed $value,array $segments): mixed{if($segments===[])return $value;if(!is_array($value))return null;$seg=(string)$segments[0];$rest=array_slice($segments,1);if(array_key_exists($seg,$value))return $this->walk($value[$seg],$rest);if(array_is_list($value)){$out=[];foreach($value as $row){$one=$this->walk($row,$segments);if($one!==null)$out[]=$one;}return $out;}return null;}
    private function escapeValue(string $value): string{return nl2br(esc_html($value));}
}

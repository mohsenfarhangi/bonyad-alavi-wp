<?php
declare(strict_types=1);

function sanitize_key(string $value): string { return strtolower(preg_replace('/[^a-z0-9_\-]/i','',$value)??''); }
function sanitize_text_field(string $value): string { return trim(strip_tags($value)); }
function wp_unslash(string $value): string { return stripslashes($value); }
function wp_kses(string $html,array $allowed): string {
    // Minimal test double: remove scripts and event-handler attributes while preserving template tokens.
    $html=preg_replace('#<script\b[^>]*>.*?</script>#is','',$html)??'';
    $html=preg_replace('/\son[a-z]+\s*=\s*(["\']).*?\1/is','',$html)??$html;
    return $html;
}

spl_autoload_register(static function(string $class): void {
    $prefix='BonyadAlavi\\FormEngine\\';
    if (!str_starts_with($class,$prefix)) return;
    $file=dirname(__DIR__).'/src/'.str_replace('\\','/',substr($class,strlen($prefix))).'.php';
    if (is_readable($file)) require_once $file;
});

use BonyadAlavi\FormEngine\Export\ExportConfigSanitizer;

$form=['steps'=>[['items'=>[
    ['name'=>'mobile','type'=>'tel','label'=>'موبایل'],
    ['name'=>'name','type'=>'text','label'=>'نام'],
]]]];
$input=[
 'pdf'=>[
   'page_size'=>'letter','orientation'=>'landscape',
   'header_html'=>'<div onclick="evil()">{{form_title}}<script>alert(1)</script></div>',
   'body_html'=>'{{fields_table}}','footer_html'=>'{{tracking_code}}',
   'css'=>'@import url(https://evil.test/x.css); body{direction:rtl;background:url(javascript:evil)} .x{color:#111}',
 ],
 'excel'=>[
   'sheet_name'=>'گزارش:/?*[] بسیار طولانی ثبت‌های فرم برای تست محدودیت نام شیت',
   'rtl'=>'1','freeze_header'=>'1','auto_filter'=>'1','header_bold'=>'1',
   'header_fill'=>'#AABBCC','header_text'=>'zz112233',
   'columns'=>[
     ['field'=>'mobile','label'=>'شماره همراه','enabled'=>'1','width'=>'999'],
     ['field'=>'unknown','label'=>'ناشناخته','enabled'=>'1','width'=>'20'],
   ],
 ],
];
$out=(new ExportConfigSanitizer())->sanitize($input,$form);
$checks=[
 'page-size'=>($out['pdf']['page_size']??'')==='LETTER',
 'orientation'=>($out['pdf']['orientation']??'')==='landscape',
 'script-removed'=>!str_contains((string)$out['pdf']['header_html'],'<script'),
 'css-import-removed'=>!str_contains(strtolower((string)$out['pdf']['css']),'@import'),
 'css-url-removed'=>!str_contains(strtolower((string)$out['pdf']['css']),'url('),
 'sheet-limit'=>(function_exists('mb_strlen')?mb_strlen((string)$out['excel']['sheet_name']):strlen((string)$out['excel']['sheet_name']))<=31,
 'unknown-column-removed'=>count((array)$out['excel']['columns'])===1 && $out['excel']['columns'][0]['field']==='mobile',
 'width-clamped'=>$out['excel']['columns'][0]['width']===80,
 'fill-sanitized'=>($out['excel']['header_fill']??'')==='AABBCC',
 'text-color-sanitized'=>($out['excel']['header_text']??'')==='112233',
];
foreach($checks as $name=>$ok){echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL;if(!$ok)exit(1);}
echo "export sanitizer ok\n";

<?php
declare(strict_types=1);
namespace BonyadAlavi\FormEngine\Export;
final class ExportPackageStatus
{
    public static function excelReady(): bool{
        $base=defined('AFE_PATH')?rtrim((string)AFE_PATH,'/\\'):rtrim(dirname(__DIR__,2),'/\\');
        return is_file($base.'/vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Spreadsheet.php')
            && is_file($base.'/vendor/phpoffice/phpspreadsheet/src/PhpSpreadsheet/Writer/Xlsx.php')
            && is_file($base.'/vendor/maennchen/zipstream-php/src/ZipStream.php');
    }
    public static function pdfReady(): bool{return class_exists('\\Com\\Tecnick\\Pdf\\Tcpdf');}
    public static function pdfFontReady(): bool{return self::pdfFontPath()!==null;}
    public static function pdfFontPath(): ?string{
        if(defined('K_PATH_FONTS')&&is_string(K_PATH_FONTS)){
            $path=rtrim(K_PATH_FONTS,'/\\');if(is_file($path.'/dejavusans.json'))return $path;
        }
        $base=defined('AFE_PATH')?AFE_PATH:dirname(__DIR__,2).'/';
        $root=rtrim($base,'/\\').'/vendor/tecnickcom/tc-lib-pdf-font/target/fonts';
        foreach([$root.'/dejavu',$root] as $path){if(is_file($path.'/dejavusans.json'))return $path;}
        return null;
    }
    public static function excelMissingExtensions(): array{$needed=['ctype','dom','fileinfo','filter','gd','iconv','libxml','mbstring','simplexml','xml','xmlreader','xmlwriter','zip','zlib'];return array_values(array_filter($needed,static fn(string $ext):bool=>!extension_loaded($ext)));}
    public static function pdfMissingExtensions(): array{$needed=['ctype','filter','hash','json','mbstring','openssl','pcre','xml','zlib'];return array_values(array_filter($needed,static fn(string $ext):bool=>!extension_loaded($ext)));}
}

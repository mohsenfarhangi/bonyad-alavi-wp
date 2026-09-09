<?php
declare(strict_types=1);
namespace BonyadAlavi\FormEngine\Export;

use RuntimeException;

final class BinaryDownload
{
    public static function streamFile(string $path,string $mime,string $filename,bool $deleteAfter=false): never
    {
        if(!is_file($path)||!is_readable($path))throw new RuntimeException('فایل خروجی ساخته شد اما برای ارسال قابل خواندن نیست.');
        $size=filesize($path);if($size===false||$size<1)throw new RuntimeException('فایل خروجی خالی است و قابل دانلود نیست.');
        if(headers_sent($source,$line))throw new RuntimeException('ارسال فایل خروجی ممکن نیست؛ Headerهای HTTP قبلاً در '.$source.':'.$line.' ارسال شده‌اند.');
        self::cleanOutputBuffers();
        @ini_set('zlib.output_compression','0');
        nocache_headers();
        header('Content-Type: '.$mime);
        header('Content-Disposition: attachment; filename="'.sanitize_file_name($filename).'"');
        header('Content-Length: '.(string)$size);
        header('Content-Transfer-Encoding: binary');
        header('X-Content-Type-Options: nosniff');
        $handle=fopen($path,'rb');
        if($handle===false)throw new RuntimeException('باز کردن فایل خروجی برای دانلود ناموفق بود.');
        try{fpassthru($handle);}finally{fclose($handle);if($deleteAfter)@unlink($path);}exit;
    }

    public static function streamBytes(string $bytes,string $mime,string $filename): never
    {
        if($bytes==='')throw new RuntimeException('فایل خروجی خالی است و قابل دانلود نیست.');
        if(headers_sent($source,$line))throw new RuntimeException('ارسال فایل خروجی ممکن نیست؛ Headerهای HTTP قبلاً در '.$source.':'.$line.' ارسال شده‌اند.');
        self::cleanOutputBuffers();
        @ini_set('zlib.output_compression','0');
        nocache_headers();
        header('Content-Type: '.$mime);
        header('Content-Disposition: attachment; filename="'.sanitize_file_name($filename).'"');
        header('Content-Length: '.strlen($bytes));
        header('Content-Transfer-Encoding: binary');
        header('X-Content-Type-Options: nosniff');
        echo $bytes;exit;
    }

    private static function cleanOutputBuffers(): void
    {
        while(ob_get_level()>0){if(!@ob_end_clean())break;}
    }
}

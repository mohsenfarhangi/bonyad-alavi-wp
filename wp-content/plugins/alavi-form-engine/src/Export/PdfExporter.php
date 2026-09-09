<?php
declare(strict_types=1);
namespace BonyadAlavi\FormEngine\Export;
use RuntimeException;
final class PdfExporter
{
    public function bytes(string $html,array $profile): string
    {
        if(!ExportPackageStatus::pdfReady())throw new RuntimeException('پکیج PDF در دسترس نیست. وابستگی tecnickcom/tc-lib-pdf باید داخل vendor افزونه نصب و باندل شود.');
        $missing=ExportPackageStatus::pdfMissingExtensions();if($missing)throw new RuntimeException('Extensionهای لازم PDF روی PHP فعال نیستند: '.implode(', ',$missing));
        if(!ExportPackageStatus::pdfFontReady())throw new RuntimeException('فونت یونیکد PDF آماده نیست. از ریشه افزونه دستور bash tools/build-pdf-fonts.sh را اجرا کنید و سپس وجود vendor/tecnickcom/tc-lib-pdf-font/target/fonts/dejavu/dejavusans.json را بررسی کنید.');
        $fontPath=ExportPackageStatus::pdfFontPath();
        if(!defined('K_PATH_FONTS')&&is_string($fontPath)&&$fontPath!==''&&is_dir($fontPath))define('K_PATH_FONTS',realpath($fontPath));
        $pdf=new \Com\Tecnick\Pdf\Tcpdf(isunicode:true,subsetfont:true,compress:true);
        $pdf->setCreator('Alavi Form Engine');$pdf->setLanguage(code:'fa-IR');
        $font=$pdf->font->insert($pdf->pon,'dejavusans','',10);
        $orientation=(string)($profile['orientation']??'portrait');$format=(string)($profile['page_size']??'A4');
        $page=$pdf->addPage(['format'=>$format,'orientation'=>$orientation==='landscape'?'L':'P']);$pdf->page->addContent($font['out']);
        $pageWidth=(float)($page['width']??210.0);
        $pdf->addHTMLCell(html:$html,posx:12,posy:12,width:max(20.0,$pageWidth-24.0));
        return $pdf->getOutPDFString();
    }
}

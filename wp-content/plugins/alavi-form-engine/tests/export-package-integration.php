<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$composer=json_decode((string)file_get_contents($root.'/composer.json'),true);
$submissions=(string)file_get_contents($root.'/src/Admin/SubmissionsPage.php');
$pdfAction=(string)file_get_contents($root.'/src/Actions/Pdf/PdfGenerator.php');
$forms=(string)file_get_contents($root.'/src/Admin/FormsPage.php');
$excel=(string)file_get_contents($root.'/src/Export/ExcelExporter.php');
$pdf=(string)file_get_contents($root.'/src/Export/PdfExporter.php');
$binary=(string)file_get_contents($root.'/src/Export/BinaryDownload.php');
$buildFonts=(string)file_get_contents($root.'/tools/build-pdf-fonts.sh');
$composerRaw=(string)file_get_contents($root.'/composer.json');
$jihadi=(string)file_get_contents($root.'/src/Forms/JihadiGroupRegistrationForm.php');
$checks=[
 'phpspreadsheet-required'=>isset($composer['require']['phpoffice/phpspreadsheet']),
 'zipstream-v3-pinned'=>(($composer['require']['maennchen/zipstream-php']??'')==='3.2.2'),
 'tclib-pdf-required'=>isset($composer['require']['tecnickcom/tc-lib-pdf']),
 'no-dom-pdf'=>!str_contains($submissions,'Dompdf\\Dompdf')&&!str_contains($pdfAction,'Dompdf\\Dompdf'),
 'no-manual-spreadsheetml'=>!str_contains($submissions,'SpreadsheetML')&&!str_contains($submissions,'application/vnd.ms-excel'),
 'xlsx-writer'=>str_contains($excel,'PhpOffice\\PhpSpreadsheet\\Writer\\Xlsx'),
 'xlsx-autoload-scope'=>str_contains($excel,'ExportAutoloadScope::run')&&str_contains($excel,'assertOwnExcelRuntime'),
 'xlsx-explicit-string'=>str_contains($excel,'DataType::TYPE_STRING'),
 'tc-lib-pdf'=>str_contains($pdf,'Com\\Tecnick\\Pdf\\Tcpdf'),
 'pdf-font-explicit-builder'=>str_contains($buildFonts,'make -C')&&str_contains($buildFonts,'target/fonts/dejavu/dejavusans.json'),
 'composer-font-build-not-silent'=>str_contains($composerRaw,'tools/build-pdf-fonts.sh')&&!str_contains($composerRaw,'|| true'),
 'xlsx-temp-before-stream'=>str_contains($excel,'$writer->save($tmp)')&&!str_contains($excel,"save('php://output')"),
 'binary-download-length'=>str_contains($binary,"Content-Length")&&str_contains($binary,"fpassthru"),
 'pdf-rtl-font'=>str_contains($pdf,"'dejavusans'"),
 'admin-pdf-template'=>str_contains($forms,'export_profile[pdf][body_html]'),
 'admin-excel-structure'=>str_contains($forms,'export_profile[excel][columns]'),
 'jihadi-output-profile'=>str_contains($jihadi,'->exports(['),
];
foreach($checks as $name=>$ok){echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL;if(!$ok)exit(1);}
echo "export package integration ok\n";

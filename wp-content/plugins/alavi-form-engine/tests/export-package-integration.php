<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$composer=json_decode((string)file_get_contents($root.'/composer.json'),true);
$submissions=(string)file_get_contents($root.'/src/Admin/SubmissionsPage.php');
$pdfAction=(string)file_get_contents($root.'/src/Actions/Pdf/PdfGenerator.php');
$forms=(string)file_get_contents($root.'/src/Admin/FormsPage.php');
$excel=(string)file_get_contents($root.'/src/Export/ExcelExporter.php');
$pdf=(string)file_get_contents($root.'/src/Export/PdfExporter.php');
$jihadi=(string)file_get_contents($root.'/src/Forms/JihadiGroupRegistrationForm.php');
$checks=[
 'phpspreadsheet-required'=>isset($composer['require']['phpoffice/phpspreadsheet']),
 'tclib-pdf-required'=>isset($composer['require']['tecnickcom/tc-lib-pdf']),
 'no-dom-pdf'=>!str_contains($submissions,'Dompdf\\Dompdf')&&!str_contains($pdfAction,'Dompdf\\Dompdf'),
 'no-manual-spreadsheetml'=>!str_contains($submissions,'SpreadsheetML')&&!str_contains($submissions,'application/vnd.ms-excel'),
 'xlsx-writer'=>str_contains($excel,'PhpOffice\\PhpSpreadsheet\\Writer\\Xlsx'),
 'xlsx-explicit-string'=>str_contains($excel,'DataType::TYPE_STRING'),
 'tc-lib-pdf'=>str_contains($pdf,'Com\\Tecnick\\Pdf\\Tcpdf'),
 'pdf-rtl-font'=>str_contains($pdf,"'dejavusans'"),
 'admin-pdf-template'=>str_contains($forms,'export_profile[pdf][body_html]'),
 'admin-excel-structure'=>str_contains($forms,'export_profile[excel][columns]'),
 'jihadi-output-profile'=>str_contains($jihadi,'->exports(['),
];
foreach($checks as $name=>$ok){echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL;if(!$ok)exit(1);}
echo "export package integration ok\n";

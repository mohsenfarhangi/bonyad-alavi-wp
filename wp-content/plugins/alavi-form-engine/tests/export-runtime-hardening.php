<?php
declare(strict_types=1);
$root=dirname(__DIR__);
$excel=(string)file_get_contents($root.'/src/Export/ExcelExporter.php');
$submissions=(string)file_get_contents($root.'/src/Admin/SubmissionsPage.php');
$pdf=(string)file_get_contents($root.'/src/Export/PdfExporter.php');
$build=(string)file_get_contents($root.'/tools/build-pdf-fonts.sh');
$composer=(string)file_get_contents($root.'/composer.json');
$checks=[
 'excel-does-not-stream-directly'=>!str_contains($excel,"save('php://output')"),
 'excel-writes-temp-first'=>str_contains($excel,'$writer->save($tmp)')&&str_contains($excel,'$head!==\'PK\''),
 'excel-wraps-throwable'=>str_contains($excel,'catch(\\Throwable $e)'),
 'submission-export-catches-throwable'=>str_contains($submissions,'catch (Throwable $e)'),
 'pdf-shared-binary-response'=>str_contains($submissions,'BinaryDownload::streamBytes'),
 'pdf-actionable-font-error'=>str_contains($pdf,'tools/build-pdf-fonts.sh'),
 'font-script-verifies-json'=>str_contains($build,'target/fonts/dejavu/dejavusans.json')&&str_contains($build,'dejavusans.json')&&str_contains($build,'make -C "$FONT_ROOT" fonts'),
 'composer-font-failure-not-hidden'=>!str_contains($composer,'|| true')&&str_contains($composer,'tools/build-pdf-fonts.sh'),
];
foreach($checks as $name=>$ok){echo ($ok?'PASS ':'FAIL ').$name.PHP_EOL;if(!$ok)exit(1);}echo "export runtime hardening ok\n";

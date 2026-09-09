#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"
if ! command -v composer >/dev/null 2>&1; then
  echo "ERROR: Composer در دسترس نیست." >&2
  exit 1
fi
composer install --no-dev --prefer-dist --optimize-autoloader --no-scripts
bash tools/build-pdf-fonts.sh
composer dump-autoload --no-dev --optimize --no-scripts
php -r '
require "vendor/autoload.php";
$required=["PhpOffice\\PhpSpreadsheet\\Spreadsheet","PhpOffice\\PhpSpreadsheet\\Writer\\Xlsx","PhpOffice\\PhpSpreadsheet\\Writer\\ZipStream0","ZipStream\\ZipStream","ZipStream\\OperationMode","Com\\Tecnick\\Pdf\\Tcpdf"];
foreach($required as $c){if(!class_exists($c) && !enum_exists($c)){fwrite(STDERR,"Missing export package class: $c\n");exit(1);}}
if(class_exists("ZipStream\\Option\\Archive")){fwrite(STDERR,"Unexpected ZipStream v2 marker detected; AFE requires isolated ZipStream v3.2.2.\n");exit(1);}
$fonts=[__DIR__."/vendor/tecnickcom/tc-lib-pdf-font/target/fonts/dejavu/dejavusans.json",__DIR__."/vendor/tecnickcom/tc-lib-pdf-font/target/fonts/dejavusans.json"];
$font=null;foreach($fonts as $candidate){if(is_file($candidate)){ $font=$candidate; break; }}
if($font===null){fwrite(STDERR,"Missing tc-lib-pdf DejaVu font assets after build.\n");exit(1);}
echo "Export vendor packages ready\n";
'

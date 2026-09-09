#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"
if ! command -v composer >/dev/null 2>&1; then
  echo "ERROR: Composer در دسترس نیست." >&2
  exit 1
fi
composer install --no-dev --prefer-dist --optimize-autoloader
php -r '
require "vendor/autoload.php";
$required=["PhpOffice\\PhpSpreadsheet\\Spreadsheet","PhpOffice\\PhpSpreadsheet\\Writer\\Xlsx","Com\\Tecnick\\Pdf\\Tcpdf"];
foreach($required as $c){if(!class_exists($c)){fwrite(STDERR,"Missing export package class: $c\n");exit(1);}}
$font=__DIR__."/vendor/tecnickcom/tc-lib-pdf-font/target/fonts/dejavusans.json";
if(!is_file($font)){fwrite(STDERR,"Missing tc-lib-pdf DejaVu font assets. Follow tc-lib-pdf font generation instructions.\n");exit(1);}
echo "Export vendor packages ready\n";
'

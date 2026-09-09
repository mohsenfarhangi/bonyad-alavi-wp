#!/usr/bin/env bash
set -u
export TERM="${TERM:-dumb}"
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"
failed=0
QA_TMP_DIR="$ROOT/.afe-qa-tmp-$$"
mkdir -p "$QA_TMP_DIR"
trap 'rm -rf "$QA_TMP_DIR"' EXIT

pass(){ printf 'PASS %s\n' "$1"; }
fail(){ printf 'FAIL %s\n' "$1"; failed=1; }

if php -r 'exit(version_compare(PHP_VERSION,"8.3.0",">=")?0:1);'; then pass "PHP >= 8.3 ($(php -r 'echo PHP_VERSION;'))"; else fail "PHP >= 8.3"; fi

test_count=0
for test_file in tests/*.php; do
  test_count=$((test_count+1))
  if php "$test_file" >$QA_TMP_DIR/test.out 2>&1; then
    :
  else
    printf '\n--- %s ---\n' "$test_file"
    cat $QA_TMP_DIR/test.out
    fail "regression $test_file"
  fi
done
if [ "$failed" -eq 0 ]; then pass "$test_count regression tests"; fi

lint_count=0
while IFS= read -r -d '' php_file; do
  lint_count=$((lint_count+1))
  if ! php -l "$php_file" >$QA_TMP_DIR/lint.out 2>&1; then
    cat $QA_TMP_DIR/lint.out
    fail "PHP lint $php_file"
  fi
done < <(find src tests -type f -name '*.php' -print0)
for php_file in alavi-form-engine.php uninstall.php; do
  lint_count=$((lint_count+1))
  if ! php -l "$php_file" >$QA_TMP_DIR/lint.out 2>&1; then
    cat $QA_TMP_DIR/lint.out
    fail "PHP lint $php_file"
  fi
done
if [ "$failed" -eq 0 ]; then pass "$lint_count PHP files linted"; fi

if command -v node >/dev/null 2>&1; then
  if node --check assets/js/admin.js && node --check assets/js/frontend.js; then pass "JavaScript syntax"; else fail "JavaScript syntax"; fi
else
  printf 'SKIP JavaScript syntax (node not installed)\n'
fi

if php -r '$j=json_decode(file_get_contents("composer.json"),true); exit(is_array($j)?0:1);'; then pass "composer.json"; else fail "composer.json"; fi

if [ "${AFE_REQUIRE_EXPORT_VENDOR:-0}" = "1" ]; then
  if php -r '''require "vendor/autoload.php"; exit(class_exists("PhpOffice\\PhpSpreadsheet\\Spreadsheet") && class_exists("PhpOffice\\PhpSpreadsheet\\Writer\\Xlsx") && class_exists("Com\\Tecnick\\Pdf\\Tcpdf") ? 0 : 1);'''; then
    pass "export vendor package classes"
  else
    fail "export vendor package classes"
  fi
  if [ -f vendor/tecnickcom/tc-lib-pdf-font/target/fonts/dejavu/dejavusans.json ] || [ -f vendor/tecnickcom/tc-lib-pdf-font/target/fonts/dejavusans.json ]; then pass "tc-lib-pdf DejaVu font assets"; else fail "tc-lib-pdf DejaVu font assets"; fi
fi

if [ -s assets/vendor/jalalidatepicker/jalalidatepicker.min.js ] && [ -s assets/vendor/jalalidatepicker/jalalidatepicker.min.css ]; then
  pass "local JalaliDatePicker assets"
else
  fail "local JalaliDatePicker assets"
fi

if grep -RInE 'wp_(register|enqueue)_(script|style)\([^\n]*(https?:)?//' src alavi-form-engine.php >$QA_TMP_DIR/cdn.out 2>/dev/null; then
  cat $QA_TMP_DIR/cdn.out
  fail "runtime JS/CSS has no external CDN registration"
else
  pass "runtime JS/CSS has no external CDN registration"
fi

header_version="$(sed -n 's/^ \* Version: //p' alavi-form-engine.php | head -1)"
const_version="$(sed -n "s/^define('AFE_VERSION', '\([^']*\)').*/\1/p" alavi-form-engine.php | head -1)"
if [ -n "$header_version" ] && [ "$header_version" = "$const_version" ]; then pass "plugin header/version constant consistency ($const_version)"; else fail "plugin header/version constant consistency"; fi

printf '\nQA RESULT: '
if [ "$failed" -eq 0 ]; then printf 'PASS\n'; else printf 'FAIL\n'; fi
exit "$failed"

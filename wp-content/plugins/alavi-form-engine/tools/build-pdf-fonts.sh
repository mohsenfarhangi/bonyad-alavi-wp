#!/usr/bin/env bash
set -euo pipefail
ROOT="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
cd "$ROOT"
FONT_ROOT="$ROOT/vendor/tecnickcom/tc-lib-pdf-font"
FONT_JSON_PRIMARY="$FONT_ROOT/target/fonts/dejavu/dejavusans.json"
FONT_JSON_LEGACY="$FONT_ROOT/target/fonts/dejavusans.json"
FONT_JSON="$FONT_JSON_PRIMARY"
if [ -s "$FONT_JSON_LEGACY" ] && [ ! -s "$FONT_JSON_PRIMARY" ]; then
  FONT_JSON="$FONT_JSON_LEGACY"
fi

if [ -s "$FONT_JSON" ]; then
  echo "PDF Unicode font assets already ready: $FONT_JSON"
  exit 0
fi
if [ ! -f "$FONT_ROOT/Makefile" ]; then
  echo "ERROR: tc-lib-pdf-font is not installed under vendor. Run composer install first." >&2
  exit 1
fi
if ! command -v make >/dev/null 2>&1; then
  echo "ERROR: make is required to generate tc-lib-pdf Unicode font assets." >&2
  exit 1
fi
if ! command -v composer >/dev/null 2>&1; then
  echo "ERROR: Composer must be available in PATH while tc-lib-pdf-font builds its font utilities." >&2
  exit 1
fi

echo "Building tc-lib-pdf Unicode fonts..."
make -C "$FONT_ROOT" fonts

if [ -s "$FONT_JSON_PRIMARY" ]; then
  FONT_JSON="$FONT_JSON_PRIMARY"
elif [ -s "$FONT_JSON_LEGACY" ]; then
  FONT_JSON="$FONT_JSON_LEGACY"
else
  echo "ERROR: font build completed but DejaVu Sans metadata was not found." >&2
  echo "Checked: $FONT_JSON_PRIMARY" >&2
  echo "Checked legacy layout: $FONT_JSON_LEGACY" >&2
  echo "Generated font files:" >&2
  find "$FONT_ROOT/target/fonts" -maxdepth 3 -type f 2>/dev/null | sed -n '1,80p' >&2 || true
  exit 1
fi

echo "PDF Unicode font assets ready: $FONT_JSON"

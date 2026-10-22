/* Node regression guards for the public frontend stylesheet. */
const fs = require('node:fs');
const path = require('node:path');
const assert = require('node:assert/strict');

const root = path.join(__dirname, '..');
const css = fs.readFileSync(path.join(root, 'assets/css/frontend.css'), 'utf8');
const entrypoint = fs.readFileSync(path.join(root, 'alavi-form-engine.php'), 'utf8');

function rule(selector) {
  const index = css.indexOf(selector);
  assert.ok(index >= 0, 'Missing CSS selector: ' + selector);
  const opening = css.indexOf('{', index);
  const ending = css.indexOf('}', opening);
  assert.ok(opening > index && ending > opening, 'Malformed CSS rule: ' + selector);
  return { index, declarations: css.slice(opening + 1, ending) };
}

const honeypot = rule('.afe-form .afe-hp {');
for (const declaration of [
  'position: absolute !important', 'top: 0 !important',
  'right: 0 !important', 'left: auto !important',
  'width: 1px !important', 'height: 1px !important',
  'clip-path: inset(50%) !important'
]) {
  assert.ok(honeypot.declarations.includes(declaration), 'Honeypot lacks ' + declaration);
}
assert.ok(!css.includes('left:-10000px') && !css.includes('left: -10000px'),
  'Old RTL overflow honeypot still present');
assert.equal(css.split('.afe-form .afe-hp {').length - 1, 1, 'Duplicate honeypot rule');

const strong = rule('.afe-shell.afe-isolation-strong .afe-form .afe-control {');
const select = rule('.afe-form select.afe-select-source.afe-select-enhanced,');
assert.ok(select.index > strong.index, 'Enhanced-select selector must follow strong isolation');
assert.equal(css.split('.afe-form select.afe-select-source.afe-select-enhanced,').length - 1, 1,
  'Duplicate enhanced-select selector');
assert.ok(css.includes('.afe-form select.afe-control.afe-select-source.afe-select-enhanced'),
  'Missing strong isolation override');
for (const declaration of [
  'width: 1px !important', 'height: 1px !important',
  'min-width: 0 !important', 'max-width: 1px !important',
  'min-height: 0 !important', 'max-height: 1px !important',
  'clip-path: inset(50%) !important', 'opacity: 0 !important', 'pointer-events: none !important'
]) {
  assert.ok(select.declarations.includes(declaration), 'Native select lacks ' + declaration);
}
const headerVersion = /^ \* Version: ([^\s]+)$/m.exec(entrypoint)?.[1];
const constantVersion = /define\('AFE_VERSION', '([^']+)'\);/.exec(entrypoint)?.[1];
assert.ok(headerVersion, 'Missing plugin header version');
assert.ok(constantVersion, 'Missing AFE_VERSION constant');
assert.equal(headerVersion, constantVersion, 'Plugin header and asset version must agree');
console.log('PASS frontend CSS cascade, overflow, and version contract');

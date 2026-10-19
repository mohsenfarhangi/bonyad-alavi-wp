import assert from 'node:assert/strict';
import { chromium } from 'playwright';
import { pathToFileURL } from 'node:url';
import { resolve, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const root = dirname(fileURLToPath(import.meta.url));
const fixture = pathToFileURL(resolve(root, 'frontend-css-browser-fixture.html')).href;
const browser = await chromium.launch({ headless: true, args: ['--allow-file-access-from-files'] });

try {
  for (const width of [360, 780, 782, 1024]) {
    const page = await browser.newPage({ viewport: { width, height: 900 }, deviceScaleFactor: 1 });
    const errors = [];
    page.on('pageerror', error => errors.push(error.message));
    await page.goto(fixture, { waitUntil: 'load' });
    await page.locator('select.afe-select-enhanced').waitFor({ state: 'attached' });
    await page.locator('.afe-custom-select__trigger').waitFor({ state: 'visible' });

    // AFE controls animate on enhancement; inspect the final cascade only after
    // width, height, and opacity transitions have settled.
    await page.waitForFunction(() => {
      const select = document.querySelector('select.afe-select-enhanced');
      if (!select) return false;
      const rect = select.getBoundingClientRect();
      return rect.width <= 1.1 && rect.height <= 1.1 && getComputedStyle(select).opacity === '0';
    }, { timeout: 5000 });

    const bounds = await page.evaluate(() => {
      const form = document.querySelector('.afe-form');
      const hidden = document.querySelector('select.afe-select-enhanced');
      const honeypot = document.querySelector('.afe-hp');
      const native = hidden.getBoundingClientRect();
      const honey = honeypot.getBoundingClientRect();
      const formBounds = form.getBoundingClientRect();
      return {
        viewport: window.innerWidth,
        docWidth: document.documentElement.scrollWidth,
        hiddenWidth: native.width,
        hiddenHeight: native.height,
        honeyWidth: honey.width,
        honeyHeight: honey.height,
        honeyRight: honey.right,
        formRight: formBounds.right,
        opacity: getComputedStyle(hidden).opacity,
        pointerEvents: getComputedStyle(hidden).pointerEvents,
        nativeName: hidden.name,
        honeypotPosition: getComputedStyle(honeypot).position,
        nativeInForm: form.contains(hidden),
      };
    });

    assert.equal(bounds.viewport, width, 'Viewport width');
    assert.ok(bounds.docWidth <= width + 1, 'RTL horizontal overflow at ' + width + ': ' + JSON.stringify(bounds));
    assert.ok(bounds.hiddenWidth <= 1.1 && bounds.hiddenHeight <= 1.1,
      'Enhanced select escaped 1px dimensions at ' + width + ': ' + JSON.stringify(bounds));
    assert.equal(bounds.opacity, '0', 'Enhanced select opacity');
    assert.equal(bounds.pointerEvents, 'none', 'Enhanced select pointer events');
    assert.ok(bounds.honeyWidth <= 1.1 && bounds.honeyHeight <= 1.1,
      'Honeypot not visually clipped at ' + width);
    assert.ok(bounds.honeyRight <= bounds.formRight + 1,
      'Honeypot outside form at ' + width);
    assert.equal(bounds.honeypotPosition, 'absolute');
    assert.equal(bounds.nativeName, 'afe_data[province]');
    assert.ok(bounds.nativeInForm, 'Native select missing from form');

    await page.locator('.afe-custom-select__trigger').click();
    await page.locator('.afe-custom-select__option[data-option-value="tehran"]').click();
    const value = await page.locator('select[name="afe_data[province]"]').inputValue();
    assert.equal(value, 'tehran', 'Native select value after custom UI selection');

    await page.locator('button.afe-submit').click();
    await page.waitForFunction(() => window.__submission !== null);
    const submission = await page.evaluate(() => window.__submission);
    assert.deepEqual(submission, { intent: 'submit', province: 'tehran', action: 'afe_submit' },
      'Mocked submission did not serialize selected option at width ' + width);
    assert.deepEqual(errors, [], 'Browser errors at width ' + width);
    console.log('PASS ' + width + 'px: no overflow, 1px native source, option selection and simulated submit');
    await page.close();
  }
} finally {
  await browser.close();
}

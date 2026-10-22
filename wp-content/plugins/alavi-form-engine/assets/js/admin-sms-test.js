(() => {
  'use strict';
  document.addEventListener('DOMContentLoaded', () => {
    const box = document.querySelector('[data-afe-sms-test]');
    if (!box || !window.afeSmsTest) return;
    const button = box.querySelector('[data-afe-sms-test-button]');
    const mobile = box.querySelector('[data-afe-sms-mobile]');
    const provider = box.querySelector('[data-afe-sms-provider]');
    const result = box.querySelector('[data-afe-sms-test-result]');
    if (!button || !mobile || !provider || !result) return;
    button.addEventListener('click', async () => {
      const number = String(mobile.value || '').trim();
      if (!/^09[0-9]{9}$/.test(number)) {
        result.textContent = 'شماره همراه را به صورت ۱۱ رقم و با ۰۹ وارد کنید.';
        return;
      }
      button.disabled = true;
      result.textContent = 'در حال ارسال پیامک آزمایشی...';
      const body = new URLSearchParams({
        action:'afe_sms_test',
        nonce:window.afeSmsTest.nonce,
        mobile:number,
        provider:provider.value,
      });
      try {
        const response = await fetch(window.afeSmsTest.url, {
          method:'POST', body, credentials:'same-origin', cache:'no-store',
        });
        const payload = await response.json();
        result.textContent = String(payload?.data?.message || 'خطا در بررسی اتصال پیامکی.');
      } catch (_) {
        result.textContent = 'ارسال درخواست آزمایشی ممکن نشد.';
      } finally {
        button.disabled = false;
      }
    });
  });
})();

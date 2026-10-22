(() => {
  'use strict';

  const one = (root, selector) => root?.querySelector(selector);

  document.addEventListener('DOMContentLoaded', () => {
    const form = document.querySelector('[data-afe-settings-form]');
    if (!form) return;

    // Progressive enhancement: the full form remains visible without JS.
    const nav = one(form, '[data-afe-settings-tabs]');
    const tabs = [...(nav?.querySelectorAll('[data-afe-settings-tab]') || [])];
    const panels = [...form.querySelectorAll('[data-afe-settings-panel]')];
    const selected = one(form, '[data-afe-current-tab]');
    const valid = new Set(tabs.map(tab => tab.dataset.afeSettingsTab));
    const activate = (key, focus = false) => {
      const active = valid.has(key) ? key : 'general';
      for (const tab of tabs) {
        const isCurrent = tab.dataset.afeSettingsTab === active;
        tab.classList.toggle('is-active', isCurrent);
        tab.setAttribute('aria-selected', isCurrent ? 'true' : 'false');
        tab.tabIndex = isCurrent ? 0 : -1;
        if (isCurrent && focus) tab.focus();
      }
      for (const panel of panels) panel.hidden = panel.dataset.afeSettingsPanel !== active;
      if (selected) selected.value = active;
    };
    if (tabs.length && panels.length) {
      activate(selected?.value || 'general');
      tabs.forEach((tab, index) => {
        tab.addEventListener('click', () => activate(tab.dataset.afeSettingsTab));
        tab.addEventListener('keydown', event => {
          if (!['ArrowRight','ArrowLeft','Home','End'].includes(event.key)) return;
          event.preventDefault();
          const i = event.key === 'Home' ? 0 : event.key === 'End' ? tabs.length - 1
            : (index + (event.key === 'ArrowLeft' ? 1 : -1) + tabs.length) % tabs.length;
          activate(tabs[i].dataset.afeSettingsTab, true);
        });
      });
    }

    const settings = window.afeSmsTest;
    if (!settings?.url) return;
    const send = async fields => {
      const body = new URLSearchParams(fields);
      const response = await fetch(settings.url, {
        method:'POST', body, credentials:'same-origin', cache:'no-store'
      });
      const json = await response.json();
      return {ok:response.ok && Boolean(json?.success), data:json?.data || {}};
    };
    const allowedDebugKeys = ['at','channel','provider','status','stage','http_status','ret_status','value_code','message_id'];
    const debugText = debug => {
      if (!debug || typeof debug !== 'object') return '';
      return allowedDebugKeys.filter(key => debug[key] !== undefined)
        .map(key => key + ': ' + String(debug[key])).join('\n');
    };

    const test = one(form, '[data-afe-sms-test]');
    const button = one(test, '[data-afe-sms-test-button]');
    const mobile = one(test, '[data-afe-sms-mobile]');
    const provider = one(test, '[data-afe-sms-provider]');
    const result = one(test, '[data-afe-sms-test-result]');
    const diagnostics = one(test, '[data-afe-sms-test-diagnostics]');
    button?.addEventListener('click', async () => {
      const number = String(mobile?.value || '').trim();
      if (!/^09[0-9]{9}$/.test(number)) {
        if (result) result.textContent = 'شماره همراه را به صورت ۱۱ رقم و با ۰۹ وارد کنید.';
        return;
      }
      button.disabled = true;
      if (result) result.textContent = 'در حال ارسال پیامک آزمایشی...';
      if (diagnostics) diagnostics.hidden = true;
      try {
        const reply = await send({
          action:'afe_sms_test', nonce:settings.nonce, mobile:number, provider:provider?.value || ''
        });
        if (result) result.textContent = String(reply.data.message || 'خطا در بررسی اتصال پیامکی.');
        if (diagnostics) {
          const summary = debugText(reply.data.debug);
          diagnostics.textContent = summary;
          diagnostics.hidden = summary === '';
        }
      } catch (_) {
        if (result) result.textContent = 'ارسال درخواست آزمایشی ممکن نشد.';
      } finally {
        button.disabled = false;
      }
    });

    const refresh = one(form, '[data-afe-sms-debug-refresh]');
    const clear = one(form, '[data-afe-sms-debug-clear]');
    const message = one(form, '[data-afe-sms-debug-message]');
    const rows = one(form, '[data-afe-sms-debug-rows]');
    const columns = ['at','channel','provider','status','stage','http_status','ret_status','value_code','message_id'];
    const fetchLog = async operation => {
      if (!rows || !settings.debugNonce) return;
      if (operation === 'clear' && !window.confirm('گزارش‌های دیباگ پیامک پاک شوند؟')) return;
      if (refresh) refresh.disabled = true;
      if (clear) clear.disabled = true;
      if (message) message.textContent = 'در حال پردازش...';
      try {
        const reply = await send({
          action:'afe_sms_debug', nonce:settings.debugNonce, operation
        });
        if (!reply.ok || !Array.isArray(reply.data.events)) {
          if (message) message.textContent = String(reply.data.message || 'دریافت گزارش انجام نشد.');
          return;
        }
        rows.replaceChildren();
        for (const entry of reply.data.events) {
          const tr = document.createElement('tr');
          for (const key of columns) {
            const td = document.createElement('td');
            td.textContent = entry[key] === undefined ? '—' : String(entry[key]);
            tr.appendChild(td);
          }
          rows.appendChild(tr);
        }
        if (message) message.textContent = operation === 'clear'
          ? 'گزارش‌ها پاک شدند.' : reply.data.events.length + ' مورد نمایش داده شد.';
      } catch (_) {
        if (message) message.textContent = 'ارتباط با سامانه گزارش‌گیری برقرار نشد.';
      } finally {
        if (refresh) refresh.disabled = false;
        if (clear) clear.disabled = false;
      }
    };
    refresh?.addEventListener('click', () => fetchLog('read'));
    clear?.addEventListener('click', () => fetchLog('clear'));
  });
})();

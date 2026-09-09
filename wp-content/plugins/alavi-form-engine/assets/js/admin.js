(() => {
  'use strict';

  const qs = (root, selector) => root.querySelector(selector);

  const faNumber = value => new Intl.NumberFormat('fa-IR').format(value || 0);

  const formatBytes = bytes => {
    if (!Number.isFinite(bytes) || bytes <= 0) return '۰ بایت';
    const units = ['B', 'KB', 'MB', 'GB'];
    const index = Math.min(Math.floor(Math.log(bytes) / Math.log(1024)), units.length - 1);
    const value = bytes / Math.pow(1024, index);
    return `${new Intl.NumberFormat('fa-IR', { maximumFractionDigits: index ? 1 : 0 }).format(value)} ${units[index]}`;
  };

  const uuid = () => {
    if (window.crypto?.randomUUID) return window.crypto.randomUUID().replace(/-/g, '_');
    return `afe_${Date.now()}_${Math.random().toString(36).slice(2, 12)}`;
  };

  const extractError = xhr => {
    try {
      const response = JSON.parse(xhr.responseText || '{}');
      return response?.data?.message || response?.data || response?.message || `HTTP ${xhr.status}`;
    } catch (error) {
      return xhr.responseText || `HTTP ${xhr.status}`;
    }
  };

  class GeoChunkUploader {
    constructor(root) {
      this.root = root;
      this.cards = [...root.querySelectorAll('.afe-geo-upload-card')];
      this.chunkSize = Math.max(8192, Number(window.afeAdmin?.geoChunkSize || 32768));
      this.uploadAllButton = qs(root, '[data-action="upload-all"]');
      this.bind();
    }

    bind() {
      this.cards.forEach(card => {
        const input = qs(card, '[data-role="file"]');
        const upload = qs(card, '[data-action="upload"]');
        const retry = qs(card, '[data-action="retry"]');

        input?.addEventListener('change', () => this.onFileSelected(card));
        upload?.addEventListener('click', () => this.start(card, true).catch(() => {}));
        retry?.addEventListener('click', () => this.start(card, false).catch(() => {}));
      });

      this.uploadAllButton?.addEventListener('click', async () => {
        const missing = this.cards.filter(card => !qs(card, '[data-role="file"]')?.files?.[0]);
        if (missing.length) {
          missing.forEach(card => this.setError(card, 'ابتدا فایل این بخش را انتخاب کنید.'));
          return;
        }

        this.uploadAllButton.disabled = true;
        this.uploadAllButton.classList.add('is-processing');
        try {
          for (const card of this.cards) {
            await this.start(card, true);
          }
        } catch (error) {
          // The failing card already displays the actionable error.
        } finally {
          this.uploadAllButton.disabled = false;
          this.uploadAllButton.classList.remove('is-processing');
        }
      });
    }

    onFileSelected(card) {
      const input = qs(card, '[data-role="file"]');
      const file = input?.files?.[0];
      const upload = qs(card, '[data-action="upload"]');
      const retry = qs(card, '[data-action="retry"]');
      card._afeGeoState = null;
      retry.hidden = true;

      if (!file) {
        upload.disabled = true;
        qs(card, '[data-role="file-info"]').textContent = 'هنوز فایلی انتخاب نشده است.';
        this.setState(card, 'idle', 'آماده انتخاب فایل');
        this.setProgress(card, 0, 0, 0, 0);
        return;
      }

      if (!/\.json$/i.test(file.name)) {
        upload.disabled = true;
        this.setError(card, 'فایل انتخابی باید پسوند JSON داشته باشد.');
        return;
      }

      upload.disabled = false;
      qs(card, '[data-role="file-info"]').textContent = `${file.name} — ${formatBytes(file.size)} — ${faNumber(Math.max(1, Math.ceil(file.size / this.chunkSize)))} chunk`;
      this.setState(card, 'ready', 'آماده ارسال');
      this.setMessage(card, 'فایل آماده است. با شروع ارسال، هر chunk مستقیماً در دیتابیس ثبت می‌شود.');
      this.setProgress(card, 0, 0, Math.max(1, Math.ceil(file.size / this.chunkSize)), 0);
    }

    async start(card, fresh) {
      const input = qs(card, '[data-role="file"]');
      const file = input?.files?.[0];
      if (!file) {
        this.setError(card, 'ابتدا فایل JSON را انتخاب کنید.');
        throw new Error('file_missing');
      }

      if (card._afeGeoState?.active) return;

      let state = card._afeGeoState;
      if (fresh || !state || state.file !== file) {
        state = {
          file,
          uploadId: uuid(),
          nextIndex: 0,
          totalChunks: Math.max(1, Math.ceil(file.size / this.chunkSize)),
          rows: 0,
          active: false,
          completed: false,
        };
        card._afeGeoState = state;
      }

      state.active = true;
      state.completed = false;
      input.disabled = true;
      qs(card, '[data-action="upload"]').disabled = true;
      qs(card, '[data-action="retry"]').hidden = true;
      card.classList.add('is-processing');
      card.classList.remove('has-error', 'is-complete');

      try {
        while (state.nextIndex < state.totalChunks) {
          await this.sendChunk(card, state, state.nextIndex);
        }
        state.completed = true;
        this.setState(card, 'success', 'تکمیل شد');
        this.setMessage(card, `ورود ${faNumber(state.rows)} رکورد با موفقیت تمام شد.`);
        card.classList.add('is-complete');
        return state;
      } catch (error) {
        this.setError(card, error?.message || 'ارسال فایل ناموفق بود.');
        qs(card, '[data-action="retry"]').hidden = false;
        throw error;
      } finally {
        state.active = false;
        input.disabled = false;
        card.classList.remove('is-processing');
        qs(card, '[data-action="upload"]').disabled = false;
      }
    }

    sendChunk(card, state, index) {
      return new Promise((resolve, reject) => {
        const start = index * this.chunkSize;
        const end = Math.min(state.file.size, start + this.chunkSize);
        const blob = state.file.slice(start, end);
        const dataset = card.dataset.dataset;
        const form = new FormData();
        form.append('action', 'afe_geo_import_chunk');
        form.append('nonce', window.afeAdmin?.geoChunkNonce || '');
        form.append('dataset', dataset);
        form.append('upload_id', state.uploadId);
        form.append('chunk_index', String(index));
        form.append('total_chunks', String(state.totalChunks));
        form.append('total_size', String(state.file.size));
        form.append('chunk', blob, `${dataset}.part.${index}`);

        const xhr = new XMLHttpRequest();
        xhr.open('POST', window.afeAdmin?.ajaxUrl || window.ajaxurl, true);
        xhr.timeout = 45000;

        this.setState(card, 'uploading', `ارسال chunk ${faNumber(index + 1)} از ${faNumber(state.totalChunks)}`);
        this.setMessage(card, 'در حال ارسال و ثبت مستقیم رکوردهای کامل‌شده در دیتابیس…');

        xhr.upload.addEventListener('progress', event => {
          if (!event.lengthComputable) return;
          const sent = Math.min(state.file.size, start + event.loaded);
          this.setProgress(card, sent, state.file.size, state.totalChunks, state.rows, index + 1);
        });

        xhr.addEventListener('load', () => {
          let response = null;
          try { response = JSON.parse(xhr.responseText || '{}'); } catch (error) { response = null; }

          if (xhr.status < 200 || xhr.status >= 300 || !response?.success) {
            reject(new Error(response?.data?.message || extractError(xhr)));
            return;
          }

          const data = response.data || {};
          state.nextIndex = Number(data.next_chunk ?? (index + 1));
          state.rows = Number(data.db_count ?? data.rows_total ?? state.rows);
          if (data.completed) {
            const countNode = document.querySelector(`[data-geo-count="${dataset}"]`);
            if (countNode) countNode.textContent = faNumber(state.rows);
          }

          const sent = Math.min(state.file.size, end);
          this.setProgress(card, sent, state.file.size, state.totalChunks, state.rows, state.nextIndex);
          this.setState(card, data.completed ? 'processing' : 'stored', data.completed ? 'تأیید نهایی دیتابیس' : `chunk ${faNumber(index + 1)} ثبت شد`);
          this.setMessage(
            card,
            `${faNumber(Number(data.rows_in_chunk || 0))} رکورد از این chunk پردازش شد؛ مجموع جدول: ${faNumber(state.rows)} رکورد.`
          );
          resolve(data);
        });

        xhr.addEventListener('error', () => reject(new Error('ارتباط Ajax با سرور قطع شد. دوباره تلاش کنید.')));
        xhr.addEventListener('timeout', () => reject(new Error('پاسخ سرور طول کشید. دکمه «تلاش مجدد» همان chunk را دوباره بررسی می‌کند.')));
        xhr.send(form);
      });
    }

    setProgress(card, bytes, totalBytes, totalChunks, rows, completedChunks = 0) {
      const percent = totalBytes > 0 ? Math.min(100, Math.round((bytes / totalBytes) * 100)) : 0;
      const bar = qs(card, '[data-role="progress-bar"]');
      if (bar) bar.style.width = `${percent}%`;
      qs(card, '[data-role="progress-text"]').textContent = `${faNumber(percent)}٪ · ${faNumber(completedChunks)}/${faNumber(totalChunks)} chunk`;
      qs(card, '[data-role="rows-text"]').textContent = `${faNumber(rows)} رکورد ثبت شده`;
    }

    setState(card, type, text) {
      const state = qs(card, '[data-role="state"]');
      if (!state) return;
      state.className = `afe-geo-upload-state is-${type}`;
      state.textContent = text;
    }

    setMessage(card, text) {
      const message = qs(card, '[data-role="message"]');
      if (message) message.textContent = text || '';
    }

    setError(card, text) {
      card.classList.add('has-error');
      this.setState(card, 'error', 'خطا');
      this.setMessage(card, text);
    }
  }


  const adminLatinDigits = value => String(value ?? '')
    .replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d))
    .replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d));

  const adminMaskCache = new Map();
  const adminMaskNodes = pattern => {
    pattern = String(pattern || '').trim();
    if (adminMaskCache.has(pattern)) return adminMaskCache.get(pattern);
    const nodes = [];
    let escaped = false;
    Array.from(pattern).forEach(char => {
      if (escaped) { nodes.push({type:'literal', value:char}); escaped = false; return; }
      if (char === '\\') { escaped = true; return; }
      nodes.push(['9','A','*'].includes(char) ? {type:'slot', value:char} : {type:'literal', value:char});
    });
    if (escaped) nodes.length = 0;
    adminMaskCache.set(pattern, nodes);
    return nodes;
  };

  const adminMaskMatches = (char, token) => {
    if (token === '9') return /^[0-9]$/.test(char);
    try {
      if (token === 'A') return /^\p{L}$/u.test(char);
      if (token === '*') return /^(?:\p{L}|[0-9])$/u.test(char);
    } catch (_) {
      if (token === 'A') return /^[A-Za-z\u0621-\u06FC]$/.test(char);
      if (token === '*') return /^[A-Za-z0-9\u0621-\u06FC]$/.test(char);
    }
    return false;
  };

  const adminUnmaskValue = (value, pattern) => {
    const nodes = adminMaskNodes(pattern);
    if (!nodes.length) return adminLatinDigits(value);
    const chars = Array.from(adminLatinDigits(value));
    let index = 0;
    let out = '';
    nodes.forEach(node => {
      if (node.type === 'literal') {
        if (chars[index] === node.value) index += 1;
        return;
      }
      while (index < chars.length) {
        const char = chars[index++];
        if (adminMaskMatches(char, node.value)) { out += char; break; }
      }
    });
    return out;
  };

  const adminFormatMask = (value, pattern) => {
    const nodes = adminMaskNodes(pattern);
    if (!nodes.length) return value;
    const clean = Array.from(adminUnmaskValue(value, pattern));
    const total = nodes.filter(node => node.type === 'slot').length;
    let slot = 0;
    let out = '';
    for (const node of nodes) {
      if (node.type === 'slot') {
        if (clean[slot] === undefined) break;
        out += clean[slot++];
      } else if (slot > 0 && slot < total && clean[slot] !== undefined) {
        out += node.value;
      }
    }
    return out;
  };

  const initAdminInputMasks = root => {
    root.querySelectorAll?.('input[data-afe-input-mask]').forEach(input => {
      if (input.dataset.afeAdminMaskReady === '1') return;
      input.dataset.afeAdminMaskReady = '1';
      const apply = () => {
        const pattern = input.dataset.afeInputMask || '';
        const formatted = adminFormatMask(input.value, pattern);
        if (input.value !== formatted) input.value = formatted;
      };
      input.addEventListener('input', apply);
      input.addEventListener('blur', apply);
      apply();
    });
  };

  const initAdminRepeaters = root => {
    root.querySelectorAll('[data-afe-admin-repeater]').forEach(repeater => {
      if (repeater.dataset.afeAdminReady === '1') return;
      repeater.dataset.afeAdminReady = '1';
      const rows = qs(repeater, '[data-afe-admin-repeater-rows]');
      const template = qs(repeater, '[data-afe-admin-repeater-template]');
      const add = qs(repeater, '[data-afe-admin-repeater-add]');
      const refresh = () => {
        const current = [...rows.querySelectorAll(':scope > [data-afe-admin-repeater-row]')];
        current.forEach((row, index) => {
          const number = qs(row, '[data-afe-admin-row-number]');
          if (number) number.textContent = faNumber(index + 1);
        });
        const counter = qs(repeater, '.afe-admin-repeater-toolbar > span');
        if (counter) counter.textContent = `${faNumber(current.length)} ردیف`;
      };
      add?.addEventListener('click', () => {
        const index = rows.querySelectorAll(':scope > [data-afe-admin-repeater-row]').length;
        const html = (template?.innerHTML || '').replaceAll('__INDEX__', String(index));
        const holder = document.createElement('div');
        holder.innerHTML = html.trim();
        if (holder.firstElementChild) {
          rows.appendChild(holder.firstElementChild);
          initAdminInputMasks(holder.firstElementChild);
        }
        refresh();
      });
      repeater.addEventListener('click', event => {
        const remove = event.target.closest('[data-afe-admin-repeater-remove]');
        if (!remove) return;
        remove.closest('[data-afe-admin-repeater-row]')?.remove();
        // Re-index names so PHP receives a clean sequential array.
        [...rows.querySelectorAll(':scope > [data-afe-admin-repeater-row]')].forEach((row, rowIndex) => {
          row.querySelectorAll('[name]').forEach(control => {
            control.name = control.name.replace(/\[[0-9]+\](?=\[[^\]]+\]$)/, `[${rowIndex}]`);
          });
        });
        refresh();
      });
      refresh();
    });
  };

  const initBrandMarkSettings = () => {
    document.querySelectorAll('[data-afe-brand-settings]').forEach(root => {
      if (root.dataset.afeBrandReady === '1') return;
      root.dataset.afeBrandReady = '1';

      const mode = qs(root, '[data-afe-brand-mode]');
      const media = qs(root, '[data-afe-brand-media]');
      const select = qs(root, '[data-afe-brand-select]');
      const remove = qs(root, '[data-afe-brand-remove]');
      const idInput = qs(root, '[data-afe-brand-image-id]');
      const urlInput = qs(root, '[data-afe-brand-image-url]');
      const preview = qs(root, '[data-afe-brand-preview]');

      const syncMode = () => {
        if (media) media.hidden = mode?.value !== 'image';
      };

      const syncPreview = url => {
        if (!preview) return;
        preview.replaceChildren();
        if (url) {
          const image = document.createElement('img');
          image.src = url;
          image.alt = '';
          preview.appendChild(image);
          if (remove) remove.hidden = false;
        } else {
          const empty = document.createElement('span');
          empty.textContent = 'هنوز تصویری انتخاب نشده است.';
          preview.appendChild(empty);
          if (remove) remove.hidden = true;
        }
      };

      mode?.addEventListener('change', syncMode);
      select?.addEventListener('click', () => {
        if (!window.wp?.media) {
          window.alert('کتابخانه رسانه وردپرس در دسترس نیست. صفحه را تازه‌سازی کنید.');
          return;
        }
        const frame = window.wp.media({
          title: 'انتخاب نشان فرم',
          button: { text: 'استفاده از این تصویر' },
          library: { type: 'image' },
          multiple: false
        });
        frame.on('select', () => {
          const attachment = frame.state().get('selection').first()?.toJSON?.();
          if (!attachment) return;
          const url = attachment.sizes?.medium?.url || attachment.sizes?.thumbnail?.url || attachment.url || '';
          if (idInput) idInput.value = String(attachment.id || 0);
          if (urlInput) urlInput.value = url;
          syncPreview(url);
        });
        frame.open();
      });
      remove?.addEventListener('click', () => {
        if (idInput) idInput.value = '0';
        if (urlInput) urlInput.value = '';
        syncPreview('');
      });

      syncMode();
    });
  };


  const initTemplateEditors = () => {
    document.querySelectorAll('[data-afe-template-editor]').forEach(root => {
      if (root.dataset.afeTemplateReady === '1') return;
      root.dataset.afeTemplateReady = '1';

      const input = qs(root, '[data-afe-template-input]');
      const defaultInput = qs(root, '[data-afe-template-default]');
      const reset = qs(root, '[data-afe-template-reset]');
      const status = qs(root, '[data-afe-template-status]');
      const hint = qs(root, '[data-afe-template-hint]');
      if (!input || !defaultInput) return;

      const canonical = value => String(value || '').replace(/\r\n?/g, '\n').trim();
      const sync = () => {
        const isDefault = canonical(input.value) === canonical(defaultInput.value);
        if (status) {
          status.textContent = isDefault ? 'قالب پیش‌فرض' : 'قالب سفارشی';
          status.classList.toggle('is-default', isDefault);
          status.classList.toggle('is-custom', !isDefault);
        }
        if (hint) {
          hint.textContent = isDefault
            ? 'در حال استفاده از قالب پیش‌فرض کد/AFE است؛ تا زمان تغییر، Override ذخیره نمی‌شود.'
            : 'پس از ذخیره، این HTML به‌عنوان Override همین فرم استفاده می‌شود.';
        }
      };

      input.addEventListener('input', sync);
      reset?.addEventListener('click', () => {
        const alreadyDefault = canonical(input.value) === canonical(defaultInput.value);
        if (!alreadyDefault && !window.confirm('تغییرات این قالب کنار گذاشته شود و قالب پیش‌فرض فعلی بازگردانی شود؟')) return;
        input.value = defaultInput.value;
        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.focus();
      });
      sync();
    });
  };

  const initFormTabs = () => {
    document.querySelectorAll('[data-afe-form-tabs]').forEach(nav => {
      if (nav.dataset.afeTabsReady === '1') return;
      nav.dataset.afeTabsReady = '1';
      const form = nav.closest('form');
      if (!form) return;
      const buttons = [...nav.querySelectorAll('[data-afe-form-tab]')];
      const panels = [...form.querySelectorAll('[data-afe-form-tab-panel]')];
      const storageKey = `afe-form-tab:${window.location.pathname}:${new URLSearchParams(window.location.search).get('form') || ''}`;

      const activate = key => {
        if (!buttons.some(button => button.dataset.afeFormTab === key)) key = 'general';
        buttons.forEach(button => {
          const active = button.dataset.afeFormTab === key;
          button.classList.toggle('is-active', active);
          button.setAttribute('aria-selected', active ? 'true' : 'false');
        });
        panels.forEach(panel => {
          const active = panel.dataset.afeFormTabPanel === key;
          panel.hidden = !active;
          panel.classList.toggle('is-active', active);
        });
        const fieldSidebar = form.querySelector('[data-afe-field-override-sidebar]');
        const adminLayout = form.querySelector('[data-afe-admin-layout]');
        if (fieldSidebar) fieldSidebar.hidden = key !== 'fields';
        adminLayout?.classList.toggle('is-fields-tab', key === 'fields');
        try { window.sessionStorage.setItem(storageKey, key); } catch (error) {}
      };

      buttons.forEach(button => button.addEventListener('click', () => activate(button.dataset.afeFormTab || 'general')));
      let initial = 'general';
      const hash = String(window.location.hash || '').replace(/^#afe-tab-/, '');
      if (hash && buttons.some(button => button.dataset.afeFormTab === hash)) initial = hash;
      else {
        try { initial = window.sessionStorage.getItem(storageKey) || 'general'; } catch (error) {}
      }
      activate(initial);
    });
  };

  const copyText = async text => {
    if (navigator.clipboard?.writeText) {
      await navigator.clipboard.writeText(text);
      return;
    }
    const textarea = document.createElement('textarea');
    textarea.value = text;
    textarea.style.position = 'fixed';
    textarea.style.opacity = '0';
    document.body.appendChild(textarea);
    textarea.select();
    document.execCommand('copy');
    textarea.remove();
  };

  const initTokenPalettes = () => {
    document.querySelectorAll('.afe-token-palette').forEach(root => {
      if (root.dataset.afeTokenReady === '1') return;
      root.dataset.afeTokenReady = '1';
      const toast = qs(root, '[data-afe-token-toast]');
      let toastTimer = null;
      root.addEventListener('click', async event => {
        const chip = event.target.closest('[data-afe-token-copy]');
        if (!chip) return;
        const token = chip.dataset.afeTokenCopy || '';
        if (!token) return;
        try {
          await copyText(token);
          chip.classList.add('is-copied');
          if (toast) toast.textContent = `کپی شد: ${token}`;
          window.clearTimeout(toastTimer);
          toastTimer = window.setTimeout(() => {
            chip.classList.remove('is-copied');
            if (toast) toast.textContent = '';
          }, 1800);
        } catch (error) {
          if (toast) toast.textContent = 'کپی انجام نشد.';
        }
      });
    });
  };

  const initActionBuilders = () => {
    document.querySelectorAll('[data-afe-action-builder]').forEach(builder => {
      if (builder.dataset.afeActionReady === '1') return;
      builder.dataset.afeActionReady = '1';
      const groupsRoot = qs(builder, '[data-afe-event-groups]');
      const groupTemplate = qs(builder, 'template[data-afe-event-template]');
      const actionTemplate = qs(builder, 'template[data-afe-action-row-template]');
      const conditionTemplate = qs(builder, 'template[data-afe-condition-template]');
      const override = qs(builder, '[data-afe-actions-override]');
      const source = qs(builder, '[data-afe-action-source]');
      let dragged = null;

      const makeId = prefix => `${prefix}_${uuid()}`.replace(/[^a-zA-Z0-9_]/g, '_');
      const htmlNode = html => {
        const holder = document.createElement('div');
        holder.innerHTML = html.trim();
        return holder.firstElementChild;
      };
      const markOverride = () => {
        if (override) override.checked = true;
        if (source) {
          source.textContent = 'Override مدیریتی';
          source.classList.remove('is-code');
          source.classList.add('is-override');
        }
      };
      const syncSource = () => {
        if (!source || !override) return;
        source.textContent = override.checked ? 'Override مدیریتی' : 'ارث‌بری از تعریف کد';
        source.classList.toggle('is-override', override.checked);
        source.classList.toggle('is-code', !override.checked);
      };
      const syncShowWhen = card => {
        card?.querySelectorAll('[data-afe-show-when-field]').forEach(field => {
          const sourceKey = field.dataset.afeShowWhenField || '';
          const expected = field.dataset.afeShowWhenValue || '';
          const control = card.querySelector(`[data-afe-config-field-key="${sourceKey}"]`);
          field.hidden = !!control && String(control.value) !== expected;
        });
      };
      const syncSmsProvider = card => {
        if (!card) return;
        const provider = card.querySelector('[data-afe-ui-role="sms_provider"]');
        const mode = card.querySelector('[data-afe-ui-role="sms_mode"]');
        if (!provider || !mode) return;
        let map = {};
        try { map = JSON.parse(provider.dataset.afeProviderModes || '{}') || {}; } catch (_) { map = {}; }
        const modes = Array.isArray(map[provider.value]) ? map[provider.value] : [];
        [...mode.options].forEach(option => {
          const supported = modes.includes(option.value);
          // Keep an already-saved unsupported value selectable so saving another
          // setting does not silently rewrite the Action. Once the user changes
          // away from it, unsupported choices become disabled.
          option.disabled = !supported && mode.value !== option.value;
        });
        const unsupported = mode.value && !modes.includes(mode.value);
        let warning = card.querySelector('[data-afe-sms-provider-warning]');
        if (unsupported && !warning) {
          warning = document.createElement('div');
          warning.className = 'afe-action-provider-warning';
          warning.dataset.afeSmsProviderWarning = '1';
          const config = card.querySelector('[data-afe-action-config]');
          config?.prepend(warning);
        }
        if (warning) {
          warning.hidden = !unsupported;
          if (unsupported) warning.textContent = 'روش ارسال انتخاب‌شده توسط Provider/Gateway فعلی پشتیبانی نمی‌شود. Provider یا روش ارسال را تغییر دهید.';
        }
      };
      const syncCondition = row => {
        if (!row) return;
        const operator = qs(row, '[data-afe-condition-operator]');
        const value = qs(row, '[data-afe-condition-value]');
        if (!operator || !value) return;
        const valueLess = ['empty', 'not_empty'].includes(operator.value);
        value.hidden = valueLess;
        value.disabled = valueLess;
      };
      const syncConditionCount = card => {
        const summaryCount = card?.querySelector('.afe-action-conditions > summary span');
        const rows = card?.querySelectorAll('[data-afe-condition-row]') || [];
        if (summaryCount) summaryCount.textContent = `${faNumber(rows.length)} شرط`;
      };
      const syncCard = card => {
        if (!card) return;
        const type = qs(card, '[data-afe-action-type]');
        const label = qs(card, '[data-afe-action-label]');
        if (type && label) label.textContent = type.options[type.selectedIndex]?.text || type.value;
        syncShowWhen(card);
        syncSmsProvider(card);
        card.querySelectorAll('[data-afe-condition-row]').forEach(syncCondition);
        syncConditionCount(card);
      };
      const replaceActionConfig = card => {
        const type = qs(card, '[data-afe-action-type]')?.value || '';
        const config = qs(card, '[data-afe-action-config]');
        const group = card.closest('[data-afe-event-group]')?.dataset.groupIndex || '';
        const action = card.dataset.actionIndex || '';
        const template = builder.querySelector(`template[data-afe-action-config-template="${type}"]`);
        if (!config || !template) return;
        const html = template.innerHTML
          .replaceAll('__GROUP__', group)
          .replaceAll('__ACTION__', action);
        config.innerHTML = html;
        syncCard(card);
      };
      const addAction = group => {
        if (!actionTemplate) return;
        const groupId = group.dataset.groupIndex || makeId('group');
        const actionId = makeId('action');
        const actionKey = `action_${uuid()}`.slice(0, 80);
        const html = actionTemplate.innerHTML
          .replaceAll('__GROUP__', groupId)
          .replaceAll('__ACTION__', actionId)
          .replaceAll('__ACTION_KEY__', actionKey);
        const node = htmlNode(html);
        if (!node) return;
        node.dataset.actionIndex = actionId;
        qs(group, '[data-afe-event-actions]')?.appendChild(node);
        syncCard(node);
        markOverride();
        node.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      };
      const addCondition = card => {
        if (!conditionTemplate) return;
        const groupId = card.closest('[data-afe-event-group]')?.dataset.groupIndex || '';
        const actionId = card.dataset.actionIndex || '';
        const conditionId = makeId('condition');
        const html = conditionTemplate.innerHTML
          .replaceAll('__GROUP__', groupId)
          .replaceAll('__ACTION__', actionId)
          .replaceAll('__CONDITION__', conditionId);
        const node = htmlNode(html);
        if (!node) return;
        qs(card, '[data-afe-condition-rows]')?.appendChild(node);
        syncCondition(node);
        syncConditionCount(card);
        markOverride();
      };

      qs(builder, '[data-afe-add-event]')?.addEventListener('click', () => {
        if (!groupsRoot || !groupTemplate) return;
        const groupId = makeId('group');
        const html = groupTemplate.innerHTML.replaceAll('__GROUP__', groupId);
        const node = htmlNode(html);
        if (!node) return;
        node.dataset.groupIndex = groupId;
        groupsRoot.appendChild(node);
        markOverride();
        node.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
      });

      override?.addEventListener('change', syncSource);

      builder.addEventListener('click', event => {
        const addActionButton = event.target.closest('[data-afe-add-action]');
        if (addActionButton) {
          const group = addActionButton.closest('[data-afe-event-group]');
          if (group) addAction(group);
          return;
        }
        const removeAction = event.target.closest('[data-afe-remove-action]');
        if (removeAction) {
          removeAction.closest('[data-afe-action-row]')?.remove();
          markOverride();
          return;
        }
        const removeEvent = event.target.closest('[data-afe-remove-event]');
        if (removeEvent) {
          if (!window.confirm('این Event و تمام Actionهای داخل آن حذف شوند؟')) return;
          removeEvent.closest('[data-afe-event-group]')?.remove();
          markOverride();
          return;
        }
        const addConditionButton = event.target.closest('[data-afe-add-condition]');
        if (addConditionButton) {
          const card = addConditionButton.closest('[data-afe-action-row]');
          if (card) addCondition(card);
          return;
        }
        const removeCondition = event.target.closest('[data-afe-remove-condition]');
        if (removeCondition) {
          const card = removeCondition.closest('[data-afe-action-row]');
          removeCondition.closest('[data-afe-condition-row]')?.remove();
          syncConditionCount(card);
          markOverride();
        }
      });

      builder.addEventListener('change', event => {
        if (event.target === override) return;
        const eventSelect = event.target.closest('[data-afe-event-select]');
        if (eventSelect) {
          const slug = eventSelect.closest('[data-afe-event-group]')?.querySelector('[data-afe-event-slug]');
          if (slug) slug.textContent = eventSelect.value;
        }
        const type = event.target.closest('[data-afe-action-type]');
        if (type) replaceActionConfig(type.closest('[data-afe-action-row]'));
        const configControl = event.target.closest('[data-afe-config-field-key]');
        if (configControl) {
          const card = configControl.closest('[data-afe-action-row]');
          syncShowWhen(card);
          syncSmsProvider(card);
        }
        const operator = event.target.closest('[data-afe-condition-operator]');
        if (operator) syncCondition(operator.closest('[data-afe-condition-row]'));
        markOverride();
      });
      builder.addEventListener('input', event => {
        if (event.target === override) return;
        if (event.target.closest('input,textarea,select')) markOverride();
      });

      builder.addEventListener('dragstart', event => {
        const card = event.target.closest('[data-afe-action-row]');
        if (!card || !event.target.closest('[data-afe-action-drag]')) {
          if (card) event.preventDefault();
          return;
        }
        dragged = card;
        card.classList.add('is-dragging');
        event.dataTransfer.effectAllowed = 'move';
      });
      builder.addEventListener('dragend', () => {
        dragged?.classList.remove('is-dragging');
        dragged = null;
      });
      builder.addEventListener('dragover', event => {
        if (!dragged) return;
        const target = event.target.closest('[data-afe-action-row]');
        const container = event.target.closest('[data-afe-event-actions]');
        if (!container || dragged.parentElement !== container) return;
        event.preventDefault();
        if (!target || target === dragged) return;
        const rect = target.getBoundingClientRect();
        const after = event.clientY > rect.top + rect.height / 2;
        container.insertBefore(dragged, after ? target.nextSibling : target);
      });
      builder.addEventListener('drop', event => {
        if (!dragged) return;
        event.preventDefault();
        markOverride();
      });

      builder.querySelectorAll('[data-afe-action-row]').forEach(syncCard);
      syncSource();
    });
  };

  const initDuplicateSettings = () => {
    document.querySelectorAll('[data-afe-duplicate-settings]').forEach(root => {
      const enabled = root.querySelector('[data-afe-duplicate-enabled]');
      const fields = [...root.querySelectorAll('input[name="duplicate_fields[]"]')];
      const count = root.querySelector('[data-afe-duplicate-count]');
      const warning = root.querySelector('[data-afe-duplicate-warning]');
      const form = root.closest('form');

      const sync = () => {
        const selected = fields.filter(field => field.checked).length;
        if (count) count.textContent = `${faNumber(selected)} فیلد`;
        if (warning) warning.hidden = !(enabled?.checked && selected === 0);
        root.classList.toggle('is-disabled', !enabled?.checked);
      };

      enabled?.addEventListener('change', sync);
      fields.forEach(field => field.addEventListener('change', sync));
      form?.addEventListener('submit', event => {
        if (!enabled?.checked || fields.some(field => field.checked)) return;
        event.preventDefault();
        warning && (warning.hidden = false);
        root.scrollIntoView({ behavior: 'smooth', block: 'center' });
        window.alert('برای فعال‌کردن جلوگیری از تکرار، حداقل یک فیلد انتخاب کنید.');
      });
      sync();
    });
  };


  const initFieldOrdering = () => {
    document.querySelectorAll('[data-afe-field-order-list]').forEach(list => {
      if (list.dataset.afeOrderReady === '1') return;
      list.dataset.afeOrderReady = '1';
      let dragged = null;
      list.addEventListener('dragstart', event => {
        const item = event.target.closest('[data-afe-field-order-item]');
        if (!item || item.parentElement !== list) return;
        dragged = item;
        item.classList.add('is-dragging');
        event.dataTransfer.effectAllowed = 'move';
      });
      list.addEventListener('dragover', event => {
        if (!dragged) return;
        const target = event.target.closest('[data-afe-field-order-item]');
        if (target && target.parentElement !== list) return;
        event.preventDefault();
        if (!target || target === dragged) return;
        const rect = target.getBoundingClientRect();
        list.insertBefore(dragged, event.clientY > rect.top + rect.height / 2 ? target.nextSibling : target);
      });
      list.addEventListener('drop', event => {
        if (dragged) event.preventDefault();
      });
      list.addEventListener('dragend', () => {
        dragged?.classList.remove('is-dragging');
        dragged = null;
      });
    });
  };

  const initFieldOverrideSidebar = () => {
    document.querySelectorAll('[data-afe-field-override-sidebar]').forEach(sidebar => {
      if (sidebar.dataset.afeFieldOverrideReady === '1') return;
      sidebar.dataset.afeFieldOverrideReady = '1';
      const form = sidebar.closest('form');
      if (!form) return;
      const panels = [...sidebar.querySelectorAll('[data-afe-field-override-panel]')];
      const triggers = [...form.querySelectorAll('[data-afe-field-override-trigger]')];
      const empty = sidebar.querySelector('[data-afe-field-override-empty]');
      const close = sidebar.querySelector('[data-afe-field-override-close]');
      const formSlug = new URLSearchParams(window.location.search).get('form') || '';
      const storageKey = `afe-field-override:${window.location.pathname}:${formSlug}`;

      const selectField = (path, options = {}) => {
        const target = panels.find(panel => panel.dataset.afeFieldOverridePanel === path) || null;
        panels.forEach(panel => { panel.hidden = panel !== target; });
        triggers.forEach(trigger => {
          const active = Boolean(target) && trigger.dataset.afeFieldPath === path;
          trigger.classList.toggle('is-active', active);
          trigger.setAttribute('aria-pressed', active ? 'true' : 'false');
          trigger.closest('[data-afe-field-order-item]')?.classList.toggle('is-selected', active);
        });
        if (empty) empty.hidden = Boolean(target);
        if (close) close.hidden = !target;
        if (target) {
          sidebar.dataset.selectedField = path;
          try { window.sessionStorage.setItem(storageKey, path); } catch (error) {}
          if (options.focus !== false) target.querySelector('input,select,textarea,button')?.focus({ preventScroll: true });
          if (options.scroll !== false && window.matchMedia('(max-width: 1000px)').matches) {
            sidebar.scrollIntoView({ behavior: 'smooth', block: 'start' });
          }
        } else {
          delete sidebar.dataset.selectedField;
          try { window.sessionStorage.removeItem(storageKey); } catch (error) {}
        }
      };

      triggers.forEach(trigger => {
        trigger.addEventListener('click', event => {
          event.stopPropagation();
          selectField(trigger.dataset.afeFieldPath || '');
        });
      });
      close?.addEventListener('click', () => selectField(''));

      let remembered = '';
      try { remembered = window.sessionStorage.getItem(storageKey) || ''; } catch (error) {}
      if (remembered && panels.some(panel => panel.dataset.afeFieldOverridePanel === remembered)) {
        selectField(remembered, { focus: false, scroll: false });
      } else {
        selectField('', { focus: false, scroll: false });
      }
    });
  };

  const initInputMaskOverrides = () => {
    document.querySelectorAll('[data-afe-input-mask-override]').forEach(root => {
      const select = qs(root, '[data-afe-input-mask-select]');
      const custom = qs(root, '[data-afe-custom-mask-row]');
      if (!select || select.dataset.afeInputMaskReady === '1') return;
      select.dataset.afeInputMaskReady = '1';
      const sync = () => {
        if (custom) custom.hidden = select.value !== 'custom';
      };
      select.addEventListener('change', sync);
      sync();
    });
  };

  const initValidatorOverrides = () => {
    document.querySelectorAll('[data-afe-validator-override]').forEach(root => {
      const select = qs(root, '[data-afe-validator-select]');
      if (!select || select.dataset.afeValidatorReady === '1') return;
      select.dataset.afeValidatorReady = '1';
      const sync = () => {
        const selected = new Set([...select.selectedOptions].map(option => option.value));
        root.querySelectorAll('[data-afe-validator-detail]').forEach(detail => {
          detail.hidden = !selected.has(detail.dataset.afeValidatorDetail || '');
        });
      };
      select.addEventListener('change', sync);
      sync();
    });
  };

  const initAdminDatePickers = () => {
    if (!window.afeAdmin?.isJalali || !window.jalaliDatepicker) return;
    try {
      window.jalaliDatepicker.startWatch({
        selector: 'input.afe-admin-date[data-jdp]',
        date: true,
        time: false,
        persianDigits: false,
        autoReadOnlyInput: false,
        hideAfterChange: true,
        showTodayBtn: true,
        showEmptyBtn: true,
        container: 'body',
        position: 'right',
        topSpace: 6,
        bottomSpace: 6,
        overflowSpace: 12,
        zIndex: 2147480000
      });
    } catch (error) {
      console.error('[AFE] Failed to initialize admin JalaliDatePicker', error);
    }
  };

  document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('.afe-code').forEach(el => {
      el.addEventListener('keydown', e => {
        if (e.key === 'Tab') {
          e.preventDefault();
          const start = el.selectionStart;
          const end = el.selectionEnd;
          el.value = el.value.substring(0, start) + '  ' + el.value.substring(end);
          el.selectionStart = el.selectionEnd = start + 2;
        }
      });
    });

    document.querySelectorAll('[data-afe-geo-chunk-uploader]').forEach(root => new GeoChunkUploader(root));
    initAdminRepeaters(document);
    initAdminInputMasks(document);
    initBrandMarkSettings();
    initTemplateEditors();
    initFormTabs();
    initTokenPalettes();
    initActionBuilders();
    initDuplicateSettings();
    initFieldOrdering();
    initFieldOverrideSidebar();
    initValidatorOverrides();
    initInputMaskOverrides();
    initAdminDatePickers();
  });
})();


// AFE 1.0.21 destructive action confirmation.
document.addEventListener('submit', function(event){
  const form = event.target.closest?.('form[data-afe-confirm]');
  if (!form) return;
  const message = form.getAttribute('data-afe-confirm') || 'آیا مطمئن هستید؟';
  if (!window.confirm(message)) event.preventDefault();
});

// Structural Excel export columns: drag & drop without external runtime libraries.
(() => {
  const lists = document.querySelectorAll('[data-afe-export-columns]');
  lists.forEach((list) => {
    let dragging = null;
    const reindex = () => {
      [...list.querySelectorAll('[data-afe-export-column]')].forEach((row, index) => {
        row.querySelectorAll('[name*="export_profile[excel][columns]"]').forEach((input) => {
          input.name = input.name.replace(/export_profile\[excel\]\[columns\]\[\d+\]/, `export_profile[excel][columns][${index}]`);
        });
      });
    };
    list.addEventListener('dragstart', (event) => {
      const row = event.target.closest('[data-afe-export-column]');
      if (!row) return;
      dragging = row;
      row.classList.add('is-dragging');
      if (event.dataTransfer) event.dataTransfer.effectAllowed = 'move';
    });
    list.addEventListener('dragover', (event) => {
      if (!dragging) return;
      event.preventDefault();
      const target = event.target.closest('[data-afe-export-column]');
      if (!target || target === dragging) return;
      const rect = target.getBoundingClientRect();
      const after = event.clientY > rect.top + rect.height / 2;
      target.parentNode.insertBefore(dragging, after ? target.nextSibling : target);
    });
    list.addEventListener('dragend', () => {
      if (dragging) dragging.classList.remove('is-dragging');
      dragging = null;
      reindex();
    });
  });
})();

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
        if (holder.firstElementChild) rows.appendChild(holder.firstElementChild);
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

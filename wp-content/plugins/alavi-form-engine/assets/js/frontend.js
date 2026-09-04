(() => {
  'use strict';

  const cfg = window.afeFrontend || {};

  const qs = (el, sel) => el.querySelector(sel);
  const qsa = (el, sel) => Array.from(el.querySelectorAll(sel));

  // ---------------------------------------------------------------------------
  // AFE Custom Select
  // ---------------------------------------------------------------------------
  // The native <select> is always the source of truth. AFE renders its own UI
  // on top of it and actively removes theme-level Select2 containers inside
  // .afe-form so the form remains visually isolated from the active theme.
  const customSelectState = new WeakMap();
  let activeCustomSelect = null;
  let customSelectSequence = 0;

  function removeThemeSelect2(select) {
    if (!select || !select.closest('.afe-form')) return;
    const field = select.closest('.afe-field') || select.parentElement;
    const jq = window.jQuery;

    if (jq && jq.fn && typeof jq.fn.select2 === 'function' && select.classList.contains('select2-hidden-accessible')) {
      try { jq(select).select2('destroy'); } catch (_) {}
    }

    if (field) qsa(field, '.select2-container').forEach(node => node.remove());
    select.classList.remove('select2-hidden-accessible');
    select.removeAttribute('data-select2-id');

    // For native mode restore attributes that Select2 commonly changes.
    if (select.dataset.afeSelectMode === 'native') {
      if (select.getAttribute('aria-hidden') === 'true') select.removeAttribute('aria-hidden');
      if (select.getAttribute('tabindex') === '-1') select.removeAttribute('tabindex');
    }
  }

  function customSelectLabel(select) {
    const selected = Array.from(select.selectedOptions || []).filter(option => option.value !== '');
    if (select.multiple) {
      if (!selected.length) return select.dataset.afeSelectPlaceholder || 'انتخاب کنید';
      if (selected.length === 1) return selected[0].textContent.trim();
      return `${selected.length} مورد انتخاب شده`;
    }
    const option = select.options[select.selectedIndex];
    return option ? option.textContent.trim() : (select.dataset.afeSelectPlaceholder || 'انتخاب کنید');
  }

  function customSelectSearchEnabled(select) {
    const setting = select.dataset.afeSelectSearchable || 'auto';
    if (setting === '1') return true;
    if (setting === '0') return false;
    const threshold = Math.max(1, Number(select.dataset.afeSelectSearchThreshold || 7));
    return Array.from(select.options).filter(option => option.value !== '').length >= threshold;
  }

  function closeCustomSelect(select, returnFocus = false) {
    const state = customSelectState.get(select);
    if (!state || !state.open) return;
    state.open = false;
    state.root.classList.remove('is-open', 'is-up');
    state.trigger.setAttribute('aria-expanded', 'false');
    state.panel.hidden = true;
    state.search.value = '';
    qsa(state.list, '.afe-custom-select__option').forEach(option => { option.hidden = false; });
    if (activeCustomSelect === select) activeCustomSelect = null;
    if (returnFocus) state.trigger.focus();
  }

  function positionCustomSelect(state) {
    state.root.classList.remove('is-up');
    const rect = state.trigger.getBoundingClientRect();
    const below = window.innerHeight - rect.bottom;
    const above = rect.top;
    if (below < 280 && above > below) state.root.classList.add('is-up');
  }

  function openCustomSelect(select) {
    const state = customSelectState.get(select);
    if (!state || select.disabled || state.open) return;
    if (activeCustomSelect && activeCustomSelect !== select) closeCustomSelect(activeCustomSelect);
    state.open = true;
    activeCustomSelect = select;
    state.root.classList.add('is-open');
    state.trigger.setAttribute('aria-expanded', 'true');
    state.panel.hidden = false;
    positionCustomSelect(state);

    requestAnimationFrame(() => {
      if (state.searchWrap.hidden === false) {
        state.search.focus();
      } else {
        const selected = qs(state.list, '.afe-custom-select__option[aria-selected="true"]:not(:disabled)');
        (selected || qs(state.list, '.afe-custom-select__option:not(:disabled)'))?.focus();
      }
    });
  }

  function setCustomSelectValue(select, optionValue, optionIndex = -1) {
    const options = Array.from(select.options);
    let option = options.find(item => String(item.value) === String(optionValue));

    // Fallback for duplicate/empty values or a list that changed while the user
    // was searching. The value is the stable identity; index is only a fallback.
    if (!option && Number.isInteger(optionIndex) && optionIndex >= 0) {
      option = select.options[optionIndex] || null;
    }
    if (!option || option.disabled) return;

    if (select.multiple) {
      if (option.value === '') return;
      option.selected = !option.selected;
    } else {
      // Commit the native select first. Explicitly setting selected flags avoids
      // stale selectedIndex/value states caused by theme plugins or option DOM
      // rebuilds while the search panel is open.
      options.forEach(item => { item.selected = item === option; });
      select.value = option.value;
    }

    // Update and close our UI before notifying third-party/change listeners.
    // External listeners (conditional logic, dependent geo selects, themes) may
    // rebuild options synchronously, so the selected value must already be final.
    refreshCustomSelect(select, true);
    if (!select.multiple) closeCustomSelect(select, true);

    select.dispatchEvent(new Event('input', {bubbles:true}));
    select.dispatchEvent(new Event('change', {bubbles:true}));

    // A listener is allowed to mutate options, but if the selected option still
    // exists keep the custom trigger synchronized with the actual native value.
    refreshCustomSelect(select, true);
  }

  function renderCustomSelectOptions(select, state) {
    state.list.innerHTML = '';
    const fragment = document.createDocumentFragment();
    const options = Array.from(select.options);

    options.forEach((option, optionIndex) => {
      const button = document.createElement('button');
      button.type = 'button';
      button.className = 'afe-custom-select__option';
      if (option.value === '') button.classList.add('is-placeholder');
      button.dataset.optionIndex = String(optionIndex);
      button.dataset.optionValue = String(option.value);
      button.dataset.search = (option.textContent || '').trim().toLocaleLowerCase('fa-IR');
      button.setAttribute('role', 'option');
      button.setAttribute('aria-selected', option.selected ? 'true' : 'false');
      button.disabled = Boolean(option.disabled);

      const check = document.createElement('span');
      check.className = 'afe-custom-select__check';
      check.setAttribute('aria-hidden', 'true');
      const label = document.createElement('span');
      label.className = 'afe-custom-select__option-label';
      label.textContent = option.textContent || '';
      button.append(check, label);

      button.addEventListener('click', event => {
        event.preventDefault();
        event.stopPropagation();
        setCustomSelectValue(select, button.dataset.optionValue ?? option.value, Number(button.dataset.optionIndex));
      });
      button.addEventListener('keydown', event => {
        if (event.key === 'Escape') {
          event.preventDefault();
          closeCustomSelect(select, true);
          return;
        }
        if (!['ArrowDown','ArrowUp','Home','End'].includes(event.key)) return;
        event.preventDefault();
        const visible = qsa(state.list, '.afe-custom-select__option:not([hidden]):not(:disabled)');
        if (!visible.length) return;
        let index = visible.indexOf(event.currentTarget);
        if (event.key === 'Home') index = 0;
        else if (event.key === 'End') index = visible.length - 1;
        else if (event.key === 'ArrowDown') index = Math.min(visible.length - 1, index + 1);
        else index = Math.max(0, index - 1);
        visible[index]?.focus();
      });
      fragment.appendChild(button);
    });

    state.list.appendChild(fragment);
    const searchEnabled = customSelectSearchEnabled(select);
    state.searchWrap.hidden = !searchEnabled;
    state.empty.hidden = options.length > 0;
  }

  function refreshCustomSelect(select, rebuild = false) {
    const state = customSelectState.get(select);
    if (!state) return;
    removeThemeSelect2(select);
    if (rebuild) renderCustomSelectOptions(select, state);

    state.triggerText.textContent = customSelectLabel(select);
    state.trigger.disabled = Boolean(select.disabled);
    state.root.classList.toggle('is-disabled', Boolean(select.disabled));
    state.root.classList.toggle('has-value', select.multiple
      ? Array.from(select.selectedOptions || []).some(option => option.value !== '')
      : Boolean(select.value));

    qsa(state.list, '.afe-custom-select__option').forEach(button => {
      const option = select.options[Number(button.dataset.optionIndex)];
      if (!option) return;
      button.disabled = Boolean(option.disabled);
      button.setAttribute('aria-selected', option.selected ? 'true' : 'false');
      button.classList.toggle('is-selected', Boolean(option.selected));
    });

    if (select.disabled) closeCustomSelect(select);
  }

  function destroyCustomSelect(select) {
    const state = customSelectState.get(select);
    if (state) {
      closeCustomSelect(select);
      state.root.remove();
      state.observer?.disconnect();
      customSelectState.delete(select);
    }
    select.classList.remove('afe-select-enhanced');
    select.removeAttribute('aria-hidden');
    if (select.getAttribute('tabindex') === '-1') select.removeAttribute('tabindex');
  }

  function enhanceCustomSelect(select) {
    removeThemeSelect2(select);
    if (select.dataset.afeSelectMode !== 'custom') {
      destroyCustomSelect(select);
      return;
    }

    const existing = customSelectState.get(select);
    if (existing) {
      refreshCustomSelect(select, true);
      return;
    }

    const root = document.createElement('div');
    root.className = 'afe-custom-select';
    root.dataset.for = select.id || '';

    const trigger = document.createElement('button');
    trigger.type = 'button';
    trigger.className = 'afe-custom-select__trigger';
    trigger.setAttribute('role', 'combobox');
    trigger.setAttribute('aria-haspopup', 'listbox');
    trigger.setAttribute('aria-expanded', 'false');

    const triggerText = document.createElement('span');
    triggerText.className = 'afe-custom-select__value';
    const arrow = document.createElement('span');
    arrow.className = 'afe-custom-select__arrow';
    arrow.setAttribute('aria-hidden', 'true');
    trigger.append(triggerText, arrow);

    const panel = document.createElement('div');
    panel.className = 'afe-custom-select__panel';
    panel.hidden = true;
    const panelId = `${select.id || 'afe_select'}_panel_${++customSelectSequence}`;
    panel.id = panelId;
    trigger.setAttribute('aria-controls', panelId);

    const searchWrap = document.createElement('div');
    searchWrap.className = 'afe-custom-select__search-wrap';
    const search = document.createElement('input');
    search.type = 'search';
    search.className = 'afe-custom-select__search';
    search.placeholder = 'جستجو...';
    search.autocomplete = 'off';
    search.setAttribute('aria-label', 'جستجو در گزینه‌ها');
    searchWrap.appendChild(search);

    const list = document.createElement('div');
    list.className = 'afe-custom-select__options';
    list.setAttribute('role', 'listbox');
    if (select.multiple) list.setAttribute('aria-multiselectable', 'true');

    const empty = document.createElement('div');
    empty.className = 'afe-custom-select__empty';
    empty.textContent = 'گزینه‌ای پیدا نشد.';
    empty.hidden = true;

    panel.append(searchWrap, list, empty);
    root.append(trigger, panel);
    select.insertAdjacentElement('afterend', root);

    select.classList.add('afe-select-enhanced');
    select.setAttribute('aria-hidden', 'true');
    select.setAttribute('tabindex', '-1');

    const state = {root, trigger, triggerText, panel, searchWrap, search, list, empty, open:false, observer:null};
    customSelectState.set(select, state);

    trigger.addEventListener('click', () => state.open ? closeCustomSelect(select) : openCustomSelect(select));
    trigger.addEventListener('keydown', event => {
      if (['ArrowDown','ArrowUp','Enter',' '].includes(event.key)) {
        event.preventDefault();
        openCustomSelect(select);
      } else if (event.key === 'Escape') {
        closeCustomSelect(select);
      }
    });

    search.addEventListener('input', () => {
      const query = search.value.trim().toLocaleLowerCase('fa-IR');
      let visibleCount = 0;
      qsa(list, '.afe-custom-select__option').forEach(button => {
        const show = !query || (button.dataset.search || '').includes(query);
        button.hidden = !show;
        if (show) visibleCount++;
      });
      empty.hidden = visibleCount !== 0;
    });
    search.addEventListener('keydown', event => {
      if (event.key === 'Escape') {
        event.preventDefault();
        closeCustomSelect(select, true);
      } else if (event.key === 'ArrowDown') {
        event.preventDefault();
        qs(list, '.afe-custom-select__option:not([hidden]):not(:disabled)')?.focus();
      }
    });

    select.addEventListener('change', () => refreshCustomSelect(select, true));

    // Dependent selects replace their <option> nodes dynamically. Observe only
    // option-level changes to avoid feedback loops with the custom UI itself.
    state.observer = new MutationObserver(() => refreshCustomSelect(select, true));
    state.observer.observe(select, {childList:true, subtree:true, attributes:true, attributeFilter:['disabled','selected']});

    renderCustomSelectOptions(select, state);
    refreshCustomSelect(select);
  }

  function initCustomSelects(root) {
    const selects = [];
    if (root instanceof Element && root.matches('select.afe-select-source')) selects.push(root);
    qsa(root, 'select.afe-select-source').forEach(select => selects.push(select));
    selects.forEach(select => {
      removeThemeSelect2(select);
      if (select.dataset.afeSelectMode === 'custom') enhanceCustomSelect(select);
      else destroyCustomSelect(select);
    });
  }

  function refreshCustomSelects(root) {
    qsa(root, 'select.afe-select-source').forEach(select => {
      removeThemeSelect2(select);
      refreshCustomSelect(select, true);
    });
  }

  function initSelectIsolation(form) {
    initCustomSelects(form);
    if (form.dataset.afeSelectIsolation === '1') return;
    form.dataset.afeSelectIsolation = '1';

    const observer = new MutationObserver(mutations => {
      mutations.forEach(mutation => {
        if (mutation.type !== 'childList') return;
        if (mutation.target instanceof HTMLSelectElement && mutation.target.matches('.afe-select-source')) {
          refreshCustomSelect(mutation.target, true);
        }
        mutation.addedNodes.forEach(node => {
          if (!(node instanceof Element)) return;
          if (node.matches('.select2-container') && node.closest('.afe-form')) {
            const field = node.closest('.afe-field');
            const select = field ? qs(field, 'select.afe-select-source') : null;
            node.remove();
            if (select) removeThemeSelect2(select);
            return;
          }
          qsa(node, '.select2-container').forEach(container => {
            const field = container.closest('.afe-field');
            const select = field ? qs(field, 'select.afe-select-source') : null;
            container.remove();
            if (select) removeThemeSelect2(select);
          });
          initCustomSelects(node);
        });
      });
    });
    observer.observe(form, {childList:true, subtree:true});
  }

  // ---------------------------------------------------------------------------
  // AFE File Upload
  // ---------------------------------------------------------------------------
  // File inputs are owned by AFE. Theme/document-level handlers must never be
  // allowed to rewrite input[type=file].value (browsers only permit assigning
  // an empty string), so input/change/click events are contained here.
  const uploadState = new WeakMap();

  function formatFileSize(bytes) {
    const size = Number(bytes || 0);
    if (size < 1024) return `${size} B`;
    if (size < 1024 * 1024) return `${(size / 1024).toFixed(size < 10240 ? 1 : 0)} KB`;
    return `${(size / (1024 * 1024)).toFixed(size < 10 * 1024 * 1024 ? 1 : 0)} MB`;
  }

  function uploadFileTypeLabel(file) {
    const name = String(file?.name || '');
    const ext = name.includes('.') ? name.split('.').pop().toUpperCase() : '';
    if (file?.type?.startsWith('image/')) return ext || 'IMAGE';
    if (file?.type === 'application/pdf') return 'PDF';
    return ext || 'FILE';
  }

  function existingToggles(wrapper) {
    return wrapper ? qsa(wrapper, '[data-existing-file-toggle]') : [];
  }

  function selectedExistingCount(wrapper) {
    return existingToggles(wrapper).filter(toggle => toggle.checked).length;
  }

  function renderExistingFiles(wrapper) {
    if (!wrapper) return;
    const selectedCount = selectedExistingCount(wrapper);
    wrapper.dataset.existingSelectedCount = String(selectedCount);

    qsa(wrapper, '[data-existing-file]').forEach(row => {
      const toggle = qs(row, '[data-existing-file-toggle]');
      const selected = Boolean(toggle?.checked);
      row.classList.toggle('is-selected', selected);
      row.classList.toggle('is-removed', !selected);

      const state = qs(row, '[data-role="existing-state"]');
      if (state) state.textContent = selected ? 'استفاده می‌شود' : 'برای حذف علامت‌گذاری شد';

      const remove = qs(row, '[data-action="remove-existing"]');
      if (remove) {
        remove.textContent = selected ? 'حذف' : 'بازگردانی';
        remove.setAttribute('aria-pressed', selected ? 'false' : 'true');
      }
    });
  }

  function uploadValidationMessage(input) {
    const wrapper = input.closest('.afe-upload');
    const files = Array.from(input.files || []);
    const maxFiles = Math.max(1, Number(input.dataset.maxFiles || 1));
    const maxSizeMb = Math.max(1, Number(input.dataset.maxSize || 5));
    const maxSize = maxSizeMb * 1024 * 1024;
    const existingCount = selectedExistingCount(wrapper);

    if (files.length + existingCount > maxFiles) {
      return existingCount > 0
        ? `با احتساب فایل‌های موجود انتخاب‌شده، حداکثر ${maxFiles} فایل مجاز است.`
        : `حداکثر ${maxFiles} فایل می‌توانید انتخاب کنید.`;
    }

    const oversized = files.find(file => file.size > maxSize);
    if (oversized) {
      return `حجم «${oversized.name}» بیشتر از ${maxSizeMb} مگابایت است.`;
    }

    const accepted = String(input.accept || '')
      .split(',')
      .map(item => item.trim().toLowerCase())
      .filter(Boolean);

    if (accepted.length) {
      const invalid = files.find(file => {
        const mime = String(file.type || '').toLowerCase();
        const name = String(file.name || '').toLowerCase();
        if (!mime && !accepted.some(token => token.startsWith('.'))) return false;
        return !accepted.some(token => {
          if (token.startsWith('.')) return name.endsWith(token);
          if (token.endsWith('/*')) return mime.startsWith(token.slice(0, -1));
          return mime === token;
        });
      });
      if (invalid) return `نوع فایل «${invalid.name}» مجاز نیست.`;
    }

    return '';
  }

  function setUploadFiles(input, files) {
    const list = Array.from(files || []);
    if (!list.length) {
      // Empty string is the only value browsers allow us to assign.
      input.value = '';
      return true;
    }
    if (typeof DataTransfer === 'undefined') return false;
    const transfer = new DataTransfer();
    list.forEach(file => transfer.items.add(file));
    input.files = transfer.files;
    return true;
  }

  function clearNewFiles(input) {
    setUploadFiles(input, []);
  }

  /**
   * Single-file fields use replace semantics:
   * - selecting a new file automatically deselects the old existing file;
   * - selecting/restoring an existing file clears the newly selected file.
   *
   * This guarantees the effective count stays at one and prevents the previous
   * "existing + new = 2" validation dead-end.
   */
  function applySingleFileReplacement(input) {
    const wrapper = input.closest('.afe-upload');
    if (!wrapper) return;
    const maxFiles = Math.max(1, Number(input.dataset.maxFiles || 1));
    if (maxFiles !== 1) return;

    const hasNew = Array.from(input.files || []).length > 0;
    if (hasNew) {
      existingToggles(wrapper).forEach(toggle => { toggle.checked = false; });
      renderExistingFiles(wrapper);
    }
  }

  function renderUpload(input) {
    const wrapper = input.closest('.afe-upload');
    if (!wrapper) return;

    renderExistingFiles(wrapper);

    const selection = qs(wrapper, '[data-role="file-selection"]');
    const summary = qs(wrapper, '[data-role="file-summary"]');
    const list = qs(wrapper, '[data-role="file-list"]');
    const dropzone = qs(wrapper, '.afe-upload__dropzone');
    const files = Array.from(input.files || []);
    const existingCount = selectedExistingCount(wrapper);
    const effectiveCount = files.length + existingCount;

    wrapper.classList.toggle('has-files', effectiveCount > 0);
    wrapper.classList.toggle('has-new-files', files.length > 0);
    wrapper.classList.toggle('has-existing-files', existingCount > 0);
    dropzone?.classList.toggle('has-files', effectiveCount > 0);

    const primaryCopy = dropzone ? qs(dropzone, '.afe-upload__copy strong') : null;
    if (primaryCopy) {
      if (files.length) {
        primaryCopy.textContent = input.multiple ? 'افزودن یا تغییر فایل‌ها' : 'تغییر فایل انتخاب‌شده';
      } else if (existingCount && Number(input.dataset.maxFiles || 1) === 1) {
        primaryCopy.textContent = 'جایگزینی فایل موجود';
      } else if (existingCount) {
        primaryCopy.textContent = 'افزودن فایل جدید';
      } else {
        primaryCopy.textContent = 'فایل را انتخاب کنید';
      }
    }

    if (!selection || !summary || !list) return;

    selection.hidden = files.length === 0;
    list.innerHTML = '';
    if (!files.length) {
      summary.textContent = '';
      return;
    }

    const totalSize = files.reduce((sum, file) => sum + Number(file.size || 0), 0);
    summary.innerHTML = `<strong>${files.length} فایل جدید انتخاب شده</strong><span>${formatFileSize(totalSize)}</span>`;

    files.forEach((file, index) => {
      const row = document.createElement('div');
      row.className = 'afe-upload__file';
      row.dataset.fileIndex = String(index);

      const badge = document.createElement('span');
      badge.className = 'afe-upload__file-type';
      badge.textContent = uploadFileTypeLabel(file);

      const info = document.createElement('span');
      info.className = 'afe-upload__file-info';
      const name = document.createElement('strong');
      name.textContent = file.name;
      name.title = file.name;
      const size = document.createElement('small');
      size.textContent = formatFileSize(file.size);
      info.append(name, size);

      const remove = document.createElement('button');
      remove.type = 'button';
      remove.className = 'afe-upload__remove';
      remove.setAttribute('aria-label', `حذف ${file.name}`);
      remove.title = 'حذف فایل جدید';
      remove.innerHTML = '<span aria-hidden="true">×</span>';
      remove.addEventListener('click', event => {
        event.preventDefault();
        event.stopPropagation();
        const current = Array.from(input.files || []);
        current.splice(index, 1);
        if (!setUploadFiles(input, current)) {
          input.value = '';
        }
        renderUpload(input);
        const field = input.closest('.afe-field');
        if (field) setError(field, uploadValidationMessage(input));
      });

      row.append(badge, info, remove);
      list.appendChild(row);
    });
  }

  function updateUpload(input, form, {newSelection = false} = {}) {
    if (newSelection) applySingleFileReplacement(input);
    renderUpload(input);

    const field = input.closest('.afe-field');
    const message = uploadValidationMessage(input);
    if (field) setError(field, message);
    if (!message && field?.classList.contains('has-error')) setError(field, '');
    applyConditions(form);
  }

  function initExistingFileControls(input, form) {
    const wrapper = input.closest('.afe-upload');
    if (!wrapper || wrapper.dataset.afeExistingReady === '1') return;
    wrapper.dataset.afeExistingReady = '1';

    existingToggles(wrapper).forEach(toggle => {
      toggle.addEventListener('click', event => event.stopPropagation());
      toggle.addEventListener('change', event => {
        event.stopPropagation();

        // In a single-file field, explicitly restoring/selecting an existing
        // file means it wins over the newly selected local file.
        if (toggle.checked && Math.max(1, Number(input.dataset.maxFiles || 1)) === 1) {
          existingToggles(wrapper).forEach(other => {
            if (other !== toggle) other.checked = false;
          });
          clearNewFiles(input);
        }

        renderUpload(input);
        const field = input.closest('.afe-field');
        if (field) setError(field, uploadValidationMessage(input));
        applyConditions(form);
      });
    });

    qsa(wrapper, '[data-action="remove-existing"]').forEach(button => {
      button.addEventListener('click', event => {
        event.preventDefault();
        event.stopPropagation();
        const row = button.closest('[data-existing-file]');
        const toggle = row ? qs(row, '[data-existing-file-toggle]') : null;
        if (!toggle) return;

        toggle.checked = !toggle.checked;
        if (toggle.checked && Math.max(1, Number(input.dataset.maxFiles || 1)) === 1) {
          existingToggles(wrapper).forEach(other => {
            if (other !== toggle) other.checked = false;
          });
          clearNewFiles(input);
        }

        renderUpload(input);
        const field = input.closest('.afe-field');
        if (field) setError(field, uploadValidationMessage(input));
        applyConditions(form);
      });
    });

    // Elementor and themes commonly delegate clicks from anchors/images. Existing
    // file UI intentionally contains no anchors; contain the remaining events too.
    qsa(wrapper, '[data-existing-file]').forEach(row => {
      row.addEventListener('click', event => event.stopPropagation());
    });

    renderExistingFiles(wrapper);
  }

  function initFileUploads(root) {
    const form = root.matches?.('.afe-form') ? root : root.closest?.('.afe-form');
    if (!form) return;

    qsa(root, 'input[type="file"][data-afe-file-input]').forEach(input => {
      if (uploadState.has(input)) return;
      const wrapper = input.closest('.afe-upload');
      const dropzone = wrapper ? qs(wrapper, '.afe-upload__dropzone') : null;
      uploadState.set(input, {wrapper, dropzone});

      initExistingFileControls(input, form);

      input.addEventListener('input', event => event.stopPropagation());
      input.addEventListener('click', event => event.stopPropagation());
      input.addEventListener('change', event => {
        event.stopPropagation();
        updateUpload(input, form, {newSelection:true});
      });

      if (dropzone) {
        ['dragenter', 'dragover'].forEach(type => dropzone.addEventListener(type, event => {
          event.preventDefault();
          event.stopPropagation();
          dropzone.classList.add('is-dragover');
        }));

        ['dragleave', 'dragend'].forEach(type => dropzone.addEventListener(type, event => {
          event.preventDefault();
          event.stopPropagation();
          dropzone.classList.remove('is-dragover');
        }));

        dropzone.addEventListener('drop', event => {
          event.preventDefault();
          event.stopPropagation();
          dropzone.classList.remove('is-dragover');
          let files = Array.from(event.dataTransfer?.files || []);
          if (!input.multiple && files.length > 1) files = files.slice(0, 1);
          if (setUploadFiles(input, files)) updateUpload(input, form, {newSelection:true});
        });
      }

      renderUpload(input);
    });
  }

  function syncUploadFilesFromServer(form, rows) {
    if (!Array.isArray(rows)) return;

    const grouped = {};
    rows.forEach(row => {
      const key = String(row?.field_key || '');
      if (!key) return;
      (grouped[key] ||= []).push(row);
    });

    qsa(form, '.afe-upload[data-field-key]').forEach(wrapper => {
      const fieldKey = String(wrapper.dataset.fieldKey || '');
      const input = qs(wrapper, 'input[type="file"][data-afe-file-input]');
      if (!fieldKey || !input) return;

      clearNewFiles(input);

      let manifest = qsa(wrapper, 'input[type="hidden"]').find(el => el.name === `afe_existing_manifest[${fieldKey}]`);
      if (!manifest) {
        manifest = document.createElement('input');
        manifest.type = 'hidden';
        manifest.name = `afe_existing_manifest[${fieldKey}]`;
        manifest.value = '1';
        const dropzone = qs(wrapper, '.afe-upload__dropzone');
        if (dropzone) wrapper.insertBefore(manifest, dropzone);
        else wrapper.prepend(manifest);
      }

      qs(wrapper, '.afe-upload__existing')?.remove();
      const files = grouped[fieldKey] || [];

      if (files.length) {
        const container = document.createElement('div');
        container.className = 'afe-upload__existing';
        container.dataset.role = 'existing-files';
        container.setAttribute('aria-label', 'فایل‌های موجود');

        const head = document.createElement('div');
        head.className = 'afe-upload__existing-head';
        const title = document.createElement('strong');
        title.textContent = 'فایل موجود';
        const hint = document.createElement('span');
        hint.textContent = 'فایل‌هایی که می‌خواهید برای این فیلد باقی بمانند انتخاب کنید.';
        head.append(title, hint);
        container.appendChild(head);

        files.forEach(file => {
          const fileId = Number(file?.id || 0);
          if (!fileId) return;

          const fileName = String(file?.original_name || 'file');
          const mime = String(file?.mime || '');
          const url = String(file?.url || '');

          const row = document.createElement('div');
          row.className = 'afe-upload__existing-file is-selected';
          row.dataset.existingFile = '';
          row.dataset.fileId = String(fileId);

          const label = document.createElement('label');
          label.className = 'afe-upload__existing-choice';

          const toggle = document.createElement('input');
          toggle.type = 'checkbox';
          toggle.className = 'afe-upload__existing-toggle';
          toggle.dataset.existingFileToggle = '';
          toggle.name = `afe_keep_files[${fieldKey}][]`;
          toggle.value = String(fileId);
          toggle.checked = true;

          const check = document.createElement('span');
          check.className = 'afe-upload__existing-check';
          check.setAttribute('aria-hidden', 'true');

          let preview;
          if (mime.startsWith('image/') && url) {
            preview = document.createElement('span');
            preview.className = 'afe-upload__existing-thumb';
            const img = document.createElement('img');
            img.src = url;
            img.alt = '';
            img.loading = 'lazy';
            preview.appendChild(img);
          } else {
            preview = document.createElement('span');
            preview.className = 'afe-upload__existing-badge';
            const ext = fileName.includes('.') ? fileName.split('.').pop().toUpperCase() : 'FILE';
            preview.textContent = ext || 'FILE';
          }

          const info = document.createElement('span');
          info.className = 'afe-upload__existing-info';
          const strong = document.createElement('strong');
          strong.textContent = fileName;
          strong.title = fileName;
          const small = document.createElement('small');
          small.textContent = `${formatFileSize(Number(file?.size || 0))} · فایل موجود`;
          info.append(strong, small);

          const state = document.createElement('span');
          state.className = 'afe-upload__existing-state';
          state.dataset.role = 'existing-state';
          state.textContent = 'استفاده می‌شود';

          label.append(toggle, check, preview, info, state);

          const remove = document.createElement('button');
          remove.type = 'button';
          remove.className = 'afe-upload__remove-existing';
          remove.dataset.action = 'remove-existing';
          remove.setAttribute('aria-label', `حذف ${fileName}`);
          remove.textContent = 'حذف';

          row.append(label, remove);
          container.appendChild(row);
        });

        const selection = qs(wrapper, '[data-role="file-selection"]');
        if (selection) wrapper.insertBefore(container, selection);
        else wrapper.appendChild(container);
      }

      wrapper.dataset.existingCount = String(files.length);
      wrapper.dataset.existingSelectedCount = String(files.length);
      wrapper.dataset.afeExistingReady = '0';
      initExistingFileControls(input, form);
      renderUpload(input);
    });
  }

  document.addEventListener('click', event => {
    if (!activeCustomSelect) return;
    const state = customSelectState.get(activeCustomSelect);
    if (state && !state.root.contains(event.target)) closeCustomSelect(activeCustomSelect);
  });
  document.addEventListener('keydown', event => {
    if (event.key === 'Escape' && activeCustomSelect) closeCustomSelect(activeCustomSelect, true);
  });
  window.addEventListener('resize', () => {
    if (activeCustomSelect) {
      const state = customSelectState.get(activeCustomSelect);
      if (state) positionCustomSelect(state);
    }
  });

  function fieldValue(form, name) {
    const singleName = `afe_data[${name}]`;
    const multipleName = `afe_data[${name}][]`;
    // Compare the DOM name property directly instead of building a CSS selector.
    // This avoids CSS.escape/browser compatibility issues and is also safe for
    // field names containing characters that would need selector escaping.
    const nodes = qsa(form, '[name]').filter(node => node.name === singleName || node.name === multipleName);
    if (!nodes.length) return '';
    const first = nodes[0];
    if (first.type === 'radio') {
      const checked = nodes.find(n => n.checked);
      return checked ? checked.value : '';
    }
    if (first.tagName === 'SELECT' && first.multiple) {
      return Array.from(first.selectedOptions).map(o => o.value);
    }
    return first.value ?? '';
  }

  function matchConditions(form, conditions) {
    return (conditions || []).every(c => {
      const actual = fieldValue(form, c.field || '');
      const expected = c.value;
      switch (c.operator || '=') {
        case '=':
        case '==': return String(actual) === String(expected);
        case '!=': return String(actual) !== String(expected);
        case '>': return Number(actual) > Number(expected);
        case '>=': return Number(actual) >= Number(expected);
        case '<': return Number(actual) < Number(expected);
        case '<=': return Number(actual) <= Number(expected);
        case 'in': return (Array.isArray(expected) ? expected : [expected]).map(String).includes(String(actual));
        case 'contains': return Array.isArray(actual) ? actual.map(String).includes(String(expected)) : String(actual).includes(String(expected));
        case 'empty': return actual === '' || actual === null || (Array.isArray(actual) && !actual.length);
        case 'not_empty': return !(actual === '' || actual === null || (Array.isArray(actual) && !actual.length));
        default: return false;
      }
    });
  }

  function applyConditions(form) {
    qsa(form, '[data-conditions]').forEach(wrapper => {
      let rules = [];
      try { rules = JSON.parse(wrapper.dataset.conditions || '[]'); } catch (_) {}
      let visible = true;
      let forceRequired = null;
      rules.forEach(rule => {
        const matched = matchConditions(form, rule.when || []);
        switch (rule.effect) {
          case 'show': visible = matched; break;
          case 'hide': visible = !matched; break;
          case 'required': if (matched) forceRequired = true; break;
          case 'optional': if (matched) forceRequired = false; break;
        }
      });
      wrapper.hidden = !visible;
      const defaultRequired = wrapper.dataset.required === '1';
      const effectiveRequired = visible && (forceRequired === null ? defaultRequired : forceRequired);
      wrapper.dataset.effectiveRequired = effectiveRequired ? '1' : '0';
      const isRepeater = wrapper.classList.contains('afe-repeater');
      qsa(wrapper, 'input,select,textarea').forEach(input => {
        if (!isRepeater && input.type !== 'file') input.required = effectiveRequired;
        input.disabled = !visible;
      });
    });
    refreshCustomSelects(form);
  }

  let validationTargetSequence = 0;

  function setError(wrapper, message) {
    const box = qs(wrapper, '.afe-field-error');
    if (box) box.textContent = message || '';
    wrapper.classList.toggle('has-error', Boolean(message));
    if (message) {
      wrapper.setAttribute('aria-invalid', 'true');
    } else {
      wrapper.removeAttribute('aria-invalid');
    }
  }

  function errorTargetId(wrapper) {
    if (!wrapper.id) {
      validationTargetSequence += 1;
      wrapper.id = `afe-validation-target-${validationTargetSequence}`;
    }
    return wrapper.id;
  }

  function cleanFieldLabel(wrapper) {
    const label = qs(wrapper, '.afe-label');
    let text = (label?.textContent || wrapper.dataset.field || 'فیلد فرم').replace(/\s*\*\s*$/, '').trim();
    const row = wrapper.closest('.afe-repeater-row');
    const number = row ? qs(row, '.afe-repeater-number')?.textContent?.trim() : '';
    if (number) text += ` — ردیف ${number}`;
    return text;
  }

  function stepInfo(step) {
    const index = Number(step?.dataset.step || 0);
    const title = qs(step, '.afe-step-heading h2')?.textContent?.trim() || `مرحله ${index + 1}`;
    return {index, title};
  }

  function collectDomErrors(form) {
    return qsa(form, '.afe-field.has-error').map(wrapper => {
      const box = qs(wrapper, '.afe-field-error');
      const message = box?.textContent?.trim();
      const step = wrapper.closest('.afe-step');
      if (!message || !step) return null;
      const meta = stepInfo(step);
      return {
        stepIndex: meta.index,
        stepTitle: meta.title,
        fieldLabel: cleanFieldLabel(wrapper),
        message,
        targetId: errorTargetId(wrapper),
      };
    }).filter(Boolean);
  }

  function updateStepErrorIndicators(form) {
    const steps = qsa(form, '.afe-step');
    const progress = qsa(form, '.afe-progress-step');
    steps.forEach((step, index) => {
      const count = qsa(step, '.afe-field.has-error').filter(wrapper => {
        const box = qs(wrapper, '.afe-field-error');
        return Boolean(box?.textContent?.trim());
      }).length;
      const button = progress[index];
      if (!button) return;
      button.classList.toggle('has-error', count > 0);
      if (count > 0) {
        button.dataset.errorCount = String(count);
        const title = qs(step, '.afe-step-heading h2')?.textContent?.trim() || `مرحله ${index + 1}`;
        button.setAttribute('aria-label', `${title} — ${count} خطا`);
      } else {
        delete button.dataset.errorCount;
        const title = qs(step, '.afe-step-heading h2')?.textContent?.trim() || `مرحله ${index + 1}`;
        button.setAttribute('aria-label', title);
      }
    });
  }

  function renderValidationSummary(form, errors, message = 'لطفاً خطاهای زیر را اصلاح کنید.', options = {}) {
    const result = qs(form, '.afe-form-result');
    if (!result) return;
    if (!errors.length) {
      const summary = qs(result, '.afe-validation-summary');
      summary?.remove();
      return;
    }

    const groups = new Map();
    errors.forEach(error => {
      const key = `${error.stepIndex}:${error.stepTitle}`;
      if (!groups.has(key)) groups.set(key, {stepIndex:error.stepIndex, stepTitle:error.stepTitle, items:[]});
      groups.get(key).items.push(error);
    });

    const groupHtml = Array.from(groups.values()).map(group => {
      const items = group.items.map(error => {
        const target = error.targetId
          ? ` data-afe-error-target="${escapeAttr(error.targetId)}" data-afe-error-step="${error.stepIndex}"`
          : '';
        return `<li><button type="button" class="afe-validation-summary__item"${target}><span class="afe-validation-summary__field">${escapeHtml(error.fieldLabel)}</span><span class="afe-validation-summary__message">${escapeHtml(error.message)}</span></button></li>`;
      }).join('');
      return `<section class="afe-validation-summary__group"><h4><span>مرحله ${group.stepIndex + 1}</span>${escapeHtml(group.stepTitle)}</h4><ul>${items}</ul></section>`;
    }).join('');

    const stepCount = groups.size;
    const errorCount = errors.length;
    const first = errors.find(error => error.targetId);
    const firstButton = first
      ? `<button type="button" class="afe-btn afe-btn-secondary afe-validation-summary__first" data-afe-error-target="${escapeAttr(first.targetId)}" data-afe-error-step="${first.stepIndex}">رفتن به اولین خطا</button>`
      : '';

    result.innerHTML = `<div class="afe-validation-summary" role="alert" aria-live="assertive"><div class="afe-validation-summary__head"><div><strong>${escapeHtml(message)}</strong><p>${errorCount} خطا در ${stepCount} مرحله پیدا شد.</p></div>${firstButton}</div>${groupHtml}</div>`;
    if (options.scroll !== false) result.scrollIntoView({behavior:'smooth', block:'center'});
  }

  function latinDigits(value) {
    return String(value ?? '').replace(/[۰-۹]/g, d => '۰۱۲۳۴۵۶۷۸۹'.indexOf(d)).replace(/[٠-٩]/g, d => '٠١٢٣٤٥٦٧٨٩'.indexOf(d));
  }

  function escapeRegexClass(value) {
    return String(value || '').replace(/[\\\]\[\-^]/g, '\\$&');
  }

  function normalizeDateMask(control, value) {
    const mask = String(control.dataset.afeDateMask || '');
    if (!mask || control.type === 'date') return value;
    const digits = latinDigits(value).replace(/\D+/g, '').slice(0, 8);
    const separator = mask.includes('/') ? '/' : '-';
    if (digits.length <= 4) return digits;
    if (digits.length <= 6) return `${digits.slice(0, 4)}${separator}${digits.slice(4)}`;
    return `${digits.slice(0, 4)}${separator}${digits.slice(4, 6)}${separator}${digits.slice(6)}`;
  }

  const inputMaskPatternCache = new Map();

  function inputMaskNodes(pattern) {
    pattern = String(pattern || '').trim();
    if (!pattern) return [];
    if (inputMaskPatternCache.has(pattern)) return inputMaskPatternCache.get(pattern);
    const nodes = [];
    let escaped = false;
    Array.from(pattern).forEach(char => {
      if (escaped) { nodes.push({type:'literal', value:char}); escaped = false; return; }
      if (char === '\\') { escaped = true; return; }
      nodes.push(['9','A','*'].includes(char) ? {type:'slot', value:char} : {type:'literal', value:char});
    });
    if (escaped) nodes.length = 0;
    inputMaskPatternCache.set(pattern, nodes);
    return nodes;
  }

  function inputMaskCharMatches(char, token) {
    if (token === '9') return /^[0-9]$/.test(char);
    try {
      if (token === 'A') return /^\p{L}$/u.test(char);
      if (token === '*') return /^(?:\p{L}|[0-9])$/u.test(char);
    } catch (_) {
      if (token === 'A') return /^[A-Za-z\u0621-\u06FC]$/.test(char);
      if (token === '*') return /^[A-Za-z0-9\u0621-\u06FC]$/.test(char);
    }
    return false;
  }

  function unmaskInputValue(value, pattern) {
    const nodes = inputMaskNodes(pattern);
    if (!nodes.length) return latinDigits(value);
    const chars = Array.from(latinDigits(value));
    let index = 0;
    let out = '';
    nodes.forEach(node => {
      if (node.type === 'literal') {
        if (chars[index] === node.value) index += 1;
        return;
      }
      while (index < chars.length) {
        const char = chars[index++];
        if (inputMaskCharMatches(char, node.value)) { out += char; break; }
      }
    });
    return out;
  }

  function formatInputMask(value, pattern) {
    const nodes = inputMaskNodes(pattern);
    if (!nodes.length) return value;
    const clean = Array.from(unmaskInputValue(value, pattern));
    const totalSlots = nodes.filter(node => node.type === 'slot').length;
    let slot = 0;
    let out = '';
    for (const node of nodes) {
      if (node.type === 'slot') {
        if (clean[slot] === undefined) break;
        out += clean[slot++];
      } else if (slot > 0 && slot < totalSlots && clean[slot] !== undefined) {
        out += node.value;
      }
    }
    return out;
  }

  function inputMaskCaretForSlots(pattern, slotCount) {
    if (slotCount <= 0) return 0;
    const nodes = inputMaskNodes(pattern);
    let slots = 0;
    let position = 0;
    for (const node of nodes) {
      position += String(node.value).length;
      if (node.type === 'slot') slots += 1;
      if (slots >= slotCount) break;
    }
    return position;
  }

  function normalizedControlValue(control) {
    const pattern = String(control.dataset.afeInputMask || '');
    return pattern ? unmaskInputValue(control.value, pattern) : latinDigits(control.value);
  }

  function normalizeConstrainedInput(control, initialize = false) {
    if (!(control instanceof HTMLInputElement || control instanceof HTMLTextAreaElement)) return;
    const inputMask = String(control.dataset.afeInputMask || '');
    const activeCaret = inputMask && control === document.activeElement && control instanceof HTMLInputElement
      ? unmaskInputValue(control.value.slice(0, Number(control.selectionStart ?? control.value.length)), inputMask).length
      : null;
    let value = latinDigits(control.value);
    const mode = String(control.dataset.afeCharacterMode || 'normal');
    const allowedExtra = escapeRegexClass(control.dataset.afeAllowedExtra || '');
    const forbiddenExtra = escapeRegexClass(control.dataset.afeForbiddenExtra || '');

    if (control.dataset.afeDigitsOnly === '1') value = value.replace(/\D+/g, '');

    const prefix = String(control.dataset.afeFixedPrefix || '');
    if (prefix) {
      if (value.startsWith(prefix)) {
        value = prefix + value.slice(prefix.length).replace(/\D+/g, '');
      } else {
        let suffix = value.replace(/\D+/g, '');
        if (suffix.startsWith('9') && prefix === '09') suffix = suffix.slice(1);
        else if (suffix.startsWith('0')) suffix = suffix.slice(1);
        value = prefix + suffix;
      }
    }

    if (mode === 'persian') value = value.replace(new RegExp(`[^\\u0621-\\u063A\\u0641-\\u064A\\u066E-\\u06D3\\u06FA-\\u06FC\\u200C\\s${allowedExtra}]`, 'gu'), '');
    else if (mode === 'english') value = value.replace(new RegExp(`[^A-Za-z\\s${allowedExtra}]`, 'g'), '');
    else if (mode === 'alnum') value = value.replace(new RegExp(`[^A-Za-z0-9\\u0621-\\u063A\\u0641-\\u064A\\u066E-\\u06D3\\u06FA-\\u06FC\\u200C\\s${allowedExtra}]`, 'gu'), '');
    else if (mode === 'digits') value = value.replace(new RegExp(`[^0-9${allowedExtra}]`, 'g'), '');

    if (forbiddenExtra) value = value.replace(new RegExp(`[${forbiddenExtra}]`, 'gu'), '');
    value = normalizeDateMask(control, value);
    if (inputMask) value = formatInputMask(value, inputMask);

    const maxLength = Number(control.getAttribute('maxlength') || 0);
    if (maxLength > 0 && value.length > maxLength) value = value.slice(0, maxLength);
    if (control.value !== value || (initialize && !control.value && prefix)) control.value = value || prefix;
    if (activeCaret !== null && control instanceof HTMLInputElement) {
      const caret = Math.min(control.value.length, inputMaskCaretForSlots(inputMask, activeCaret));
      try { control.setSelectionRange(caret, caret); } catch (_) {}
    }
  }

  function validateConstrainedControl(control) {
    if (!(control instanceof HTMLInputElement || control instanceof HTMLTextAreaElement)) return;
    control.setCustomValidity('');
    const value = normalizedControlValue(control);
    if (!value) return;

    const exact = Number(control.dataset.afeValueExactLength || 0);
    const min = Number(control.dataset.afeValueMinLength || 0);
    const max = Number(control.dataset.afeValueMaxLength || 0);
    const length = Array.from(value).length;
    if (exact > 0 && length !== exact) {
      control.setCustomValidity(`طول مقدار باید دقیقاً ${exact} کاراکتر باشد.`);
      return;
    }
    if (min > 0 && length < min) {
      control.setCustomValidity(`حداقل طول مقدار ${min} کاراکتر است.`);
      return;
    }
    if (max > 0 && length > max) {
      control.setCustomValidity(`حداکثر طول مقدار ${max} کاراکتر است.`);
      return;
    }

    const normalizedPattern = String(control.dataset.afeNormalizedPattern || '');
    if (normalizedPattern) {
      try {
        const regex = new RegExp(`^(?:${normalizedPattern})$`, 'u');
        if (!regex.test(value)) {
          control.setCustomValidity(String(control.dataset.afeInvalidMessage || 'مقدار واردشده با قالب مورد انتظار مطابقت ندارد.'));
          return;
        }
      } catch (_) {}
    }

    const customPattern = String(control.dataset.afeCustomRegex || '');
    if (!customPattern) return;
    try {
      const flags = String(control.dataset.afeCustomRegexFlags || 'u').replace(/[^imsu]/g, '');
      const regex = new RegExp(customPattern, flags);
      if (!regex.test(value)) control.setCustomValidity(String(control.dataset.afeCustomRegexMessage || 'مقدار واردشده معتبر نیست.'));
    } catch (error) {
      // PHP is the source of truth. Unsupported PCRE syntax in JS simply skips UX validation.
      control.setCustomValidity('');
    }
  }

  function initConstrainedInputs(root) {
    qsa(root, 'input[data-afe-digits-only="1"],input[data-afe-fixed-prefix],input[data-afe-character-mode],textarea[data-afe-character-mode],input[data-afe-date-mask],input[data-afe-custom-regex],textarea[data-afe-custom-regex],input[data-afe-input-mask]').forEach(control => {
      if (control.dataset.afeConstraintReady === '1') return;
      control.dataset.afeConstraintReady = '1';
      normalizeConstrainedInput(control, true);
      validateConstrainedControl(control);
      control.addEventListener('input', () => { normalizeConstrainedInput(control); validateConstrainedControl(control); });
      control.addEventListener('blur', () => { normalizeConstrainedInput(control); validateConstrainedControl(control); });
      control.addEventListener('keydown', event => {
        if (control.dataset.afeDatePickerOnly === '1' && !['Tab','Enter','Escape'].includes(event.key)) {
          event.preventDefault();
          return;
        }
        const prefix = String(control.dataset.afeFixedPrefix || '');
        if (!prefix || !(control instanceof HTMLInputElement)) return;
        const start = Number(control.selectionStart ?? 0);
        if ((event.key === 'Backspace' && start <= prefix.length) || (event.key === 'Delete' && start < prefix.length)) {
          event.preventDefault();
          control.setSelectionRange(prefix.length, prefix.length);
        }
      });
      control.addEventListener('paste', event => {
        if (control.dataset.afeDatePickerOnly === '1') event.preventDefault();
      });
      control.addEventListener('focus', () => {
        const prefix = String(control.dataset.afeFixedPrefix || '');
        if (prefix && control instanceof HTMLInputElement && Number(control.selectionStart ?? 0) < prefix.length) control.setSelectionRange(prefix.length, prefix.length);
      });
    });
  }

  function validateStep(form, step, options = {}) {
    const shouldScroll = options.scroll !== false;
    let ok = true;
    qsa(step, '.afe-field:not([hidden])').forEach(wrapper => {
      setError(wrapper, '');
      const required = (wrapper.dataset.effectiveRequired || wrapper.dataset.required) === '1';
      const fileInput = qs(wrapper, 'input[type="file"][data-afe-file-input]');
      if (fileInput && !fileInput.disabled) {
        const uploadError = uploadValidationMessage(fileInput);
        if (uploadError) {
          setError(wrapper, uploadError);
          ok = false;
          return;
        }
      }
      const nativeControls = qsa(wrapper, 'input:not([type="hidden"]),select,textarea')
        .filter(el => !el.disabled && !el.closest('.afe-custom-select'));
      const invalidControl = nativeControls.find(el => {
        if (el.type === 'file' || el.type === 'radio' || el.type === 'checkbox') return false;
        const hasValue = String(el.value || '').trim() !== '';
        return hasValue && typeof el.checkValidity === 'function' && !el.checkValidity();
      });
      if (invalidControl) {
        const message = invalidControl.validity?.customError
          ? invalidControl.validationMessage
          : (invalidControl.dataset.afeInvalidMessage || 'مقدار واردشده با قالب مورد انتظار مطابقت ندارد.');
        setError(wrapper, message);
        ok = false;
        return;
      }
      if (!required) return;
      const repeater = wrapper.classList.contains('afe-repeater');
      if (repeater) {
        const min = Number(wrapper.dataset.min || 1);
        const count = qsa(wrapper, '.afe-repeater-items > .afe-repeater-row').length;
        if (count < min) { setError(wrapper, 'حداقل یک مورد باید اضافه شود.'); ok = false; }
        return;
      }
      const controls = qsa(wrapper, 'input:not([type="hidden"]),select,textarea')
        .filter(el => !el.disabled && !el.closest('.afe-custom-select'));
      if (!controls.length) return;
      const radio = controls[0].type === 'radio';
      const hasValue = radio ? controls.some(el => el.checked) : controls.some(el => {
        if (el.type === 'file') {
          const upload = el.closest('.afe-upload');
          return (el.files && el.files.length) || selectedExistingCount(upload) > 0;
        }
        return String(el.value || '').trim() !== '';
      });
      if (!hasValue) { setError(wrapper, 'تکمیل این فیلد الزامی است.'); ok = false; }
    });
    updateStepErrorIndicators(form);
    if (!ok && shouldScroll) {
      const first = qs(step, '.has-error');
      first?.scrollIntoView({behavior:'smooth', block:'center'});
    }
    return ok;
  }

  function updateRepeater(wrapper) {
    const rows = qsa(wrapper, '.afe-repeater-items > .afe-repeater-row');
    rows.forEach((row, i) => {
      row.dataset.index = String(i);
      const n = qs(row, '.afe-repeater-number');
      if (n) n.textContent = String(i + 1);
    });
    const count = qs(wrapper, '.afe-repeater-count');
    if (count) count.textContent = `${rows.length} مورد`;
    const add = qs(wrapper, '.afe-repeater-add');
    if (add) add.disabled = rows.length >= Number(wrapper.dataset.max || 50);
    qsa(wrapper, '.afe-repeater-remove').forEach(btn => {
      btn.disabled = rows.length <= Number(wrapper.dataset.min || 0);
    });
  }

  function initRepeaters(form) {
    qsa(form, '.afe-repeater').forEach(wrapper => {
      updateRepeater(wrapper);
      wrapper.addEventListener('click', e => {
        const add = e.target.closest('.afe-repeater-add');
        if (add) {
          const rows = qs(wrapper, '.afe-repeater-items');
          const current = qsa(rows, '.afe-repeater-row').length;
          if (current >= Number(wrapper.dataset.max || 50)) return;
          const template = qs(wrapper, '.afe-repeater-template');
          const html = template.innerHTML.replaceAll('__INDEX__', String(current));
          const holder = document.createElement('div');
          holder.innerHTML = html.trim();
          const row = holder.firstElementChild;
          rows.appendChild(row);
          initCustomSelects(row);
          initFileUploads(row);
          initConstrainedInputs(row);
          updateRepeater(wrapper);
          row.scrollIntoView({behavior:'smooth', block:'nearest'});
          return;
        }
        const remove = e.target.closest('.afe-repeater-remove');
        if (remove) {
          const rows = qsa(wrapper, '.afe-repeater-items > .afe-repeater-row');
          if (rows.length <= Number(wrapper.dataset.min || 0)) return;
          remove.closest('.afe-repeater-row')?.remove();
          // Reindex names to keep PHP arrays compact.
          qsa(wrapper, '.afe-repeater-items > .afe-repeater-row').forEach((row, i) => {
            qsa(row, '[name]').forEach(input => {
              input.name = input.name.replace(/\]\[\d+\]\[/, `][${i}][`);
            });
          });
          updateRepeater(wrapper);
        }
      });
    });
  }

  function controlFieldKey(control) {
    if (!(control instanceof Element)) return '';
    const wrapper = control.closest('.afe-field[data-field]');
    if (wrapper?.dataset.field) return wrapper.dataset.field;

    const name = control.getAttribute('name') || '';
    const match = name.match(/^afe_data\[([^\]]+)\]/);
    return match ? match[1] : '';
  }

  function dependencyParentValue(form, select, parentName) {
    const row = select.closest('.afe-repeater-row');
    if (row) {
      const suffix = `[${parentName}]`;
      const parent = qsa(row, '[name]').find(control => String(control.name || '').endsWith(suffix));
      if (parent) {
        if (parent.tagName === 'SELECT' && parent.multiple) return Array.from(parent.selectedOptions).map(o => o.value);
        return parent.value ?? '';
      }
    }
    return fieldValue(form, parentName);
  }

  async function loadDependent(form, select) {
    const parentName = select.dataset.dependsOn;
    const level = select.dataset.sourceLevel;
    if (!parentName || !level) return;

    const parentValue = dependencyParentValue(form, select, parentName);
    const requestId = String((Number(select.dataset.afeGeoRequest || 0) + 1));
    select.dataset.afeGeoRequest = requestId;

    select.innerHTML = '<option value="">در حال دریافت...</option>';
    select.disabled = true;
    select.setAttribute('aria-busy', 'true');
    refreshCustomSelect(select, true);

    if (!parentValue || Array.isArray(parentValue)) {
      select.innerHTML = '<option value="">ابتدا گزینه بالادستی را انتخاب کنید</option>';
      select.disabled = false;
      select.removeAttribute('aria-busy');
      refreshCustomSelect(select, true);
      select.dispatchEvent(new Event('change', {bubbles:true}));
      select.dispatchEvent(new CustomEvent('afe:dependent-updated', {bubbles:true, detail:{level, parent:''}}));
      return;
    }

    const data = new URLSearchParams({
      action: 'afe_geo_options',
      level,
      parent: String(parentValue)
    });
    // The geo endpoint is read-only public data. Sending the nonce when present is
    // harmless, but the server no longer requires it so cached Elementor pages do
    // not lose their county/district cascade when a WordPress nonce expires.
    if (cfg.geoNonce) data.set('nonce', cfg.geoNonce);

    let loaded = false;
    try {
      const res = await fetch(cfg.ajaxUrl, {
        method: 'POST',
        headers: {'Content-Type':'application/x-www-form-urlencoded; charset=UTF-8'},
        body: data.toString(),
        credentials: 'same-origin',
        cache: 'no-store'
      });
      const json = await res.json();
      if (select.dataset.afeGeoRequest !== requestId) return;
      if (!res.ok || !json.success) {
        throw new Error(json?.data?.message || `HTTP ${res.status}`);
      }

      const options = json?.data?.options || {};
      select.innerHTML = '<option value="">انتخاب کنید</option>';
      Object.entries(options).forEach(([value,label]) => {
        const option = document.createElement('option');
        option.value = String(value);
        option.textContent = String(label);
        select.appendChild(option);
      });

      if (!Object.keys(options).length) {
        select.innerHTML = '<option value="">موردی برای گزینه انتخاب‌شده یافت نشد</option>';
      }
      loaded = true;
    } catch (error) {
      if (select.dataset.afeGeoRequest !== requestId) return;
      console.error('[AFE] Geography options request failed:', error);
      select.innerHTML = '<option value="">خطا در دریافت اطلاعات؛ دوباره تلاش کنید</option>';
    } finally {
      if (select.dataset.afeGeoRequest !== requestId) return;
      select.disabled = false;
      select.removeAttribute('aria-busy');
      refreshCustomSelect(select, true);

      // Notify the next level (e.g. county -> district) without reloading this
      // select. Native change remains the source of truth for conditional logic.
      select.dispatchEvent(new Event('change', {bubbles:true}));
      select.dispatchEvent(new CustomEvent('afe:dependent-updated', {
        bubbles:true,
        detail:{level, parent:String(parentValue), loaded}
      }));
    }
  }

  function initDependencies(form) {
    if (form.dataset.afeDependenciesReady === '1') return;
    form.dataset.afeDependenciesReady = '1';

    // Delegation is intentional: custom selects, Elementor re-renders and repeater
    // rows can all be created after initial form boot. Listening on the form means
    // every real <select> change can drive its dependants without fragile bindings.
    form.addEventListener('change', event => {
      const source = event.target;
      if (!(source instanceof HTMLInputElement || source instanceof HTMLSelectElement || source instanceof HTMLTextAreaElement)) return;
      const fieldKey = controlFieldKey(source);
      if (!fieldKey) return;

      const sourceRow = source.closest('.afe-repeater-row');
      qsa(form, 'select[data-depends-on]').forEach(dependent => {
        if (dependent.dataset.dependsOn !== fieldKey) return;
        const dependentRow = dependent.closest('.afe-repeater-row');
        if (sourceRow ? dependentRow !== sourceRow : dependentRow !== null) return;
        loadDependent(form, dependent);
      });
    });
  }

  function initUniqueGroups(form) {
    const groups = {};
    qsa(form, '[data-unique-group]').forEach(wrapper => {
      (groups[wrapper.dataset.uniqueGroup] ||= []).push(qs(wrapper, 'select,input'));
    });
    Object.values(groups).forEach(inputs => {
      const update = () => {
        const selected = inputs.map(i => i?.value).filter(Boolean);
        inputs.forEach(input => {
          if (!input || input.tagName !== 'SELECT') return;
          Array.from(input.options).forEach(o => {
            o.disabled = o.value && o.value !== input.value && selected.includes(o.value);
          });
          refreshCustomSelect(input, true);
        });
      };
      inputs.forEach(i => i?.addEventListener('change', update));
      update();
    });
  }

  function showServerErrors(form, errors, message = 'لطفاً خطاهای فرم را اصلاح کنید.') {
    qsa(form, '.afe-field').forEach(w => setError(w, ''));
    const unmapped = [];
    Object.entries(errors || {}).forEach(([key,errorMessage]) => {
      const root = key.split('.')[0];
      const wrapper = qsa(form, '.afe-field[data-field]').find(field => field.dataset.field === root);
      if (wrapper) {
        setError(wrapper, String(errorMessage));
      } else {
        const lastStep = qsa(form, '.afe-step').at(-1);
        const meta = stepInfo(lastStep);
        unmapped.push({
          stepIndex: meta.index,
          stepTitle: meta.title,
          fieldLabel: key && !key.startsWith('_') ? key : 'اطلاعات فرم',
          message: String(errorMessage),
          targetId: '',
        });
      }
    });
    updateStepErrorIndicators(form);
    const mapped = collectDomErrors(form);
    renderValidationSummary(form, [...mapped, ...unmapped], message);
    return mapped.length + unmapped.length;
  }

  function ensureEditFields(form, data) {
    const id = data.submission_id;
    if (id && !qs(form, '[name="_afe_submission_id"]')) {
      const input = document.createElement('input');
      input.type='hidden'; input.name='_afe_submission_id'; input.value=String(id); form.appendChild(input);
    }
    if (data.edit_token && !qs(form, '[name="_afe_edit_token"]')) {
      const input = document.createElement('input');
      input.type='hidden'; input.name='_afe_edit_token'; input.value=data.edit_token; form.appendChild(input);
    }
  }

  async function send(form, intent) {
    const result = qs(form, '.afe-form-result');
    const buttons = qsa(form, 'button');
    buttons.forEach(b => b.disabled = true);
    form.classList.add('is-loading');
    if (result) result.innerHTML = '<div class="afe-notice afe-notice-info">در حال ذخیره اطلاعات...</div>';
    const fd = new FormData(form);
    fd.set('afe_intent', intent);

    // Send a lightweight client-side upload manifest in addition to the actual
    // multipart file body. PHP can otherwise turn transport/configuration
    // failures (upload_max_filesize, file_uploads=Off, temporary directory
    // problems, security middleware) into an empty $_FILES entry, which used to
    // surface incorrectly as "this field is required". The manifest contains
    // metadata only; file bytes are still sent exclusively by FormData.
    qsa(form, 'input[type="file"][data-afe-file-input]').forEach(input => {
      const wrapper = input.closest('.afe-upload');
      const fieldKey = String(wrapper?.dataset.fieldKey || '');
      if (!fieldKey) return;
      const files = Array.from(input.files || []);

      // Normalize multipart transport on the client too. Even a cached form that
      // still renders the legacy afe_files[field][] name is rewritten into one
      // flat top-level multipart key per AFE FileField before fetch(). This avoids
      // nested $_FILES normalization bugs in PHP/security middleware.
      if (input.name) fd.delete(input.name);
      const uploadName = String(input.dataset.uploadName || `afe_upload_${fieldKey}`);
      files.forEach(file => fd.append(`${uploadName}[]`, file, file.name));

      fd.set(`afe_new_file_manifest[${fieldKey}]`, JSON.stringify({
        count: files.length,
        files: files.map(file => ({
          name: String(file.name || ''),
          size: Number(file.size || 0),
          type: String(file.type || ''),
        })),
      }));
    });
    try {
      const res = await fetch(cfg.ajaxUrl, {method:'POST', body:fd, credentials:'same-origin'});
      const json = await res.json();
      if (!json.success) {
        const payload = json.data || {};
        const count = showServerErrors(form, payload.errors || {}, payload.message || 'خطا در ثبت اطلاعات');
        if (!count && result) {
          const duplicateLink = payload.code === 'duplicate' && payload.edit_url
            ? `<a class="afe-result-link" href="${escapeAttr(payload.edit_url)}">مشاهده یا ادامه ثبت قبلی</a>`
            : '';
          result.innerHTML = `<div class="afe-notice afe-notice-error">${escapeHtml(payload.message || 'خطا در ثبت اطلاعات')}${duplicateLink}</div>`;
        }
        if (String(payload.code || '').startsWith('captcha')) {
          qs(form, '[data-afe-captcha-refresh]')?.click();
        }
        return false;
      }
      ensureEditFields(form, json.data);
      syncUploadFilesFromServer(form, json.data.files || []);
      // Redirect Action is resolved on the server only after all server-side Actions
      // have finished. The first effective Redirect wins and takes precedence over
      // the default lock/edit navigation below.
      if (json.data.redirect_url) {
        window.location.assign(new URL(json.data.redirect_url, window.location.href).toString());
        return true;
      }
      // A lock-after-submit response must immediately enter the persisted locked view.
      // Leaving the live preview DOM mounted would incorrectly keep the "back/edit" button
      // visible and the edit-request panel would not exist until a manual refresh.
      if (json.data.locked && json.data.edit_url) {
        const lockedUrl = new URL(json.data.edit_url, window.location.href);
        lockedUrl.searchParams.set('afe_submitted', '1');
        window.location.assign(lockedUrl.toString());
        return true;
      }
      if (result) {
        const edit = json.data.edit_url ? `<a class="afe-result-link" href="${escapeAttr(json.data.edit_url)}">${json.data.locked ? 'مشاهده اطلاعات ثبت‌شده' : 'لینک ویرایش و ادامه فرم'}</a>` : '';
        result.innerHTML = `<div class="afe-success"><strong>${escapeHtml(json.data.message)}</strong><span>کد رهگیری: <b dir="ltr">${escapeHtml(json.data.tracking_code)}</b></span>${edit}</div>`;
        result.scrollIntoView({behavior:'smooth', block:'center'});
      }
      return true;
    } catch (e) {
      if (result) result.innerHTML = '<div class="afe-notice afe-notice-error">ارتباط با سرور برقرار نشد. دوباره تلاش کنید.</div>';
      return false;
    } finally {
      buttons.forEach(b => b.disabled = false);
      form.classList.remove('is-loading');
      qsa(form, '.afe-repeater').forEach(updateRepeater);
      refreshCustomSelects(form);
    }
  }

  function escapeHtml(value) {
    const d = document.createElement('div'); d.textContent = String(value ?? ''); return d.innerHTML;
  }
  function escapeAttr(value) { return escapeHtml(value).replaceAll('"','&quot;'); }

  function previewFieldWrapper(form, fieldName) {
    return qsa(form, '.afe-field[data-field]').find(el => el.dataset.field === fieldName) || null;
  }

  function previewControlText(wrapper) {
    if (!wrapper) return '—';
    const upload = qs(wrapper, '.afe-upload');
    if (upload) {
      const names = [];
      qsa(upload, '[data-existing-file].is-selected .afe-upload__existing-info strong').forEach(el => {
        const text = String(el.textContent || '').trim(); if (text) names.push(text);
      });
      const input = qs(upload, 'input[type="file"]');
      Array.from(input?.files || []).forEach(file => names.push(file.name));
      return names.length ? names.join('، ') : '—';
    }
    const checked = qs(wrapper, 'input[type="radio"]:checked');
    if (checked) {
      const label = checked.closest('label');
      return String(label?.textContent || checked.value || '').trim();
    }
    const select = qs(wrapper, 'select');
    if (select) {
      const selected = Array.from(select.selectedOptions || []).filter(o => o.value);
      return selected.length ? selected.map(o => String(o.textContent || o.value).trim()).join('، ') : '—';
    }
    const input = qs(wrapper, 'textarea,input:not([type="hidden"]):not([type="file"])');
    const value = String(input?.value || '').trim();
    return value || '—';
  }

  function previewRepeaterHtml(wrapper) {
    if (!wrapper) return '<span class="afe-preview-empty">ثبت نشده</span>';
    const rows = qsa(wrapper, '.afe-repeater-items > .afe-repeater-row');
    if (!rows.length) return '<span class="afe-preview-empty">ثبت نشده</span>';
    const fieldHeaders = qsa(rows[0], '.afe-field').map(field => cleanFieldLabel(field));
    const head = fieldHeaders.map(label => `<th>${escapeHtml(label)}</th>`).join('');
    const body = rows.map((row, index) => {
      const cells = qsa(row, '.afe-field').map(field => `<td>${escapeHtml(previewControlText(field))}</td>`).join('');
      return `<tr><td>${index + 1}</td>${cells}</tr>`;
    }).join('');
    return `<div class="afe-preview-repeater"><table><thead><tr><th>ردیف</th>${head}</tr></thead><tbody>${body}</tbody></table></div>`;
  }

  function updatePreview(form) {
    qsa(form, '[data-afe-preview-value]').forEach(target => {
      const name = String(target.dataset.afePreviewValue || '');
      if (!name) return;
      const wrapper = previewFieldWrapper(form, name);
      const block = target.closest('.afe-preview-field');
      if (block) block.hidden = !wrapper || wrapper.hidden;
      if (!wrapper || wrapper.hidden) return;
      if (target.dataset.previewType === 'repeater') target.innerHTML = previewRepeaterHtml(wrapper);
      else target.textContent = previewControlText(wrapper);
    });
  }

  function initReadonly(form) {
    if (form.dataset.afeReadonly !== '1') return;
    qsa(form, 'input,textarea,select').forEach(control => {
      if (control.type === 'hidden') return;
      if (control instanceof HTMLInputElement && ['text','tel','email','url','number','date'].includes(control.type)) control.readOnly = true;
      else if (control instanceof HTMLTextAreaElement) control.readOnly = true;
      else control.disabled = true;
    });
    qsa(form, '.afe-save-draft,.afe-submit,.afe-repeater-add,.afe-repeater-remove,.afe-upload__browse,.afe-upload__remove-existing').forEach(el => el.remove());
    form.classList.add('is-readonly');
  }

  function initCaptchaRefresh() {
    qsa(document, '[data-afe-captcha-refresh]').forEach(button => {
      if (button.dataset.afeReady === '1') return;
      button.dataset.afeReady = '1';
      button.addEventListener('click', async () => {
        const box = button.closest('[data-afe-captcha]');
        if (!box) return;
        const question = qs(box, '[data-afe-captcha-question]');
        const token = qs(box, 'input[name="_afe_captcha_token"]');
        const answer = qs(box, 'input[name="_afe_captcha_answer"]');
        button.disabled = true;
        button.classList.add('is-loading');
        try {
          const fd = new FormData();
          fd.set('action', 'afe_refresh_captcha');
          const res = await fetch(cfg.ajaxUrl, {method:'POST', body:fd, credentials:'same-origin'});
          const json = await res.json();
          if (!json.success || !json.data?.token) throw new Error(json?.data?.message || 'خطا در تولید کپچای جدید');
          if (question) question.textContent = json.data.question || '';
          if (token) token.value = json.data.token;
          if (answer) { answer.value = ''; answer.focus(); }
        } catch (error) {
          window.alert(error.message || 'تولید کپچای جدید ناموفق بود.');
        } finally {
          button.disabled = false;
          button.classList.remove('is-loading');
        }
      });
    });
  }

  function initEditRequests() {
    qsa(document, '[data-afe-edit-request]').forEach(requestForm => {
      if (requestForm.dataset.afeReady === '1') return;
      requestForm.dataset.afeReady = '1';
      requestForm.addEventListener('submit', async event => {
        event.preventDefault();
        const button = qs(requestForm, 'button[type="submit"]');
        const result = qs(requestForm, '.afe-edit-request__result');
        if (button) button.disabled = true;
        if (result) result.innerHTML = '<span>در حال ارسال درخواست...</span>';
        try {
          const res = await fetch(cfg.ajaxUrl, {method:'POST', body:new FormData(requestForm), credentials:'same-origin'});
          const json = await res.json();
          if (!json.success) throw new Error(json?.data?.message || 'ثبت درخواست ناموفق بود.');
          if (result) result.innerHTML = `<strong>${escapeHtml(json.data.message || 'درخواست ثبت شد.')}</strong>`;
          requestForm.classList.add('is-sent');
          qsa(requestForm, 'textarea,button').forEach(el => el.disabled = true);
        } catch (error) {
          if (result) result.innerHTML = `<span class="afe-text-error">${escapeHtml(error.message || 'خطا در ارسال درخواست')}</span>`;
          if (button) button.disabled = false;
        }
      });
    });
  }

  function initForm(form) {
    const steps = qsa(form, '.afe-step');
    let current = Math.max(0, steps.findIndex(s => s.classList.contains('is-active')));
    let maxVisited = form.dataset.afeReadonly === '1' ? steps.length - 1 : current;
    const captcha = qs(form, '.afe-captcha-wrap');

    const renderStep = index => {
      current = Math.max(0, Math.min(index, steps.length - 1));
      maxVisited = Math.max(maxVisited, current);
      steps.forEach((s,i) => s.classList.toggle('is-active', i === current));
      const pct = Math.round(((current + 1) / Math.max(1,steps.length)) * 100);
      const track = qs(form, '.afe-progress-track > span');
      if (track) track.style.width = `${pct}%`;
      const text = qs(form, '.afe-progress-text');
      if (text) text.textContent = `مرحله ${current + 1} از ${steps.length}`;
      const percent = qs(form, '.afe-progress-percent');
      if (percent) percent.textContent = `${pct}٪`;
      qsa(form, '.afe-progress-step').forEach((b,i) => {
        b.classList.toggle('is-active', i === current);
        b.classList.toggle('is-done', i < current);
        b.disabled = i > maxVisited;
      });
      if (captcha) captcha.hidden = current !== steps.length - 1;
      if (steps[current]?.dataset.afePreviewStep === '1') updatePreview(form);
      applyConditions(form);
      form.scrollIntoView({behavior:'smooth', block:'start'});
    };

    form.addEventListener('click', async e => {
      const errorLink = e.target.closest('[data-afe-error-target]');
      if (errorLink) {
        const stepIndex = Number(errorLink.dataset.afeErrorStep || 0);
        const targetId = errorLink.dataset.afeErrorTarget || '';
        renderStep(stepIndex);
        requestAnimationFrame(() => {
          const wrapper = targetId ? document.getElementById(targetId) : null;
          if (!wrapper || !form.contains(wrapper)) return;
          wrapper.classList.add('afe-error-focus');
          wrapper.scrollIntoView({behavior:'smooth', block:'center'});
          const focusable = qs(wrapper, '.afe-custom-select__trigger, .afe-upload-browse, input:not([type="hidden"]):not([type="file"]), textarea, select:not([hidden]), button');
          focusable?.focus({preventScroll:true});
          window.setTimeout(() => wrapper.classList.remove('afe-error-focus'), 1800);
        });
        return;
      }

      if (e.target.closest('.afe-next')) {
        if (validateStep(form, steps[current])) renderStep(current + 1);
      } else if (e.target.closest('.afe-prev')) {
        renderStep(current - 1);
      } else if (e.target.closest('.afe-save-draft')) {
        await send(form, 'draft');
      } else {
        const go = e.target.closest('[data-goto-step]');
        if (go && Number(go.dataset.gotoStep) <= maxVisited) renderStep(Number(go.dataset.gotoStep));
      }
    });

    form.addEventListener('submit', async e => {
      e.preventDefault();
      if (form.dataset.afeReadonly === '1') return;
      let valid = true;
      for (const step of steps) {
        if (step.dataset.afePreviewStep === '1') continue;
        if (!validateStep(form, step, {scroll:false})) valid = false;
      }
      if (!valid) {
        renderValidationSummary(form, collectDomErrors(form));
        return;
      }
      if (form.dataset.afePreviewEnabled === '1' && current !== steps.length - 1) {
        renderStep(steps.length - 1);
        updatePreview(form);
        return;
      }
      if (form.dataset.afeLockAfterSubmit === '1') {
        const confirmBox = qs(form, '[data-afe-lock-confirm]');
        if (confirmBox && !confirmBox.checked) {
          renderStep(steps.length - 1);
          const result = qs(form, '.afe-form-result');
          if (result) result.innerHTML = '<div class="afe-notice afe-notice-warning">برای ثبت نهایی، تأیید هشدار قفل فرم الزامی است.</div>';
          confirmBox.focus();
          return;
        }
        if (!confirmBox && form.dataset.afePreviewEnabled !== '1') {
          const warning = form.dataset.afeLockWarning || 'پس از ثبت نهایی امکان ویرایش مستقیم وجود ندارد. ادامه می‌دهید؟';
          if (!window.confirm(warning)) return;
        }
      }
      await send(form, 'submit');
    });

    form.addEventListener('change', () => applyConditions(form));
    form.addEventListener('input', e => {
      // The custom select search box is presentation-only and must not trigger
      // field validation/conditional logic while the user is filtering options.
      if (e.target.closest('.afe-custom-select__search')) return;
      const wrapper = e.target.closest('.afe-field');
      if (wrapper?.classList.contains('has-error')) {
        setError(wrapper, '');
        updateStepErrorIndicators(form);
        const remaining = collectDomErrors(form);
        const summary = qs(form, '.afe-validation-summary');
        if (summary) {
          if (remaining.length) renderValidationSummary(form, remaining, 'لطفاً خطاهای زیر را اصلاح کنید.', {scroll:false});
          else summary.remove();
        }
      }
      applyConditions(form);
    });

    initReadonly(form);
    initSelectIsolation(form);
    initFileUploads(form);
    initConstrainedInputs(form);
    initRepeaters(form);
    initDependencies(form);
    initUniqueGroups(form);
    renderStep(current);
  }

  document.addEventListener('DOMContentLoaded', () => {
    if (window.jalaliDatepicker && qs(document, 'input[data-afe-calendar="jalali"][data-jdp]')) {
      window.jalaliDatepicker.startWatch({
        selector: 'input[data-afe-calendar="jalali"][data-jdp]',
        persianDigits: false,
        autoReadOnlyInput: false,
        date: true,
        time: false
      });
    }
    qsa(document, '.afe-form').forEach(initForm);
    initEditRequests();
    initCaptchaRefresh();
    qsa(document, '.afe-resume-button').forEach(button => {
      button.addEventListener('click', () => {
        const box = button.closest('.afe-resume');
        const input = qs(box, '.afe-resume-code');
        const code = String(input?.value || '').trim();
        if (!code) { input?.focus(); return; }
        const url = new URL(window.location.href);
        url.searchParams.delete('afe_edit');
        url.searchParams.delete('afe_submission');
        url.searchParams.set('afe_tracking', code);
        window.location.href = url.toString();
      });
    });
  });
})();
/* محاسبه مدت اقامت فرم پذیرش اردوگاه؛ تمام رفتارها به همین فرم محدود است. */
(() => {
  'use strict';

  const FORM_SELECTOR = 'form.afe-form[data-form="imam-khomeini-camp-admission"]';
  const DAY_MS = 86400000;
  const JALALI_BREAKS = [-61, 9, 38, 199, 426, 686, 756, 818, 1111, 1181, 1210, 1635, 2060, 2097, 2192, 2262, 2324, 2394, 2456, 3178];

  const latinDigits = value => String(value || '').replace(/[۰-۹٠-٩]/g, digit => {
    const code = digit.charCodeAt(0);
    return String(code >= 0x06f0 && code <= 0x06f9 ? code - 0x06f0 : code - 0x0660);
  });

  const isJalaliLeapYear = year => {
    if (year < JALALI_BREAKS[0] || year >= JALALI_BREAKS[JALALI_BREAKS.length - 1]) return false;
    let jp = JALALI_BREAKS[0];
    let jump = 0;
    for (let index = 1; index < JALALI_BREAKS.length; index += 1) {
      const jm = JALALI_BREAKS[index];
      jump = jm - jp;
      if (year < jm) break;
      jp = jm;
    }
    let n = year - jp;
    if (jump - n < 6) n = n - jump + 33 * Math.floor((jump + 4) / 33);
    let leap = (((n + 1) % 33) - 1) % 4;
    if (leap < 0) leap += 4;
    return leap === 0;
  };

  const jalaliToGregorian = (jy, jm, jd) => {
    let gy;
    if (jy > 979) {
      gy = 1600;
      jy -= 979;
    } else {
      gy = 621;
    }

    let days = 365 * jy
      + Math.floor(jy / 33) * 8
      + Math.floor(((jy % 33) + 3) / 4)
      + 78
      + jd
      + (jm < 7 ? (jm - 1) * 31 : ((jm - 7) * 30) + 186);

    gy += 400 * Math.floor(days / 146097);
    days %= 146097;
    if (days > 36524) {
      gy += 100 * Math.floor(--days / 36524);
      days %= 36524;
      if (days >= 365) days += 1;
    }
    gy += 4 * Math.floor(days / 1461);
    days %= 1461;
    if (days > 365) {
      gy += Math.floor((days - 1) / 365);
      days = (days - 1) % 365;
    }
    let gd = days + 1;
    const leap = (gy % 4 === 0 && gy % 100 !== 0) || gy % 400 === 0;
    const months = [0, 31, leap ? 29 : 28, 31, 30, 31, 30, 31, 31, 30, 31, 30, 31];
    let gm = 1;
    for (; gm <= 12 && gd > months[gm]; gm += 1) gd -= months[gm];
    return { year: gy, month: gm, day: gd };
  };

  const jalaliDayNumber = raw => {
    const value = latinDigits(raw).trim();
    const match = value.match(/^(\d{4})\/(\d{2})\/(\d{2})$/);
    if (!match) return null;

    const year = Number(match[1]);
    const month = Number(match[2]);
    const day = Number(match[3]);
    if (year < 1 || month < 1 || month > 12 || day < 1) return null;
    const maxDay = month <= 6 ? 31 : (month <= 11 ? 30 : (isJalaliLeapYear(year) ? 30 : 29));
    if (day > maxDay) return null;

    const gregorian = jalaliToGregorian(year, month, day);
    return Math.floor(Date.UTC(gregorian.year, gregorian.month - 1, gregorian.day) / DAY_MS);
  };

  const initializeForm = form => {
    if (!form || form.dataset.baCampAdmissionInitialized === '1') return;

    const startInput = form.querySelector('[name="afe_data[from_date]"]');
    const endInput = form.querySelector('[name="afe_data[to_date]"]');
    const durationInput = form.querySelector('[name="afe_data[stay_duration]"]');
    if (!startInput || !endInput || !durationInput) return;

    form.dataset.baCampAdmissionInitialized = '1';
    durationInput.readOnly = true;
    durationInput.setAttribute('aria-readonly', 'true');
    durationInput.setAttribute('autocomplete', 'off');

    form.querySelectorAll('.afe-submit').forEach(button => {
      button.textContent = 'ثبت درخواست پذیرش';
    });

    const setDuration = value => {
      const nextValue = value > 0 ? String(value) : '';
      if (durationInput.value === nextValue) return;
      durationInput.value = nextValue;
      durationInput.dispatchEvent(new Event('input', { bubbles: true }));
      durationInput.dispatchEvent(new Event('change', { bubbles: true }));
    };

    const updateDuration = () => {
      startInput.setCustomValidity('');
      endInput.setCustomValidity('');
      durationInput.setCustomValidity('');

      const startRaw = String(startInput.value || '').trim();
      const endRaw = String(endInput.value || '').trim();
      if (!startRaw || !endRaw) {
        setDuration('');
        return;
      }

      const startDay = jalaliDayNumber(startRaw);
      const endDay = jalaliDayNumber(endRaw);
      if (startDay === null) {
        setDuration('');
        startInput.setCustomValidity('تاریخ شروع شمسی معتبر نیست.');
        return;
      }
      if (endDay === null) {
        setDuration('');
        endInput.setCustomValidity('تاریخ پایان شمسی معتبر نیست.');
        return;
      }

      const nights = endDay - startDay;
      if (nights <= 0) {
        setDuration('');
        endInput.setCustomValidity('تاریخ پایان باید بعد از تاریخ شروع باشد.');
        return;
      }

      setDuration(nights);
    };

    const scheduleUpdate = () => {
      updateDuration();
      // Run once more after AFE's own input normalizers have handled the event.
      Promise.resolve().then(updateDuration);
    };

    ['input', 'change', 'blur'].forEach(eventName => {
      startInput.addEventListener(eventName, scheduleUpdate);
      endInput.addEventListener(eventName, scheduleUpdate);
    });
    form.addEventListener('submit', updateDuration, true);
    updateDuration();
  };

  const initialize = root => {
    if (!root) return;
    if (root.matches && root.matches(FORM_SELECTOR)) initializeForm(root);
    if (root.querySelectorAll) root.querySelectorAll(FORM_SELECTOR).forEach(initializeForm);
  };

  const boot = () => {
    initialize(document);
    if (document.body && window.MutationObserver) {
      const observer = new MutationObserver(records => {
        records.forEach(record => record.addedNodes.forEach(node => {
          if (node.nodeType === 1) initialize(node);
        }));
      });
      observer.observe(document.body, { childList: true, subtree: true });
    }
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, { once: true });
  } else {
    boot();
  }

  document.addEventListener('elementor/popup/show', () => initialize(document));
})();

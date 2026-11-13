(function () {
    'use strict';

    function formatAmount(value, decimals) {
        try {
            return new Intl.NumberFormat('fa-IR', {
                maximumFractionDigits: Math.max(0, Number(decimals) || 0)
            }).format(Number(value));
        } catch (error) {
            return String(value);
        }
    }

    function enhanceQuickSupportForm(form) {
        if (!form || form.dataset.baQsEnhanced === '1') return;

        var context = {};
        try {
            context = JSON.parse(form.getAttribute('data-afe-context') || '{}');
        } catch (error) {
            return;
        }

        if (!context || !context.category_id) return;

        var field = form.querySelector('.afe-field[data-field="amount"]');
        var input = field ? field.querySelector('input[name="afe_data[amount]"]') : null;
        if (!field || !input) return;

        function groupInput() {
            var start = input.selectionStart;
            var raw = input.value.replace(/[۰-۹٠-٩]/g, function (digit) {
                var code = digit.charCodeAt(0);
                return String(code >= 1776 ? code - 1776 : code - 1632);
            }).replace(/٬/g, ',').replace(/٫/g, '.');
            var before = raw.slice(0, start == null ? raw.length : start).replace(/,/g, '').length;
            var parts = raw.replace(/,/g, '').split('.');
            var integer = parts.shift().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
            var formatted = integer + (parts.length ? '.' + parts.join('.') : '');
            input.value = formatted;
            if (start != null) {
                var pos = 0, seen = 0;
                while (pos < formatted.length && seen < before) {
                    if (formatted.charAt(pos) !== ',') seen++;
                    pos++;
                }
                input.setSelectionRange(pos, pos);
            }
        }
        input.addEventListener('input', groupInput);
        groupInput();

        if (Array.isArray(context.suggested_amounts) && context.suggested_amounts.length) {
            var group = document.createElement('div');
            group.className = 'ba-qs-amount-suggestions';
            group.setAttribute('role', 'group');
            group.setAttribute('aria-label', 'مبالغ پیشنهادی');

            var title = document.createElement('span');
            title.className = 'ba-qs-amount-suggestions__title';
            title.textContent = 'مبالغ پیشنهادی';
            group.appendChild(title);

            context.suggested_amounts.forEach(function (amount) {
                var numericAmount = Number(amount);
                if (!Number.isFinite(numericAmount) || numericAmount <= 0) return;

                var button = document.createElement('button');
                button.type = 'button';
                button.className = 'ba-qs-amount-suggestions__item';
                button.textContent = formatAmount(numericAmount, context.currency_decimals)
                    + (context.currency_symbol ? ' ' + context.currency_symbol : '');
                button.addEventListener('click', function () {
                    input.value = String(numericAmount);
                    input.dispatchEvent(new Event('input', { bubbles: true }));
                    input.dispatchEvent(new Event('change', { bubbles: true }));
                });
                group.appendChild(button);
            });

            if (group.querySelector('.ba-qs-amount-suggestions__item')) {
                field.appendChild(group);
            }
        }

        var minimum = Number(context.minimum_amount || 0);
        var maximum = Number(context.maximum_amount || 0);
        if (minimum > 0 || maximum > 0) {
            var limits = document.createElement('div');
            limits.className = 'ba-qs-amount-limits';
            var parts = [];
            if (minimum > 0) {
                parts.push('حداقل ' + formatAmount(minimum, context.currency_decimals));
            }
            if (maximum > 0) {
                parts.push('حداکثر ' + formatAmount(maximum, context.currency_decimals));
            }
            limits.textContent = parts.join('  ·  ')
                + (context.currency_symbol ? ' ' + context.currency_symbol : '');
            field.appendChild(limits);
        }

        form.dataset.baQsEnhanced = '1';
    }

    function scanQuickSupportForms() {
        document.querySelectorAll('form.afe-form[data-afe-context]').forEach(enhanceQuickSupportForm);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', scanQuickSupportForms);
    } else {
        scanQuickSupportForms();
    }

    document.addEventListener('elementor/frontend/init', scanQuickSupportForms);
    if (window.elementorFrontend && window.elementorFrontend.hooks) {
        window.elementorFrontend.hooks.addAction('frontend/element_ready/global', scanQuickSupportForms);
    }

    if ('MutationObserver' in window && document.documentElement) {
        var observer = new MutationObserver(scanQuickSupportForms);
        observer.observe(document.documentElement, { childList: true, subtree: true });
    }
})();

(function () {
    'use strict';

    /**
     * ارقام فارسی و عربی را برای انیمیشن شمارنده به عدد لاتین تبدیل می‌کند.
     */
    function normalizeDigits(value) {
        var persian = '۰۱۲۳۴۵۶۷۸۹';
        var arabic = '٠١٢٣٤٥٦٧٨٩';
        return String(value || '')
            .replace(/[۰-۹]/g, function (digit) { return String(persian.indexOf(digit)); })
            .replace(/[٠-٩]/g, function (digit) { return String(arabic.indexOf(digit)); });
    }

    /**
     * یک مقدار نمایشی آمار را به عدد و پیشوند/پسوند تفکیک می‌کند.
     */
    function parseCounter(raw) {
        var normalized = normalizeDigits(raw);
        var match = normalized.match(/^([^0-9-]*)(-?[0-9][0-9,]*)(.*)$/);
        if (!match) {
            return null;
        }
        return {
            prefix: match[1] || '',
            value: Number(match[2].replace(/,/g, '')),
            suffix: match[3] || ''
        };
    }

    /**
     * شمارنده آمار را با رعایت prefers-reduced-motion اجرا می‌کند.
     */
    function initCounters(root) {
        var counters = root.querySelectorAll('[data-ba-jc-counter]');
        var formatter = new Intl.NumberFormat('fa-IR');
        var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        /**
         * انیمیشن یک شمارنده را اجرا می‌کند.
         */
        function animate(element) {
            var parsed = parseCounter(element.getAttribute('data-ba-jc-counter'));
            if (!parsed || !Number.isFinite(parsed.value)) {
                return;
            }
            if (reduced) {
                element.textContent = parsed.prefix + formatter.format(parsed.value) + parsed.suffix;
                return;
            }
            var start = performance.now();
            var duration = 1300;
            /**
             * فریم بعدی انیمیشن شمارنده را محاسبه می‌کند.
             */
            function frame(now) {
                var progress = Math.min((now - start) / duration, 1);
                var eased = 1 - Math.pow(1 - progress, 3);
                element.textContent = parsed.prefix + formatter.format(Math.round(parsed.value * eased)) + parsed.suffix;
                if (progress < 1) {
                    requestAnimationFrame(frame);
                }
            }
            requestAnimationFrame(frame);
        }

        if (!('IntersectionObserver' in window)) {
            counters.forEach(animate);
            return;
        }
        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (entry.isIntersecting) {
                    animate(entry.target);
                    observer.unobserve(entry.target);
                }
            });
        }, { threshold: 0.5 });
        counters.forEach(function (counter) { observer.observe(counter); });
    }

    /**
     * رفتار Accordion پرسش‌های متداول را داخل همان نمونه ویجت فعال می‌کند.
     */
    function initFaq(root) {
        root.querySelectorAll('[data-ba-jc-faq-button]').forEach(function (button) {
            button.addEventListener('click', function () {
                var item = button.closest('[data-ba-jc-faq-item]');
                var wasOpen = item.classList.contains('ba-jihadi-center__faq-item--open');

                root.querySelectorAll('[data-ba-jc-faq-item]').forEach(function (row) {
                    row.classList.remove('ba-jihadi-center__faq-item--open');
                    var rowButton = row.querySelector('[data-ba-jc-faq-button]');
                    var symbol = row.querySelector('.ba-jihadi-center__faq-symbol');
                    if (rowButton) { rowButton.setAttribute('aria-expanded', 'false'); }
                    if (symbol) { symbol.textContent = '+'; }
                });

                if (!wasOpen) {
                    item.classList.add('ba-jihadi-center__faq-item--open');
                    button.setAttribute('aria-expanded', 'true');
                    var activeSymbol = item.querySelector('.ba-jihadi-center__faq-symbol');
                    if (activeSymbol) { activeSymbol.textContent = '−'; }
                }
            });
        });
    }

    /**
     * یک نمونه ویجت را فقط یک بار مقداردهی می‌کند.
     */
    function init(root) {
        if (!root || root.dataset.baJihadiCenterReady === '1') {
            return;
        }
        root.dataset.baJihadiCenterReady = '1';
        initCounters(root);
        initFaq(root);
    }

    /**
     * همه نمونه‌های موجود در سند را پس از آماده‌شدن DOM مقداردهی می‌کند.
     */
    function initDocument() {
        document.querySelectorAll('[data-ba-jihadi-center]').forEach(init);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDocument);
    } else {
        initDocument();
    }

    if (window.elementorFrontend && window.elementorFrontend.hooks) {
        window.elementorFrontend.hooks.addAction('frontend/element_ready/bonyad_alavi_jihadi_center.default', function ($scope) {
            init($scope[0].querySelector('[data-ba-jihadi-center]'));
        });
    }
})();

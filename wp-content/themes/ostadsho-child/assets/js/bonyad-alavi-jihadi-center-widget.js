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
     * سکشن‌های ویجت را هنگام ورود به viewport با Fade-in نمایش می‌دهد.
     * برای بخش‌های پایین صفحه، lazy rendering مرورگر با content-visibility در CSS فعال است.
     */
    function initSectionReveal(root) {
        var sections = root.querySelectorAll('[data-ba-jc-reveal]');
        var reduced = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        /**
         * یک سکشن را فقط یک بار نمایان می‌کند.
         */
        function reveal(section) {
            if (section.dataset.baJcRevealed === '1') {
                return;
            }

            section.dataset.baJcRevealed = '1';
            section.classList.add('ba-jihadi-center__viewport-section--visible');

            if (reduced || typeof section.animate !== 'function') {
                return;
            }

            section.animate(
                [
                    { opacity: 0 },
                    { opacity: 1 }
                ],
                {
                    duration: 650,
                    easing: 'cubic-bezier(0.22, 1, 0.36, 1)'
                }
            );
        }

        var isEditor = window.elementorFrontend && typeof window.elementorFrontend.isEditMode === 'function' && window.elementorFrontend.isEditMode();
        if (isEditor) {
            sections.forEach(function (section) {
                section.dataset.baJcRevealed = '1';
                section.classList.add('ba-jihadi-center__viewport-section--visible');
            });
            return;
        }

        if (reduced || !('IntersectionObserver' in window)) {
            sections.forEach(reveal);
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                if (!entry.isIntersecting) {
                    return;
                }

                reveal(entry.target);
                observer.unobserve(entry.target);
            });
        }, {
            threshold: 0.08,
            rootMargin: '0px 0px -6% 0px'
        });

        sections.forEach(function (section) {
            observer.observe(section);
        });
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
        initSectionReveal(root);
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

(function () {
    'use strict';

    var AREA_EVENT = 'ba:studies-area-change';
    var instances = new WeakMap();

    function parseConfig(element, attribute) {
        try {
            return JSON.parse(element.getAttribute(attribute) || '{}');
        } catch (error) {
            return {};
        }
    }

    function prefersReducedMotion() {
        return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    /**
     * Swiper نصب‌شده Elementor را استفاده می‌کند؛ نسخه جداگانه‌ای همراه قالب نیست.
     */
    function createSwiper(element, options) {
        var frontend = window.elementorFrontend;
        var AsyncSwiper = frontend && frontend.utils && frontend.utils.swiper;
        var pending = null;

        try {
            if (AsyncSwiper) pending = new AsyncSwiper(element, options);
            else if (window.Swiper) pending = new window.Swiper(element, options);
        } catch (error) {
            pending = null;
        }

        return Promise.resolve(pending);
    }

    function destroy(root) {
        var state = instances.get(root);
        if (!state) return;
        state.cleanups.forEach(function (cleanup) { cleanup(); });
        if (state.swiper && !state.swiper.destroyed) state.swiper.destroy(true, true);
        instances.delete(root);
    }

    /* ---------- Hero ---------- */

    function initHero(root) {
        var slider = root && root.querySelector('[data-ba-studies-hero]');
        if (!slider) return;
        destroy(root);

        var config = parseConfig(slider, 'data-ba-studies-hero');
        var state = { swiper: null, cleanups: [] };
        var slideCount = slider.querySelectorAll('.swiper-slide').length;
        var dots = slider.querySelector('[data-ba-studies-dots]');
        instances.set(root, state);

        if (slideCount < 2) return;

        createSwiper(slider, {
            slidesPerView: 1,
            loop: Boolean(config.loop),
            speed: prefersReducedMotion() ? 0 : 600,
            autoplay: config.autoplay && !prefersReducedMotion()
                ? { delay: Number(config.delay) || 6000, disableOnInteraction: false, pauseOnMouseEnter: true }
                : false,
            navigation: {
                prevEl: slider.querySelector('[data-ba-studies-prev]'),
                nextEl: slider.querySelector('[data-ba-studies-next]')
            },
            pagination: dots ? { el: dots, clickable: true } : false,
            keyboard: { enabled: true, onlyInViewport: true },
            a11y: { prevSlideMessage: 'اسلاید قبلی', nextSlideMessage: 'اسلاید بعدی' }
        }).then(function (swiper) {
            if (swiper && instances.get(root) === state) state.swiper = swiper;
        });
    }

    /* ---------- Carousel ---------- */

    function initCarousel(root) {
        if (!root || !root.hasAttribute('data-ba-studies-carousel')) return;
        destroy(root);

        var config = parseConfig(root, 'data-ba-studies-carousel');
        var element = root.querySelector('[data-ba-studies-swiper]');
        var track = root.querySelector('[data-ba-studies-track]');
        var empty = root.querySelector('[data-ba-studies-empty]');
        var dots = root.querySelector('[data-ba-studies-dots]');
        var state = { swiper: null, cleanups: [], controller: null, cache: {} };
        var swiperConfig = config.swiper || {};
        instances.set(root, state);

        if (!element || !track) return;

        function view(device) {
            var item = swiperConfig[device] || {};
            return { slidesPerView: Number(item.perView) || 1, spaceBetween: Number(item.gap) || 0 };
        }

        function mount() {
            if (state.swiper && !state.swiper.destroyed) state.swiper.destroy(true, true);
            state.swiper = null;
            if (element.hidden) return;

            var options = {
                slidesPerView: view('mobile').slidesPerView,
                spaceBetween: view('mobile').spaceBetween,
                speed: prefersReducedMotion() ? 0 : 450,
                watchOverflow: true,
                grabCursor: true,
                navigation: {
                    prevEl: root.querySelector('[data-ba-studies-prev]'),
                    nextEl: root.querySelector('[data-ba-studies-next]')
                },
                pagination: dots ? { el: dots, clickable: true } : false,
                keyboard: { enabled: true, onlyInViewport: true },
                breakpoints: {
                    761: view('tablet'),
                    1081: view('desktop')
                }
            };

            if (swiperConfig.autoplay && !prefersReducedMotion()) {
                options.autoplay = { delay: 5000, disableOnInteraction: false, pauseOnMouseEnter: true };
            }

            createSwiper(element, options).then(function (swiper) {
                if (swiper && instances.get(root) === state) state.swiper = swiper;
            });
        }

        function render(html) {
            track.innerHTML = html;
            var hasItems = Boolean(track.querySelector('.swiper-slide'));
            element.hidden = !hasItems;
            if (empty) empty.hidden = hasItems;
            if (dots) dots.hidden = !hasItems;
            mount();
        }

        function load(area) {
            if (!config.follow || !config.endpoint) return;
            var key = area || '__all__';

            if (Object.prototype.hasOwnProperty.call(state.cache, key)) {
                render(state.cache[key]);
                return;
            }

            if (state.controller) state.controller.abort();
            state.controller = window.AbortController ? new AbortController() : null;

            var url = new URL(config.endpoint, window.location.href);
            var query = config.query || {};
            Object.keys(query).forEach(function (name) {
                url.searchParams.set(name, String(query[name]));
            });
            url.searchParams.set('area', area || '');

            root.classList.add('is-loading');
            root.setAttribute('aria-busy', 'true');

            fetch(url.toString(), {
                credentials: 'same-origin',
                headers: { Accept: 'application/json' },
                signal: state.controller ? state.controller.signal : undefined
            }).then(function (response) {
                if (!response.ok) throw new Error('HTTP ' + response.status);
                return response.json();
            }).then(function (data) {
                var html = data && typeof data.html === 'string' ? data.html : '';
                state.cache[key] = html;
                render(html);
            }).catch(function (error) {
                if (error && error.name === 'AbortError') return;
                render('');
            }).finally(function () {
                root.classList.remove('is-loading');
                root.removeAttribute('aria-busy');
            });
        }

        function onAreaChange(event) {
            load(event.detail ? String(event.detail.area || '') : '');
        }

        if (config.follow) {
            document.addEventListener(AREA_EVENT, onAreaChange);
            state.cleanups.push(function () {
                document.removeEventListener(AREA_EVENT, onAreaChange);
                if (state.controller) state.controller.abort();
            });
        }

        mount();
    }

    /* ---------- فیلتر حوزه ---------- */

    function initFilter(root) {
        var filter = root && (root.matches('[data-ba-studies-filter]') ? root : root.querySelector('[data-ba-studies-filter]'));
        if (!filter || filter.dataset.baStudiesReady) return;
        filter.dataset.baStudiesReady = '1';

        var config = parseConfig(filter, 'data-ba-studies-filter');
        var select = filter.querySelector('[data-ba-studies-filter-select]');
        if (!select) return;

        select.addEventListener('change', function () {
            var area = select.value;

            if (config.mode === 'url') {
                var target = new URL(config.target || window.location.href, window.location.href);
                if (area) target.searchParams.set('area', area);
                else target.searchParams.delete('area');
                target.searchParams.delete('ba_page');
                window.location.assign(target.toString());
                return;
            }

            if (window.history && window.history.replaceState) {
                var current = new URL(window.location.href);
                if (area) current.searchParams.set('area', area);
                else current.searchParams.delete('area');
                window.history.replaceState(window.history.state, '', current.toString());
            }

            document.dispatchEvent(new CustomEvent(AREA_EVENT, { detail: { area: area } }));
        });
    }

    /* ---------- bootstrap ---------- */

    function initDocument() {
        document.querySelectorAll('.ba-studies-hero').forEach(initHero);
        document.querySelectorAll('[data-ba-studies-carousel]').forEach(initCarousel);
        document.querySelectorAll('[data-ba-studies-filter]').forEach(initFilter);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDocument, { once: true });
    } else {
        initDocument();
    }

    if (window.jQuery) {
        window.jQuery(window).on('elementor/frontend/init', function () {
            var hooks = window.elementorFrontend && window.elementorFrontend.hooks;
            if (!hooks) return;
            hooks.addAction('frontend/element_ready/bonyad_alavi_studies_hero.default', function ($scope) {
                initHero($scope[0].querySelector('.ba-studies-hero'));
            });
            hooks.addAction('frontend/element_ready/bonyad_alavi_studies_carousel.default', function ($scope) {
                initCarousel($scope[0].querySelector('[data-ba-studies-carousel]'));
            });
            hooks.addAction('frontend/element_ready/bonyad_alavi_studies_area_filter.default', function ($scope) {
                initFilter($scope[0]);
            });
        });
    }
})();

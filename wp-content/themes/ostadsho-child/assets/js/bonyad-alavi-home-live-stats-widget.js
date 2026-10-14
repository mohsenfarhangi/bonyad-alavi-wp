(function () {
    'use strict';

    var instances = new WeakMap();
    var tablet = window.matchMedia('(max-width: 1080px)');
    var mobile = window.matchMedia('(max-width: 760px)');
    var reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');

    function destroy(root) {
        var state = instances.get(root);
        if (!state) return;
        state.token++;
        state.cleanups.forEach(function (cleanup) { cleanup(); });
        if (state.swiper && !state.swiper.destroyed) state.swiper.destroy(true, true);
        root.classList.remove('has-carousel-overflow');
        instances.delete(root);
    }

    function init(root) {
        if (!root) return;
        destroy(root);
        var carousel = root.querySelector('[data-ba-live-stats-carousel]');
        if (!carousel) return;
        var config;
        try {
            config = JSON.parse(carousel.getAttribute('data-ba-live-stats-carousel') || '{}');
        } catch (error) {
            return;
        }
        var state = { swiper: null, cleanups: [], token: 0 };
        instances.set(root, state);

        function choose() {
            if (mobile.matches) return config.mobile || {};
            if (tablet.matches) return config.tablet || {};
            return config.desktop || {};
        }

        function mount() {
            var token = ++state.token;
            if (state.swiper && !state.swiper.destroyed) state.swiper.destroy(true, true);
            state.swiper = null;
            root.classList.remove('has-carousel-overflow');

            var current = choose();
            var columns = Math.max(1, Number(current.columns) || 1);
            var rows = Math.max(1, Number(current.rows) || 1);
            var gap = Math.max(0, Number(current.gap) || 0);
            var rowGap = Math.max(0, Number(current.rowGap) || 0);
            var group = Math.max(1, Math.min(columns, Number(current.group) || 1));
            var slideCount = carousel.querySelectorAll('.swiper-slide').length;
            var height = mobile.matches ? 100 : (tablet.matches ? 90 : 96);

            // Swiper Grid needs an explicit height to calculate each row.
            carousel.style.height = (height * rows + rowGap * (rows - 1)) + 'px';
            carousel.style.setProperty('--ba-stats-row-gap', rowGap + 'px');
            carousel.style.setProperty('--ba-stats-rows', String(rows));
            root.classList.toggle('is-multi-row', rows > 1);
            var canSlide = slideCount > columns * rows;
            var canLoop = rows === 1 && slideCount > columns * 2;
            var useLoop = config.loop && canLoop;
            var free = config.freeMode && rows === 1 && !useLoop;
            var navigation = !!config.navigation && canSlide;
            var pagination = (config.pagination || 'none') !== 'none' && canSlide;
            var navPrev = root.querySelector('[data-ba-stats-prev]');
            var navNext = root.querySelector('[data-ba-stats-next]');
            var dots = root.querySelector('[data-ba-stats-pagination]');

            root.classList.toggle('has-carousel-navigation', navigation);
            root.classList.toggle('has-carousel-pagination', pagination);
            function refreshControls(swiper) {
                root.classList.toggle('has-carousel-overflow',
                    !swiper.isLocked && canSlide && (navigation || pagination));
            }

            var options = {
                direction: 'horizontal',
                slidesPerView: columns,
                slidesPerGroup: group,
                spaceBetween: gap,
                grid: { rows: rows, fill: 'row' },
                loop: useLoop,
                freeMode: free,
                speed: Math.max(100, Number(config.speed) || 450),
                allowTouchMove: config.touch !== false,
                grabCursor: !!config.grabCursor,
                watchOverflow: true,
                observeParents: true,
                keyboard: { enabled: config.keyboard !== false, onlyInViewport: true },
                navigation: navigation ? { prevEl: navPrev, nextEl: navNext } : false,
                pagination: pagination ? {
                    el: dots, clickable: config.pagination === 'bullets',
                    type: config.pagination
                } : false,
                autoplay: config.autoplay && canSlide && !reducedMotion.matches ? {
                    delay: Math.max(500, Number(config.delay) || 5000),
                    pauseOnMouseEnter: config.pauseHover !== false,
                    disableOnInteraction: config.disableInteraction !== false
                } : false,
                on: {
                    init: function (swiper) { refreshControls(swiper); },
                    lock: function (swiper) { refreshControls(swiper); },
                    unlock: function (swiper) { refreshControls(swiper); }
                }
            };

            // Elementor dynamically loads its own Swiper; never ship a duplicate copy.
            var frontend = window.elementorFrontend;
            var AsyncSwiper = frontend && frontend.utils && frontend.utils.swiper;
            var SwiperConstructor = window.Swiper;
            var pending;
            try {
                if (AsyncSwiper) pending = new AsyncSwiper(carousel, options);
                else if (SwiperConstructor) pending = new SwiperConstructor(carousel, options);
                else return;
            } catch (error) {
                return;
            }

            Promise.resolve(pending).then(function (swiper) {
                if (!swiper) return;
                if (instances.get(root) !== state || token !== state.token) {
                    if (!swiper.destroyed) swiper.destroy(true, true);
                    return;
                }
                state.swiper = swiper;
                refreshControls(swiper);
            }).catch(function () {
                root.classList.remove('has-carousel-overflow');
            });
        }

        function changeMode() { mount(); }
        [tablet, mobile, reducedMotion].forEach(function (media) {
            if (media.addEventListener) media.addEventListener('change', changeMode);
            else media.addListener(changeMode);
            state.cleanups.push(function () {
                if (media.removeEventListener) media.removeEventListener('change', changeMode);
                else media.removeListener(changeMode);
            });
        });
        state.cleanups.push(function () {
            carousel.style.removeProperty('height');
            carousel.style.removeProperty('--ba-stats-row-gap');
            carousel.style.removeProperty('--ba-stats-rows');
            root.classList.remove('is-multi-row', 'has-carousel-navigation', 'has-carousel-pagination');
        });
        mount();
    }

    function initDocument() {
        document.querySelectorAll('.ba-home-live-stats-widget').forEach(init);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDocument, { once: true });
    } else {
        initDocument();
    }
    if (window.elementorFrontend && window.elementorFrontend.hooks) {
        window.elementorFrontend.hooks.addAction(
            'frontend/element_ready/bonyad_alavi_home_live_stats.default',
            function ($scope) {
                init($scope[0].querySelector('.ba-home-live-stats-widget'));
            }
        );
    }
})();

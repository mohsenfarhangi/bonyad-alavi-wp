(function () {
    'use strict';

    var states = new WeakMap();

    function destroy(root) {
        var state = states.get(root);
        if (!state) return;

        state.cleanups.forEach(function (cleanup) {
            try {
                cleanup();
            } catch (error) {}
        });

        states.delete(root);
    }

    function initSlider(root, cleanups) {
        var slider = root.querySelector('[data-js-slider]');
        var viewport = slider && slider.querySelector('.ba-slider__viewport');
        var frontend = window.elementorFrontend;
        var AsyncSwiper = frontend && frontend.utils && frontend.utils.swiper;
        if (!viewport || !AsyncSwiper) return;

        var count = viewport.querySelectorAll('.swiper-slide').length;
        if (!count) return;
        var media = window.matchMedia('(prefers-reduced-motion: reduce)');
        var swiper = null;
        var disposed = false;
        var generation = 0;
        var hovered = false;
        var pausedByUI = false;
        var interactionStopped = false;
        var pauseHover = root.dataset.sliderPauseHover === '1';
        var disableInteraction = root.dataset.sliderDisableInteraction === '1';
        var autoplay = root.dataset.sliderAutoplay === '1' && count > 1;

        function syncPause() {
            if (!swiper || !swiper.autoplay || !autoplay || media.matches) return;
            var focused = slider.contains(document.activeElement);
            var paused = document.hidden || (pauseHover && (hovered || focused));
            if (paused && !pausedByUI) {
                pausedByUI = swiper.autoplay.running;
                if (pausedByUI) swiper.autoplay.stop();
            } else if (!paused && pausedByUI) {
                pausedByUI = false;
                if (!interactionStopped) swiper.autoplay.start();
            }
        }

        function syncSlides(instance) {
            Array.prototype.forEach.call(instance.slides, function (slide, index) {
                var hidden = index !== instance.activeIndex;
                slide.setAttribute('aria-hidden', hidden ? 'true' : 'false');
                slide.toggleAttribute('inert', hidden);
            });
        }

        function stopForInteraction() {
            if (!disableInteraction) return;
            interactionStopped = true;
            if (swiper && swiper.autoplay) swiper.autoplay.stop();
        }

        function mount() {
            var token = ++generation;
            if (swiper && !swiper.destroyed) swiper.destroy(true, true);
            swiper = null;
            pausedByUI = false;
            interactionStopped = false;
            var options = {
                slidesPerView: 1,
                slidesPerGroup: 1,
                loop: root.dataset.sliderLoop === '1' && count > 1,
                effect: root.dataset.sliderEffect === 'slide' ? 'slide' : 'fade',
                fadeEffect: { crossFade: true },
                speed: media.matches ? 0 : Math.max(0, Number(root.dataset.sliderSpeed) || 0),
                allowTouchMove: root.dataset.sliderTouch !== '0' && count > 1,
                watchOverflow: true,
                observer: true,
                observeParents: true,
                keyboard: { enabled: root.dataset.sliderKeyboard !== '0' && count > 1, onlyInViewport: true },
                a11y: {
                    prevSlideMessage: 'اسلاید قبل', nextSlideMessage: 'اسلاید بعد',
                    paginationBulletMessage: 'اسلاید {{index}}'
                },
                navigation: {
                    prevEl: slider.querySelector('[data-dir="prev"]'),
                    nextEl: slider.querySelector('[data-dir="next"]')
                },
                pagination: {
                    el: slider.querySelector('.ba-slider__pagination'),
                    clickable: true,
                    bulletClass: 'ba-slider__dot',
                    bulletActiveClass: 'is-active',
                    renderBullet: function (index, className) {
                        return '<button type="button" class="' + className + '" aria-label="اسلاید ' + (index + 1) + '"></button>';
                    }
                },
                autoplay: autoplay && !media.matches ? {
                    delay: Math.max(1500, Number(root.dataset.sliderDelay) || 6200),
                    disableOnInteraction: disableInteraction,
                    pauseOnMouseEnter: false
                } : false,
                on: { init: syncSlides, slideChange: syncSlides, sliderFirstMove: stopForInteraction }
            };
            var pending;
            try {
                pending = new AsyncSwiper(viewport, options);
            } catch (error) {
                console.warn('BA Hero: Elementor Swiper could not initialize.', error);
                return;
            }
            Promise.resolve(pending).then(function (instance) {
                if (disposed || token !== generation) {
                    if (instance && !instance.destroyed) instance.destroy(true, true);
                    return;
                }
                swiper = instance;
                syncPause();
            }).catch(function (error) {
                if (!disposed) console.warn('BA Hero: Elementor Swiper could not initialize.', error);
            });
        }

        function enter() { hovered = true; syncPause(); }
        function leave() { hovered = false; syncPause(); }
        function focusOut(event) {
            if (!event.relatedTarget || !slider.contains(event.relatedTarget)) {
                // focusout fires before document.activeElement points at the next target.
                Promise.resolve().then(function () { if (!disposed) syncPause(); });
            }
        }
        function click(event) {
            if (event.target.closest('.ba-slider__arrow, .ba-slider__dot')) stopForInteraction();
        }
        slider.addEventListener('mouseenter', enter);
        slider.addEventListener('mouseleave', leave);
        slider.addEventListener('focusin', syncPause);
        slider.addEventListener('focusout', focusOut);
        slider.addEventListener('click', click, true);
        document.addEventListener('visibilitychange', syncPause);
        if (media.addEventListener) media.addEventListener('change', mount);
        else media.addListener(mount);
        cleanups.push(function () {
            disposed = true;
            generation++;
            if (swiper && !swiper.destroyed) swiper.destroy(true, true);
            slider.removeEventListener('mouseenter', enter);
            slider.removeEventListener('mouseleave', leave);
            slider.removeEventListener('focusin', syncPause);
            slider.removeEventListener('focusout', focusOut);
            slider.removeEventListener('click', click, true);
            document.removeEventListener('visibilitychange', syncPause);
            if (media.removeEventListener) media.removeEventListener('change', mount);
            else media.removeListener(mount);
            viewport.querySelectorAll('.swiper-slide').forEach(function (slide) {
                slide.removeAttribute('inert');
                slide.removeAttribute('aria-hidden');
            });
        });
        mount();
    }

    function initVerticalTicker(root, cleanups) {
        var ticker = root.querySelector('[data-js-ticker]');
        if (!ticker) return;

        var track = ticker.querySelector('[data-js-ticker-track]');
        var items = Array.prototype.slice.call(ticker.querySelectorAll('[data-js-ticker-item]'));
        var autoplay = root.dataset.tickerAutoplay === '1';
        var delay = Math.max(1500, Number(root.dataset.tickerDelay) || 6000);
        var reducedMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if (!track || items.length < 2) {
            if (track) track.style.animation = 'none';
            return;
        }

        if (!autoplay || reducedMotion) {
            ticker.classList.add('is-paused');
            track.style.animation = 'none';
            cleanups.push(function () {
                ticker.classList.remove('is-paused');
                track.style.animation = '';
            });
            return;
        }

        // The reference HTML has 3 real items + a duplicate first item and a fixed 18s CSS animation.
        // Keep that exact path untouched. Other query counts fall back to the same vertical-step behavior in JS.
        if (items.length === 3 && delay === 6000) {
            return;
        }

        ticker.classList.add('is-dynamic');
        var index = 0;
        var timer = null;

        function position() {
            var rowHeight = ticker.querySelector('.ba-ticker__viewport').clientHeight || 46;
            track.style.transform = 'translateY(' + (-index * rowHeight) + 'px)';
        }

        function stop() {
            if (timer) {
                window.clearInterval(timer);
                timer = null;
            }
        }

        function start() {
            stop();
            timer = window.setInterval(function () {
                index = (index + 1) % items.length;
                position();
            }, delay);
        }

        var enter = function () {
            stop();
        };
        var leave = function () {
            start();
        };
        var resize = function () {
            position();
        };

        ticker.addEventListener('mouseenter', enter);
        ticker.addEventListener('mouseleave', leave);
        window.addEventListener('resize', resize);
        cleanups.push(function () {
            stop();
            ticker.removeEventListener('mouseenter', enter);
            ticker.removeEventListener('mouseleave', leave);
            window.removeEventListener('resize', resize);
            ticker.classList.remove('is-dynamic');
            track.style.transform = '';
        });

        start();
    }

    function initMobileTicker(root, cleanups) {
        var ticker = root.querySelector('[data-js-ticker]');
        var original = ticker && ticker.querySelector('[data-js-ticker-track]');
        var viewport = ticker && ticker.querySelector('.ba-ticker__viewport');
        if (!original || !viewport) return;

        var source = Array.prototype.slice.call(original.querySelectorAll('[data-js-ticker-item]'))
            .filter(function (item) { return !item.classList.contains('ba-ticker__item--clone'); });
        if (!source.length) return;

        var autoplay = root.dataset.tickerAutoplay === '1';
        var speed = Math.min(120, Math.max(10, Number(root.dataset.tickerMobileSpeed) || 32));
        var media = window.matchMedia('(prefers-reduced-motion: reduce)');
        var track = document.createElement('div');
        track.className = 'ba-ticker__mobile-track';
        track.setAttribute('data-js-ticker-mobile-track', '');
        viewport.appendChild(track);
        ticker.classList.add('is-mobile-ticker');

        var animation = null;
        var resizeObserver = null;
        var frame = 0;
        var hovered = false;
        var focused = false;
        var pausedByTab = document.hidden;

        function clearAnimation() {
            if (animation) {
                animation.cancel();
                animation = null;
            }
        }

        function build() {
            clearAnimation();
            track.replaceChildren();
            var group = document.createElement('div');
            group.className = 'ba-ticker__mobile-group';
            source.forEach(function (item) {
                var copy = item.cloneNode(true);
                copy.classList.remove('ba-ticker__item--clone');
                copy.removeAttribute('data-js-ticker-item');
                group.appendChild(copy);
            });
            track.appendChild(group);
            var baseWidth = group.getBoundingClientRect().width;
            var repeat = Math.max(1, Math.ceil(viewport.clientWidth / Math.max(1, baseWidth)));
            for (var i = 1; i < repeat; i++) {
                source.forEach(function (item) {
                    var copy = item.cloneNode(true);
                    copy.classList.remove('ba-ticker__item--clone');
                    copy.removeAttribute('data-js-ticker-item');
                    group.appendChild(copy);
                });
            }
            var groupWidth = group.getBoundingClientRect().width;
            if (!autoplay || media.matches || !groupWidth) {
                track.style.transform = '';
                return;
            }
            var duplicate = group.cloneNode(true);
            duplicate.setAttribute('aria-hidden', 'true');
            duplicate.querySelectorAll('a,button,[tabindex]').forEach(function (element) {
                element.setAttribute('tabindex', '-1');
            });
            track.insertBefore(duplicate, group);
            animation = track.animate([
                { transform: 'translateX(' + (-groupWidth) + 'px)' },
                { transform: 'translateX(0px)' }
            ], {
                duration: (groupWidth / speed) * 1000,
                iterations: Infinity,
                easing: 'linear'
            });
            syncPause();
        }

        function syncPause() {
            if (animation) animation.playbackRate = hovered || focused || pausedByTab ? 0 : 1;
        }
        function onEnter() { hovered = true; syncPause(); }
        function onLeave() { hovered = false; syncPause(); }
        function onFocusIn() { focused = true; syncPause(); }
        function onFocusOut(event) {
            focused = !!(event.relatedTarget && ticker.contains(event.relatedTarget));
            syncPause();
        }
        function onVisibility() { pausedByTab = document.hidden; syncPause(); }
        function schedule() {
            cancelAnimationFrame(frame);
            frame = requestAnimationFrame(build);
        }

        ticker.addEventListener('mouseenter', onEnter);
        ticker.addEventListener('mouseleave', onLeave);
        ticker.addEventListener('focusin', onFocusIn);
        ticker.addEventListener('focusout', onFocusOut);
        document.addEventListener('visibilitychange', onVisibility);
        window.addEventListener('resize', schedule);
        if (media.addEventListener) media.addEventListener('change', schedule);
        if (window.ResizeObserver) {
            resizeObserver = new ResizeObserver(schedule);
            resizeObserver.observe(viewport);
        }
        if (document.fonts) document.fonts.ready.then(function () {
            if (track.isConnected) schedule();
        });
        build();

        cleanups.push(function () {
            cancelAnimationFrame(frame);
            clearAnimation();
            if (resizeObserver) resizeObserver.disconnect();
            ticker.removeEventListener('mouseenter', onEnter);
            ticker.removeEventListener('mouseleave', onLeave);
            ticker.removeEventListener('focusin', onFocusIn);
            ticker.removeEventListener('focusout', onFocusOut);
            document.removeEventListener('visibilitychange', onVisibility);
            window.removeEventListener('resize', schedule);
            if (media.removeEventListener) media.removeEventListener('change', schedule);
            track.remove();
            ticker.classList.remove('is-mobile-ticker');
        });
    }

    function initTicker(root, cleanups) {
        var breakpoint = window.matchMedia('(max-width: 760px)');
        var modeCleanups = [];
        var currentMode = null;

        function switchMode() {
            var mobile = breakpoint.matches;
            if (currentMode === mobile) return;
            modeCleanups.forEach(function (cleanup) { cleanup(); });
            modeCleanups = [];
            currentMode = mobile;
            if (mobile) initMobileTicker(root, modeCleanups);
            else initVerticalTicker(root, modeCleanups);
        }
        switchMode();
        if (breakpoint.addEventListener) breakpoint.addEventListener('change', switchMode);
        else breakpoint.addListener(switchMode);
        cleanups.push(function () {
            if (breakpoint.removeEventListener) breakpoint.removeEventListener('change', switchMode);
            else breakpoint.removeListener(switchMode);
            modeCleanups.forEach(function (cleanup) { cleanup(); });
        });
    }

    function init(root) {
        if (!root) return;

        destroy(root);

        var cleanups = [];
        states.set(root, { cleanups: cleanups });
        initSlider(root, cleanups);
        initTicker(root, cleanups);
    }

    function initDocument() {
        document.querySelectorAll('[data-ba-home-hero]').forEach(init);
    }

    var hooksRegistered = false;
    function registerElementor() {
        if (hooksRegistered || !window.elementorFrontend || !window.elementorFrontend.hooks) return;
        hooksRegistered = true;
        var Base = window.elementorModules && window.elementorModules.frontend.handlers.Base;
        var HeroHandler = Base && class extends Base {
            onInit() {
                super.onInit();
                this.heroRoot = this.$element[0].querySelector('[data-ba-home-hero]');
                init(this.heroRoot);
            }
            onDestroy() {
                if (this.heroRoot) destroy(this.heroRoot);
                super.onDestroy();
            }
        };
        window.elementorFrontend.hooks.addAction('frontend/element_ready/bonyad_alavi_home_hero.default', function ($scope) {
            if (HeroHandler) window.elementorFrontend.elementsHandler.addHandler(HeroHandler, { $element: $scope });
            else init($scope[0].querySelector('[data-ba-home-hero]'));
        });
        initDocument();
    }
    if (window.jQuery) window.jQuery(window).on('elementor/frontend/init', registerElementor);
    function boot() {
        registerElementor();
        if (!hooksRegistered) initDocument();
    }
    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', boot, { once: true });
    else boot();
})();

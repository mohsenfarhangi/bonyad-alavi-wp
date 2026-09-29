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
        if (!slider) return;

        var slides = Array.prototype.slice.call(slider.querySelectorAll('[data-js-slide]'));
        var dots = Array.prototype.slice.call(slider.querySelectorAll('[data-js-slider-dot]'));
        var arrows = Array.prototype.slice.call(slider.querySelectorAll('[data-js-slider-arrow]'));
        var index = 0;
        var timer = null;
        var autoplay = root.dataset.sliderAutoplay === '1';
        var delay = Math.max(1500, Number(root.dataset.sliderDelay) || 6200);
        var pauseOnHover = root.dataset.sliderPauseHover === '1';

        if (!slides.length) return;

        function show(next) {
            index = (next + slides.length) % slides.length;
            slides.forEach(function (slide, itemIndex) {
                slide.classList.toggle('is-active', itemIndex === index);
            });
            dots.forEach(function (dot, itemIndex) {
                dot.classList.toggle('is-active', itemIndex === index);
            });
        }

        function stop() {
            if (timer) {
                window.clearInterval(timer);
                timer = null;
            }
        }

        function auto() {
            stop();
            if (!autoplay || slides.length < 2) return;
            timer = window.setInterval(function () {
                if (!document.documentElement.contains(root)) {
                    stop();
                    return;
                }
                show(index + 1);
            }, delay);
        }

        arrows.forEach(function (button) {
            var handler = function () {
                show(index + (button.dataset.dir === 'next' ? 1 : -1));
                auto();
            };
            button.addEventListener('click', handler);
            cleanups.push(function () {
                button.removeEventListener('click', handler);
            });
        });

        dots.forEach(function (dot, dotIndex) {
            var handler = function () {
                show(dotIndex);
                auto();
            };
            dot.addEventListener('click', handler);
            cleanups.push(function () {
                dot.removeEventListener('click', handler);
            });
        });

        if (pauseOnHover) {
            var enter = function () {
                stop();
            };
            var leave = function () {
                auto();
            };
            slider.addEventListener('mouseenter', enter);
            slider.addEventListener('mouseleave', leave);
            cleanups.push(function () {
                slider.removeEventListener('mouseenter', enter);
                slider.removeEventListener('mouseleave', leave);
            });
        }

        cleanups.push(stop);
        show(0);
        auto();
    }

    function initTicker(root, cleanups) {
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

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDocument);
    } else {
        initDocument();
    }

    if (window.elementorFrontend && window.elementorFrontend.hooks) {
        window.elementorFrontend.hooks.addAction('frontend/element_ready/bonyad_alavi_home_hero.default', function ($scope) {
            init($scope[0].querySelector('[data-ba-home-hero]'));
        });
    }
})();

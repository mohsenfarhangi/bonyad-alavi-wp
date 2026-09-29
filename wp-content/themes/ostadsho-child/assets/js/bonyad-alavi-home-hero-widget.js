(function () {
    'use strict';

    var states = new WeakMap();

    function reducedMotion() {
        return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
    }

    function initSlider(root, cleanups) {
        var slider = root.querySelector('[data-ba-home-hero-slider]');
        if (!slider) return;

        var slides = Array.prototype.slice.call(slider.querySelectorAll('[data-ba-home-hero-slide]'));
        var dots = Array.prototype.slice.call(slider.querySelectorAll('[data-ba-home-hero-dot]'));
        var arrows = Array.prototype.slice.call(slider.querySelectorAll('[data-ba-home-hero-arrow]'));
        if (!slides.length) return;

        var index = 0;
        var timer = null;
        var autoplay = root.dataset.sliderAutoplay === '1' && slides.length > 1 && !reducedMotion();
        var delay = Math.max(1500, Number(root.dataset.sliderDelay) || 6200);
        var pauseHover = root.dataset.sliderPauseHover === '1';

        function show(next) {
            index = (next + slides.length) % slides.length;
            slides.forEach(function (slide, i) {
                var active = i === index;
                slide.classList.toggle('is-active', active);
                slide.setAttribute('aria-hidden', active ? 'false' : 'true');
                slide.querySelectorAll('a').forEach(function (link) { link.tabIndex = active ? 0 : -1; });
            });
            dots.forEach(function (dot, i) {
                var active = i === index;
                dot.classList.toggle('is-active', active);
                dot.setAttribute('aria-current', active ? 'true' : 'false');
            });
        }

        function stop() {
            if (timer) {
                window.clearInterval(timer);
                timer = null;
            }
        }

        function start() {
            stop();
            if (!autoplay) return;
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
                start();
            };
            button.addEventListener('click', handler);
            cleanups.push(function () { button.removeEventListener('click', handler); });
        });

        dots.forEach(function (dot, i) {
            var handler = function () {
                show(i);
                start();
            };
            dot.addEventListener('click', handler);
            cleanups.push(function () { dot.removeEventListener('click', handler); });
        });

        var keyHandler = function (event) {
            if (event.key === 'ArrowLeft') {
                event.preventDefault();
                show(index - 1);
                start();
            } else if (event.key === 'ArrowRight') {
                event.preventDefault();
                show(index + 1);
                start();
            }
        };
        slider.addEventListener('keydown', keyHandler);
        cleanups.push(function () { slider.removeEventListener('keydown', keyHandler); });

        if (pauseHover && autoplay) {
            var pause = function () { stop(); };
            var resume = function (event) {
                if (!event || !slider.contains(event.relatedTarget)) start();
            };
            slider.addEventListener('mouseenter', pause);
            slider.addEventListener('mouseleave', resume);
            slider.addEventListener('focusin', pause);
            slider.addEventListener('focusout', resume);
            cleanups.push(function () {
                slider.removeEventListener('mouseenter', pause);
                slider.removeEventListener('mouseleave', resume);
                slider.removeEventListener('focusin', pause);
                slider.removeEventListener('focusout', resume);
            });
        }

        cleanups.push(stop);
        show(0);
        start();
    }

    function initTicker(root, cleanups) {
        var ticker = root.querySelector('[data-ba-home-hero-ticker]');
        var viewport = root.querySelector('[data-ba-home-hero-ticker-viewport]');
        var track = root.querySelector('[data-ba-home-hero-ticker-track]');
        if (!ticker || !viewport || !track) return;

        var items = Array.prototype.slice.call(track.querySelectorAll('[data-ba-home-hero-ticker-item]'));
        if (items.length < 2 || root.dataset.tickerAutoplay !== '1' || reducedMotion()) return;

        var clone = items[0].cloneNode(true);
        clone.setAttribute('aria-hidden', 'true');
        clone.tabIndex = -1;
        clone.removeAttribute('data-ba-home-hero-ticker-item');
        track.appendChild(clone);

        var index = 0;
        var timer = null;
        var resetting = false;
        var delay = Math.max(1500, Number(root.dataset.tickerDelay) || 6000);

        function height() {
            return viewport.clientHeight || 48;
        }

        function position(animate) {
            track.style.transition = animate ? '' : 'none';
            track.style.transform = 'translateY(' + (-index * height()) + 'px)';
            if (!animate) {
                void track.offsetHeight;
                track.style.transition = '';
            }
        }

        function next() {
            if (resetting) return;
            index += 1;
            position(true);
            if (index === items.length) {
                resetting = true;
                window.setTimeout(function () {
                    index = 0;
                    position(false);
                    resetting = false;
                }, 470);
            }
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
                if (!document.documentElement.contains(root)) {
                    stop();
                    return;
                }
                next();
            }, delay);
        }

        var pause = function () { stop(); };
        var resume = function (event) {
            if (!event || !ticker.contains(event.relatedTarget)) start();
        };
        var resize = function () { position(false); };

        ticker.addEventListener('mouseenter', pause);
        ticker.addEventListener('mouseleave', resume);
        ticker.addEventListener('focusin', pause);
        ticker.addEventListener('focusout', resume);
        window.addEventListener('resize', resize);

        cleanups.push(function () {
            stop();
            ticker.removeEventListener('mouseenter', pause);
            ticker.removeEventListener('mouseleave', resume);
            ticker.removeEventListener('focusin', pause);
            ticker.removeEventListener('focusout', resume);
            window.removeEventListener('resize', resize);
            if (clone.parentNode === track) track.removeChild(clone);
            track.style.transform = '';
        });

        start();
    }

    function destroy(root) {
        var state = states.get(root);
        if (!state) return;
        state.cleanups.forEach(function (cleanup) {
            try { cleanup(); } catch (error) {}
        });
        states.delete(root);
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

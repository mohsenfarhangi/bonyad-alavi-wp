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

    function initFunding(root, cleanups) {
        var impactFunding = root.querySelector('[data-js-impact-funding]');
        if (!impactFunding) return;

        var impactCounters = Array.prototype.slice.call(impactFunding.querySelectorAll('[data-impact-counter]'));
        var impactSegments = Array.prototype.slice.call(impactFunding.querySelectorAll('[data-js-impact-segment]'));
        var impactSources = Array.prototype.slice.call(impactFunding.querySelectorAll('[data-js-impact-source]'));
        var reduceImpactMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var impactLayoutFrame = null;
        var counterFrames = [];
        var counterTimeouts = [];
        var observer = null;

        function formatImpactNumber(value, decimals) {
            return new Intl.NumberFormat('fa-IR', {
                minimumFractionDigits: decimals,
                maximumFractionDigits: decimals
            }).format(value);
        }

        function renderImpactCounter(counter, value) {
            var decimals = Number(counter.dataset.decimals || 0);
            var suffix = counter.dataset.suffix || '';
            counter.textContent = formatImpactNumber(value, decimals) + suffix;
        }

        function animateImpactCounter(counter) {
            var target = Number(counter.dataset.value || 0);
            var duration = 1150;
            var startedAt = performance.now();

            function frame(now) {
                var progress = Math.min(1, (now - startedAt) / duration);
                var eased = 1 - Math.pow(1 - progress, 3);
                renderImpactCounter(counter, target * eased);
                if (progress < 1) {
                    counterFrames.push(window.requestAnimationFrame(frame));
                }
            }

            counterFrames.push(window.requestAnimationFrame(frame));
        }

        function setImpactSourceActive(source, active) {
            impactSegments.concat(impactSources).forEach(function (element) {
                if (element.dataset.impactSource === source) {
                    element.classList.toggle('is-active', active);
                }
            });
        }

        function positionImpactSourceCards() {
            var visual = impactFunding.querySelector('.ba-impact__funding-visual');
            var chartWrap = impactFunding.querySelector('.ba-impact__chart-wrap');
            if (!visual || !chartWrap) return;

            if (window.innerWidth <= 760) {
                impactSources.forEach(function (source) {
                    source.style.removeProperty('left');
                    source.style.removeProperty('top');
                    source.style.removeProperty('--ba-impact-connector-angle');
                    source.style.removeProperty('--ba-impact-connector-length');
                    source.style.removeProperty('--ba-impact-connector-start-x');
                    source.style.removeProperty('--ba-impact-connector-start-y');
                });
                return;
            }

            var visualRect = visual.getBoundingClientRect();
            var chartRect = chartWrap.getBoundingClientRect();
            var centerX = chartRect.left - visualRect.left + chartRect.width / 2;
            var centerY = chartRect.top - visualRect.top + chartRect.height / 2;
            var chartOuterRadius = chartRect.width * .38;
            var orbitRadius = chartRect.width * .68;

            impactSources.forEach(function (source) {
                var angle = Number(source.dataset.impactAngle || 0) * Math.PI / 180;
                var cardWidth = source.offsetWidth;
                var cardHeight = source.offsetHeight;
                var cardCenterX = centerX + Math.sin(angle) * orbitRadius;
                var cardCenterY = centerY - Math.cos(angle) * orbitRadius;
                var halfWidth = cardWidth / 2;
                var halfHeight = cardHeight / 2;

                cardCenterX = Math.max(halfWidth + 3, Math.min(visualRect.width - halfWidth - 3, cardCenterX));
                cardCenterY = Math.max(halfHeight + 3, Math.min(visualRect.height - halfHeight - 3, cardCenterY));

                source.style.left = cardCenterX + 'px';
                source.style.top = cardCenterY + 'px';

                var dx = centerX - cardCenterX;
                var dy = centerY - cardCenterY;
                var distance = Math.hypot(dx, dy) || 1;
                var unitX = dx / distance;
                var unitY = dy / distance;
                var edgeDistanceX = Math.abs(unitX) > .001 ? halfWidth / Math.abs(unitX) : Infinity;
                var edgeDistanceY = Math.abs(unitY) > .001 ? halfHeight / Math.abs(unitY) : Infinity;
                var cardEdgeDistance = Math.min(edgeDistanceX, edgeDistanceY);
                var connectorLength = Math.max(12, distance - cardEdgeDistance - chartOuterRadius + 5);
                var startX = halfWidth + unitX * cardEdgeDistance;
                var startY = halfHeight + unitY * cardEdgeDistance;
                var connectorAngle = Math.atan2(unitY, unitX);

                source.style.setProperty('--ba-impact-connector-start-x', startX + 'px');
                source.style.setProperty('--ba-impact-connector-start-y', startY + 'px');
                source.style.setProperty('--ba-impact-connector-length', connectorLength + 'px');
                source.style.setProperty('--ba-impact-connector-angle', connectorAngle + 'rad');
            });
        }

        function bindHover(element) {
            var enter = function () {
                setImpactSourceActive(element.dataset.impactSource, true);
            };
            var leave = function () {
                setImpactSourceActive(element.dataset.impactSource, false);
            };

            element.addEventListener('mouseenter', enter);
            element.addEventListener('mouseleave', leave);
            cleanups.push(function () {
                element.removeEventListener('mouseenter', enter);
                element.removeEventListener('mouseleave', leave);
            });
        }

        impactSegments.forEach(bindHover);
        impactSources.forEach(bindHover);

        positionImpactSourceCards();

        var resize = function () {
            if (impactLayoutFrame) window.cancelAnimationFrame(impactLayoutFrame);
            impactLayoutFrame = window.requestAnimationFrame(positionImpactSourceCards);
        };

        window.addEventListener('resize', resize);
        cleanups.push(function () {
            window.removeEventListener('resize', resize);
            if (impactLayoutFrame) window.cancelAnimationFrame(impactLayoutFrame);
        });

        function revealImpactFunding() {
            impactFunding.classList.add('is-visible');
            impactCounters.forEach(function (counter, index) {
                var timeout = window.setTimeout(function () {
                    animateImpactCounter(counter);
                }, index * 55);
                counterTimeouts.push(timeout);
            });
        }

        if (reduceImpactMotion || !('IntersectionObserver' in window)) {
            impactFunding.classList.add('is-visible');
            impactCounters.forEach(function (counter) {
                renderImpactCounter(counter, Number(counter.dataset.value || 0));
            });
        } else {
            impactFunding.classList.add('is-animation-ready');
            observer = new IntersectionObserver(function (entries) {
                if (!entries.some(function (entry) { return entry.isIntersecting; })) return;
                revealImpactFunding();
                observer.disconnect();
                observer = null;
            }, { threshold: .3 });
            observer.observe(impactFunding);
        }

        cleanups.push(function () {
            if (observer) observer.disconnect();
            counterTimeouts.forEach(function (timeout) {
                window.clearTimeout(timeout);
            });
            counterFrames.forEach(function (frame) {
                window.cancelAnimationFrame(frame);
            });
            impactFunding.classList.remove('is-animation-ready', 'is-visible');
            impactSegments.concat(impactSources).forEach(function (element) {
                element.classList.remove('is-active');
            });
        });
    }

    function init(root) {
        if (!root) return;

        destroy(root);

        var cleanups = [];
        states.set(root, { cleanups: cleanups });
        initFunding(root, cleanups);
    }

    function initDocument() {
        document.querySelectorAll('[data-ba-home-about]').forEach(init);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDocument);
    } else {
        initDocument();
    }

    if (window.elementorFrontend && window.elementorFrontend.hooks) {
        window.elementorFrontend.hooks.addAction('frontend/element_ready/bonyad_alavi_home_about.default', function ($scope) {
            init($scope[0].querySelector('[data-ba-home-about]'));
        });
    }
})();

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

        var impactSegments = Array.prototype.slice.call(impactFunding.querySelectorAll('[data-js-impact-segment]'));
        var impactSources = Array.prototype.slice.call(impactFunding.querySelectorAll('[data-js-impact-source]'));
        var impactTotalCounters = Array.prototype.slice.call(impactFunding.querySelectorAll('[data-impact-total]'));
        var impactDesc = impactFunding.querySelector('[data-js-impact-desc]');
        var reduceImpactMotion = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        var impactSegmentGap = 1;
        var impactCardGap = 10;
        var impactVisualInset = 8;
        var impactUnit = impactFunding.dataset.impactUnit || '';
        var impactDecimals = Number(impactFunding.dataset.impactDecimals || 1);
        var impactLayoutFrame = null;
        var impactDataInitialized = false;
        var impactResizeObserver = null;
        var impactValueObserver = null;
        var impactObserver = null;
        var counterFrames = [];
        var counterTimeouts = [];

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

        function getImpactSourceData() {
            return impactSources.map(function (source, index) {
                var value = Math.max(0, Number(source.dataset.impactValue) || 0);
                var sourceId = source.dataset.impactSource;
                var labelNode = source.querySelector('.ba-impact__source-label');
                var label = labelNode ? labelNode.textContent.trim() : sourceId;
                var counter = source.querySelector('[data-impact-counter]');
                var segment = impactSegments.find(function (item) {
                    return item.dataset.impactSource === sourceId;
                });

                return {
                    source: source,
                    sourceId: sourceId,
                    label: label,
                    value: value,
                    counter: counter,
                    segment: segment,
                    index: index
                };
            });
        }

        function syncImpactFundingData() {
            var sourceData = getImpactSourceData();
            var total = sourceData.reduce(function (sum, item) {
                return sum + item.value;
            }, 0);
            var cumulativeShare = 0;

            sourceData.forEach(function (item, index) {
                var rawShare = total > 0 ? item.value / total * 100 : 0;
                var gap = rawShare > 0 ? Math.min(impactSegmentGap, rawShare * .22) : 0;
                var visibleShare = Math.max(0, rawShare - gap);
                var angleShare = total > 0
                    ? cumulativeShare + visibleShare / 2
                    : (index + .5) / Math.max(1, sourceData.length) * 100;
                var angle = angleShare * 3.6;

                item.source.dataset.impactAngle = angle.toFixed(4);

                if (item.segment) {
                    item.segment.style.setProperty('--ba-impact-segment-dash', visibleShare.toFixed(4) + ' ' + (100 - visibleShare).toFixed(4));
                    item.segment.style.setProperty('--ba-impact-segment-offset', (-cumulativeShare).toFixed(4));
                    item.segment.dataset.impactValue = String(item.value);

                    var itemDecimals = item.counter ? Number(item.counter.dataset.decimals || impactDecimals) : impactDecimals;
                    var itemSuffix = item.counter ? (item.counter.dataset.suffix || '') : (impactUnit ? ' ' + impactUnit : '');
                    item.segment.setAttribute('aria-label', item.label + '، ' + formatImpactNumber(item.value, itemDecimals) + itemSuffix);
                }

                if (item.counter) {
                    item.counter.dataset.value = String(item.value);
                    renderImpactCounter(item.counter, item.value);
                }

                cumulativeShare += rawShare;
            });

            impactTotalCounters.forEach(function (counter) {
                counter.dataset.value = String(total);
                renderImpactCounter(counter, total);
            });

            if (impactDesc) {
                var details = sourceData.map(function (item) {
                    return item.label + ' ' + formatImpactNumber(item.value, impactDecimals) + (impactUnit ? ' ' + impactUnit : '');
                }).join('، ');
                impactDesc.textContent = 'مقادیر منابع: ' + details + '. مجموع منابع ' + formatImpactNumber(total, impactDecimals) + (impactUnit ? ' ' + impactUnit : '') + '.';
            }

            impactDataInitialized = true;
            positionImpactSourceCards();
        }

        function setImpactSourceActive(source, active) {
            impactSegments.concat(impactSources).forEach(function (element) {
                if (element.dataset.impactSource === source) {
                    element.classList.toggle('is-active', active);
                }
            });
        }

        function resolveImpactCardCollisions(items, visualHeight, inset) {
            var gap = 8;

            ['left', 'right'].forEach(function (side) {
                var group = items
                    .filter(function (item) {
                        return item.side === side;
                    })
                    .sort(function (a, b) {
                        return a.cardCenterY - b.cardCenterY;
                    });

                if (!group.length) return;

                group.forEach(function (item, index) {
                    var minY = item.halfHeight + inset;
                    if (index === 0) {
                        item.cardCenterY = Math.max(minY, item.cardCenterY);
                        return;
                    }

                    var previous = group[index - 1];
                    var requiredY = previous.cardCenterY + previous.halfHeight + gap + item.halfHeight;
                    item.cardCenterY = Math.max(item.cardCenterY, requiredY);
                });

                for (var index = group.length - 1; index >= 0; index--) {
                    var item = group[index];
                    var maxY = visualHeight - item.halfHeight - inset;

                    if (index === group.length - 1) {
                        item.cardCenterY = Math.min(maxY, item.cardCenterY);
                        continue;
                    }

                    var next = group[index + 1];
                    var allowedY = next.cardCenterY - next.halfHeight - gap - item.halfHeight;
                    item.cardCenterY = Math.min(item.cardCenterY, allowedY);
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

            var layoutItems = impactSources.map(function (source) {
                var angle = Number(source.dataset.impactAngle || 0) * Math.PI / 180;
                var radialX = Math.sin(angle);
                var radialY = -Math.cos(angle);
                var cardWidth = source.offsetWidth;
                var cardHeight = source.offsetHeight;
                var halfWidth = cardWidth / 2;
                var halfHeight = cardHeight / 2;
                var edgeDistanceX = Math.abs(radialX) > .001 ? halfWidth / Math.abs(radialX) : Infinity;
                var edgeDistanceY = Math.abs(radialY) > .001 ? halfHeight / Math.abs(radialY) : Infinity;
                var cardEdgeDistance = Math.min(edgeDistanceX, edgeDistanceY);
                var cardRadius = chartOuterRadius + impactCardGap + cardEdgeDistance;
                var cardCenterX = centerX + radialX * cardRadius;
                var cardCenterY = centerY + radialY * cardRadius;

                cardCenterX = Math.max(
                    halfWidth + impactVisualInset,
                    Math.min(visualRect.width - halfWidth - impactVisualInset, cardCenterX)
                );
                cardCenterY = Math.max(
                    halfHeight + impactVisualInset,
                    Math.min(visualRect.height - halfHeight - impactVisualInset, cardCenterY)
                );

                return {
                    source: source,
                    angle: angle,
                    halfWidth: halfWidth,
                    halfHeight: halfHeight,
                    cardCenterX: cardCenterX,
                    cardCenterY: cardCenterY,
                    side: radialX >= 0 ? 'right' : 'left'
                };
            });

            resolveImpactCardCollisions(layoutItems, visualRect.height, impactVisualInset);

            layoutItems.forEach(function (item) {
                var source = item.source;
                var angle = item.angle;
                var halfWidth = item.halfWidth;
                var halfHeight = item.halfHeight;
                var cardCenterX = item.cardCenterX;
                var cardCenterY = item.cardCenterY;
                var segmentX = centerX + Math.sin(angle) * chartOuterRadius;
                var segmentY = centerY - Math.cos(angle) * chartOuterRadius;
                var dx = segmentX - cardCenterX;
                var dy = segmentY - cardCenterY;
                var distance = Math.hypot(dx, dy) || 1;
                var unitX = dx / distance;
                var unitY = dy / distance;
                var edgeDistanceX = Math.abs(unitX) > .001 ? halfWidth / Math.abs(unitX) : Infinity;
                var edgeDistanceY = Math.abs(unitY) > .001 ? halfHeight / Math.abs(unitY) : Infinity;
                var cardEdgeDistance = Math.min(edgeDistanceX, edgeDistanceY);
                var startX = halfWidth + unitX * cardEdgeDistance;
                var startY = halfHeight + unitY * cardEdgeDistance;
                var connectorLength = Math.max(0, distance - cardEdgeDistance);
                var connectorAngle = Math.atan2(unitY, unitX);

                source.style.left = cardCenterX + 'px';
                source.style.top = cardCenterY + 'px';
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

        syncImpactFundingData();

        var resize = function () {
            if (impactLayoutFrame) window.cancelAnimationFrame(impactLayoutFrame);
            impactLayoutFrame = window.requestAnimationFrame(positionImpactSourceCards);
        };
        window.addEventListener('resize', resize);
        cleanups.push(function () {
            window.removeEventListener('resize', resize);
        });

        if ('ResizeObserver' in window) {
            impactResizeObserver = new ResizeObserver(function () {
                if (impactLayoutFrame) window.cancelAnimationFrame(impactLayoutFrame);
                impactLayoutFrame = window.requestAnimationFrame(positionImpactSourceCards);
            });

            var visual = impactFunding.querySelector('.ba-impact__funding-visual');
            if (visual) impactResizeObserver.observe(visual);
        }

        if ('MutationObserver' in window) {
            impactValueObserver = new MutationObserver(function (mutations) {
                var hasValueChange = mutations.some(function (mutation) {
                    return mutation.type === 'attributes' && mutation.attributeName === 'data-impact-value';
                });
                if (!hasValueChange) return;
                syncImpactFundingData();
            });

            impactSources.forEach(function (source) {
                impactValueObserver.observe(source, {
                    attributes: true,
                    attributeFilter: ['data-impact-value']
                });
            });
        }

        function revealImpactFunding() {
            impactFunding.classList.add('is-visible');
            var impactCounters = Array.prototype.slice.call(impactFunding.querySelectorAll('[data-impact-counter]'));

            impactCounters.forEach(function (counter, index) {
                var timeout = window.setTimeout(function () {
                    animateImpactCounter(counter);
                }, index * 55);
                counterTimeouts.push(timeout);
            });
        }

        if (reduceImpactMotion || !('IntersectionObserver' in window)) {
            impactFunding.classList.add('is-visible');
        } else {
            impactFunding.classList.add('is-animation-ready');
            impactObserver = new IntersectionObserver(function (entries) {
                var visible = entries.some(function (entry) {
                    return entry.isIntersecting;
                });
                if (!visible || !impactDataInitialized) return;

                revealImpactFunding();
                impactObserver.disconnect();
                impactObserver = null;
            }, { threshold: .3 });

            impactObserver.observe(impactFunding);
        }

        cleanups.push(function () {
            if (impactLayoutFrame) window.cancelAnimationFrame(impactLayoutFrame);
            if (impactResizeObserver) impactResizeObserver.disconnect();
            if (impactValueObserver) impactValueObserver.disconnect();
            if (impactObserver) impactObserver.disconnect();

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

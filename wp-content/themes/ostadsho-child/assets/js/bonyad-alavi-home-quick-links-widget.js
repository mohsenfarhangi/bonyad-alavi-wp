(function () {
    'use strict';

    var states = new WeakMap();

    function destroy(root) {
        var state = states.get(root);
        if (state) state.cleanup();
        states.delete(root);
        return !!(state && state.expanded);
    }

    function init(root) {
        if (!root) return;

        var expanded = destroy(root);
        var quick = root.querySelector('[data-js-quick-list]');
        var more = root.querySelector('[data-js-quick-more]');
        var moreText = root.querySelector('[data-js-quick-more-text]');
        if (!quick || !more || !moreText) return;

        var items = Array.prototype.slice.call(quick.querySelectorAll('[data-js-quick-item]'));
        var mobile = window.matchMedia('(max-width: 760px)');
        var mobileVisibleCount = 6;
        var frame = 0;
        var lastWidth = -1;
        var lastMobile = null;
        var disposed = false;

        function getQuickGap() {
            var styles = window.getComputedStyle(quick);
            return parseFloat(styles.columnGap || styles.gap) || 0;
        }

        function itemWidth(item, scale) {
            return item.getBoundingClientRect().width / scale;
        }

        function totalItemsWidth(gap, scale) {
            return items.reduce(function (sum, item) {
                return sum + itemWidth(item, scale);
            }, 0) + Math.max(0, items.length - 1) * gap;
        }

        function refreshQuick() {
            if (disposed) return;
            lastWidth = quick.clientWidth;
            lastMobile = mobile.matches;
            quick.classList.remove('is-expanded');
            items.forEach(function (item) {
                item.classList.remove('is-hidden-overflow');
            });
            more.style.display = 'none';

            var visibleCount = items.length;
            if (mobile.matches) {
                visibleCount = Math.min(mobileVisibleCount, items.length);
            } else {
                var css = window.getComputedStyle(quick);
                var availableWidth = quick.clientWidth - (parseFloat(css.paddingLeft) || 0) - (parseFloat(css.paddingRight) || 0);
                var scale = quick.offsetWidth ? quick.getBoundingClientRect().width / quick.offsetWidth : 1;
                scale = scale || 1;
                var gap = getQuickGap();

                if (totalItemsWidth(gap, scale) > availableWidth + 1) {
                    more.style.display = 'flex';
                    var usedWidth = itemWidth(more, scale);
                    visibleCount = 0;
                    for (const item of items) {
                        var nextWidth = usedWidth + gap + itemWidth(item, scale);
                        if (nextWidth > availableWidth + 1) break;
                        usedWidth = nextWidth;
                        visibleCount++;
                    }
                    visibleCount = Math.max(1, visibleCount);
                }
            }

            var hasOverflow = visibleCount < items.length;
            more.style.display = hasOverflow ? 'flex' : 'none';
            more.setAttribute('aria-expanded', String(expanded));
            moreText.textContent = expanded ? 'جمع کردن' : 'بیشتر';
            if (expanded) quick.classList.add('is-expanded');
            items.forEach(function (item, index) {
                item.classList.toggle('is-hidden-overflow', !expanded && index >= visibleCount);
            });
            states.get(root).expanded = expanded;
        }

        function onMoreClick() {
            expanded = !expanded;
            refreshQuick();
        }

        function scheduleRefresh() {
            if (disposed) return;
            cancelAnimationFrame(frame);
            frame = requestAnimationFrame(refreshQuick);
        }

        function onResize() {
            // Mobile browser chrome can change height while scrolling; keep the user's choice.
            if (quick.clientWidth === lastWidth && mobile.matches === lastMobile) return;
            scheduleRefresh();
        }

        var observer = typeof ResizeObserver === 'function' ? new ResizeObserver(onResize) : null;
        states.set(root, {
            expanded: expanded,
            cleanup: function () {
                disposed = true;
                cancelAnimationFrame(frame);
                more.removeEventListener('click', onMoreClick);
                window.removeEventListener('resize', onResize);
                mobile.removeEventListener('change', scheduleRefresh);
                if (observer) observer.disconnect();
                if (document.fonts) document.fonts.removeEventListener('loadingdone', scheduleRefresh);
            }
        });

        more.addEventListener('click', onMoreClick);
        window.addEventListener('resize', onResize, { passive: true });
        mobile.addEventListener('change', scheduleRefresh);
        if (observer) observer.observe(quick);
        if (document.fonts) {
            document.fonts.ready.then(scheduleRefresh);
            document.fonts.addEventListener('loadingdone', scheduleRefresh);
        }
        refreshQuick();
    }

    function initDocument() {
        document.querySelectorAll('[data-ba-home-quick-links]').forEach(init);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDocument);
    } else {
        initDocument();
    }

    if (window.elementorFrontend && window.elementorFrontend.hooks) {
        window.elementorFrontend.hooks.addAction('frontend/element_ready/bonyad_alavi_home_quick_links.default', function ($scope) {
            init($scope[0].querySelector('[data-ba-home-quick-links]'));
        });
    }
})();

(function () {
    'use strict';

    var states = new WeakMap();

    function destroy(root) {
        var cleanup = states.get(root);
        if (typeof cleanup === 'function') {
            cleanup();
        }
        states.delete(root);
    }

    function init(root) {
        if (!root) return;

        destroy(root);

        var quick = root.querySelector('[data-js-quick-list]');
        var more = root.querySelector('[data-js-quick-more]');
        var moreText = root.querySelector('[data-js-quick-more-text]');

        if (!quick || !more || !moreText) return;

        var items = Array.prototype.slice.call(quick.querySelectorAll('[data-js-quick-item]'));
        var expanded = false;

        function getQuickGap() {
            var styles = window.getComputedStyle(quick);
            return parseFloat(styles.columnGap || styles.gap) || 0;
        }

        function totalItemsWidth(gap) {
            return items.reduce(function (sum, item) {
                return sum + item.getBoundingClientRect().width;
            }, 0) + Math.max(0, items.length - 1) * gap;
        }

        function setMoreState(isExpanded) {
            expanded = isExpanded;
            more.setAttribute('aria-expanded', String(isExpanded));
            moreText.textContent = isExpanded ? 'جمع کردن' : 'بیشتر';
        }

        function collapseQuick() {
            setMoreState(false);
            quick.classList.remove('is-expanded');
            items.forEach(function (item) {
                item.classList.remove('is-hidden-overflow');
            });
            more.style.display = 'none';

            var availableWidth = quick.clientWidth;
            var gap = getQuickGap();

            if (totalItemsWidth(gap) <= availableWidth + 1) return;

            more.style.display = 'flex';
            var moreWidth = more.getBoundingClientRect().width;
            var usedWidth = moreWidth;
            var visibleCount = 0;

            items.forEach(function (item) {
                if (visibleCount < 0) return;
                var itemWidth = item.getBoundingClientRect().width;
                var nextWidth = usedWidth + gap + itemWidth;
                if (nextWidth > availableWidth + 1) {
                    visibleCount = -visibleCount - 1;
                    return;
                }
                usedWidth = nextWidth;
                visibleCount++;
            });

            if (visibleCount < 0) {
                visibleCount = -visibleCount - 1;
            }

            visibleCount = Math.max(1, visibleCount);
            items.forEach(function (item, index) {
                item.classList.toggle('is-hidden-overflow', index >= visibleCount);
            });
        }

        function expandQuick() {
            setMoreState(true);
            quick.classList.add('is-expanded');
            items.forEach(function (item) {
                item.classList.remove('is-hidden-overflow');
            });
            more.style.display = 'flex';
        }

        function onMoreClick() {
            if (expanded) collapseQuick();
            else expandQuick();
        }

        more.addEventListener('click', onMoreClick);
        window.addEventListener('resize', collapseQuick);
        collapseQuick();

        states.set(root, function () {
            more.removeEventListener('click', onMoreClick);
            window.removeEventListener('resize', collapseQuick);
        });
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

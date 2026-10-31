(function () {
    'use strict';

    /**
     * دکمه play وسط پوستر: ویدئو را با کنترل‌های بومی پخش می‌کند.
     */
    function initVideo(root) {
        if (!root || root.dataset.baSessionVideoReady) return;
        var video = root.querySelector('[data-ba-session-video-media]');
        var button = root.querySelector('[data-ba-session-video-play]');
        if (!video || !button) return;
        root.dataset.baSessionVideoReady = '1';

        button.addEventListener('click', function () {
            root.classList.add('is-playing');
            video.setAttribute('controls', '');
            video.preload = 'auto';
            var playing = video.play();
            if (playing && typeof playing.catch === 'function') {
                playing.catch(function () { /* مرورگر پخش را رد کرد؛ کنترل‌ها در دسترس‌اند. */ });
            }
            video.focus();
        });

        video.addEventListener('ended', function () {
            root.classList.remove('is-playing');
            video.removeAttribute('controls');
        });
    }

    function initDocument() {
        document.querySelectorAll('[data-ba-session-video]').forEach(initVideo);
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
            hooks.addAction('frontend/element_ready/bonyad_alavi_studies_session_video.default', function ($scope) {
                initVideo($scope[0].querySelector('[data-ba-session-video]'));
            });
        });
    }
})();

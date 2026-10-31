(function ($) {
    'use strict';

    if (!$ || !window.wp || !window.wp.media) {
        return;
    }

    var labels = window.BAStudiesSessionAdmin || {};

    /**
     * انتخابگر Media Library برای ویدئو و فایل‌های نشست؛ ردیف‌های جدید Repeater
     * هم با event delegation پشتیبانی می‌شوند.
     */
    $(document).on('click', '[data-ba-session-media-select]', function (event) {
        event.preventDefault();
        var $field = $(this).closest('[data-ba-session-media]');
        var type = $field.data('baSessionMediaType') || '';
        var frame = window.wp.media({
            title: type === 'video' ? labels.videoTitle : labels.fileTitle,
            button: { text: labels.useButton },
            library: type ? { type: type } : {},
            multiple: false
        });

        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            $field.find('[data-ba-session-media-input]').val(attachment.id).trigger('change');
            $field.find('[data-ba-session-media-name]').text(attachment.filename || attachment.title || '');
            $field.find('[data-ba-session-media-remove]').prop('hidden', false);
        });

        frame.open();
    });

    $(document).on('click', '[data-ba-session-media-remove]', function (event) {
        event.preventDefault();
        var $field = $(this).closest('[data-ba-session-media]');
        $field.find('[data-ba-session-media-input]').val('').trigger('change');
        $field.find('[data-ba-session-media-name]').text('');
        $(this).prop('hidden', true);
    });
})(window.jQuery);

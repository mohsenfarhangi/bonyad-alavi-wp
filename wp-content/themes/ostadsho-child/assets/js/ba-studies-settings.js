(function ($) {
    'use strict';

    /**
     * انتخاب تصویر اسلایدر Hero از کتابخانه رسانه (تب «مرکز مطالعات»).
     *
     * @param {jQuery} $field ریشه فیلد رسانه.
     */
    function openMediaFrame($field) {
        var frame = wp.media({
            title: BAStudiesSettings.mediaTitle,
            button: { text: BAStudiesSettings.mediaButton },
            library: { type: 'image' },
            multiple: false
        });

        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            var mime = String(attachment.mime || '');

            if (mime.indexOf('image/') !== 0 || mime === 'image/svg+xml') {
                window.alert(BAStudiesSettings.notImage);
                return;
            }

            var preview = attachment.sizes && attachment.sizes.medium ? attachment.sizes.medium.url : attachment.url;

            $field.find('[data-ba-studies-media-input]').val(attachment.id).trigger('change');
            $field.find('[data-ba-studies-media-preview]').empty().append($('<img>', { src: preview, alt: '' }));
            $field.find('[data-ba-studies-media-remove]').prop('hidden', false);
        });

        frame.open();
    }

    $(document).on('click', '[data-ba-studies-media-select]', function () {
        openMediaFrame($(this).closest('[data-ba-studies-media]'));
    });

    $(document).on('click', '[data-ba-studies-media-remove]', function () {
        var $field = $(this).closest('[data-ba-studies-media]');

        $field.find('[data-ba-studies-media-input]').val('').trigger('change');
        $field.find('[data-ba-studies-media-preview]').empty();
        $(this).prop('hidden', true);
    });
})(jQuery);

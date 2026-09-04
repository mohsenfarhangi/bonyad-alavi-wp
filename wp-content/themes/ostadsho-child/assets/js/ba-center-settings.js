(function ($) {
    'use strict';

    /**
     * کنترل انتخاب رسانه در تب تنظیمات مرکز را باز می‌کند.
     *
     * @param {jQuery} $field ریشه فیلد رسانه.
     */
    function openMediaFrame($field) {
        var frame = wp.media({
            title: BACenterSettings.mediaTitle,
            button: { text: BACenterSettings.mediaButton },
            multiple: false
        });

        frame.on('select', function () {
            var attachment = frame.state().get('selection').first().toJSON();
            var accept = String($field.data('accept') || '');

            if (accept === 'image/svg+xml' && attachment.mime !== 'image/svg+xml') {
                window.alert('برای این فیلد یک فایل SVG انتخاب کنید.');
                return;
            }

            $field.find('[data-ba-media-input]').val(attachment.id).trigger('change');
            $field.find('[data-ba-media-preview]').html('<img src="' + attachment.url + '" alt="">');
            $field.find('[data-ba-media-remove]').prop('hidden', false);
        });

        frame.open();
    }

    $(document).on('click', '[data-ba-media-select]', function () {
        openMediaFrame($(this).closest('[data-ba-media-field]'));
    });

    $(document).on('click', '[data-ba-media-remove]', function () {
        var $field = $(this).closest('[data-ba-media-field]');
        $field.find('[data-ba-media-input]').val('').trigger('change');
        $field.find('[data-ba-media-preview]').empty();
        $(this).prop('hidden', true);
    });

    $(document).on('change', '[data-ba-media-type-switch]', function () {
        var $row = $(this).closest('[data-ba-partner-row]');
        var type = this.checked ? 'svg' : 'image';
        $row.find('[data-ba-media-type-value]').val(type).trigger('change');
        $row.find('[data-ba-media-mode]').prop('hidden', true);
        $row.find('[data-ba-media-mode="' + type + '"]').prop('hidden', false);
    });
})(jQuery);

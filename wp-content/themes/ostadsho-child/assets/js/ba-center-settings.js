(function ($) {
    'use strict';

    /**
     * کنترل انتخاب رسانه در تب تنظیمات مرکز.
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

            $field.find('[data-ba-media-input]').val(attachment.id);
            $field.find('[data-ba-media-preview]').html('<img src="' + attachment.url + '" alt="">');
            $field.find('[data-ba-media-remove]').prop('hidden', false);
        });

        frame.open();
    }

    /**
     * اندیس بعدی یک Repeater را از تعداد ردیف‌های فعلی می‌سازد.
     */
    function getNextIndex($repeater) {
        var max = -1;
        $repeater.find('[data-ba-repeater-item] :input[name]').each(function () {
            var match = String(this.name).match(/\[(\d+)\]/);
            if (match) {
                max = Math.max(max, parseInt(match[1], 10));
            }
        });
        return max + 1;
    }

    $(document).on('click', '[data-ba-media-select]', function () {
        openMediaFrame($(this).closest('[data-ba-media-field]'));
    });

    $(document).on('click', '[data-ba-media-remove]', function () {
        var $field = $(this).closest('[data-ba-media-field]');
        $field.find('[data-ba-media-input]').val('');
        $field.find('[data-ba-media-preview]').empty();
        $(this).prop('hidden', true);
    });

    $(document).on('click', '[data-ba-repeater-add]', function () {
        var $button = $(this);
        var $repeater = $button.closest('[data-ba-repeater]');
        var template = $('#' + $button.data('template')).html() || '';
        var index = getNextIndex($repeater);
        $repeater.find('[data-ba-repeater-list]').append(template.replace(/__INDEX__/g, index));
    });

    $(document).on('click', '[data-ba-repeater-remove]', function () {
        $(this).closest('[data-ba-repeater-item]').remove();
    });

    $(document).on('change', '[data-ba-media-type-switch]', function () {
        var $row = $(this).closest('[data-ba-partner-row]');
        var type = this.checked ? 'svg' : 'image';
        $row.find('[data-ba-media-type-value]').val(type);
        $row.find('[data-ba-media-mode]').prop('hidden', true);
        $row.find('[data-ba-media-mode="' + type + '"]').prop('hidden', false);
    });
})(jQuery);

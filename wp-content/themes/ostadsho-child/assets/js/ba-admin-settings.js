(function ($) {
    'use strict';

    function normalizeDigits(value) {
        var persian = '۰۱۲۳۴۵۶۷۸۹';
        var arabic = '٠١٢٣٤٥٦٧٨٩';

        return String(value || '')
            .replace(/[۰-۹]/g, function (digit) {
                return persian.indexOf(digit);
            })
            .replace(/[٠-٩]/g, function (digit) {
                return arabic.indexOf(digit);
            });
    }

    function onlyDigits(value) {
        return normalizeDigits(value).replace(/[^0-9]/g, '');
    }

    function formatThousands(value) {
        var digits = onlyDigits(value);
        return digits.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    $(document).on('input', '.ba-number-input', function () {
        var $input = $(this);
        var shouldFormat = $input.attr('data-format-thousands') === '1';
        var value = onlyDigits($input.val());

        $input.val(shouldFormat ? formatThousands(value) : value);
    });

    $('form').on('submit', function () {
        $(this).find('.ba-number-input').each(function () {
            $(this).val(onlyDigits($(this).val()));
        });
    });

    $('[data-ba-media-picker]').each(function () {
        var $picker = $(this);
        var $input = $picker.find('.ba-media-picker__input');
        var $preview = $picker.find('.ba-media-picker__preview');
        var $empty = $picker.find('.ba-media-picker__empty');
        var $clear = $picker.find('.ba-media-picker__clear');
        var frame = null;

        function getIds() {
            return $preview.find('.ba-media-picker__item').map(function () {
                return parseInt($(this).attr('data-id'), 10) || 0;
            }).get().filter(Boolean);
        }

        function syncInput() {
            var ids = getIds();
            $input.val(ids.join(','));
            $empty.prop('hidden', ids.length > 0);
            $clear.prop('hidden', ids.length === 0);
        }

        function createItem(attachment) {
            var thumbnail = attachment.sizes && attachment.sizes.thumbnail
                ? attachment.sizes.thumbnail.url
                : attachment.url;

            return $('<li>', {
                'class': 'ba-media-picker__item',
                'data-id': attachment.id
            }).append(
                $('<img>', { src: thumbnail, alt: '' }),
                $('<button>', {
                    type: 'button',
                    'class': 'ba-media-picker__remove',
                    'aria-label': BASettings.removeImage,
                    text: '×'
                }),
                $('<span>', {
                    'class': 'dashicons dashicons-move ba-media-picker__drag',
                    'aria-hidden': 'true'
                })
            );
        }

        $preview.sortable({
            items: '.ba-media-picker__item',
            handle: '.ba-media-picker__drag',
            update: syncInput
        });

        $picker.on('click', '.ba-media-picker__select', function (event) {
            event.preventDefault();

            if (!frame) {
                frame = wp.media({
                    title: BASettings.mediaTitle,
                    button: { text: BASettings.mediaButton },
                    library: { type: 'image' },
                    multiple: true
                });

                frame.on('open', function () {
                    var selection = frame.state().get('selection');
                    selection.reset();

                    getIds().forEach(function (id) {
                        var attachment = wp.media.attachment(id);
                        attachment.fetch();
                        selection.add(attachment);
                    });
                });

                frame.on('select', function () {
                    var selection = frame.state().get('selection');
                    $preview.empty();

                    selection.each(function (model) {
                        $preview.append(createItem(model.toJSON()));
                    });

                    syncInput();
                });
            }

            frame.open();
        });

        $picker.on('click', '.ba-media-picker__remove', function () {
            $(this).closest('.ba-media-picker__item').remove();
            syncInput();
        });

        $picker.on('click', '.ba-media-picker__clear', function (event) {
            event.preventDefault();
            $preview.empty();
            syncInput();
        });

        syncInput();
    });
})(jQuery);

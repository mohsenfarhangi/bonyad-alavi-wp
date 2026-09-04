(function ($) {
    'use strict';

    var $form = $('[data-ba-settings-form]').first();
    var $saveBar = $('[data-ba-settings-savebar]').first();
    var $saveButton = $saveBar.find('[data-ba-settings-save-button]');
    var $statusText = $saveBar.find('[data-ba-settings-save-status-text]');
    var $statusIcon = $saveBar.find('.ba-settings-savebar__status-icon');
    var isDirty = false;
    var isSaving = false;

    /**
     * ارقام فارسی و عربی را به رقم لاتین تبدیل می‌کند.
     *
     * @param {*} value مقدار ورودی.
     * @returns {string} مقدار نرمال‌شده.
     */
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

    /**
     * فقط ارقام یک مقدار را نگه می‌دارد.
     *
     * @param {*} value مقدار ورودی.
     * @returns {string} رشته عددی.
     */
    function onlyDigits(value) {
        return normalizeDigits(value).replace(/[^0-9]/g, '');
    }

    /**
     * عدد را برای نمایش مدیریت سه‌رقم سه‌رقم جدا می‌کند.
     *
     * @param {*} value مقدار ورودی.
     * @returns {string} مقدار فرمت‌شده.
     */
    function formatThousands(value) {
        var digits = onlyDigits(value);
        return digits.replace(/\B(?=(\d{3})+(?!\d))/g, ',');
    }

    /**
     * وضعیت بصری نوار ذخیره را به‌روزرسانی می‌کند.
     *
     * @param {'pristine'|'dirty'|'saving'|'success'|'error'} state وضعیت جدید.
     * @param {string} message متن وضعیت.
     */
    function setSaveState(state, message) {
        if (!$saveBar.length) {
            return;
        }

        $saveBar.removeClass(
            'ba-settings-savebar--pristine ba-settings-savebar--dirty ba-settings-savebar--saving ba-settings-savebar--success ba-settings-savebar--error'
        ).addClass('ba-settings-savebar--' + state);

        $statusText.text(message || '');
        $statusIcon.removeClass('dashicons-saved dashicons-warning dashicons-update dashicons-yes-alt');

        if (state === 'saving') {
            $statusIcon.addClass('dashicons-update');
        } else if (state === 'dirty' || state === 'error') {
            $statusIcon.addClass('dashicons-warning');
        } else if (state === 'success') {
            $statusIcon.addClass('dashicons-yes-alt');
        } else {
            $statusIcon.addClass('dashicons-saved');
        }
    }

    /**
     * فرم را دارای تغییر ذخیره‌نشده علامت‌گذاری می‌کند.
     */
    function markDirty() {
        if (isSaving || !$form.length) {
            return;
        }

        isDirty = true;
        setSaveState('dirty', BASettings.i18n.dirty);
    }

    /**
     * مقدار editorهای وردپرس را پیش از ساخت FormData به textarea منتقل می‌کند.
     */
    function syncWordPressEditors() {
        if (window.tinyMCE && typeof window.tinyMCE.triggerSave === 'function') {
            window.tinyMCE.triggerSave();
        }
    }

    /**
     * مقدار خام ورودی‌های عددی را فقط داخل FormData قرار می‌دهد تا فرمت نمایشی فیلد حفظ شود.
     *
     * @param {FormData} formData داده آماده ارسال.
     * @param {jQuery} $targetForm فرم مقصد.
     */
    function normalizeNumericFormData(formData, $targetForm) {
        $targetForm.find('.ba-number-input[name]').each(function () {
            formData.set(this.name, onlyDigits($(this).val()));
        });
    }

    /**
     * پیام خطای مناسب را از پاسخ AJAX استخراج می‌کند.
     *
     * @param {Object|null} response پاسخ JSON یا jqXHR.responseJSON.
     * @returns {string} پیام خطا.
     */
    function getErrorMessage(response) {
        if (response && response.data && response.data.message) {
            return String(response.data.message);
        }

        return BASettings.i18n.error;
    }

    /**
     * TinyMCE را برای تشخیص تغییرات و نمایش dirty-state متصل می‌کند.
     *
     * @param {Object} editor نمونه TinyMCE.
     */
    function bindTinyMceEditor(editor) {
        if (!editor || editor.__baSettingsDirtyBound) {
            return;
        }

        var element = typeof editor.getElement === 'function' ? editor.getElement() : null;
        if (!element || !$(element).closest('[data-ba-settings-form]').length) {
            return;
        }

        editor.__baSettingsDirtyBound = true;
        editor.on('change input undo redo', markDirty);
    }

    $(document).on('input change', '[data-ba-settings-form] :input', function () {
        markDirty();
    });

    document.addEventListener('ba:admin-repeater:change', function (event) {
        if ($(event.target).closest('[data-ba-settings-form]').length) {
            markDirty();
        }
    });

    $(document).on('input', '.ba-number-input', function () {
        var $input = $(this);
        var shouldFormat = $input.attr('data-format-thousands') === '1';
        var value = onlyDigits($input.val());

        $input.val(shouldFormat ? formatThousands(value) : value);
    });

    $('[data-ba-media-picker]').each(function () {
        var $picker = $(this);
        var $input = $picker.find('.ba-media-picker__input');
        var $preview = $picker.find('.ba-media-picker__preview');
        var $empty = $picker.find('.ba-media-picker__empty');
        var $clear = $picker.find('.ba-media-picker__clear');
        var frame = null;

        /**
         * شناسه تصاویر فعلی انتخاب‌شده را می‌خواند.
         *
         * @returns {number[]} شناسه تصاویر.
         */
        function getIds() {
            return $preview.find('.ba-media-picker__item').map(function () {
                return parseInt($(this).attr('data-id'), 10) || 0;
            }).get().filter(Boolean);
        }

        /**
         * مقدار hidden input و empty-state انتخاب‌گر را همگام می‌کند.
         *
         * @param {boolean} notifyChange آیا تغییر باید به فرم اعلام شود؟
         */
        function syncInput(notifyChange) {
            var ids = getIds();
            var value = ids.join(',');
            var changed = String($input.val() || '') !== value;

            $input.val(value);
            $empty.prop('hidden', ids.length > 0);
            $clear.prop('hidden', ids.length === 0);

            if (notifyChange && changed) {
                $input.trigger('change');
            }
        }

        /**
         * آیتم پیش‌نمایش یک Attachment را می‌سازد.
         *
         * @param {Object} attachment داده Attachment وردپرس.
         * @returns {jQuery} آیتم ساخته‌شده.
         */
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
            update: function () {
                syncInput(true);
            }
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

                    syncInput(true);
                });
            }

            frame.open();
        });

        $picker.on('click', '.ba-media-picker__remove', function () {
            $(this).closest('.ba-media-picker__item').remove();
            syncInput(true);
        });

        $picker.on('click', '.ba-media-picker__clear', function (event) {
            event.preventDefault();
            $preview.empty();
            syncInput(true);
        });

        syncInput(false);
    });

    $(document).on('tinymce-editor-init', function (event, editor) {
        bindTinyMceEditor(editor);
    });

    if (window.tinymce) {
        if (Array.isArray(window.tinymce.editors)) {
            window.tinymce.editors.forEach(bindTinyMceEditor);
        }

        if (typeof window.tinymce.on === 'function') {
            window.tinymce.on('AddEditor', function (event) {
                bindTinyMceEditor(event.editor);
            });
        }
    }

    if ($form.length && $saveBar.length && window.BASettings && BASettings.ajaxUrl) {
        setSaveState('pristine', BASettings.i18n.pristine);

        $form.on('submit', function (event) {
            event.preventDefault();

            if (isSaving) {
                return;
            }

            syncWordPressEditors();

            var formData = new window.FormData($form.get(0));
            normalizeNumericFormData(formData, $form);
            formData.set('action', BASettings.ajaxAction);
            formData.set('_ajax_nonce', BASettings.ajaxNonce);

            isSaving = true;
            $saveButton.prop('disabled', true);
            setSaveState('saving', BASettings.i18n.saving);

            $.ajax({
                url: BASettings.ajaxUrl,
                method: 'POST',
                data: formData,
                processData: false,
                contentType: false,
                dataType: 'json'
            }).done(function (response) {
                if (!response || !response.success) {
                    setSaveState('error', getErrorMessage(response));
                    return;
                }

                isDirty = false;
                setSaveState('success', response.data && response.data.message ? response.data.message : BASettings.i18n.success);
            }).fail(function (xhr) {
                setSaveState('error', getErrorMessage(xhr.responseJSON));
            }).always(function () {
                isSaving = false;
                $saveButton.prop('disabled', false);
            });
        });

        window.addEventListener('beforeunload', function (event) {
            if (!isDirty || isSaving) {
                return;
            }

            event.preventDefault();
            event.returnValue = '';
        });
    }
})(jQuery);

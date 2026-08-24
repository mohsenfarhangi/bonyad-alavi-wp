(function () {
    'use strict';

    var root = document.getElementById('bap-thankyou');

    if (!root) {
        return;
    }

    var status = root.querySelector('.bap-thankyou-status');
    var copyButton = root.querySelector('#bap-copy-order');
    var shareButton = root.querySelector('#bap-share-projects');
    var orderNumber = root.dataset.orderNumber || '';
    var shopUrl = root.dataset.shopUrl || window.location.href;

    function show(message) {
        if (!status) {
            return;
        }

        status.textContent = message;
        window.clearTimeout(status._timer);
        status._timer = window.setTimeout(function () {
            status.textContent = '';
        }, 2600);
    }

    if (copyButton) {
        copyButton.addEventListener('click', function () {
            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(orderNumber).then(function () {
                    show('شماره پیگیری کپی شد.');
                }).catch(function () {
                    show('امکان کپی خودکار وجود ندارد.');
                });
                return;
            }

            show('شماره پیگیری: ' + orderNumber);
        });
    }

    if (shareButton) {
        shareButton.addEventListener('click', function () {
            var data = {
                title: 'پروژه‌های مشارکت بنیاد علوی',
                text: 'شما هم می‌توانید در پروژه‌های عام‌المنفعه بنیاد علوی مشارکت کنید.',
                url: shopUrl
            };

            if (navigator.share) {
                navigator.share(data).catch(function () {});
                return;
            }

            if (navigator.clipboard && window.isSecureContext) {
                navigator.clipboard.writeText(shopUrl).then(function () {
                    show('لینک پروژه‌ها کپی شد.');
                });
                return;
            }

            show('لینک پروژه‌ها: ' + shopUrl);
        });
    }
}());

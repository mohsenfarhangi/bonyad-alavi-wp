# Patch Manifest — ostadsho-child v0.2.0

## مبنا

- نسخه مبنا: `v0.1.0`
- نسخه مقصد: `v0.2.0`
- نوع تغییر: MINOR / قابلیت جدید

## روش اعمال Patch

محتویات پوشه `ostadsho-child/` این Patch را روی پوشه قالب فرزند فعلی با همان مسیرها جایگزین/ادغام کنید. فایل‌های موجود فقط در مسیرهای فهرست‌شده تغییر می‌کنند و فایل‌های جدید نیز در همان ساختار اضافه می‌شوند.

## فایل‌های تغییرکرده یا جدید

- `assets/css/bonyad-alavi-participation-widget.css`
- `assets/css/bonyad-alavi-product-gallery-widget.css` (جدید)
- `assets/js/ba-product-carousel-core.js` (جدید)
- `assets/js/bonyad-alavi-participation-widget.js`
- `assets/js/bonyad-alavi-product-gallery-widget.js` (جدید)
- `inc/elementor/elementor-widgets.php`
- `inc/elementor/widgets/class-bonyad-alavi-participation-widget.php`
- `inc/elementor/widgets/class-bonyad-alavi-product-gallery-widget.php` (جدید)
- `inc/helpers/class-ba-media-helper.php` (جدید)
- `inc/services/class-ba-product-media-service.php` (جدید)
- `docs/handoff.md`
- `docs/versions/v0.2.0.md` (جدید)

## خلاصه قابلیت

- تصویر ویجت مشارکت از Product Image و Product Gallery ووکامرس خوانده می‌شود.
- با وجود گالری، تصویر اصلی به کاروسل تبدیل می‌شود.
- آیکون `bap__zoom` در حالت گالری تغییر می‌کند.
- ویجت مستقل تصویر محصول برای Loop Elementor اضافه شده است.
- سرویس رسانه، Helper تصویر و هسته مشترک JavaScript برای جلوگیری از کد تکراری اضافه شده‌اند.

برای جزئیات کامل به `ostadsho-child/docs/versions/v0.2.0.md` مراجعه شود.

## SHA-256 فایل‌های Patch

```text
ba8ad1319b4973552826ea61ccb9125092cb63754260dd7d16a83684ac199bd7  ostadsho-child/assets/css/bonyad-alavi-participation-widget.css
293180488e7468ef3df124e59392385c61d71fbbd682455a48760199451507bf  ostadsho-child/assets/css/bonyad-alavi-product-gallery-widget.css
e70ab3526a9181dd31c5d511088745a40b7a908d4d70ea72ba0521e85edd5cb2  ostadsho-child/assets/js/ba-product-carousel-core.js
8d693c8e36219d89dc3cc181e1bb34b1111f0e860d7519088ea98dbcb18110de  ostadsho-child/assets/js/bonyad-alavi-participation-widget.js
807ab6bec833f4c8609d7841367cae549cf263df96fe44788bf791e630c2e1fa  ostadsho-child/assets/js/bonyad-alavi-product-gallery-widget.js
cb8be374b4b9c185742df86cd8cf009aa1fd15dd475c8629626e61513576acff  ostadsho-child/docs/handoff.md
20792091fe2ddf91e4ffa82dc2b2da3b482eeaa55f7cbf2efd1606cdb6c394db  ostadsho-child/docs/versions/v0.2.0.md
0723494cc69ae01995faccc0ba9795d38eeae8a9995b4c05e28b13f7cc341f66  ostadsho-child/inc/elementor/elementor-widgets.php
c1745029da34fe90f6e0d17620c797719112d24ed850a290a82ba249ea931b81  ostadsho-child/inc/elementor/widgets/class-bonyad-alavi-participation-widget.php
745458b190826f756ba7e9aeb54497962646ca1915d5dbe4db8d5fb2d520ae28  ostadsho-child/inc/elementor/widgets/class-bonyad-alavi-product-gallery-widget.php
2040983382481dbc86557f09af89d51c7c617b97a43843177d3c339f95d85f0e  ostadsho-child/inc/helpers/class-ba-media-helper.php
3df9e64f618979402f627382cd25df72345a74db732c8c576482a02b6e8fcb37  ostadsho-child/inc/services/class-ba-product-media-service.php
```

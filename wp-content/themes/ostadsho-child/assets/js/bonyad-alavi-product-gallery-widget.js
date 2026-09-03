/**
 * رفتار فرانت‌اند ویجت تصویر محصول برای Loop المنتور.
 */
(() => {
	'use strict';

	/**
	 * Handler المنتور برای اتصال هسته کاروسل به Markup اختصاصی ویجت Loop.
	 */
	class BonyadAlaviProductGalleryHandler extends elementorModules.frontend.handlers.Base {
		/**
		 * ریشه ویجت را پیدا می‌کند و در صورت فعال بودن کاروسل، نمونه مشترک را می‌سازد.
		 */
		onInit() {
			super.onInit();

			this.root = this.$element && this.$element[0]
				? this.$element[0].querySelector('.ba-product-gallery')
				: null;

			if (!this.root || this.root.dataset.carouselEnabled !== 'true') {
				return;
			}

			const Carousel = window.BonyadAlavi && window.BonyadAlavi.ProductCarousel;
			if (!Carousel) {
				return;
			}

			this.carousel = new Carousel(this.root, {
				slideSelector: '.ba-product-gallery__slide',
				prevSelector: '.ba-product-gallery__nav--prev',
				nextSelector: '.ba-product-gallery__nav--next',
				viewportSelector: '.ba-product-gallery__viewport',
				activeClass: 'ba-product-gallery__slide--active',
			});
		}

		/**
		 * نمونه کاروسل را هنگام حذف یا بازسازی ویجت در ادیتور آزاد می‌کند.
		 */
		onDestroy() {
			if (this.carousel) {
				this.carousel.destroy();
				this.carousel = null;
			}

			super.onDestroy();
		}
	}

	/**
	 * Handler را پس از آماده‌شدن فرانت‌اند المنتور به ویجت ثبت‌شده متصل می‌کند.
	 */
	window.addEventListener('elementor/frontend/init', () => {
		elementorFrontend.elementsHandler.attachHandler(
			'bonyad_alavi_product_gallery',
			BonyadAlaviProductGalleryHandler
		);
	});
})();

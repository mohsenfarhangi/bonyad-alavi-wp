/**
 * هسته مشترک کاروسل تصاویر محصول برای ویجت‌های بنیاد علوی.
 */
(() => {
	'use strict';

	window.BonyadAlavi = window.BonyadAlavi || {};

	/**
	 * کاروسل سبک و مستقل که با selectorهای BEM هر ویجت پیکربندی می‌شود.
	 */
	class BAProductCarousel {
		/**
		 * ریشه کاروسل و قرارداد selectorها را دریافت و رویدادها را فعال می‌کند.
		 *
		 * @param {HTMLElement} root ریشه کاروسل.
		 * @param {Object} options تنظیمات selector و کلاس فعال.
		 */
		constructor(root, options = {}) {
			this.root = root;
			this.options = Object.assign({
				slideSelector: '[data-carousel-slide]',
				prevSelector: '[data-carousel-prev]',
				nextSelector: '[data-carousel-next]',
				viewportSelector: '[data-carousel-viewport]',
				activeClass: '',
			}, options);

			this.slides = [...this.root.querySelectorAll(this.options.slideSelector)];
			this.prevButton = this.root.querySelector(this.options.prevSelector);
			this.nextButton = this.root.querySelector(this.options.nextSelector);
			this.viewport = this.root.querySelector(this.options.viewportSelector) || this.root;
			this.index = this.options.activeClass
				? Math.max(0, this.slides.findIndex((slide) => slide.classList.contains(this.options.activeClass)))
				: 0;
			this.touchStartX = null;
			this.abortController = typeof AbortController !== 'undefined' ? new AbortController() : null;
			this.listenerOptions = this.abortController ? { signal: this.abortController.signal } : false;

			if (this.slides.length > 1) {
				this.bindEvents();
				this.show(this.index);
			}
		}

		/**
		 * کنترل‌های کلیک، صفحه‌کلید و Swipe را فقط برای کاروسل چندتصویری متصل می‌کند.
		 */
		bindEvents() {
			if (this.prevButton) {
				this.prevButton.addEventListener('click', () => this.show(this.index - 1), this.listenerOptions);
			}

			if (this.nextButton) {
				this.nextButton.addEventListener('click', () => this.show(this.index + 1), this.listenerOptions);
			}

			this.viewport.addEventListener('keydown', (event) => {
				if (event.key === 'ArrowRight') {
					event.preventDefault();
					this.show(this.index - 1);
				} else if (event.key === 'ArrowLeft') {
					event.preventDefault();
					this.show(this.index + 1);
				}
			}, this.listenerOptions);

			this.viewport.addEventListener('touchstart', (event) => {
				this.touchStartX = event.changedTouches && event.changedTouches[0]
					? event.changedTouches[0].clientX
					: null;
			}, this.listenerOptions);

			this.viewport.addEventListener('touchend', (event) => {
				if (this.touchStartX === null || !event.changedTouches || !event.changedTouches[0]) {
					return;
				}

				const deltaX = event.changedTouches[0].clientX - this.touchStartX;
				this.touchStartX = null;

				if (Math.abs(deltaX) < 40) {
					return;
				}

				this.show(this.index + (deltaX < 0 ? 1 : -1));
			}, this.listenerOptions);
		}

		/**
		 * اسلاید مقصد را با گردش حلقه‌ای فعال و وضعیت دسترس‌پذیری بقیه را به‌روز می‌کند.
		 *
		 * @param {number} index شماره اسلاید مقصد.
		 */
		show(index) {
			if (!this.slides.length || !this.options.activeClass) {
				return;
			}

			const total = this.slides.length;
			this.index = ((Number(index) || 0) % total + total) % total;

			this.slides.forEach((slide, slideIndex) => {
				const isActive = slideIndex === this.index;
				slide.classList.toggle(this.options.activeClass, isActive);
				slide.setAttribute('aria-hidden', isActive ? 'false' : 'true');
			});
		}

		/**
		 * Listenerهای ایجادشده توسط نمونه را آزاد می‌کند.
		 */
		destroy() {
			if (this.abortController) {
				this.abortController.abort();
			}
		}
	}

	window.BonyadAlavi.ProductCarousel = BAProductCarousel;
})();

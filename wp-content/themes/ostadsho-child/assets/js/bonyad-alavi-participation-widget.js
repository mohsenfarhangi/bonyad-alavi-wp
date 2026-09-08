/**
 * Handler فرانت‌اند ویجت مشارکت مردمی بنیاد علوی.
 * این فایل فقط هنگام حضور ویجت در خروجی Elementor بارگذاری می‌شود.
 */
(() => {
	'use strict';

	/**
	 * Handler اصلی رفتارهای تعاملی صفحه مشارکت مردمی.
	 */
	class BonyadAlaviParticipationHandler extends elementorModules.frontend.handlers.Base {
		onInit() {
			super.onInit();

			this.root = this.$element && this.$element[0]
				? this.$element[0].querySelector('.bap')
				: null;

			if (!this.root) {
				return;
			}

			this.abortController = typeof AbortController !== 'undefined' ? new AbortController() : null;
			this.listenerOptions = this.abortController ? { signal: this.abortController.signal } : false;
			this.toastTimer = null;
			this.lastFocusedElement = null;
			this.documentOverflow = '';
			this.lightboxIndex = 0;
			this.lightboxTouchStartX = null;
			this.checkoutStage = 'amount';
			this.lockedAmount = 0;
			this.payButtonText = 'پرداخت';

			this.faNumber = new Intl.NumberFormat('fa-IR');
			this.compactFaNumber = new Intl.NumberFormat('fa-IR', {
				notation: 'compact',
				maximumFractionDigits: 1,
			});

			this.cacheElements();
			this.readConfiguration();
			this.renderFundingState();
			this.bindWidgetEvents();
			this.setupMediaCarousel();
			this.setupStickySidebar();
		}

		cacheElements() {
			this.elements = {
				progressPercent: this.root.querySelector('.bap__progress-percent'),
				progressBar: this.root.querySelector('.bap__progress'),
				progressFill: this.root.querySelector('.bap__progress-fill'),
				collected: this.root.querySelector('.bap__collected'),
				target: this.root.querySelector('.bap__target'),
				minimum: this.root.querySelector('.bap__minimum'),
				form: this.root.querySelector('.bap__donation-form'),
				amountInput: this.root.querySelector('.bap__amount-input'),
				amountError: this.root.querySelector('.bap__amount-error'),
				presetButtons: [...this.root.querySelectorAll('.bap__preset-grid [data-amount]')],
				dockAmount: this.root.querySelector('.bap__dock-amount'),
				dockButton: this.root.querySelector('.bap__dock-button'),
				donationColumn: this.root.querySelector('.bap__donation-column'),
				donationCard: this.root.querySelector('.bap__donation-card'),
				toast: this.root.querySelector('.bap__toast'),
				mediaCarouselRoot: this.root.querySelector('.bap__media-carousel[data-carousel-enabled="true"]'),
				zoomButton: this.root.querySelector('.bap__zoom'),
				lightbox: this.root.querySelector('.bap__lightbox'),
				lightboxClose: this.root.querySelector('.bap__lightbox-close'),
				lightboxStage: this.root.querySelector('.bap__lightbox-stage'),
				lightboxSlides: [...this.root.querySelectorAll('.bap__lightbox-slide')],
				lightboxPrev: this.root.querySelector('.bap__lightbox-prev'),
				lightboxNext: this.root.querySelector('.bap__lightbox-next'),
				lightboxCurrent: this.root.querySelector('.bap__lightbox-current'),
				lightboxThumbs: [...this.root.querySelectorAll('.bap__lightbox-thumb[data-lightbox-index]')],
				shareButton: this.root.querySelector('.bap__share-button'),
				copyLinkButton: this.root.querySelector('.bap__copy-link'),
				noncashButtons: [...this.root.querySelectorAll('button.bap__noncash-option[data-noncash]')],
				submitButton: this.root.querySelector('.bap__submit'),
				submitLabel: this.root.querySelector('.bap__submit > span'),
				quickCheckout: this.root.querySelector('.bap__quick-checkout')
			};
		}

		readConfiguration() {
			const data = this.root.dataset;
			this.config = {
				projectId: data.projectId || '',
				projectTitle: data.projectTitle || '',
				collected: this.parseAmount(data.collected),
				publicTarget: this.parseAmount(data.publicTarget),
				totalBudget: this.parseAmount(data.totalBudget),
				minimum: this.parseAmount(data.minimum),
				currency: data.currency || 'تومان',
				successMessage: data.successMessage || 'مبلغ انتخاب‌شده به سبد همیاری افزوده شد.',
				shareUrl: data.shareUrl || window.location.href,
				shareTitle: data.shareTitle || data.projectTitle || document.title,
				shareText: data.shareText || '',
				copyMessage: data.copyMessage || 'لینک پروژه کپی شد.',
				goalAmount: this.parseAmount(data.goalAmount),

				quickPrepareEndpoint: data.quickPrepareEndpoint || '',
				quickPaymentEndpoint: data.quickPaymentEndpoint || '',
				cartNonce: data.cartNonce || '',
			};
		}

		bindWidgetEvents() {
			const {
				amountInput,
				presetButtons,
				form,
				dockButton,
				zoomButton,
				lightbox,
				lightboxClose,
				lightboxStage,
				lightboxPrev,
				lightboxNext,
				lightboxThumbs,
				shareButton,
				copyLinkButton,
				noncashButtons,
				quickCheckout,
			} = this.elements;

			presetButtons.forEach((button) => {
				button.addEventListener('click', () => {
					this.syncAmount(this.parseAmount(button.dataset.amount), button);
				}, this.listenerOptions);
			});

			if (amountInput) {
				amountInput.addEventListener('input', () => this.handleAmountInput(), this.listenerOptions);
				amountInput.addEventListener('keydown', (event) => {
					if (event.key === 'Enter' && form) {
						event.preventDefault();
						form.requestSubmit ? form.requestSubmit() : form.submit();
					}
				}, this.listenerOptions);
			}

			if (form) {
				form.addEventListener('submit', (event) => this.handleSubmit(event), this.listenerOptions);
			}

			if (dockButton) {
				dockButton.addEventListener('click', () => this.focusDonationForm(), this.listenerOptions);
			}

			if (shareButton) {
				shareButton.addEventListener('click', () => this.shareProject(), this.listenerOptions);
			}

			if (copyLinkButton) {
				copyLinkButton.addEventListener('click', () => this.copyProjectLink(), this.listenerOptions);
			}



			if (quickCheckout) {
				quickCheckout.addEventListener('click', (event) => {
					const editButton = event.target.closest('.bap__quick-edit-amount');
					if (editButton) {
						event.preventDefault();
						this.resetQuickCheckout();
					}
				}, this.listenerOptions);
			}

			noncashButtons.forEach((button) => {
				button.addEventListener('click', () => {
					this.root.dispatchEvent(new CustomEvent('bonyad-alavi:noncash-select', {
						bubbles: true,
						detail: {
							projectId: this.config.projectId,
							projectTitle: this.config.projectTitle,
							type: button.dataset.noncash || '',
							button,
						},
					}));
				}, this.listenerOptions);
			});

			if (zoomButton && lightbox && lightboxClose) {
				zoomButton.addEventListener('click', () => this.openLightbox(this.mediaCarousel ? this.mediaCarousel.index : 0), this.listenerOptions);
				lightboxClose.addEventListener('click', () => this.closeLightbox(), this.listenerOptions);
				lightbox.addEventListener('click', (event) => {
					if (event.target === lightbox) {
						this.closeLightbox();
					}
				}, this.listenerOptions);

				if (lightboxPrev) {
					lightboxPrev.addEventListener('click', () => this.showLightboxSlide(this.lightboxIndex - 1), this.listenerOptions);
				}

				if (lightboxNext) {
					lightboxNext.addEventListener('click', () => this.showLightboxSlide(this.lightboxIndex + 1), this.listenerOptions);
				}

				lightboxThumbs.forEach((button) => {
					button.addEventListener('click', () => {
						this.showLightboxSlide(Number(button.dataset.lightboxIndex) || 0);
					}, this.listenerOptions);
				});

				if (lightboxStage) {
					lightboxStage.addEventListener('touchstart', (event) => {
						this.lightboxTouchStartX = event.changedTouches && event.changedTouches[0]
							? event.changedTouches[0].clientX
							: null;
					}, this.listenerOptions);

					lightboxStage.addEventListener('touchend', (event) => {
						if (this.lightboxTouchStartX === null || !event.changedTouches || !event.changedTouches[0]) {
							return;
						}

						const deltaX = event.changedTouches[0].clientX - this.lightboxTouchStartX;
						this.lightboxTouchStartX = null;

						if (Math.abs(deltaX) < 45) {
							return;
						}

						this.showLightboxSlide(this.lightboxIndex + (deltaX < 0 ? 1 : -1));
					}, this.listenerOptions);
				}

				document.addEventListener('keydown', (event) => {
					if (lightbox.hidden) {
						return;
					}

					if (event.key === 'Escape') {
						this.closeLightbox();
					} else if (event.key === 'ArrowLeft') {
						this.showLightboxSlide(this.lightboxIndex + 1);
					} else if (event.key === 'ArrowRight') {
						this.showLightboxSlide(this.lightboxIndex - 1);
					}
				}, this.listenerOptions);
			}
		}


		/**
		 * هسته مشترک کاروسل را به بخش رسانه اصلی پروژه متصل می‌کند.
		 */
		setupMediaCarousel() {
			const { mediaCarouselRoot } = this.elements;
			const Carousel = window.BonyadAlavi && window.BonyadAlavi.ProductCarousel;

			if (!mediaCarouselRoot || !Carousel) {
				return;
			}

			this.mediaCarousel = new Carousel(mediaCarouselRoot, {
				slideSelector: '.bap__media-slide',
				prevSelector: '.bap__media-nav--prev',
				nextSelector: '.bap__media-nav--next',
				viewportSelector: '.bap__media-viewport',
				activeClass: 'bap__media-slide--active',
			});
		}

		renderFundingState() {
			const { collected, publicTarget, minimum, currency } = this.config;
			const progress = publicTarget > 0
				? Math.min(100, Math.max(0, (collected / publicTarget) * 100))
				: 0;
			const roundedProgress = Math.round(progress);
			const { progressPercent, progressBar, progressFill, collected: collectedElement, target, minimum: minimumElement } = this.elements;

			if (progressPercent) {
				progressPercent.textContent = `${this.faNumber.format(roundedProgress)}٪`;
			}
			if (progressBar) {
				progressBar.setAttribute('aria-valuenow', String(roundedProgress));
			}
			if (progressFill) {
				requestAnimationFrame(() => {
					progressFill.style.width = `${progress}%`;
				});
			}
			if (collectedElement) {
				collectedElement.textContent = this.formatMoney(collected, currency);
			}
			if (target) {
				target.textContent = this.formatMoney(publicTarget, currency);
			}
			if (minimumElement) {
				minimumElement.textContent = this.formatMoney(minimum, currency);
			}
		}

		handleAmountInput() {
			const { amountInput, presetButtons, dockAmount, amountError } = this.elements;

			if (!amountInput) {
				return;
			}

			const rawValue = amountInput.value;
			const cursorPosition = typeof amountInput.selectionStart === 'number' ? amountInput.selectionStart : rawValue.length;
			const digitsBeforeCursor = this.toEnglishDigits(rawValue.slice(0, cursorPosition)).replace(/[^0-9]/g, '').length;
			const amount = this.parseAmount(rawValue);
			const formattedValue = amount ? this.faNumber.format(amount) : '';

			if (amountInput.value !== formattedValue) {
				amountInput.value = formattedValue;

				if (document.activeElement === amountInput) {
					const nextCursorPosition = this.findCaretAfterDigits(formattedValue, digitsBeforeCursor);
					try {
						amountInput.setSelectionRange(nextCursorPosition, nextCursorPosition);
					} catch (error) {
						// Some browsers/input modes do not expose a writable text selection.
					}
				}
			}

			presetButtons.forEach((button) => button.classList.remove('is-active'));
			if (dockAmount) {
				dockAmount.textContent = amount ? this.formatMoney(amount) : 'انتخاب نشده';
			}
			if (amountError) {
				amountError.textContent = '';
			}

			if (amountError) {
				if (this.config.goalAmount > 0 && amount > this.config.goalAmount) {
					amountError.textContent = `مبلغ مشارکت نمی‌تواند بیشتر از ${this.faNumber.format(this.config.goalAmount)} ${this.config.currency} باشد.`;
				} else {
					amountError.textContent = '';
				}
			}
		}

		findCaretAfterDigits(value, digitCount) {
			if (digitCount <= 0) {
				return 0;
			}

			let seenDigits = 0;
			for (let index = 0; index < value.length; index += 1) {
				if (/[0-9۰-۹٠-٩]/.test(value[index])) {
					seenDigits += 1;
				}

				if (seenDigits >= digitCount) {
					return index + 1;
				}
			}

			return value.length;
		}

		formatAmountInput() {
			const { amountInput } = this.elements;
			if (!amountInput) {
				return;
			}

			const amount = this.parseAmount(amountInput.value);
			amountInput.value = amount ? this.faNumber.format(amount) : '';
		}

		syncAmount(amount, sourceButton = null) {
			const { amountInput, presetButtons, dockAmount, amountError } = this.elements;

			presetButtons.forEach((button) => button.classList.toggle('is-active', button === sourceButton));
			if (amountInput) {
				amountInput.value = amount ? this.faNumber.format(amount) : '';
			}
			if (dockAmount) {
				dockAmount.textContent = amount ? this.formatMoney(amount) : 'انتخاب نشده';
			}
			if (amountError) {
				amountError.textContent = '';
			}
		}

		handleSubmit(event) {
			const {
				form,
				amountInput,
				amountError,
			} = this.elements;

			if (!form || !amountInput) {
				return;
			}

			const amount = this.parseAmount(
				amountInput.value
			);

			/*
             * حداقل مبلغ فعلی ویجت.
             */
			if (amount < this.config.minimum) {
				event.preventDefault();

				if (amountError) {
					amountError.textContent =
						`مبلغ باید حداقل ${
							this.compactFaNumber.format(
								this.config.minimum
							)
						} ${this.config.currency} باشد.`;
				}

				amountInput.focus();

				return;
			}

			/*
             * کنترل UX سمت مرورگر.
             * کنترل اصلی دوباره روی سرور انجام می‌شود.
             */
			if (
				this.config.goalAmount > 0 &&
				amount > this.config.goalAmount
			) {
				event.preventDefault();

				if (amountError) {
					amountError.textContent =
						`مبلغ مشارکت نمی‌تواند بیشتر از ${
							this.faNumber.format(
								this.config.goalAmount
							)
						} ${this.config.currency} باشد.`;
				}

				amountInput.focus();

				return;
			}

			const isEditor = Boolean(
				window.elementorFrontend &&
				elementorFrontend.isEditMode &&
				elementorFrontend.isEditMode()
			);

			if (isEditor) {
				event.preventDefault();

				this.showToast(
					'در پیش‌نمایش المنتور امکان افزودن به سبد خرید وجود ندارد.'
				);

				return;
			}

			const mode =
				form.dataset.mode || 'woocommerce';

			/*
             * اتصال اصلی به WooCommerce.
             */
			if (mode === 'woocommerce') {
				event.preventDefault();

				if (this.checkoutStage === 'checkout') {
					this.processQuickPayment(this.lockedAmount || amount);
				} else {
					this.prepareQuickCheckout(amount);
				}

				return;
			}

			/*
             * حالت قبلی همچنان حفظ شده.
             */
			if (mode === 'event') {
				event.preventDefault();

				this.root.dispatchEvent(
					new CustomEvent(
						'bonyad-alavi:donation-submit',
						{
							bubbles: true,
							detail: {
								projectId:
								this.config.projectId,
								projectTitle:
								this.config.projectTitle,
								amount,
								currency:
								this.config.currency,
								form,
								widget: this.root,
							},
						}
					)
				);

				this.showToast(this.config.successMessage);

				return;
			}

			/*
             * POST قدیمی.
             */
			amountInput.value = String(amount);
		}
		focusDonationForm() {
			const { donationCard, amountInput } = this.elements;
			if (!donationCard) {
				return;
			}

			donationCard.scrollIntoView({ behavior: this.prefersReducedMotion() ? 'auto' : 'smooth', block: 'center' });
			window.setTimeout(() => {
				if (amountInput) {
					try {
						amountInput.focus({ preventScroll: true });
					} catch (error) {
						amountInput.focus();
					}
				}
			}, this.prefersReducedMotion() ? 0 : 450);
		}

		openLightbox(index = 0) {
			const { lightbox, lightboxClose } = this.elements;
			if (!lightbox || !lightboxClose) {
				return;
			}

			this.lastFocusedElement = document.activeElement;
			this.documentOverflow = document.documentElement.style.overflow;
			lightbox.hidden = false;
			this.showLightboxSlide(index);
			document.documentElement.style.overflow = 'hidden';
			lightboxClose.focus();
		}

		showLightboxSlide(index) {
			const { lightboxSlides, lightboxThumbs, lightboxCurrent } = this.elements;
			if (!lightboxSlides.length) {
				return;
			}

			const total = lightboxSlides.length;
			this.lightboxIndex = ((Number(index) || 0) % total + total) % total;

			lightboxSlides.forEach((slide, slideIndex) => {
				const isActive = slideIndex === this.lightboxIndex;
				slide.classList.toggle('is-active', isActive);
				slide.setAttribute('aria-hidden', isActive ? 'false' : 'true');
			});

			lightboxThumbs.forEach((button, buttonIndex) => {
				const isActive = buttonIndex === this.lightboxIndex;
				button.classList.toggle('is-active', isActive);
				button.setAttribute('aria-current', isActive ? 'true' : 'false');
			});

			if (lightboxCurrent) {
				lightboxCurrent.textContent = this.faNumber.format(this.lightboxIndex + 1);
			}

			const activeThumb = lightboxThumbs[this.lightboxIndex];
			if (activeThumb && typeof activeThumb.scrollIntoView === 'function') {
				activeThumb.scrollIntoView({
					behavior: this.prefersReducedMotion() ? 'auto' : 'smooth',
					block: 'nearest',
					inline: 'center',
				});
			}
		}

		async shareProject() {
			const shareData = {
				title: this.config.shareTitle,
				text: this.config.shareText,
				url: this.config.shareUrl,
			};

			if (navigator.share) {
				try {
					await navigator.share(shareData);
					return;
				} catch (error) {
					if (error && error.name === 'AbortError') {
						return;
					}
				}
			}

			await this.copyProjectLink();
		}

		async copyProjectLink() {
			const url = this.config.shareUrl || window.location.href;

			try {
				if (navigator.clipboard && window.isSecureContext) {
					await navigator.clipboard.writeText(url);
				} else {
					this.fallbackCopyText(url);
				}

				this.showToast(this.config.copyMessage);
			} catch (error) {
				this.fallbackCopyText(url);
				this.showToast(this.config.copyMessage);
			}
		}

		fallbackCopyText(text) {
			const textarea = document.createElement('textarea');
			textarea.value = text;
			textarea.setAttribute('readonly', '');
			textarea.style.position = 'fixed';
			textarea.style.opacity = '0';
			document.body.appendChild(textarea);
			textarea.select();
			document.execCommand('copy');
			textarea.remove();
		}

		closeLightbox() {
			const { lightbox } = this.elements;
			if (!lightbox) {
				return;
			}

			lightbox.hidden = true;
			document.documentElement.style.overflow = this.documentOverflow;
			if (this.lastFocusedElement && typeof this.lastFocusedElement.focus === 'function') {
				this.lastFocusedElement.focus();
			}
		}

		showToast(message) {
			const { toast } = this.elements;
			if (!toast || !message) {
				return;
			}

			toast.textContent = message;
			toast.classList.add('is-visible');
			window.clearTimeout(this.toastTimer);
			this.toastTimer = window.setTimeout(() => toast.classList.remove('is-visible'), 2800);
		}

		toEnglishDigits(value) {
			return String(value)
				.replace(/[۰-۹]/g, (digit) => String('۰۱۲۳۴۵۶۷۸۹'.indexOf(digit)))
				.replace(/[٠-٩]/g, (digit) => String('٠١٢٣٤٥٦٧٨٩'.indexOf(digit)));
		}

		parseAmount(value) {
			const normalized = this.toEnglishDigits(value).replace(/[^0-9]/g, '');
			return Number(normalized) || 0;
		}

		formatMoney(value, currency = this.config.currency) {
			return `${this.faNumber.format(Number(value) || 0)} ${currency}`;
		}

		prefersReducedMotion() {
			return window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
		}

		setupStickySidebar() {
			const { donationColumn } = this.elements;

			if (!donationColumn) {
				return;
			}

			const update = () => this.updateStickySidebar();

			requestAnimationFrame(update);

			window.addEventListener(
				'resize',
				update,
				this.listenerOptions
			);

			if (typeof ResizeObserver !== 'undefined') {
				this.sidebarResizeObserver = new ResizeObserver(() => {
					this.updateStickySidebar();
				});

				this.sidebarResizeObserver.observe(donationColumn);
			}

			if (document.fonts && document.fonts.ready) {
				document.fonts.ready.then(() => {
					this.updateStickySidebar();
				});
			}
		}

		updateStickySidebar() {
			const { donationColumn } = this.elements;

			if (!donationColumn) {
				return;
			}

			/*
             * در موبایل Sticky نداریم.
             */
			if (window.matchMedia('(max-width: 767px)').matches) {
				donationColumn.style.removeProperty('--bap-sidebar-sticky-top');
				return;
			}

			const styles = window.getComputedStyle(donationColumn);

			const topOffset =
				parseFloat(
					styles.getPropertyValue('--bap-sticky-top-offset')
				) || 94;

			const bottomGap =
				parseFloat(
					styles.getPropertyValue('--bap-sticky-bottom-gap')
				) || 20;

			const sidebarHeight = donationColumn.offsetHeight;
			const viewportHeight = window.innerHeight;

			/*
             * اگر ستون بلندتر از فضای قابل مشاهده باشد،
             * top منفی می‌شود تا ابتدا ستون همراه صفحه حرکت کند.
             *
             * دقیقاً وقتی انتهای ستون به پایین viewport رسید،
             * position: sticky فعال می‌شود.
             */
			const bottomAlignedTop =
				viewportHeight - sidebarHeight - bottomGap;

			const stickyTop = Math.min(
				topOffset,
				bottomAlignedTop
			);

			donationColumn.style.setProperty(
				'--bap-sidebar-sticky-top',
				`${Math.floor(stickyTop)}px`
			);
		}


		async prepareQuickCheckout(amount) {
			const {amountError, quickCheckout} = this.elements;

			if (!this.config.quickPrepareEndpoint || !this.config.cartNonce || !quickCheckout) {
				if (amountError) {
					amountError.textContent = 'پرداخت سریع در دسترس نیست.';
				}
				return;
			}

			if (amountError) {
				amountError.textContent = '';
			}

			this.setSubmitLoading(true);
			const body = new URLSearchParams();
			body.set('nonce', this.config.cartNonce);
			body.set('product_id', this.config.projectId);
			body.set('amount', String(amount));

			try {
				const payload = await this.requestJson(this.config.quickPrepareEndpoint, body);
				const data = payload.data || {};

				quickCheckout.innerHTML = data.html || '';
				quickCheckout.hidden = false;
				this.checkoutStage = 'checkout';
				this.lockedAmount = Number(data.amount) || amount;
				this.payButtonText = data.pay_label || 'پرداخت';
				this.setAmountControlsLocked(true);
				this.refreshSubmitLabel();
				this.initializeCheckoutFields();
				this.updateStickySidebar();

				quickCheckout.scrollIntoView({
					behavior: this.prefersReducedMotion() ? 'auto' : 'smooth',
					block: 'nearest',
				});
			} catch (error) {
				if (amountError) {
					amountError.textContent = error instanceof Error ? error.message : 'آماده‌سازی پرداخت انجام نشد.';
				}
			} finally {
				this.setSubmitLoading(false);
			}
		}

		async processQuickPayment(amount) {
			const {form, amountError} = this.elements;

			if (!form || !this.config.quickPaymentEndpoint || !this.config.cartNonce) {
				return;
			}

			if (amountError) {
				amountError.textContent = '';
			}

			this.setSubmitLoading(true);
			const body = new URLSearchParams();
			const formData = new FormData(form);

			formData.forEach((value, key) => {
				if (typeof value === 'string') {
					body.append(key, value);
				}
			});

			body.set('nonce', this.config.cartNonce);
			body.set('product_id', this.config.projectId);
			body.set('amount', String(amount));

			try {
				const payload = await this.requestJson(this.config.quickPaymentEndpoint, body);
				const data = payload.data || {};

				if (!data.redirect) {
					throw new Error('آدرس انتقال به درگاه دریافت نشد.');
				}

				window.location.assign(data.redirect);
			} catch (error) {
				if (amountError) {
					amountError.textContent = error instanceof Error ? error.message : 'شروع پرداخت انجام نشد.';
				}
				this.setSubmitLoading(false);
			}
		}

		async requestJson(endpoint, body) {
			const response = await fetch(endpoint, {
				method: 'POST',
				credentials: 'same-origin',
				headers: {
					'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8',
				},
				body: body.toString(),
			});

			let payload = null;
			try {
				payload = await response.json();
			} catch (error) {
				throw new Error('پاسخ نامعتبر از سرور دریافت شد.');
			}

			if (!payload || !payload.success) {
				throw new Error(payload && payload.data && payload.data.message ? payload.data.message : 'عملیات پرداخت انجام نشد.');
			}

			return payload;
		}

		resetQuickCheckout() {
			const {quickCheckout, amountInput} = this.elements;
			this.checkoutStage = 'amount';
			this.lockedAmount = 0;
			this.setAmountControlsLocked(false);

			if (quickCheckout) {
				quickCheckout.innerHTML = '';
				quickCheckout.hidden = true;
			}

			this.refreshSubmitLabel();
			this.updateStickySidebar();

			if (amountInput) {
				amountInput.focus();
			}
		}

		setAmountControlsLocked(locked) {
			const {amountInput, presetButtons} = this.elements;
			if (amountInput) {
				amountInput.readOnly = Boolean(locked);
				amountInput.setAttribute('aria-readonly', locked ? 'true' : 'false');
			}
			presetButtons.forEach((button) => {
				button.disabled = Boolean(locked);
			});
			this.root.classList.toggle('bap--checkout-active', Boolean(locked));
		}

		initializeCheckoutFields() {
			if (!window.jQuery) {
				return;
			}

			window.jQuery(document.body).trigger('wc-enhanced-select-init');
			window.jQuery(document.body).trigger('country_to_state_changing');
			window.jQuery(document.body).trigger('country_to_state_changed');
			window.jQuery(document.body).trigger('updated_checkout');
		}

		setSubmitLoading(loading) {
			const {submitButton, submitLabel} = this.elements;

			if (!submitButton) {
				return;
			}

			if (typeof this.submitOriginalText === 'undefined') {
				this.submitOriginalText = submitLabel ? submitLabel.textContent : '';
			}

			submitButton.disabled = Boolean(loading);

			submitButton.classList.toggle('is-loading', Boolean(loading));

			submitButton.setAttribute('aria-busy', loading ? 'true' : 'false');

			if (submitLabel) {
				if (loading) {
					submitLabel.textContent = this.checkoutStage === 'checkout' ? 'در حال انتقال به درگاه...' : 'در حال آماده‌سازی...';
				} else {
					this.refreshSubmitLabel();
				}
			}
		}

		refreshSubmitLabel() {
			const {submitLabel} = this.elements;
			if (!submitLabel) {
				return;
			}
			submitLabel.textContent = this.checkoutStage === 'checkout' ? this.payButtonText : (this.submitOriginalText || submitLabel.textContent);
		}
		onDestroy() {
			window.clearTimeout(this.toastTimer);

			if (this.elements && this.elements.lightbox && !this.elements.lightbox.hidden) {
				this.closeLightbox();
			}

			if (this.mediaCarousel) {
				this.mediaCarousel.destroy();
				this.mediaCarousel = null;
			}

			if (this.sidebarResizeObserver) {
				this.sidebarResizeObserver.disconnect();
				this.sidebarResizeObserver = null;
			}

			if (this.abortController) {
				this.abortController.abort();
			}

			super.onDestroy();
		}
	}

	window.addEventListener('elementor/frontend/init', () => {
		elementorFrontend.elementsHandler.attachHandler(
			'bonyad_alavi_participation',
			BonyadAlaviParticipationHandler
		);
	});
})();

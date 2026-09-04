(() => {
	'use strict';

	const ROOT_SELECTOR = '[data-ba-admin-repeater]';

	/**
	 * وضعیت خالی Repeater را بعد از هر تغییر همگام می‌کند.
	 *
	 * @param {HTMLElement} repeater ریشه Repeater.
	 */
	const syncEmptyState = (repeater) => {
		const list = repeater.querySelector('[data-ba-admin-repeater-list]');
		const empty = repeater.querySelector('[data-ba-admin-repeater-empty]');

		if (!list || !empty) {
			return;
		}

		empty.hidden = Boolean(list.querySelector('[data-ba-admin-repeater-item]'));
	};

	/**
	 * رویداد سفارشی تغییر Repeater را برای مصرف‌کننده‌های آینده منتشر می‌کند.
	 *
	 * @param {HTMLElement} repeater ریشه Repeater.
	 * @param {string} action نوع تغییر.
	 * @param {HTMLElement|null} item ردیف مرتبط.
	 */
	const dispatchChange = (repeater, action, item = null) => {
		repeater.dispatchEvent(new CustomEvent('ba:admin-repeater:change', {
			bubbles: true,
			detail: { action, item }
		}));
	};

	/**
	 * ردیف جدید را از template همان Repeater ایجاد می‌کند.
	 *
	 * @param {HTMLElement} repeater ریشه Repeater.
	 */
	const addItem = (repeater) => {
		const list = repeater.querySelector('[data-ba-admin-repeater-list]');
		const template = repeater.querySelector('[data-ba-admin-repeater-template]');

		if (!list || !template) {
			return;
		}

		const nextIndex = Number.parseInt(repeater.dataset.baAdminRepeaterNextIndex || '0', 10);
		const html = template.innerHTML.split('__INDEX__').join(String(nextIndex));
		repeater.dataset.baAdminRepeaterNextIndex = String(nextIndex + 1);
		list.insertAdjacentHTML('beforeend', html);

		const item = list.lastElementChild;
		syncEmptyState(repeater);
		dispatchChange(repeater, 'add', item);

		item?.querySelector('input:not([type="hidden"]), textarea, select, button')?.focus();
	};

	/**
	 * یک ردیف را در صورت وجود همسایه به بالا یا پایین منتقل می‌کند.
	 *
	 * @param {HTMLElement} repeater ریشه Repeater.
	 * @param {HTMLElement} item ردیف جاری.
	 * @param {'up'|'down'} direction جهت حرکت.
	 */
	const moveItem = (repeater, item, direction) => {
		if (direction === 'up' && item.previousElementSibling) {
			item.parentElement.insertBefore(item, item.previousElementSibling);
			dispatchChange(repeater, 'move-up', item);
			return;
		}

		if (direction === 'down' && item.nextElementSibling) {
			item.parentElement.insertBefore(item.nextElementSibling, item);
			dispatchChange(repeater, 'move-down', item);
		}
	};

	document.addEventListener('click', (event) => {
		const addButton = event.target.closest('[data-ba-admin-repeater-add]');
		if (addButton) {
			const repeater = addButton.closest(ROOT_SELECTOR);
			if (repeater) {
				addItem(repeater);
			}
			return;
		}

		const item = event.target.closest('[data-ba-admin-repeater-item]');
		const repeater = item?.closest(ROOT_SELECTOR);
		if (!item || !repeater) {
			return;
		}

		if (event.target.closest('[data-ba-admin-repeater-remove]')) {
			item.remove();
			syncEmptyState(repeater);
			dispatchChange(repeater, 'remove');
			return;
		}

		if (event.target.closest('[data-ba-admin-repeater-up]')) {
			moveItem(repeater, item, 'up');
			return;
		}

		if (event.target.closest('[data-ba-admin-repeater-down]')) {
			moveItem(repeater, item, 'down');
		}
	});

	document.querySelectorAll(ROOT_SELECTOR).forEach(syncEmptyState);
})();

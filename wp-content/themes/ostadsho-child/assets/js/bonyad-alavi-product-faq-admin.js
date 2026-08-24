(() => {
	'use strict';

	const metabox = document.querySelector('#bap-product-faqs');

	if (!metabox) {
		return;
	}

	const rows = metabox.querySelector('[data-bap-faq-rows]');
	const template = metabox.querySelector('[data-bap-faq-template]');
	const addButton = metabox.querySelector('[data-bap-faq-add]');
	let nextIndex = Number.parseInt(metabox.dataset.nextIndex || '0', 10);

	if (!rows || !template || !addButton) {
		return;
	}

	addButton.addEventListener('click', () => {
		const html = template.innerHTML.split('__INDEX__').join(String(nextIndex));
		nextIndex += 1;
		rows.insertAdjacentHTML('beforeend', html);

		const createdRow = rows.lastElementChild;
		createdRow?.querySelector('input[type="text"]')?.focus();
	});

	metabox.addEventListener('click', (event) => {
		const removeButton = event.target.closest('[data-bap-faq-remove]');
		const upButton = event.target.closest('[data-bap-faq-up]');
		const downButton = event.target.closest('[data-bap-faq-down]');
		const row = event.target.closest('[data-bap-faq-row]');

		if (!row) {
			return;
		}

		if (removeButton) {
			row.remove();
			return;
		}

		if (upButton && row.previousElementSibling) {
			rows.insertBefore(row, row.previousElementSibling);
			return;
		}

		if (downButton && row.nextElementSibling) {
			rows.insertBefore(row.nextElementSibling, row);
		}
	});
})();

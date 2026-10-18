/* The AFE engine owns submission and validation; this adapter only
   customizes the success message of the facilitator recruitment form. */
(() => {
  'use strict';

  const initialize = (root = document) => {
    root.querySelectorAll('form.afe-form[data-form="facilitator-recruitment"]').forEach(form => {
      if (form.dataset.baFacilitatorInitialized === '1') return;
      form.dataset.baFacilitatorInitialized = '1';

      const result = form.querySelector('.afe-form-result');
      if (!result) return;

      const update = () => {
        const success = result.querySelector('.afe-success:not([data-ba-cleaned])');
        if (!success) return;
        success.dataset.baCleaned = '1';
        success.replaceChildren();
        const message = document.createElement('strong');
        message.textContent = 'اطلاعات شما با موفقیت ثبت شد.';
        success.appendChild(message);
        form.querySelectorAll('.afe-submit').forEach(button => {
          button.disabled = true;
        });
      };

      const observer = new MutationObserver(update);
      observer.observe(result, { childList: true, subtree: true });
      update();
    });
  };

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', () => initialize(), { once: true });
  } else {
    initialize();
  }

  // Elementor can inject a shortcode's markup into the editor preview.
  document.addEventListener('elementor/popup/show', () => initialize());
})();

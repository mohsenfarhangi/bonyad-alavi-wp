(() => {
  function initOverflowMenu() {
    const menu = document.querySelector(
      '.elementor-location-header ' +
      '.elementor-element-1de8c0f .pweb_menu > ul.menu'
    );

    if (!menu || menu.dataset.overflowReady) return;
    menu.dataset.overflowReady = 'true';

    const desktop = window.matchMedia('(min-width: 1025px)');
    const items = [...menu.children].filter(el => el.tagName === 'LI');

    const more = document.createElement('li');
    more.className = 'alavi-more';
    more.hidden = true;

    const button = document.createElement('button');
    button.type = 'button';
    button.textContent = 'بیشتر ▾';
    button.setAttribute('aria-expanded', 'false');

    const dropdown = document.createElement('ul');
    dropdown.className = 'alavi-more-list';
    dropdown.id = 'alavi-overflow-dropdown';
    dropdown.hidden = true;

    button.setAttribute('aria-controls', dropdown.id);
    more.append(button, dropdown);
    menu.append(more);

    const style = document.createElement('style');
    style.textContent = `
      .pweb_menu ul.alavi-overflow {
        display: flex !important;
        flex-wrap: nowrap !important;
        align-items: center;
        width: 100%;
        min-width: 0;
        box-sizing: border-box;
        overflow: visible !important;
      }

      .pweb_menu ul.alavi-overflow > li {
        flex: 0 0 auto;
      }

      .pweb_menu ul.alavi-overflow > li > a {
        white-space: nowrap;
      }

      .pweb_menu .alavi-more[hidden],
      .pweb_menu .alavi-more-list[hidden] {
        display: none !important;
      }

      .pweb_menu ul.alavi-overflow > li.alavi-more {
        position: relative;
      }

      .pweb_menu .alavi-more > button {
        appearance: none;
        border: 0;
        margin: 0;
        padding: 0 12px;
        border-radius: 10px;
        background: transparent;
        color: inherit;
        font-family: inherit;
        font-size: 14px;
        font-weight: 500;
        line-height: 25px;
        white-space: nowrap;
        cursor: pointer;
      }

      .pweb_menu .alavi-more > button:hover,
      .pweb_menu .alavi-more > button[aria-expanded="true"] {
        color: var(--theme-main-color, #16704a);
        background: var(--theme-main-opaque-color, #eef5f1);
      }

      .pweb_menu .alavi-more > button:focus-visible {
        outline: 2px solid var(--theme-main-color, #16704a);
        outline-offset: 3px;
      }

      .pweb_menu ul.alavi-overflow > li.alavi-more > ul.alavi-more-list {
        position: absolute !important;
        top: calc(100% + 10px) !important;
        right: auto !important;
        left: 0 !important;
        display: block;
        width: 250px;
        max-width: calc(100vw - 32px);
        max-height: 65vh;
        overflow: auto;
        box-sizing: border-box;
        margin: 0 !important;
        padding: 6px !important;
        list-style: none;
        background: var(--pweb-header-menu-li-ul-background, #fff);
        border-top: 3px solid var(--theme-main-color, #16704a);
        border-radius: 10px;
        box-shadow: 0 12px 35px rgb(0 0 0 / 15%);
        opacity: 1 !important;
        visibility: visible !important;
        z-index: 1000;
        text-align: right;
      }

      .pweb_menu ul.alavi-more-list li {
        width: auto;
        list-style: none;
      }

      .pweb_menu ul.alavi-more-list li > a {
        display: block;
        margin: 0;
        white-space: normal;
      }

      /* زیرمنوهای آیتم‌های منتقل‌شده داخل دراپ‌داون باز می‌شوند. */
      .pweb_menu ul.alavi-more-list li > ul.sub-menu {
        display: none !important;
        position: static !important;
        width: auto !important;
        top: auto !important;
        right: auto !important;
        left: auto !important;
        margin: 0 !important;
        padding: 0 12px 0 0 !important;
        border: 0;
        box-shadow: none;
        opacity: 1 !important;
        visibility: visible !important;
      }

      .pweb_menu ul.alavi-more-list li:hover > ul.sub-menu,
      .pweb_menu ul.alavi-more-list li:focus-within > ul.sub-menu {
        display: block !important;
      }
    `;
    document.head.append(style);

    function setOpen(open) {
      dropdown.hidden = !open;
      button.setAttribute('aria-expanded', String(open));
    }

    // همان عناصر اصلی جابه‌جا می‌شوند؛ کپی ایجاد نمی‌شود.
    function restoreItems() {
      items.forEach(item => menu.insertBefore(item, more));
    }

    function outerWidth(element) {
      const css = getComputedStyle(element);
      return (
        element.getBoundingClientRect().width +
        (parseFloat(css.marginLeft) || 0) +
        (parseFloat(css.marginRight) || 0)
      );
    }

    function update() {
      const focused = document.activeElement;
      const hadFocus = menu.contains(focused);

      restoreItems();
      more.hidden = true;

      if (!desktop.matches || !menu.getClientRects().length) {
        setOpen(false);
        menu.classList.remove('alavi-overflow');
        return;
      }

      menu.classList.add('alavi-overflow');

      const css = getComputedStyle(menu);
      const available =
        menu.clientWidth -
        (parseFloat(css.paddingLeft) || 0) -
        (parseFloat(css.paddingRight) || 0);

      const gap = parseFloat(css.columnGap) || 0;
      const widths = items.map(outerWidth);
      const total = widths.reduce((sum, width) => sum + width, 0);
      const allGaps = gap * Math.max(0, items.length - 1);

      if (total + allGaps <= available) {
        setOpen(false);
      } else {
        // فضای دکمه «بیشتر» هم در محاسبه منظور می‌شود.
        more.hidden = false;
        const buttonWidth = outerWidth(more);

        let visibleCount = items.length;
        let usedWidth = total;

        while (
          visibleCount > 0 &&
          usedWidth + buttonWidth + gap * visibleCount > available
        ) {
          visibleCount--;
          usedWidth -= widths[visibleCount];
        }

        items.slice(visibleCount).forEach(item => dropdown.append(item));

        // اگر لینکِ دارای فوکوس منتقل شد، همچنان قابل دسترسی بماند.
        if (hadFocus && dropdown.contains(focused)) setOpen(true);
      }

      if (
        hadFocus &&
        focused instanceof HTMLElement &&
        !focused.closest('[hidden]')
      ) {
        focused.focus({ preventScroll: true });
      }
    }

    let frame = 0;

    function scheduleUpdate() {
      cancelAnimationFrame(frame);
      frame = requestAnimationFrame(update);
    }

    button.addEventListener('click', () => {
      setOpen(dropdown.hidden);
    });

    more.addEventListener('mouseenter', () => {
      setOpen(true);
    });

    more.addEventListener('mouseleave', () => {
      // هنگام استفاده از صفحه‌کلید، منو باز بماند.
      if (!more.contains(document.activeElement)) {
        setOpen(false);
      }
    });

    button.addEventListener('keydown', event => {
      if (event.key !== 'ArrowDown') return;
      event.preventDefault();
      setOpen(true);
      dropdown.querySelector('a')?.focus();
    });

    document.addEventListener('click', event => {
      if (!more.contains(event.target)) setOpen(false);
    });

    more.addEventListener('keydown', event => {
      if (event.key !== 'Escape') return;
      event.preventDefault();
      setOpen(false);
      button.focus();
    });

    more.addEventListener('focusout', event => {
      if (!more.contains(event.relatedTarget)) setOpen(false);
    });

    window.addEventListener('resize', scheduleUpdate, { passive: true });
    window.addEventListener('load', scheduleUpdate, { once: true });
    desktop.addEventListener('change', scheduleUpdate);

    const observer = new ResizeObserver(scheduleUpdate);
    observer.observe(menu.parentElement);

    if (document.fonts) {
      document.fonts.ready.then(scheduleUpdate);
      document.fonts.addEventListener('loadingdone', scheduleUpdate);
    }

    update();
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', initOverflowMenu, {
      once: true
    });
  } else {
    initOverflowMenu();
  }
})();

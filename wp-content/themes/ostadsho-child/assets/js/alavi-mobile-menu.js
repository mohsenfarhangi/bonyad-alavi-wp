/* Mobile menu enhancements for Bonyad Alavi. */
(() => {
  function init() {
    const widget = document.querySelector('.elementor-element-13247ab');
    const sidebar = widget?.querySelector('.mobile-sidebar');
    const panel = sidebar?.querySelector('.hambmenu-menus');
    const menu = panel?.querySelector('ul.menu');
    const close = panel?.querySelector('button.close');
    const headline = panel?.querySelector('.headline');
    const trigger = widget?.querySelector('.hambmenu');
    if (!menu || !close || !headline || !trigger || sidebar.dataset.liveRedesign) return;
    sidebar.dataset.liveRedesign = 'true';
    sidebar.classList.add('alavi-mobile-redesign');
    const path = s => s.replace(/\/+$/, '') || '/';
    [...menu.children].forEach(li => {
      const a = li.querySelector(':scope > a');
      if (!a || li.classList.contains('menu-item-has-children')) return;
      const url = new URL(a.href, location.href);
      li.classList.toggle('am-current', !url.hash && url.origin === location.origin && path(url.pathname) === path(location.pathname));
    });
    close.setAttribute('aria-label', 'بستن منو');
    const intro = document.createElement('div');
    intro.className = 'am-intro';
    intro.innerHTML = '<strong>همراهِ آبادانی و توانمندسازی</strong><p>دسترسی به بخش‌های بنیاد علوی</p>';
    headline.after(intro);
    const groups = [...menu.children].filter(li => li.querySelector(':scope > ul.sub-menu'));
    groups.forEach((li, i) => {
      const sub = li.querySelector(':scope > ul.sub-menu');
      sub.id = 'am-submenu-' + i;
      const toggle = document.createElement('button');
      toggle.type = 'button';
      toggle.className = 'am-toggle';
      toggle.setAttribute('aria-controls', sub.id);
      toggle.setAttribute('aria-label', 'زیرمنوی ' + (li.querySelector(':scope > a')?.textContent.trim() || ''));
      toggle.innerHTML = '<i class="fas fa-chevron-down" aria-hidden="true"></i>';
      const apply = open => {
        li.classList.toggle('am-expanded', open);
        toggle.setAttribute('aria-expanded', String(open));
        sub.hidden = !open;
        sub.style.display = open ? 'block' : 'none';
      };
      li.amApply = apply;
      apply(false);
      li.insertBefore(toggle, sub);
      const change = e => {
        e.preventDefault();
        e.stopImmediatePropagation();
        const open = toggle.getAttribute('aria-expanded') !== 'true';
        groups.forEach(g => g.amApply?.(false));
        apply(open);
      };
      toggle.addEventListener('click', change);
      const a = li.querySelector(':scope > a');
      if (a?.getAttribute('href') === '/') a.addEventListener('click', change, true);
    });
    const footer = document.createElement('div');
    footer.className = 'am-footer';
    const actions = document.createElement('div');
    actions.className = 'am-actions';
    const account = document.querySelector('header a[href="https://bonyadalavi.ir/my-account/"]');
    const contact = menu.querySelector('a[href="https://bonyadalavi.ir/contact/"]');
    if (account) {
      const a = account.cloneNode(false);
      a.textContent = 'حساب کاربری';
      actions.append(a);
    }
    if (contact) {
      const a = contact.cloneNode(false);
      a.textContent = 'ارتباط با بنیاد';
      actions.append(a);
    }
    footer.append(actions);
    const langs = document.createElement('div');
    langs.className = 'am-languages';
    document.querySelectorAll('#ep-megamenu-daa5df0 .ep-megamenu-panel a').forEach((source, i) => {
      const a = source.cloneNode(false);
      a.textContent = source.textContent.trim();
      if (i === 0) a.setAttribute('aria-current', 'page');
      langs.append(a);
    });
    footer.append(langs);
    panel.append(footer);
    panel.setAttribute('role', 'dialog');
    panel.setAttribute('aria-modal', 'true');
    panel.setAttribute('aria-label', 'منوی بنیاد علوی');
    trigger.setAttribute('role', 'button');
    trigger.setAttribute('tabindex', '0');
    trigger.setAttribute('aria-label', 'باز کردن منو');
    const observer = new MutationObserver(() => {
      const open = sidebar.classList.contains('active');
      trigger.setAttribute('aria-expanded', String(open));
      if (open) close.focus({ preventScroll: true });
      else if (sidebar.contains(document.activeElement)) trigger.focus({ preventScroll: true });
    });
    observer.observe(sidebar, { attributes: true, attributeFilter: ['class'] });
    trigger.addEventListener('keydown', e => {
      if (e.key === 'Enter' || e.key === ' ') {
        e.preventDefault();
        trigger.click();
      }
    });
    sidebar.addEventListener('keydown', e => {
      if (!sidebar.classList.contains('active')) return;
      if (e.key === 'Escape') {
        e.preventDefault();
        close.click();
      }
      if (e.key === 'Tab') {
        const visible = [...panel.querySelectorAll('a,button,input,[tabindex="0"]')].filter(n => n.getClientRects().length);
        const first = visible[0], last = visible.at(-1);
        if (!first || !last) return;
        if (e.shiftKey && document.activeElement === first) {
          e.preventDefault(); last.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
          e.preventDefault(); first.focus();
        }
      }
    });
    trigger.setAttribute('aria-expanded', String(sidebar.classList.contains('active')));
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', init, { once: true });
  else init();
})();

(() => {
  const root = document.documentElement;
  const storageKey = 'electro-theme-mode';
  const allowedModes = ['light', 'dark'];
  const readMode = () => {
    try {
      const saved = localStorage.getItem(storageKey);
      return allowedModes.includes(saved) ? saved : 'light';
    } catch (_) {
      return 'light';
    }
  };
  const applyMode = (mode) => {
    if (!allowedModes.includes(mode)) return;
    root.dataset.themeMode = mode;
    document.querySelectorAll('[data-theme-mode-option]').forEach((button) => {
      button.setAttribute('aria-pressed', String(button.dataset.themeModeOption === mode));
    });
    try { localStorage.setItem(storageKey, mode); } catch (_) {}
  };

  // Keep the store's established default palette and discard older palette selections.
  root.dataset.themePreset = 'default';
  try { localStorage.removeItem('electro-theme-preset'); } catch (_) {}
  applyMode(readMode());

  // Place the storefront theme control in the account/action bar beside Orders.
  const headerActions = document.querySelector('.site-header .actions');
  const themeWidget = document.querySelector('[data-theme-switcher]');
  if (headerActions && themeWidget) {
    themeWidget.classList.add('theme-switcher-header');
    const anchor = headerActions.querySelector('.orders-nav') || headerActions.querySelector('a[href*="giohang"]');
    if (anchor) anchor.after(themeWidget);
  }

  document.querySelectorAll('[data-theme-switcher]').forEach((widget) => {
    const panel = widget.querySelector('.theme-switcher-panel');
    const trigger = widget.querySelector('[data-theme-open]');
    const close = widget.querySelector('[data-theme-close]');
    if (!panel || !trigger || !close) return;
    const toggle = (open) => {
      panel.hidden = !open;
      trigger.setAttribute('aria-expanded', String(open));
      if (open) {
        const chatPanel = document.querySelector('[data-chat-panel]');
        const chatTrigger = document.querySelector('[data-chat-open]');
        if (chatPanel) chatPanel.hidden = true;
        if (chatTrigger) chatTrigger.setAttribute('aria-expanded', 'false');
        close.focus();
      } else {
        trigger.focus();
      }
    };
    trigger.addEventListener('click', () => toggle(panel.hidden));
    close.addEventListener('click', () => toggle(false));
    widget.querySelectorAll('[data-theme-mode-option]').forEach((button) => {
      button.addEventListener('click', () => applyMode(button.dataset.themeModeOption));
    });
    document.addEventListener('keydown', (event) => {
      if (event.key === 'Escape' && !panel.hidden) toggle(false);
    });
    document.addEventListener('click', (event) => {
      if (!panel.hidden && !widget.contains(event.target)) {
        panel.hidden = true;
        trigger.setAttribute('aria-expanded', 'false');
      }
    });
  });
})();

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
  const updateToggleLabels = (mode) => {
    const nextMode = mode === 'dark' ? 'light' : 'dark';
    const label = nextMode === 'dark' ? 'Chuyển sang giao diện tối' : 'Chuyển sang giao diện sáng';
    document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
      button.setAttribute('aria-pressed', String(mode === 'dark'));
      button.setAttribute('aria-label', label);
      button.title = label;
    });
  };
  const applyMode = (mode) => {
    if (!allowedModes.includes(mode)) return;
    root.dataset.themeMode = mode;
    updateToggleLabels(mode);
    try { localStorage.setItem(storageKey, mode); } catch (_) {}
  };

  // Keep the store's established default palette and discard older palette selections.
  root.dataset.themePreset = 'default';
  try { localStorage.removeItem('electro-theme-preset'); } catch (_) {}
  applyMode(readMode());

  // Keep the theme control in the storefront header, beside Orders or sign-in links.
  const headerActions = document.querySelector('.site-header .actions');
  const themeWidget = document.querySelector('[data-theme-switcher]');
  if (headerActions && themeWidget) {
    themeWidget.classList.add('theme-switcher-header');
    const anchor = headerActions.querySelector('.orders-nav') || headerActions.querySelector('a[href*="giohang"]');
    const guestAnchor = headerActions.querySelector('a[href*="dangnhap"]') || headerActions.querySelector('a[href*="dangky"]');
    if (anchor) anchor.before(themeWidget);
    else if (guestAnchor) guestAnchor.before(themeWidget);
    else headerActions.append(themeWidget);
  }

  document.querySelectorAll('[data-theme-toggle]').forEach((button) => {
    button.addEventListener('click', () => {
      const currentMode = root.dataset.themeMode === 'dark' ? 'dark' : 'light';
      applyMode(currentMode === 'dark' ? 'light' : 'dark');
    });
  });
})();
/* ═══════════════════════════════════════
   Taqwim LMS — Theme Manager v2.0
   Dark/Light switch with localStorage
   ═══════════════════════════════════════ */

(function() {
  'use strict';

  const STORAGE_KEY = 'taqwim_theme';
  const DEFAULT     = 'light';

  // Apply theme immediately (prevent flash)
  function getTheme() {
    return localStorage.getItem(STORAGE_KEY) || DEFAULT;
  }

  function applyTheme(theme) {
    document.documentElement.setAttribute('data-theme', theme);
    localStorage.setItem(STORAGE_KEY, theme);

    // Update all toggle icons
    document.querySelectorAll('.theme-icon').forEach(el => {
      el.textContent = theme === 'dark' ? '☀️' : '🌙';
    });
    document.querySelectorAll('.theme-toggle').forEach(el => {
      el.setAttribute('aria-label', theme === 'dark' ? 'Switch to light mode' : 'Switch to dark mode');
    });
  }

  function toggleTheme() {
    const current = getTheme();
    applyTheme(current === 'dark' ? 'light' : 'dark');
  }

  // Apply on load
  applyTheme(getTheme());

  // Expose globally
  window.toggleTheme = toggleTheme;
  window.applyTheme  = applyTheme;
  window.getTheme    = getTheme;

  // DOM ready: attach click handlers
  document.addEventListener('DOMContentLoaded', function() {
    document.querySelectorAll('.theme-toggle').forEach(btn => {
      btn.addEventListener('click', toggleTheme);
    });
    // Apply again after DOM ready (for icon update)
    applyTheme(getTheme());
  });
})();

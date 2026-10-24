/* MDO 0.8.0 - recover native mini Ticket grid height, without changing GLPI widget coordinates. */
(() => {
  'use strict';
  if (!/(?:^|\/)ticket\.php$/.test(location.pathname)) return;
  const wired = new WeakSet();
  // Native mini margin-bottom:-45px is neutralized by ticket-mini-dashboard.css.
  function fix(grid) {
    if (!grid.isConnected) return;
    const top = grid.getBoundingClientRect().top;
    let bottom = 0;
    for (const item of grid.querySelectorAll(':scope > .grid-stack-item')) {
      const rect = item.getBoundingClientRect();
      const card = item.querySelector('.grid-stack-item-content > .card');
      bottom = Math.max(bottom, rect.bottom - top,
        card ? card.getBoundingClientRect().bottom - top : 0);
    }
    const needed = Math.ceil(bottom + 12);
    if (bottom > 0 && Number.isFinite(needed)
      && needed > grid.getBoundingClientRect().height + 2) {
      grid.style.minHeight = needed + 'px';
    }
  }
  function attach(grid) {
    if (wired.has(grid)) return;
    wired.add(grid);
    const schedule = () => requestAnimationFrame(() => fix(grid));
    if (typeof ResizeObserver !== 'undefined') {
      new ResizeObserver(schedule).observe(grid);
    }
    new MutationObserver(schedule).observe(grid, {childList:true,subtree:true});
    schedule();
  }
  function scan() {
    document.querySelectorAll('.dashboard.mini .grid-stack').forEach(attach);
  }
  function boot() {
    scan();
    if (document.querySelector('.dashboard.mini .grid-stack')) return;
    const root = document.querySelector('main') || document.body;
    if (!root) return;
    const pending = new MutationObserver(() => {
      scan();
      if (document.querySelector('.dashboard.mini .grid-stack')) pending.disconnect();
    });
    pending.observe(root, {childList:true,subtree:true});
  }
  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', boot, {once:true});
  } else {
    boot();
  }
})();

/* MDO 0.8.0 - recover native mini Ticket grid height, without changing GLPI widget coordinates. */
(() => {
  'use strict';
  if (!/(?:^|\/)ticket\.php$/.test(location.pathname)) return;
  const wired = new WeakMap();
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
    if (Number.isFinite(needed)) {
      const value = (bottom > 0 ? needed : 0) + 'px';
      if (grid.style.getPropertyValue('--mdo-mini-content-height') !== value) {
        grid.style.setProperty('--mdo-mini-content-height', value);
      }
    }
  }
  function attach(grid) {
    if (wired.has(grid)) return;
    let frame = 0;
    const schedule = () => { if (!frame) frame = requestAnimationFrame(() => { frame = 0; fix(grid); }); };
    const observed = new Set();
    const sizes = typeof ResizeObserver !== 'undefined' ? new ResizeObserver(schedule) : null;
    const watch = () => {
      const nodes = new Set([grid, ...grid.querySelectorAll(':scope > .grid-stack-item, .grid-stack-item-content > .card')]);
      for (const node of nodes) { if (!observed.has(node)) { sizes?.observe(node); observed.add(node); } }
      for (const node of observed) { if (!nodes.has(node)) { sizes?.unobserve(node); observed.delete(node); } }
    };
    new MutationObserver(records => { watch(); if (records.some(r => r.target !== grid || r.type === 'childList')) schedule(); })
      .observe(grid, {childList:true,subtree:true,attributes:true,attributeFilter:['style','class','gs-y','gs-h']});
    watch();
    wired.set(grid, {sizes});
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

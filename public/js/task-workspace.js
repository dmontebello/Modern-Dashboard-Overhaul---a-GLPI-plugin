/* MDO progressive navigation and authenticated Kanban actions. */
(() => {
  'use strict';
  const selector = '.mdo-tasks';
  const chooseBoard = root => root.querySelector('[data-mdo-board]');
  const isTaskUrl = url => url.origin === location.origin &&
    url.pathname.endsWith('/plugins/mdoverhaul/front/tasks.php') &&
    !url.searchParams.has('new');
  const isSafeView = url => url.searchParams.get('source') === 'all' ||
    url.searchParams.get('view') === 'board';
  let busy = false;

  function initializeBoard(root) {
    const board = chooseBoard(root);
    if (!board) return;
    const live = root.querySelector('[data-mdo-live]');
    let dragged = null;
    async function move(card, target) {
      if (!card || busy || card.dataset.canMove !== '1') return;
      const oldColumn = card.closest('[data-mdo-column]');
      const destination = board.querySelector('[data-mdo-column="' + target + '"] .mdo-tasks-column-body');
      if (!oldColumn || !destination || oldColumn.dataset.mdoColumn === target) return;
      busy = true;
      const form = new URLSearchParams({
        source: card.dataset.source, id: card.dataset.id, stage: target,
        version: card.dataset.version || '', _glpi_csrf_token: board.dataset.token
      });
      try {
        const response = await fetch(board.dataset.endpoint, {
          method:'POST', credentials:'same-origin',
          headers:{'Content-Type':'application/x-www-form-urlencoded','Accept':'application/json'},
          body:form.toString()
        });
        const data = await response.json();
        if (!response.ok || !data.ok) throw new Error('Stage update rejected');
        destination.querySelector('.mdo-tasks-hint')?.remove();
        destination.append(card);
        card.dataset.version = data.version || '';
        for (const column of board.querySelectorAll('[data-mdo-column]')) {
          const count = column.querySelectorAll('.mdo-tasks-card').length;
          const heading = column.querySelector('header span');
          if (heading) heading.textContent = String(count);
          if (!count && !column.querySelector('.mdo-tasks-column-body .mdo-tasks-hint')) {
            const empty = document.createElement('p');
            empty.className = 'mdo-tasks-hint';
            empty.textContent = 'No tasks';
            column.querySelector('.mdo-tasks-column-body').append(empty);
          }
        }
        if (live) live.textContent = 'Task updated.';
        const select = card.querySelector('[data-stage-choice]');
        if (select) select.value = target;
      } catch {
        if (live) live.textContent = 'Unable to move task. Refresh and retry.';
        const select = card.querySelector('[data-stage-choice]');
        if (select) select.value = oldColumn.dataset.mdoColumn;
      } finally {
        busy = false;
      }
    }
    board.addEventListener('dragstart', event => {
      const card = event.target.closest('.mdo-tasks-card[data-can-move="1"]');
      if (!card) return;
      dragged = card;
      if (event.dataTransfer) event.dataTransfer.setData('text/plain', card.dataset.source + ':' + card.dataset.id);
    });
    board.addEventListener('dragover', event => {
      if (dragged && event.target.closest('[data-mdo-column]')) event.preventDefault();
    });
    board.addEventListener('drop', event => {
      const column = event.target.closest('[data-mdo-column]');
      if (column && dragged) {
        event.preventDefault();
        void move(dragged, column.dataset.mdoColumn);
      }
      dragged = null;
    });
    board.addEventListener('dragend', () => { dragged = null; });
    board.addEventListener('change', event => {
      if (!event.target.matches('[data-stage-choice]')) return;
      const card = event.target.closest('.mdo-tasks-card');
      if (card) void move(card, event.target.value);
    });
  }

  async function navigate(url, replace = false) {
    if (busy) return;
    busy = true;
    const root = document.querySelector(selector);
    root?.setAttribute('aria-busy','true');
    try {
      const response = await fetch(url.href, {credentials:'same-origin',headers:{Accept:'text/html'}});
      if (!response.ok) throw new Error('Navigation failed');
      const doc = new DOMParser().parseFromString(await response.text(), 'text/html');
      const fragment = doc.querySelector(selector);
      if (!root || !fragment) throw new Error('Workspace unavailable');
      root.replaceWith(fragment);
      if (!replace) history.pushState({mdoWorkspace:true},'',url.href);
      initializeBoard(fragment);
      fragment.querySelector('h1')?.setAttribute('tabindex','-1');
      fragment.querySelector('h1')?.focus({preventScroll:true});
    } catch {
      location.assign(url.href);
    } finally {
      busy = false;
    }
  }

  function start() {
    const root = document.querySelector(selector);
    if (!root) return;
    initializeBoard(root);
    document.addEventListener('click', event => {
      const link = event.target.closest('.mdo-tasks-view-tabs a, .mdo-tasks-stat, .mdo-tasks-pages a');
      if (!link || event.defaultPrevented || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;
      const url = new URL(link.href);
      if (!isTaskUrl(url) || !isSafeView(url)) return;
      event.preventDefault();
      void navigate(url);
    });
    document.addEventListener('submit', event => {
      const form = event.target.closest('.mdo-tasks-filters');
      if (!form || form.method.toLowerCase() !== 'get') return;
      const url = new URL(form.action);
      for (const [key,value] of new FormData(form)) url.searchParams.set(key,String(value));
      if (!isTaskUrl(url) || !isSafeView(url)) return;
      event.preventDefault();
      void navigate(url);
    });
    window.addEventListener('popstate', () => {
      const url = new URL(location.href);
      if (isTaskUrl(url) && isSafeView(url)) void navigate(url,true);
    });
  }
  if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded',start);
  else start();
})();

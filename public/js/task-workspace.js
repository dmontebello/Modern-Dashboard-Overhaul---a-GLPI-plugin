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
    let dragPreview = null;
    const clearDrag = () => {
      board.querySelectorAll('.mdo-drop-target').forEach(el => el.classList.remove('mdo-drop-target'));
      dragged?.classList.remove('mdo-card-dragging');
      dragged = null;
      if (dragPreview) { dragPreview.remove(); dragPreview = null; }
    };
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
        // GLPI consumes a token even when an authenticated POST returns 403/409.
        if (typeof data.token === 'string' && data.token) board.dataset.token = data.token;
        if (!response.ok || !data.ok) throw new Error('Stage update rejected');
        destination.querySelector('.mdo-tasks-hint')?.remove();
        destination.append(card);
        card.dataset.version = data.version || '';
        card.dataset.stage = target;
        if (card.dataset.source === 'project') {
          const progress = target === 'todo' ? 0 : target === 'done' ? 100 :
            Math.max(1, Math.min(99, Number(card.querySelector('[role="progressbar"]')?.getAttribute('aria-valuenow')) || 50));
          const bar = card.querySelector('[role="progressbar"]');
          if (bar) {
            bar.setAttribute('aria-valuenow', String(progress));
            const fill = bar.querySelector('span');
            if (fill) fill.style.width = progress + '%';
            const value = bar.nextElementSibling;
            if (value?.tagName === 'SMALL') value.textContent = progress + '%';
          }
        }
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
        root.querySelectorAll('.mdo-tasks-stat').forEach(stat => {
          const key = new URL(stat.href).searchParams.get('filter');
          const allCards = board.querySelectorAll('.mdo-tasks-card');
          const value = key === 'all' ? allCards.length : key === 'open' ?
            board.querySelectorAll('[data-mdo-column="todo"] .mdo-tasks-card, [data-mdo-column="doing"] .mdo-tasks-card').length :
            key === 'done' ? board.querySelectorAll('[data-mdo-column="done"] .mdo-tasks-card').length : null;
          if (value !== null) { const label = stat.querySelector('strong'); if (label) label.textContent = String(value); }
        });
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
      card.classList.add('mdo-card-dragging');
      if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', card.dataset.source + ':' + card.dataset.id);
        dragPreview = card.cloneNode(true);
        dragPreview.classList.add('mdo-drag-preview');
        dragPreview.style.width = card.getBoundingClientRect().width + 'px';
        // Chromium needs the custom drag image inside the viewport at capture time.
        dragPreview.style.left = Math.max(0, event.clientX - 30) + 'px';
        dragPreview.style.top = Math.max(0, event.clientY - 24) + 'px';
        document.body.append(dragPreview);
        event.dataTransfer.setDragImage(dragPreview, 30, 24);
        requestAnimationFrame(() => { if (dragPreview) { dragPreview.remove(); dragPreview = null; } });
      }
    });
    board.addEventListener('dragover', event => {
      const column = event.target.closest('[data-mdo-column]');
      if (!dragged || !column || busy) return;
      event.preventDefault();
      if (event.dataTransfer) event.dataTransfer.dropEffect = 'move';
      board.querySelectorAll('.mdo-drop-target').forEach(el => el.classList.remove('mdo-drop-target'));
      column.classList.add('mdo-drop-target');
    });
    board.addEventListener('dragleave', event => {
      const column = event.target.closest('[data-mdo-column]');
      if (column && !column.contains(event.relatedTarget)) column.classList.remove('mdo-drop-target');
    });
    board.addEventListener('drop', event => {
      const column = event.target.closest('[data-mdo-column]');
      if (column && dragged) {
        event.preventDefault();
        void move(dragged, column.dataset.mdoColumn);
      }
      clearDrag();
    });
    board.addEventListener('dragend', clearDrag);
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
      const deleteForm = event.target.closest('.mdo-task-delete-form');
      if (deleteForm) {
        event.preventDefault();
        const button = deleteForm.querySelector('[data-mdo-task-delete]');
        if (button?.disabled || !window.confirm(button?.dataset.confirm || 'Move this task to trash?')) return;
        deleteForm.querySelector('[name="confirm_delete"]').value = '1';
        button.disabled = true;
        const row = deleteForm.closest('[data-mdo-list-row]');
        (async () => {
          try {
            const response = await fetch(deleteForm.action, {
              method:'POST', credentials:'same-origin',
              headers:{'Content-Type':'application/x-www-form-urlencoded', 'Accept':'application/json',
                'X-Requested-With':'XMLHttpRequest'},
              body:new URLSearchParams(new FormData(deleteForm)).toString()
            });
            const data = await response.json();
            if (data.token) deleteForm.querySelector('[name="_glpi_csrf_token"]').value = data.token;
            if (!response.ok || !data.ok) throw new Error('Deletion rejected');
            row?.remove();
            // Refresh only the workspace region: counters, pagination and filters
            // are now calculated by the server after a successful deletion.
            const url = new URL(location.href);
            if (isTaskUrl(url) && isSafeView(url)) await navigate(url, true);
          } catch {
            button.disabled = false;
            window.alert('Unable to delete task. Check permissions and retry.');
          }
        })();
        return;
      }
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

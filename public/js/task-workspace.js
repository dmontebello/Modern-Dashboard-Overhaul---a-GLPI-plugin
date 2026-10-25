/* MDO Kanban progressive enhancement. */
(() => {
  const board = document.querySelector('.mdo-tasks-board[data-mdo-board]');
  if (!board) return;
  let dragged = null;
  const live = document.querySelector('[data-mdo-live]');
  async function move(card, target) {
    const current = card.closest('[data-mdo-column]');
    if (!current || current.dataset.mdoColumn === target) return;
    const form = new URLSearchParams({
      source: card.dataset.source, id: card.dataset.id,
      stage: target, version: card.dataset.version || '',
      _glpi_csrf_token: board.dataset.token
    });
    try {
      const response = await fetch(board.dataset.endpoint, {
        method: 'POST', credentials: 'same-origin',
        headers: {'Content-Type': 'application/x-www-form-urlencoded', 'Accept': 'application/json'},
        body: form.toString()
      });
      const result = await response.json();
      if (!response.ok || !result.ok) throw new Error(result.error || 'failed');
      const destination = board.querySelector('[data-mdo-column="' + target + '"] .mdo-tasks-column-body');
      destination.append(card);
      card.dataset.version = result.version || '';
      board.querySelectorAll('[data-mdo-column]').forEach(column => {
        column.querySelector('header span').textContent = column.querySelectorAll('.mdo-tasks-card').length;
      });
      if (live) live.textContent = 'Task updated.';
    } catch (error) {
      if (live) live.textContent = 'Unable to move task. Refresh and retry.';
      const select = card.querySelector('[data-stage-choice]');
      if (select) select.value = current.dataset.mdoColumn;
    }
  }
  board.addEventListener('dragstart', event => {
    const card = event.target.closest('.mdo-tasks-card[data-can-move="1"]');
    if (card) { dragged = card; event.dataTransfer.setData('text/plain', card.dataset.source + ':' + card.dataset.id); }
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
})();

(() => {
  const entries = document.querySelector('[data-mdo-activity-notes]');
  if (!entries) return;
  document.addEventListener('click', async event => {
    const link = event.target.closest('[data-mdo-more]');
    if (!link) return;
    event.preventDefault();
    if (link.dataset.busy) return;
    link.dataset.busy='1';
    try {
      const target = new URL(link.href);
      if (target.origin !== location.origin) return;
      const response = await fetch(target.href, {credentials:'same-origin'});
      if (!response.ok) throw Error('Request failed');
      const template = document.createElement('template');
      template.innerHTML = await response.text();
      const more = template.content.querySelector('[data-mdo-more]');
      if (more) more.remove();
      entries.append(template.content);
      if (more) link.replaceWith(more);
      else link.remove();
    } catch {
      link.removeAttribute('data-busy');
      link.textContent='Retry loading previous updates';
    }
  });
})();

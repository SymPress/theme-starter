export function setupNavigation(root = document) {
  const button = root.querySelector('[data-menu-toggle]');
  const nav = root.querySelector('#primary-navigation');
  if (!button || !nav) return;

  root.documentElement.classList.add('has-menu-js');
  const setOpen = (open) => {
    button.setAttribute('aria-expanded', String(open));
    nav.classList.toggle('is-open', open);
  };
  button.addEventListener('click', () => setOpen(button.getAttribute('aria-expanded') !== 'true'));
  root.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && button.getAttribute('aria-expanded') === 'true') {
      setOpen(false);
      button.focus();
    }
  });
  nav.addEventListener('click', (event) => {
    if (event.target.closest('a') && window.matchMedia('(max-width: 767px)').matches) {
      setOpen(false);
    }
  });
  window.matchMedia('(min-width: 768px)').addEventListener('change', () => setOpen(false));
}

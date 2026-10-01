# SymPress Starter Theme

This directory is an independent theme package. Keep changes inside this package
unless a cross-package change is explicitly part of the task.

- Read `README.md` and `docs/design/README.md` before changing runtime or visuals.
- The site owns Composer autoloading and the SymPress kernel. Never boot a kernel
  from theme functions.php or introduce a second Twig environment in production.
- Keep WordPress calls in `src/WordPress`, the explicit Twig bridge, and native
  entry points. Templates escape data; only WordPress-rendered HTML is trusted.
- Assets use Encore 7 ESM configuration, Tailwind 4 PostCSS, and SymPress Assets.
  Use prefixed handles and keep editor styles separate from frontend Preflight.
- Preserve WordPress content, password protection, comments, menus and pagination.
- Do not seed or activate the theme on existing sites without an explicit request.
- Run `npm run build` and `composer qa` for relevant changes. Browser-facing work
  requires `npm run test:browser` against the disposable fixture; see its README.
- Report package QA separately from site integration, browser and accessibility
  evidence. Automated accessibility checks do not replace screen-reader testing.
- Keep design tokens, theme.json, the translation catalog and documentation aligned.
- Do not edit generated `build`, `vendor`, `node_modules` or test-site core files.

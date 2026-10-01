# Screen design: SymPress Journal

Design first, implementation second. Open `index.html` directly in a browser.

The starter is an editorial WordPress theme, not a marketing page for a development
framework. Its visitors read articles, browse archives, search and navigate pages.
The reference uses clearly fictional example articles; the installed theme only
renders WordPress content. No seeded content or settings are installed automatically.

## Design contract

- Reference: the existing SymPress website's cobalt `#3858e9`, ink `#101517`,
  paper `#f6f7f7`, serif display and system sans typography.
- Asymmetric editorial opening, large serif title, a blue typographic cover,
  compact metadata, restrained rules and a two-column article section.
- Blue identifies links, selection and the sample cover. No gradients, social
  proof, invented statistics, rounded card grids or decorative motion.
- The cover is a typographic specimen, intentionally not a fake product screenshot
  or stock photo. Real posts use their own featured images; missing images do not
  trigger remote placeholder requests.
- Local system fonts, no remote font/CDN dependencies.
- Variance 6, motion 2, density 3. Hover/focus feedback only; reduced motion respected.
- Light appearance is deliberate. No automatic dark appearance that changes a
  visitor's authored content or editor colors.
- At 320px: single column, visible navigation without JavaScript; enhanced menu
  with a labelled button and Escape support when JavaScript is available.
- Search, no results, archives, singular content, password protection, comments,
  pagination, block editor styles and 404 belong to the implementation contract.

Skills applied: `design-taste-frontend` and `frontend-ui-engineering`.
The requested PHP/Twig/Encore stack takes precedence over React defaults.

## Sources

- https://github.com/roots/sage — functional inspiration (templates, assets, editor).
- https://roots.io/sage/ — Sage uses Blade and Vite; this theme uses Twig and Encore.
- https://github.com/SymPress/twig-bundle — Symfony-backed Twig integration.
- https://tailwindcss.com/docs/installation/using-postcss — Tailwind 4 PostCSS plugin.
- https://symfony.com/doc/current/frontend/encore/simple-example.html — Encore entries.

Consulted 2026-10-01. See `../verification.md` for executed checks and limitations.

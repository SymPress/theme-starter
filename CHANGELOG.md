# Changelog

## 0.2.0 — 2026-10-01

- Move generic rendering to Twig Bundle 1.1 and asset helpers to Assets 1.2.
- Adopt child-aware `@theme`, lazy Post models, menu/pagination objects and optional ACF metadata.
- Replace the theme-local composers and context hooks; see `UPGRADE-0.2.md`.
- Add real WordPress CI, deployment guidance and an explicit public API policy.

## 0.1.2 — 2026-10-01

- Use WordPress's native `theme.json` preset variables in frontend and editor
  styles. Replace the token generator with a Tailwind CSS alias map.
- Use `sympress/qa` 0.1.1 for PHP syntax checking instead of a theme-local script.
- Move asset and HTTP checks into `tests/`; expose HTTP checks as `npm run test:http`.
- Remove internal review reports, design drafts and obsolete helper scripts;
  streamline the README and keep browser screenshots in ignored test output.

## 0.1.1 — 2026-10-01

- Require stable Assets `^1.1` and use its public Encore `loadFromArray()` API.
  Remove the theme-local adapter that called the protected parser.
- Validate frontend and editor manifest entries independently, support nested
  build paths, and preserve frontend output when optional editor assets are broken.
- Honor WordPress excerpt filters and support public reusable blocks with cycle,
  depth and total-reference limits. Strip embedded shortcodes before excerpt
  filters run; keep metadata extraction free of rendering side effects.
- Resolve template candidates for the current query and support explicit
  templates/context for callers outside the normal WordPress template loader.
- Add PR/main CI for PHP QA, translations, dependency audits, production assets
  and lazy chunks. Verify both generated token files: `tokens.css` and
  `tailwind-tokens.css`.
- Align design tokens and translation extraction; generate tokens only during
  compilation, and preserve Composer's selected PHP executable for QA commands.

## 0.1.0

- Initial SymPress starter theme with Twig, Tailwind and Webpack Encore.

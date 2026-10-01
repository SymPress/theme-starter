# SymPress Starter Theme

A WordPress starter theme with **Symfony, Twig, Tailwind CSS 4 and Webpack Encore 7**.
It includes an editorial layout, block editor styles, template-specific context
composers and a Composer-managed build workflow.

Inspired by [Sage](https://github.com/roots/sage), built for the SymPress stack.

![SymPress Starter Theme](screenshot.png)

## Requirements

- PHP 8.5 and Composer 2.
- Node 22.18+, 24.11+ or 26+ within the ranges in `package.json`.
- WordPress 6.6+ for theme.json v3.
- A Composer-managed SymPress site whose MU plugin boots
  `SymPress\Kernel\Kernel\SiteKernel` before the theme loads.

The site owns autoloading, the kernel and the Twig environment. This theme is
installed through the site's Composer project, not as a standalone WordPress ZIP.
Runtime dependencies use stable releases: Assets `^1.1`, Kernel `^1.1` and
Twig Bundle `^1.0.2`.

## Installation

From your **site root**, clone the theme into a package directory and register it
as a Composer path repository:

```sh
git clone https://github.com/SymPress/theme-starter.git packages/theme-starter
composer config repositories.theme-starter path packages/theme-starter
composer require sympress/theme-starter:@dev
```

The site's `composer.json` must allow `composer/installers` and define the theme
installation path. Adapt this example to your WordPress directory layout:

```json
{
  "extra": {
    "installer-paths": {
      "public/wp-content/themes/{$name}/": ["type:wordpress-theme"]
    }
  },
  "config": {
    "allow-plugins": { "composer/installers": true }
  }
}
```

Build assets from the **theme source directory**:

```sh
cd packages/theme-starter
npm ci
npm run build
```

Activate **SymPress Starter** in Appearance → Themes, or run this from the site root:

```sh
wp --path=public/wp theme activate sympress-starter
```

For sites using `sympress/asset-compiler`, add `sympress/theme-starter` to the
root asset-compiler package allowlist. The theme already declares its build
scripts and source inputs in `extra.sympress.asset-compiler`.

## Development

```sh
npm run dev       # development build with source maps
npm run watch     # rebuild on changes
npm run build     # minified, content-hashed production assets
```

Tailwind scans Twig templates, block patterns and JavaScript. Use complete utility
class names rather than dynamically constructed fragments. Frontend Preflight
and editor styles are separate. Encore's runtime public path is `auto`, so lazy
chunks resolve from the script URL on nested routes. The relative manifest-path
warning is intentional; asset URLs are resolved against the active theme.

## Customization

| Concern | Source |
| --- | --- |
| Site title, description and front page | WordPress Settings |
| Menus | WordPress Appearance → Menus |
| Colors, typography and spacing | `theme.json` |
| Frontend styles | `resources/css/site.css`, `resources/css/app.css` |
| Editor styles | `resources/css/editor.css` |
| Twig templates | `resources/views/` |
| Template data | `src/WordPress/Context.php` |
| WordPress setup and assets | `src/WordPress/Theme.php` |
| Template selection | `src/View/TemplateResolver.php` |
| Example block pattern | `patterns/editorial-intro.php` |

Edit colors and fonts in `theme.json`. WordPress provides the corresponding
`--wp--preset--*` CSS variables in the frontend and editor; no token generator is
needed. `resources/css/tailwind-theme.css` maps those variables to Tailwind utility
names without duplicating their values. When adding a new preset, add an alias
there if you also want a named utility such as `bg-brand`.

Twig uses the `@StarterTheme` namespace and WordPress's native template hierarchy.
Add templates for categories, taxonomies, authors, dates, post types, attachments
or page slugs as needed. Custom page templates live in `resources/views/custom/`
and declare a `Template Name:` header inside a Twig comment. Native PHP templates
take precedence. A static front page displays the page's editor content by default.

Use `sympress_starter/context` to extend view data, or implement `ContextComposer`
as a service for template-specific data. Autoconfiguration tags the service;
`supports($template)` receives the resolved `@StarterTheme/*.html.twig` name.
The `sympress_starter/template_candidates` filter adjusts template selection.

Callers outside the WordPress template loader can pass explicit templates and data:

```php
$renderer->render(['partials/post-summary'], ['post' => $postViewData]);
```

The candidate filter also applies to explicit candidates. Query-based rendering
calls core template getters again during Twig fallback selection, so callbacks
on `{type}_template` should tolerate multiple calls. Explicit candidates avoid
that second hierarchy lookup.

Twig autoescaping is enabled. Only WordPress-rendered HTML is marked trusted.
Available helpers include `__`, `_x`, `_n`, `sprintf`, `menu` and the explicit
`esc_html`, `esc_attr`, `esc_url` filters. Theme strings use the `sympress-starter`
text domain and German source copy. See [translations](languages/README.md).

Card excerpts honor WordPress excerpt filters. Public reusable blocks use the
excerpt block allowlist, cycle detection, a maximum depth of 20 and a total limit
of 100 reusable references per excerpt. Additional references are omitted. Custom
dynamic blocks require `excerpt_allowed_blocks` opt-in; authored excerpts are
useful for complex layouts. Protected posts do not expose content or excerpts.

When renaming the theme, update its Composer name, installer name, kernel entry,
PHP namespace, text domain, `style.css` and asset-compiler allowlist together.
The kernel entry must match the installed theme directory. Rebuild the site's
autoload/container cache afterward. Child-theme discovery is not implemented.

## Testing

From the theme directory:

```sh
composer install
composer qa
php scripts/extract-translations.php --check
npm ci
npm run build
npx playwright install --with-deps chromium
npm run test:chunks
```

Composer QA includes syntax checks, Twig parsing, PHPCS, PHPStan and PHPUnit.
It uses the PHP executable that launches Composer. GitHub Actions also checks
translations, both generated token files and dependency audits on PRs and `main`.

The [disposable WordPress fixture](tests/site/README.md) provides integration and
browser tests. Run its WordPress checks and `npm run test:browser` sequentially
because they share a test database. Screenshots and traces go to ignored
`test-results/` directories. The fixture uses deliberately public test credentials
and must never be deployed to a live site.

## Deployment

Deploy the production `build/` directory alongside PHP, configuration,
`resources/views/`, `resources/css/`, translations, patterns, `style.css`,
`theme.json` and `screenshot.png`. The site manages Composer runtime dependencies.
Development dependencies, tests, caches and local environment files are not
deployment artifacts. Build output is ignored in Git and must be generated.

Missing or invalid manifests show an admin notice and a readable CSS fallback.
Frontend entries are validated independently of optional editor entries. Nested
build paths are supported; paths and symlinks resolving outside `build/` are
rejected.

Small production CSS files are inlined when they contain no relative URL or
import references. To keep all styles external, for example with a strict CSP:

```php
add_filter('sympress_starter/inline_styles', '__return_false');
```

Configure compression on the web server. Use long immutable cache lifetimes for
hashed assets, not for HTML, manifests or unhashed files.

The theme provides a plain-text meta-description fallback and stands down for
Yoast SEO, Rank Math, All in One SEO, SEOPress and The SEO Framework. Disable it
for another SEO integration with:

```php
add_filter('sympress_starter/meta_description_enabled', '__return_false');
```

Protected content, previews, search and 404 pages are excluded from the fallback.
Use `sympress_starter/meta_description` to customize its text.

## License

[GPL-2.0-or-later](LICENSE.md). See [CHANGELOG.md](CHANGELOG.md) for release changes.

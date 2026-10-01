# SymPress Starter Theme

An editorial WordPress starter theme built with **SymPress, Twig, Tailwind CSS 4
and Webpack Encore 7**. Start with a readable journal and adapt its templates,
services and design tokens to your project.

Sage is the functional reference. This is an original implementation using the
SymPress kernel and `sympress/twig-bundle`, with no Blade, Acorn or Vite dependency.

## Quickstart

For an existing SymPress site: add this directory as a Composer path repository,
require `sympress/theme-starter:@dev`, run `npm ci && npm run build` here, then
activate **SymPress Starter**. PHP QA is independent of an asset build:
`composer install && composer qa`. Full installation details follow below.

## Preview the design

Open [the screen design](docs/design/index.html) directly, or run:

```sh
npm ci
npm run preview
```

Visit `http://127.0.0.1:4178/docs/design/index.html`. The screen design is a static
reference with sample content. It was created before the WordPress implementation.
See the [design contract](docs/design/README.md), [desktop](docs/design/desktop.png)
and [mobile](docs/design/mobile.png) references. The installed theme reads your
WordPress site title, description, menus and posts; it does not insert demo content.

## Requirements

- PHP 8.5, Composer 2 and a Composer-managed SymPress WordPress site.
- Node 22.18+, 24.11+ or 26+ in the ranges supported by Encore 7 (see package.json).
- WordPress 6.6+ for theme.json v3; runtime tested on WordPress 7.1.2.
- The site's MU plugin must boot `SymPress\Kernel\Kernel\SiteKernel` before the
  theme loads. Use the existing SymPress site's bootstrap. The theme never boots
  a second kernel and is not a standalone WordPress ZIP installation.
- Production dependencies use stable releases: assets `^1.1`, kernel `^1.1`,
  twig-bundle `^1.0.2`. A consuming site maintains its own root lock.

## Install in a SymPress site

Keep this directory as your theme source, alongside your site repository, or move
it into the site's `packages/` directory. From the **site root**, add a path
repository pointing at that source, then require it:

```sh
composer config repositories.theme-starter path ../theme-starter
composer require sympress/theme-starter:@dev
```

For a source in `packages/theme-starter`, use that path instead. Root Composer
repository declarations are required; dependency repository declarations do not
propagate. `@dev` permits the local theme checkout; its production dependencies
do not require development stability.

The root Composer project must allow `composer/installers` and provide its normal
theme installer path, for example:

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

Merge those entries with your existing configuration. Then, in the **theme source**:

```sh
npm ci
npm run build
```

Activate **SymPress Starter** in Appearance → Themes, or use the site's WP-CLI:

```sh
wp --path=public/wp theme activate sympress-starter
```

The package's `extra.kernel` metadata makes the active theme discoverable. Its
bundle registers services and the `@StarterTheme` Twig namespace. Assets load
through `sympress/assets` from Encore's `build/entrypoints.json`. There is no
hardcoded `/wp-content` URL in the theme runtime.

Validated entries are passed to the public `EncoreEntrypointsLoader::loadFromArray()`
API, available since Assets 1.1.0. No theme-local parser subclass is needed.

For sites using `sympress/asset-compiler`, enable `sympress/theme-starter` in the
root asset-compiler package allowlist. The package already declares its build
scripts and source inputs in `extra.sympress.asset-compiler`.

## Development

```sh
npm run dev     # one development build with source maps
npm run watch   # rebuild when templates, CSS or JS change
npm run build   # minified, content-hashed production assets
```

Encore uses ESM configuration. Tailwind 4 runs through `@tailwindcss/postcss` and
scans Twig templates, block patterns and JavaScript. Use complete utility class
names; do not construct fragments dynamically. Tailwind Preflight is limited to
the frontend. The editor entry includes scoped content styles and utilities.

Encore 7 requires an explicit CSS minifier: the production build uses Lightning
CSS via `configureCssMinimizerPlugin`. JavaScript uses Encore's default minifier.

Encore's relative manifest-path warning is intentional: the SymPress asset loader
resolves entry URLs against the active theme directory. Webpack's runtime public
path is `auto`, so lazy chunks resolve from the script URL on nested routes. Watch rebuilds files; this
starter does not implement an HMR/dev-server proxy.

For package-level PHP work (a site's root vendor directory alone does not provide
the standalone test runner's autoload path):

```sh
composer install
npm ci && npm run build
composer qa
npm audit --audit-level=moderate
composer audit --locked
```

Composer's QA scripts use `@php`, so child tools inherit the PHP executable used
to start Composer. In images whose default PHP is 8.4, use
`php8.5 /usr/local/bin/composer qa`; there is no need to change the image's global
`php` alternative. Calling `vendor/bin/qa` directly still uses its PATH shebang.

The [integration fixture](tests/site/README.md) tests a separate WordPress database.
`npm run test:browser` requires that fixture running, or `THEME_TEST_URL` pointing
at an equivalent seeded **test** site. Never point the fixture setup at a live site.

GitHub Actions runs `composer qa`, the translation catalog check, a production
build, a generated-token consistency check, `test:chunks` in Chromium and dependency
audits on pull requests and pushes to `main`. Workflow linting uses SymPress's
shared workflow. The full WordPress/browser fixture remains a separate local
integration check; the CI chunk test does not boot WordPress.

## Make it yours

| Concern | Source |
| --- | --- |
| Site title, description, front page | WordPress Settings → General / Reading |
| Navigation | WordPress Appearance → Menus, primary and footer locations |
| Design tokens | `theme.json` → generated `resources/css/tokens.css` |
| Frontend layout | `resources/css/site.css` |
| Tailwind entry and theme tokens | `resources/css/app.css` |
| Editor palette, typography and widths | `theme.json`, `resources/css/editor.css` |
| HTML layout and reusable partials | `resources/views/` |
| WordPress view data | `src/WordPress/Context.php` |
| WordPress setup and assets | `src/WordPress/Theme.php` |
| Twig selection | `src/View/TemplateResolver.php` |
| Optional editor pattern | `patterns/editorial-intro.php` |

Edit palette/font values in `theme.json`; the compiler's `beforeCompile` hook
regenerates shared CSS tokens and watches `theme.json`. Merely importing the
Webpack configuration does not write files. `npm run tokens` generates them
explicitly without compiling assets. Font variable names follow their preset
slugs (`display` becomes `--font-display`). Visible theme strings use the
`sympress-starter` text domain, with German source copy. WordPress dates, standard
comment fields and admin text follow the site's locale. Run `composer i18n` to
extract PHP and Twig messages into the POT automatically. PHP extraction selects
the `sympress-starter` text domain; Twig helpers bind that domain themselves.
Printf placeholders receive `php-format` flags. Check the committed catalog with
`php scripts/extract-translations.php --check`.

The native WordPress PHP template hierarchy remains intact: plugin template
overrides can continue to work. `index.php` delegates to Twig when WordPress reaches
the theme fallback. Twig selection observes WordPress's `*_template_hierarchy`
filters: taxonomy/category/tag, author, date, post-type archives, attachments,
page slugs/IDs and custom page templates follow core ordering. Add optional
templates as needed; no `front-page.html.twig` is shipped,
so a static homepage displays the page's editor content.

Candidates are collected for the current query on each render, with temporary
hooks removed afterwards, including on exceptions. REST/AJAX/CLI callers that
already have view data can bypass the WordPress main loop explicitly:

```php
$renderer->render(['partials/post-summary'], ['post' => $postViewData]);
```

The `sympress_starter/template_candidates` filter also applies to explicitly
supplied candidates. Query-based rendering calls core template getters again
after WordPress's template loader; callbacks on `{type}_template` should tolerate
multiple calls. Explicit candidates avoid that second hierarchy lookup.

Card excerpts use native `get_the_excerpt`, `excerpt_length` and `excerpt_more`
filters. Public reusable blocks are expanded through WordPress's excerpt block
allowlist with cycle protection, a maximum nesting depth of 20 and a total budget
of 100 reusable references per excerpt. Further references are omitted; an
authored excerpt avoids truncation for unusually large reusable layouts.
A custom dynamic block must be opted in
via `excerpt_allowed_blocks`; its renderer must not recursively request excerpts.
Singular pages do not compute unused card excerpts. Metadata continues to use
stored plain text and never renders blocks or shortcodes.

Use `sympress_starter/template_candidates` to add specific archive/page templates,
or `sympress_starter/context` to add view data. Implement `ContextComposer` as a
service for template-specific data; autoconfiguration tags it automatically.
Its `supports($template)` receives the resolved `@StarterTheme/*.html.twig` name.
Put editor-selectable Twig page templates in `resources/views/custom/` and add a
Twig comment containing a `Template Name: Landing page` header. Native PHP
templates retain precedence. Symfony also supports site template
overrides under `templates/bundles/StarterThemeBundle/`. This starter is intended
to be copied and customized; child-theme discovery is not implemented.

Twig autoescaping stays enabled. The WordPress adapter explicitly marks rendered
blocks, menus, thumbnails and pagination through the central `Content::html()`
boundary. Native hook functions declare Twig `is_safe`. The theme's WordPress
extension also provides `_x`, `_n`, `sprintf`, `menu` and explicit `esc_html`,
`esc_attr`, `esc_url` filters. It remains theme-local because its text domain is
theme-specific; the generic Twig bundle does not gain a WordPress dependency.
Do not mark user input safe or add generic “call any PHP function” Twig helpers.
Password-protected posts omit content, excerpts, images and comments until access
is granted through WordPress.

When renaming the package, update Composer name, installer-name, kernel entry,
PHP namespace, text domain, style.css and asset-compiler allowlist together. The
kernel entry should match the installed theme directory. Rebuild the site
autoload/container cache using your site's normal commands.

## Release

Build in CI and deploy `build/` alongside PHP, `resources/views/`, `resources/css/`, `style.css`, `theme.json`,
patterns and `screenshot.png`. Keep the site's Composer vendor dependencies in its
normal deployment. Do not ship node_modules, tests, docs, caches or local secrets.
Build output is ignored in source control and must be included in release artifacts.
Missing or invalid asset manifests show an admin notice and a readable CSS fallback.
The frontend entry is validated independently of the optional editor entry.
Build assets may use relative subdirectories such as `js/` and `css/`; paths
outside `build/` are rejected, including symlinks resolving outside that directory.
Deploy actual files within `build/` or use symlinks whose targets remain inside it.
A broken editor entry does not disable frontend
assets. Script-only frontend entries are supported.

Configure gzip/Brotli on the hosting webserver for HTML, CSS and JavaScript. Give
content-hashed build assets `Cache-Control: public, max-age=31536000, immutable`;
do not apply that rule to HTML, manifests or unhashed files. The local fixture
emulates compression and hashed-asset caching in its router. The theme itself
does not control HTTP delivery.

Small production CSS files (up to 16 KiB each) are inlined in the head through
SymPress Assets, eliminating their separate render-blocking network request.
Only the frontend app entry is eligible; editor styles stay separate. Development
files, larger CSS files and files containing `url()` or `@import` stay external,
preserving asset-relative URLs. SymPress also checks local paths and file access.
The entire small stylesheet is included, so rendering works without JavaScript
and there is no asynchronously styled intermediate state.

Inlining favors the first visit: CSS is transferred with each HTML response and
cannot be cached independently. To favor navigation with a warm asset cache, or
when your CSP does not permit inline styles, retain external CSS instead:

```php
add_filter('sympress_starter/inline_styles', '__return_false');
```

The theme supplies a basic meta-description fallback: the site tagline for a
posts homepage, the stored post/page excerpt (or plain text from stored content) for
singular pages and a separate posts page, and the term description for taxonomy
archives. A static front page uses its own excerpt. Maintain these texts in
WordPress; empty sources do not produce an empty tag. Descriptions are plain text,
escaped for HTML and limited to 30 words as a theme default. Extraction never
runs content filters, block renderers or shortcode callbacks.

Protected content, previews, search and 404 pages are excluded. The fallback
stands down when Yoast SEO, Rank Math, All in One SEO, SEOPress or The SEO Framework
defines its plugin-version constant. For another SEO integration, disable it:

```php
add_filter('sympress_starter/meta_description_enabled', '__return_false');
```

Use `sympress_starter/meta_description` to override the description for the current
page. A dedicated SEO plugin can provide more detailed editorial controls; when
one is detected, it owns the tag and must be configured to output its description.

See [verification.md](docs/verification.md) for evidence and untested boundaries.
License: GPL-2.0-or-later; see [LICENSE.md](LICENSE.md).

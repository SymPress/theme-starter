# Upgrade from 0.1.x to 0.2 / 1.0

Update the site's lock with Twig Bundle >=1.1.1 and Assets >=1.2.0, then rebuild
assets and the production container. Remove development path repositories for
those dependencies. The theme no longer implements generic WordPress rendering.

| Before | After |
| --- | --- |
| `@StarterTheme` | `@theme` (child before parent) |
| Theme-local `ContextComposer::supports()/compose()` | `TemplateComposerInterface::compose(TemplateContext): array` |
| Resolved path matching in `supports()` | `#[AsTemplateComposer(templates: ['page-*'], priority: 10)]` |
| `sympress_starter/context` | `sympress/twig/context` |
| `sympress_starter/template_candidates` | `sympress/twig/template_candidates` |
| Eager post arrays | Lazy Post objects |
| Menu/pagination HTML strings | Menu/Pagination objects with Twig partials |
| Theme-local renderer | `SymPress\TwigBundle\WordPress\ThemeRenderer` |
| `index.php` rendering fallback | Bundle `template_include`; index is a 503 guard |

Composer patterns match hierarchy names such as `page-contact`, not namespaced
Twig filenames. Register composer services with autoconfiguration. The context
filter receives the data array and resolved Twig filename. If needed during a
staged migration, set `sympress_twig.wordpress.hook_prefix: sympress_starter`;
this restores hook names only, not old array/composer contracts.

Iterate `posts` directly so native WordPress loop globals remain valid. Replace
eager `first`/`slice` layouts with `loop.first`. Use `post.content`,
`post.excerpt(30)`, `post.thumbnail('large', {class: 'cover'})`, `post.categories`
and `post.meta('key')`. The default metadata resolver optionally formats ACF
image, relation and date fields when ACF is active.

The bundle provides `@wordpress/document.html.twig`; extend it through the theme
layout. Child themes override matching files in `resources/views/`; an
unregistered child inherits the registered parent's configuration. Editor custom
templates declare `Template Name` and optional `Template Post Type` headers.

Version 0.1.2 already removed generated `tokens.css` and `tailwind-tokens.css`.
Presets now come directly from `theme.json`, with utility aliases in
`resources/css/tailwind-theme.css`. Do not restore the removed token generator.

The three theme-specific metadata/inline-style hooks retain their names.
See [deployment](docs/deployment.md) and the
[stable API policy](docs/stability.md). The 1.0 release preserves the 0.2 contract.

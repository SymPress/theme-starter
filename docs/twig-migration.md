# Twig integration migration (0.2)

The theme now owns setup, entry names, templates and its metadata fallback.
Generic template discovery, context, models, HTML helpers and composers belong
to Twig Bundle. Generic manifest validation and small-style configuration belong
to Assets. Historical review reports describe the 0.1 implementation.

| 0.1 API | 0.2 API |
| --- | --- |
| `@StarterTheme` | `@theme` (child before parent) |
| `ContextComposer` | `TemplateComposerInterface` and `AsTemplateComposer` |
| `sympress_starter/context` | `sympress/twig/context` |
| `sympress_starter/template_candidates` | `sympress/twig/template_candidates` |
| `post.image` | `post.thumbnail('large', {class: 'featured-image'})` |
| `post.category` | `post.categories\|first.name\|default('')` |
| `post.show_meta` | `post.type == 'post'` |
| `post.pages` | `wp_link_pages()` |
| `comments()` | `comments_template()` |
| menu HTML in context | `menu(location).items`, rendered by the menu partial |
| pagination HTML in context | `pagination().pages`, rendered by the pagination partial |
| `year` | `'now'\|date('Y')` |

No compatibility namespace is retained in this 0.x change. `single`, `page` and
`archive` forwarding templates are removed: hierarchy fallback reaches `singular`
or `index`. Theme assets, metadata filter names and navigation locations remain
theme-owned. Add `Template Post Type` for non-page custom templates.

The bundle registration uses the actual installed slug `sympress-starter`.
When renaming a theme, update that slug in `StarterThemeBundle`, the Composer
installer name, WordPress headers and the theme's textdomain as appropriate.
The `sympress-twig` theme support is a capability declaration; registration in the
container remains required.

## Stable releases and verification

Twig Bundle 1.1.0 and Assets 1.2.0 provide the shared APIs. The theme and disposable
fixture use these published versions; development sibling aliases are not part
of the release configuration. See [UPGRADE-0.2.md](../UPGRADE-0.2.md) and
[deployment](deployment.md) for migration and production cache handling.

The fixture provides runtime, metadata, regression, scoped-loop and admin checks;
the shared DDEV workflow also runs browser tests and production asset compilation.
Fragment caching, makers and profiler tooling remain optional follow-up work.
ACF contract tests simulate its public API; they do not certify every ACF version
or recursively convert nested field structures.

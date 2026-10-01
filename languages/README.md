# Translation

The text domain is `sympress-starter`. Frontend source strings are German; technical
admin notices are English. `sympress-starter.pot` includes PHP **and Twig** strings.

Create a locale PO/MO pair from this catalog, for example `en_US.po` and `en_US.mo`,
using a gettext editor. WordPress loads it from this directory through
`load_theme_textdomain`. Keep the catalog updated when changing either PHP or Twig;
WordPress's standard PHP extractor alone does not cover these Twig templates.

Dates, native comments and WordPress admin UI use the site's selected locale.

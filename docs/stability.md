# Public API and release policy

The 1.0 theme contract freezes the interfaces introduced in 0.2. The shared Twig
layer follows its own [1.x API policy](https://github.com/SymPress/twig-bundle/blob/main/docs/public-api.md).
Menu and pagination are objects; optional ACF support remains in Twig Bundle.

Supported theme extension points are the `primary`/`footer` menu locations,
`sympress_starter/inline_styles`, `sympress_starter/meta_description_enabled`
and `sympress_starter/meta_description` filters, the `sympress-starter` textdomain,
the registered theme slug, `@theme` child override convention and the
`head`/`body`/`content` layout blocks. Twig view names and theme.json preset
slugs shipped in 1.0 remain available through the major version.

PHP setup methods, Encore filenames/hashes, internal CSS selectors and exact
visual layout are implementation details. Copying and customizing the starter
creates a downstream theme whose compatibility is owned by its maintainer.

Patch releases fix defects; minor releases may add compatible functionality.
Removing a supported interface requires a major release. Deprecations document
a replacement in the changelog/upgrade guide, remain through the current major
and are removed no earlier than the next major. Security restrictions may reject
unsafe input and will be documented.

Required release gates are package QA, locked stable dependencies, production
asset compilation, real WordPress integration and browser tests in CI. Automated
accessibility checks do not certify manual screen-reader usability. Optional
English translations and manual screen-reader testing are tracked separately.

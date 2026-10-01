# Production deployment

The official production path is a Composer-managed SymPress site using
`sympress/asset-compiler`. GitHub source archives do not include `build/` and
must not be uploaded as standalone WordPress theme ZIPs. Local `npm run build`
is a development/verification command; production compilation belongs to the
site's release pipeline.

Require `sympress/asset-compiler:^1.0` in the site and allow that Composer plugin.
Select the theme explicitly in the root configuration:

```json
{
  "config": { "allow-plugins": { "sympress/asset-compiler": true } },
  "extra": {
    "sympress.asset-compiler": {
      "auto-discover": false,
      "packages": { "sympress/theme-starter": true }
    }
  }
}
```

In an isolated release directory:

```sh
composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader
composer compile-assets --mode production --no-dev --packages sympress/theme-starter
```

Use the supported Node/npm toolchain and retain `build/` in the resulting site
artifact. The theme already declares build scripts and source inputs in
`extra.sympress.asset-compiler`. Fail deployment if compilation fails. Deploy PHP,
Twig views, source fallback CSS, translations, patterns, theme.json, style.css,
screenshot.png and built assets together. Exclude tests, local configuration,
development dependencies and node_modules from the runtime artifact.

## Container and Twig caches

Compile under the site's actual production WordPress configuration.
`WP_DEBUG` influences Twig `auto_reload` at container compilation time; a change
requires a rebuild. Never ship a development container.

Use a new `SYMPRESS_KERNEL_BUILD_ID` per deployment, or remove the new release's
`var/cache/<environment>/kernel` before booting. If reusing a release directory,
also clear the directory configured by `twig.cache`. Keep caches release-local
and writable by the runtime user. Do not clear a live shared directory while
requests are writing it.

Boot WordPress through the production MU plugin, then warm actual frontend
requests for home, a post, an archive and a custom page template. Confirm HTTP
200 and working CSS/JS. An admin/CLI-only boot does not warm the active frontend
Twig runtime. Switch traffic atomically after checks; retain the prior release
and caches for rollback. No standalone Symfony console command is assumed.

The index fallback returns HTTP 503 if the Twig WordPress bridge was not
activated, `template_include` was disabled/removed, or no matching Twig view
(including index) exists. It never silently produces an empty successful page.
Verify the MU kernel bootstrap, theme slug registration, bundle version/config
and deployed view files. The public error reveals no filesystem paths.

Serve hashed assets with immutable caching and compression; avoid immutable
caching for HTML and manifest files.

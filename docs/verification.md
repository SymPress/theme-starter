# Verification — 2026-10-01

The theme was built and exercised in a new `theme-starter/` directory. Existing
`starter/`, `demo/`, `twig-bundle/`, `assets/` and website source files were not
modified by this task. No GitHub repository, release or production activation was
created.

## Executed checks

| Check | Result |
| --- | --- |
| Composer install from public package metadata | Passed; root lock recorded |
| Composer validate --strict | Passed |
| PHP syntax | 18 source/test PHP files passed |
| Twig parse | 14 templates passed |
| Template/escaping/asset-loader checks | 48 assertions passed |
| Encore development build | Passed, source maps generated |
| Encore production build | Passed, content-hashed output generated |
| npm audit --audit-level=moderate | 0 vulnerabilities reported |
| composer audit --locked | No advisories or abandoned packages reported |
| Real SymPress container | Theme renderer and Twig namespace resolved |
| Static front page | Real WordPress query selected the page Twig template |
| Editor registration | Compiled editor CSS, featured-image support and pattern registered |
| Playwright on real WordPress | 10 tests passed |
| Automated axe checks | No violations in the tested homepage sizes, single post, search-empty and 404 states |
| Visual inspection | Design desktop/mobile, rendered theme desktop/mobile and block editor inspected |

The real WordPress tests cover homepage layout at 320, 768, 1024 and 1440px;
horizontal overflow; actual loaded CSS/JS; keyboard navigation and Escape;
navigation without JavaScript; search submission and escaped search input;
single post, page, archive and pagination; password-protected content/excerpts;
HTTP 404 status and search recovery; and editor canvas colors without loading
frontend CSS into the editor chrome.

## Environment and artifacts

- PHP 8.5.9 in a local DDEV image, WordPress 7.1.2, MariaDB 11.8.
- Node 24.20.0, Encore 7.2.0, Tailwind 4.3.3, Playwright 1.63.0, Chromium.
- SymPress dependencies: `assets` 9224102, `kernel` c242730,
  `framework-bundle` 1aedbaf, `twig-bundle` 7ab3a13; full references in composer.lock.
- Production frontend: approximately 15.6 KiB CSS + 761 bytes JavaScript,
  excluding WordPress core/plugin output. Editor CSS: approximately 1.18 KiB.
- Screen design: [desktop](design/desktop.png), [mobile](design/mobile.png).
- Rendered theme: [1440px](design/theme-1440.png), [320px](design/theme-320.png).
- Editor: [editor.png](design/editor.png).
- WordPress theme thumbnail: [screenshot.png](../screenshot.png), 1200 × 900.

The screen design was made before the PHP theme. Its editorial sample copy is
deliberately separate from the installed theme's real WordPress content. The
fixture initially used en_US and was subsequently switched to de_DE for the
final screenshots and browser run.

## Notes and boundaries

- Encore reports its generic relative-public-path warning. Relative entries are
  intentional for SymPress's URL-aware asset loader and theme relocation. Real
  browser asset requests passed; no theme folder URL is hardcoded in production.
- The local WP-CLI PHAR emits PHP 8.5 deprecations from its own bundled libraries.
  These are not theme errors. The fixture originally booted framework services
  during WordPress installation; its bootstrap now skips `wp_installing()`.
- A first browser run found missing assets caused by PHP's built-in test router
  reporting `/` as SCRIPT_NAME. The fixture now models `/index.php` correctly;
  the application asset loader was not bypassed or replaced.
- Minimum declared WordPress version 6.6 was not separately exercised. Safari,
  Firefox, real mobile devices, WooCommerce, multisite, child themes and production
  deployment were not tested. Child-theme discovery is not part of this starter.
- Axe and keyboard tests are useful evidence, not a complete accessibility
  certification. No human screen-reader review or user acceptance test was run.
- Composer QA here means validation, PHP syntax, Twig parsing and the 48
  assertions. No PHPStan/PHPCS suite or full SymPress workspace QA is claimed.
- The test fixture uses its own database and local-only test credentials. It is
  reproducible using [its setup instructions](../tests/site/README.md). Its
  containers are stopped after verification; generated data remains local.
- Build artifacts are available locally but ignored by Git; release/deployment
  packaging must include `build/`. No production-ready ZIP was published.

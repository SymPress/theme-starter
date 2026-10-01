# Upgrade to Theme Starter 1.1

Update Composer dependencies and commit the resulting lock. This release requires
Twig Bundle 1.2 or newer and retains Assets 1.2. Build assets through the site's
official asset-compiler path and rebuild container/Twig caches before switching
traffic, following the deployment steps in the README.

The home template now renders the featured post and grid in two passes over the
repeatable PostCollection. Child themes can retain their overrides or adopt the
new complete section wrappers. Twig lint compiles each template and rejects
unknown helpers as well as forbidden raw output.

Twig Bundle's author context is now a public profile, with `id`, `name`, `slug`,
`url`, `description` and `avatar`. The safe aliases `ID`, `display_name` and
`user_nicename` remain. Private WP_User fields such as email and password hashes
are unavailable; intentionally authorized account data belongs in a PHP composer.
`post.author` and `post.terms(taxonomy)` are lazy and memoized. Readonly post/term
models can receive container services after their native-object constructor argument.

WordPress helpers are available in regular admin rendering, while frontend theme
paths and interception stay isolated. Related-post loops retain the main query.
Only renderCurrent() appends page debug comments. Object menus run native menu
and title filters and add description/attr_title. The raw-filter lint is not a
sandbox: action() still dispatches registered WordPress handlers from trusted
application templates.

Use escaping filters such as `|esc_url`; the broken `e('esc_url')` strategies were
withdrawn in Twig Bundle 1.1.2. Nullable filters accept missing metadata, wpautop
escapes untrusted strings, and shortcode output is sanitized. Prefer `|wp_date`;
DateInterval and timezone:false now preserve Twig-compatible cases.

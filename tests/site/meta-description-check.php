<?php

declare(strict_types=1);

use SymPress\StarterTheme\WordPress\Theme;

if (wp_get_environment_type() !== 'local' || DB_NAME !== 'theme_test') {
    throw new RuntimeException('This check is restricted to the theme_test fixture.');
}

$checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
    ++$checks;
    if (!$condition) {
        throw new RuntimeException($message);
    }
};
$render = static function (): string {
    ob_start();
    (new Theme())->metaDescription();
    return (string) ob_get_clean();
};
$originalQuery = $GLOBALS['wp_query'];
$page = get_page_by_path('ueber');
$assert($page instanceof WP_Post, 'Fixture page exists.');

try {
    $GLOBALS['wp_query'] = new WP_Query(['page_id' => $page->ID]);
    $pageDescription = $render();
    $assert(str_contains($pageDescription, '<meta name="description"'), 'Page receives a description.');

    $frontMode = static fn () => 'page';
    $pageId = static fn () => $page->ID;
    add_filter('pre_option_show_on_front', $frontMode);
    add_filter('pre_option_page_on_front', $pageId);
    $assert(is_front_page(), 'Static front-page query is active without changing persisted options.');
    $assert($render() === $pageDescription, 'Static front page keeps its own description.');
    remove_filter('pre_option_page_on_front', $pageId);
    remove_filter('pre_option_show_on_front', $frontMode);

    $GLOBALS['wp_query'] = new WP_Query();
    $postsMode = static fn () => 'posts';
    add_filter('pre_option_show_on_front', $postsMode);
    add_filter('pre_option_blogdescription', '__return_empty_string');
    $GLOBALS['wp_query']->is_home = true;
    $assert(str_contains($render(), esc_attr(wp_get_document_title())), 'An empty home tagline falls back to the document title.');
    add_filter('sympress_starter/meta_description', '__return_empty_string');
    $assert($render() === '', 'An explicit empty override also suppresses the title fallback.');
    remove_filter('sympress_starter/meta_description', '__return_empty_string');
    remove_filter('pre_option_show_on_front', $postsMode);
    remove_filter('pre_option_blogdescription', '__return_empty_string');
    $GLOBALS['wp_query'] = new WP_Query(['page_id' => $page->ID]);

    $override = static fn () => '<p>Eine "Beschreibung" &amp; mehr.</p><script>BAD_SCRIPT</script>';
    add_filter('sympress_starter/meta_description', $override);
    $html = $render();
    $assert(str_contains($html, '&quot;Beschreibung&quot; &amp; mehr.'), 'Override is attribute-escaped.');
    $assert(!str_contains($html, 'BAD_SCRIPT') && !str_contains($html, '<p>'), 'HTML and scripts are removed.');
    remove_filter('sympress_starter/meta_description', $override);

    add_filter('sympress_starter/meta_description', '__return_empty_string');
    $assert($render() === '', 'Empty description is omitted.');
    remove_filter('sympress_starter/meta_description', '__return_empty_string');

    add_filter('sympress_starter/meta_description_enabled', '__return_false');
    $assert($render() === '', 'Custom SEO integration can disable the fallback.');
    remove_filter('sympress_starter/meta_description_enabled', '__return_false');

    $protected = get_page_by_path('geschuetzt', OBJECT, 'post');
    $assert($protected instanceof WP_Post, 'Protected fixture exists.');
    $GLOBALS['wp_query'] = new WP_Query(['p' => $protected->ID]);
    add_filter('sympress_starter/meta_description', $override);
    $assert($render() === '', 'Protected posts bypass even the custom description filter.');
    remove_filter('sympress_starter/meta_description', $override);

    // Simulate plugin detection for this process only; do not install or activate
    // a provider or claim that its own metadata output has been integration-tested.
    $GLOBALS['wp_query'] = new WP_Query(['page_id' => $page->ID]);
    define('WPSEO_VERSION', 'fixture-check');
    $assert($render() === '', 'A detected SEO provider owns description output.');
    echo "Passed {$checks} metadata integration assertions.\n";
} finally {
    $GLOBALS['wp_query'] = $originalQuery;
}

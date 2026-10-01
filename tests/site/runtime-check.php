<?php

declare(strict_types=1);

use SymPress\Kernel\App;
use SymPress\TwigBundle\WordPress\ThemeRenderer;
use SymPress\TwigBundle\WordPress\TemplateHierarchy;
use SymPress\TwigBundle\Renderer\TemplateRendererInterface;

if (wp_get_environment_type() !== 'local' || DB_NAME !== 'theme_test') {
    throw new RuntimeException('This check is restricted to the theme_test fixture.');
}

$assert = static function (bool $condition, string $message): void {
    if (!$condition) {
        throw new RuntimeException($message);
    }
};
$assert(App::make(ThemeRenderer::class) instanceof ThemeRenderer, 'Theme renderer must be public in the compiled site container.');
$templates = App::make(TemplateRendererInterface::class);
$assert($templates instanceof TemplateRendererInterface, 'Use the real SymPress Twig renderer.');
$assert($templates->exists('@theme/home.html.twig'), 'Theme namespace must be registered by the bundle.');
$assert(current_theme_supports('editor-styles'), 'Editor styles must be enabled.');
$assert(current_theme_supports('post-thumbnails'), 'Featured images must be enabled.');
$assert(isset($GLOBALS['editor_styles'][0]) && str_contains($GLOBALS['editor_styles'][0], 'build/sympress-starter-editor.'), 'Compiled editor stylesheet must be registered.');
$assert(WP_Block_Patterns_Registry::get_instance()->is_registered('sympress-starter/editorial-intro'), 'Editorial pattern must be registered.');

$options = [];
foreach (['show_on_front', 'page_on_front', 'page_for_posts'] as $option) {
    $options[$option] = get_option($option);
}
$page = get_page_by_path('ueber');
$assert($page instanceof WP_Post, 'The test page must exist.');
try {
    update_option('show_on_front', 'page');
    update_option('page_on_front', $page->ID);
    update_option('page_for_posts', 0);
    $GLOBALS['wp_query'] = new WP_Query(['page_id' => $page->ID]);
    $GLOBALS['wp_the_query'] = $GLOBALS['wp_query'];
    $resolver = new TemplateHierarchy();
    get_front_page_template();
    get_page_template();
    get_singular_template();
    $assert(App::make(ThemeRenderer::class)->resolve($resolver->forQuery($GLOBALS['wp_query'])) === '@theme/singular.html.twig', 'Static front page must use page content.');
    echo "PASS: compiled container, real Twig renderer, namespace, editor styles, pattern and static homepage.\n";
} finally {
    foreach ($options as $key => $value) {
        update_option($key, $value);
    }
}

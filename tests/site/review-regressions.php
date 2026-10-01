<?php

declare(strict_types=1);

use SymPress\Kernel\App;
use SymPress\StarterTheme\View\TemplateResolver;
use SymPress\StarterTheme\WordPress\Context;
use SymPress\StarterTheme\WordPress\Theme;
use SymPress\TwigBundle\Renderer\TemplateRendererInterface;

if (wp_get_environment_type() !== 'local' || DB_NAME !== 'theme_test') {
    throw new RuntimeException('Restricted to the disposable theme_test fixture.');
}
$checks = 0;
$assert = static function (bool $condition, string $message) use (&$checks): void {
    ++$checks;
    if (!$condition) {
        throw new RuntimeException($message);
    }
};
$templates = App::make(TemplateRendererInterface::class);
$originalQuery = $GLOBALS['wp_query'];
$originalMain = $GLOBALS['wp_the_query'];
$originalPost = $GLOBALS['post'] ?? null;
$postId = 0;
$shortcodes = 0;
$blocks = 0;
$extraPosts = [];
$terms = [];
try {
    add_shortcode('review_count', static function () use (&$shortcodes): string {
        ++$shortcodes;
        return '<strong>Shortcode output</strong>';
    });
    register_block_type('review/count', ['render_callback' => static function () use (&$blocks): string {
        ++$blocks;
        return '<p>Dynamic block output</p>';
    }]);
    $postId = wp_insert_post([
        'post_type' => 'page', 'post_status' => 'publish', 'post_title' => 'Review regression fixture',
        'post_name' => 'review-regression-fixture',
        'post_content' => '<p>Stored description &amp; text.</p>[review_count]<!-- wp:review/count /-->',
    ], true);
    if (is_wp_error($postId)) {
        throw new RuntimeException($postId->get_error_message());
    }
    update_post_meta($postId, '_wp_page_template', 'custom/landing.html.twig');
    $GLOBALS['wp_query'] = $GLOBALS['wp_the_query'] = new WP_Query(['page_id' => $postId]);
    $GLOBALS['post'] = get_post($postId);
    $resolver = new TemplateResolver($templates);
    $resolver->register();
    get_page_template();
    get_singular_template();
    $assert($resolver->candidates() === ['custom/landing', 'page-review-regression-fixture', 'page-' . $postId, 'page', 'singular'], 'Native page hierarchy: ' . json_encode($resolver->candidates()));
    $assert($resolver->resolve($resolver->candidates()) === '@StarterTheme/custom/landing.html.twig', 'Custom Twig page selected.');
    $assert(isset(wp_get_theme()->get_page_templates()['custom/landing.html.twig']), 'Custom Twig template appears in the editor.');
    ob_start();
    (new Theme())->metaDescription();
    $metadata = ob_get_clean();
    $assert($shortcodes === 0 && $blocks === 0, 'Metadata must not render shortcodes or blocks.');
    $assert(str_contains($metadata, 'Stored description &amp; text.'), 'Plain stored metadata is escaped once.');
    $context = (new Context())->build('@StarterTheme/custom/landing.html.twig');
    $assert($shortcodes === 1 && $blocks === 1, 'Body renders shortcode and dynamic block exactly once.');
    $assert(str_contains((string) $context['posts'][0]['content'], 'Dynamic block output'), 'Rendered block reaches Twig.');

    $cases = [
        [['cat' => 1], 'get_category_template', 'category-1'],
        [['author' => 1], 'get_author_template', 'author-1'],
        [['year' => 2026, 'monthnum' => 10], 'get_date_template', 'date'],
        [['s' => 'Notizbuch'], 'get_search_template', 'search'],
    ];
    foreach ($cases as [$query, $getter, $expected]) {
        $GLOBALS['wp_query'] = $GLOBALS['wp_the_query'] = new WP_Query($query);
        $resolver = new TemplateResolver($templates);
        $resolver->register();
        $getter();
        $assert(in_array($expected, $resolver->candidates(), true), 'Core hierarchy captured: ' . $expected);
    }
    register_post_type('review_book', ['public' => true, 'has_archive' => true]);
    register_taxonomy('review_genre', 'review_book', ['public' => true]);
    foreach (['post_tag', 'review_genre'] as $taxonomy) {
        $term = wp_insert_term('Review ' . wp_generate_uuid4(), $taxonomy);
        if (is_wp_error($term)) {
            throw new RuntimeException($term->get_error_message());
        }
        $terms[] = [$term['term_id'], $taxonomy];
        $GLOBALS['wp_query'] = $GLOBALS['wp_the_query'] = new WP_Query([
            'tax_query' => [['taxonomy' => $taxonomy, 'field' => 'term_id', 'terms' => [$term['term_id']]]],
        ]);
        $resolver = new TemplateResolver($templates);
        $resolver->register();
        $taxonomy === 'post_tag' ? get_tag_template() : get_taxonomy_template();
        $expected = $taxonomy === 'post_tag' ? 'tag-' . $term['term_id'] : 'taxonomy-review_genre';
        $assert(in_array($expected, $resolver->candidates(), true), 'Native term hierarchy: ' . $expected);
    }
    $GLOBALS['wp_query'] = $GLOBALS['wp_the_query'] = new WP_Query(['post_type' => 'review_book']);
    $resolver = new TemplateResolver($templates);
    $resolver->register();
    get_archive_template();
    $assert($resolver->candidates() === ['archive-review_book', 'archive'], 'Custom post-type archive hierarchy.');
    $attachment = wp_insert_attachment(['post_title' => 'Review image', 'post_mime_type' => 'image/png']);
    $extraPosts[] = $attachment;
    $GLOBALS['post'] = get_post($attachment);
    $GLOBALS['wp_query'] = $GLOBALS['wp_the_query'] = new WP_Query(['attachment_id' => $attachment]);
    $resolver = new TemplateResolver($templates);
    $resolver->register();
    get_attachment_template();
    $assert(in_array('image-png', $resolver->candidates(), true) && in_array('attachment', $resolver->candidates(), true), 'Attachment MIME hierarchy.');
    echo "PASS: {$checks} native WordPress review regression checks.\n";
} finally {
    foreach ($extraPosts as $id) {
        wp_delete_attachment($id, true);
    }
    foreach ($terms as [$id, $taxonomy]) {
        wp_delete_term($id, $taxonomy);
    }
    unregister_taxonomy('review_genre');
    unregister_post_type('review_book');
    if (is_int($postId) && $postId > 0) {
        wp_delete_post($postId, true);
    }
    remove_shortcode('review_count');
    unregister_block_type('review/count');
    $GLOBALS['wp_query'] = $originalQuery;
    $GLOBALS['wp_the_query'] = $originalMain;
    $GLOBALS['post'] = $originalPost;
}

<?php

declare(strict_types=1);

use SymPress\Kernel\App;
use SymPress\TwigBundle\WordPress\TemplateHierarchy;
use SymPress\TwigBundle\WordPress\QueryContextProvider;
use SymPress\TwigBundle\WordPress\ThemeRenderer;
use SymPress\TwigBundle\WordPress\Excerpt;
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
$temporaryPosts = [];
$lengthFilter = static fn (): int => 3;
$moreFilter = static fn (): string => ' [more]';
$allowedBlocks = static fn (array $allowed): array => [...$allowed, 'review/count'];
$excerptFilter = static fn (string $text): string => $text . ' [filtered]';
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
    $resolver = new TemplateHierarchy();
    $assert($resolver->forQuery($GLOBALS['wp_query']) === ['custom/landing', 'page-review-regression-fixture', 'page-' . $postId, 'page', 'singular', 'index'], 'Native page hierarchy: ' . json_encode($resolver->forQuery($GLOBALS['wp_query'])));
    $assert(App::make(ThemeRenderer::class)->resolve($resolver->forQuery($GLOBALS['wp_query'])) === '@theme/custom/landing.html.twig', 'Custom Twig page selected.');
    $assert(isset(wp_get_theme()->get_page_templates()['custom/landing.html.twig']), 'Custom Twig template appears in the editor.');
    ob_start();
    (new Theme())->metaDescription();
    $metadata = ob_get_clean();
    $assert($shortcodes === 0 && $blocks === 0, 'Metadata must not render shortcodes or blocks.');
    $assert(str_contains($metadata, 'Stored description &amp; text.'), 'Plain stored metadata is escaped once.');
    $context = App::make(QueryContextProvider::class)->context();
    $assert($shortcodes === 0 && $blocks === 0, 'Context construction is lazy.');
    $content = $context['post']->content();
    $assert($shortcodes === 1 && $blocks === 1, 'Body renders shortcode and dynamic block exactly once.');
    $assert(str_contains((string) $content, 'Dynamic block output'), 'Rendered block reaches Twig.');

    add_filter('excerpt_length', $lengthFilter);
    add_filter('excerpt_more', $moreFilter);
    add_filter('excerpt_allowed_blocks', $allowedBlocks);
    add_filter('get_the_excerpt', $excerptFilter, 20);
    $excerptPost = wp_insert_post(['post_status' => 'publish', 'post_content' => 'One two three four five']);
    $temporaryPosts[] = $excerptPost;
    $assert(Excerpt::fromPost(get_post($excerptPost)) === 'One two three [more] [filtered]', 'Cards respect native excerpt length, suffix and final filter.');
    $reusable = wp_insert_post(['post_type' => 'wp_block', 'post_status' => 'publish', 'post_content' => '<!-- wp:paragraph --><p>Reusable excerpt content</p><!-- /wp:paragraph -->']);
    $temporaryPosts[] = $reusable;
    wp_update_post(['ID' => $excerptPost, 'post_content' => '<!-- wp:block {"ref":' . $reusable . '} /-->']);
    $assert(str_contains(Excerpt::fromPost(get_post($excerptPost)), 'Reusable excerpt content'), 'Reusable blocks contribute native excerpt text.');
    $shortcodesBeforeExcerpt = $shortcodes;
    wp_update_post(['ID' => $reusable, 'post_content' => '<!-- wp:paragraph --><p>[review_count] Reusable excerpt content</p><!-- /wp:paragraph -->']);
    $reusableExcerpt = Excerpt::fromPost(get_post($excerptPost));
    $assert(str_contains($reusableExcerpt, 'Reusable excerpt content'), 'Reusable text survives shortcode removal.');
    $assert($shortcodes === $shortcodesBeforeExcerpt, 'Reusable excerpts strip shortcodes before the native content filters run.');
    $assert(!str_contains($reusableExcerpt, '[review_count]'), 'Shortcode markers are absent from reusable excerpts.');
    wp_update_post(['ID' => $reusable, 'post_content' => '<!-- wp:block {"ref":' . $reusable . '} /--><!-- wp:paragraph --><p>Safe cyclic ending</p><!-- /wp:paragraph -->']);
    $assert(str_contains(Excerpt::fromPost(get_post($excerptPost)), 'Safe cyclic ending'), 'Cyclic reusable references terminate and preserve remaining text.');
    wp_update_post(['ID' => $reusable, 'post_status' => 'private', 'post_content' => '<!-- wp:paragraph --><p>PRIVATE_PATTERN_SECRET</p><!-- /wp:paragraph -->']);
    $assert(!str_contains(Excerpt::fromPost(get_post($excerptPost)), 'PRIVATE_PATTERN_SECRET'), 'Private reusable content does not leak into card excerpts.');
    wp_update_post(['ID' => $reusable, 'post_status' => 'publish', 'post_content' => '<!-- wp:paragraph --><p>Budget text</p><!-- /wp:paragraph -->']);
    wp_update_post(['ID' => $excerptPost, 'post_content' => str_repeat('<!-- wp:block {"ref":' . $reusable . '} /-->', 105)]);
    $reusableRenders = 0;
    $countReusable = static function (string $html) use (&$reusableRenders): string {
        ++$reusableRenders;
        return $html;
    };
    add_filter('render_block_core/paragraph', $countReusable);
    try {
        Excerpt::fromPost(get_post($excerptPost));
        $assert($reusableRenders === 100, 'Wide reusable references have a total expansion budget.');
        Excerpt::fromPost(get_post($excerptPost));
        $assert($reusableRenders === 200, 'The expansion budget resets for each excerpt.');
    } finally {
        remove_filter('render_block_core/paragraph', $countReusable);
    }
    wp_update_post(['ID' => $excerptPost, 'post_content' => '<!-- wp:review/count /-->']);
    $assert(str_contains(Excerpt::fromPost(get_post($excerptPost)), 'Dynamic block output'), 'Dynamic blocks allowed by WordPress contribute excerpt text.');
    remove_filter('excerpt_length', $lengthFilter);
    remove_filter('excerpt_more', $moreFilter);
    remove_filter('excerpt_allowed_blocks', $allowedBlocks);
    remove_filter('get_the_excerpt', $excerptFilter, 20);

    $cases = [
        [['cat' => 1], 'get_category_template', 'category-1'],
        [['author' => 1], 'get_author_template', 'author-1'],
        [['year' => 2026, 'monthnum' => 10], 'get_date_template', 'date'],
        [['s' => 'Notizbuch'], 'get_search_template', 'search'],
    ];
    foreach ($cases as [$query, $getter, $expected]) {
        $GLOBALS['wp_query'] = $GLOBALS['wp_the_query'] = new WP_Query($query);
        $assert(in_array($expected, $resolver->forQuery($GLOBALS['wp_query']), true), 'Core hierarchy captured: ' . $expected);
        $assert(!in_array('custom/landing', $resolver->forQuery($GLOBALS['wp_query']), true), 'The reused resolver does not retain the previous page hierarchy.');
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
        $expected = $taxonomy === 'post_tag' ? 'tag-' . $term['term_id'] : 'taxonomy-review_genre';
        $assert(in_array($expected, $resolver->forQuery($GLOBALS['wp_query']), true), 'Native term hierarchy: ' . $expected);
    }
    $GLOBALS['wp_query'] = $GLOBALS['wp_the_query'] = new WP_Query(['post_type' => 'review_book']);
    $assert($resolver->forQuery($GLOBALS['wp_query']) === ['archive-review_book', 'archive', 'index'], 'Custom post-type archive hierarchy.');
    $attachment = wp_insert_attachment(['post_title' => 'Review image', 'post_mime_type' => 'image/png']);
    $extraPosts[] = $attachment;
    $GLOBALS['post'] = get_post($attachment);
    $GLOBALS['wp_query'] = $GLOBALS['wp_the_query'] = new WP_Query(['attachment_id' => $attachment]);
    $assert(in_array('image-png', $resolver->forQuery($GLOBALS['wp_query']), true) && in_array('attachment', $resolver->forQuery($GLOBALS['wp_query']), true), 'Attachment MIME hierarchy.');
    $GLOBALS['wp_query'] = $GLOBALS['wp_the_query'] = new WP_Query(['cat' => 1]);
    $before = $GLOBALS['wp_filter']['category_template_hierarchy']->callbacks[PHP_INT_MAX] ?? [];
    $failHierarchy = static fn (): never => throw new RuntimeException('Expected hierarchy failure');
    add_filter('category_template_hierarchy', $failHierarchy);
    try {
        $assert(in_array('category', $resolver->forQuery($GLOBALS['wp_query']), true), 'Explicit query hierarchy does not replay native filters.');
        get_category_template();
        throw new RuntimeException('The hierarchy exception must propagate.');
    } catch (RuntimeException $error) {
        $assert($error->getMessage() === 'Expected hierarchy failure', 'Exceptions propagate without returning stale candidates.');
    } finally {
        remove_filter('category_template_hierarchy', $failHierarchy);
    }
    $assert(($GLOBALS['wp_filter']['category_template_hierarchy']->callbacks[PHP_INT_MAX] ?? []) === $before, 'Temporary capture filters are removed after exceptions.');
    echo "PASS: {$checks} native WordPress review regression checks.\n";
} finally {
    remove_filter('excerpt_length', $lengthFilter);
    remove_filter('excerpt_more', $moreFilter);
    remove_filter('excerpt_allowed_blocks', $allowedBlocks);
    remove_filter('get_the_excerpt', $excerptFilter, 20);
    foreach ($temporaryPosts as $id) {
        wp_delete_post($id, true);
    }
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

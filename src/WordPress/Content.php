<?php

declare(strict_types=1);

namespace SymPress\StarterTheme\WordPress;

use Twig\Markup;

/** Explicit boundary between WordPress-rendered HTML and plain template data. */
final class Content
{
    public static function text(string $value): string
    {
        return html_entity_decode(wp_strip_all_tags($value), ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    public static function html(string $value): Markup
    {
        return new Markup($value, 'UTF-8');
    }

    public static function excerpt(\WP_Post $post, int $words = 55): string
    {
        if ($post->post_password !== '') {
            return '';
        }

        // Read stored text only: do not run the_content, render_block or shortcode callbacks.
        $source = $post->post_excerpt !== '' ? $post->post_excerpt : $post->post_content;
        $source = preg_replace('/<\/(?:p|div|h[1-6]|li|blockquote)>/i', '$0 ', $source) ?? $source;
        $text = self::text(strip_shortcodes($source));
        $text = trim(preg_replace('/\s+/u', ' ', $text) ?? '');

        return wp_trim_words($text, $words, '…');
    }
}

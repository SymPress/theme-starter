<?php

declare(strict_types=1);

namespace SymPress\StarterTheme\Tests\Unit;

use Brain\Monkey\Functions;
use SymPress\StarterTheme\WordPress\Content;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class ContentTest extends WordPressTestCase
{
    public function testPlainTextIsDecodedBeforeTwigEscapesItOnce(): void
    {
        Functions\when('wp_strip_all_tags')->alias(strip_tags(...));
        $twig = new Environment(new ArrayLoader(['test' => '{{ text }}']), ['autoescape' => 'html']);
        self::assertSame('A &amp; B', $twig->render('test', ['text' => Content::text('A &amp; B')]));
    }

    public function testExcerptReadsStoredTextWithoutRenderingCallbacks(): void
    {
        Functions\when('wp_strip_all_tags')->alias(strip_tags(...));
        Functions\expect('strip_shortcodes')->once()->with('<p>A &amp; B</p> ')->andReturn('<p>A &amp; B</p> ');
        Functions\expect('wp_trim_words')->once()->with('A & B', 30, '…')->andReturn('A & B');
        Functions\expect('get_the_excerpt')->never();
        Functions\expect('do_shortcode')->never();
        Functions\expect('do_blocks')->never();
        $post = new \WP_Post();
        $post->post_content = '<p>A &amp; B</p>';
        self::assertSame('A & B', Content::excerpt($post, 30));
        $post->post_password = 'secret';
        self::assertSame('', Content::excerpt($post, 30));
    }
}

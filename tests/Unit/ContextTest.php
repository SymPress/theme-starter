<?php

declare(strict_types=1);

namespace SymPress\StarterTheme\Tests\Unit;

use Brain\Monkey\Functions;
use SymPress\StarterTheme\View\ContextComposer;
use SymPress\StarterTheme\WordPress\Context;
use Twig\Environment;
use Twig\Loader\ArrayLoader;

final class ContextTest extends WordPressTestCase
{
    public function testContextKeepsRawUrlsAndNamesAndRunsMatchingComposers(): void
    {
        $post = new \WP_Post();
        $post->post_excerpt = 'Excerpt';
        Functions\expect('have_posts')->twice()->andReturn(true, false);
        Functions\when('the_post')->justReturn(null);
        Functions\when('get_post')->justReturn($post);
        Functions\when('post_password_required')->justReturn(false);
        Functions\when('get_the_category')->justReturn([(object) ['name' => 'Design &amp; Code']]);
        foreach (['is_singular', 'is_404', 'is_search', 'is_archive', 'comments_open'] as $name) {
            Functions\when($name)->justReturn(false);
        }
        Functions\when('get_the_ID')->justReturn(12);
        Functions\when('get_the_title')->justReturn('Title');
        Functions\when('get_permalink')->justReturn('https://example.test/?a=1&b=2');
        Functions\expect('sanitize_url')->twice()->andReturnUsing(static fn (string $url): string => $url);
        Functions\expect('esc_url')->never();
        Functions\when('get_the_date')->justReturn('2026-10-01');
        Functions\when('wp_strip_all_tags')->alias(strip_tags(...));
        Functions\when('strip_shortcodes')->returnArg();
        Functions\when('wp_trim_words')->returnArg();
        Functions\when('get_the_excerpt')->justReturn('Excerpt');
        foreach (['get_the_post_thumbnail', 'wp_link_pages', 'get_custom_logo', 'wp_nav_menu', 'get_the_posts_pagination', 'get_search_query'] as $name) {
            Functions\when($name)->justReturn('');
        }
        Functions\when('get_post_type')->justReturn('post');
        Functions\when('wp_reset_postdata')->justReturn(null);
        Functions\when('get_option')->justReturn(false);
        Functions\when('get_bloginfo')->justReturn('Site');
        Functions\when('home_url')->justReturn('https://example.test/?a=1&b=2');
        Functions\when('wp_date')->justReturn('2026');
        Functions\when('__')->returnArg();
        $composer = $this->createMock(ContextComposer::class);
        $composer->expects(self::once())->method('supports')->with('@StarterTheme/page.html.twig')->willReturn(true);
        $composer->expects(self::once())->method('compose')->willReturnCallback(static fn (array $data): array => [...$data, 'custom' => 'page data']);
        $context = (new Context([$composer]))->build('@StarterTheme/page.html.twig');
        self::assertSame('Design & Code', $context['posts'][0]['category']);
        self::assertSame('page data', $context['custom']);
        $twig = new Environment(new ArrayLoader(['test' => '<a href="{{ posts[0].url }}">{{ posts[0].category }}</a>']), ['autoescape' => 'html']);
        self::assertSame('<a href="https://example.test/?a=1&amp;b=2">Design &amp; Code</a>', $twig->render('test', $context));
    }
}

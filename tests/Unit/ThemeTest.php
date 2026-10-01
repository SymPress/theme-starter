<?php

declare(strict_types=1);

namespace SymPress\StarterTheme\Tests\Unit;

use Brain\Monkey\Functions;
use SymPress\Assets\AssetManager;
use SymPress\Assets\Script;
use SymPress\Assets\Style;
use SymPress\StarterTheme\WordPress\BuildManifest;
use SymPress\StarterTheme\WordPress\Theme;

final class ThemeTest extends WordPressTestCase
{
    public function testNestedFrontendAssetsSurviveABrokenOptionalEditor(): void
    {
        $directory = dirname(__DIR__) . '/Fixtures/nested';
        Functions\when('get_template_directory')->justReturn($directory);
        Functions\when('get_template_directory_uri')->justReturn('https://example.test/theme');
        Functions\expect('wp_enqueue_style')->never();
        $entries = BuildManifest::read($directory . '/build/entrypoints.json');
        self::assertArrayHasKey('sympress-starter-app', $entries);
        self::assertArrayNotHasKey('sympress-starter-editor', $entries);
        $manager = new AssetManager();
        (new Theme())->assets($manager);
        $assets = $manager->assets();
        self::assertSame($directory . '/build/css/app.12345678.css', $assets[Style::class]['sympress-starter-app']->filePath());
        self::assertSame($directory . '/build/js/app.12345678.js', $assets[Script::class]['sympress-starter-app']->filePath());
        self::assertSame('https://example.test/theme/build/js/app.12345678.js', $assets[Script::class]['sympress-starter-app']->url());
        self::assertSame('https://example.test/theme/build/css/app.12345678.css', $assets[Style::class]['sympress-starter-app']->url());
    }

    public function testManifestWithoutEditorAndScriptOnlyEntryAreValid(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'theme-manifest-');
        $asset = tempnam(sys_get_temp_dir(), 'theme-asset-') . '.js';
        file_put_contents($asset, '// test');
        try {
            file_put_contents($file, json_encode(['entrypoints' => ['sympress-starter-app' => ['js' => [basename($asset)]]]]));
            self::assertArrayHasKey('sympress-starter-app', BuildManifest::read($file));
            file_put_contents($file, json_encode(['entrypoints' => ['sympress-starter-app' => ['js' => ['../' . basename($asset)]]]]));
            self::assertNull(BuildManifest::read($file));
        } finally {
            unlink($file);
            unlink($asset);
            unlink(substr($asset, 0, -3));
        }
    }

    public function testValidBuildRegistersOnlyFrontendAssets(): void
    {
        Functions\when('get_template_directory')->justReturn(dirname(__DIR__) . '/Fixtures');
        Functions\when('get_template_directory_uri')->justReturn('https://example.test/theme');
        Functions\when('is_admin')->justReturn(false);
        $manager = new AssetManager();
        (new Theme())->assets($manager);
        $assets = $manager->assets();
        self::assertArrayHasKey('sympress-starter-app', $assets[Style::class]);
        self::assertArrayHasKey('sympress-starter-app', $assets[Script::class]);
        self::assertArrayNotHasKey('sympress-starter-editor', $assets[Style::class]);
    }

    public function testBrokenManifestsFallBackWithoutThrowing(): void
    {
        $directory = sys_get_temp_dir() . '/sympress-manifest-' . bin2hex(random_bytes(6));
        mkdir($directory . '/build', 0777, true);
        Functions\when('get_template_directory')->justReturn($directory);
        Functions\when('get_template_directory_uri')->justReturn('https://example.test/theme');
        Functions\when('is_admin')->justReturn(false);
        Functions\expect('wp_enqueue_style')->times(5)->with('sympress-starter-unbuilt', 'https://example.test/theme/resources/css/site.css', [], '0.1.0');
        try {
            foreach (['{broken', 'null', '{}', '{"entrypoints":false}', '{"entrypoints":{"sympress-starter-app":{"css":"wrong"},"sympress-starter-editor":{"css":[]}}}'] as $json) {
                file_put_contents($directory . '/build/entrypoints.json', $json);
                self::assertNull(BuildManifest::read($directory . '/build/entrypoints.json'));
                (new Theme())->assets(new AssetManager());
            }
        } finally {
            unlink($directory . '/build/entrypoints.json');
            rmdir($directory . '/build');
            rmdir($directory);
        }
    }

    public function testMetaDescriptionUsesStoredContentAndEscapesAttribute(): void
    {
        foreach (['is_admin', 'is_feed', 'is_search', 'is_404', 'is_preview'] as $function) {
            Functions\when($function)->justReturn(false);
        }
        Functions\when('is_singular')->justReturn(true);
        $post = new \WP_Post();
        $post->post_excerpt = 'A & B';
        Functions\when('get_queried_object')->justReturn($post);
        Functions\when('strip_shortcodes')->returnArg();
        Functions\when('wp_strip_all_tags')->alias(strip_tags(...));
        Functions\when('wp_trim_words')->returnArg();
        Functions\when('esc_attr')->alias(static fn (string $value): string => htmlspecialchars($value, ENT_QUOTES));
        Functions\expect('get_the_excerpt')->never();
        $this->expectOutputString("<meta name=\"description\" content=\"A &amp; B\">\n");
        (new Theme())->metaDescription();
        $post->post_password = 'secret';
        (new Theme())->metaDescription();
    }
}

<?php

declare(strict_types=1);

namespace SymPress\StarterTheme\WordPress;

use SymPress\Assets\AssetManager;
use SymPress\Assets\Loader\EncoreEntrypointsLoader;
use SymPress\Assets\Style;

final class Theme
{
    private bool $registered = false;

    public function register(): void
    {
        if ($this->registered) {
            return;
        }

        $this->registered = true;
        add_action('after_setup_theme', $this->setup(...));
        add_action('wp_head', $this->metaDescription(...), 1);
        add_action(AssetManager::ACTION_SETUP, $this->assets(...));
        add_action('wp_enqueue_scripts', static function (): void {
            if (is_singular() && comments_open() && get_option('thread_comments')) {
                wp_enqueue_script('comment-reply');
            }
        });
        add_action('admin_notices', $this->buildNotice(...));
    }

    public function setup(): void
    {
        load_theme_textdomain('sympress-starter', get_template_directory() . '/languages');
        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        add_theme_support('automatic-feed-links');
        add_theme_support('responsive-embeds');
        add_theme_support('align-wide');
        add_theme_support('editor-styles');
        add_theme_support('wp-block-styles');
        add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
        register_nav_menus([
            'primary' => __('Primary navigation', 'sympress-starter'),
            'footer' => __('Footer navigation', 'sympress-starter'),
        ]);

        $file = get_template_directory() . '/build/entrypoints.json';
        if (is_file($file)) {
            $entries = json_decode((string) file_get_contents($file), true, 512, JSON_THROW_ON_ERROR);
            foreach ($entries['entrypoints']['sympress-starter-editor']['css'] ?? [] as $css) {
                add_editor_style('build/' . basename($css));
            }
        }
    }

    public function assets(AssetManager $manager): void
    {
        $file = get_template_directory() . '/build/entrypoints.json';
        if (!is_file($file)) {
            // Keep the site readable before the first build. Admins see an action below.
            if (!is_admin()) {
                wp_enqueue_style('sympress-starter-unbuilt', get_template_directory_uri() . '/resources/css/site.css', [], '0.1.0');
            }
            return;
        }

        $loader = (new EncoreEntrypointsLoader())->withDirectoryUrl(get_template_directory_uri() . '/build/');
        foreach ($loader->load($file) as $asset) {
            if ($asset->handle() === 'sympress-starter-app' || str_starts_with($asset->handle(), 'sympress-starter-app-')) {
                // A small production stylesheet costs less as part of the first
                // HTML response. Keep large/dev files and URL-based CSS external.
                if ($asset instanceof Style && apply_filters('sympress_starter/inline_styles', true)) {
                    $path = $asset->filePath();
                    if (preg_match('/\.[a-f0-9]{8,}\.css$/', $path)
                        && is_readable($path) && filesize($path) <= 16_384
                    ) {
                        $css = file_get_contents($path);
                        if ($css !== false && !preg_match('/\burl\s*\(|@import\b/i', $css)) {
                            $asset->useInlineFilter();
                        }
                    }
                }
                $manager->register($asset);
            }
        }
    }

    public function metaDescription(): void
    {
        if (is_admin() || is_feed() || is_search() || is_404() || is_preview()) {
            return;
        }

        // Let established SEO providers own their metadata. Other integrations
        // can disable the fallback with the enabled filter.
        $hasSeoProvider = defined('WPSEO_VERSION') || defined('RANK_MATH_VERSION')
            || defined('AIOSEO_VERSION') || defined('SEOPRESS_VERSION')
            || defined('THE_SEO_FRAMEWORK_VERSION');
        if (!apply_filters('sympress_starter/meta_description_enabled', !$hasSeoProvider)) {
            return;
        }

        $description = '';
        $post = null;
        if (is_singular()) {
            $post = get_queried_object();
        } elseif (is_home() && get_option('page_for_posts')) {
            $post = get_post((int) get_option('page_for_posts'));
        }

        if ($post instanceof \WP_Post) {
            // Keep protected content out of metadata even for unlocked visits.
            if ($post->post_password !== '') {
                return;
            }
            $description = get_the_excerpt($post);
        } elseif (is_home()) {
            $description = get_bloginfo('description', 'raw');
        } elseif (is_category() || is_tag() || is_tax()) {
            $description = get_the_archive_description();
        }

        $description = (string) apply_filters('sympress_starter/meta_description', $description);
        $description = html_entity_decode(wp_strip_all_tags($description), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $description = trim((string) preg_replace('/\s+/u', ' ', $description));
        $description = wp_trim_words($description, 30, '…');
        if ($description !== '') {
            echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
        }
    }

    public function buildNotice(): void
    {
        if (current_user_can('edit_theme_options') && !is_file(get_template_directory() . '/build/entrypoints.json')) {
            echo '<div class="notice notice-warning"><p>' . esc_html__(
                'SymPress Starter assets are missing. Run npm ci && npm run build in the theme directory.',
                'sympress-starter',
            ) . '</p></div>';
        }
    }
}

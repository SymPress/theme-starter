<?php

declare(strict_types=1);

namespace SymPress\StarterTheme\WordPress;

use SymPress\Assets\AssetManager;
use SymPress\Assets\Loader\EncoreEntrypointsLoader;
use SymPress\Assets\Loader\EncoreManifest;
use SymPress\Assets\Script;
use SymPress\Assets\Security\FilesystemPathPolicy;
use SymPress\Assets\SmallStyleConfigurator;
use SymPress\TwigBundle\WordPress\Excerpt;

/** @internal */
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
        add_action('admin_notices', $this->buildNotice(...));
    }

    public function setup(): void
    {
        load_theme_textdomain('sympress-starter', get_template_directory() . '/languages');
        add_theme_support('sympress-twig');
        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        add_theme_support('custom-logo', ['flex-width' => true, 'flex-height' => true]);
        add_theme_support('automatic-feed-links');
        add_theme_support('responsive-embeds');
        add_theme_support('align-wide');
        add_theme_support('editor-styles');
        add_theme_support('wp-block-styles');
        add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script']);
        register_nav_menus([
            'primary' => __('Hauptnavigation', 'sympress-starter'),
            'footer'  => __('Fußnavigation', 'sympress-starter'),
        ]);

        $file = get_template_directory() . '/build/entrypoints.json';
        foreach ((new EncoreEntrypointsLoader())->editorStyles($file, 'sympress-starter-editor') as $css) {
            add_editor_style($css);
        }
    }

    public function assets(AssetManager $manager): void
    {
        $file = get_template_directory() . '/build/entrypoints.json';
        $entries = EncoreManifest::read($file);
        if (!isset($entries['sympress-starter-app'])) {
            // Keep the site readable before the first build. Admins see an action below.
            if (!is_admin()) {
                wp_enqueue_style('sympress-starter-unbuilt', get_template_directory_uri() . '/resources/css/site.css', [], '1.0.0');
            }
            return;
        }

        $loader = (new EncoreEntrypointsLoader())->withDirectoryUrl(get_template_directory_uri() . '/build/');
        foreach ($loader->fromFile($file) as $asset) {
            if ($asset->handle() !== 'sympress-starter-app' && !str_starts_with($asset->handle(), 'sympress-starter-app-')) {
                continue;
            }

            if (apply_filters('sympress_starter/inline_styles', true)) {
                (new SmallStyleConfigurator(new FilesystemPathPolicy([dirname($file)])))->configure($asset);
            }
            if ($asset instanceof Script) {
                $asset->isInHeader();
                if ($asset->handle() === 'sympress-starter-app') {
                    // Select the mobile layout before first paint; the bundle remains deferred.
                    $asset->prependInlineScript("document.documentElement.classList.add('has-menu-js');");
                }
            }
            $manager->register($asset);
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
            $description = Excerpt::plainExcerpt($post, 30);
        } elseif (is_home()) {
            $description = get_bloginfo('description', 'raw');
        } elseif (is_category() || is_tag() || is_tax()) {
            $description = get_the_archive_description();
        }

        if (self::descriptionText($description) === '') {
            $description = wp_get_document_title();
        }
        $description = self::descriptionText((string) apply_filters('sympress_starter/meta_description', $description));
        if ($description === '') {
            return;
        }

        echo '<meta name="description" content="' . esc_attr($description) . '">' . "\n";
    }

    private static function descriptionText(string $description): string
    {
        $description = html_entity_decode(wp_strip_all_tags($description), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $description = trim((string) preg_replace('/\s+/u', ' ', $description));

        return wp_trim_words($description, 30, '…');
    }

    public function buildNotice(): void
    {
        $entries = EncoreManifest::read(get_template_directory() . '/build/entrypoints.json');
        if (!current_user_can('edit_theme_options') || isset($entries['sympress-starter-app'])) {
            return;
        }

        echo '<div class="notice notice-warning"><p>' . esc_html__(
            'Die SymPress-Starter-Assets fehlen oder sind beschädigt. Bitte npm ci && npm run build im Theme-Verzeichnis ausführen.',
            'sympress-starter',
        ) . '</p></div>';
    }
}

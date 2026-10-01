<?php

declare(strict_types=1);

use SymPress\Kernel\App;
use SymPress\StarterTheme\WordPress\Theme;

if (!defined('ABSPATH')) {
    exit;
}

// The site's MU plugin owns booting SymPress. Never create a second kernel here.
$theme = class_exists(App::class) && App::container()?->has(Theme::class)
    ? App::make(Theme::class)
    : null;

if ($theme instanceof Theme) {
    $theme->register();
} else {
    add_action('admin_notices', static function (): void {
        echo '<div class="notice notice-error"><p>' . esc_html__(
            'SymPress Starter requires the site Composer autoloader, a booted SymPress kernel and sympress/twig-bundle. See the theme README for setup.',
            'sympress-starter',
        ) . '</p></div>';
    });
}

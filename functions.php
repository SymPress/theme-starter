<?php

declare(strict_types=1);

use SymPress\Kernel\App;
use SymPress\StarterTheme\WordPress\Theme;
use SymPress\StarterTheme\View\TemplateResolver;

if (!defined('ABSPATH')) {
    exit;
}

// The site's MU plugin owns booting SymPress. Never create a second kernel here.
$theme = class_exists(App::class) && App::container()?->has(Theme::class)
    ? App::make(Theme::class)
    : null;

if ($theme instanceof Theme) {
    App::make(TemplateResolver::class)->register();
    $theme->register();
} else {
    add_action('admin_notices', static function (): void {
        echo '<div class="notice notice-error"><p>' . esc_html__(
            'SymPress Starter benötigt den Composer-Autoloader der Website, einen gestarteten SymPress-Kernel und sympress/twig-bundle. Die Einrichtung ist in der Theme-README beschrieben.',
            'sympress-starter',
        ) . '</p></div>';
    });
}

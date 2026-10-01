<?php

declare(strict_types=1);

use SymPress\Kernel\App;
use SymPress\StarterTheme\View\Renderer;

if (!defined('ABSPATH')) {
    exit;
}

$renderer = class_exists(App::class) && App::container()?->has(Renderer::class)
    ? App::make(Renderer::class)
    : null;

if (!$renderer instanceof Renderer) {
    wp_die(
        esc_html__('The theme is not configured. Please contact the site administrator.', 'sympress-starter'),
        esc_html__('Theme configuration required', 'sympress-starter'),
        ['response' => 503],
    );
}

// Templates escape text. Only explicitly trusted WordPress HTML is marked safe.
echo $renderer->render(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped

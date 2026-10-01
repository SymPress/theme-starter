<?php

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

wp_die(
        esc_html__('Twig theme rendering is unavailable. The site administrator must check the SymPress kernel bootstrap, active theme registration, template_include configuration and deployed Twig views.', 'sympress-starter'),
        esc_html__('Theme configuration required', 'sympress-starter'),
        ['response' => 503],
);

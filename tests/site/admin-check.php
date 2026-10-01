<?php
declare(strict_types=1);
define('WP_ADMIN', true);
require __DIR__ . '/public/wp/wp-load.php';
$templates = wp_get_theme()->get_page_templates(null, 'page');
if (!isset($templates['custom/landing.html.twig'])) {
    throw new RuntimeException('Twig landing template is missing from the editor template list.');
}
echo "PASS: admin custom-template discovery.\n";

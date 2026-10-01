<?php
declare(strict_types=1);
define('WP_ADMIN', true);
define('DOING_AJAX', true);
require __DIR__ . '/public/wp/wp-load.php';
// Scope the script like WP-CLI eval-file: its local $post must not replace globals.
(static function (): void {
    require __DIR__ . '/twig-integration-check.php';
})();

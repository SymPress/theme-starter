<?php

declare(strict_types=1);

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = realpath(__DIR__ . '/public' . $path);
// Emulate production asset delivery in this local PHP-server fixture. Only
// content-hashed theme assets are immutable; WordPress HTML is never cached here.
if ($file !== false && is_file($file)
    && str_starts_with($file, dirname(__DIR__, 2) . '/build/')
    && preg_match('/\.[a-f0-9]{8,}\.(css|js)$/', $file, $match)
) {
    ini_set('zlib.output_compression', '1');
    header('Content-Type: ' . ($match[1] === 'css' ? 'text/css' : 'application/javascript') . '; charset=UTF-8');
    header('Cache-Control: public, max-age=31536000, immutable');
    if ($_SERVER['REQUEST_METHOD'] !== 'HEAD') {
        readfile($file);
    }
    return true;
}
if ($path !== '/' && $file !== false && is_file($file) && (
    str_starts_with($file, __DIR__ . '/public/')
    || str_starts_with($file, dirname(__DIR__, 2) . '/build/')
    || $file === dirname(__DIR__, 2) . '/style.css'
    || $file === dirname(__DIR__, 2) . '/screenshot.png'
)) {
    return false;
}
// Match a normal WordPress front controller; cli-server otherwise reports '/' as
// SCRIPT_NAME, which WordPress can mistake for a login request.
$_SERVER['SCRIPT_NAME'] = '/index.php';
$_SERVER['PHP_SELF'] = '/index.php';
ini_set('zlib.output_compression', '1');
define('WP_USE_THEMES', true);
require __DIR__ . '/public/wp/wp-blog-header.php';

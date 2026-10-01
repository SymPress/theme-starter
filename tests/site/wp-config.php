<?php

declare(strict_types=1);

// Disposable local test database only. No production settings or credentials.
define('DB_NAME', 'theme_test');
define('DB_USER', 'theme_test');
define('DB_PASSWORD', 'local-theme-tests-only');
define('DB_HOST', '127.0.0.1:19367');
define('DB_CHARSET', 'utf8mb4');
define('DB_COLLATE', '');
$table_prefix = getenv('THEME_TEST_TABLE_PREFIX') ?: 'theme_';
define('WP_DEBUG', true);
define('WP_DEBUG_DISPLAY', false);
define('WP_DEBUG_LOG', __DIR__ . '/var/debug.log');
define('WP_ENVIRONMENT_TYPE', 'local');
define('WP_HOME', getenv('THEME_TEST_URL') ?: 'http://127.0.0.1:18943');
define('WP_SITEURL', WP_HOME . '/wp');
define('WP_CONTENT_DIR', __DIR__ . '/public/wp-content');
define('WP_CONTENT_URL', WP_HOME . '/wp-content');
define('DISALLOW_FILE_MODS', true);
define('DISABLE_WP_CRON', true);

// Test-only ephemeral salts; the fixture is never used for persistent sessions.
foreach (['AUTH_KEY', 'SECURE_AUTH_KEY', 'LOGGED_IN_KEY', 'NONCE_KEY', 'AUTH_SALT', 'SECURE_AUTH_SALT', 'LOGGED_IN_SALT', 'NONCE_SALT'] as $salt) {
    define($salt, hash('sha256', 'sympress-disposable-theme-tests-' . $salt));
}
require_once __DIR__ . '/vendor/autoload.php';
if (!defined('ABSPATH')) {
    define('ABSPATH', __DIR__ . '/public/wp/');
}
require_once ABSPATH . 'wp-settings.php';

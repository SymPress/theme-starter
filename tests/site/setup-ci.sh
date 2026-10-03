#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")"
mkdir -p var
php -r 'umask(0077); if (!is_file("var/app-secret")) { file_put_contents("var/app-secret", bin2hex(random_bytes(32))); }'
mysql -hdb -uroot -proot -e "CREATE DATABASE IF NOT EXISTS theme_test; GRANT ALL ON theme_test.* TO 'db'@'%';"
composer install --no-interaction --prefer-dist
mkdir -p public/wp-content/mu-plugins var
ln -sfn ../wp-config.php public/wp-config.php
ln -sfn ../front-controller.php public/index.php
ln -sfn ../../../mu-plugin.php public/wp-content/mu-plugins/theme-test.php
wp --path=public/wp core install --url="$THEME_TEST_URL" --title='SymPress Journal' --admin_user=theme-test --admin_password=local-fixture-only-no-reuse --admin_email=dev@example.test --skip-email
wp --path=public/wp eval-file seed.php
curl --fail --silent --show-error "$THEME_TEST_URL/" > /dev/null

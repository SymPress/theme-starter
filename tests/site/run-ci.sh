#!/usr/bin/env bash
set -euo pipefail
cd "$(dirname "$0")"
wp() { php var/wp-cli.phar "$@"; }
for check in runtime-check.php meta-description-check.php review-regressions.php twig-integration-check.php; do
  wp --path=public/wp eval-file "$check"
done
php admin-check.php
php ajax-check.php

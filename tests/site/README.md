# Disposable WordPress integration fixture

This fixture uses its own MariaDB database, theme source and Composer vendor tree.
No existing SymPress starter/demo configuration is changed. The seed script checks
both `WP_ENVIRONMENT_TYPE=local` and database name `theme_test` before resetting
fixture posts. The database credentials and salts here are public test values.

Executed locally with Podman and the existing PHP 8.5 DDEV image. Commands below
assume the workspace is mounted as `/workspace` in the PHP container:

```sh
# From the SymPress workspace root:
podman run -d --name sympress-theme-test-db \
  -p 127.0.0.1:19367:3306 \
  -e MARIADB_RANDOM_ROOT_PASSWORD=1 \
  -e MARIADB_DATABASE=theme_test -e MARIADB_USER=theme_test \
  -e MARIADB_PASSWORD=local-theme-tests-only docker.io/library/mariadb:11.8

podman run -d --name sympress-theme-test-web --network host \
  -v "$PWD:/workspace" -w /workspace/theme-starter/tests/site \
  --entrypoint php8.5 docker.io/ddev/ddev-webserver:v1.25.4 \
  -S 127.0.0.1:18943 -t public router.php
```

Before starting the web container on a fresh checkout, install the fixture Composer
dependencies with PHP 8.5 (`composer install` in this directory), create `var/`
and `public/wp-content/mu-plugins/`, and create these links:

```sh
ln -s ../wp-config.php public/wp-config.php
ln -s ../../../mu-plugin.php public/wp-content/mu-plugins/theme-test.php
```

Wait for MariaDB readiness, then install and seed using PHP 8.5 explicitly (the
image's default `php` executable can be 8.4):

```sh
podman exec sympress-theme-test-web php8.5 /usr/local/bin/wp-cli \
  --allow-root --path=public/wp core install \
  --url=http://127.0.0.1:18943 --title='SymPress Journal' \
  --admin_user=theme-test --admin_password=local-fixture-only-no-reuse \
  --admin_email=dev@example.test --skip-email
podman exec sympress-theme-test-web php8.5 /usr/local/bin/wp-cli \
  --allow-root --path=public/wp eval-file seed.php
podman exec sympress-theme-test-web php8.5 /usr/local/bin/wp-cli \
  --allow-root --path=public/wp eval-file runtime-check.php
podman exec sympress-theme-test-web php8.5 /usr/local/bin/wp-cli \
  --allow-root --path=public/wp eval-file meta-description-check.php
```

Build theme assets, then run `npm run test:browser` from the theme root. The router
sets the same front-controller server variables as a normal WordPress request;
otherwise PHP's CLI server can report `/` as SCRIPT_NAME and trigger login asset
detection. This adjustment belongs to the fixture, not the theme.

The router compresses frontend HTML and content-hashed theme CSS/JS through PHP's
zlib extension. Hashed assets receive a one-year immutable cache header; HTML and
unversioned manifests do not. A real deployment should implement these rules on
its webserver. Verify the fixture's headers and decoded response integrity with
`node scripts/check-http.mjs` from the theme root.

The fixture intentionally has no email delivery or production hardening. WP-CLI's
bundled dependencies emit PHP 8.5 deprecation notices; record these separately
from frontend runtime failures. Stop the test containers with
`podman stop sympress-theme-test-web sympress-theme-test-db` when no longer needed.

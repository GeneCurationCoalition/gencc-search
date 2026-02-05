#!/usr/bin/env sh
set -e

mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache
chown -R www:www storage bootstrap/cache 2>/dev/null || true

if [ -f artisan ] && [ ! -f bootstrap/cache/packages.php ]; then
  php artisan package:discover --ansi >/dev/null 2>&1
fi

exec "$@"

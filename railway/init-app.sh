#!/bin/sh
set -eu

php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --force
php artisan storage:link || true

# Create/update the first admin only when secure deployment variables are provided.
if [ -n "${ADMIN_EMAIL:-}" ] && [ -n "${ADMIN_PASSWORD:-}" ]; then
  php artisan app:bootstrap-admin --no-interaction
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache

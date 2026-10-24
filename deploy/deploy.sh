#!/usr/bin/env bash
#
# Actualiza la aplicación en el servidor. Lo ejecuta GitHub Actions por SSH
# en cada push a main; también se puede correr a mano en el servidor.
#
set -euo pipefail

cd /var/www/bloqueria

php artisan down --retry=15 || true
trap 'php artisan up' EXIT

git fetch --quiet origin main
git reset --hard origin/main

composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-progress
npm ci --no-audit --no-fund
npm run build

php artisan migrate --force
php artisan storage:link 2>/dev/null || true
php artisan optimize
php artisan filament:optimize
php artisan queue:restart

echo "Despliegue terminado: $(git log -1 --format='%h %s')"

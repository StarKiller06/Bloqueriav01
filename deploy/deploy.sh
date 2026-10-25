#!/usr/bin/env bash
#
# Actualiza la aplicación en el servidor. Lo ejecuta GitHub Actions por SSH
# en cada push a main; también se puede correr a mano en el servidor.
#
set -euo pipefail

cd /opt/bloqueria

git fetch --quiet origin main
git reset --hard origin/main

docker compose build
docker compose up -d --wait db
docker compose run --rm --no-deps -u www-data app php artisan migrate --force
docker compose up -d --remove-orphans
docker image prune -f > /dev/null

echo "Despliegue terminado: $(git log -1 --format='%h %s')"

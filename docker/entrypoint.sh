#!/bin/sh
#
# Arranque de los contenedores de la app (web, cola y programador): arma las
# cachés de Laravel y Filament con el .env montado y luego corre el comando.
#
set -e

php artisan optimize
php artisan filament:optimize

if [ "$(id -u)" = "0" ]; then
    chown -R www-data:www-data storage bootstrap/cache
fi

exec "$@"

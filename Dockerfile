# syntax=docker/dockerfile:1

# PHP 8.5.12 o superior: exigido por composer.json (parche de PHP2Shell).
FROM docker.io/library/php:8.5.12-fpm-trixie AS base

COPY --from=docker.io/mlocati/php-extension-installer:2.12.3 /usr/bin/install-php-extensions /usr/local/bin/
RUN install-php-extensions pdo_pgsql intl gd zip bcmath pcntl \
    && php -r 'exit(version_compare(PHP_VERSION, "8.5.12", "<") ? 1 : 0);'

COPY docker/php.ini $PHP_INI_DIR/conf.d/zz-bloqueria.ini
WORKDIR /var/www/html

# Aplicación: PHP-FPM; también la usan la cola y el programador de tareas.
FROM base AS app

COPY --from=docker.io/library/composer:2.10.3 /usr/bin/composer /usr/bin/composer
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --no-progress

COPY . .
RUN composer dump-autoload --optimize --no-dev --no-interaction \
    && rm /usr/bin/composer \
    && chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]

# Servidor web: Caddy sirve public/ y pasa PHP a la app; saca HTTPS solo si hay dominio.
FROM docker.io/library/caddy:2.11.7-alpine AS web

COPY docker/Caddyfile /etc/caddy/Caddyfile
COPY --from=app /var/www/html/public /var/www/html/public

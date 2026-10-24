#!/usr/bin/env bash
#
# Prepara un EC2 nuevo con Ubuntu 24.04 (se corre UNA sola vez, con sudo):
#   sudo bash preparar-servidor.sh
#
# Instala PHP 8.5, Nginx, PostgreSQL, Composer, Node 22, Supervisor y Certbot,
# crea el usuario "deploy" (dueño de la app y de PHP-FPM) y la carpeta
# /var/www/bloqueria. No toca contraseñas: la base de datos y el .env se
# configuran a mano después (ver los pasos que imprime al final).
#
set -euo pipefail

if [[ $EUID -ne 0 ]]; then
    echo "Correr con sudo." >&2
    exit 1
fi

export DEBIAN_FRONTEND=noninteractive

apt-get update
apt-get install -y software-properties-common curl git unzip ufw cron
add-apt-repository -y ppa:ondrej/php
curl -fsSL https://deb.nodesource.com/setup_22.x | bash -
apt-get update

apt-get install -y \
    nginx postgresql supervisor certbot python3-certbot-nginx nodejs \
    php8.5-fpm php8.5-cli php8.5-pgsql php8.5-mbstring php8.5-xml php8.5-curl \
    php8.5-zip php8.5-gd php8.5-intl php8.5-bcmath

if [[ ! -x /usr/local/bin/composer ]]; then
    curl -fsSL https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer
fi

if ! id deploy &>/dev/null; then
    adduser --disabled-password --gecos "" deploy
    usermod -aG www-data deploy
    install -d -m 700 -o deploy -g deploy /home/deploy/.ssh
fi

sed -i 's/^user = www-data/user = deploy/; s/^group = www-data/group = deploy/' /etc/php/8.5/fpm/pool.d/www.conf
systemctl restart php8.5-fpm

install -d -o deploy -g deploy /var/www/bloqueria

echo "* * * * * cd /var/www/bloqueria && php artisan schedule:run >> /dev/null 2>&1" | crontab -u deploy -

ufw allow OpenSSH
ufw allow 'Nginx Full'
ufw --force enable

php -v | head -1
echo
echo "Listo. Revisa que PHP sea 8.5.12 o superior. Siguientes pasos en el mensaje de despliegue."

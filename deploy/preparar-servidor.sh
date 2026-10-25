#!/usr/bin/env bash
#
# Prepara un EC2 nuevo con Ubuntu (se corre UNA sola vez, con sudo):
#   sudo bash preparar-servidor.sh
#
# Instala Docker, agrega 2 GB de swap y deja el proyecto en /opt/bloqueria
# a nombre del usuario ubuntu. PHP, PostgreSQL y el servidor web corren
# dentro de los contenedores, así que no se instalan en el sistema.
#
set -euo pipefail

if [[ $EUID -ne 0 ]]; then
    echo "Correr con sudo." >&2
    exit 1
fi

export DEBIAN_FRONTEND=noninteractive

apt-get update
apt-get install -y git docker.io docker-compose-v2 docker-buildx
systemctl enable --now docker
usermod -aG docker ubuntu

if [[ ! -f /swapfile ]]; then
    fallocate -l 2G /swapfile
    chmod 600 /swapfile
    mkswap /swapfile
    swapon /swapfile
    echo '/swapfile none swap sw 0 0' >> /etc/fstab
fi

if [[ ! -d /opt/bloqueria/.git ]]; then
    git clone https://github.com/StarKiller06/Bloqueriav01.git /opt/bloqueria
fi
chown -R ubuntu:ubuntu /opt/bloqueria

docker --version
docker compose version
echo
echo "Listo. Falta crear /opt/bloqueria/.env (ver deploy/env.produccion.example)."

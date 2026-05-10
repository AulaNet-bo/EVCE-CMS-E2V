#!/bin/bash
set -e

echo "==========================================================="
echo "   EVCE PLATFORM - BARE METAL UBUNTU INSTALLATION SCRIPT   "
echo "==========================================================="

# Ensure script is run as root
if [ "$EUID" -ne 0 ]; then
  echo "Por favor, ejecuta este script como root (sudo bash install_ubuntu.sh)"
  exit 1
fi

echo -e "\n[1/6] Configurando Memoria Swap (2GB) para prevenir cuelgues (OOM)..."
if [ ! -f /swapfile ]; then
    fallocate -l 2G /swapfile
    chmod 600 /swapfile
    mkswap /swapfile
    swapon /swapfile
    echo '/swapfile none swap sw 0 0' | tee -a /etc/fstab
    echo "Swap configurada exitosamente."
else
    echo "Swap ya está configurada, omitiendo."
fi

echo -e "\n[2/6] Actualizando el sistema e instalando utilidades básicas..."
apt-get update -y
apt-get install -y apt-transport-https ca-certificates curl software-properties-common git unzip nano

echo -e "\n[3/6] Instalando Docker y Docker Compose..."
if ! command -v docker &> /dev/null; then
    curl -fsSL https://download.docker.com/linux/ubuntu/gpg | apt-key add -
    add-apt-repository "deb [arch=amd64] https://download.docker.com/linux/ubuntu $(lsb_release -cs) stable" -y
    apt-get update -y
    apt-get install -y docker-ce docker-ce-cli containerd.io docker-compose-plugin
    systemctl start docker
    systemctl enable docker
    echo "Docker instalado exitosamente."
else
    echo "Docker ya está instalado, omitiendo."
fi

echo -e "\n[4/6] Preparando variables de entorno (.env)..."
cd "$(dirname "$0")/.." # Go to project root (one level up from deployment/)

if [ ! -f .env ]; then
    echo "Creando archivo .env desde .env.example..."
    cp .env.example .env
    
    # Generate random passwords for production
    DB_PASS=$(openssl rand -hex 12)
    APP_KEY=$(openssl rand -base64 32)
    
    sed -i "s/DB_PASSWORD=.*/DB_PASSWORD=${DB_PASS}/g" .env
    sed -i "s/APP_KEY=.*/APP_KEY=base64:${APP_KEY}/g" .env
    
    # Asegurar que apunte a los servicios de Docker (MariaDB y Redis unificados)
    sed -i "s/DB_HOST=127.0.0.1/DB_HOST=db/g" .env
    sed -i "s/REDIS_HOST=127.0.0.1/REDIS_HOST=redis/g" .env
    echo "Archivo .env generado con contraseñas seguras."
else
    echo "El archivo .env ya existe, omitiendo."
fi

echo -e "\n[5/6] Levantando plataforma completa (Base de Datos, STEVE Java, CMS y Redis)..."
# Copiar el docker-compose.full.yml a la raíz como docker-compose.yml si no existe,
# o usar el comando docker compose apuntando al archivo en la carpeta deployment.
if [ -f docker-compose.yml ]; then
    echo "Respaldando docker-compose original..."
    mv docker-compose.yml docker-compose.yml.bak
fi
cp deployment/docker-compose.full.yml docker-compose.yml

echo "Construyendo contenedores e iniciando servicios..."
docker compose build
docker compose up -d

echo -e "\n[6/6] Inicializando el CMS (Instalando dependencias y Base de Datos)..."
echo "Esperando 15 segundos a que la base de datos MariaDB inicialice..."
sleep 15

echo "Instalando dependencias de PHP (Composer)..."
docker exec evce-cms-app bash -c "curl -sS https://getcomposer.org/installer | php && php composer.phar install --no-dev --optimize-autoloader" || echo "Aviso: Error instalando dependencias (o ya están instaladas)."

echo "Ejecutando migraciones de base de datos..."
docker exec evce-cms-app php artisan migrate --force

echo "Limpiando cachés y optimizando..."
docker exec evce-cms-app php artisan optimize:clear
docker exec evce-cms-app php artisan storage:link || true

echo -e "\n==========================================================="
echo "   !INSTALACIÓN COMPLETADA EXITOSAMENTE!   "
echo "==========================================================="
echo "El sistema está corriendo."
echo " - El CMS (Laravel) está disponible en el puerto 80."
echo " - El Backend STEVE (Web) está en el puerto 8180."
echo " - El endpoint OCPP WSS está en el puerto 8443."
echo ""
echo "Recuerda apuntar tu dominio (ej: e2v.evbol.com) a la IP pública de este servidor."
echo "Si vas a usar Cloudflare Tunnels (cloudflared), deberás instalarlo por separado:"
echo "Comando: curl -L --output cloudflared.deb https://github.com/cloudflare/cloudflared/releases/latest/download/cloudflared-linux-amd64.deb && sudo dpkg -i cloudflared.deb && cloudflared service install TU_TOKEN_AQUI"

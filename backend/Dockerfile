# Dockerfile para Producción - Backend (Laravel Octane)
FROM serversideup/php:8.4-cli

USER root

# Instalación de extensiones necesarias para Octane/RoadRunner
RUN apt-get update && apt-get install -y --no-install-recommends \
    git \
    unzip \
    && docker-php-ext-install sockets \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

# Copiar archivos de dependencias primero para aprovechar la caché de Docker
COPY --chown=www-data:www-data composer.json composer.lock ./

# Instalar dependencias de producción
RUN composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev

# Copiar el resto del código
COPY --chown=www-data:www-data . .

# Permisos correctos para Laravel
RUN chmod -R 775 storage bootstrap/cache

USER www-data

# Comando para iniciar Octane con RoadRunner
CMD ["php", "artisan", "octane:start", "--server=roadrunner", "--host=0.0.0.0", "--rpc-port=6001", "--port=8000"]

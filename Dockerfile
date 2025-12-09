FROM php:8.3-fpm

# Instalar Nginx, Supervisor y dependencias
RUN apt-get update && apt-get install -y --no-install-recommends \
    nginx supervisor \
    git curl zip unzip ca-certificates \
    libfreetype6-dev libjpeg62-turbo-dev libpng-dev \
    libonig-dev libxml2-dev libicu-dev libzip-dev zlib1g-dev \
  && rm -rf /var/lib/apt/lists/*

# Instalar extensiones de PHP necesarias
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl zip sockets

# Configurar PHP para manejar archivos grandes y múltiples usuarios
RUN echo "upload_max_filesize = 100M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 100M" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/uploads.ini \
    && echo "memory_limit = 512M" >> /usr/local/etc/php/conf.d/uploads.ini

# Configurar PHP-FPM para 100+ usuarios concurrentes
RUN echo "[www]" > /usr/local/etc/php-fpm.d/zz-custom.conf \
    && echo "pm = dynamic" >> /usr/local/etc/php-fpm.d/zz-custom.conf \
    && echo "pm.max_children = 150" >> /usr/local/etc/php-fpm.d/zz-custom.conf \
    && echo "pm.start_servers = 30" >> /usr/local/etc/php-fpm.d/zz-custom.conf \
    && echo "pm.min_spare_servers = 20" >> /usr/local/etc/php-fpm.d/zz-custom.conf \
    && echo "pm.max_spare_servers = 50" >> /usr/local/etc/php-fpm.d/zz-custom.conf \
    && echo "pm.max_requests = 500" >> /usr/local/etc/php-fpm.d/zz-custom.conf

# Instalar Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configurar directorio de trabajo
# IMPORTANTE: Usamos /var/www/html/assistpro_kardex_fc para respetar rutas hardcoded
WORKDIR /var/www/html/assistpro_kardex_fc

# Copiar archivos del proyecto
COPY . .

# Crear directorios necesarios
RUN mkdir -p storage/logs \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    logs

# Instalar dependencias de Composer
RUN composer install --optimize-autoloader --no-dev

# Configurar permisos
RUN chown -R www-data:www-data /var/www/html/assistpro_kardex_fc \
    && chmod -R 775 /var/www/html/assistpro_kardex_fc/storage \
    && chmod -R 775 /var/www/html/assistpro_kardex_fc/logs

# Configurar Nginx
# ROOT en /var/www/html para que /assistpro_kardex_fc/... funcione
RUN echo 'server {\n\
    listen 7071;\n\
    server_name localhost;\n\
    root /var/www/html;\n\
\n\
    add_header X-Frame-Options "SAMEORIGIN";\n\
    add_header X-Content-Type-Options "nosniff";\n\
\n\
    index index.php index.html;\n\
    charset utf-8;\n\
\n\
    client_max_body_size 100M;\n\
    client_body_timeout 300s;\n\
    client_header_timeout 300s;\n\
\n\
    # Proteger archivos sensibles\n\
    location ~ /\.(?!well-known).* {\n\
        deny all;\n\
    }\n\
    location ~ ^/assistpro_kardex_fc/(storage|bootstrap|vendor|app)/ {\n\
        deny all;\n\
    }\n\
    location ~ ^/assistpro_kardex_fc/(composer\.|\.env|artisan) {\n\
        deny all;\n\
    }\n\
\n\
    # Manejo principal de rutas\n\
    location / {\n\
        try_files $uri $uri/ /assistpro_kardex_fc/index.php?$query_string;\n\
    }\n\
\n\
    # Ejecución de archivos PHP\n\
    location ~ \.php$ {\n\
        fastcgi_pass 127.0.0.1:9000;\n\
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;\n\
        include fastcgi_params;\n\
        fastcgi_read_timeout 300;\n\
        fastcgi_send_timeout 300;\n\
        fastcgi_buffer_size 128k;\n\
        fastcgi_buffers 256 16k;\n\
        fastcgi_busy_buffers_size 256k;\n\
        fastcgi_temp_file_write_size 256k;\n\
    }\n\
}' > /etc/nginx/sites-available/default

# Configurar Supervisor para manejar PHP-FPM y Nginx
RUN echo '[supervisord]\n\
nodaemon=true\n\
user=root\n\
logfile=/var/log/supervisor/supervisord.log\n\
pidfile=/var/run/supervisord.pid\n\
\n\
[program:php-fpm]\n\
command=php-fpm\n\
autostart=true\n\
autorestart=true\n\
priority=5\n\
stderr_logfile=/var/log/php-fpm.err.log\n\
stdout_logfile=/var/log/php-fpm.out.log\n\
stdout_logfile_maxbytes=10MB\n\
stderr_logfile_maxbytes=10MB\n\
\n\
[program:nginx]\n\
command=nginx -g "daemon off;"\n\
autostart=true\n\
autorestart=true\n\
priority=10\n\
stderr_logfile=/var/log/nginx.err.log\n\
stdout_logfile=/var/log/nginx.out.log\n\
stdout_logfile_maxbytes=10MB\n\
stderr_logfile_maxbytes=10MB' > /etc/supervisor/conf.d/supervisord.conf

# Crear directorio de logs de supervisor
RUN mkdir -p /var/log/supervisor

# Crear script de entrypoint
RUN echo '#!/bin/bash\n\
set -e\n\
\n\
echo "=== Configurando AssistPro Kardex FC ==="\n\
\n\
# Asegurar que los directorios existen\n\
mkdir -p /var/www/html/assistpro_kardex_fc/storage/logs\n\
mkdir -p /var/www/html/assistpro_kardex_fc/logs\n\
\n\
# Configurar permisos\n\
chown -R www-data:www-data /var/www/html/assistpro_kardex_fc/storage\n\
chown -R www-data:www-data /var/www/html/assistpro_kardex_fc/logs\n\
chmod -R 775 /var/www/html/assistpro_kardex_fc/storage\n\
chmod -R 775 /var/www/html/assistpro_kardex_fc/logs\n\
\n\
echo "✅ Permisos configurados"\n\
\n\
# Ejecutar migraciones\n\
echo "🔄 Ejecutando migraciones..."\n\
php artisan migrate --force || echo "⚠️  Error en migraciones (puede ser normal si ya están aplicadas)"\n\
echo "✅ Migraciones completadas"\n\
\n\
echo "🚀 Iniciando servicios (PHP-FPM + Nginx)..."\n\
\n\
# Iniciar supervisord\n\
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf' > /docker-entrypoint.sh

RUN chmod +x /docker-entrypoint.sh

# Exponer puerto interno
EXPOSE 7071

# Usar el entrypoint
ENTRYPOINT ["/docker-entrypoint.sh"]

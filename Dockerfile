FROM php:8.2-fpm

WORKDIR /var/www/app

# System deps for common Laravel requirements
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        git \
        unzip \
        libpng-dev \
        libonig-dev \
        libxml2-dev \
        libpq-dev \
        nginx \
        supervisor \
    && docker-php-ext-install \
        pdo_mysql \
        pdo_pgsql \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
    && rm -rf /var/lib/apt/lists/*

# Install Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy app and install PHP dependencies
COPY . .
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Ensure Laravel can write cache/logs
RUN chown -R www-data:www-data /var/www/app/storage /var/www/app/bootstrap/cache

# Nginx config optimized for SSE endpoints (disable buffering).
RUN rm -f /etc/nginx/sites-enabled/default \
    && printf '%s\n' \
    'server {' \
    '    listen 8000;' \
    '    server_name _;' \
    '    root /var/www/app/public;' \
    '    index index.php;' \
    '' \
    '    location / {' \
    '        try_files $uri $uri/ /index.php?$query_string;' \
    '    }' \
    '' \
    '    location ~ \\.php$ {' \
    '        include fastcgi_params;' \
    '        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;' \
    '        fastcgi_pass 127.0.0.1:9000;' \
    '        fastcgi_read_timeout 3600;' \
    '    }' \
    '' \
    '    location ~* /(stream|events)$ {' \
    '        include fastcgi_params;' \
    '        fastcgi_param SCRIPT_FILENAME $document_root/index.php;' \
    '        fastcgi_param SCRIPT_NAME /index.php;' \
    '        fastcgi_param PATH_INFO $uri;' \
    '        fastcgi_pass 127.0.0.1:9000;' \
    '        fastcgi_read_timeout 3600;' \
    '        fastcgi_buffering off;' \
    '        fastcgi_keep_conn on;' \
    '    }' \
    '}' \
    > /etc/nginx/conf.d/default.conf

RUN printf '%s\n' \
    '[supervisord]' \
    'nodaemon=true' \
    '' \
    '[program:php-fpm]' \
    'command=docker-php-entrypoint php-fpm' \
    'autorestart=true' \
    '' \
    '[program:nginx]' \
    'command=nginx -g "daemon off;"' \
    'autorestart=true' \
    > /etc/supervisor/conf.d/supervisord.conf

EXPOSE 8000
CMD ["supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

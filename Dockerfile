FROM php:8.2-fpm-alpine AS base

# Install system dependencies & PHP extensions required by CodeIgniter 4
RUN apk add --no-cache \
    nginx \
    supervisor \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    icu-dev \
    oniguruma-dev \
    sqlite-dev \
    bash \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        gd \
        intl \
        mbstring \
        pdo \
        pdo_mysql \
        pdo_sqlite \
        zip \
        bcmath \
        opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files and install production dependencies
COPY composer.json composer.lock ./
RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader --no-scripts

# Copy project files
COPY . .

# Set permissions for CodeIgniter writable folder
RUN chown -R www-data:www-data /var/www/html/writable /var/www/html/public

# Nginx config for CodeIgniter 4
RUN mkdir -p /run/nginx /etc/supervisor/conf.d
COPY <<'EOF' /etc/nginx/http.d/default.conf
server {
    listen 80;
    server_name _;
    root /var/www/html/public;
    index index.php index.html;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass 127.0.0.1:9000;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
EOF

# Supervisor config to run both PHP-FPM and Nginx
COPY <<'EOF' /etc/supervisor/conf.d/supervisord.conf
[supervisord]
nodaemon=true
user=root

[program:php-fpm]
command=php-fpm -F
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0

[program:nginx]
command=nginx -g 'daemon off;'
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
EOF

EXPOSE 80

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]

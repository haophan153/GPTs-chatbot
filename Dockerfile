FROM php:8.3-fpm-alpine

LABEL maintainer="GPTs-chatbot"

# Install system dependencies
RUN apk add --no-cache \
    nginx \
    supervisor \
    libzip \
    libpng \
    libjpeg-turbo \
    freetype \
    icu \
    libxml2 \
    curl \
    fcgi \
    bash \
    git \
    unzip \
    oniguruma \
    postgresql-client \
    && apk add --no-cache --virtual .build-deps \
    build-base \
    autoconf \
    g++ \
    gcc \
    make \
    linux-headers \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    icu-dev \
    libxml2-dev \
    oniguruma-dev \
    postgresql-dev

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_pgsql \
        mbstring \
        zip \
        pcntl \
        bcmath \
        gd \
        intl \
        opcache \
        xml \
        sockets

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer
ENV PATH="/usr/local/bin:${PATH}"

# Configure PHP
RUN echo "cgi.fix_pathinfo=0" > /usr/local/etc/php/conf.d/docker-custom.ini \
    && echo "upload_max_filesize=100M" >> /usr/local/etc/php/conf.d/docker-custom.ini \
    && echo "post_max_size=100M" >> /usr/local/etc/php/conf.d/docker-custom.ini \
    && echo "memory_limit=256M" >> /usr/local/etc/php/conf.d/docker-custom.ini \
    && echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/docker-custom.ini \
    && echo "opcache.memory_consumption=128" >> /usr/local/etc/php/conf.d/docker-custom.ini \
    && echo "opcache.revalidate_freq=0" >> /usr/local/etc/php/conf.d/docker-custom.ini

# Configure PHP-FPM pool
RUN echo "[www]" > /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm = dynamic" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.max_children = 10" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.start_servers = 2" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.min_spare_servers = 1" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "pm.max_spare_servers = 3" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "listen = 127.0.0.1:9000" >> /usr/local/etc/php-fpm.d/www.conf

# Configure Nginx on port 10000 (Render requirement)
RUN echo "server {" > /etc/nginx/http.d/default.conf \
    && echo "    listen 10000;" >> /etc/nginx/http.d/default.conf \
    && echo "    server_name localhost;" >> /etc/nginx/http.d/default.conf \
    && echo "    root /var/www/html/public;" >> /etc/nginx/http.d/default.conf \
    && echo "    index index.php index.html;" >> /etc/nginx/http.d/default.conf \
    && echo "    charset utf-8;" >> /etc/nginx/http.d/default.conf \
    && echo "    location / {" >> /etc/nginx/http.d/default.conf \
    && echo "        try_files \$uri \$uri/ /index.php?\$query_string;" >> /etc/nginx/http.d/default.conf \
    && echo "    }" >> /etc/nginx/http.d/default.conf \
    && echo "    location ~ \\.php\$ {" >> /etc/nginx/http.d/default.conf \
    && echo "        fastcgi_pass 127.0.0.1:9000;" >> /etc/nginx/http.d/default.conf \
    && echo "        fastcgi_index index.php;" >> /etc/nginx/http.d/default.conf \
    && echo "        fastcgi_param SCRIPT_FILENAME \$document_root\$fastcgi_script_name;" >> /etc/nginx/http.d/default.conf \
    && echo "        include fastcgi_params;" >> /etc/nginx/http.d/default.conf \
    && echo "        fastcgi_read_timeout 300;" >> /etc/nginx/http.d/default.conf \
    && echo "    }" >> /etc/nginx/http.d/default.conf \
    && echo "    location ~ /\\. {" >> /etc/nginx/http.d/default.conf \
    && echo "        deny all;" >> /etc/nginx/http.d/default.conf \
    && echo "    }" >> /etc/nginx/http.d/default.conf \
    && echo "}" >> /etc/nginx/http.d/default.conf

# Configure Supervisor
RUN echo "[supervisord]" > /etc/supervisord.conf \
    && echo "nodaemon=true" >> /etc/supervisord.conf \
    && echo "logfile=/var/log/supervisor/supervisord.log" >> /etc/supervisord.conf \
    && echo "pidfile=/var/run/supervisord.pid" >> /etc/supervisord.conf \
    && echo "user=root" >> /etc/supervisord.conf \
    && echo "" >> /etc/supervisord.conf \
    && echo "[program:php-fpm]" >> /etc/supervisord.conf \
    && echo "command=php-fpm" >> /etc/supervisord.conf \
    && echo "autostart=true" >> /etc/supervisord.conf \
    && echo "autorestart=true" >> /etc/supervisord.conf \
    && echo "stdout_logfile=/dev/stdout" >> /etc/supervisord.conf \
    && echo "stdout_logfile_maxbytes=0" >> /etc/supervisord.conf \
    && echo "stderr_logfile=/dev/stderr" >> /etc/supervisord.conf \
    && echo "stderr_logfile_maxbytes=0" >> /etc/supervisord.conf \
    && echo "" >> /etc/supervisord.conf \
    && echo "[program:nginx]" >> /etc/supervisord.conf \
    && echo "command=nginx -g 'daemon off;'" >> /etc/supervisord.conf \
    && echo "autostart=true" >> /etc/supervisord.conf \
    && echo "autorestart=true" >> /etc/supervisord.conf \
    && echo "stdout_logfile=/dev/stdout" >> /etc/supervisord.conf \
    && echo "stdout_logfile_maxbytes=0" >> /etc/supervisord.conf \
    && echo "stderr_logfile=/dev/stderr" >> /etc/supervisord.conf \
    && echo "stderr_logfile_maxbytes=0" >> /etc/supervisord.conf

WORKDIR /var/www/html

# Copy application
COPY . .

# Install composer deps (production)
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Create required directories
RUN mkdir -p bootstrap/cache storage/logs storage/framework/sessions storage/framework/views storage/framework/cache storage/debugbar \
    && chmod -R 775 bootstrap/cache storage

# Clean up build deps
RUN apk del .build-deps

EXPOSE 10000

CMD ["/usr/bin/supervisord", "-c", "/etc/supervisord.conf"]

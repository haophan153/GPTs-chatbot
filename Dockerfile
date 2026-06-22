FROM dunglas/frankenphp:php8.4

# Install system deps
RUN install-php-extensions \
    pdo_mysql \
    pdo_pgsql \
    mbstring \
    zip \
    pcntl \
    bcmath \
    gd \
    intl \
    opcache \
    xml \
    redis

WORKDIR /app

# Copy app
COPY . .

# Install deps
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Permissions
RUN chmod -R 775 bootstrap/cache storage \
    && mkdir -p bootstrap/cache

# Caddyfile for FrankenPHP
RUN echo '{ auto_https off }' > /etc/caddy/Caddyfile \
    && echo ':80 {' >> /etc/caddy/Caddyfile \
    && echo '    root * /app/public' >> /etc/caddy/Caddyfile \
    && echo '    php_server' >> /etc/caddy/Caddyfile \
    && echo '}' >> /etc/caddy/Caddyfile

EXPOSE 80 443

CMD ["frankenphp", "run", "--config", "/etc/caddy/Caddyfile"]

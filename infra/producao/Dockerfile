# Imagem única da aplicação (web, horizon e scheduler mudam só o comando).
# Contexto de build: platform/   ->   docker build -f infra/producao/Dockerfile platform
# FrankenPHP em vez de PHP-FPM + nginx: um processo só serve PHP e estáticos, sem socket nem volume compartilhado.

# 1) Dependências PHP (o Ziggy do front importa de vendor/, então vem antes dos assets)
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist --no-interaction --ignore-platform-reqs

# 2) Assets
FROM node:22-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY . .
COPY --from=vendor /app/vendor vendor
RUN npm run build

# 3) Aplicação
FROM dunglas/frankenphp:1-php8.4 AS app
RUN install-php-extensions pdo_pgsql redis intl gd zip bcmath opcache pcntl
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
ENV SERVER_NAME=:8080 APP_ENV=production XDG_CONFIG_HOME=/tmp XDG_DATA_HOME=/tmp
WORKDIR /app
COPY --from=vendor /app/vendor vendor
COPY . .
COPY --from=assets /app/public/build public/build
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-vidra.ini
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative --no-scripts \
    && php artisan package:discover --ansi \
    && mkdir -p storage/framework/cache storage/framework/sessions storage/framework/views storage/logs bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache
COPY docker/Caddyfile /etc/caddy/Caddyfile
USER www-data
EXPOSE 8080
HEALTHCHECK --interval=30s --timeout=5s CMD curl -fs http://localhost:8080/up || exit 1

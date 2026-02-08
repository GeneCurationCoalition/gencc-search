# -- PHP dependency stage (PHP 7.4 platform) --
FROM php:7.4-cli-alpine AS vendor

ENV COMPOSER_ALLOW_SUPERUSER=1

WORKDIR /app

# Tools needed by Composer + extensions required by locked deps
RUN apk add --no-cache git unzip \
  && apk add --no-cache \
    freetype libjpeg-turbo libpng libzip oniguruma \
  && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    freetype-dev libjpeg-turbo-dev libpng-dev libzip-dev oniguruma-dev \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install bcmath gd mbstring pdo_mysql zip \
  && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-interaction --prefer-dist --optimize-autoloader

# -- Asset build stage --
#
# Note: this app uses Laravel Mix 5 / Webpack 4, which is not compatible with
# OpenSSL 3 without extra flags. Using Node 16 avoids needing NODE_OPTIONS.
FROM node:16-bullseye-slim AS assets
WORKDIR /app
COPY package.json package-lock.json ./
RUN npm ci
COPY resources ./resources
COPY public ./public
COPY webpack.mix.js tailwind.config.js ./
RUN npm run production

# -- Production stage --
FROM php:7.4-fpm-alpine

# Build argument for application version
ARG APP_VERSION=dev
ENV APP_VERSION=${APP_VERSION}

WORKDIR /var/www/html

# System deps + runtime services (nginx + supervisord)
RUN apk add --no-cache \
    nginx \
    supervisor \
    freetype-dev \
    libjpeg-turbo-dev \
    libpng-dev \
    libzip-dev \
    oniguruma-dev \
  && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
  && docker-php-ext-configure gd --with-freetype --with-jpeg \
  && docker-php-ext-install bcmath gd mbstring pdo_mysql zip \
  && apk del .build-deps

# Create an unprivileged user for the app processes
RUN addgroup -g 1000 -S www && adduser -u 1000 -S -D -G www www

# PHP-FPM pool configuration (optimized for ~100 concurrent users)
COPY docker/php-fpm-pool.conf /usr/local/etc/php-fpm.d/www.conf

# Copy application code (without vendor/node_modules; see .dockerignore)
COPY . .

# Bring in built dependencies + assets
COPY --from=vendor /app/vendor/ ./vendor/
COPY --from=assets /app/public/ ./public/

# PHP configuration (increase memory limit for large submitters)
COPY docker/php.ini /usr/local/etc/php/conf.d/app.ini

# Nginx + supervisor config
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/supervisord.conf /etc/supervisord.conf
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod +x /entrypoint.sh

# Writable dirs
RUN mkdir -p storage bootstrap/cache \
  && chown -R www:www storage bootstrap/cache

EXPOSE 80

ENTRYPOINT ["/entrypoint.sh"]
CMD ["supervisord", "-c", "/etc/supervisord.conf"]

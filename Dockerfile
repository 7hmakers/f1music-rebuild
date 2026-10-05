# Frontend builder
FROM node:16-slim as frontend

RUN npm config set registry https://registry.npmmirror.com/ && \
    npm install -g pnpm@^8

WORKDIR /app/react

# Files required by pnpm install
COPY react/package.json react/pnpm-lock.yaml ./

RUN pnpm config set registry https://registry.npmmirror.com/ && \
    pnpm install --frozen-lockfile

COPY react/ .

RUN mkdir /app/public

ARG COMMIT_HASH
ARG VITE_SENTRY_DSN

ENV COMMIT_HASH=${COMMIT_HASH:-"unknown"}
ENV VITE_SENTRY_DSN=$VITE_SENTRY_DSN

RUN pnpm build


# Backend dependency builder for production
FROM php:8.2-apache as build

RUN set -eux; \
    for f in /etc/apt/sources.list /etc/apt/sources.list.d/debian.sources; do \
        if [ -f "$f" ]; then \
            sed -i 's|deb.debian.org|mirrors.aliyun.com|g; s|security.debian.org|mirrors.aliyun.com|g' "$f"; \
        fi; \
    done; \
    apt-get update

# Required for composer
RUN apt-get install -y --no-install-recommends git unzip

WORKDIR /app

COPY . .
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN composer install --prefer-dist --no-dev --optimize-autoloader --no-interaction --ignore-platform-req=ext-gmp


# Backend dependency builder for development
FROM php:8.2-apache as build-dev

RUN set -eux; \
    for f in /etc/apt/sources.list /etc/apt/sources.list.d/debian.sources; do \
        if [ -f "$f" ]; then \
            sed -i 's|deb.debian.org|mirrors.aliyun.com|g; s|security.debian.org|mirrors.aliyun.com|g' "$f"; \
        fi; \
    done; \
    apt-get update

# Required for composer
RUN apt-get install -y --no-install-recommends git unzip

WORKDIR /app

COPY . .
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

RUN composer install --prefer-dist --no-interaction --ignore-platform-req=ext-gmp


# Application builder for development
FROM php:8.2-apache as dev

RUN set -eux; \
    for f in /etc/apt/sources.list /etc/apt/sources.list.d/debian.sources; do \
        if [ -f "$f" ]; then \
            sed -i 's|deb.debian.org|mirrors.aliyun.com|g; s|security.debian.org|mirrors.aliyun.com|g' "$f"; \
        fi; \
    done; \
    apt-get update; \
    apt-get install -y --no-install-recommends libgmp-dev; \
    docker-php-ext-install bcmath pdo_mysql gmp; \
    rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY --from=frontend /app/public ./public
COPY --from=build-dev --chown=www-data:www-data ./app .

COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

RUN a2enmod rewrite && \
    mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini" && \
    bash ./docker/configure.sh


# Application builder for production
FROM php:8.2-apache as production

RUN set -eux; \
    for f in /etc/apt/sources.list /etc/apt/sources.list.d/debian.sources; do \
        if [ -f "$f" ]; then \
            sed -i 's|deb.debian.org|mirrors.aliyun.com|g; s|security.debian.org|mirrors.aliyun.com|g' "$f"; \
        fi; \
    done; \
    apt-get update; \
    apt-get install -y --no-install-recommends libgmp-dev; \
    docker-php-ext-install bcmath pdo_mysql gmp; \
    rm -rf /var/lib/apt/lists/*

WORKDIR /var/www/html

COPY --from=frontend /app/public ./public
COPY --from=build --chown=www-data:www-data /app .

COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf

RUN a2enmod rewrite && \
    mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" && \
    bash ./docker/configure.sh

HEALTHCHECK --interval=30s --timeout=10s --start-period=5s --retries=3 \
    CMD curl -f http://localhost/ || exit 1
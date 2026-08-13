# ═══════════════════════════════════════════════════════════════════
# Insurance 2026 — Multi-stage Production Dockerfile
# ═══════════════════════════════════════════════════════════════════

# ── Stage 1: Build frontend assets ────────────────────────────────
FROM node:22-alpine AS frontend
WORKDIR /build

COPY package.json package-lock.json ./
RUN npm ci --ignore-scripts

COPY vite.config.js jsconfig.json tailwind.config.js* postcss.config.js* ./
COPY resources/ resources/
# Only explicitly public Vite values may enter the frontend build. Production
# environment files and server-side secrets are excluded by .dockerignore.
ARG VITE_APP_NAME="Insurance 2026"
ARG VITE_APP_URL=""
ARG VITE_AUDIT="false"
ARG VITE_MOJAZ_FEE="119"
ARG VITE_QUOTE_DIAGNOSTICS="false"
ARG VITE_REVERB_APP_KEY=""
ARG VITE_REVERB_HOST=""
ARG VITE_REVERB_PORT="443"
ARG VITE_REVERB_SCHEME="https"
ARG VITE_SUPPORT_EMAIL_DOMAIN=""
ENV VITE_APP_NAME=${VITE_APP_NAME} \
    VITE_APP_URL=${VITE_APP_URL} \
    VITE_AUDIT=${VITE_AUDIT} \
    VITE_MOJAZ_FEE=${VITE_MOJAZ_FEE} \
    VITE_QUOTE_DIAGNOSTICS=${VITE_QUOTE_DIAGNOSTICS} \
    VITE_REVERB_APP_KEY=${VITE_REVERB_APP_KEY} \
    VITE_REVERB_HOST=${VITE_REVERB_HOST} \
    VITE_REVERB_PORT=${VITE_REVERB_PORT} \
    VITE_REVERB_SCHEME=${VITE_REVERB_SCHEME} \
    VITE_SUPPORT_EMAIL_DOMAIN=${VITE_SUPPORT_EMAIL_DOMAIN}
RUN npm run build


# ── Stage 2: Install PHP dependencies ────────────────────────────
FROM composer:2 AS vendor
WORKDIR /build

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-interaction \
    --no-scripts \
    --prefer-dist \
    --optimize-autoloader \
    --ignore-platform-reqs

COPY . .
RUN composer dump-autoload --optimize --no-dev --no-scripts


# ── Stage 3: Production runtime ──────────────────────────────────
FROM php:8.4-fpm-alpine AS runtime

# Build-time version tracking (passed from deploy.sh)
ARG APP_BUILD_SHA=unknown
ENV APP_BUILD_SHA=${APP_BUILD_SHA}
LABEL org.opencontainers.image.revision=${APP_BUILD_SHA}

# Install system deps + PHP extensions
RUN apk add --no-cache \
    icu-libs \
    libzip \
    libpng \
    libjpeg-turbo \
    freetype \
    oniguruma \
    linux-headers \
    curl \
    && apk add --no-cache --virtual .build-deps \
    icu-dev \
    libzip-dev \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    oniguruma-dev \
    $PHPIZE_DEPS \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
    pdo_mysql \
    mbstring \
    intl \
    zip \
    gd \
    bcmath \
    opcache \
    pcntl \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del .build-deps \
    && rm -rf /tmp/pear

# Copy custom PHP config
COPY docker/php/php-production.ini /usr/local/etc/php/conf.d/99-production.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/www.conf

# Copy entrypoint (reads Docker secrets into env vars)
COPY docker/php/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Create non-root user
RUN addgroup -g 1000 -S appuser \
    && adduser -u 1000 -S appuser -G appuser

WORKDIR /var/www/html

# Copy application from vendor stage
COPY --from=vendor --chown=appuser:appuser /build/ .

# Ensure stale hashed Vite assets from the source tree cannot survive the image build.
RUN rm -rf public/build /opt/build-assets/build

# Copy built frontend assets
COPY --from=frontend --chown=appuser:appuser /build/public/build/ public/build/

# Pristine copy outside the volume mount — entrypoint syncs into volume on boot
COPY --from=frontend --chown=appuser:appuser /build/public/build/ /opt/build-assets/build/

# Create required directories and clear stale dev caches
RUN mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache \
    storage/logs \
    bootstrap/cache \
    && rm -f bootstrap/cache/packages.php bootstrap/cache/services.php \
    && chown -R appuser:appuser storage bootstrap/cache \
    && chmod -R 775 storage bootstrap/cache

# Install su-exec for dropping from root to appuser in entrypoint
RUN apk add --no-cache su-exec

# Entrypoint runs as root (reads Docker secrets), then exec's CMD as appuser
EXPOSE 9000

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]

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
# Vite reads VITE_* from process.env to inline at build time.
# Source .env.production so shell resolves ${VAR} references,
# then write resolved VITE_* vars to .env for Vite to pick up.
COPY .env.production .env.production
RUN set -a && . ./.env.production && set +a \
    && env | grep '^VITE_' > .env \
    && npm run build


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

# ── Browsershot runtime: Chromium + Node + puppeteer ─────────────
# Used by AdminPaymentCardExportController::pdf() to render the same
# Blade the admin sees in-browser → byte-perfect visual parity (vs mPDF).
RUN apk add --no-cache \
    chromium \
    nss \
    harfbuzz \
    ca-certificates \
    ttf-freefont \
    font-noto \
    font-noto-arabic \
    nodejs \
    npm

ENV PUPPETEER_SKIP_DOWNLOAD=true \
    PUPPETEER_EXECUTABLE_PATH=/usr/bin/chromium-browser \
    NODE_PATH=/usr/lib/node_modules

RUN npm install -g --omit=dev puppeteer@^23 \
    && npm cache clean --force

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

# Copy built frontend assets
COPY --from=frontend --chown=appuser:appuser /build/public/build/ public/build/

# Pristine copy outside the volume mount — entrypoint syncs into volume on boot
COPY --from=frontend --chown=appuser:appuser /build/public/build/ /opt/build-assets/build/

# Create required directories and clear stale dev caches
RUN mkdir -p storage/framework/sessions storage/framework/views storage/framework/cache \
    storage/logs \
    bootstrap/cache \
    && rm -f bootstrap/cache/packages.php bootstrap/cache/services.php \
    && mv .env.production .env \
    && chown -R appuser:appuser storage bootstrap/cache .env \
    && chmod -R 775 storage bootstrap/cache

# Install su-exec for dropping from root to appuser in entrypoint
RUN apk add --no-cache su-exec

# Entrypoint runs as root (reads Docker secrets), then exec's CMD as appuser
EXPOSE 9000

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]

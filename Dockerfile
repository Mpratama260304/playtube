# ============================================
# PlayTube Laravel Application Dockerfile
# Multi-stage build for production
# ============================================

# Stage 1: Build frontend assets
FROM node:20-alpine AS frontend

WORKDIR /app

# Copy package files
COPY package.json package-lock.json ./

# Install dependencies
RUN npm ci

# Copy source files needed for build
COPY resources/ resources/
COPY vite.config.js postcss.config.js tailwind.config.js ./

# Build frontend assets
RUN npm run build

# ============================================
# Stage 2: PHP/Laravel Application
# ============================================
FROM php:8.4-fpm-alpine AS app

# Install system dependencies
RUN apk add --no-cache \
    git \
    curl \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    zip \
    unzip \
    oniguruma-dev \
    icu-dev \
    ffmpeg \
    sqlite \
    sqlite-dev \
    nginx \
    supervisor

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) \
        pdo_mysql \
        pdo_sqlite \
        mbstring \
        exif \
        pcntl \
        bcmath \
        gd \
        zip \
        intl \
        opcache

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www/html

# Copy composer files first (for caching)
COPY composer.json composer.lock ./

# Install PHP dependencies (no dev for production)
RUN composer install --no-dev --no-scripts --no-autoloader --prefer-dist

# Copy application files
COPY . .

# Copy built frontend assets from stage 1
COPY --from=frontend /app/public/build public/build/

# Generate optimized autoloader
RUN composer dump-autoload --optimize

# Set permissions
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html/storage \
    && chmod -R 755 /var/www/html/bootstrap/cache

# Copy configuration files
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Create storage symlink placeholder script
RUN echo '#!/bin/sh' > /usr/local/bin/start.sh \
    && echo 'set -e' >> /usr/local/bin/start.sh \
    && echo 'cd /var/www/html' >> /usr/local/bin/start.sh \
    && echo '' >> /usr/local/bin/start.sh \
    && echo '# Ensure directories exist with proper permissions' >> /usr/local/bin/start.sh \
    && echo 'mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs' >> /usr/local/bin/start.sh \
    && echo 'mkdir -p database' >> /usr/local/bin/start.sh \
    && echo 'chown -R www-data:www-data storage database bootstrap/cache' >> /usr/local/bin/start.sh \
    && echo 'chmod -R 775 storage database bootstrap/cache' >> /usr/local/bin/start.sh \
    && echo '' >> /usr/local/bin/start.sh \
    && echo '# Create SQLite database if not exists' >> /usr/local/bin/start.sh \
    && echo 'touch database/database.sqlite 2>/dev/null || true' >> /usr/local/bin/start.sh \
    && echo 'chown www-data:www-data database/database.sqlite 2>/dev/null || true' >> /usr/local/bin/start.sh \
    && echo '' >> /usr/local/bin/start.sh \
    && echo '# Generate app key if not set' >> /usr/local/bin/start.sh \
    && echo 'if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then' >> /usr/local/bin/start.sh \
    && echo '  php artisan key:generate --force' >> /usr/local/bin/start.sh \
    && echo 'fi' >> /usr/local/bin/start.sh \
    && echo '' >> /usr/local/bin/start.sh \
    && echo '# Create storage link' >> /usr/local/bin/start.sh \
    && echo 'php artisan storage:link --force 2>/dev/null || true' >> /usr/local/bin/start.sh \
    && echo '' >> /usr/local/bin/start.sh \
    && echo '# Run migrations' >> /usr/local/bin/start.sh \
    && echo 'php artisan migrate --force 2>/dev/null || true' >> /usr/local/bin/start.sh \
    && echo '' >> /usr/local/bin/start.sh \
    && echo '# Cache config (skip if errors)' >> /usr/local/bin/start.sh \
    && echo 'php artisan config:cache 2>/dev/null || true' >> /usr/local/bin/start.sh \
    && echo 'php artisan route:cache 2>/dev/null || true' >> /usr/local/bin/start.sh \
    && echo 'php artisan view:cache 2>/dev/null || true' >> /usr/local/bin/start.sh \
    && echo '' >> /usr/local/bin/start.sh \
    && echo '# Start supervisord' >> /usr/local/bin/start.sh \
    && echo 'exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf' >> /usr/local/bin/start.sh \
    && chmod +x /usr/local/bin/start.sh

# Expose port
EXPOSE 80

# Health check - increased start period for initialization
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=5 \
    CMD curl -f http://localhost/health || exit 1

# Start supervisord
CMD ["/usr/local/bin/start.sh"]

# ============================================
# PlayTube Laravel Application Dockerfile
# Multi-stage build for production
# Updated: Shorts scroll feature support
# ============================================

# Stage 1: Build frontend assets
FROM node:20-alpine AS frontend

WORKDIR /app

# Copy package files
COPY package.json package-lock.json ./

# Install dependencies with clean cache
RUN npm ci --no-audit --no-fund && npm cache clean --force

# Copy source files needed for build
COPY resources/ resources/
COPY vite.config.js postcss.config.js tailwind.config.js ./

# Build frontend assets for production
RUN npm run build

# ============================================
# Stage 2: PHP/Laravel Application
# ============================================
FROM php:8.4-fpm-alpine AS app

# Set environment for non-interactive installs
ENV COMPOSER_ALLOW_SUPERUSER=1

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
    supervisor \
    # Additional tools for video processing
    && rm -rf /var/cache/apk/*

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

# Install PHP Redis extension (phpredis) for queue/cache/session
RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && apk del --no-network .build-deps \
    && rm -rf /tmp/pear

# Verify redis extension is installed
RUN php -m | grep -i redis

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

# Create start script
COPY <<'EOF' /usr/local/bin/start.sh
#!/bin/sh
cd /var/www/html

echo "==> Starting PlayTube initialization..."

# Ensure directories exist
echo "==> Creating directories..."
mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs
mkdir -p database
mkdir -p /var/log/supervisor

# Set permissions
echo "==> Setting permissions..."
chown -R www-data:www-data storage database bootstrap/cache 2>/dev/null || true
chmod -R 775 storage database bootstrap/cache 2>/dev/null || true

# Create SQLite database
echo "==> Creating SQLite database..."
touch database/database.sqlite 2>/dev/null || true
chown www-data:www-data database/database.sqlite 2>/dev/null || true
chmod 664 database/database.sqlite 2>/dev/null || true

# Generate app key if not set
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
  echo "==> Generating APP_KEY..."
  php artisan key:generate --force 2>/dev/null || true
fi

# Create storage link
echo "==> Creating storage link..."
php artisan storage:link --force 2>/dev/null || true

# Run migrations
echo "==> Running migrations..."
php artisan migrate --force 2>/dev/null || true

# Cache config (optional, skip errors)
echo "==> Caching configuration..."
php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true

echo "==> Starting supervisord..."
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
EOF

RUN chmod +x /usr/local/bin/start.sh

# Expose port
EXPOSE 80

# Health check - increased start period for initialization
HEALTHCHECK --interval=30s --timeout=10s --start-period=60s --retries=5 \
    CMD curl -f http://localhost/health || exit 1

# Start supervisord
CMD ["/usr/local/bin/start.sh"]

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
    gettext \
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
COPY docker/nginx-main.conf /etc/nginx/nginx.conf
COPY docker/nginx.conf.template /etc/nginx/templates/default.conf.template
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/queue-worker.sh /usr/local/bin/queue-worker.sh
RUN chmod +x /usr/local/bin/queue-worker.sh

# Ensure nginx directories exist and have correct permissions
RUN mkdir -p /etc/nginx/templates /etc/nginx/http.d /run/nginx /var/lib/nginx/html /var/log/nginx \
    && chown -R root:root /var/lib/nginx /run/nginx /var/log/nginx

# Create start script with Railway-compatible startup and diagnostics
COPY <<'EOF' /usr/local/bin/start.sh
#!/bin/sh
set -e
cd /var/www/html

echo "============================================"
echo "PlayTube Container Startup"
echo "============================================"
echo "Date: $(date)"
echo "PORT=${PORT:-not set}"
echo "APP_ENV=${APP_ENV:-production}"
echo "DB_CONNECTION=${DB_CONNECTION:-sqlite}"
echo "QUEUE_CONNECTION=${QUEUE_CONNECTION:-database}"
echo "============================================"

# ============================================
# 1. Set PORT (Railway provides this)
# ============================================
export PORT="${PORT:-8080}"
echo "==> Using PORT: ${PORT}"

# ============================================
# 2. Directory Setup
# ============================================
echo "==> Creating directories..."
mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs
mkdir -p database
mkdir -p /var/log/supervisor
mkdir -p /run/nginx
mkdir -p /var/lib/nginx/html
mkdir -p /etc/nginx/http.d

# Set permissions
echo "==> Setting permissions..."
chown -R www-data:www-data storage database bootstrap/cache 2>/dev/null || true
chmod -R 775 storage database bootstrap/cache 2>/dev/null || true

# ============================================
# 3. Create .env file
# ============================================
echo "==> Creating .env file..."
cat > .env << 'ENVFILE'
APP_NAME=PlayTube
APP_ENV=production
APP_KEY=
APP_DEBUG=false
LOG_CHANNEL=stderr
LOG_LEVEL=debug
ENVFILE

# ============================================
# 4. Generate Nginx config from template
# ============================================
echo "==> Generating Nginx config for port ${PORT}..."
mkdir -p /etc/nginx/http.d
envsubst '${PORT}' < /etc/nginx/templates/default.conf.template > /etc/nginx/http.d/default.conf

# Show generated config for debugging
echo "==> Generated nginx config:"
cat /etc/nginx/http.d/default.conf | head -20

# Verify nginx config
echo "==> Testing Nginx configuration..."
nginx -t 2>&1 || { echo "ERROR: Nginx config test failed!"; cat /etc/nginx/http.d/default.conf; exit 1; }

# ============================================
# 5. Database Setup (SQLite fallback)
# ============================================
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    echo "==> Creating SQLite database..."
    touch database/database.sqlite 2>/dev/null || true
    chown www-data:www-data database/database.sqlite 2>/dev/null || true
    chmod 664 database/database.sqlite 2>/dev/null || true
fi

# ============================================
# 6. Laravel APP_KEY
# ============================================
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    echo "==> Generating APP_KEY..."
    php artisan key:generate --force
else
    echo "==> Using provided APP_KEY"
    sed -i "s|APP_KEY=.*|APP_KEY=${APP_KEY}|" .env
fi

# ============================================
# 7. Storage Link
# ============================================
echo "==> Creating storage link..."
php artisan storage:link --force 2>/dev/null || true

# ============================================
# 8. Migrations (controlled by RUN_MIGRATIONS)
# ============================================
if [ "${RUN_MIGRATIONS:-true}" = "true" ]; then
    echo "==> Running migrations..."
    php artisan migrate --force
else
    echo "==> Skipping migrations (RUN_MIGRATIONS=${RUN_MIGRATIONS})"
fi

# ============================================
# 9. Seeding (controlled by RUN_SEED)
# ============================================
if [ "${RUN_SEED:-false}" = "true" ]; then
    echo "==> Running database seeders..."
    php artisan db:seed --force
fi

# ============================================
# 10. Laravel Optimization
# ============================================
echo "==> Optimizing Laravel..."
php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true

if [ "${APP_ENV:-production}" = "production" ]; then
    php artisan config:cache 2>/dev/null || true
    php artisan route:cache 2>/dev/null || true
    php artisan view:cache 2>/dev/null || true
fi

# ============================================
# 11. Start Supervisord
# ============================================
echo "============================================"
echo "==> Starting services on port ${PORT}..."
echo "============================================"
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
EOF

RUN chmod +x /usr/local/bin/start.sh

# Expose port (documentation only, Railway uses $PORT)
EXPOSE 8080

# Health check
HEALTHCHECK --interval=30s --timeout=10s --start-period=120s --retries=5 \
    CMD curl -fsS http://127.0.0.1:${PORT:-8080}/health || exit 1

# Start the application
CMD ["/usr/local/bin/start.sh"]

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
COPY docker/nginx.conf.template /etc/nginx/nginx.conf.template
COPY docker/nginx.conf /etc/nginx/http.d/default.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/custom.ini
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/www.conf
COPY docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY docker/queue-worker.sh /usr/local/bin/queue-worker.sh
RUN chmod +x /usr/local/bin/queue-worker.sh

# Create start script with Railway-compatible startup
COPY <<'EOF' /usr/local/bin/start.sh
#!/bin/sh
set -e
cd /var/www/html

echo "==> Starting PlayTube initialization..."
echo "==> Environment: ${APP_ENV:-production}"

# ============================================
# 1. Directory Setup
# ============================================
echo "==> Creating directories..."
mkdir -p storage/app/public storage/framework/cache storage/framework/sessions storage/framework/views storage/logs
mkdir -p database
mkdir -p /var/log/supervisor
mkdir -p /run/nginx

# Set permissions
echo "==> Setting permissions..."
chown -R www-data:www-data storage database bootstrap/cache 2>/dev/null || true
chmod -R 775 storage database bootstrap/cache 2>/dev/null || true

# ============================================
# 2. Create .env file (Railway uses env vars, but Laravel needs .env to exist)
# ============================================
echo "==> Creating .env file from environment variables..."
# Always create/update .env with APP_KEY placeholder for key:generate to work
cat > .env << 'ENVFILE'
# This file is auto-generated at container startup
# Actual values come from Railway environment variables
APP_NAME=PlayTube
APP_ENV=production
APP_KEY=
APP_DEBUG=false
LOG_CHANNEL=stderr
LOG_LEVEL=debug
ENVFILE

# ============================================
# 3. Railway Dynamic Port Configuration
# ============================================
# Railway sets PORT env var, default to 80
export PORT="${PORT:-80}"
echo "==> Configuring Nginx to listen on port ${PORT}..."

# Generate Nginx config from template if it exists
if [ -f /etc/nginx/nginx.conf.template ]; then
    envsubst '${PORT}' < /etc/nginx/nginx.conf.template > /etc/nginx/http.d/default.conf
else
    # Fallback: sed the existing config
    sed -i "s/listen 80;/listen ${PORT};/g" /etc/nginx/http.d/default.conf
    sed -i "s/listen \[::\]:80;/listen [::]:${PORT};/g" /etc/nginx/http.d/default.conf
fi

# ============================================
# 4. Database Setup (SQLite fallback only)
# ============================================
if [ "${DB_CONNECTION:-sqlite}" = "sqlite" ]; then
    echo "==> Creating SQLite database..."
    touch database/database.sqlite 2>/dev/null || true
    chown www-data:www-data database/database.sqlite 2>/dev/null || true
    chmod 664 database/database.sqlite 2>/dev/null || true
fi

# ============================================
# 5. Laravel Application Setup
# ============================================
# Generate app key if not set in environment
if [ -z "$APP_KEY" ] || [ "$APP_KEY" = "" ]; then
    echo "==> Generating APP_KEY..."
    php artisan key:generate --force
else
    echo "==> APP_KEY already set in environment, updating .env..."
    sed -i "s|APP_KEY=.*|APP_KEY=${APP_KEY}|" .env
fi

# Create storage link
echo "==> Creating storage link..."
php artisan storage:link --force 2>/dev/null || true

# ============================================
# 6. Database Migrations (always run on boot)
# ============================================
echo "==> Running migrations..."
php artisan migrate --force 2>&1 || echo "Migration warning (may be ok if tables exist)"

# ============================================
# 7. Conditional Seeding (controlled by env)
# ============================================
if [ "${RUN_SEED:-false}" = "true" ]; then
    echo "==> Running database seeders..."
    php artisan db:seed --force 2>&1 || echo "Seeding warning (may be ok if data exists)"
fi

# ============================================
# 8. Clear and Cache Configuration
# ============================================
echo "==> Optimizing Laravel..."
php artisan config:clear 2>/dev/null || true
php artisan route:clear 2>/dev/null || true
php artisan view:clear 2>/dev/null || true

# Only cache config in production (requires all env vars to be set)
if [ "${APP_ENV:-production}" = "production" ]; then
    php artisan config:cache 2>/dev/null || true
    php artisan route:cache 2>/dev/null || true
    php artisan view:cache 2>/dev/null || true
fi

# ============================================
# 9. Start Services
# ============================================
echo "==> Starting supervisord on port ${PORT}..."
exec /usr/bin/supervisord -n -c /etc/supervisor/conf.d/supervisord.conf
EOF

RUN chmod +x /usr/local/bin/start.sh

# Expose port (Railway will override with $PORT)
EXPOSE 80

# Health check - Railway compatible
HEALTHCHECK --interval=30s --timeout=10s --start-period=90s --retries=5 \
    CMD curl -f http://localhost:${PORT:-80}/health || exit 1

# Start supervisord
CMD ["/usr/local/bin/start.sh"]

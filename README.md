# PlayTube - Video CMS & Sharing Platform

A complete video sharing platform similar to YouTube/PlayTube, built with Laravel 12, Laravel Breeze, Filament v3, and SQLite.

## Features

### Frontend (Public)
- 🏠 **Home Page** - Browse trending and latest videos with category filtering
- 🔍 **Search** - Search videos with filters (relevance, date, views)
- 📁 **Categories** - Browse videos by category
- 📱 **Shorts** - TikTok-style short videos (< 60 seconds)
- 👤 **Channel Pages** - View user profiles, videos, playlists, and about info
- ▶️ **Video Watch** - Full video player with likes, comments, and recommendations

### User Features (Authenticated)
- 📺 **Creator Studio** - Dashboard with analytics, upload videos, manage content
- 📚 **Library** - Watch history, watch later, liked videos
- 📋 **Playlists** - Create and manage playlists
- 🔔 **Notifications** - Subscriber and comment notifications
- 💬 **Messages** - Direct messaging between users
- ⚙️ **Settings** - Profile, password, and account management

### Admin Panel (Filament)
- 👥 **User Management** - CRUD users, assign roles, ban/unban
- 🎬 **Video Management** - Moderate videos, change status
- 📁 **Category Management** - Manage video categories
- 💬 **Comment Moderation** - Review and moderate comments
- 🚩 **Report Management** - Handle user reports
- ⚙️ **Site Settings** - Configure site name, upload limits, registration
- 🔐 **Admin Account** - Change admin username and password

## Tech Stack

- **Backend**: Laravel 12
- **Auth**: Laravel Breeze (Blade + Tailwind CSS)
- **Admin Panel**: Filament v3
- **Database**: SQLite (default)
- **Frontend**: Blade, Tailwind CSS, Alpine.js
- **Build Tool**: Vite

## System Requirements

Before installation, ensure your system meets these requirements:

| Requirement | Version | Required |
|-------------|---------|----------|
| PHP | 8.1+ | ✅ Yes |
| PHP intl extension | - | ✅ Yes |
| PHP pdo_sqlite extension | - | ✅ Yes |
| PHP fileinfo extension | - | ✅ Yes |
| PHP gd extension | - | ⚠️ Recommended |
| Node.js | 18+ | ✅ Yes |
| Composer | 2.x | ✅ Yes |
| FFmpeg | 4.x+ | ⚠️ For video processing |

### Installing PHP intl Extension

The **intl** extension is **required** for Filament admin panel. Without it, you'll see:
```
RuntimeException: The "intl" PHP extension is required to use the [format] method.
```

**Ubuntu/Debian:**
```bash
sudo apt-get update
sudo apt-get install -y php8.3-intl
sudo service php8.3-fpm restart  # If using PHP-FPM
```

**Alpine Linux (Docker):**
```dockerfile
RUN apk add --no-cache icu-dev \
    && docker-php-ext-install intl
```

**Debian-based Docker (php:8.3-fpm):**
```dockerfile
RUN apt-get update \
    && apt-get install -y libicu-dev \
    && docker-php-ext-install intl \
    && apt-get clean
```

**Windows:**
1. Open `php.ini` (find location with `php --ini`)
2. Uncomment or add: `extension=intl`
3. Restart your web server (Apache/Nginx) or PHP

**macOS (Homebrew):**
```bash
brew install php@8.3
# intl is included by default
```

**Verify installation:**
```bash
php -m | grep intl
# Should output: intl
```

## Installation

1. **Clone the repository**
   ```bash
   git clone https://github.com/yourusername/playtube.git
   cd playtube
   ```

2. **Install dependencies**
   ```bash
   composer install
   npm install
   ```

3. **Configure environment**
   ```bash
   cp .env.example .env
   php artisan key:generate
   ```

4. **Run migrations and seed database**
   ```bash
   php artisan migrate --seed
   ```

5. **Create storage link**
   ```bash
   php artisan storage:link
   ```

6. **Build frontend assets**
   ```bash
   npm run build
   ```

7. **Run system health check** ⚠️ Important!
   ```bash
   php artisan app:doctor
   ```
   This command checks all requirements (intl, database, storage, etc.) and shows helpful error messages if something is missing.

8. **Start the server**
   ```bash
   php artisan serve
   ```

## Running in GitHub Codespaces

GitHub Codespaces requires special configuration for Vite dev server because assets must be served over HTTPS through the forwarded port.

### Option 1: Production Build (Recommended - No CORS issues)

The simplest approach - build assets once and serve them through Laravel:

```bash
# Remove hot file to disable Vite dev server detection
rm -f public/hot

# Install dependencies and build production assets
npm install
npm run build

# Start Laravel server
php artisan serve --host 0.0.0.0 --port 8000
```

Open `https://<CODESPACE_NAME>-8000.app.github.dev` - fully styled, no CORS errors.

### Option 2: Vite Dev Server (For active development with HMR)

1. **Start Laravel server (Terminal 1):**
   ```bash
   php artisan serve --host 0.0.0.0 --port 8000
   ```

2. **Start Vite dev server (Terminal 2):**
   ```bash
   npm run dev
   ```

3. **IMPORTANT: Set port 5173 to Public visibility:**
   - Open the **Ports** tab in VS Code
   - Find port `5173` 
   - Right-click → Change Port Visibility → **Public**
   - Without this, CORS will block asset loading!

4. **Access the app:**
   - Open `https://<CODESPACE_NAME>-8000.app.github.dev`

### Troubleshooting Codespaces

| Issue | Solution |
|-------|----------|
| CORS errors / assets not loading | Set port 5173 visibility to **Public** |
| MIME type errors for CSS/JS | Restart Vite: `npm run dev` |
| Stale hot file | `rm -f public/hot && npm run dev` |
| HMR not connecting | Verify WSS to 5173 is allowed (Public visibility) |
| Mixed Content warnings | Both ports must use HTTPS (automatic in Codespaces) |

### How it Works

The `vite.config.js` automatically detects Codespaces via `process.env.CODESPACE_NAME` and configures:

- `cors: { origin: true }` + explicit headers → Allows cross-origin requests
- `origin: https://<CODESPACE>-5173.app.github.dev` → Correct asset URLs  
- `hmr.protocol: 'wss'` → Secure WebSocket for Hot Module Replacement
- `hmr.clientPort: 443` → Routes through Codespaces HTTPS proxy
- `allowedHosts: ['.app.github.dev']` → Accepts Codespaces proxy hosts

### Environment Variables

**In `.env` (for Codespaces):**
```bash
APP_URL=https://<CODESPACE_NAME>-8000.app.github.dev
ASSET_URL=https://<CODESPACE_NAME>-8000.app.github.dev
```

⚠️ **WARNING:** Never set `ASSET_URL` to the 5173 port! This causes MIME type and CORS errors. The Vite dev server URL is handled automatically by `vite.config.js`.

## Default Credentials

### Admin Panel
- **URL**: `http://localhost:8000/admin`
- **Email**: `admin@example.com`
- **Password**: `admin123`

### Test User
- **Email**: `john@example.com`
- **Password**: `password`

## Troubleshooting

### "intl" PHP extension required error

If you see this error when accessing `/admin/users` or `/admin/reports`:
```
RuntimeException: The "intl" PHP extension is required to use the [format] method.
```

**Solution:** Install the intl extension (see [Installing PHP intl Extension](#installing-php-intl-extension) above).

### Quick verification:
```bash
# Check if intl is installed
php -m | grep intl

# Run the doctor command to check all requirements
php artisan app:doctor
```

### Storage permission issues

If uploads fail or images don't show:
```bash
php artisan storage:link
chmod -R 775 storage bootstrap/cache
```

### Database issues

```bash
# Reset database
php artisan migrate:fresh --seed

# Check database connection
php artisan app:doctor
```

## Video Processing

Videos are processed in the background using Laravel Queue. Run the queue worker:

```bash
php artisan queue:work
```

For production, use a process manager like Supervisor.

## Video Streaming Architecture

PlayTube uses optimized MP4 streaming for fast video playback:

### How It Works

1. **Faststart MP4**: Videos are processed with FFmpeg's `-movflags +faststart` flag, which moves the moov atom to the beginning of the file. This allows playback to begin immediately without downloading the entire file.

2. **Multi-Quality Renditions**: Videos are transcoded to multiple qualities (360p, 480p, 720p, 1080p) so users can select appropriate quality for their connection. Mobile devices default to 360p.

3. **HTTP Range Requests (RFC 7233)**: The streaming controller supports byte-range requests, enabling:
   - Instant seeking to any position
   - Efficient bandwidth usage
   - Resume after network interruption

4. **Client-Side Optimizations**:
   - **Preload metadata**: `<video preload="metadata">` loads only video headers initially
   - **Warmup cache fetch**: First 512KB is prefetched via Range request to prime CDN/proxies
   - **Adaptive quality**: Auto-downshifts quality if buffering is detected
   - **Loading skeleton**: Shows immediately with poster image while video initializes

### Streaming Endpoint

```
GET /stream/{video}?quality=360|480|720|1080
```

Headers returned:
- `Accept-Ranges: bytes`
- `Content-Range: bytes 0-999/10000`
- `Content-Type: video/mp4`

### Production Setup

For production, configure Nginx to handle streaming directly:

```nginx
location /stream/ {
    internal;
    alias /path/to/storage/app/videos/;
}
```

Enable X-Accel-Redirect by setting in your `.env`:
```
VIDEO_USE_XACCEL=true
```


## API Endpoints

Basic REST API available at `/api/v1`:

- `GET /api/v1/videos` - List videos
- `GET /api/v1/videos/{id}` - Get video details
- `GET /api/v1/search` - Search videos
- `POST /api/v1/videos/{id}/view` - Record view (auth required)

## Docker Support

Example `Dockerfile` with intl extension:

```dockerfile
FROM php:8.3-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev \
    zip \
    unzip

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd intl

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY . .

RUN composer install --no-dev --optimize-autoloader
```

## License

This project is open-sourced software licensed under the MIT license.

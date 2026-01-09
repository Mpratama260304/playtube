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

## Installation

1. Clone the repository
2. Run `composer install`
3. Run `npm install`
4. Copy `.env.example` to `.env` and configure
5. Run `php artisan key:generate`
6. Run `php artisan migrate --seed`
7. Run `php artisan storage:link`
8. Run `npm run build`
9. Run `php artisan serve`

## Default Credentials

### Admin Panel
- **URL**: `/admin`
- **Username**: `admin`
- **Password**: `admin123`

### Test User
- **Email**: `john@example.com`
- **Password**: `password`

## Video Processing

Videos are processed asynchronously using Laravel's queue system. This includes:
- **Duration extraction** - Getting the video length using FFprobe
- **Thumbnail generation** - Creating a preview image from the video
- **HLS transcoding** - Converting to HTTP Live Streaming format (720p, 480p, 360p)

### Development

Run the queue worker for video processing:
```bash
php artisan queue:work
```

Or process one job at a time:
```bash
php artisan queue:work --once
```

### Production (Recommended)

For production, use a process manager like Supervisor to keep the queue worker running:

**Install Supervisor:**
```bash
sudo apt-get install supervisor
```

**Create config file `/etc/supervisor/conf.d/playtube-worker.conf`:**
```ini
[program:playtube-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/playtube/artisan queue:work --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=2
redirect_stderr=true
stdout_logfile=/path/to/playtube/storage/logs/worker.log
stopwaitsecs=3600
```

**Start Supervisor:**
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start playtube-worker:*
```

### Troubleshooting

**Videos stuck in "processing" status:**
1. Check if queue worker is running: `php artisan queue:work --once`
2. Check for pending jobs: `php artisan tinker` then `DB::table('jobs')->count()`
3. Check failed jobs: `php artisan queue:failed`
4. Retry failed jobs: `php artisan queue:retry all`

**Processing failed:**
1. Check FFmpeg is installed: `which ffmpeg`
2. Check the processing_error field in the videos table
3. Check Laravel logs: `storage/logs/laravel.log`
4. Use the "Retry Processing" button in Studio to re-process

**Thumbnails not showing in admin dashboard:**
1. Ensure storage symlink exists: `php artisan storage:link`
2. Run thumbnail diagnostics: `php artisan debug:thumbnails --limit=20`
3. Run thumbnail doctor: `php artisan thumbnails:doctor --limit=50`
4. Fix missing thumbnails: `php artisan thumbnails:doctor --fix --limit=50`
5. Verify file exists: `ls storage/app/public/videos/{uuid}/thumb.jpg`

**GitHub Codespaces / HTTPS proxy issues:**
- Thumbnails must use relative URLs (e.g., `/storage/videos/...`) not absolute URLs
- Never use `url()` helper for storage paths in Filament ImageColumns
- If thumbnails still show placeholders, check the `has_thumbnail` accessor:
  ```bash
  php artisan tinker
  >>> Video::first()->has_thumbnail
  >>> Video::first()->thumbnail_url
  ```

**Thumbnail Doctor Commands:**
```bash
# Diagnose thumbnail issues (dry run)
php artisan thumbnails:doctor --limit=50

# Fix issues automatically
php artisan thumbnails:doctor --fix --limit=50

# Force regenerate all thumbnails
php artisan thumbnails:doctor --fix --force --limit=50
```

### FFmpeg Installation

PlayTube requires FFmpeg for video transcoding:

**Ubuntu/Debian:**
```bash
sudo apt update && sudo apt install -y ffmpeg
```

**macOS:**
```bash
brew install ffmpeg
```

**Verify installation:**
```bash
ffmpeg -version
```

## License

MIT License

# PlayTube - Quick Start After Major Update

## What Changed?

PlayTube has been completely optimized for **fast video playback** (1-2 seconds instead of 10-15 seconds). HLS has been removed and replaced with progressive MP4 streaming + multi-quality renditions.

## Quick Start

### 1. Run Migrations

```bash
php artisan migrate
```

This adds:
- `stream_path`, `renditions`, `stream_ready` columns to `videos` table
- Updates `video_processing_logs` table schema

### 2. Update Environment Variables

```env
# Development
VIDEO_DELIVERY_DRIVER=php

# Production
VIDEO_DELIVERY_DRIVER=nginx
QUEUE_CONNECTION=redis
CACHE_DRIVER=redis
```

### 3. Start Queue Worker

```bash
# Development
php artisan queue:work --queue=high,default,low

# Production (use Docker Compose)
docker-compose -f docker-compose.prod.yml up -d
```

### 4. Test Video Upload

1. Upload a new video
2. Check processing logs: `php artisan video:probe {video-id}`
3. Watch page should load video in 1-2 seconds
4. Quality selector should show available options (360p, 720p, etc.)

## Key Commands

### Check Video Status

```bash
php artisan video:probe 123
# or
php artisan video:probe 9c3f2e1a-...
```

Shows:
- Processing state and progress
- Stream ready status
- Available renditions
- Faststart check
- File sizes

### Monitor Queue

```bash
# Watch queue in real-time
php artisan queue:monitor

# Process one job manually
php artisan queue:work --once --verbose

# Clear failed jobs
php artisan queue:flush
```

### Check Logs

```bash
# Application logs
tail -f storage/logs/laravel.log

# Docker logs
docker-compose logs -f worker-high
docker-compose logs -f app
```

## How It Works

### Upload Flow

```
1. User uploads video
   ↓
2. ProcessVideoJob (immediate)
   - Extract metadata (duration, resolution)
   - Generate thumbnail
   - Dispatch PrepareStreamMp4Job
   ↓
3. PrepareStreamMp4Job (HIGH priority, 1-3 min)
   - Create faststart MP4 → videos/{uuid}/stream.mp4
   - Set stream_ready = true
   ↓
4. BuildRenditionsJob (DEFAULT priority, 5-10 min)
   - Generate 360p, 480p, 720p, 1080p
   - Store in videos/{uuid}/renditions/
   - Update renditions JSON
```

### Watch Flow

```
1. User opens watch page
   ↓
2. Player loads with optimal quality:
   - Mobile (≤420px): 360p
   - Tablet (≤820px): 480p or 720p
   - Desktop: 720p
   ↓
3. Auto-downshift if buffering occurs
   (3+ stalls → drop one quality level)
   ↓
4. User can manually switch quality
   (preserves currentTime and play state)
```

## Production Deployment

### Docker Compose

```bash
# Build image
docker build -t mpratamamail/playtube:latest .
docker push mpratamamail/playtube:latest

# Deploy
docker-compose -f docker-compose.prod.yml up -d

# Check services
docker-compose ps

# Services running:
# - nginx (port 8080)
# - app (PHP-FPM)
# - worker-high (stream MP4 jobs)
# - worker-default (renditions jobs)
# - redis (queue + cache)
# - db (MariaDB)
```

### Environment Variables (Production)

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=db
DB_DATABASE=playtube
DB_USERNAME=playtube
DB_PASSWORD=your-strong-password

QUEUE_CONNECTION=redis
CACHE_DRIVER=redis
SESSION_DRIVER=redis
REDIS_HOST=redis

VIDEO_DELIVERY_DRIVER=nginx
```

## Troubleshooting

### Video Still Slow

**Check**: Is stream_path ready?
```bash
php artisan video:probe {video-id}
```

**Fix**: If stream_ready is false, check worker logs.

### Buffering on Mobile

**Check**: Is quality selector defaulting to low quality?

**Fix**: Clear localStorage: `localStorage.removeItem('playtube_quality')`

### Processing Jobs Stuck

**Check**: Are workers running?
```bash
docker-compose ps
# OR
ps aux | grep "queue:work"
```

**Fix**: Restart workers
```bash
docker-compose restart worker-high worker-default
```

### ffmpeg Not Found

**Check**:
```bash
which ffmpeg
ffmpeg -version
```

**Fix**: Install ffmpeg in Docker image (already included in base image)

## File Structure

```
playtube/
├── app/
│   ├── Jobs/
│   │   ├── PrepareStreamMp4Job.php (NEW)
│   │   ├── BuildRenditionsJob.php (NEW)
│   │   └── ProcessVideoJob.php (UPDATED)
│   ├── Models/
│   │   └── Video.php (UPDATED)
│   └── Http/Controllers/
│       └── VideoStreamController.php (UPDATED)
├── docker/
│   └── nginx/
│       └── nginx.conf (NEW - X-Accel-Redirect config)
├── docker-compose.prod.yml (NEW)
├── VIDEO_PERFORMANCE.md (NEW - detailed docs)
└── MAJOR_UPDATE_SUMMARY.md (NEW - summary)
```

## Performance Targets

| Metric | Target | Notes |
|--------|--------|-------|
| Time to first frame | < 2 sec | With stream_ready |
| Buffering frequency | < 1 per 10 min | On appropriate quality |
| Quality switch | < 1 sec | Preserves time |
| Mobile startup | < 3 sec | On 360p/480p |

## Next Steps

1. **Upload Test Videos**: Upload videos of different sizes and resolutions
2. **Monitor Processing**: Watch `video_processing_logs` table for job progress
3. **Test Mobile**: Open watch page on mobile device (should default to 360p)
4. **Test Quality Switch**: Try switching between qualities (should preserve time)
5. **Load Test**: Test with multiple concurrent users

## Documentation

- **[VIDEO_PERFORMANCE.md](./VIDEO_PERFORMANCE.md)**: Detailed technical documentation
- **[MAJOR_UPDATE_SUMMARY.md](./MAJOR_UPDATE_SUMMARY.md)**: Complete change summary

## Support

Check logs:
- Laravel: `storage/logs/laravel.log`
- Nginx: `docker-compose logs nginx`
- Workers: `docker-compose logs worker-high worker-default`

Common issues are documented in [VIDEO_PERFORMANCE.md](./VIDEO_PERFORMANCE.md#troubleshooting).

---

**Status**: ✅ All major features implemented and tested  
**Version**: 2.0 - Super Fast Playback  
**Date**: January 10, 2026

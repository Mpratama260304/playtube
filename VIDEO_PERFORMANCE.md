# Video Performance Optimization Guide

## Overview

PlayTube uses an optimized video delivery system designed for fast startup times and smooth playback across all devices, especially mobile. The system eliminates HLS complexity in favor of progressive MP4 streaming with multi-quality renditions.

## How It Works

### 1. Fast-Start MP4 Preparation

When a video is uploaded, the system automatically:

1. **Extracts metadata** (duration, resolution) using `ffprobe`
2. **Generates thumbnail** at 25% position
3. **Dispatches high-priority job** to create fast-start MP4

#### Fast-Start MP4 Job (`PrepareStreamMp4Job`)

- **Priority**: HIGH queue
- **Goal**: Enable instant playback (1-2 seconds)
- **Process**:
  - Transcodes to H.264 + AAC
  - Uses `movflags +faststart` to move moov atom to file start
  - Reasonable bitrate for quick processing
  - Stored as `videos/{uuid}/stream.mp4`

**Result**: Video can start playing immediately without downloading entire file metadata.

### 2. Multi-Quality Renditions

After the fast-start MP4 is ready, a lower-priority job generates multiple quality versions:

#### Renditions Job (`BuildRenditionsJob`)

- **Priority**: DEFAULT queue
- **Qualities Generated**: 360p, 480p, 720p, 1080p (only if source is larger)
- **Storage**: `videos/{uuid}/renditions/{quality}p.mp4`
- **Metadata**: Stored in `videos.renditions` JSON column

**Quality Ladder**:

| Quality | Resolution | Max Bitrate | Use Case |
|---------|-----------|-------------|----------|
| 360p    | 640×360   | 800 kbps    | Mobile default (poor connection) |
| 480p    | 854×480   | 1400 kbps   | Mobile/tablet |
| 720p    | 1280×720  | 2500 kbps   | Desktop default |
| 1080p   | 1920×1080 | 5000 kbps   | High quality (opt-in) |

### 3. Intelligent Video Delivery

#### Development Mode (PHP Streaming)

```php
// VideoStreamController uses StreamedResponse with Range support
return new StreamedResponse(function() use ($path, $start, $length) {
    // ... stream file in chunks
}, 206);
```

**Limitations**: Slower throughput, higher CPU usage.

#### Production Mode (Nginx X-Accel-Redirect)

```php
// Laravel checks permissions and returns redirect
return response('', 200, [
    'X-Accel-Redirect' => '/_protected_storage/videos/...',
]);
```

**Benefits**:
- Nginx handles file serving (sendfile) - extremely fast
- Range requests handled automatically
- Zero PHP overhead after auth check
- Optimal for concurrent users

### 4. Quality Selector

The watch page provides automatic and manual quality selection:

#### Auto Mode (Default)

Chooses initial quality based on device:
- **Mobile (≤420px)**: 360p
- **Tablet (≤820px)**: 480p or 720p
- **Desktop**: 720p

**Auto-Downshift**: If user experiences 3+ buffering events, automatically drops one quality level and shows notification.

#### Manual Mode

User can select any available quality. Preference is saved to `localStorage`.

**Quality Switch Behavior**:
- Preserves `currentTime`
- Preserves play/pause state
- Smooth transition without full page reload

## Performance Metrics

### Target Performance

| Metric | Target | Notes |
|--------|--------|-------|
| Time to first frame | < 2 seconds | On stream_ready videos |
| Buffering frequency | < 1 per 10 min | On appropriate quality |
| Quality switch time | < 1 second | Maintains playback position |
| Mobile startup | < 3 seconds | On 360p/480p |

### Monitoring

Check video processing status in Filament admin:
- **Stream Ready**: Fast-start MP4 available
- **Renditions**: Available quality options
- **Processing Logs**: Detailed job history

## Troubleshooting

### Video Starts Slowly (>10 seconds)

**Diagnosis**:
```bash
# Check if stream MP4 is ready
php artisan tinker
>>> $video = Video::find(123);
>>> $video->stream_ready; // Should be true
>>> $video->stream_path;  // Should have path
```

**Fix**:
```bash
# Reprocess stream MP4
php artisan video:regenerate-stream {video-id}
```

**Root Cause**: If `stream_ready` is false, the original file might not be faststart. Check processing logs.

### Buffering on Mobile

**Diagnosis**:
- Open browser DevTools → Network
- Check which quality is being requested
- If 720p/1080p on mobile = wrong default

**Fix**:
- Ensure quality selector defaults to 360p/480p on mobile
- Check `getDefaultQuality()` in watch page JavaScript
- Clear localStorage: `localStorage.removeItem('playtube_quality')`

### Range Requests Not Working

**Symptoms**: Can't seek video, seeking starts from beginning.

**Check**:
```bash
# Test Range request
curl -I -H "Range: bytes=0-1000" http://localhost/stream/{uuid}
```

**Should return**:
```
HTTP/1.1 206 Partial Content
Content-Range: bytes 0-1000/123456789
Accept-Ranges: bytes
```

**Fix**:
- In development: Check `VideoStreamController::streamViaPhp()`
- In production: Check nginx config has `sendfile on`

### Processing Jobs Stuck

**Diagnosis**:
```bash
# Check queue status
php artisan queue:monitor
php artisan queue:work --once --verbose

# Check logs
tail -f storage/logs/laravel.log
```

**Common Issues**:
- ffmpeg not installed
- Insufficient disk space
- Memory limit too low
- File permissions

**Fix**:
```bash
# Restart workers
docker-compose restart worker-high worker-default

# Check ffmpeg
which ffmpeg
ffmpeg -version

# Clear failed jobs
php artisan queue:flush
```

### Thumbnail Not Showing

**Diagnosis**:
```bash
# Check storage link
ls -la public/storage

# Check file exists
ls storage/app/public/videos/{uuid}/thumb.jpg

# Check permissions
ls -la storage/app/public/videos/
```

**Fix**:
```bash
# Recreate storage link
php artisan storage:link

# Regenerate thumbnail
php artisan video:regenerate-thumbnail {video-id}
```

## Production Deployment

### 1. Docker Compose Setup

```bash
# Use production compose file
docker-compose -f docker-compose.prod.yml up -d

# Check all services running
docker-compose ps

# View logs
docker-compose logs -f app
docker-compose logs -f worker-high
docker-compose logs -f nginx
```

### 2. Environment Variables

Key production settings:

```env
# .env
VIDEO_DELIVERY_DRIVER=nginx  # Use X-Accel-Redirect
QUEUE_CONNECTION=redis       # Fast queue processing
CACHE_DRIVER=redis           # Performance boost

# Optional: External storage (S3, Cloudflare R2)
FILESYSTEM_DISK=s3
AWS_BUCKET=playtube-videos
```

### 3. Nginx Configuration

The production nginx.conf includes:
- X-Accel-Redirect support via `/_protected_storage/`
- Range request handling
- Aggressive caching headers
- sendfile optimization

**Custom Domain**:
```nginx
server {
    listen 443 ssl http2;
    server_name playtube.example.com;
    
    ssl_certificate /path/to/cert.pem;
    ssl_certificate_key /path/to/key.pem;
    
    # ... rest of config
}
```

### 4. Scaling Considerations

**Horizontal Scaling**:
- Add more worker containers: `docker-compose up -d --scale worker-default=3`
- Use external Redis cluster
- Use CDN for video delivery (CloudFlare, BunnyCDN)

**Storage Scaling**:
- Move to object storage (S3, R2, Backblaze B2)
- Keep database and Redis local for low latency
- Use signed URLs for protected content

## Advanced Optimization

### CDN Integration

For maximum performance, serve videos through CDN:

1. Upload renditions to CDN-backed storage (S3 + CloudFront)
2. Update `renditions` JSON with CDN URLs
3. Keep Laravel auth check, return CDN URLs instead of X-Accel

### Adaptive Bitrate (Future)

To implement true adaptive streaming:

1. Generate DASH or HLS manifests from renditions
2. Use dash.js or hls.js player
3. Let player auto-switch based on bandwidth

**Trade-off**: Increased complexity vs current simple approach.

### Monitoring & Analytics

Track key metrics:

```php
// Log playback events
Log::channel('analytics')->info('video_play', [
    'video_id' => $video->id,
    'quality' => $request->get('quality'),
    'user_agent' => $request->userAgent(),
    'time_to_play' => $timeToPlay, // ms
]);
```

Analyze:
- Average time-to-first-frame by quality
- Buffering rate by quality/region
- Most-used quality per device type

## Commands

### Artisan Commands (to be created)

```bash
# Verify video is streamable
php artisan video:probe {video-id}

# Regenerate stream MP4
php artisan video:regenerate-stream {video-id}

# Rebuild all renditions
php artisan video:rebuild-renditions {video-id}

# Reprocess all (stream + renditions + thumbnail)
php artisan video:reprocess {video-id}

# Batch check all videos
php artisan video:check-all
```

## Summary

The optimized video system provides:

✅ **Fast startup**: 1-2 seconds with fast-start MP4  
✅ **Smooth mobile playback**: Auto-defaults to 360p/480p  
✅ **Production-grade delivery**: Nginx X-Accel-Redirect  
✅ **Adaptive quality**: Auto-downshift on buffering  
✅ **Simple architecture**: No HLS complexity  

**Result**: YouTube-like experience without HLS infrastructure.

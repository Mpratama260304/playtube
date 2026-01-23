# Railway Deployment Guide for PlayTube

This guide provides step-by-step instructions for deploying PlayTube to Railway with production-grade configuration.

## Table of Contents

1. [Prerequisites](#prerequisites)
2. [Quick Start](#quick-start)
3. [Architecture Overview](#architecture-overview)
4. [Deployment Steps](#deployment-steps)
5. [Environment Variables](#environment-variables)
6. [Storage Configuration](#storage-configuration)
7. [Go Video Server Setup](#go-video-server-setup)
8. [Health Checks](#health-checks)
9. [Troubleshooting](#troubleshooting)
10. [Test Plan](#test-plan)

---

## Prerequisites

- Railway account ([railway.app](https://railway.app))
- GitHub repository with PlayTube code
- (Optional) Cloudflare R2 or AWS S3 account for persistent storage
- (Optional) Custom domain

---

## Quick Start

### Minimal Deployment (SQLite + Local Storage)

```bash
# 1. Connect your GitHub repo to Railway
# 2. Add these environment variables in Railway dashboard:

APP_NAME=PlayTube
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app.up.railway.app
APP_KEY=base64:... # Generate with: php artisan key:generate --show
RUN_MIGRATIONS=true
RUN_SEED=true
SESSION_SECURE_COOKIE=true
```

> ⚠️ **Warning**: This minimal setup uses ephemeral storage. Uploaded videos will be lost on redeployment!

---

## Architecture Overview

### Recommended Production Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                        Railway                               │
│  ┌─────────────────┐   ┌─────────────────┐                  │
│  │   Laravel App   │   │  Go Video Server │  (optional)     │
│  │   (Main App)    │←──│  (Port 8090)     │                  │
│  │   Port: $PORT   │   └─────────────────┘                  │
│  └────────┬────────┘                                         │
│           │                                                  │
│  ┌────────┴────────┐   ┌─────────────────┐                  │
│  │   PostgreSQL    │   │     Redis       │                  │
│  │   (Plugin)      │   │   (Plugin)      │                  │
│  └─────────────────┘   └─────────────────┘                  │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
                    ┌─────────────────┐
                    │  Cloudflare R2  │
                    │  or AWS S3      │
                    │  (Video Storage)│
                    └─────────────────┘
```

### Services Needed

| Service | Purpose | Required |
|---------|---------|----------|
| Laravel App | Main application | ✅ Yes |
| PostgreSQL Plugin | Database | ✅ Yes (prod) |
| Redis Plugin | Queue/Cache/Session | 🔶 Recommended |
| Go Video Server | High-perf streaming | 🔶 Recommended |
| S3/R2 Storage | Persistent file storage | ✅ Yes (prod) |

---

## Deployment Steps

### Step 1: Create Railway Project

1. Go to [Railway Dashboard](https://railway.app/dashboard)
2. Click "New Project"
3. Select "Deploy from GitHub repo"
4. Choose your PlayTube repository
5. Railway will auto-detect the Dockerfile

### Step 2: Add PostgreSQL

1. In your project, click "New" → "Database" → "PostgreSQL"
2. Railway auto-injects these variables:
   - `DATABASE_URL`
   - `PGHOST`, `PGPORT`, `PGDATABASE`, `PGUSER`, `PGPASSWORD`

### Step 3: Add Redis (Recommended)

1. Click "New" → "Database" → "Redis"
2. Railway auto-injects `REDIS_URL`

### Step 4: Configure Environment Variables

Click on your Laravel service → "Variables" → "Raw Editor" and paste:

```env
# ============================================
# REQUIRED VARIABLES
# ============================================
APP_NAME=PlayTube
APP_ENV=production
APP_DEBUG=false
APP_URL=https://your-app.up.railway.app
APP_KEY=base64:your-generated-key

# ============================================
# DATABASE (auto-configured by Railway)
# ============================================
DB_CONNECTION=pgsql
DB_HOST=${PGHOST}
DB_PORT=${PGPORT}
DB_DATABASE=${PGDATABASE}
DB_USERNAME=${PGUSER}
DB_PASSWORD=${PGPASSWORD}

# ============================================
# REDIS (auto-configured by Railway)
# ============================================
REDIS_URL=${REDIS_URL}
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis

# ============================================
# SECURITY
# ============================================
SESSION_SECURE_COOKIE=true

# ============================================
# MIGRATIONS (set to true on FIRST deploy only)
# ============================================
RUN_MIGRATIONS=true
RUN_SEED=true

# ============================================
# GO VIDEO SERVER (if deployed separately)
# ============================================
USE_GO_VIDEO_SERVER=false
# Or if using separate Go service:
# USE_GO_VIDEO_SERVER=true
# GO_VIDEO_SERVER_URL=http://go-video-server.railway.internal:8090
# GO_VIDEO_SECRET_KEY=your-32-char-minimum-secret-key

# ============================================
# S3 STORAGE (REQUIRED for persistence)
# ============================================
FILESYSTEM_DISK=s3

# For AWS S3:
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=playtube-videos

# For Cloudflare R2:
# AWS_ENDPOINT=https://ACCOUNT_ID.r2.cloudflarestorage.com
# AWS_USE_PATH_STYLE_ENDPOINT=true
```

### Step 5: Configure Health Check

In Railway project settings → Service → Health Check:
- **Path**: `/health`
- **Timeout**: `60s`

### Step 6: Add Custom Domain (Optional)

1. Go to Settings → Domains
2. Add your custom domain
3. Update `APP_URL` to match

---

## Environment Variables Reference

### Core Application

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `APP_NAME` | Yes | PlayTube | Application name |
| `APP_ENV` | Yes | production | Environment |
| `APP_DEBUG` | Yes | false | Debug mode (NEVER true in prod) |
| `APP_URL` | Yes | - | Full HTTPS URL |
| `APP_KEY` | Yes | - | Encryption key (base64:...) |

### Database

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `DB_CONNECTION` | Yes | sqlite | pgsql, mysql, sqlite |
| `DB_HOST` | * | - | Database host |
| `DB_PORT` | * | - | Database port |
| `DB_DATABASE` | * | - | Database name |
| `DB_USERNAME` | * | - | Database user |
| `DB_PASSWORD` | * | - | Database password |

### Redis

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `REDIS_URL` | No | - | Full Redis URL |
| `QUEUE_CONNECTION` | No | database | database, redis |
| `CACHE_STORE` | No | database | database, redis, file |
| `SESSION_DRIVER` | No | file | file, database, redis |

### Security

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `SESSION_SECURE_COOKIE` | Yes | false | true for HTTPS |

### Storage (S3/R2)

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `FILESYSTEM_DISK` | Yes | local | local, s3 |
| `AWS_ACCESS_KEY_ID` | * | - | S3/R2 access key |
| `AWS_SECRET_ACCESS_KEY` | * | - | S3/R2 secret key |
| `AWS_DEFAULT_REGION` | * | us-east-1 | AWS region or "auto" for R2 |
| `AWS_BUCKET` | * | - | Bucket name |
| `AWS_ENDPOINT` | R2 | - | R2 endpoint URL |
| `AWS_USE_PATH_STYLE_ENDPOINT` | R2 | false | true for R2 |

### Go Video Server

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `USE_GO_VIDEO_SERVER` | No | true | Enable Go server |
| `GO_VIDEO_SERVER_URL` | * | localhost:8090 | Go server URL |
| `GO_VIDEO_SECRET_KEY` | * | - | Shared secret (min 32 chars) |

### Startup Control

| Variable | Required | Default | Description |
|----------|----------|---------|-------------|
| `RUN_MIGRATIONS` | No | false | Run migrations on boot |
| `RUN_SEED` | No | false | Run seeders on boot |

---

## Storage Configuration

### Option 1: Cloudflare R2 (Recommended)

Cloudflare R2 is S3-compatible with no egress fees.

1. Create R2 bucket in Cloudflare dashboard
2. Generate API token with R2 read/write permissions
3. Configure in Railway:

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-r2-access-key-id
AWS_SECRET_ACCESS_KEY=your-r2-secret-access-key
AWS_DEFAULT_REGION=auto
AWS_BUCKET=playtube-videos
AWS_ENDPOINT=https://YOUR_ACCOUNT_ID.r2.cloudflarestorage.com
AWS_USE_PATH_STYLE_ENDPOINT=true
```

### Option 2: AWS S3

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-aws-access-key
AWS_SECRET_ACCESS_KEY=your-aws-secret-key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=playtube-videos
```

### CORS Configuration for S3/R2

Add this CORS policy to your bucket:

```json
[
  {
    "AllowedOrigins": ["https://your-app.up.railway.app"],
    "AllowedMethods": ["GET", "PUT", "POST", "DELETE", "HEAD"],
    "AllowedHeaders": ["*"],
    "ExposeHeaders": ["ETag"],
    "MaxAgeSeconds": 3600
  }
]
```

---

## Go Video Server Setup

### Option A: Disable Go Server (Simpler)

Set `USE_GO_VIDEO_SERVER=false` and the app will use PHP/Nginx streaming.

### Option B: Separate Railway Service (Recommended for Performance)

1. In your Railway project, click "New" → "GitHub Repo"
2. Select same repo but set **Root Directory**: `video-server`
3. Configure environment:

```env
VIDEO_SERVER_PORT=8090
VIDEO_BASE_PATH=/data/videos
VIDEO_SECRET_KEY=your-32-char-minimum-secret-key
ALLOWED_ORIGINS=https://your-app.up.railway.app
```

4. In Laravel service, add:

```env
USE_GO_VIDEO_SERVER=true
GO_VIDEO_SERVER_URL=http://go-video-server.railway.internal:8090
GO_VIDEO_SECRET_KEY=your-32-char-minimum-secret-key
```

---

## Health Checks

### Endpoints

| Endpoint | Purpose | Expected Response |
|----------|---------|-------------------|
| `/health` | Nginx health check | 200 "healthy" |
| `/up` | Laravel health check | 200 (with cache) |

### Railway Configuration

- Health check path: `/health`
- Health check timeout: 60 seconds
- Start delay: 90 seconds (allow for migrations)

---

## Troubleshooting

### Common Issues

#### 1. "502 Bad Gateway" on first deploy
- **Cause**: App still initializing
- **Fix**: Wait 2-3 minutes for startup to complete

#### 2. Sessions not persisting
- **Cause**: Using file sessions with ephemeral storage
- **Fix**: Set `SESSION_DRIVER=redis` or `SESSION_DRIVER=database`

#### 3. Uploaded files disappear after redeploy
- **Cause**: Railway filesystem is ephemeral
- **Fix**: Configure S3/R2 storage (see Storage Configuration)

#### 4. Video processing fails silently
- **Cause**: PHP functions disabled
- **Fix**: Already fixed in docker/php.ini - ensure you deployed latest

#### 5. Dailymotion embeds not loading on mobile
- **Cause**: Referrer policy issues
- **Fix**: Already fixed - embeds now use `strict-origin-when-cross-origin`

#### 6. Go Video Server unreachable
- **Cause**: Service not deployed or misconfigured
- **Fix**: Check `GO_VIDEO_SERVER_URL`, or set `USE_GO_VIDEO_SERVER=false`

### Viewing Logs

```bash
# Railway CLI
railway logs

# Or in dashboard: Service → Deployments → View Logs
```

---

## Test Plan

### Pre-Deployment Checklist

- [ ] All environment variables configured
- [ ] Database plugin added
- [ ] Redis plugin added (if using)
- [ ] S3/R2 storage configured
- [ ] Custom domain configured (optional)

### Post-Deployment Tests

#### 1. Basic Functionality
- [ ] Homepage loads
- [ ] User registration works
- [ ] Login/logout works
- [ ] Session persists across requests

#### 2. Video Upload (> 512MB)
- [ ] Upload 600MB+ video file
- [ ] Verify upload completes
- [ ] Verify video appears in library
- [ ] Verify video plays back

#### 3. Dailymotion Embed (Mobile)
- [ ] iOS Safari: Embed loads and plays
- [ ] iOS Safari (Private): Embed loads
- [ ] Android Chrome: Embed loads and plays
- [ ] Android Chrome (Incognito): Embed loads
- [ ] Slow network: Fallback UI appears if needed

#### 4. Go Video Server (if enabled)
- [ ] `/health` on Go server returns healthy
- [ ] Video streaming uses Go server
- [ ] Fallback works when Go server is down

#### 5. Error Handling
- [ ] File too large shows proper error
- [ ] Invalid video format rejected
- [ ] Processing failures logged (check admin panel)

### Mobile Embed Test Checklist

Test these scenarios for Dailymotion embeds:

| Test | iOS Safari | iOS Private | Android Chrome | Android Incognito |
|------|------------|-------------|----------------|-------------------|
| Embed loads | ☐ | ☐ | ☐ | ☐ |
| Video plays | ☐ | ☐ | ☐ | ☐ |
| Fullscreen works | ☐ | ☐ | ☐ | ☐ |
| Sound works | ☐ | ☐ | ☐ | ☐ |
| Slow 3G loads | ☐ | ☐ | ☐ | ☐ |
| Fallback shows on error | ☐ | ☐ | ☐ | ☐ |

---

## Migrations & Release Commands

### Recommended: Use Railway Release Command

Instead of `RUN_MIGRATIONS=true`, use Railway's release command:

1. Create `railway.json` in project root:

```json
{
  "$schema": "https://railway.app/railway.schema.json",
  "build": {
    "builder": "DOCKERFILE"
  },
  "deploy": {
    "startCommand": "/usr/local/bin/start.sh",
    "healthcheckPath": "/health",
    "healthcheckTimeout": 60,
    "restartPolicyType": "ON_FAILURE"
  }
}
```

2. Set release command in Railway dashboard:
   - Service → Settings → Deploy → Release Command
   - Command: `php artisan migrate --force`

---

## Security Checklist

- [ ] `APP_DEBUG=false` in production
- [ ] `APP_ENV=production`
- [ ] `APP_URL` uses HTTPS
- [ ] `SESSION_SECURE_COOKIE=true`
- [ ] Strong `APP_KEY` generated
- [ ] Strong `GO_VIDEO_SECRET_KEY` (32+ characters)
- [ ] Database credentials are secure
- [ ] S3/R2 credentials have minimal required permissions

---

## Support

For issues specific to this deployment guide, check:
1. Railway documentation: https://docs.railway.app
2. Laravel deployment docs: https://laravel.com/docs/deployment
3. Project issues on GitHub

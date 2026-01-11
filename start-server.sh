#!/bin/bash

# PlayTube Server Startup Script
# Starts Go Video Server, Laravel, and Queue Worker

echo "🚀 Starting PlayTube Servers..."

# Kill existing processes
echo "Stopping existing servers..."
pkill -f "video-server" 2>/dev/null
pkill -f "php artisan serve" 2>/dev/null
pkill -f "php artisan queue:work" 2>/dev/null
sleep 2

# Start Go Video Server
echo "Starting Go Video Server on port 8090..."
cd /workspaces/playtube/video-server
./video-server &
GO_PID=$!
sleep 1

# Verify Go server is running
if curl -s http://localhost:8090/health > /dev/null 2>&1; then
    echo "✅ Go Video Server started (PID: $GO_PID)"
else
    echo "❌ Go Video Server failed to start"
fi

# Start Laravel Server WITHOUT Xdebug (critical for performance)
echo "Starting Laravel Server on port 8000 (Xdebug disabled)..."
cd /workspaces/playtube
XDEBUG_MODE=off php artisan serve --host=0.0.0.0 --port=8000 &
LARAVEL_PID=$!
sleep 2

# Verify Laravel is running
if curl -s http://localhost:8000/ > /dev/null 2>&1; then
    echo "✅ Laravel Server started (PID: $LARAVEL_PID)"
else
    echo "❌ Laravel Server failed to start"
fi

# Start Queue Worker for background video processing
echo "Starting Queue Worker for video processing..."
cd /workspaces/playtube
XDEBUG_MODE=off nohup php artisan queue:work --queue=high,default --tries=3 --timeout=3600 > /tmp/queue-worker.log 2>&1 &
QUEUE_PID=$!
sleep 1
echo "✅ Queue Worker started (PID: $QUEUE_PID)"

echo ""
echo "=========================================="
echo "🎬 PlayTube is ready!"
echo "=========================================="
echo "Laravel:       http://localhost:8000"
echo "Go Server:     http://localhost:8090"
echo "Queue Worker:  Running (log: /tmp/queue-worker.log)"
echo ""
echo "Note: Xdebug is disabled for optimal performance"
echo "      Video uploads process in background (no timeout!)"
echo ""

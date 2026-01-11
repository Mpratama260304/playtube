#!/bin/bash

# PlayTube Server Startup Script
# Starts both Go Video Server and Laravel with optimized settings

echo "🚀 Starting PlayTube Servers..."

# Kill existing processes
echo "Stopping existing servers..."
pkill -f "video-server" 2>/dev/null
pkill -f "php artisan serve" 2>/dev/null
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
if curl -s http://localhost:8000/health > /dev/null 2>&1; then
    echo "✅ Laravel Server started (PID: $LARAVEL_PID)"
else
    echo "❌ Laravel Server failed to start"
fi

echo ""
echo "=========================================="
echo "🎬 PlayTube is ready!"
echo "=========================================="
echo "Laravel:     http://localhost:8000"
echo "Go Server:   http://localhost:8090"
echo ""
echo "Note: Xdebug is disabled for optimal performance"
echo "      Response times should be <10ms now"
echo ""

#!/bin/bash

# =================================================
# PlayTube Video System Setup Script
# =================================================
# This script sets up the high-performance video
# streaming system with Go Video Server
# =================================================

set -e

echo "🎬 PlayTube Video System Setup"
echo "=============================="
echo ""

# Check for required tools
check_requirements() {
    echo "📋 Checking requirements..."
    
    local missing=()
    
    if ! command -v go &> /dev/null; then
        missing+=("go")
    fi
    
    if ! command -v ffmpeg &> /dev/null; then
        missing+=("ffmpeg")
    fi
    
    if ! command -v docker &> /dev/null; then
        missing+=("docker")
    fi
    
    if [ ${#missing[@]} -gt 0 ]; then
        echo "❌ Missing required tools: ${missing[*]}"
        echo ""
        echo "Please install:"
        for tool in "${missing[@]}"; do
            case $tool in
                go)
                    echo "  - Go: https://golang.org/dl/"
                    ;;
                ffmpeg)
                    echo "  - FFmpeg: sudo apt install ffmpeg"
                    ;;
                docker)
                    echo "  - Docker: https://docs.docker.com/get-docker/"
                    ;;
            esac
        done
        exit 1
    fi
    
    echo "✅ All requirements met"
    echo ""
}

# Build Go Video Server
build_video_server() {
    echo "🔨 Building Go Video Server..."
    
    cd video-server
    
    # Download dependencies
    go mod download
    go mod tidy
    
    # Build binary
    CGO_ENABLED=0 go build -ldflags="-w -s" -o video-server .
    
    echo "✅ Video Server built successfully"
    cd ..
    echo ""
}

# Create HLS directory structure
setup_directories() {
    echo "📁 Setting up directories..."
    
    mkdir -p storage/app/private/videos
    mkdir -p storage/app/private/hls
    mkdir -p storage/app/public/thumbnails
    
    # Set permissions
    chmod -R 775 storage/app/private/videos
    chmod -R 775 storage/app/private/hls
    chmod -R 775 storage/app/public/thumbnails
    
    echo "✅ Directories created"
    echo ""
}

# Generate environment variables
setup_env() {
    echo "⚙️  Configuring environment..."
    
    # Check if .env exists
    if [ ! -f .env ]; then
        cp .env.example .env 2>/dev/null || echo "Warning: .env.example not found"
    fi
    
    # Add Go Video Server config if not exists
    if ! grep -q "USE_GO_VIDEO_SERVER" .env 2>/dev/null; then
        cat >> .env << 'EOF'

# ===========================================
# Go Video Server Configuration
# ===========================================
USE_GO_VIDEO_SERVER=true
GO_VIDEO_SERVER_URL=http://localhost:8090
GO_VIDEO_SECRET_KEY=change-this-to-a-secure-random-key
VIDEO_DELIVERY_DRIVER=go

# HLS Settings
HLS_ENABLED=true
HLS_SEGMENT_DURATION=6
EOF
        echo "✅ Environment variables added to .env"
    else
        echo "ℹ️  Environment variables already configured"
    fi
    echo ""
}

# Start services
start_services() {
    echo "🚀 Starting services..."
    
    # Start Go Video Server in background
    if [ -f video-server/video-server ]; then
        echo "Starting Go Video Server..."
        
        # Kill existing instance if running
        pkill -f "video-server" 2>/dev/null || true
        
        # Start in background
        nohup ./video-server/video-server \
            -port 8090 \
            -video-path "$(pwd)/storage/app/private/videos" \
            -hls-path "$(pwd)/storage/app/private/hls" \
            > storage/logs/video-server.log 2>&1 &
        
        echo "✅ Go Video Server started on port 8090"
        echo "   Logs: storage/logs/video-server.log"
    fi
    
    # Run Laravel queue worker
    echo ""
    echo "To start Laravel queue worker (for HLS generation):"
    echo "  php artisan queue:work --queue=hls,default"
    echo ""
}

# Process existing videos
process_existing_videos() {
    echo "🎥 Processing existing videos..."
    echo ""
    echo "To generate HLS for existing videos, run:"
    echo "  php artisan playtube:generate-hls --all"
    echo ""
    echo "Or for a specific video:"
    echo "  php artisan playtube:generate-hls --video=<uuid>"
    echo ""
}

# Test installation
test_installation() {
    echo "🧪 Testing installation..."
    
    # Test Go Video Server
    if curl -s http://localhost:8090/health > /dev/null 2>&1; then
        echo "✅ Go Video Server is running"
        
        # Show stats
        echo ""
        echo "Server Stats:"
        curl -s http://localhost:8090/stats | head -20
    else
        echo "⚠️  Go Video Server not responding"
        echo "   Try starting it manually: ./video-server/video-server"
    fi
    
    echo ""
}

# Main menu
main() {
    case "${1:-all}" in
        check)
            check_requirements
            ;;
        build)
            build_video_server
            ;;
        setup)
            setup_directories
            setup_env
            ;;
        start)
            start_services
            ;;
        test)
            test_installation
            ;;
        all)
            check_requirements
            build_video_server
            setup_directories
            setup_env
            start_services
            test_installation
            
            echo ""
            echo "=============================="
            echo "🎉 Setup Complete!"
            echo "=============================="
            echo ""
            echo "Next steps:"
            echo "1. Start Laravel: php artisan serve"
            echo "2. Start queue: php artisan queue:work --queue=hls,default"
            echo "3. Visit: http://localhost:8000"
            echo ""
            echo "Documentation: VIDEO_STREAMING.md"
            ;;
        *)
            echo "Usage: $0 {check|build|setup|start|test|all}"
            exit 1
            ;;
    esac
}

main "$@"

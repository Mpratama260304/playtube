#!/bin/bash
# ============================================
# PlayTube Deployment Script
# ============================================
# Usage: ./deploy.sh [build|push|deploy|all]
# ============================================

set -e

# Configuration
DOCKER_USERNAME="${DOCKER_USERNAME:-your-dockerhub-username}"
IMAGE_NAME="${IMAGE_NAME:-playtube}"
IMAGE_TAG="${IMAGE_TAG:-latest}"
FULL_IMAGE_NAME="${DOCKER_USERNAME}/${IMAGE_NAME}:${IMAGE_TAG}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Functions
print_header() {
    echo -e "${BLUE}============================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}============================================${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

# Build Docker image
build() {
    print_header "Building Docker Image"
    
    echo "Building ${FULL_IMAGE_NAME}..."
    docker build -t ${FULL_IMAGE_NAME} .
    
    print_success "Image built successfully: ${FULL_IMAGE_NAME}"
}

# Push to DockerHub
push() {
    print_header "Pushing to DockerHub"
    
    # Check if logged in
    if ! docker info 2>/dev/null | grep -q "Username"; then
        print_warning "Not logged in to Docker Hub. Please login first."
        docker login
    fi
    
    echo "Pushing ${FULL_IMAGE_NAME}..."
    docker push ${FULL_IMAGE_NAME}
    
    print_success "Image pushed successfully to DockerHub"
}

# Deploy (for local testing)
deploy_local() {
    print_header "Deploying Locally"
    
    # Stop existing containers
    docker-compose down 2>/dev/null || true
    
    # Start containers
    docker-compose up -d
    
    print_success "Application deployed locally"
    echo ""
    echo "Access the application at: http://localhost:8080"
    echo "View logs: docker-compose logs -f"
}

# Deploy to remote server
deploy_remote() {
    print_header "Deploying to Remote Server"
    
    if [ -z "$REMOTE_HOST" ]; then
        print_error "REMOTE_HOST environment variable not set"
        echo "Usage: REMOTE_HOST=user@server ./deploy.sh deploy-remote"
        exit 1
    fi
    
    echo "Deploying to ${REMOTE_HOST}..."
    
    # SSH commands to run on remote server
    ssh ${REMOTE_HOST} << EOF
        cd ~/playtube
        
        # Pull latest image
        docker pull ${FULL_IMAGE_NAME}
        
        # Stop and remove old containers
        docker-compose down
        
        # Start new containers
        docker-compose up -d
        
        # Cleanup old images
        docker image prune -f
        
        echo "Deployment complete!"
EOF
    
    print_success "Deployed to ${REMOTE_HOST}"
}

# Show help
show_help() {
    echo "PlayTube Deployment Script"
    echo ""
    echo "Usage: ./deploy.sh [command]"
    echo ""
    echo "Commands:"
    echo "  build          Build Docker image"
    echo "  push           Push image to DockerHub"
    echo "  deploy-local   Deploy locally with docker-compose"
    echo "  deploy-remote  Deploy to remote server (requires REMOTE_HOST)"
    echo "  all            Build, push, and deploy locally"
    echo "  help           Show this help message"
    echo ""
    echo "Environment Variables:"
    echo "  DOCKER_USERNAME  DockerHub username (default: your-dockerhub-username)"
    echo "  IMAGE_NAME       Image name (default: playtube)"
    echo "  IMAGE_TAG        Image tag (default: latest)"
    echo "  REMOTE_HOST      Remote server SSH address (for deploy-remote)"
    echo ""
    echo "Examples:"
    echo "  ./deploy.sh build"
    echo "  DOCKER_USERNAME=myuser ./deploy.sh push"
    echo "  REMOTE_HOST=ubuntu@192.168.1.100 ./deploy.sh deploy-remote"
}

# Main
case "${1:-help}" in
    build)
        build
        ;;
    push)
        build
        push
        ;;
    deploy-local)
        deploy_local
        ;;
    deploy-remote)
        deploy_remote
        ;;
    all)
        build
        push
        deploy_local
        ;;
    help|--help|-h)
        show_help
        ;;
    *)
        print_error "Unknown command: $1"
        show_help
        exit 1
        ;;
esac

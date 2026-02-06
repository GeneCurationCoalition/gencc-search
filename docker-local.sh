#!/usr/bin/env bash
#
# docker-local.sh - Build and run gencc-search container image locally
#
# Usage:
#   ./docker-local.sh build          # Build the image
#   ./docker-local.sh run            # Run the container
#   ./docker-local.sh build-run      # Build and run
#   ./docker-local.sh stop           # Stop the container
#   ./docker-local.sh logs           # View container logs
#   ./docker-local.sh shell          # Shell into running container
#
set -e

# Use podman (can override with CONTAINER_ENGINE=docker)
CONTAINER_ENGINE="${CONTAINER_ENGINE:-podman}"

# Configuration
IMAGE_NAME="gencc-search"
CONTAINER_NAME="gencc-search-local"
LOCAL_PORT="${LOCAL_PORT:-8000}"

# Database configuration (override with environment variables)
# Use host.containers.internal for podman, host.docker.internal for docker
DB_HOST="${DB_HOST:-host.containers.internal}"
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-gencc_sub}"
DB_USERNAME="${DB_USERNAME:-root}"
DB_PASSWORD="${DB_PASSWORD:-password}"

# App configuration
APP_KEY="${APP_KEY:-}"
APP_ENV="${APP_ENV:-local}"
APP_DEBUG="${APP_DEBUG:-true}"
APP_URL="${APP_URL:-http://localhost:${LOCAL_PORT}}"

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

info() {
    echo -e "${GREEN}[INFO]${NC} $1"
}

warn() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

error() {
    echo -e "${RED}[ERROR]${NC} $1"
    exit 1
}

build() {
    info "Building container image: ${IMAGE_NAME}"

    ${CONTAINER_ENGINE} build \
        --tag "${IMAGE_NAME}:latest" \
        --tag "${IMAGE_NAME}:$(git rev-parse --short HEAD 2>/dev/null || echo 'dev')" \
        .

    info "Build complete!"
}

run() {
    # Check if container already exists
    if ${CONTAINER_ENGINE} ps -a --format '{{.Names}}' | grep -q "^${CONTAINER_NAME}$"; then
        warn "Container ${CONTAINER_NAME} already exists. Stopping and removing..."
        ${CONTAINER_ENGINE} stop "${CONTAINER_NAME}" 2>/dev/null || true
        ${CONTAINER_ENGINE} rm "${CONTAINER_NAME}" 2>/dev/null || true
    fi

    # Validate required configuration
    if [ -z "${APP_KEY}" ]; then
        warn "APP_KEY not set. Generating a temporary key..."
        APP_KEY="base64:$(openssl rand -base64 32)"
        info "Generated APP_KEY: ${APP_KEY}"
    fi

    if [ -z "${DB_PASSWORD}" ]; then
        warn "DB_PASSWORD not set. Database connection may fail."
    fi

    info "Starting container: ${CONTAINER_NAME}"
    info "  - Web UI: http://localhost:${LOCAL_PORT}"
    info "  - Database: ${DB_HOST}:${DB_PORT}/${DB_DATABASE}"

    ${CONTAINER_ENGINE} run -d \
        --name "${CONTAINER_NAME}" \
        --publish "${LOCAL_PORT}:80" \
        --env "APP_NAME=GenCC" \
        --env "APP_ENV=${APP_ENV}" \
        --env "APP_KEY=${APP_KEY}" \
        --env "APP_DEBUG=${APP_DEBUG}" \
        --env "APP_URL=${APP_URL}" \
        --env "DB_CONNECTION=mysql" \
        --env "DB_HOST=${DB_HOST}" \
        --env "DB_PORT=${DB_PORT}" \
        --env "DB_DATABASE=${DB_DATABASE}" \
        --env "DB_USERNAME=${DB_USERNAME}" \
        --env "DB_PASSWORD=${DB_PASSWORD}" \
        --env "CACHE_DRIVER=file" \
        --env "SESSION_DRIVER=file" \
        --env "LOG_CHANNEL=stack" \
        "${IMAGE_NAME}:latest"

    info "Container started! View logs with: $0 logs"
}

stop() {
    info "Stopping container: ${CONTAINER_NAME}"
    ${CONTAINER_ENGINE} stop "${CONTAINER_NAME}" 2>/dev/null || warn "Container not running"
    ${CONTAINER_ENGINE} rm "${CONTAINER_NAME}" 2>/dev/null || true
    info "Container stopped and removed"
}

logs() {
    ${CONTAINER_ENGINE} logs -f "${CONTAINER_NAME}"
}

shell() {
    ${CONTAINER_ENGINE} exec -it "${CONTAINER_NAME}" /bin/sh
}

status() {
    if ${CONTAINER_ENGINE} ps --format '{{.Names}}' | grep -q "^${CONTAINER_NAME}$"; then
        info "Container ${CONTAINER_NAME} is running"
        ${CONTAINER_ENGINE} ps --filter "name=${CONTAINER_NAME}" --format "table {{.ID}}\t{{.Status}}\t{{.Ports}}"
    else
        warn "Container ${CONTAINER_NAME} is not running"
    fi
}

# Main
case "${1:-}" in
    build)
        build
        ;;
    run)
        run
        ;;
    build-run)
        build
        run
        ;;
    stop)
        stop
        ;;
    logs)
        logs
        ;;
    shell)
        shell
        ;;
    status)
        status
        ;;
    *)
        echo "Usage: $0 {build|run|build-run|stop|logs|shell|status}"
        echo ""
        echo "Commands:"
        echo "  build      Build the Docker image"
        echo "  run        Run the container (stops existing if running)"
        echo "  build-run  Build and run in one step"
        echo "  stop       Stop and remove the container"
        echo "  logs       Follow container logs"
        echo "  shell      Open a shell in the running container"
        echo "  status     Show container status"
        echo ""
        echo "Environment variables:"
        echo "  LOCAL_PORT    Port to expose (default: 8080)"
        echo "  DB_HOST       Database host (default: host.docker.internal)"
        echo "  DB_PORT       Database port (default: 3306)"
        echo "  DB_DATABASE   Database name (default: gencc_sub)"
        echo "  DB_USERNAME   Database user (default: gencc_search_reader)"
        echo "  DB_PASSWORD   Database password (required for DB access)"
        echo "  APP_KEY       Laravel app key (auto-generated if not set)"
        echo "  APP_ENV       Environment (default: local)"
        echo "  APP_DEBUG     Debug mode (default: true)"
        echo ""
        echo "Example:"
        echo "  DB_PASSWORD=secret ./docker-local.sh build-run"
        exit 1
        ;;
esac

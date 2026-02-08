#!/bin/bash
#
# GenCC Search - Development Server Startup Script
#
# Usage:
#   ./scripts/start-dev-servers.sh          # Start locally with php artisan serve (default)
#   ./scripts/start-dev-servers.sh local    # Start locally with php artisan serve
#   ./scripts/start-dev-servers.sh docker   # Start with Docker/Podman container
#   ./scripts/start-dev-servers.sh stop     # Stop all servers (local and Docker)
#   ./scripts/start-dev-servers.sh status   # Show status (local or Docker)
#   ./scripts/start-dev-servers.sh logs     # View logs (Docker only)
#   ./scripts/start-dev-servers.sh shell    # Shell into Docker container
#   ./scripts/start-dev-servers.sh build    # Build Docker image only
#
# Note: This project uses the gencc-sub database (read-only). No database
# restore or migrations are needed.
#

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Get the directory of this script
SCRIPT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
PROJECT_DIR="$( cd "$SCRIPT_DIR/.." && pwd )"

# Container settings
CONTAINER_NAME="gencc-search-local"
IMAGE_NAME="gencc-search"
LOCAL_PORT="${LOCAL_PORT:-8000}"
ARTISAN_PORT="${ARTISAN_PORT:-8000}"

# PID file for tracking local server
PID_FILE="${PROJECT_DIR}/storage/logs/artisan-serve.pid"

# Database configuration (override with environment variables)
DB_PORT="${DB_PORT:-3306}"
DB_DATABASE="${DB_DATABASE:-gencc_sub}"
DB_USERNAME="${DB_USERNAME:-root}"
DB_PASSWORD="${DB_PASSWORD:-password}"

# App configuration
APP_KEY="${APP_KEY:-}"
APP_ENV="${APP_ENV:-local}"
APP_DEBUG="${APP_DEBUG:-true}"

# Parse command line arguments
MODE="${1:-local}"

# Detect container runtime (prefer podman, fallback to docker)
detect_container_runtime() {
    if command -v podman &> /dev/null; then
        echo "podman"
    elif command -v docker &> /dev/null; then
        echo "docker"
    else
        echo ""
    fi
}

# Check if Podman machine is accessible and restart if needed
ensure_podman_running() {
    if ! command -v podman &> /dev/null; then
        return 0  # Not using podman, skip
    fi

    # Try a simple podman command to test connection
    if podman info &> /dev/null; then
        return 0  # Connection works
    fi

    echo -e "${YELLOW}Podman connection issue detected. Restarting Podman machine...${NC}"

    # Check if machine exists
    if ! podman machine list 2>/dev/null | grep -q "podman-machine"; then
        echo -e "${RED}No Podman machine found. Please run: podman machine init${NC}"
        return 1
    fi

    # Stop and start the machine to reset the socket
    echo -e "${YELLOW}Stopping Podman machine...${NC}"
    podman machine stop 2>/dev/null || true
    sleep 2

    echo -e "${YELLOW}Starting Podman machine...${NC}"
    if ! podman machine start; then
        echo -e "${RED}Failed to start Podman machine.${NC}"
        return 1
    fi

    # Wait for machine to be ready
    sleep 3

    # Verify connection now works
    if podman info &> /dev/null; then
        echo -e "${GREEN}Podman machine restarted successfully.${NC}"
        echo ""
        return 0
    else
        echo -e "${RED}Podman still not responding after restart.${NC}"
        return 1
    fi
}

show_usage() {
    echo -e "${BLUE}GenCC Search - Development Server Script${NC}"
    echo ""
    echo -e "${BLUE}Usage:${NC}"
    echo -e "  $0 [command]"
    echo ""
    echo -e "${BLUE}Commands:${NC}"
    echo -e "  local    Start server locally in background (default)"
    echo -e "  docker   Start server using Docker/Podman container"
    echo -e "  stop     Stop all servers (both local and Docker)"
    echo -e "  status   Show server status"
    echo -e "  logs     View server logs (local or Docker)"
    echo -e "  shell    Open shell in Docker container"
    echo -e "  build    Build Docker image only"
    echo ""
    echo -e "${BLUE}Environment Variables:${NC}"
    echo -e "  LOCAL_PORT     Port for Docker container (default: 8000)"
    echo -e "  ARTISAN_PORT   Port for artisan serve (default: 8000)"
    echo -e "  DB_HOST        Database host (default: 127.0.0.1 for local, host.containers.internal for Docker)"
    echo -e "  DB_DATABASE    Database name (default: gencc_sub)"
    echo -e "  DB_USERNAME    Database username (default: root)"
    echo -e "  DB_PASSWORD    Database password (default: password)"
    echo -e "  APP_KEY        Laravel app key (auto-generated if not set)"
    echo ""
    echo -e "${BLUE}Examples:${NC}"
    echo -e "  $0                           # Start locally in background"
    echo -e "  $0 docker                    # Start Docker container"
    echo -e "  $0 status                    # Check what's running"
    echo -e "  $0 logs                      # View server logs"
    echo -e "  $0 stop                      # Stop all servers"
    echo ""
}

# Function to clear caches
clear_caches() {
    echo -e "${YELLOW}Clearing application caches...${NC}"
    cd "$PROJECT_DIR"
    php artisan config:clear 2>/dev/null || true
    php artisan cache:clear 2>/dev/null || true
    php artisan route:clear 2>/dev/null || true
    php artisan view:clear 2>/dev/null || true
    echo -e "${GREEN}Caches cleared.${NC}"
    echo ""
}

# Function to check if local server is running
is_local_running() {
    # Check PID file first
    if [ -f "$PID_FILE" ]; then
        local pid
        pid=$(cat "$PID_FILE" 2>/dev/null)
        if [ -n "$pid" ] && kill -0 "$pid" 2>/dev/null; then
            return 0
        fi
    fi
    # Also check if port is in use
    if lsof -ti:"${ARTISAN_PORT}" > /dev/null 2>&1; then
        return 0
    fi
    return 1
}

# Function to get local server PID
get_local_pid() {
    if [ -f "$PID_FILE" ]; then
        local pid
        pid=$(cat "$PID_FILE" 2>/dev/null)
        if [ -n "$pid" ] && kill -0 "$pid" 2>/dev/null; then
            echo "$pid"
            return
        fi
    fi
    # Fallback to lsof
    lsof -ti:"${ARTISAN_PORT}" 2>/dev/null | head -1
}

# Function to check if Docker container is running
is_docker_running() {
    CONTAINER_CMD=$(detect_container_runtime)
    if [ -n "$CONTAINER_CMD" ]; then
        if $CONTAINER_CMD ps --format "{{.Names}}" 2>/dev/null | grep -q "^${CONTAINER_NAME}$"; then
            return 0
        fi
    fi
    return 1
}

# Function to start locally with php artisan serve
start_local() {
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}  GenCC Search - Local Mode${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""

    cd "$PROJECT_DIR"

    # Generate version
    APP_VERSION=$("$SCRIPT_DIR/version.sh")
    export APP_VERSION
    echo -e "${YELLOW}Application version: ${APP_VERSION}${NC}"
    echo ""

    # Stop any existing local server on this port
    if is_local_running; then
        echo -e "${YELLOW}Stopping existing server on port ${ARTISAN_PORT}...${NC}"
        stop_local_server
        sleep 1
    fi

    clear_caches

    # Ensure log directory exists
    mkdir -p "$(dirname "$PID_FILE")"

    # Start the server in background, redirect output to log
    echo -e "${YELLOW}Starting Laravel development server in background...${NC}"
    nohup php artisan serve --port="${ARTISAN_PORT}" > storage/logs/artisan-serve.log 2>&1 &
    SERVER_PID=$!

    # Save PID to file
    echo "$SERVER_PID" > "$PID_FILE"

    sleep 2

    # Check if server started successfully
    if kill -0 "$SERVER_PID" 2>/dev/null; then
        echo ""
        echo -e "${GREEN}========================================${NC}"
        echo -e "${GREEN}  Server started successfully!${NC}"
        echo -e "${GREEN}========================================${NC}"
        echo ""
        echo -e "${BLUE}Server URL:${NC} http://127.0.0.1:${ARTISAN_PORT}"
        echo -e "${BLUE}Process ID:${NC} ${SERVER_PID}"
        echo -e "${BLUE}Log file:${NC} storage/logs/artisan-serve.log"
        echo ""
        echo -e "${BLUE}Useful commands:${NC}"
        echo -e "  $0 status    - Check server status"
        echo -e "  $0 stop      - Stop the server"
        echo -e "  tail -f storage/logs/artisan-serve.log  - View server logs"
        echo -e "  tail -f storage/logs/laravel.log        - View Laravel logs"
        echo ""
    else
        rm -f "$PID_FILE"
        echo -e "${RED}Failed to start server!${NC}"
        exit 1
    fi
}

# Function to stop local server
stop_local_server() {
    local pid
    pid=$(get_local_pid)
    if [ -n "$pid" ]; then
        kill "$pid" 2>/dev/null || kill -9 "$pid" 2>/dev/null || true
    fi
    rm -f "$PID_FILE"
}

# Function to build Docker image
docker_build() {
    cd "$PROJECT_DIR"

    CONTAINER_CMD=$(detect_container_runtime)
    if [ -z "$CONTAINER_CMD" ]; then
        echo -e "${RED}Error: Neither podman nor docker found.${NC}"
        exit 1
    fi

    echo -e "${YELLOW}Building container image: ${IMAGE_NAME}${NC}"

    $CONTAINER_CMD build \
        --tag "${IMAGE_NAME}:latest" \
        --tag "${IMAGE_NAME}:$(git rev-parse --short HEAD 2>/dev/null || echo 'dev')" \
        .

    echo -e "${GREEN}Build complete!${NC}"
}

# Function to run Docker container
docker_run() {
    cd "$PROJECT_DIR"

    CONTAINER_CMD=$(detect_container_runtime)

    # Check if container already exists
    if $CONTAINER_CMD ps -a --format '{{.Names}}' | grep -q "^${CONTAINER_NAME}$"; then
        echo -e "${YELLOW}Container ${CONTAINER_NAME} already exists. Stopping and removing...${NC}"
        $CONTAINER_CMD stop "${CONTAINER_NAME}" 2>/dev/null || true
        $CONTAINER_CMD rm "${CONTAINER_NAME}" 2>/dev/null || true
    fi

    # Validate required configuration
    if [ -z "${APP_KEY}" ]; then
        echo -e "${YELLOW}APP_KEY not set. Generating a temporary key...${NC}"
        APP_KEY="base64:$(openssl rand -base64 32)"
    fi

    # Set DB_HOST for container networking
    if [ "$CONTAINER_CMD" = "podman" ]; then
        DB_HOST="${DB_HOST:-host.containers.internal}"
    else
        DB_HOST="${DB_HOST:-host.docker.internal}"
    fi

    APP_URL="http://localhost:${LOCAL_PORT}"

    echo -e "${YELLOW}Starting container: ${CONTAINER_NAME}${NC}"
    echo -e "  - Web UI: http://localhost:${LOCAL_PORT}"
    echo -e "  - Database: ${DB_HOST}:${DB_PORT}/${DB_DATABASE}"

    $CONTAINER_CMD run -d \
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

    echo -e "${GREEN}Container started!${NC}"
}

# Function to stop Docker container
docker_stop() {
    CONTAINER_CMD=$(detect_container_runtime)
    if [ -z "$CONTAINER_CMD" ]; then
        return
    fi

    echo -e "${YELLOW}Stopping container: ${CONTAINER_NAME}${NC}"
    $CONTAINER_CMD stop "${CONTAINER_NAME}" 2>/dev/null || true
    $CONTAINER_CMD rm "${CONTAINER_NAME}" 2>/dev/null || true
    echo -e "${GREEN}Container stopped and removed${NC}"
}

# Function to show Docker container status
docker_status() {
    CONTAINER_CMD=$(detect_container_runtime)
    if [ -z "$CONTAINER_CMD" ]; then
        echo -e "  ${YELLOW}Docker/Podman not installed${NC}"
        return
    fi

    if $CONTAINER_CMD ps --format '{{.Names}}' | grep -q "^${CONTAINER_NAME}$"; then
        echo -e "  ${GREEN}Running${NC}"
        $CONTAINER_CMD ps --filter "name=${CONTAINER_NAME}" --format "table {{.ID}}\t{{.Status}}\t{{.Ports}}"
    else
        echo -e "  ${YELLOW}Not running${NC}"
    fi
}

# Function to show Docker container logs
docker_logs() {
    CONTAINER_CMD=$(detect_container_runtime)
    $CONTAINER_CMD logs -f "${CONTAINER_NAME}"
}

# Function to open shell in Docker container
docker_shell() {
    CONTAINER_CMD=$(detect_container_runtime)
    $CONTAINER_CMD exec -it "${CONTAINER_NAME}" /bin/sh
}

# Function to start with Docker/Podman
start_docker() {
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}  GenCC Search - Docker Mode${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""

    cd "$PROJECT_DIR"

    CONTAINER_CMD=$(detect_container_runtime)
    if [ -z "$CONTAINER_CMD" ]; then
        echo -e "${RED}Error: Neither podman nor docker found.${NC}"
        echo -e "${RED}Please install one of them to use Docker mode.${NC}"
        exit 1
    fi

    echo -e "${YELLOW}Using container runtime: ${CONTAINER_CMD}${NC}"
    echo ""

    # Ensure Podman machine is running (if using Podman)
    if [ "$CONTAINER_CMD" = "podman" ]; then
        if ! ensure_podman_running; then
            echo -e "${RED}Cannot connect to Podman. Please check your Podman installation.${NC}"
            exit 1
        fi
    fi

    # Generate version
    APP_VERSION=$("$SCRIPT_DIR/version.sh")
    export APP_VERSION
    echo -e "${YELLOW}Application version: ${APP_VERSION}${NC}"
    echo ""

    # Build and run
    docker_build
    echo ""
    docker_run

    echo ""
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}  Container started successfully!${NC}"
    echo -e "${GREEN}========================================${NC}"
    echo ""
    echo -e "${BLUE}Server URL:${NC} http://localhost:${LOCAL_PORT}"
    echo ""
    echo -e "${BLUE}Useful commands:${NC}"
    echo -e "  $0 status    - Check container status"
    echo -e "  $0 logs      - View container logs"
    echo -e "  $0 shell     - Open shell in container"
    echo -e "  $0 stop      - Stop the container"
    echo ""
}

# Function to build Docker image only
build_docker() {
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}  GenCC Search - Build Docker Image${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""

    cd "$PROJECT_DIR"

    CONTAINER_CMD=$(detect_container_runtime)
    if [ -z "$CONTAINER_CMD" ]; then
        echo -e "${RED}Error: Neither podman nor docker found.${NC}"
        exit 1
    fi

    echo -e "${YELLOW}Using container runtime: ${CONTAINER_CMD}${NC}"
    echo ""

    # Ensure Podman machine is running (if using Podman)
    if [ "$CONTAINER_CMD" = "podman" ]; then
        if ! ensure_podman_running; then
            echo -e "${RED}Cannot connect to Podman. Please check your Podman installation.${NC}"
            exit 1
        fi
    fi

    docker_build

    echo ""
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}  Build complete!${NC}"
    echo -e "${GREEN}========================================${NC}"
    echo ""
}

# Function to stop all servers
stop_all() {
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}  Stopping All GenCC Search Servers${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""

    cd "$PROJECT_DIR"

    # Stop local server
    echo -e "${YELLOW}Stopping local server...${NC}"
    if is_local_running; then
        stop_local_server
        echo -e "${GREEN}Local server stopped${NC}"
    else
        echo -e "${YELLOW}No local server running${NC}"
    fi
    echo ""

    # Stop Docker container
    CONTAINER_CMD=$(detect_container_runtime)
    if [ -n "$CONTAINER_CMD" ]; then
        echo -e "${YELLOW}Stopping Docker container...${NC}"
        if is_docker_running; then
            docker_stop
        else
            echo -e "${YELLOW}No container running${NC}"
        fi
    fi
    echo ""

    echo -e "${GREEN}All servers stopped.${NC}"
    echo ""
}

# Function to show status
show_status() {
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}  GenCC Search Server Status${NC}"
    echo -e "${BLUE}========================================${NC}"
    echo ""

    cd "$PROJECT_DIR"

    # Check local server
    echo -e "${YELLOW}Local Server:${NC}"
    if is_local_running; then
        local pid
        pid=$(get_local_pid)
        echo -e "  ${GREEN}Running${NC} on port ${ARTISAN_PORT} (PID: ${pid})"
        echo -e "  Log: storage/logs/artisan-serve.log"
    else
        echo -e "  ${YELLOW}Not running${NC}"
    fi
    echo ""

    # Check Docker container
    echo -e "${YELLOW}Docker Container:${NC}"
    docker_status
    echo ""
}

# Function to show logs
show_logs() {
    cd "$PROJECT_DIR"

    # Check if local server is running first
    if is_local_running; then
        echo -e "${BLUE}Showing local server logs (Ctrl+C to exit)...${NC}"
        echo ""
        tail -f storage/logs/artisan-serve.log
        return
    fi

    # Check Docker
    if is_docker_running; then
        echo -e "${BLUE}Showing container logs (Ctrl+C to exit)...${NC}"
        echo ""
        docker_logs
        return
    fi

    echo -e "${YELLOW}No server is currently running.${NC}"
    echo -e "${YELLOW}Start one with: $0 local  or  $0 docker${NC}"
    exit 1
}

# Function to open shell in Docker container
open_shell() {
    cd "$PROJECT_DIR"

    CONTAINER_CMD=$(detect_container_runtime)
    if [ -z "$CONTAINER_CMD" ]; then
        echo -e "${RED}Error: Neither podman nor docker found.${NC}"
        exit 1
    fi

    if is_docker_running; then
        echo -e "${BLUE}Opening shell in container...${NC}"
        echo ""
        docker_shell
    else
        echo -e "${RED}Error: Container is not running.${NC}"
        echo -e "${YELLOW}Start it first with: $0 docker${NC}"
        exit 1
    fi
}

# Main
case "$MODE" in
    local|serve)
        start_local
        ;;
    docker|podman|container)
        start_docker
        ;;
    build)
        build_docker
        ;;
    stop)
        stop_all
        ;;
    status)
        show_status
        ;;
    logs)
        show_logs
        ;;
    shell|bash|sh)
        open_shell
        ;;
    -h|--help|help)
        show_usage
        ;;
    *)
        echo -e "${RED}Unknown command: $MODE${NC}"
        echo ""
        show_usage
        exit 1
        ;;
esac

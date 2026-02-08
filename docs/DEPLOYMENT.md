# GenCC Search Deployment Guide

Complete guide for deploying the GenCC Search application as a container.

## Table of Contents

- [Prerequisites](#prerequisites)
- [Building the Container](#building-the-container)
- [Configuration](#configuration)
- [Memory Sizing](#memory-sizing)
- [Deployment Options](#deployment-options)
- [Health Checks](#health-checks)
- [Monitoring](#monitoring)
- [Troubleshooting](#troubleshooting)

## Prerequisites

- Docker or Podman
- Access to the gencc-sub MySQL database (read-only)
- `.env` file with database credentials

## Building the Container

### Basic Build

```bash
podman build -f Dockerfile -t gencc-search:latest .
```

### Build with Version Tag

```bash
# Version from git (recommended for production)
podman build -f Dockerfile \
  --build-arg APP_VERSION=$(./scripts/version.sh) \
  -t gencc-search:$(./scripts/version.sh) .
```

### Multi-Architecture Build

```bash
# For both AMD64 and ARM64
podman build --platform linux/amd64,linux/arm64 \
  -f Dockerfile -t gencc-search:latest .
```

## Configuration

### Required Environment Variables

| Variable | Description | Example |
| -------- | ----------- | ------- |
| `DB_HOST` | MySQL database host | `db.example.com` |
| `DB_PORT` | MySQL port | `3306` |
| `DB_DATABASE` | Database name | `gencc_sub` |
| `DB_USERNAME` | Database user (read-only) | `gencc_search_reader` |
| `DB_PASSWORD` | Database password | `***` |

### Optional Environment Variables

| Variable | Default | Description |
| -------- | ------- | ----------- |
| `APP_ENV` | `production` | Environment name |
| `APP_DEBUG` | `false` | Enable debug mode |
| `APP_URL` | `http://localhost` | Application URL |
| `LOG_CHANNEL` | `stack` | Log driver (`daily`, `stderr`) |

### Minimal .env File

```env
APP_NAME=GenCC
APP_ENV=production
APP_KEY=base64:YOUR_APP_KEY_HERE
APP_DEBUG=false
APP_URL=https://search.thegencc.org

LOG_CHANNEL=stderr

DB_CONNECTION=mysql
DB_HOST=your-database-host
DB_PORT=3306
DB_DATABASE=gencc_sub
DB_USERNAME=gencc_search_reader
DB_PASSWORD=your-password

CACHE_DRIVER=file
SESSION_DRIVER=file
SESSION_LIFETIME=120
```

Generate `APP_KEY` with: `php artisan key:generate --show`

## Memory Sizing

### Calculation Methodology

The memory requirements were calculated based on:

1. **PHP Memory Profiling**: Measured actual memory usage for different request types:
   - Normal page views: 50-100 MB per PHP process
   - Search queries with large result sets: 100-150 MB
   - CSV/TSV exports (28k+ records): 300-400 MB
   - XLSX exports (Excel with formatting): up to 512 MB

2. **Concurrency Model**: Not all users execute PHP simultaneously:
   - Static assets (CSS, JS, images) served by nginx without PHP
   - Browser think time between requests
   - Page caching reduces PHP execution
   - **Rule of thumb**: 30-50% of concurrent users have active PHP processes

3. **Safety Margin**: Added 20-30% buffer for:
   - PHP-FPM overhead
   - Nginx memory usage (~50 MB)
   - Operating system overhead
   - Temporary memory spikes

### Sizing Table

| Concurrent Users | Active PHP Processes | Memory per Process | Container Memory |
| ---------------- | -------------------- | ------------------ | ---------------- |
| 25 | 10-15 | 150 MB avg | 2 GB |
| 50 | 20-25 | 150 MB avg | 4 GB |
| 100 | 40-50 | 150 MB avg | 8 GB |
| 200 | 80-100 | 150 MB avg | 16 GB |

### Worst Case Scenario

If all users simultaneously trigger XLSX exports:
- 50 processes × 512 MB = 25.6 GB

This is unlikely in practice. The 8 GB recommendation for 100 users assumes:
- 90% normal requests (150 MB average)
- 10% export requests (400 MB average)
- Peak: 45 × 150 MB + 5 × 400 MB = 8.75 GB

### PHP-FPM Configuration

The `docker/php-fpm-pool.conf` file controls worker processes:

```ini
; Adjust based on container memory
pm.max_children = 50        ; Max concurrent PHP processes
pm.start_servers = 10       ; Initial workers
pm.min_spare_servers = 5    ; Minimum idle workers
pm.max_spare_servers = 20   ; Maximum idle workers
```

**Formula**: `max_children = (Container Memory - 500MB) / Average Memory per Process`

For 8 GB container: `(8192 - 500) / 150 ≈ 51` → use 50

## Deployment Options

### Docker / Podman (Single Host)

```bash
podman run -d \
  --name gencc-search \
  --restart unless-stopped \
  -p 8080:80 \
  --memory=8g \
  --memory-swap=8g \
  -v /path/to/.env:/var/www/html/.env:ro \
  gencc-search:latest
```

### Docker Compose

```yaml
version: "3.8"
services:
  gencc-search:
    image: gencc-search:latest
    container_name: gencc-search
    restart: unless-stopped
    ports:
      - "8080:80"
    deploy:
      resources:
        limits:
          memory: 8G
          cpus: "4"
        reservations:
          memory: 4G
          cpus: "1"
    volumes:
      - ./.env:/var/www/html/.env:ro
    environment:
      - DB_HOST=db.example.com
    healthcheck:
      test: ["CMD", "curl", "-f", "http://localhost/-/healthz"]
      interval: 30s
      timeout: 10s
      retries: 3
      start_period: 10s
```

### Kubernetes

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: gencc-search
  labels:
    app: gencc-search
spec:
  replicas: 2
  selector:
    matchLabels:
      app: gencc-search
  template:
    metadata:
      labels:
        app: gencc-search
    spec:
      containers:
        - name: gencc-search
          image: gencc-search:latest
          ports:
            - containerPort: 80
          resources:
            requests:
              memory: "4Gi"
              cpu: "1000m"
            limits:
              memory: "8Gi"
              cpu: "4000m"
          envFrom:
            - secretRef:
                name: gencc-search-env
          livenessProbe:
            httpGet:
              path: /-/healthz
              port: 80
            initialDelaySeconds: 10
            periodSeconds: 30
          readinessProbe:
            httpGet:
              path: /-/healthz
              port: 80
            initialDelaySeconds: 5
            periodSeconds: 10
---
apiVersion: v1
kind: Service
metadata:
  name: gencc-search
spec:
  selector:
    app: gencc-search
  ports:
    - port: 80
      targetPort: 80
  type: ClusterIP
```

### Google Cloud Run

```bash
# Deploy to Cloud Run
gcloud run deploy gencc-search \
  --image=gcr.io/PROJECT_ID/gencc-search:latest \
  --platform=managed \
  --region=us-central1 \
  --memory=8Gi \
  --cpu=4 \
  --concurrency=100 \
  --max-instances=10 \
  --min-instances=1 \
  --port=80 \
  --set-env-vars="DB_HOST=...,DB_DATABASE=gencc_sub" \
  --set-secrets="DB_PASSWORD=gencc-db-password:latest"
```

### AWS ECS / Fargate

Task definition excerpt:

```json
{
  "containerDefinitions": [
    {
      "name": "gencc-search",
      "image": "gencc-search:latest",
      "memory": 8192,
      "cpu": 4096,
      "portMappings": [
        {
          "containerPort": 80,
          "protocol": "tcp"
        }
      ],
      "healthCheck": {
        "command": ["CMD-SHELL", "curl -f http://localhost/-/healthz || exit 1"],
        "interval": 30,
        "timeout": 10,
        "retries": 3
      }
    }
  ]
}
```

## Health Checks

### Endpoint

```
GET /-/healthz
```

Returns `200 OK` with body `ok` when healthy.

### Load Balancer Configuration

Configure your load balancer to:
- Health check path: `/-/healthz`
- Health check interval: 30 seconds
- Healthy threshold: 2 consecutive successes
- Unhealthy threshold: 3 consecutive failures
- Timeout: 10 seconds

## Monitoring

### Container Metrics

```bash
# Docker/Podman
docker stats gencc-search

# Kubernetes
kubectl top pod -l app=gencc-search
```

### Log Access

```bash
# Docker/Podman
docker logs -f gencc-search

# Kubernetes
kubectl logs -f -l app=gencc-search
```

### Key Metrics to Monitor

| Metric | Warning | Critical |
| ------ | ------- | -------- |
| Memory usage | > 70% | > 90% |
| CPU usage | > 80% | > 95% |
| Response time (p95) | > 2s | > 5s |
| Error rate | > 1% | > 5% |

## Troubleshooting

### Container won't start

1. Check logs: `docker logs gencc-search`
2. Verify .env file is mounted and readable
3. Check database connectivity from container network

### Out of Memory (OOM)

1. Increase container memory limit
2. Reduce `pm.max_children` in `docker/php-fpm-pool.conf`
3. Check for memory leaks (restart container periodically)

### Slow Response Times

1. Check database query performance
2. Verify container has sufficient CPU
3. Check if `pm.max_children` is being maxed out
4. Enable slow query logging in PHP-FPM

### Database Connection Errors

1. Verify `DB_HOST` is reachable from container
2. Check firewall rules
3. Verify credentials in `.env`
4. For containers, use `host.containers.internal` or actual IP (not `localhost`)

### SSL/TLS Issues

The container serves HTTP on port 80. For HTTPS:
- Use a reverse proxy (nginx, Traefik, Caddy)
- Use cloud load balancer with SSL termination
- Use Kubernetes Ingress with cert-manager

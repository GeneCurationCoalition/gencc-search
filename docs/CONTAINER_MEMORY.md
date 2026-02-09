# Container Memory Configuration

Guide for configuring memory settings for the GenCC Search container to handle expected traffic loads.

## Memory Requirements by Workload

| Workload | Concurrent Users | Container Memory | PHP-FPM max_children |
| -------- | ---------------- | ---------------- | -------------------- |
| Light | 25 | 2 GB | 15 |
| Medium | 50 | 4 GB | 25 |
| Standard | 100 | 8 GB | 50 |
| High | 200 | 16 GB | 100 |

## How Memory is Used

### Per-Request Memory Usage

| Request Type | Memory per Worker |
| ------------ | ----------------- |
| Normal page views | 50-100 MB |
| Gene search queries | 100-150 MB |
| CSV/TSV exports | 50-100 MB |
| XLSX exports | up to 512 MB |

Note: XLSX exports use batch cell caching (configured in `config/excel.php`) which
reduces memory churn, but PhpSpreadsheet still requires ~500MB for 25K+ records.

### Calculation

Not all concurrent users execute PHP simultaneously:
- Static assets (CSS, JS, images) served directly by nginx
- Browser idle time between requests
- Caching reduces PHP execution

**Rule of thumb:** 30-50% of concurrent users will have active PHP processes at peak.

For 100 concurrent users:
- Peak PHP processes: ~50
- Average memory per process: ~100 MB (mixed workload)
- Worst case (all doing XLSX exports): 50 × 512 MB = 25 GB (unrealistic)
- Realistic peak: 45 × 100 MB + 5 × 512 MB = 7.1 GB
- **Recommended container memory: 8 GB** (allows a few concurrent XLSX exports)

## Configuration Files

### PHP Memory Limit

File: `docker/php.ini`

```ini
; 512M needed for XLSX exports of 25K+ records
memory_limit = 512M
max_execution_time = 300
```

### PHP-FPM Pool Settings

File: `docker/php-fpm-pool.conf`

Key settings to adjust for different workloads:

```ini
; Maximum concurrent PHP processes
pm.max_children = 50

; Starting number of workers
pm.start_servers = 10

; Idle worker range
pm.min_spare_servers = 5
pm.max_spare_servers = 20
```

## Setting Container Memory Limits

### Docker / Podman

```bash
# Run with 8GB memory limit (recommended for 100 users)
podman run --rm -p 8080:80 \
  --memory=8g \
  --memory-swap=8g \
  -e DB_HOST=host.containers.internal \
  -v $(pwd)/.env:/var/www/html/.env:ro \
  gencc-search:latest

# Run with 4GB memory limit (for 50 users)
podman run --rm -p 8080:80 \
  --memory=4g \
  --memory-swap=4g \
  -e DB_HOST=host.containers.internal \
  -v $(pwd)/.env:/var/www/html/.env:ro \
  gencc-search:latest
```

### Kubernetes

```yaml
apiVersion: apps/v1
kind: Deployment
metadata:
  name: gencc-search
spec:
  template:
    spec:
      containers:
        - name: gencc-search
          image: gencc-search:latest
          resources:
            requests:
              memory: "4Gi"
              cpu: "1000m"
            limits:
              memory: "8Gi"
              cpu: "4000m"
```

### Docker Compose

```yaml
services:
  gencc-search:
    image: gencc-search:latest
    deploy:
      resources:
        limits:
          memory: 8G
        reservations:
          memory: 4G
    ports:
      - "8080:80"
```

### Google Cloud Run

```bash
gcloud run deploy gencc-search \
  --image=gcr.io/PROJECT/gencc-search:latest \
  --memory=8Gi \
  --cpu=4 \
  --concurrency=100 \
  --max-instances=10
```

## Adjusting for Different Loads

### Reduce max_children (save memory)

Edit `docker/php-fpm-pool.conf`:

```ini
; For 50 concurrent users with 4GB container
pm.max_children = 25
pm.start_servers = 5
pm.min_spare_servers = 3
pm.max_spare_servers = 10
```

Rebuild and redeploy the container.

### Increase max_children (handle more traffic)

```ini
; For 200 concurrent users with 16GB container
pm.max_children = 100
pm.start_servers = 20
pm.min_spare_servers = 10
pm.max_spare_servers = 40
```

## Monitoring

### Check PHP-FPM status

If you enable the status page in `php-fpm-pool.conf`:

```ini
pm.status_path = /fpm-status
```

Then access: `http://localhost:8080/fpm-status`

### Container memory usage

```bash
# Docker/Podman
docker stats gencc-search

# Kubernetes
kubectl top pod -l app=gencc-search
```

## Troubleshooting

### Out of Memory (OOM) kills

If the container is being killed:
1. Increase container memory limit
2. Reduce `pm.max_children`
3. Consider rate limiting large exports

### Slow response times under load

If response times increase under load:
1. Check if `pm.max_children` is being reached
2. Increase `pm.max_children` and container memory
3. Check database query performance

### Workers not scaling up

If traffic increases but workers don't:
1. Check `pm.max_spare_servers` isn't limiting growth
2. Increase `pm.start_servers` for faster initial capacity

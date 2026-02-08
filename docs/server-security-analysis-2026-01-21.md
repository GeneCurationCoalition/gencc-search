# GenCC Server Security Analysis & Bot Mitigation

**Date:** January 21, 2026 (Updated: February 8, 2026)
**Deployment:** Container with Nginx
**Sites Affected:** search.thegencc.org, search.clinicalgenome.org

---

## Implementation Status

| Recommendation | Status | Notes |
| -------------- | ------ | ----- |
| robots.txt | Implemented | Comprehensive bot blocking in `public/robots.txt` |
| Nginx bot blocking | Pending | Add to nginx.conf in container |
| LOG_CHANNEL=daily | Pending | Set in production .env |
| Session cleanup | N/A | Use ephemeral storage or cron in container |

---

## Executive Summary

Analysis of access logs revealed significant bot and scraper traffic consuming server resources and causing cache/session file bloat. Approximately 40-50% of traffic to search.thegencc.org was from automated scrapers, SEO crawlers, and AI bots.

---

## Problems Identified

### 1. Cache/Session File Bloat

- `storage/framework/cache` and `storage/framework/sessions` directories growing rapidly
- Caused by file-based session driver (`SESSION_DRIVER=file`)
- Each bot request without cookies creates a new session file

#### Why Laravel's Built-in Cleanup Doesn't Keep Up

Laravel uses a **lottery-based garbage collection** system configured in `config/session.php`:

```php
'lifetime' => 120,        // Sessions expire after 120 minutes
'lottery' => [2, 100],    // 2% chance of GC running on each request
```

**How it works:**

- On each request, Laravel "rolls the dice"
- With `[2, 100]`, there's a 2% chance (2 out of 100) that garbage collection runs
- When GC runs, it deletes session files older than `lifetime` minutes

**Why it fails under bot traffic:**

- Low traffic = GC rarely triggers
- High bot traffic with no cookies = creates sessions but bots don't trigger GC (each request is stateless)
- Files accumulate faster than they're cleaned
- The lottery system cannot keep pace with aggressive scrapers

**Recommendation:** Use ephemeral container storage or scheduled cleanup in container.

### 2. Heavy Bot Traffic on search.thegencc.org

| User Agent | Requests | Type |
| ---------- | -------- | ---- |
| Empty (`-`) | 315,408 | Suspicious/Hidden |
| SemrushBot | 44,165 | SEO Crawler |
| python-requests | 38,504 | Scrapers |
| AhrefsBot | 35,889 | SEO Crawler |
| MJ12bot | 30,876 | SEO Crawler (Majestic) |
| DotBot | 23,949 | SEO Crawler (Moz) |
| Barkrowler | 21,468 | SEO Crawler |
| ClaudeBot | 16,918 | AI Crawler (Anthropic) |
| Baiduspider | 16,914 | Chinese Search |
| meta-externalagent | 12,458 | Facebook/Meta |
| Amazonbot | 10,609 | Amazon AI |
| PetalBot | 7,909 | Huawei Search |

### 3. Legitimate Research Traffic (Do Not Block)

These IPs are hospitals and research institutions legitimately accessing gene data:

| IP | Organization | Country |
| -- | ------------ | ------- |
| 192.75.158.166 | Hospital for Sick Children | Canada |
| 205.189.56.186 | University Health Network | Canada |
| 80.232.215.136 | Riga University Hospital | Latvia |
| 203.1.252.70 | Australian Gov OCIO | Australia |
| 130.56.50.117 | AARNet (Australian Research) | Australia |

---

## Actions Taken

### 1. robots.txt (Implemented)

A comprehensive robots.txt is in place at `public/robots.txt` that:

- Allows legitimate search engines (Google, Bing, Apple) with crawl delays
- Blocks SEO crawlers (Semrush, Ahrefs, Moz, Majestic, etc.)
- Blocks AI training bots (GPTBot, ClaudeBot, Amazonbot, etc.)

### 2. Nginx Bot Blocking Rules (Add to Container)

Add to `nginx.conf` or site configuration:

```nginx
server {
    listen 80;
    server_name search.thegencc.org;
    root /var/www/html/public;
    index index.php;

    # Block empty user agents
    if ($http_user_agent = "") {
        return 403;
    }
    if ($http_user_agent = "-") {
        return 403;
    }

    # Block SEO crawlers
    if ($http_user_agent ~* (DotBot|MJ12bot|SemrushBot|AhrefsBot|Barkrowler|PetalBot)) {
        return 403;
    }

    # Block AI bots
    if ($http_user_agent ~* (ClaudeBot|GPTBot|ChatGPT-User|CCBot|Amazonbot)) {
        return 403;
    }

    # Block scrapers
    if ($http_user_agent ~* (python-requests|python-urllib|Scrapy|node-fetch)) {
        return 403;
    }

    # Allow download/export endpoints (skip blocking for these paths)
    location ~ ^/(download|export|api/export) {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Laravel routing
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass php:9000;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

**Alternative: Use a map block for better performance:**

```nginx
# In http block (before server blocks)
map $http_user_agent $is_bad_bot {
    default 0;
    ""      1;
    "-"     1;
    ~*DotBot 1;
    ~*MJ12bot 1;
    ~*SemrushBot 1;
    ~*AhrefsBot 1;
    ~*Barkrowler 1;
    ~*PetalBot 1;
    ~*ClaudeBot 1;
    ~*GPTBot 1;
    ~*ChatGPT-User 1;
    ~*CCBot 1;
    ~*Amazonbot 1;
    ~*python-requests 1;
    ~*python-urllib 1;
    ~*Scrapy 1;
    ~*node-fetch 1;
}

server {
    # ... other config ...

    # Block bad bots (except for download/export paths)
    location / {
        if ($is_bad_bot) {
            return 403;
        }
        try_files $uri $uri/ /index.php?$query_string;
    }
}
```

---

## Container-Specific Recommendations

### 1. Ephemeral Session Storage

In containerized deployments, session files are naturally cleaned when containers restart. However, for long-running containers:

```bash
# Add to container entrypoint or cron
find /var/www/html/storage/framework/sessions -type f -mmin +120 -delete
find /var/www/html/storage/framework/cache/data -type f -mtime +1 -delete
```

Or use a scheduled task in your container orchestration (Kubernetes CronJob, ECS scheduled task, etc.).

### 2. Log Management

Set in production `.env`:

```env
LOG_CHANNEL=daily
```

The `daily` driver in `config/logging.php` automatically keeps only the last 14 days of logs.

For containers that log to stdout/stderr, consider:

```env
LOG_CHANNEL=stderr
```

### 3. Health Check Endpoint

The application has a `/-/healthz` endpoint for container health monitoring.

---

## Useful Commands Reference

### Log Analysis

```bash
# Top user agents from nginx logs
cat /var/log/nginx/access.log | awk -F'"' '{print $6}' | sort | uniq -c | sort -rn | head -20

# Top IPs
cat /var/log/nginx/access.log | awk '{print $1}' | sort | uniq -c | sort -rn | head -20

# Look up IP info
curl -s ipinfo.io/1.2.3.4
```

### Cache/Session Management

```bash
# Count files
find /var/www/html/storage/framework/sessions -type f | wc -l
find /var/www/html/storage/framework/cache -type f | wc -l

# Clear Laravel cache
php artisan cache:clear

# Delete sessions older than 2 hours
find /var/www/html/storage/framework/sessions -type f -mmin +120 -delete

# Delete cache files older than 1 day
find /var/www/html/storage/framework/cache/data -type f -mtime +1 -delete
```

### Nginx Management

```bash
# Test nginx config
nginx -t

# Reload nginx (graceful, no downtime)
nginx -s reload

# View nginx error log
tail -f /var/log/nginx/error.log
```

---

## Notes

- The python-requests traffic from hospitals (SickKids, UHN, etc.) is legitimate research use - do not block
- SEO crawlers (Semrush, Ahrefs, Moz, Majestic) provide no value to your site - safe to block
- AI crawlers (ClaudeBot, GPTBot, ChatGPT) are harvesting data for training - block if desired
- Empty user agent traffic is suspicious and safe to block - legitimate browsers always send a UA
- This application is **read-only** - it connects to the gencc_sub database for data; it does not need database-backed sessions

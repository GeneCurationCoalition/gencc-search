# GenCC Server Security Analysis & Bot Mitigation

**Date:** January 21, 2026
**Server:** web2 (GCP VM running Apache)
**Sites Affected:** search.thegencc.org, search.clinicalgenome.org

---

## Executive Summary

Analysis of Apache access logs revealed significant bot and scraper traffic consuming server resources and causing cache/session file bloat. Approximately 40-50% of traffic to search.thegencc.org was from automated scrapers, SEO crawlers, and AI bots.

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

**Options to make GC more aggressive:**

```php
'lottery' => [2, 100],   // Default: 2% chance
'lottery' => [10, 100],  // More aggressive: 10% chance
'lottery' => [1, 1],     // Every request triggers GC (not recommended - slow)
```

**Recommendation:** Don't rely on the lottery system. Use database sessions or a scheduled cron job for reliable cleanup.

### 2. Heavy Bot Traffic on search.thegencc.org

| User Agent | Requests | Type |
|------------|----------|------|
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

### 3. Specific Bad Actor IPs

| IP | Requests | Source | User Agent |
|----|----------|--------|------------|
| 35.237.78.79 | 64,533 | Google Cloud | Empty UA / Spoofed Firefox |
| 216.244.66.195 | 23,786 | Wowrack | DotBot |
| 34.66.114.108 | 19,729 | Google Cloud | Empty UA |
| 216.73.216.104 | 9,608 | AWS | ClaudeBot |
| 51.161.84.110 | 4,690 | OVH | MJ12bot |
| 54.224.118.241 | 83,330 | AWS | node-fetch (on ClinGen) |

### 4. Legitimate Research Traffic (Do Not Block)

These IPs are hospitals and research institutions legitimately accessing gene data:

| IP | Organization | Country |
|----|--------------|---------|
| 192.75.158.166 | Hospital for Sick Children | Canada |
| 205.189.56.186 | University Health Network | Canada |
| 80.232.215.136 | Riga University Hospital | Latvia |
| 203.1.252.70 | Australian Gov OCIO | Australia |
| 130.56.50.117 | AARNet (Australian Research) | Australia |

---

## Actions Taken

### 1. IP Blocks via UFW

Blocked specific high-volume scraper IPs using UFW (Uncomplicated Firewall). UFW automatically persists rules across reboots.

**IMPORTANT: UFW Rule Order Matters!**

UFW rules are evaluated in order. DENY rules must be placed **before** ALLOW rules, otherwise traffic will match the ALLOW rule first and the DENY rule is never reached.

**Incorrect (DENY after ALLOW - won't work):**

```text
[ 1] 22/tcp      ALLOW    Anywhere
[ 2] 80/tcp      ALLOW    Anywhere       ← Traffic matches here first!
[ 3] 443/tcp    ALLOW    Anywhere
[ 4] Anywhere    DENY     54.224.118.241  ← Never reached
```

**Correct (DENY before ALLOW - works):**

```text
[ 1] Anywhere    DENY     54.224.118.241  ← Checked first, blocks IP
[ 2] Anywhere    DENY     185.100.157.14
[ 3] 22/tcp      ALLOW    Anywhere
[ 4] 80/tcp      ALLOW    Anywhere
[ 5] 443/tcp    ALLOW    Anywhere
```

**Use `insert 1` to add DENY rules at the top:**

```bash
# Block known scraper IPs (insert at position 1 for highest priority)
sudo ufw insert 1 deny from 54.224.118.241  # node-fetch scraper (AWS)
sudo ufw insert 1 deny from 185.100.157.14  # .env probe attempt

# Verify rules and their order
sudo ufw status numbered
```

**Note:** If UFW is not enabled, enable it first (ensure SSH is allowed):

```bash
sudo ufw allow ssh
sudo ufw enable
```

### 2. Apache Bot Blocking Rules (GenCC Only)

Bot blocking rules are placed in the Laravel `.htaccess` file (not the VirtualHost config) to work correctly with Laravel's routing.

**File:** `/var/www/gencc-search/public/.htaccess`

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # If download/export path, skip next 4 blocking rules (match original request)
    # Uses THE_REQUEST to match the original HTTP request line before any rewrites
    RewriteCond %{THE_REQUEST} \s/download/ [OR]
    RewriteCond %{THE_REQUEST} \s/export [OR]
    RewriteCond %{THE_REQUEST} \s/api/export/
    RewriteRule ^ - [S=4]

    # Rule 1 - Block empty user agents
    RewriteCond %{HTTP_USER_AGENT} ^-?$ [OR]
    RewriteCond %{HTTP_USER_AGENT} ^$
    RewriteRule ^ - [F,L]

    # Rule 2 - Block SEO crawlers
    RewriteCond %{HTTP_USER_AGENT} (DotBot|MJ12bot|SemrushBot|AhrefsBot|Barkrowler|PetalBot) [NC]
    RewriteRule ^ - [F,L]

    # Rule 3 - Block AI bots
    RewriteCond %{HTTP_USER_AGENT} (ClaudeBot|GPTBot|ChatGPT-User|CCBot|Amazonbot) [NC]
    RewriteRule ^ - [F,L]

    # Rule 4 - Block scrapers
    RewriteCond %{HTTP_USER_AGENT} (python-requests|python-urllib|Scrapy|node-fetch) [NC]
    RewriteRule ^ - [F,L]

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Handle Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>
```

**Key points:**
- Rules are in `.htaccess`, not VirtualHost config (allows Laravel routing to work)
- Uses `THE_REQUEST` to match the original HTTP request before any URL rewriting
- Uses `[S=4]` (skip) flag to jump over the 4 blocking rules for allowed paths
- Allows `/download/`, `/export`, and `/api/export/` paths for data exports

**VirtualHost config** (`/etc/apache2/sites-enabled/gencc-godaddy-ssl.conf`) should be clean:

```apache
<IfModule mod_ssl.c>
<VirtualHost *:443>
    ServerAdmin webmaster@localhost
    ServerName  search.thegencc.org

    DocumentRoot /var/www/gencc-search/public
    SSLEngine on
    <Directory /var/www/gencc-search/public>
        Options Indexes FollowSymLinks MultiViews
        AllowOverride All
        Require all granted
    </Directory>

    SSLCertificateFile /etc/letsencrypt/live/search.thegencc.org/fullchain.pem
    SSLCertificateKeyFile /etc/letsencrypt/live/search.thegencc.org/privkey.pem
    Include /etc/letsencrypt/options-ssl-apache.conf
</VirtualHost>
</IfModule>
```

Applied with:
```bash
sudo apache2ctl configtest
sudo systemctl reload apache2
```

---

## Recommendations Going Forward

### Immediate Actions

1. **Switch to database session/cache driver** to prevent file bloat:
   ```
   # In .env on production
   SESSION_DRIVER=database
   CACHE_DRIVER=database
   ```

   Run migrations first:
   ```bash
   php artisan session:table
   php artisan cache:table
   php artisan migrate
   ```

2. **Clean up existing session/cache files**:
   ```bash
   find /var/www/gencc-search/storage/framework/sessions -type f -mmin +120 -delete
   find /var/www/gencc-search/storage/framework/cache -type f -mtime +1 -delete
   ```

3. **Create robots.txt** to politely discourage bots (add to `public/robots.txt`):
   ```
   User-agent: SemrushBot
   Disallow: /

   User-agent: AhrefsBot
   Disallow: /

   User-agent: MJ12bot
   Disallow: /

   User-agent: DotBot
   Disallow: /

   User-agent: ClaudeBot
   Disallow: /

   User-agent: GPTBot
   Disallow: /

   User-agent: *
   Allow: /
   ```

### Ongoing Maintenance

1. **Add scheduled cleanup** in `app/Console/Kernel.php`:
   ```php
   protected function schedule(Schedule $schedule)
   {
       // Clean expired cache daily (for database driver)
       $schedule->call(function () {
           DB::table('cache')->where('expiration', '<', time())->delete();
       })->daily();

       // Clean expired sessions daily (for database driver)
       $schedule->call(function () {
           $lifetime = config('session.lifetime') * 60;
           DB::table('sessions')
               ->where('last_activity', '<', time() - $lifetime)
               ->delete();
       })->daily();
   }
   ```

2. **Ensure Laravel scheduler is running** on production:
   ```bash
   # Add to crontab
   * * * * * cd /var/www/gencc-search && php artisan schedule:run >> /dev/null 2>&1
   ```

3. **Monitor bot traffic periodically**:
   ```bash
   # Top user agents this week
   zgrep -h "search.thegencc.org" /var/log/apache2/other_vhosts_access.log* | \
     awk -F'"' '{print $6}' | sort | uniq -c | sort -rn | head -20

   # Top IPs this week
   zgrep -h "search.thegencc.org" /var/log/apache2/other_vhosts_access.log* | \
     awk '{print $2}' | sort | uniq -c | sort -rn | head -20
   ```

4. **Review UFW rules** in 1-2 weeks - can remove IP blocks if Apache rules are sufficient (`sudo ufw status numbered`)

### Consider for Future

1. **Install mod_evasive** for automatic rate limiting:
   ```bash
   sudo apt install libapache2-mod-evasive
   sudo a2enmod evasive
   ```

2. **Use GCP Cloud Armor** for DDoS protection and bot management at the load balancer level

3. **Provide bulk data download** for researchers instead of them scraping - add a link on the site to data exports

---

## Useful Commands Reference

### Log Analysis

```bash
# Top user agents (all logs)
zcat -f /var/log/apache2/other_vhosts_access.log* | awk -F'"' '{print $6}' | sort | uniq -c | sort -rn | head -20

# Top IPs (all logs)
zcat -f /var/log/apache2/other_vhosts_access.log* | awk '{print $2}' | sort | uniq -c | sort -rn | head -20

# Filter by specific vhost
zgrep -h "search.thegencc.org" /var/log/apache2/other_vhosts_access.log* | ...

# Look up IP info
curl -s ipinfo.io/1.2.3.4

# Watch live traffic
tail -f /var/log/apache2/other_vhosts_access.log | grep "search.thegencc.org"
```

### Blocking with UFW

**IMPORTANT:** DENY rules must come before ALLOW rules in UFW. Use `insert 1` to add blocks at the top.

```bash
# Check UFW status
sudo ufw status
sudo ufw status numbered

# Enable UFW (first time only - ensure SSH is allowed!)
sudo ufw allow ssh
sudo ufw enable

# Block an IP (use insert 1 to add at top, before ALLOW rules)
sudo ufw insert 1 deny from 1.2.3.4

# Block an IP range (CIDR)
sudo ufw insert 1 deny from 1.2.3.0/24

# Remove a UFW rule by number
sudo ufw status numbered
sudo ufw delete <rule_number>

# Remove a UFW rule by specification
sudo ufw delete deny from 1.2.3.4

# Move existing DENY rule to top (delete and re-add)
sudo ufw delete deny from 1.2.3.4
sudo ufw insert 1 deny from 1.2.3.4
```

### Apache Management

```bash
# Test Apache config
sudo apache2ctl configtest

# Reload Apache (graceful, no downtime)
sudo systemctl reload apache2
```

### Cache/Session Management

```bash
# Count files
find /var/www/gencc-search/storage/framework/sessions -type f | wc -l
find /var/www/gencc-search/storage/framework/cache -type f | wc -l

# Clear Laravel cache
php artisan cache:clear

# Clear expired cache (database driver)
php artisan tinker --execute="DB::table('cache')->where('expiration', '<', time())->delete()"
```

#### Clearing Stale File-Based Sessions

```bash
# Delete sessions older than 2 hours
find /var/www/gencc-search/storage/framework/sessions -type f -mmin +120 -delete

# Delete sessions older than 1 day
find /var/www/gencc-search/storage/framework/sessions -type f -mtime +1 -delete

# Delete ALL sessions (forces everyone to re-login)
rm -f /var/www/gencc-search/storage/framework/sessions/*

# Check how many remain
find /var/www/gencc-search/storage/framework/sessions -type f | wc -l
```

#### Clearing Stale File-Based Cache

```bash
# Delete cache files older than 1 day
find /var/www/gencc-search/storage/framework/cache/data -type f -mtime +1 -delete

# Delete ALL cache files
rm -rf /var/www/gencc-search/storage/framework/cache/data/*

# Or use artisan
php artisan cache:clear
```

#### Database Session/Cache Cleanup

```bash
# Clear expired sessions (database driver)
php artisan tinker --execute="DB::table('sessions')->where('last_activity', '<', now()->subHours(2)->timestamp)->delete()"

# Clear expired cache (database driver)
php artisan tinker --execute="DB::table('cache')->where('expiration', '<', time())->delete()"

# Or via MySQL directly
mysql -u your_user -p your_database -e "DELETE FROM sessions WHERE last_activity < UNIX_TIMESTAMP(NOW() - INTERVAL 2 HOUR)"
mysql -u your_user -p your_database -e "DELETE FROM cache WHERE expiration < UNIX_TIMESTAMP()"
```

#### Automated Cleanup via Cron (File-Based Sessions)

Add to server crontab if not using Laravel scheduler:

```bash
# Runs daily at 3am - deletes sessions older than 2 hours
0 3 * * * find /var/www/gencc-search/storage/framework/sessions -type f -mmin +120 -delete

# Runs daily at 3am - deletes cache older than 1 day
0 3 * * * find /var/www/gencc-search/storage/framework/cache/data -type f -mtime +1 -delete
```

### Laravel Log Management

**Laravel's `storage/logs/laravel.log` does NOT get automatically cleaned up.** It grows indefinitely until you manage it.

#### Option 1: Use Daily Log Files (Recommended)

In `config/logging.php`, change the default channel:

```php
'default' => env('LOG_CHANNEL', 'daily'),  // Instead of 'stack' or 'single'
```

Or in `.env`:

```
LOG_CHANNEL=daily
```

This creates files like `laravel-2026-01-21.log` and automatically keeps only the last 14 days (configurable):

```php
'daily' => [
    'driver' => 'daily',
    'path' => storage_path('logs/laravel.log'),
    'level' => 'debug',
    'days' => 14,  // Keep 14 days of logs
],
```

#### Option 2: Use System Logrotate

Create `/etc/logrotate.d/laravel`:

```
/var/www/gencc-search/storage/logs/*.log {
    daily
    missingok
    rotate 14
    compress
    delaycompress
    notifempty
    create 0664 www-data www-data
}
```

#### Option 3: Manual/Cron Cleanup

```bash
# Truncate log (keeps file, empties content)
truncate -s 0 /var/www/gencc-search/storage/logs/laravel.log

# Delete old daily logs
find /var/www/gencc-search/storage/logs -name "*.log" -mtime +14 -delete

# Check current size
du -h /var/www/gencc-search/storage/logs/
```

Add to crontab for automated cleanup:

```bash
# Weekly log truncation (if using single log file)
0 0 * * 0 truncate -s 0 /var/www/gencc-search/storage/logs/laravel.log

# Or delete old daily logs (if using daily driver)
0 3 * * * find /var/www/gencc-search/storage/logs -name "*.log" -mtime +14 -delete
```

**Recommendation:** Switch to `LOG_CHANNEL=daily` with `'days' => 14` - it's the cleanest Laravel-native solution.

---

## Notes

- The python-requests traffic from hospitals (SickKids, UHN, etc.) is legitimate research use - do not block
- SEO crawlers (Semrush, Ahrefs, Moz, Majestic) provide no value to your site - safe to block
- AI crawlers (ClaudeBot, GPTBot, ChatGPT) are harvesting data for training - block if desired
- Empty user agent traffic is suspicious and safe to block - legitimate browsers always send a UA

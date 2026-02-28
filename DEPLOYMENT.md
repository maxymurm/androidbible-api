# Production Deployment Configuration

## Server Requirements
- PHP 8.4+
- PostgreSQL 16+
- Redis 7+
- Nginx or Apache
- Supervisor (for queue workers)
- Node.js 20+ (for asset compilation)

## Environment Variables (Required)
```
APP_NAME=AndroidBible
APP_ENV=production
APP_KEY=<generate with php artisan key:generate>
APP_DEBUG=false
APP_URL=https://api.androidbible.app

DB_CONNECTION=pgsql
DB_HOST=<your-db-host>
DB_PORT=5432
DB_DATABASE=androidbible
DB_USERNAME=<your-db-user>
DB_PASSWORD=<your-db-password>

REDIS_HOST=<your-redis-host>
REDIS_PASSWORD=<your-redis-password>

CACHE_DRIVER=redis
SESSION_DRIVER=redis
QUEUE_CONNECTION=redis

REVERB_APP_ID=<your-reverb-app-id>
REVERB_APP_KEY=<your-reverb-key>
REVERB_APP_SECRET=<your-reverb-secret>
REVERB_HOST=<your-reverb-host>
REVERB_PORT=8080
REVERB_SCHEME=https

MEILISEARCH_HOST=http://<your-meilisearch-host>:7700
MEILISEARCH_KEY=<your-meilisearch-key>

GOOGLE_CLIENT_ID=<your-google-client-id>
GOOGLE_CLIENT_SECRET=<your-google-client-secret>
APPLE_CLIENT_ID=<your-apple-client-id>
APPLE_CLIENT_SECRET=<your-apple-client-secret>
FCM_SERVER_KEY=<your-fcm-server-key>
```

## Deployment Steps

### 1. Initial Setup
```bash
git clone https://github.com/maxymurm/androidbible-api.git
cd androidbible-api
composer install --no-dev --optimize-autoloader
cp .env.example .env
php artisan key:generate
```

### 2. Database
```bash
php artisan migrate --force
php artisan db:seed --force  # If seeders exist
```

### 3. Cache & Optimize
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
php artisan optimize
```

### 4. Supervisor (Queue Workers)
```ini
[program:androidbible-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/androidbible-api/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=2
redirect_stderr=true
stdout_logfile=/var/log/androidbible-worker.log
```

### 5. Reverb WebSocket Server
```ini
[program:androidbible-reverb]
command=php /var/www/androidbible-api/artisan reverb:start --host=0.0.0.0 --port=8080
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/log/androidbible-reverb.log
```

### 6. Horizon (Queue Dashboard)
```ini
[program:androidbible-horizon]
command=php /var/www/androidbible-api/artisan horizon
autostart=true
autorestart=true
redirect_stderr=true
stdout_logfile=/var/log/androidbible-horizon.log
```

### 7. Scheduler (Cron)
```
* * * * * cd /var/www/androidbible-api && php artisan schedule:run >> /dev/null 2>&1
```

### 8. Nginx Configuration
```nginx
server {
    listen 80;
    listen [::]:80;
    server_name api.androidbible.app;
    return 301 https://$host$request_uri;
}

server {
    listen 443 ssl http2;
    listen [::]:443 ssl http2;
    server_name api.androidbible.app;

    root /var/www/androidbible-api/public;
    index index.php;

    ssl_certificate /etc/letsencrypt/live/api.androidbible.app/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/api.androidbible.app/privkey.pem;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";
    add_header X-XSS-Protection "1; mode=block";

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_hide_header X-Powered-By;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

## Security Checklist
- [ ] APP_DEBUG=false
- [ ] APP_ENV=production
- [ ] HTTPS enforced
- [ ] Database credentials rotated
- [ ] Redis password set
- [ ] Rate limiting enabled
- [ ] CORS configured properly
- [ ] Sanctum stateful domains configured
- [ ] File permissions (storage/ and bootstrap/cache/ writable)

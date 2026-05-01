# SiteGround Cloud Deployment Guide for HotelMaint Pro

This guide provides step-by-step instructions for deploying HotelMaint Pro to SiteGround Cloud Hosting with Redis, Supervisor, and Cron support.

## Prerequisites

- SiteGround Cloud Hosting account
- SSH access enabled
- Domain pointed to your SiteGround server
- Git repository URL: `https://github.com/mkresna2/HotelMaintenanceApp.git`

---

## Phase 1: Database & Redis Setup

### 1.1 Create MySQL Database
1. Log into **SiteGround Client Area** → **Site Tools**
2. Navigate to **MySQL** → **Databases**
3. Click **Create New Database**
   - Database Name: `hotelmaint_prod`
   - User: `hotelmaint_user`
   - Password: Generate a strong password
4. Assign **ALL PRIVILEGES** to the user
5. Note the database host (usually `localhost` or a specific hostname)

### 1.2 Activate Redis
1. In **Site Tools**, go to **Speed** → **Redis**
2. Click **Activate** (included in Cloud plans)
3. Note the Redis connection details:
   - Host: `127.0.0.1`
   - Port: `6379`
   - Password: (if set, otherwise null)

---

## Phase 2: SSH Access & Clone Repository

### 2.1 Enable SSH
1. Go to **Site Tools** → **Devs** → **SSH Access**
2. Click **Manage** and enable SSH
3. Note your SSH credentials or add your SSH key

### 2.2 Connect via SSH
```bash
ssh your_username@your_server_ip
```

### 2.3 Navigate to Web Directory
```bash
cd /home/customer/www/yourdomain.com
```

### 2.4 Clone Repository
```bash
git clone https://github.com/mkresna2/HotelMaintenanceApp.git .
```

### 2.5 Install Dependencies
```bash
# Install PHP dependencies
composer install --optimize-autoloader --no-dev

# Install Node.js dependencies
npm install

# Build frontend assets
npm run build
```

---

## Phase 3: Environment Configuration

### 3.1 Create .env File
```bash
cp .env.example .env
```

### 3.2 Edit .env File
```bash
nano .env
```

Update the following values:

```env
# Application
APP_NAME=HotelMaint_Pro
APP_ENV=production
APP_KEY=
APP_DEBUG=false
APP_URL=https://yourdomain.com

# Database
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=hotelmaint_prod
DB_USERNAME=hotelmaint_user
DB_PASSWORD=your_strong_password

# Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis

# Mail (Configure based on your email provider)
MAIL_MAILER=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your_email@gmail.com
MAIL_PASSWORD=your_app_password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@yourdomain.com
MAIL_FROM_NAME="${APP_NAME}"

# AWS S3 (Optional - for file storage)
AWS_ACCESS_KEY_ID=your_access_key
AWS_SECRET_ACCESS_KEY=your_secret_key
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your_bucket_name
AWS_URL=https://your_bucket_name.s3.amazonaws.com

# Sanctum
SANCTUM_STATEFUL_DOMAINS=yourdomain.com,www.yourdomain.com
```

### 3.3 Generate Application Key
```bash
php artisan key:generate
```

---

## Phase 4: Database Migration & Optimization

### 4.1 Run Migrations
```bash
php artisan migrate --force
```

### 4.2 Create Storage Link
```bash
php artisan storage:link
```

### 4.3 Seed Initial Data (Optional - for demo)
```bash
php artisan db:seed --class=RoleSeeder
php artisan db:seed --class=DepartmentSeeder
php artisan db:seed --class=WorkOrderStatusSeeder
php artisan db:seed --class=AssetCategorySeeder
```

### 4.4 Optimize Caches
```bash
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

---

## Phase 5: Queue Worker Setup (Supervisor)

SiteGround Cloud supports Supervisor for persistent queue workers.

### 5.1 Create Supervisor Configuration
```bash
nano /etc/supervisor/conf.d/hotelmaint-worker.conf
```

Add the following configuration:

```ini
[program:hotelmaint-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /home/customer/www/yourdomain.com/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=customer
numprocs=2
redirect_stderr=true
stdout_logfile=/home/customer/www/yourdomain.com/storage/logs/worker.log
stopwaitsecs=3600
```

**Note:** Adjust the path and user according to your SiteGround setup.

### 5.2 Start Supervisor
```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start hotelmaint-worker:*
sudo supervisorctl status
```

You should see 2 workers running.

---

## Phase 6: Scheduled Tasks (Cron)

### 6.1 Add Cron Job in SiteGround
1. Go to **Site Tools** → **Devs** → **Cron Jobs**
2. Click **Create Cron Job**
3. Configure:
   - **Frequency**: `* * * * *` (every minute)
   - **Command**:
   ```bash
   cd /home/customer/www/yourdomain.com && php artisan schedule:run >> /dev/null 2>&1
   ```

### 6.2 Verify Scheduler
The Laravel scheduler will now run the following automated tasks:
- **Daily**: Generate PM work orders from schedules
- **Hourly**: Check SLA breaches and trigger escalations
- **Nightly**: Calculate Asset Health scores

---

## Phase 7: Web Server Configuration

### Option A: Move Public Folder Contents (Recommended for SiteGround)

SiteGround serves files from the root directory by default.

```bash
# Backup existing files
mkdir -p /home/customer/www/yourdomain.com/backup
mv /home/customer/www/yourdomain.com/* /home/customer/www/yourdomain.com/backup/ 2>/dev/null

# Move public folder contents to root
mv /home/customer/www/yourdomain.com/public/* /home/customer/www/yourdomain.com/
mv /home/customer/www/yourdomain.com/public/.htaccess /home/customer/www/yourdomain.com/

# Update index.php paths
nano /home/customer/www/yourdomain.com/index.php
```

Update `index.php`:
```php
// Change these lines:
require __DIR__.'/../vendor/autoload.php';
$app = require_once __DIR__.'/../bootstrap/app.php';

// To:
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
```

### Option B: Use .htaccess Rewrite (Alternative)

If you prefer to keep the structure intact, create a `.htaccess` in the root:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    RewriteRule ^$ public/ [L]
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>
```

---

## Phase 8: Final Verification & Testing

### 8.1 Set Permissions
```bash
chmod -R 755 /home/customer/www/yourdomain.com/storage
chmod -R 755 /home/customer/www/yourdomain.com/bootstrap/cache
chown -R customer:customer /home/customer/www/yourdomain.com
```

### 8.2 Test Website
1. Visit `https://yourdomain.com`
2. You should see the HotelMaint Pro login page

### 8.3 Test Login
Use seeded credentials (if you ran seeders):
- **Email**: admin@hotelmaint.com
- **Password**: password (change immediately!)

### 8.4 Verify Queues
1. Create a test complaint
2. Check if notification emails are sent (may take up to 1 minute)
3. Check worker logs:
```bash
tail -f /home/customer/www/yourdomain.com/storage/logs/worker.log
tail -f /home/customer/www/yourdomain.com/storage/logs/laravel.log
```

### 8.5 Verify Scheduler
Check scheduled tasks execution:
```bash
php artisan schedule:list
```

---

## Troubleshooting

### Issue: 500 Internal Server Error
- Check logs: `storage/logs/laravel.log`
- Verify `.env` configuration
- Ensure `APP_KEY` is generated
- Check file permissions

### Issue: Queues Not Processing
- Verify Supervisor is running: `supervisorctl status`
- Check Redis connection: `redis-cli ping`
- Restart workers: `supervisorctl restart hotelmaint-worker:*`

### Issue: Emails Not Sending
- Verify SMTP credentials in `.env`
- Check mail provider requirements (app passwords for Gmail)
- Test manually: `php artisan tinker` then `Mail::raw('test', function($m) { $m->to('you@example.com')->subject('Test'); });`

### Issue: Assets Not Loading
- Rebuild assets: `npm run build`
- Clear cache: `php artisan view:clear && php artisan config:clear`
- Check `ASSET_URL` in `.env` if using CDN

---

## Post-Deployment Checklist

- [ ] Change default admin password
- [ ] Configure backup strategy (SiteGround offers daily backups)
- [ ] Set up SSL certificate (SiteGround provides free Let's Encrypt)
- [ ] Configure domain email forwarding if needed
- [ ] Monitor disk space and logs regularly
- [ ] Set up monitoring alerts (optional: UptimeRobot, Pingdom)
- [ ] Document custom configurations for team reference

---

## Maintenance Commands

### Clear Caches
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

### Run Database Migrations
```bash
php artisan migrate --force
```

### Rebuild Assets
```bash
npm install && npm run build
```

### Restart Queue Workers
```bash
supervisorctl restart hotelmaint-worker:*
```

### View Logs
```bash
tail -f storage/logs/laravel.log
tail -f storage/logs/worker.log
```

---

## Support

For issues specific to SiteGround hosting, contact their 24/7 support through the Client Area.

For application-specific issues, refer to the HotelMaint Pro documentation or create an issue on GitHub.

---

**Version**: 1.0  
**Last Updated**: 2026-05-01  
**Compatible With**: Laravel 11.x, PHP 8.2+, SiteGround Cloud Hosting

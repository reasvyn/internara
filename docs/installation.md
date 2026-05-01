# Installation Guide

Internara can be installed in any environment that meets the system requirements. The installation process supports both automated CLI initialization and a guided web-based setup wizard.

---

## System Requirements

| Requirement | Minimum | Recommended |
|-------------|---------|-------------|
| PHP | 8.4 | 8.4+ |
| Extensions | `mbstring`, `ctype`, `fileinfo`, `pdo`, `openssl`, `tokenizer`, `xml`, `json`, `curl` | All minimum + `redis` (for queue/cache) |
| Database | SQLite 3, MySQL 8.0+, PostgreSQL 14+ | MySQL 8.0+ or PostgreSQL 15+ |
| Node.js | 20+ | 22+ (LTS) |
| Memory | 512 MB PHP memory limit | 1 GB+ |
| Disk | 500 MB (base installation) | 2 GB+ (with file storage) |

---

## Installation Methods

### Method 1: CLI + Web Wizard (Recommended)

```bash
# 1. Clone the repository
git clone https://github.com/reasvyn/internara.git
cd internara

# 2. Install dependencies
composer install --no-dev    # Use --no-dev for production
pnpm install

# 3. Create environment file
cp .env.example .env
php artisan key:generate

# 4. Configure database in .env
#    DB_CONNECTION=mysql
#    DB_HOST=127.0.0.1
#    DB_PORT=3306
#    DB_DATABASE=internara
#    DB_USERNAME=root
#    DB_PASSWORD=

# 5. Run the installation command
php artisan setup:install
```

The command outputs a signed URL with a setup token. Open it in your browser to continue with the web wizard.

### Method 2: Manual Initialization

```bash
composer install && pnpm install
cp .env.example .env
php artisan key:generate
php artisan migrate:fresh --seed
php artisan storage:link
php artisan serve
```

After manual initialization, create a SuperAdmin user directly in the database or use the seeder output to log in.

---

## Web Setup Wizard

**Route**: `/setup?setup_token=...`  
**Component**: `App\Livewire\Setup\SetupWizard`

The wizard guides you through initial configuration in 6 steps:

| Step | Purpose | What You'll Enter |
|------|---------|-------------------|
| 1. Welcome | Pre-flight system audit | — (automatic checks) |
| 2. School | School profile | Name, code, address, email, logo |
| 3. Account | Admin user | Name, email, password |
| 4. Department | First academic department | Department name |
| 5. Internship | First internship program | Name, dates, description |
| 6. Finalize | Verification & lock | Review checklist, confirm |

### Pre-Flight Checks

The wizard automatically verifies:
- PHP version (must be 8.4+)
- Required PHP extensions loaded
- Storage directory writability
- Database connectivity
- Environment configuration

If any check fails, the wizard displays the specific issue and required action.

---

## Post-Installation

After successful installation:

- Database schema is fully initialized with all tables and indexes
- SuperAdmin user is created with full system access
- School, department, and internship records are seeded
- Storage symlink is created (`public/storage` → `storage/app/public`)
- Lock file (`storage/app/.installed`) prevents re-access to setup routes
- Application cache is cleared

You can now log in at `/login` with the SuperAdmin credentials created during setup.

---

## Environment Configuration

### Required `.env` Variables

```env
APP_NAME="Your Institution Name"
APP_ENV=production
APP_KEY=base64:...
APP_DEBUG=false
APP_URL=https://your-domain.com

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=internara
DB_USERNAME=your_user
DB_PASSWORD=your_password

CACHE_STORE=database        # Use "redis" if available
SESSION_DRIVER=database     # Use "redis" if available
QUEUE_CONNECTION=database   # Use "redis" if available

MAIL_MAILER=smtp
MAIL_HOST=your-smtp-host
MAIL_PORT=587
MAIL_USERNAME=your-email
MAIL_PASSWORD=your-password
MAIL_ENCRYPTION=tls
MAIL_FROM_ADDRESS=noreply@your-domain.com
MAIL_FROM_NAME="${APP_NAME}"
```

### Optional Configurations

```env
# File storage (default: local)
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
AWS_DEFAULT_REGION=...
AWS_BUCKET=...
AWS_URL=...

# Redis (uncomment if using redis for cache/session/queue)
# REDIS_HOST=127.0.0.1
# REDIS_PASSWORD=null
# REDIS_PORT=6379
```

---

## Upgrading

When updating to a new version:

```bash
# 1. Pull latest changes
git pull

# 2. Update dependencies
composer install
pnpm install

# 3. Run migrations (safe — additive only)
php artisan migrate

# 4. Clear caches
php artisan cache:clear
php artisan config:clear
php artisan view:clear

# 5. Rebuild assets
pnpm build
```

**Important**: Always back up your database before running migrations. Migrations are designed to be additive and non-destructive, but a backup ensures safety.

---

## Server Deployment

### Shared Hosting

1. Upload all files to your hosting directory
2. Set document root to `public/`
3. Run `composer install --optimize-autoloader --no-dev`
4. Create `.env` with your database credentials
5. Run `php artisan setup:install`
6. Set up a cron job for scheduled tasks:
   ```
   * * * * * cd /path/to/internara && php artisan schedule:run >> /dev/null 2>&1
   ```

### VPS / Dedicated Server

Recommended stack:
- **Web server**: Nginx with PHP-FPM
- **Database**: MySQL 8.0+ or PostgreSQL 15+
- **Queue worker**: Supervisor for `php artisan queue:work`
- **Cache**: Redis for cache, session, and queue
- **SSL**: Let's Encrypt (certbot)

Nginx configuration:
```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /var/www/internara/public;

    add_header X-Frame-Options "SAMEORIGIN";
    add_header X-Content-Type-Options "nosniff";

    index index.php;

    charset utf-8;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location = /favicon.ico { access_log off; log_not_found off; }
    location = /robots.txt  { access_log off; log_not_found off; }

    error_page 404 /index.php;

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.4-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### Docker

A `docker-compose.yml` setup is recommended for local development. For production, use a multi-stage Dockerfile with PHP-FPM, Nginx, and a separate database container.

---

## Security

- Setup routes are token-protected with rate limiting (20 attempts/minute/IP)
- Setup tokens expire after 24 hours
- Lock file provides defense-in-depth (checked by middleware and component independently)
- Database operations during installation are wrapped in a transaction (atomicity)
- Every installation event is logged with environment context (IP, User Agent)
- `APP_DEBUG` must be `false` in production environments

---

*Last Updated: April 30, 2026*

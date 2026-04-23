# 📦 Installation & Configuration Guide

Complete step-by-step guide for installing and configuring Internara from scratch to production-ready deployment.

---

## Table of Contents

1. [System Requirements](#-system-requirements)
2. [Installation Steps](#-installation-steps)
3. [Configuration](#-configuration)
4. [Database Setup](#-database-setup)
5. [Web Setup Wizard](#-web-setup-wizard)
6. [Development Setup](#-development-setup)
7. [Production Deployment](#-production-deployment)
8. [Verification & Testing](#-verification--testing)
9. [Troubleshooting](#-troubleshooting)

---

## 🖥️ System Requirements

### Minimum Requirements
- **PHP**: 8.4 or higher (with extensions: bcmath, ctype, fileinfo, json, mbstring, openssl, pdo, tokenizer, xml)
- **Node.js**: 20.x or higher
- **Composer**: Latest version
- **Git**: For cloning the repository
- **Database**: SQLite (dev) / PostgreSQL / MySQL (production)
- **RAM**: 2GB minimum
- **Disk**: 500MB free space

### Recommended Requirements (Production)
- **Server OS**: Linux (Ubuntu 22.04 LTS, Debian 12, or similar)
- **Web Server**: Nginx or Apache with PHP-FPM
- **PHP**: 8.4 with OPcache enabled
- **Database**: PostgreSQL 14+ (with connection pooling)
- **Redis**: For caching and queue
- **SSL**: HTTPS required
- **RAM**: 4GB+
- **Disk**: 10GB+ SSD

### PHP Extensions
Verify all required extensions are installed:
```bash
php -m | grep -E "bcmath|ctype|fileinfo|json|mbstring|openssl|pdo|tokenizer|xml"
```

---

## 📥 Installation Steps

### Step 1: Clone the Repository
```bash
# Clone from GitHub
git clone https://github.com/reasvyn/internara.git
cd internara

# Or clone from your fork
git clone https://github.com/{YOUR_USERNAME}/internara.git
cd internara
```

### Step 2: Run Automated Setup
The `composer setup` script automates all initialization steps:

```bash
composer setup
```

**What this script does:**
1. Installs all PHP dependencies via Composer
2. Creates `.env` file from `.env.example` (if not exists)
3. Generates `APP_KEY` for encryption
4. Creates SQLite database file
5. Runs all database migrations
6. Installs JavaScript dependencies
7. Builds frontend assets (CSS/JS)

**Expected output:**
```
Composer update succeeded
.env file created
APP_KEY generated: base64:xxx...
Database migrations completed
npm install completed
Assets compiled successfully
```

### Step 3: Verify Installation
```bash
# Check application status
php artisan about

# List all routes
php artisan route:list | head -20
```

---

## ⚙️ Configuration

### Environment File (`.env`)

The `.env` file stores configuration for your environment. Created automatically by `composer setup`.

#### Application Settings
```bash
APP_NAME=Internara
APP_ENV=local              # local|staging|production
APP_DEBUG=true             # false in production
APP_KEY=base64:xxx...      # Encryption key (auto-generated)
APP_URL=http://localhost:8000
APP_TIMEZONE=Asia/Jakarta
APP_LOCALE=id
APP_FALLBACK_LOCALE=en
```

#### Database Configuration
```bash
# SQLite (Development - Recommended)
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# PostgreSQL (Production)
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=internara
DB_USERNAME=postgres
DB_PASSWORD=your_password

# MySQL
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=internara
DB_USERNAME=root
DB_PASSWORD=your_password
```

#### Queue Configuration
```bash
# For background jobs
QUEUE_CONNECTION=database   # database|redis|sync

# For development (sync = runs immediately)
# For production (redis or database = asynchronous)
```

#### Cache Configuration
```bash
# For caching data
CACHE_STORE=database        # database|redis|file

# For development (database = simple)
# For production (redis = fast)
```

#### Mail Configuration (Optional)
```bash
MAIL_MAILER=log            # log|smtp|mailgun|postmark|etc
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=xxx
MAIL_PASSWORD=xxx
```

#### Session Configuration
```bash
SESSION_DRIVER=database     # database|file|cookie
SESSION_LIFETIME=120        # Minutes
SESSION_ENCRYPT=false
```

### Configuration Files
Core configuration files in `config/` directory:

| File | Purpose |
| :--- | :--- |
| `config/app.php` | Application settings, timezone, locale |
| `config/database.php` | Database connections |
| `config/cache.php` | Cache stores |
| `config/queue.php` | Queue configuration |
| `config/mail.php` | Mail configuration |
| `config/auth.php` | Authentication guards and providers |
| `config/bindings.php` | Service auto-binding (critical!) |

For detailed configuration options, see individual files.

---

## 🗄️ Database Setup

### SQLite (Development)

**Advantages**: Zero setup, perfect for development, single file database

1. Already configured after `composer setup`
2. Database file: `database/database.sqlite`
3. Automatic backups: Copy the `.sqlite` file

```bash
# View SQLite database
sqlite3 database/database.sqlite

# Export database
sqlite3 database/database.sqlite .dump > backup.sql

# Restore database
sqlite3 database/database.sqlite < backup.sql
```

### PostgreSQL (Recommended for Production)

**Advantages**: Enterprise-grade, scalable, excellent performance

#### 1. Install PostgreSQL
```bash
# Ubuntu/Debian
sudo apt-get install postgresql postgresql-contrib

# Start service
sudo systemctl start postgresql
sudo systemctl enable postgresql
```

#### 2. Create Database and User
```bash
# Login as postgres
sudo -u postgres psql

# Create database
CREATE DATABASE internara;

# Create user
CREATE USER internara WITH PASSWORD 'your_password';

# Grant privileges
GRANT ALL PRIVILEGES ON DATABASE internara TO internara;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO internara;

# Exit
\q
```

#### 3. Update `.env`
```bash
DB_CONNECTION=pgsql
DB_HOST=localhost
DB_PORT=5432
DB_DATABASE=internara
DB_USERNAME=internara
DB_PASSWORD=your_password
```

#### 4. Run Migrations
```bash
php artisan migrate
```

### MySQL (Alternative)

**Advantages**: Simple setup, widely supported

#### 1. Create Database and User
```bash
mysql -u root -p

CREATE DATABASE internara CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'internara'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON internara.* TO 'internara'@'localhost';
FLUSH PRIVILEGES;
EXIT;
```

#### 2. Update `.env`
```bash
DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=internara
DB_USERNAME=internara
DB_PASSWORD=your_password
```

#### 3. Run Migrations
```bash
php artisan migrate
```

### Database Migrations

Migrations create the database schema automatically:

```bash
# Run all pending migrations
php artisan migrate

# Rollback last batch
php artisan migrate:rollback

# Rollback all migrations
php artisan migrate:reset

# Refresh (reset + migrate)
php artisan migrate:refresh

# Seed test data
php artisan db:seed
```

---

## 🚀 Web Setup Wizard

After installation, the **Setup Wizard** guides you through initial configuration via web interface.

### Accessing the Wizard

1. Start the installation suite and copy the access token:
   ```bash
   php artisan app:install
   ```

2. Run the development server and visit: **http://localhost:8000**
   ```bash
   composer dev
   ```

3. You'll be automatically redirected to: **http://localhost:8000/setup**

### Wizard Steps

#### 1️⃣ Welcome Screen
- **Purpose**: Introduction and prerequisites verification
- **Actions**: Review information, click "Continue"

#### 2️⃣ Environment Configuration
- **Application Name**: How the system identifies itself (e.g., "SMKN 1 Jakarta")
- **Timezone**: Select your timezone (Asia/Jakarta recommended for Indonesia)
- **Locale**: Language (English, Indonesian, etc.)
- **Actions**: Fill form, click "Next"

#### 3️⃣ School/Institution Setup
- **School Name**: Full name of educational institution
- **School Type**: Type of institution (SMA, SMK, University, etc.)
- **Contact Email**: Institution contact email
- **Phone**: Institution phone number
- **Address**: Full address
- **Logo**: Upload school logo (optional)
- **Actions**: Fill form, upload logo, click "Next"

#### 4️⃣ Administrator Account
- **Email**: Admin account email
- **Password**: Strong password (min 8 chars, mixed case, numbers)
- **Confirm Password**: Verify password
- **Actions**: Fill form, click "Create Administrator"

#### 5️⃣ System Configuration
- **Queue Driver**: Select background job queue (database recommended for dev)
- **Cache Store**: Select caching backend (database/redis)
- **Logging Level**: Log verbosity (debug/info/warning/error)
- **Actions**: Configure settings, click "Next"

#### 6️⃣ Department Setup
- **Create Departments**: Add organizational departments
  - Department Name: (e.g., "Accounting", "Hospitality")
  - Department Code: Unique identifier
  - Head Email: Department head contact
- **Actions**: Add departments, click "Next"

#### 7️⃣ Internship Configuration
- **Program Name**: Name of internship program
- **Duration**: Internship length (months)
- **Start Date**: Program start date
- **Grading Scale**: Assessment scale (1-5, A-F, etc.)
- **Requirements**: Minimum requirements for participation
- **Actions**: Configure program, click "Next"

#### 8️⃣ Completion
- **Summary**: Review all configuration
- **Download Configuration**: Option to export settings
- **Actions**: Verify all settings, click "Finish"

### After Setup Wizard

✅ **Setup is now locked** (security measure)
- Setup routes become inaccessible (404 errors)
- Application redirects to login page
- Users must authenticate with admin credentials created in step 4

**To reset setup** (emergency only):
```bash
php artisan app:setup-reset
```

---

## 💻 Development Setup

### Full Development Environment

Start all development services with one command:

```bash
composer dev
```

This runs concurrently:
- 🔵 **Laravel Server** — PHP application server (port 8000)
- 🟣 **Queue Worker** — Background job processor
- 🔴 **Log Viewer** — Live log display
- 🟡 **Vite** — Asset bundler with HMR

### Asset Compilation

Assets are automatically compiled during development:

```bash
# Watch mode (automatic recompilation)
npm run dev

# Production build
npm run build

# One-time build
npm run build
```

### Code Quality Tools

Before committing, verify code quality:

```bash
# Run all tests
composer test

# Check code style
composer lint

# Auto-format code
composer format

# Run specific linter
vendor/bin/pint --test
```

### Database Seeding (Optional)

Load sample data for development:

```bash
# Run all seeders
php artisan db:seed

# Run specific seeder
php artisan db:seed --class=UserSeeder
```

---

## 🌐 Production Deployment

### Pre-Deployment Checklist

- [ ] `.env` configured for production
- [ ] `APP_DEBUG=false`
- [ ] `APP_ENV=production`
- [ ] Database backups configured
- [ ] SSL certificate installed
- [ ] HTTPS enforced
- [ ] Firewall properly configured
- [ ] Backup system in place

### Deployment Steps

#### 1. Server Setup
```bash
# Create application directory
mkdir -p /var/www/internara
cd /var/www/internara

# Clone repository
git clone https://github.com/reasvyn/internara.git .
```

#### 2. Environment Configuration
```bash
# Copy and configure .env
cp .env.example .env
nano .env

# Critical changes:
# APP_ENV=production
# APP_DEBUG=false
# DB_CONNECTION=pgsql (or mysql)
# DB_HOST=your-database-server
# Queue provider for your infrastructure
```

#### 3. Install Dependencies
```bash
# Install PHP dependencies
composer install --no-dev --optimize-autoloader

# Install frontend dependencies
npm install --production

# Build frontend
npm run build
```

#### 4. Database Setup
```bash
# Generate encryption key
php artisan key:generate

# Run migrations
php artisan migrate --force

# Optionally seed data
php artisan db:seed --force
```

#### 5. Permissions
```bash
# Set proper permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data /var/www/internara
```

#### 6. Web Server Configuration

**Nginx example:**
```nginx
server {
    listen 443 ssl http2;
    server_name internara.example.com;

    ssl_certificate /etc/ssl/certs/your-cert.pem;
    ssl_certificate_key /etc/ssl/private/your-key.pem;

    root /var/www/internara/public;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/run/php/php8.4-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.ht {
        deny all;
    }
}
```

#### 7. Queue Worker (Background Jobs)
```bash
# Install supervisor
sudo apt-get install supervisor

# Create supervisor config
sudo nano /etc/supervisor/conf.d/internara-queue.conf
```

```ini
[program:internara-queue]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/internara/artisan queue:work --queue=default --tries=3
autostart=true
autorestart=true
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/internara/storage/logs/queue.log
```

```bash
# Start supervisor
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start internara-queue:*
```

#### 8. Caching
```bash
# Cache configuration for performance
php artisan config:cache
php artisan route:cache
php artisan view:cache

# (Re-run after code deployments)
```

#### 9. SSL Certificate (Let's Encrypt)
```bash
# Install certbot
sudo apt-get install certbot python3-certbot-nginx

# Obtain certificate
sudo certbot certonly --webroot -w /var/www/internara/public -d internara.example.com
```

### Production Monitoring

```bash
# View recent logs
tail -f storage/logs/laravel.log

# Monitor queue status
php artisan queue:monitor

# Check database status
php artisan db:check

# System health check
php artisan about
```

---

## ✅ Verification & Testing

### Post-Installation Verification

```bash
# Check application status
php artisan about

# List all routes
php artisan route:list

# Test database connection
php artisan tinker
>>> DB::connection()->getPdo()
>>> exit

# Test email (if configured)
php artisan tinker
>>> Mail::to('test@example.com')->send(new TestMail())
>>> exit
```

### Running Tests

```bash
# Full test suite
composer test

# Specific test file
vendor/bin/pest tests/Feature/AuthTest.php

# Watch mode
vendor/bin/pest --watch

# With coverage
vendor/bin/pest --coverage
```

### Security Audit

```bash
# Check for security vulnerabilities
composer audit

# Static analysis
vendor/bin/phpstan analyse

# Code quality
composer lint
```

---

## 🐛 Troubleshooting

### Installation Issues

#### "Composer install failed"
```bash
# Clear composer cache
composer clear-cache

# Update composer
composer self-update

# Retry
composer install
```

#### "npm install failed"
```bash
# Clear npm cache
npm cache clean --force

# Delete node_modules and package-lock
rm -rf node_modules package-lock.json

# Reinstall
npm install
```

#### "APP_KEY not generated"
```bash
# Generate manually
php artisan key:generate

# Verify in .env
cat .env | grep APP_KEY
```

### Database Issues

#### "database.sqlite not found"
```bash
# Create manually
touch database/database.sqlite

# Run migrations
php artisan migrate
```

#### "SQLSTATE[HY000]: General error: 1 database disk image is malformed"
```bash
# Backup and recreate
mv database/database.sqlite database/database.sqlite.backup
touch database/database.sqlite
php artisan migrate
```

#### Migration errors
```bash
# Rollback and retry
php artisan migrate:reset
php artisan migrate

# Or verbose output
php artisan migrate --verbose
```

### Server Issues

#### "Port 8000 already in use"
```bash
# Use different port
php artisan serve --port=8001

# Or find and kill process
lsof -i :8000
kill -9 <PID>
```

#### "Class not found" errors
```bash
# Refresh autoloader
composer dump-autoload

# Clear cache
php artisan optimize:clear
```

#### "Permission denied" errors
```bash
# Fix storage permissions
chmod -R 775 storage bootstrap/cache
chown -R $(whoami):www-data storage bootstrap/cache
```

### Setup Wizard Issues

#### "Setup wizard loop"
```bash
# Check APP_INSTALLED setting
php artisan tinker
>>> config('app.installed')
>>> exit

# Force setup completion
php artisan app:setup-reset
```

#### "Setup page not loading"
```bash
# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# Restart server
# Ctrl+C and run: composer dev
```

---

## 📚 Next Steps

1. **Configure for Production** — Update `.env` for your server
2. **Deploy** — Follow deployment steps above
3. **Monitor** — Set up logging and monitoring
4. **Backup** — Configure automated backups
5. **Security** — Run security audit and update regularly

---

## 🤝 Support

- **Installation Issues?** → [GitHub Issues](https://github.com/reasvyn/internara/issues)
- **Questions?** → [GitHub Discussions](https://github.com/reasvyn/internara/discussions)
- **Email**: [reasvyn@gmail.com](mailto:reasvyn@gmail.com)

---

**Happy installing! 🚀**

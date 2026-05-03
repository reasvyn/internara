# Installation Guide

Internara supports both automated CLI initialization and a guided web-based setup wizard.

## 1. Requirements

- **PHP**: 8.4+
- **Extensions**: `mbstring`, `pdo`, `openssl`, `tokenizer`, `xml`, `curl`, `fileinfo`
- **Database**: SQLite, MySQL 8+, or PostgreSQL 14+
- **Frontend**: Node.js 20+

## 2. Installation Steps

### Automated Setup (Recommended)
```bash
# Clone and install dependencies
git clone https://github.com/reasvyn/internara.git
composer install
npm install

# Initialize environment
cp .env.example .env
php artisan key:generate

# Run installer
php artisan setup:install
```
The installer will generate a **One-Time Setup URL**. Open this URL in your browser to complete the configuration via the Web Wizard.

### Manual Setup
```bash
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

## 3. Security

- **Lock File**: Once installed, the system creates a lock file (`storage/app/.installed`) that permanently disables setup routes.
- **One-Time Tokens**: Setup URLs are token-protected and expire after use or timeout.
- **Audit**: Every installation step is logged for security auditing.

## 4. Post-Installation

After setup, you can access the dashboard at `/login` using the credentials created during the wizard. Ensure your scheduler is running:
`* * * * * cd /path/to/internara && php artisan schedule:run >> /dev/null 2>&1`

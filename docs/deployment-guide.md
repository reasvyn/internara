# Deployment Guide (DG): Environment Orchestration

This document formalizes the **Deployment Guide (DG)** for the Internara system, standardized according to **ISO/IEC 12207** (Software life cycle processes).

---

## 1. Production Baseline Prerequisites

Before deployment, your environment must satisfy the following hardened requirements:

- **PHP**: `8.4.x` with optimized OPcache and JIT enabled.
- **Web Server**: Nginx or Apache with **TLS 1.3** and HSTS enabled.
- **Database**: PostgreSQL (Recommended for scale) or MySQL 8.0+.
- **Cache/Queue**: Redis (Mandatory for job orchestration and real-time telemetry).
- **Process Manager**: Supervisor (to manage `queue:work` listeners).

---

## 2. Installation Sequence

### 2.1 Artifact Preparation
```bash
# Clone the Stable Release
git clone -b main https://github.com/reasnov/internara.git
cd internara

# Optimize Dependencies
composer install --no-dev --optimize-autoloader
npm install && npm run build
```

### 2.2 Environment Initialization
```bash
cp .env.example .env
php artisan key:generate
```

### 2.3 Database Orchestration
```bash
php artisan migrate --force
php artisan permission:sync
php artisan db:seed --class=DatabaseSeeder
```

---

## 3. Operational Orchestration

### 3.1 Task Scheduling
Ensure the Laravel scheduler is running via Cron:
```cron
* * * * * cd /path-to-your-project && php artisan schedule:run >> /dev/null 2>&1
```

### 3.2 Performance Caching
```bash
php artisan optimize
php artisan view:cache
php artisan config:cache
```

---

## 4. Post-Deployment Audit (3S Check)

1.  **S1 (Secure)**: Verify `APP_DEBUG=false` and SSL is active. Check `APP_KEY` backup status.
2.  **S2 (Sustainable)**: Check system logs for hydration errors.
3.  **S3 (Scalable)**: Verify Redis connectivity and queue worker health.

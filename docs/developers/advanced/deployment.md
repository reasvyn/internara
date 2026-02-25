# Deployment Guide: Production Orchestration

This document formalizes the protocols for deploying the Internara ecosystem into production 
environments, ensuring compliance with **ISO/IEC 12207** (Maintenance Process).

---

## 1. Production Baseline Prerequisites

Before deployment, your environment must satisfy the following hardened requirements:

- **PHP**: `8.4.x` with optimized OPcache and JIT enabled.
- **Web Server**: Nginx or Apache with **TLS 1.3** and HSTS enabled.
- **Database**: PostgreSQL (Recommended for scale) or MySQL 8.0+.
- **Cache/Queue**: Redis (Mandatory for job orchestration and real-time telemetry).
- **Process Manager**: Supervisor (to manage `queue:work` and `pail` listeners).

---

## 2. Deployment Orchestration

### 2.1 Artifact Preparation

1.  **Clone the Stable Release**:
    ```bash
    git clone -b main https://github.com/reasnov/internara.git
    ```
2.  **Optimize Dependencies**:
    ```bash
    composer install --no-dev --optimize-autoloader
    npm install && npm run build
    ```

### 2.2 Environment Hardening (`.env`)

Ensure the following production invariants are set:
- `APP_ENV=production`
- `APP_DEBUG=false`
- `LOG_LEVEL=info` (or `error` to minimize disk I/O)
- `SESSION_SECURE_COOKIE=true`

---

## 3. Maintenance & Monitoring

### 3.1 Persistence & Migration
Always perform a backup before executing migrations in production:
```bash
php artisan migrate --force
```

### 3.2 Performance Optimization
Execute the framework's native optimization commands to cache the baseline:
```bash
php artisan optimize
php artisan view:cache
php artisan config:cache
```

### 3.3 Health Checks
Monitor the system health using the built-in audit tools:
- **Metadata Audit**: `php artisan app:info`
- **Queue Status**: `php artisan queue:monitor`

---

## 4. Security & Privacy Compliance

- **SSL/TLS**: Mandatory for all endpoints.
- **PII Encryption**: Ensure the `APP_KEY` is securely backed up; losing it will render 
  encrypted student data unrecoverable.
- **Honeypot & Turnstile**: Verify these protection layers are active in the `Setting` module.

---

_Proactive deployment management ensures the resilience and availability of the Internara platform 
for institutional stakeholders._

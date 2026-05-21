# ADR-017: Low-End Shared Hosting Deployment

## Status
Accepted

## Context

Internara is an open-source project that targets educational institutions
with limited budgets. Many such institutions use **shared hosting** —
inexpensive web hosting plans with a cPanel or similar control panel — rather
than VPS or cloud hosting.

Shared hosting imposes severe constraints on what can run:

| Resource | Shared Hosting | VPS / Cloud |
|---|---|---|
| **Long-running processes** | ❌ Not allowed (killed after ~30s) | ✅ Supervisor/systemd |
| **Queue worker** | ❌ Cannot run `php artisan queue:work` | ✅ Runs continuously |
| **WebSocket server** | ❌ Cannot run `php artisan reverb:start` | ✅ Runs continuously |
| **Scheduler cron** | ⚠️ Limited (min 5 min, no custom) | ✅ Every minute |
| **Redis / Memcached** | ❌ Not available | ✅ Installable |
| **Composer** | ❌ Usually not available | ✅ Available |
| **Node.js / npm** | ❌ Not available | ✅ Available |
| **PHP extensions** | ⚠️ Limited set (no pcntl, posix) | ✅ Full control |
| **Database** | ⚠️ MySQL/MariaDB (limited connections) | ✅ Full MySQL/PostgreSQL |
| **Environment vars** | ⚠️ `.env` via FTP upload (not shell) | ✅ SSH / CLI |
| **Memory / CPU** | ⚠️ Very limited (256–512 MB) | ✅ Scalable |
| **Disk I/O** | ⚠️ Shared, slow | ✅ Dedicated |

Two approaches were considered:

1. **Require VPS or cloud** — Simply document that shared hosting is not
   supported. This limits the user base to institutions with infrastructure
   budgets.
2. **Support shared hosting with reduced features** — Queue, WebSocket, and
   scheduler features are disabled or replaced with alternatives. The core
   internship management functionality remains fully usable.

## Decision

Internara supports shared hosting with an **explicitly documented reduced
feature set**. The following changes are applied when `APP_ENV=production`
is detected on a shared hosting environment:

### Feature Cuts for Shared Hosting

| Feature | Shared Hosting Behavior | Reason |
|---|---|---|
| **Queue** | Forced to `sync` driver. All jobs execute synchronously during the HTTP request. | No long-running processes allowed. |
| **WebSocket / Reverb** | Not available. Notifications rely on the in-app database channel. Page refresh required for new notifications. | WebSocket server cannot run. |
| **Scheduler** | Manual trigger via web-accessible URL (`/cron`), protected by a cron token. Institution sets up a cron job hitting this URL every 5+ minutes. | No minute-level cron available. |
| **Media conversions** | Processed synchronously (no queue). Uploads may take longer. | Queue forced to sync. |
| **Cache** | Forced to `file` or `database` driver. No Redis. | Redis not available. |
| **Session** | Forced to `file` or `database` driver. No Redis. | Redis not available. |
| **Broadcasting** | Falls back to `log` driver (essentially disabled). | Reverb cannot run. |
| **Pulse** | Requires at least the web cron hit. Recording limited to request-based ingestion. | No `pulse:work` daemon. |
| **Telescope / Debug** | Disabled by default in production. | Performance and security. |

### Configuration Preset

A shared hosting preset is provided in `.env.sharing`:

```env
APP_ENV=production
APP_DEBUG=false

QUEUE_CONNECTION=sync
CACHE_STORE=file
SESSION_DRIVER=file
BROADCAST_CONNECTION=log
FILESYSTEM_DISK=local

# Mail still works (SMTP)
MAIL_MAILER=smtp

# Cron token for manual scheduler trigger
CRON_SECRET=your-random-secret-here
```

### Deployment Process

Since composer and npm are not available on shared hosting, the application
must be built locally and uploaded:

```
Local machine:
  1. composer install --optimize-autoloader --no-dev
  2. npm install && npm run build
  3. rsync or FTP upload to shared hosting

Shared hosting (via cPanel or FTP):
  4. Upload built files to document root
  5. Configure .env (upload or cPanel file editor)
  6. Run php artisan migrate (via cPanel PHP CLI or web installer)
  7. Configure cron job hitting /cron with CRON_SECRET
```

### What Remains Fully Functional

Despite the cuts, all core Internara features work on shared hosting:

- ✅ User authentication and RBAC
- ✅ School, department, academic year management
- ✅ Company and partnership management
- ✅ Internship program lifecycle
- ✅ Student registration and placement
- ✅ Attendance tracking (clock-in/out)
- ✅ Logbook entries and verification
- ✅ Assignments and submissions
- ✅ Assessment rubrics and grading
- ✅ Reports and revisions
- ✅ Certificates (PDF generation)
- ✅ Mentoring and supervision logs
- ✅ Incidents and evaluations
- ✅ In-app notifications (no real-time, require refresh)
- ✅ Email notifications (via SMTP)

## Consequences

- **Positive**: The application is accessible to institutions with minimal
  infrastructure budgets. A basic shared hosting plan ($3–10/month) suffices.
- **Positive**: The same codebase runs on both shared hosting and VPS — no
  separate branches or forks.
- **Positive**: Feature cuts are explicit and documented. Institutions that
  outgrow shared hosting can upgrade to a VPS and restore features by
  changing `.env` settings.
- **Negative**: Queueable operations (certificate generation, bulk
  notifications) run synchronously and may slow HTTP responses.
- **Negative**: Real-time notifications require page refresh.
- **Negative**: Deployment is more manual (build locally, upload via FTP).
- **Negative**: Cron-triggered scheduler runs at 5+ minute intervals, not
  every minute.
- **Negative**: `pcntl` and `posix` extensions are typically unavailable,
  causing minor warnings in health checks.

## References
- `docs/en/blueprints/11-shared-hosting-deployment.md` — detailed guide
- `routes/web/cron.php` — web-based scheduler trigger
- `app/Domain/Core/Console/Commands/HealthCommand.php` — adjusted checks
- `.env.sharing` — shared hosting preset (template only, not committed to .env)

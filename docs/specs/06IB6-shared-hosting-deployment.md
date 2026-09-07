# Shared Hosting Deployment — Conventional cPanel-Style Server Operations

> **Spec ID:** 06IB8

## Description

Specification for deploying Internara to conventional shared hosting (cPanel-style servers with
PHP 8.4+, MySQL/MariaDB, and limited-interval cron). Defines the no-daemon constraints
(synchronous queue, file-based cache, database sessions), the off-server build workflow, the
`/cron/{secret}` webhook for scheduler triggering, and manual symlink creation. The deployment
profile catalog, environment detection, and configuration application are defined in
[deployment.md](06IB6-deployment.md).

---

## 1. Problem Statements

### PS-1 — Shared Hosting Has No Long-Running Daemons

Conventional shared hosting provides PHP 8.4, MySQL/MariaDB, and 5-15 minute interval cron, but
no long-running queue worker, no Redis/Memcached, no Composer/Node at runtime, and usually no SSH.
A background worker or Redis-backed queue cannot run reliably on this environment.

### PS-2 — Build Must Happen Off-Server

Shared hosting typically has no command-line tooling available at runtime. The application must
be built off-server (`composer install --optimize-autoloader --no-dev` + `npm run build`) and
uploaded via FTP/cPanel.

### PS-3 — Cron Intervals Are Limited

Shared hosting cron typically runs every 5-15 minutes, too coarse for the application scheduler
that expects minute-level scheduling. The system must support a webhook trigger that can be
invoked by a more frequent cron entry (or by an external scheduler service).

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Run the full application with no background daemon, no Redis, and no SSH access |
| G2  | Trigger the scheduler via the `/cron/{secret}` webhook route |
| G3  | Allow the buildable artifact to be uploaded via FTP/cPanel |
| G4  | Provide a manual symlink creation path when SSH is unavailable |
| G5  | Support MySQL 8+, MariaDB 10.6+, and SQLite as the database |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Real-time cron (minute-level is the closest a shared host typically allows) |
| NG2  | Queue worker daemon (jobs run synchronously) |
| NG3  | Redis/Memcached (cache is `file`, sessions are `database`) |
| NG4  | On-server build (must be built off-server) |

---

## 3. User Stories / Use Cases

### UC-06IB8-1 — School IT Deploys on Cheap Conventional Shared Hosting

**Actor:** School IT staff (cPanel / FTP access only)

**Preconditions:** Shared hosting plan with PHP 8.4+, MySQL 8+ or MariaDB 10.6+, configurable
document root, and cron (5-15 minute intervals acceptable).

**Flow:**
1. IT staff builds the artifact off-server: `composer install --optimize-autoloader --no-dev` +
   `npm install && npm run build`, then uploads the application files via FTP/cPanel.
2. IT staff sets document root to `public/` and creates the `public/storage` symlink manually.
3. IT staff copies `.env.example` to `.env`, sets `APP_URL`, `APP_DEBUG=false`, DB/MAIL credentials,
   and `CRON_SECRET`.
4. IT staff runs `php artisan setup:install` to provision the system and obtain the setup URL.
5. IT staff adds a cPanel cron entry hitting `/cron/{secret}` (5-15 minute interval acceptable).
6. IT staff runs `php artisan system:health` and confirms all checks pass.

**Postconditions:** System runs with `QUEUE_CONNECTION=sync`, `CACHE_STORE=file`,
`SESSION_DRIVER=database`; all core features functional for up to 500 users.

---

## 4. Functional Requirements

| ID     | Requirement |
| ------ | ----------- |
| FR-06IB8-SH1 | Shared hosting must not require a queue worker daemon — jobs run synchronously via `QUEUE_CONNECTION=sync` |
| FR-06IB8-SH2 | Shared hosting must not require Redis or Memcached — cache is `file`, sessions are `database` |
| FR-06IB8-SH3 | The scheduler must be triggerable via the `/cron/{secret}` webhook route (`routes/web/sysadmin.php`) when minute-level cron is unavailable |
| FR-06IB8-SH4 | The deployable artifact must be buildable off-server — `composer install --optimize-autoloader --no-dev` and `npm run build` must be run before upload, and must not be required on the server |
| FR-06IB8-SH5 | Shared hosting must support a configurable document root pointed at `public/` |
| FR-06IB8-SH6 | The `public/storage` symlink must be creatable manually when SSH is unavailable (see [installation.md](8NZAU-installation.md)) |
| FR-06IB8-SH7 | MySQL 8+ / MariaDB 10.6+ and SQLite must both be supported as the database on shared hosting |
| FR-06IB8-SH8 | All core features (auth, registration, attendance, logbook, assignments, assessments, reports, certificates) must work under the shared-hosting preset |
| FR-06IB8-SH9 | `deploy:configure --profile=shared-hosting` MUST write `QUEUE_CONNECTION=sync` and `SESSION_DRIVER=database` to `.env` |

---

## 5. Non-Functional Requirements

| ID     | Requirement |
| ------ | ----------- |
| NFR-06IB8-S1 | `CRON_SECRET` must be required for the `/cron/{secret}` webhook; requests without a valid secret must be rejected (existing `CronController` behavior) |
| NFR-06IB8-R1 | A deployment must be reproducible from documented commands alone |
| NFR-06IB8-U1 | The install guide must describe the off-server build, FTP upload, symlink, and cron entry steps in order |

---

## 6. API / Data Contracts

### Cron Webhook Route

```php
// routes/web/sysadmin.php
Route::get('/cron/{secret}', CronController::class)
    ->name('cron')
    ->middleware('throttle:...');
```

### Runtime Drivers (Shared Hosting Preset)

| Driver | Value | Reason |
| ------ | ----- | ------ |
| `QUEUE_CONNECTION` | `sync` | No daemon allowed |
| `CACHE_STORE` | `file` | No Redis/Memcached |
| `SESSION_DRIVER` | `database` | No Redis; avoids cookie size limits |
| `BROADCAST_CONNECTION` | `log` | No WebSocket support |
| Scheduler | webhook (via `/cron/{secret}`) | Limited cron granularity |

---

## 7. Design Decisions

### DD-1 — Off-Server Build as a Hard Requirement

**Decision:** The deployment artifact must be built off-server before upload. No on-server
build, no on-server Composer/Node.

**Rationale:** Shared hosting typically has no command-line tooling, limited disk space, and
no long-running processes. Building on a developer machine and uploading the result is faster
and more reliable.

**Trade-off:** Each deploy requires a local build step. Mitigated by the build commands being
well-documented and reproducible.

### DD-2 — Webhook Scheduler Trigger

**Decision:** The scheduler is triggered via the `/cron/{secret}` webhook rather than relying on
`php artisan schedule:work` daemon.

**Rationale:** Shared hosting cron runs every 5-15 minutes, which is fine for batch operations
but too coarse for time-sensitive jobs. The webhook can be called by a 5-15 minute cron entry
on the host, allowing the application to run scheduled tasks within that window.

**Trade-off:** Scheduler granularity is limited by the host's cron interval. Acceptable —
most scheduled jobs (cleanup, backup) run on a daily/weekly cadence.

---

## 8. Success Metrics

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Deploy to cheap shared hosting | ≤ 30 minutes end-to-end | Time from upload to `system:health` pass |
| Job loss on shared hosting | None — jobs run synchronously | No failed_jobs on sync connection |
| Core feature coverage | 100% (auth, registration, attendance, logbook, assignments, assessments, reports, certificates) | Functional test suite under shared-hosting preset |

---

## 9. Roadmap

### Prerequisites
This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [deployment.md](06IB6-deployment.md) | Deployment profile catalog, `DEPLOY_PROFILE` env var, `deploy:detect`, `deploy:configure` |
| [system-maintenance.md](E1MSJ-system-maintenance.md) | `system:health` command used as the deployment acceptance gate |
| [installation.md](8NZAU-installation.md) | `setup:install` provisioning, environment audit, `.env` handling, manual symlink |

### Build Guide
After implementing this spec, school IT staff can deploy Internara on cheap shared hosting by
uploading a pre-built artifact, setting the document root, configuring `.env`, and adding a
single cron entry. No SSH, no Composer, no Node at runtime.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [deployment.md](06IB6-deployment.md) | Profile detection and `DEPLOY_PROFILE` env var apply the `shared-hosting` preset drivers |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| --- | --------------------------------- | ------ | ----- | -------- |

## Quick References

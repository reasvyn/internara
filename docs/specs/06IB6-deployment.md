# Conditional Deployment — Shared Hosting & Docker VPS

> **Spec ID:** 06IB6

## Description

Specification for delivering Internara to two supported hosting conditions — limited/conventional
shared hosting and Docker-based VPS — using a single codebase with conditionally adapted runtime
configuration. Defines the deployment profile model, automatic environment detection with manual
override, per-profile environment presets, and the post-deploy verification gate. The Docker VPS
path supports deployment **without a working-copy repository on the server**: the image is built
directly from a public Git URL build context, or pulled from a container registry.

---

## 1. Problem Statements

### PS-1 — Two Environments, Mutually Exclusive Capabilities

Shared hosting provides PHP 8.4, MySQL/MariaDB, and limited-interval cron, but no long-running
daemons, no Redis/Memcached, no Composer/Node at runtime, and usually no SSH. A Docker VPS provides
daemon capability (scheduler, queue workers), Nginx, and optional Redis. No single static
configuration can serve both — a config tuned for one breaks silently on the other.

### PS-2 — Manual Per-Environment Tuning Is Error-Prone

The existing deployment documentation (`docs/guides/infra/deployment.md`) describes three static
paths that require operators to hand-edit `.env` keys (`QUEUE_CONNECTION`, `CACHE_STORE`,
`SESSION_DRIVER`). Every key is a decision point; a wrong choice produces jobs that never run, email
that blocks requests, or sessions that don't persist — discovered only after users report failures.

### PS-3 — No Authoritative Delivery Contract

Docker configuration exists (`docker-compose.yml`, `Dockerfile`, `docker/shared-hosting/`) and
shared-hosting-optimized defaults exist in `.env.example`, but there is no specification that binds
them together: which preset belongs to which environment, how the environment is detected, how an
operator overrides detection, and how a deployment is verified. Without a contract, docs and Docker
config drift independently.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Deploy the same codebase to cheap conventional shared hosting with zero manual driver tuning |
| G2  | Deploy the same codebase to a Docker VPS via `docker compose up -d` with the full service stack |
| G3  | Auto-detect the target environment and recommend the correct deployment profile |
| G4  | Allow explicit manual override of the detected profile via a single environment variable |
| G5  | Reuse the existing `docker-compose.yml`, `Dockerfile`, `docker/shared-hosting/`, and `.env.example` rather than re-creating them |
| G6  | Verify every deployment with the existing `php artisan system:health` gate |
| G7  | Keep shared-hosting as the safe default profile when detection is inconclusive |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Kubernetes / multi-node orchestration (single-node Docker Compose only) |
| NG2  | Automatic server provisioning (Apache/Nginx config is documented, not generated) |
| NG3  | Full CI/CD pipeline automation (see `docs/guides/infra/ci-cd.md`) |
| NG4  | Migration tooling between hosting conditions (use backup/restore) |
| NG5  | HA / zero-downtime release strategies |
| NG6  | Multi-tenant or platform provisioning (single-tenant self-hosted) |

---

## 3. User Stories / Use Cases

### UC-1 — School Deploys on Cheap Conventional Shared Hosting

**Actor:** School IT staff (cPanel / FTP access only)

**Preconditions:** Shared hosting plan with PHP 8.4+, MySQL 8+ or MariaDB 10.6+, configurable document
root, and cron (5-15 minute intervals acceptable).

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

### UC-2 — Sysadmin Deploys on Docker VPS

**Actor:** Sysadmin

**Preconditions:** VPS with Docker + Compose, `APP_KEY` and `DB_PASSWORD` prepared.

**Flow:**
1. Sysadmin clones the repository onto the VPS.
2. Sysadmin runs `docker compose up -d` — `app`, `web`, and `db` start; the `app` entrypoint runs
   pending migrations and starts the scheduler (and queue worker when enabled).
3. Sysadmin runs `php artisan setup:install` inside the `app` container.
4. Sysadmin opens the signed setup URL and completes the setup wizard.
5. Sysadmin runs `php artisan system:health` inside the `app` container.

**Postconditions:** Full stack runs with sync queue, file cache, database sessions, a scheduler
daemon inside the `app` container, and a reverse-proxying nginx `web` service.

### UC-3 — Detection Recommends a Profile on an Unknown Server

**Actor:** Operator on a new server

**Preconditions:** Application files present; deployment profile not yet chosen.

**Flow:**
1. Operator runs `php artisan deploy:detect`.
2. Command probes Redis reachability, container/daemon capability, and Composer availability.
3. Command outputs a recommended profile (`shared-hosting` or `vps-docker`) with per-probe results.
4. Operator follows the recommendation or applies an explicit override.

**Postconditions:** Operator knows which profile matches the environment before tuning `.env`.

### UC-4 — Operator Overrides Auto-Detection

**Actor:** Operator

**Preconditions:** A shared-hosting-like environment that exposes Redis (e.g., a limited VPS running
Docker) — or the reverse.

**Flow:**
1. Detection recommends a profile the operator does not want (cost, simplicity, or constraints).
2. Operator sets `DEPLOY_PROFILE=shared-hosting` (or `vps-docker`) explicitly in `.env`.
3. Operator runs `deploy:configure --profile=...` to apply the preset, or writes the preset by hand.
4. Operator verifies with `php artisan system:health`.

**Postconditions:** The explicit profile takes precedence over detection; runtime matches the chosen
preset.

---

## 4. Functional Requirements

### 4.1 Deployment Profiles & Configuration

| ID    | Requirement |
| ----- | ----------- |
| FR-P1 | The system must define exactly two canonical deployment profiles: `shared-hosting` and `vps-docker` |
| FR-P2 | Profile presets must be declared in a single configuration source (`config/deployment.php`) |
| FR-P3 | The `shared-hosting` preset must map to: `QUEUE_CONNECTION=sync`, `CACHE_STORE=file`, `SESSION_DRIVER=database`, `BROADCAST_CONNECTION=log`, scheduler mode `webhook`, no Redis |
| FR-P4 | The `vps-docker` preset must map to: `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`, `SESSION_DRIVER=redis`, `BROADCAST_CONNECTION=log`, scheduler mode `daemon`, Redis required |
| FR-P5 | The active profile must be selectable via `DEPLOY_PROFILE` env var with values `shared-hosting`, `vps-docker`, or `auto` |
| FR-P6 | When `DEPLOY_PROFILE` is `auto` or unset, the active profile must be resolved by automatic detection (see 4.2) |
| FR-P7 | When detection is inconclusive, the system must default to the `shared-hosting` profile |
| FR-P8 | `config/deployment.php` must declare the default profile as `shared-hosting` |

### 4.2 Automatic Detection

| ID    | Requirement |
| ----- | ----------- |
| FR-D1 | The system must provide a `deploy:detect` artisan command that probes the environment and recommends a profile |
| FR-D2 | Detection must probe at minimum: container runtime presence, Redis reachability, daemon-capable extensions (pcntl/posix), and Composer availability at runtime |
| FR-D3 | Detection must recommend `vps-docker` when a container runtime is detected or Redis is reachable together with daemon capability |
| FR-D4 | Detection must recommend `shared-hosting` when no container, no Redis, or no daemon capability is detected |
| FR-D5 | Detection must be non-destructive: it only reads environment state and writes nothing |
| FR-D6 | Detection must support a `--json` flag for machine-readable output |
| FR-D7 | An explicitly set `DEPLOY_PROFILE` (non-`auto`) must always take precedence over the detection recommendation |

### 4.3 Profile Configuration Application

| ID    | Requirement |
| ----- | ----------- |
| FR-C1 | The system must provide a `deploy:configure` artisan command that applies a profile preset to `.env` |
| FR-C2 | `deploy:configure` must accept `--profile=shared-hosting\|vps-docker` and validate the value against the known profiles |
| FR-C3 | `deploy:configure` must write only driver keys (queue/cache/session/broadcast) and never secrets (DB_PASSWORD, APP_KEY, MAIL_PASSWORD) |
| FR-C4 | `deploy:configure` must be idempotent — running it twice produces the same `.env` state |
| FR-C5 | `deploy:configure` without `--profile` must use the resolved profile from FR-P6/FR-P7 |
| FR-C6 | Applying a profile must not modify `docker-compose.yml` or `Dockerfile` |

### 4.4 Shared Hosting Operation

| ID    | Requirement |
| ----- | ----------- |
| FR-SH1 | Shared hosting must not require a queue worker daemon — jobs run synchronously via `QUEUE_CONNECTION=sync` |
| FR-SH2 | Shared hosting must not require Redis or Memcached — cache is `file`, sessions are `database` |
| FR-SH3 | The scheduler must be triggerable via the `/cron/{secret}` webhook route (`routes/web/sysadmin.php`) when minute-level cron is unavailable |
| FR-SH4 | The deployable artifact must be buildable off-server — `composer install --optimize-autoloader --no-dev` and `npm run build` must be run before upload, and must not be required on the server |
| FR-SH5 | Shared hosting must support a configurable document root pointed at `public/` |
| FR-SH6 | The `public/storage` symlink must be creatable manually when SSH is unavailable (see [installation.md](8NZAU-installation.md)) |
| FR-SH7 | MySQL 8+ / MariaDB 10.6+ and SQLite must both be supported as the database on shared hosting |
| FR-SH8 | All core features (auth, registration, attendance, logbook, assignments, assessments, reports, certificates) must work under the shared-hosting preset |

### 4.5 Docker VPS Operation

| ID     | Requirement |
| ------ | ----------- |
| FR-VD1 | `docker-compose.yml` must provide the services: `app`, `web`, `db`. The queue worker and scheduler run **inside the `app` container**, managed by the Docker entrypoint via `RUN_QUEUE` / `RUN_SCHEDULER` env flags |
| FR-VD2 | The `app` service must run PHP-FPM from the project `Dockerfile`, depend on healthy `db`, and run `php artisan migrate --force` from the entrypoint before starting processes |
| FR-VD3 | The queue worker must be started by the entrypoint when `RUN_QUEUE=true` (`php artisan queue:work --sleep=3 --tries=3`). The default deploy uses `QUEUE_CONNECTION=sync` and needs no worker; a Redis-backed worker is supported when a `redis` service is present |
| FR-VD4 | The scheduler must be started by the entrypoint when `RUN_SCHEDULER=true` (`php artisan schedule:work` daemon) |
| FR-VD5 | The `web` service must be an nginx image built from `.docker/nginx.Dockerfile` proxying to `app:9000` on port 80 (configurable via `NGINX_PORT`) |
| FR-VD6 | The `db` service must be `mysql:8` with a named volume and healthcheck |
| FR-VD7 | Redis is optional — omitted from the default stack; the app image bundles the `phpredis` extension so a `redis:7-alpine` service can be added later without rebuilding the app |
| FR-VD8 | Application storage must persist via the `storage_data` named volume shared across `app` and `web`; compiled public assets must persist via the `app_data` named volume shared across `app` and `web` |
| FR-VD9 | Default runtime drivers must be non-Redis and match the shared-hosting preset: `QUEUE_CONNECTION=sync`, `CACHE_STORE=file`, `SESSION_DRIVER=database`, `BROADCAST_CONNECTION=log`; Redis-backed drivers remain supported when a `redis` service is present |
| FR-VD10 | `php artisan setup:install` and `php artisan system:health` must be runnable inside the `app` container |
| FR-VD11 | The `app` service must expose a healthcheck (`docker/fpm-healthcheck` probing FPM port 9000) and `web` must depend on `app` with `condition: service_healthy` |
| FR-VD12 | The `app` and `db` services must fail fast at start when required secrets are missing (`APP_KEY`, `DB_PASSWORD`) and use `restart: unless-stopped` |

### 4.6 Verification & Documentation

| ID    | Requirement |
| ----- | ----------- |
| FR-V1 | `php artisan system:health` must be the final acceptance gate for both deployment conditions |
| FR-V2 | `docs/guides/infra/deployment.md` must present the two profiles and their presets as the canonical deployment guide |
| FR-V3 | `docker/README.md` must document the mapping: `docker-compose.yml` = minimal 3-service Docker topology running shared-hosting drivers (FR-VD1–FR-VD12), `docker/shared-hosting/` = shared-hosting simulation |
| FR-V4 | `.env.example` must remain the shared-hosting-optimized default and document `DEPLOY_PROFILE` |
| FR-V5 | Adding a new profile must not require changes to application business code |

---

## 5. Non-Functional Requirements

### 5.1 Security

| ID     | Requirement |
| ------ | ----------- |
| NFR-S1 | `CRON_SECRET` must be required for the `/cron/{secret}` webhook; requests without a valid secret must be rejected (existing `CronController` behavior) |
| NFR-S2 | Detection output must not expose credentials, hostnames, or internal paths beyond probe pass/fail status |
| NFR-S3 | `deploy:configure` must never write secrets or clear `APP_KEY` |
| NFR-S4 | No secrets may be committed; `.env` remains excluded via `.gitignore` |

### 5.2 Performance

| ID     | Requirement |
| ------ | ----------- |
| NFR-P1* | `deploy:detect` must complete in under 5 seconds — manual perf, not unit-testable |
| NFR-P2* | Shared-hosting page loads must remain within the documented targets (cached < 500ms, uncached < 1.5s at 500 users) — manual perf, not unit-testable |
| NFR-P3* | Docker VPS default deploy must run with file cache and sync queue (sufficient for single-tenant low-volume PKL workloads); Redis-backed drivers must remain available when load demands them — infra, verified manually |

### 5.3 Reliability

| ID     | Requirement |
| ------ | ----------- |
| NFR-R1 | Detection must be safe to run repeatedly and at any time (idempotent, non-mutating) |
| NFR-R2 | An explicit profile override must survive `deploy:configure` re-runs |
| NFR-R3 | A deployment must be reproducible from documented commands alone (`composer install`, `npm run build`, `setup:install`, `system:health`) |
| NFR-R4 | Docker services must use healthchecks so Compose waits for `db` before starting the `app`, and for a healthy `app` before starting `web` |

### 5.4 Usability

| ID     | Requirement |
| ------ | ----------- |
| NFR-U1 | All new CLI output must be bilingual via `__()` (English and Indonesian) |
| NFR-U2 | `deploy:detect` output must explain each probe result in plain language |

### 5.5 Maintainability

| ID     | Requirement |
| ------ | ----------- |
| NFR-M1 | Presets must live only in `config/deployment.php` — docs and Docker files reference, never duplicate, the driver mapping |
| NFR-M2 | New commands must follow the Action/Console conventions and carry `declare(strict_types=1)` |
| NFR-M3 | All deployment behavior must be testable via the Pest suite (no manual-only steps) |

---

## 6. API / Data Contracts

### 6.1 Profile Configuration — `config/deployment.php`

```php
return [
    'default' => 'shared-hosting',

    'profiles' => [
        'shared-hosting' => [
            'queue'      => 'sync',
            'cache'      => 'file',
            'session'    => 'database',
            'broadcast'  => 'log',
            'scheduler'  => 'webhook',
            'redis'      => false,
        ],
        'vps-docker' => [
            'queue'      => 'redis',
            'cache'      => 'redis',
            'session'    => 'redis',
            'broadcast'  => 'log',
            'scheduler'  => 'daemon',
            'redis'      => true,
        ],
    ],
];
```

### 6.2 Environment Variable

```
DEPLOY_PROFILE=auto|shared-hosting|vps-docker   # default: auto
```

### 6.3 CLI Contracts

```php
// app/Core/.../Console/Commands/DeployDetectCommand.php
class DeployDetectCommand extends Command
{
    protected $signature = 'deploy:detect
        {--json : Output results as JSON}';

    public function handle(): int;
    // Output: recommended profile + per-probe status (pass/fail)
}

// app/Core/.../Console/Commands/DeployConfigureCommand.php
class DeployConfigureCommand extends Command
{
    protected $signature = 'deploy:configure
        {--profile= : shared-hosting|vps-docker (defaults to resolved profile)}';

    public function handle(): int;
    // Applies driver preset to .env; exits FAILURE on unknown profile
}
```

### 6.4 Detection Probes

| Probe | Signal | Recommended Profile |
| ----- | ------ | ------------------- |
| Container runtime | `/.dockerenv` present or cgroup contains `docker` | `vps-docker` |
| Redis reachability | TCP connect to `REDIS_HOST:REDIS_PORT` succeeds | `vps-docker` (with daemon) |
| Daemon capability | `pcntl` + `posix` extensions loaded | `vps-docker` (with Redis) |
| Composer at runtime | `composer` resolvable on PATH | informational only |

### 6.5 Docker Compose Services (existing — referenced, not re-specified)

| Service | Image | Purpose |
| ------- | ----- | ------- |
| `app` | Custom (Dockerfile) | PHP-FPM application server; entrypoint runs migrations + scheduler (`RUN_SCHEDULER`) and optional queue worker (`RUN_QUEUE`) |
| `web` | Custom (.docker/nginx.Dockerfile) | Reverse proxy → `app` |
| `db` | mysql:8 | Database |

Redis is optional (FR-VD7): no service by default; add a `redis:7-alpine` service and switch
`QUEUE_CONNECTION`/`CACHE_STORE`/`SESSION_DRIVER` when throughput demands it.

### 6.6 Scheduler Webhook (existing)

```php
// routes/web/sysadmin.php
Route::get('/cron/{secret}', CronController::class)
    ->name('cron')
    ->middleware('throttle:...');
```

---

## 7. Design Decisions

### DD-1 — Profile Presets as Single Source of Truth

**Decision:** All driver mappings live in `config/deployment.php`; `.env.example`, docs, and Docker
configs reference the profiles rather than re-declaring driver values.

**Rationale:** `docs/guides/infra/deployment.md` and `.env.example` already encode these values, but
in prose. Centralizing them removes the risk of docs↔config drift (Clean Code / dedup doctrine) and
makes `deploy:configure` data-driven.

**Trade-off:** A new config file must be kept in sync with `.env.example` defaults. Mitigated by
FR-V4 and a single doc reference table.

### DD-2 — Auto-Detection with Explicit Override

**Decision:** Detection recommends a profile, but an explicit `DEPLOY_PROFILE` always wins.

**Rationale:** Auto-detection lowers the skill bar for school IT staff (UC-3); explicit override
preserves operator control for ambiguous environments such as a low-cost VPS that happens to expose
Redis (UC-4). Detection-only would force a config choice; override-only would lose the guidance.

**Trade-off:** Two code paths to maintain. Mitigated by a small, deterministic probe set (6.4).

### DD-3 — Shared Hosting as Safe Default

**Decision:** The default profile is `shared-hosting` when detection is inconclusive or unset.

**Rationale:** The shared-hosting preset is the most constrained and therefore the safest fallback —
it never assumes a daemon, Redis, or runtime Composer. A `vps-docker` default could silently require
services that are absent on a bare environment.

**Trade-off:** A VPS deployer who skips detection gets sync queue (functional, lower throughput).
Documented in UC-2/FR-P7.

### DD-4 — Reuse Existing Docker Topology

**Decision:** The spec references, does not redefine, the `docker-compose.yml` service topology; the
compose stack is the single source of truth for services.

**Rationale:** Redefining the topology in the spec would create a second source of truth and let the
two drift (Clean Code / dedup doctrine).

**Trade-off:** The compose file must stay in sync with FR-VD1–FR-VD12; enforced by the arch-guard
scanners, `docker compose config` validation, and the spec↔code audit (`spec-audit`).

### DD-5 — Configure Applies Drivers Only, Never Secrets

**Decision:** `deploy:configure` writes only driver keys (queue/cache/session/broadcast); it never
writes or rewrites secrets (`DB_PASSWORD`, `APP_KEY`, `MAIL_PASSWORD`).

**Rationale:** Secrets are deployment-specific and environment-owned; a config command that writes
them would encourage committing them or complicate rotation.

**Trade-off:** Operators must supply secrets themselves. Mitigated by fail-fast env validation in the
compose file (FR-VD12) and `.env.example` documentation.

### DD-6 — Minimal Docker Topology Without Redis

**Decision:** The production `docker-compose.yml` provides a minimal three-service stack (`app`,
`web`, `db`) with no Redis. The scheduler and optional queue worker run inside the `app` container,
managed by the Docker entrypoint (`RUN_SCHEDULER` / `RUN_QUEUE`). Runtime drivers default to the
non-Redis shared-hosting set: `QUEUE_CONNECTION=sync`, `CACHE_STORE=file`,
`SESSION_DRIVER=database`, `BROADCAST_CONNECTION=log`.

**Rationale:** Internara is single-tenant and low-volume (an SMA/SMK school). A sync queue executes
jobs inline during the HTTP request — entirely sufficient for certificate/PDF generation and
notifications at this scale — and removes a whole class of operational burden (Redis availability,
memory, backup). Consolidating scheduler/queue into the app container keeps the stack to three
services and a single restart policy. The image still bundles `phpredis` so a Redis service can be
added later without rebuilding the app (FR-VD7, FR-VD9).

**Trade-off:** Heavy or long-running document jobs block the request under the sync driver; the
`documents` queue pipeline exists specifically to isolate such work once a Redis-backed worker is
enabled. The entrypoint-managed background processes rely on the container init (`exec` → php-fpm)
for lifecycle; this is acceptable for a single-app container and is revisited if graceful worker
shutdown becomes a requirement.

---

## 8. Success Metrics

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Deploy to cheap shared hosting | ≤ 30 minutes end-to-end | Time from upload to `system:health` pass |
| Deploy to Docker VPS | ≤ 15 minutes | Time from `docker compose up -d` to `system:health` pass |
| Zero manual driver tuning | All driver keys set by preset | `deploy:configure` output vs `.env` diff |
| Detection accuracy | Recommended profile matches environment in 100% of probes | `deploy:detect` against both reference environments |
| Detection runtime | < 5 seconds | `time php artisan deploy:detect` |
| Post-deploy verification | `system:health` passes in both conditions | Health check exit code |
| Job loss on shared hosting | None — jobs run synchronously | No failed_jobs on sync connection |

---

## 9. Roadmap

### Prerequisites

This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|------------------|
| [system-requirements.md](J68GZ-system-requirements.md) (J68GZ) | PHP/extensions contract (FR-SY1–FR-SY3), database portability (FR-DB2–FR-DB4) the presets rely on |
| [installation.md](8NZAU-installation.md) (8NZAU) | `setup:install` provisioning, environment audit, and `.env` handling used in both deployment paths |
| [job-queue-infrastructure.md](8FVZA-job-queue-infrastructure.md) (8FVZA) | Queue driver contract (sync/redis) and worker lifecycle consumed by `vps-docker` |
| [system-maintenance.md](E1MSJ-system-maintenance.md) (E1MSJ) | `system:health` command used as the deployment acceptance gate (FR-V1) |

### Build Guide

Implement the conditional deployment mechanism: add `config/deployment.php` with the two profile
presets, `deploy:detect` (probes per 6.4, `--json` output), and `deploy:configure` (driver-only
`.env` writer, idempotent). Then align `docs/guides/infra/deployment.md` and `docker/README.md` to
the two-profile model and verify both paths end-to-end with `setup:install` + `system:health`.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [backup-system.md](HBXCI-backup-system.md) (HBXCI) | Backup scheduling must be configured per profile — webhook cron on shared hosting, daemon scheduler on VPS |

---

## Quick References

- `config/deployment.php` — profile presets (new, single source of truth)
- `docker-compose.yml` — VPS Docker topology (existing, referenced)
- `docker/shared-hosting/docker-compose.yml` — shared-hosting simulation (existing)
- `Dockerfile` — PHP-FPM app image (existing)
- `.env.example` — shared-hosting-optimized defaults (existing)
- `docs/guides/infra/deployment.md` — canonical deployment guide (to be aligned)
- `docs/guides/infra/ci-cd.md` — CI/CD pipeline and artifact requirements
- `routes/web/sysadmin.php` — `/cron/{secret}` webhook scheduler route
- `app/SysAdmin/Http/Controllers/CronController.php` — webhook cron validation (existing)
- **Related specs:** [system-requirements.md](J68GZ-system-requirements.md) (J68GZ), [installation.md](8NZAU-installation.md) (8NZAU), [job-queue-infrastructure.md](8FVZA-job-queue-infrastructure.md) (8FVZA), [system-maintenance.md](E1MSJ-system-maintenance.md) (E1MSJ)

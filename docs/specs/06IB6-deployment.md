# Conditional Deployment — Profile Catalog, Detection & Configuration

> **Spec ID:** 06IB6

## Description

Specification for the deployment profile catalog, automatic environment detection, per-profile
configuration application, and post-deploy verification gate. Two hosting conditions are supported —
shared hosting and Docker VPS — each defined in its own spec:
[docker-vps-deployment.md](06IB6-docker-vps-deployment.md) and
[shared-hosting-deployment.md](06IB6-shared-hosting-deployment.md). This spec defines the
shared infrastructure (profile catalog, detection, `deploy:configure`, health gate) used by both.

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
| G3  | Auto-detect the target environment and recommend the correct deployment profile |
| G4  | Allow explicit manual override of the detected profile via a single environment variable |
| G5  | Reuse the existing `docker-compose.yml`, `Dockerfile`, `docker/shared-hosting/`, and `.env.example` rather than re-creating them |
| G6  | Verify every deployment with the existing `php artisan system:health` gate |
| G7  | Keep shared-hosting as the safe default profile when detection is inconclusive |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Kubernetes / multi-node orchestration (single-node Docker Compose only) |
| NG2  | Per-hosting-condition details (see docker-vps-deployment.md and shared-hosting-deployment.md) |
| NG3  | Full CI/CD pipeline automation (see `docs/guides/infra/ci-cd.md`) |
| NG4  | Migration tooling between hosting conditions (use backup/restore) |
| NG5  | HA / zero-downtime release strategies |
| NG6  | Multi-tenant or platform provisioning (single-tenant self-hosted) |

---

## 3. User Stories / Use Cases

| ID | Actor | Action / Expected Outcome |
|----|-------|---------------------------|
| UC-06IB6-1 | School | Deploys on cheap conventional shared hosting |
| UC-06IB6-2 | Sysadmin | Deploys on Docker VPS |
| UC-06IB6-3 | System | Detection recommends a profile on an unknown server |
| UC-06IB6-4 | Operator | Overrides auto-detection |

### UC-06IB6-3 — Detection Recommends a Profile on an Unknown Server

**Actor:** Operator on a new server

**Preconditions:** Application files present; deployment profile not yet chosen.

**Flow:**
1. Operator runs `php artisan deploy:detect`.
2. Command probes Redis reachability, container/daemon capability, and Composer availability.
3. Command outputs a recommended profile (`shared-hosting` or `vps-docker`) with per-probe results.
4. Operator follows the recommendation or applies an explicit override.

**Postconditions:** Operator knows which profile matches the environment before tuning `.env`.

### UC-06IB6-4 — Operator Overrides Auto-Detection

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
| FR-06IB6-P1 | The system must define exactly two canonical deployment profiles: `shared-hosting` and `vps-docker` |
| FR-06IB6-P2 | Profile presets must be declared in a single configuration source (`config/deployment.php`) |
| FR-06IB6-P3 | The `shared-hosting` preset must map to: `QUEUE_CONNECTION=sync`, `CACHE_STORE=file`, `SESSION_DRIVER=database`, `BROADCAST_CONNECTION=log`, scheduler mode `webhook`, no Redis |
| FR-06IB6-P4 | The `vps-docker` preset must map to: `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`, `SESSION_DRIVER=redis`, `BROADCAST_CONNECTION=log`, scheduler mode `daemon`, Redis required |
| FR-06IB6-P5 | The active profile must be selectable via `DEPLOY_PROFILE` env var with values `shared-hosting`, `vps-docker`, or `auto` |
| FR-06IB6-P6 | When `DEPLOY_PROFILE` is `auto` or unset, the active profile must be resolved by automatic detection (see 4.2) |
| FR-06IB6-P7 | When detection is inconclusive, the system must default to the `shared-hosting` profile |
| FR-06IB6-P8 | `config/deployment.php` must declare the default profile as `shared-hosting` |

### 4.2 Automatic Detection

| ID    | Requirement |
| ----- | ----------- |
| FR-06IB6-D1 | The system must provide a `deploy:detect` artisan command that probes the environment and recommends a profile |
| FR-06IB6-D2 | Detection must probe at minimum: container runtime presence, Redis reachability, daemon-capable extensions (pcntl/posix), and Composer availability at runtime |
| FR-06IB6-D3 | Detection must recommend `vps-docker` when a container runtime is detected or Redis is reachable together with daemon capability |
| FR-06IB6-D4 | Detection must recommend `shared-hosting` when no container, no Redis, or no daemon capability is detected |
| FR-06IB6-D5 | Detection must be non-destructive: it only reads environment state and writes nothing |
| FR-06IB6-D6 | Detection must support a `--json` flag for machine-readable output |
| FR-06IB6-D7 | An explicitly set `DEPLOY_PROFILE` (non-`auto`) must always take precedence over the detection recommendation |

### 4.3 Profile Configuration Application

| ID    | Requirement |
| ----- | ----------- |
| FR-06IB6-C1 | The system must provide a `deploy:configure` artisan command that applies a profile preset to `.env` |
| FR-06IB6-C2 | `deploy:configure` must accept `--profile=shared-hosting\|vps-docker` and validate the value against the known profiles |
| FR-06IB6-C3 | `deploy:configure` must write only driver keys (queue/cache/session/broadcast) and never secrets (DB_PASSWORD, APP_KEY, MAIL_PASSWORD) |
| FR-06IB6-C4 | `deploy:configure` must be idempotent — running it twice produces the same `.env` state |
| FR-06IB6-C5 | `deploy:configure` without `--profile` must use the resolved profile from FR-06IB6-P6/FR-06IB6-P7 |
| FR-06IB6-C6 | Applying a profile must not modify `docker-compose.yml` or `Dockerfile` |

### 4.6 Verification & Documentation

| ID    | Requirement |
| ----- | ----------- |
| FR-06IB6-V1 | `php artisan system:health` must be the final acceptance gate for both deployment conditions |
| FR-06IB6-V2 | `docs/guides/infra/deployment.md` must present the two profiles and their presets as the canonical deployment guide |
| FR-06IB6-V3 | `docker/README.md` must document the mapping: `docker-compose.yml` = minimal 3-service Docker topology running shared-hosting drivers (docker-vps-deployment.md (FR-06IB6-VD1–FR-06IB6-VD12)), `docker/shared-hosting/` = shared-hosting simulation |
| FR-06IB6-V4 | `.env.example` must remain the shared-hosting-optimized default and document `DEPLOY_PROFILE` |
| FR-06IB6-V5 | Adding a new profile must not require changes to application business code |

---

## 5. Non-Functional Requirements

### 5.1 Security

| ID     | Requirement |
| ------ | ----------- |
| NFR-06IB6-S1 | `CRON_SECRET` must be required for the `/cron/{secret}` webhook; requests without a valid secret must be rejected (existing `CronController` behavior) |
| NFR-06IB6-S2 | Detection output must not expose credentials, hostnames, or internal paths beyond probe pass/fail status |
| NFR-06IB6-S3 | `deploy:configure` must never write secrets or clear `APP_KEY` |
| NFR-06IB6-S4 | No secrets may be committed; `.env` remains excluded via `.gitignore` |

### 5.3 Reliability

| ID     | Requirement |
| ------ | ----------- |
| NFR-06IB6-R1 | Detection must be safe to run repeatedly and at any time (idempotent, non-mutating) |
| NFR-06IB6-R2 | An explicit profile override must survive `deploy:configure` re-runs |
| NFR-06IB6-R3 | A deployment must be reproducible from documented commands alone (`composer install`, `npm run build`, `setup:install`, `system:health`) |
| NFR-06IB6-R4 | Docker services must use healthchecks so Compose waits for `db` before starting the `app`, and for a healthy `app` before starting `web` |

### 5.4 Usability

| ID     | Requirement |
| ------ | ----------- |
| NFR-06IB6-U1 | All new CLI output must be bilingual via `__()` (English and Indonesian) |
| NFR-06IB6-U2 | `deploy:detect` output must explain each probe result in plain language |

### 5.5 Maintainability

| ID     | Requirement |
| ------ | ----------- |
| NFR-06IB6-M1 | Presets must live only in `config/deployment.php` — docs and Docker files reference, never duplicate, the driver mapping |
| NFR-06IB6-M2 | New commands must follow the Action/Console conventions and carry `declare(strict_types=1)` |
| NFR-06IB6-M3 | All deployment behavior must be testable via the Pest suite (no manual-only steps) |

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
// app/Modules/Core/.../Console/Commands/DeployDetectCommand.php
class DeployDetectCommand extends Command
{
    protected $signature = 'deploy:detect
        {--json : Output results as JSON}';

    public function handle(): int;
    // Output: recommended profile + per-probe status (pass/fail)
}

// app/Modules/Core/.../Console/Commands/DeployConfigureCommand.php
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
FR-06IB6-V4 and a single doc reference table.

### DD-2 — Auto-Detection with Explicit Override

**Decision:** Detection recommends a profile, but an explicit `DEPLOY_PROFILE` always wins.

**Rationale:** Auto-detection lowers the skill bar for school IT staff (UC-06IB6-3); explicit override
preserves operator control for ambiguous environments such as a low-cost VPS that happens to expose
Redis (UC-06IB6-4). Detection-only would force a config choice; override-only would lose the guidance.

**Trade-off:** Two code paths to maintain. Mitigated by a small, deterministic probe set (6.4).

### DD-3 — Shared Hosting as Safe Default

**Decision:** The default profile is `shared-hosting` when detection is inconclusive or unset.

**Rationale:** The shared-hosting preset is the most constrained and therefore the safest fallback —
it never assumes a daemon, Redis, or runtime Composer. A `vps-docker` default could silently require
services that are absent on a bare environment.

**Trade-off:** A VPS deployer who skips detection gets sync queue (functional, lower throughput).
Documented in UC-06IB6-2/FR-06IB6-P7.

### DD-5 — Configure Applies Drivers Only, Never Secrets

**Decision:** `deploy:configure` writes only driver keys (queue/cache/session/broadcast); it never
writes or rewrites secrets (`DB_PASSWORD`, `APP_KEY`, `MAIL_PASSWORD`).

**Rationale:** Secrets are deployment-specific and environment-owned; a config command that writes
them would encourage committing them or complicate rotation.

**Trade-off:** Operators must supply secrets themselves. Mitigated by fail-fast env validation in the
compose file (FR-06IB6-DV12, in [docker-vps-deployment.md](06IB6-docker-vps-deployment.md)) and `.env.example` documentation.

## 8. Success Metrics

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Zero manual driver tuning | All driver keys set by preset | `deploy:configure` output vs `.env` diff |
| Detection accuracy | Recommended profile matches environment in 100% of probes | `deploy:detect` against both reference environments |
| Detection runtime | < 5 seconds | `time php artisan deploy:detect` |
| Post-deploy verification | `system:health` passes in both conditions | Health check exit code |

Per-hosting-condition metrics (deploy time, scheduler uptime) are tracked in
[docker-vps-deployment.md](06IB6-docker-vps-deployment.md) and
[shared-hosting-deployment.md](06IB6-shared-hosting-deployment.md).

---

## 9. Roadmap

### Prerequisites

This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|------------------|
| [system-requirements.md](J68GZ-system-requirements.md) (J68GZ) | PHP/extensions contract, database portability the presets rely on |
| [installation.md](8NZAU-installation.md) (8NZAU) | `setup:install` provisioning, environment audit, and `.env` handling used in both deployment paths |
| [job-queue-infrastructure.md](8FVZA-job-queue-infrastructure.md) (8FVZA) | Queue driver contract (sync/redis) and worker lifecycle consumed by `vps-docker` |
| [system-maintenance.md](E1MSJ-system-maintenance.md) (E1MSJ) | `system:health` command used as the deployment acceptance gate (FR-06IB6-V1) |

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

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| --- | --------------------------------- | ------ | ----- | -------- |


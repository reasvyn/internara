# Docker VPS Deployment — Docker-Based Virtual Private Server Operations

> **Spec ID:** 06IB7

## Description

Specification for deploying Internara to a Docker VPS using `docker compose`. Defines the
service topology, container orchestration, volume management, scheduler/queue daemon lifecycle,
and health checks. The deployment profile catalog, environment detection, and configuration
application are defined in [deployment.md](06IB6-deployment.md).

---

## 1. Problem Statements

### PS-1 — Docker-Based VPS Requires a Defined Service Topology

A Docker VPS provides daemon capability (scheduler, queue workers), Nginx, and optional Redis,
but requires a documented compose stack that ties together the application container, web
server, database, and persistent storage. Without a specification, the compose file and the
application contract drift independently.

### PS-2 — Scheduler and Queue Workers Must Run Inside the App Container

On VPS (unlike shared hosting), background jobs and the scheduler can run as long-running
processes rather than triggered by cron webhooks. The entrypoint must manage these processes
without requiring the operator to manually start separate containers or cron jobs.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Provide a minimal three-service Docker Compose stack: `app`, `web`, `db` |
| G2  | Start scheduler daemon and optional queue worker via entrypoint `RUN_SCHEDULER` / `RUN_QUEUE` flags |
| G3  | Persist application storage and compiled assets across container restarts via named volumes |
| G4  | Healthcheck the `app` service via FPM ping and the `web` service via HTTP |
| G5  | Fail fast at start when required secrets are missing |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Kubernetes / multi-node orchestration (single-node Docker Compose only) |
| NG2  | Redis service in the default stack (optional; add via compose override) |
| NG3  | Automatic server provisioning (Docker must be pre-installed) |
| NG4  | Build-time asset compilation (must be run before `docker compose up`) |

---

## 3. User Stories / Use Cases

### UC-06IB7-1 — Sysadmin Deploys on Docker VPS

**Actor:** Sysadmin

**Preconditions:** VPS with Docker + Compose installed, `APP_KEY` and `DB_PASSWORD` prepared.

**Flow:**
1. Sysadmin clones the repository onto the VPS.
2. Sysadmin runs `docker compose up -d` — `app`, `web`, and `db` start; the `app` entrypoint runs
   pending migrations and starts the scheduler (and queue worker when enabled).
3. Sysadmin runs `php artisan setup:install` inside the `app` container.
4. Sysadmin opens the signed setup URL and completes the setup wizard.
5. Sysadmin runs `php artisan system:health` inside the `app` container.

**Postconditions:** Full stack runs with scheduler daemon inside the `app` container, nginx reverse
proxy, and a database service.

### UC-06IB7-2 — Sysadmin Enables Redis-Backed Queue

**Actor:** Sysadmin

**Preconditions:** Redis service added via compose override.

**Flow:**
1. Sysadmin adds a `redis:7-alpine` service to `docker-compose.override.yml`
2. Sysadmin sets `RUN_QUEUE=true` in the `app` service environment
3. Sysadmin restarts: `docker compose up -d`
4. Queue worker starts inside the `app` container, consuming jobs from Redis

**Postconditions:** Background jobs run via Redis-backed queue worker

---

## 4. Functional Requirements

| ID     | Requirement |
| ------ | ----------- |
| FR-06IB7-DV1 | `docker-compose.yml` must provide the services: `app`, `web`, `db`. The queue worker and scheduler run **inside the `app` container**, managed by the Docker entrypoint via `RUN_QUEUE` / `RUN_SCHEDULER` env flags |
| FR-06IB7-DV2 | The `app` service must run PHP-FPM from the project `Dockerfile`, depend on healthy `db`, and run `php artisan migrate --force` from the entrypoint before starting processes |
| FR-06IB7-DV3 | The queue worker must be started by the entrypoint when `RUN_QUEUE=true` (`php artisan queue:work --sleep=3 --tries=3`). The default deploy uses `QUEUE_CONNECTION=sync` and needs no worker; a Redis-backed worker is supported when a `redis` service is present |
| FR-06IB7-DV4 | The scheduler must be started by the entrypoint when `RUN_SCHEDULER=true` (`php artisan schedule:work` daemon) |
| FR-06IB7-DV5 | The `web` service must be an nginx image built from `.docker/nginx.Dockerfile` proxying to `app:9000` on port 80 (configurable via `NGINX_PORT`) |
| FR-06IB7-DV6 | The `db` service must be `mysql:8` with a named volume and healthcheck |
| FR-06IB7-DV7 | Redis is optional — omitted from the default stack; the app image bundles the `phpredis` extension so a `redis:7-alpine` service can be added later without rebuilding the app |
| FR-06IB7-DV8 | Application storage must persist via the `storage_data` named volume shared across `app` and `web`; compiled public assets must persist via the `app_data` named volume shared across `app` and `web` |
| FR-06IB7-DV9 | Default runtime drivers must be non-Redis and match the shared-hosting preset: `QUEUE_CONNECTION=sync`, `CACHE_STORE=file`, `SESSION_DRIVER=database`, `BROADCAST_CONNECTION=log`; Redis-backed drivers remain supported when a `redis` service is present |
| FR-06IB7-DV10 | `php artisan setup:install` and `php artisan system:health` must be runnable inside the `app` container |
| FR-06IB7-DV11 | The `app` service must expose a healthcheck (`docker/fpm-healthcheck` probing FPM port 9000) and `web` must depend on `app` with `condition: service_healthy` |
| FR-06IB7-DV12 | The `app` and `db` services must fail fast at start when required secrets are missing (`APP_KEY`, `DB_PASSWORD`) and use `restart: unless-stopped` |

---

## 5. Non-Functional Requirements

| ID     | Requirement |
| ------ | ----------- |
| NFR-06IB7-R1 | Docker services must use healthchecks so Compose waits for `db` before starting the `app`, and for a healthy `app` before starting `web` |
| NFR-06IB7-R2 | A deployment must be reproducible from documented commands alone |
| NFR-06IB7-S1 | No secrets may be committed; `.env` remains excluded via `.gitignore` |

---

## 6. API / Data Contracts

### Docker Compose Services

| Service | Image | Purpose |
| ------- | ----- | ------- |
| `app` | Custom (Dockerfile) | PHP-FPM application server; entrypoint runs migrations + scheduler (`RUN_SCHEDULER`) and optional queue worker (`RUN_QUEUE`) |
| `web` | Custom (.docker/nginx.Dockerfile) | Reverse proxy → `app` |
| `db` | mysql:8 | Database |

Redis is optional (FR-06IB7-DV7): no service by default; add a `redis:7-alpine` service and switch
`QUEUE_CONNECTION`/`CACHE_STORE`/`SESSION_DRIVER` when throughput demands it.

### Entrypoint Environment Flags

| Flag | Default | Purpose |
| ---- | ------- | ------- |
| `RUN_SCHEDULER` | `true` | Start `php artisan schedule:work` daemon |
| `RUN_QUEUE` | `false` | Start `php artisan queue:work` (requires Redis) |
| `NGINX_PORT` | `80` | Exposed port for the web service |

---

## 7. Design Decisions

### DD-1 — Minimal Docker Topology Without Redis

**Decision:** The production `docker-compose.yml` provides a minimal three-service stack (`app`,
`web`, `db`) with no Redis. The scheduler and optional queue worker run inside the `app` container,
managed by the Docker entrypoint (`RUN_SCHEDULER` / `RUN_QUEUE`).

**Rationale:** Internara is single-tenant and low-volume (an SMA/SMK school). A sync queue executes
jobs inline during the HTTP request — entirely sufficient for certificate/PDF generation and
notifications at this scale — and removes a whole class of operational burden (Redis availability,
memory, backup). Consolidating scheduler/queue into the app container keeps the stack to three
services and a single restart policy. The image still bundles `phpredis` so a Redis service can be
added later without rebuilding the app.

**Trade-off:** Heavy or long-running document jobs block the request under the sync driver; the
`documents` queue pipeline exists specifically to isolate such work once a Redis-backed worker is
enabled.

### DD-2 — Entrypoint-Managed Background Processes

**Decision:** The scheduler and optional queue worker run as subprocesses managed by the PHP
entrypoint, not as separate containers.

**Rationale:** Single-process container is simpler to operate and shares the same filesystem
volumes without cross-container volume mounting complexity.

**Trade-off:** If the entrypoint process crashes, both scheduler and queue worker stop.
Acceptable for a single-app container; revisited if graceful worker shutdown becomes a requirement.

---

## 8. Success Metrics

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Deploy to Docker VPS | ≤ 15 minutes | Time from `docker compose up -d` to `system:health` pass |
| Healthcheck propagation | Compose waits for `db` → `app` → `web` | Service startup order |
| Scheduler uptime | 24/7 within container | Process survives FPM worker restart |

---

## 9. Roadmap

### Prerequisites
This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [deployment.md](06IB6-deployment.md) | Deployment profile catalog, `DEPLOY_PROFILE` env var, `deploy:detect`, `deploy:configure` |
| [system-maintenance.md](E1MSJ-system-maintenance.md) | `system:health` command used as the deployment acceptance gate |

### Build Guide
After implementing this spec, `docker compose up -d` starts a fully operational Internara instance
with persistent storage, a scheduler daemon, and a healthcheck chain. The compose stack is the
single source of truth for the Docker topology.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [deployment.md](06IB6-deployment.md) | Profile detection and `DEPLOY_PROFILE` env var apply the `vps-docker` preset drivers |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| --- | --------------------------------- | ------ | ----- | -------- |

## Quick References

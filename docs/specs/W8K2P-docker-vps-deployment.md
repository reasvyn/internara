# Docker VPS Deployment — Docker-Based Virtual Private Server Operations

> **Spec ID:** W8K2P
> **Status:** Full
> **Owner:** Core
> **Depends on:** [system-maintenance](E1MSJ-system-maintenance.md) (E1MSJ)

## Description

Specifies the Docker VPS hosting condition and deployment lifecycle for Internara: a minimal three-service Compose stack, entrypoint-managed scheduler and queue lifecycle, named-volume persistence, ordered healthchecks, and fail-fast startup. It governs the `vps-docker` profile in `config/deployment.php`, capability detection via `deploy:detect`, driver configuration via `deploy:configure`, and the three-tier promotion lifecycle (`dev` > `pre-release` > `release`) where pre-release (`alpha`, `beta`, `rc`) is deployed to `staging.internara.web.id` via Docker.

---

## 1. Problem Statements

### PS-1 — Docker-Based VPS Requires a Defined Service Topology

A Docker VPS offers daemon capability, Nginx, and optional Redis, but without a specified compose stack the application container, web server, database, and storage drift apart — image changes assume volumes that no longer exist, or the proxy targets a port nothing listens on.
**→ Requirement:** FR-DOCK-001/005/006 (three-service topology), FR-DOCK-008 (volume persistence).

### PS-2 — Scheduler and Queue Workers Must Run Inside the App Container

Unlike shared hosting, a VPS can host long-running processes — but only if something starts them. Requiring operators to launch separate worker containers or hand-rolled cron turns every reboot into a checklist and every checklist into an outage.
**→ Requirement:** FR-DOCK-003/004 (entrypoint-managed scheduler and queue worker).

### PS-3 — Promotion Lifecycle Lacks Staging Parity Without Dedicated Pre-Release Deploy

Without automated pre-release deployments to a real VPS environment, release candidates (`alpha`, `beta`, `rc`) are tested only in synthetic CI environments. Volume mounts, reverse proxy headers, and container entrypoints can fail silently when first pushed to production.
**→ Requirement:** FR-DOCK-014 (three-tier lifecycle), FR-DOCK-015 (pre-release deploy to `staging.internara.web.id`), FR-DOCK-016 (production release).

### PS-4 — VPS Environment Detection and Configuration

Operators deploying on VPS need automated capability probing and driver application so `vps-docker` drivers engage without error-prone manual `.env` file editing.
**→ Requirement:** FR-DOCK-017 (preset catalog), FR-DOCK-018 (detection probe), FR-DOCK-019 (configuration applicator), FR-DOCK-020 (health gate).

---

## 2. Goals & Non-Goals

### Goals

- **Minimal three-service stack** — `app`, `web`, `db`, nothing else by default. *Why:* a single-tenant school system must stay operable by staff who manage it alongside everything else.
- **Pre-release deployment on staging.internara.web.id** — every pre-release tag (`alpha`, `beta`, `rc`) deploys to this staging VPS host under domain `staging.internara.web.id` using Docker Compose. *Why:* true staging parity validates containers and reverse proxy before production.
- **Entrypoint-owned background processes** — scheduler daemon and optional queue worker start via `RUN_SCHEDULER` / `RUN_QUEUE` flags. *Why:* reboots recover without operator checklists.
- **Persistence across restarts** — application storage and compiled assets live on named volumes shared by `app` and `web`. *Why:* a container restart must never look like data loss.
- **Ordered, healthchecked startup** — FPM ping for `app`, HTTP for `web`, Compose waits in chain. *Why:* first-boot races are the flakiest failures and the easiest to prevent.
- **Fail-fast on missing secrets** — absent `APP_KEY` or `DB_PASSWORD` stops startup immediately. *Why:* a loudly refused boot beats a half-running system every time.

### Non-Goals

- **Kubernetes / multi-node orchestration**. *Why:* single-node Compose covers the Tier-2 band; orchestration is post-MVP depth with no buyer at school scale.
- **Redis in the default stack**. *Why:* the default runs Tier-1 drivers; Redis arrives later via compose override when throughput demands it.
- **Automatic server provisioning**. *Why:* Docker pre-installed is a stated prerequisite; provisioning automation is a different product.
- **Build-time asset compilation inside the stack**. *Why:* assets build before `docker compose up`, keeping images lean and startups fast.

---

## 3. User Stories / Use Cases

VPS operator journeys, both verified against a running stack through compose lifecycle and the health gate.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-DOCK-001 | Sysadmin deploys the full stack on a Docker VPS: compose up, in-container provisioning, setup wizard, health gate | P0 | F | Full |
| UC-DOCK-002 | Sysadmin graduates to a Redis-backed queue via compose override and the queue flag, with jobs flowing through Redis | P1 | F | Full |
| UC-DOCK-003 | Release team pushes a pre-release tag (alpha, beta, rc) triggering automated Docker deployment to staging.internara.web.id with health gate verification | P0 | F | Full |
| UC-DOCK-004 | Sysadmin on a Docker VPS runs deploy:detect, confirms recommended vps-docker profile, and applies driver configuration | P0 | F | Full |

### 3.1 Operating the VPS Stack

#### UC-DOCK-001 — From Empty VPS to Green Health Gate

The sysadmin starts with a bare VPS, Docker installed, two secrets in hand. One compose command raises three services; the app entrypoint migrates before serving, and the scheduler daemon wakes inside the same container — no second terminal, no cron line to remember. Provisioning and the setup wizard run inside the container where the environment matches production exactly, and the health gate closes the day. Fifteen minutes later the school has a system whose entire topology fits in one compose file the next admin can read.

#### UC-DOCK-002 — Switching the Queue to Redis

Growth arrives as a symptom: certificate season slows responses because document jobs run inline. The fix is deliberately boring — add a Redis service through a compose override, flip one flag, restart. The entrypoint notices the flag and starts a worker consuming from Redis; nothing is rebuilt, no image changes. If Redis ever goes away, the flag flips back and the stack degrades to the same synchronous behavior the school started with, which is exactly what a reversible growth path should feel like.

#### UC-DOCK-003 — Automated Pre-Release Staging on staging.internara.web.id

During pre-release cycles, a maintainer cuts a tag such as `v1.0.0-beta.1` or `v1.0.0-rc.1`. The CI pipeline passes automated test gates and triggers an automated deployment to the VPS at `staging.internara.web.id` using Docker Compose. The deployment runs migrations, starts the web, app, and db containers, and gates acceptance against `php artisan system:health` and an HTTP 200 check at `https://staging.internara.web.id`. Testers and stakeholders can immediately test the exact containerized build on a live domain before production release.

#### UC-DOCK-004 — VPS Profile Detection & Configuration

When configuring a new or existing VPS, the operator runs `deploy:detect`. The command verifies that Docker runtime, daemon capability, and Redis are present, recommending `vps-docker`. Running `deploy:configure` applies the Redis and daemon drivers to `.env` without overwriting secrets or modifying compose files.

---

## 4. Functional Requirements

VPS topology, lifecycle, persistence, drivers, and startup safety. `Priority` ranks criticality P0–P3; `Layer` declares the verifying test layer; `Status` tracks this requirement independently of the spec's registry status.

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-DOCK-001 | `docker-compose.yml` provides exactly three services — `app`, `web`, `db` — with scheduler and queue worker running inside the `app` container via `RUN_SCHEDULER` / `RUN_QUEUE` flags | P0 | F | Full |
| FR-DOCK-002 | `app` runs PHP-FPM from the project `Dockerfile`, starts only after healthy `db`, and runs `php artisan migrate --force` from the entrypoint before serving | P0 | F | Full |
| FR-DOCK-003 | Entrypoint starts the queue worker when `RUN_QUEUE=true` (`php artisan queue:work --sleep=3 --tries=3`); the default uses `QUEUE_CONNECTION=sync` with no worker, Redis-backed work starts when a `redis` service is present | P0 | F | Full |
| FR-DOCK-004 | Entrypoint starts the scheduler daemon when `RUN_SCHEDULER=true` (`php artisan schedule:work`) | P0 | F | Full |
| FR-DOCK-005 | `web` is an nginx image built from `.docker/nginx.Dockerfile`, proxying to `app:9000` on port 80 (configurable via `NGINX_PORT`) | P0 | F | Full |
| FR-DOCK-006 | `db` is `mysql:8` with a named volume and a healthcheck | P0 | F | Full |
| FR-DOCK-007 | Redis stays optional and out of the default stack; the app image bundles the `phpredis` extension so a `redis:7-alpine` service joins later without rebuilding | P1 | A | Full |
| FR-DOCK-008 | Application storage persists on the `storage_data` named volume and compiled public assets on the `app_data` named volume, both shared across `app` and `web` | P0 | F | Full |
| FR-DOCK-009 | Default runtime drivers are non-Redis and match the shared-hosting preset (`QUEUE_CONNECTION=sync`, `CACHE_STORE=file`, `SESSION_DRIVER=database`, `BROADCAST_CONNECTION=log`); Redis-backed drivers engage when a `redis` service is present | P0 | A | Full |
| FR-DOCK-010 | `php artisan setup:install` and `php artisan system:health` run inside the `app` container | P0 | F | Full |
| FR-DOCK-011 | `app` exposes a healthcheck (`docker/fpm-healthcheck` against FPM port 9000) and `web` depends on `app` with `condition: service_healthy` | P0 | F | Full |
| FR-DOCK-012 | `app` and `db` fail fast at start when `APP_KEY` or `DB_PASSWORD` is missing, and both use `restart: unless-stopped` | P0 | F | Full |
| FR-DOCK-013 | The stack boots on Tier-1 drivers by default and graduates to Tier-2 Redis drivers through environment swaps plus compose override only, with zero application code changes | P1 | A | Full |
| FR-DOCK-014 | System defines a three-tier deployment lifecycle: dev (local/CI), pre-release (staging deployment on staging.internara.web.id via Docker), and release (production deployment) | P0 | A | Full |
| FR-DOCK-015 | Pre-release stage is triggered by SemVer pre-release tags (*-alpha.*, *-beta.*, *-rc.*) and deploys to the staging host binding domain staging.internara.web.id using Docker Compose | P0 | F | Full |
| FR-DOCK-016 | Final release stage is triggered by stable SemVer tags (v*.*.* without suffix) and deploys to the designated production environment | P0 | F | Full |
| FR-DOCK-017 | config/deployment.php declares the vps-docker preset mapping to QUEUE_CONNECTION=redis, CACHE_STORE=redis, SESSION_DRIVER=redis, BROADCAST_CONNECTION=log, and scheduler=daemon | P0 | A | Full |
| FR-DOCK-018 | deploy:detect probes container runtime, Redis reachability, daemon capabilities, and composer, recommending vps-docker when container/redis and daemon exist | P0 | F | Full |
| FR-DOCK-019 | deploy:configure applies the vps-docker preset drivers to .env idempotently, never touching application secrets, docker-compose.yml, or Dockerfile | P0 | F | Full |
| FR-DOCK-020 | Post-deployment verification executes php artisan system:health and verifies HTTP 200 on HEALTH_URL (https://staging.internara.web.id for pre-release) | P0 | F | Full |

### 4.1 Service Topology

#### FR-DOCK-001 — Three Services, No Passengers

Every extra container is a log to check, a restart policy to verify, and a 2 AM page for a school that has no on-call rotation. Three services map one-to-one onto real needs: something runs PHP, something terminates HTTP, something holds rows. Workers and scheduler live inside the app container because splitting them would buy isolation the workload does not need at the price of operational surface the staff cannot afford.

#### FR-DOCK-002 — Migrate Before Serving

A container that serves traffic against an unmigrated schema produces the cruelest errors — pages that render, forms that explode on submit. Running migrations from the entrypoint before the process supervisor starts anything else makes schema currency a precondition of serving, not a step someone remembers. Depending on a healthy database (not merely a started one) closes the remaining race where MySQL accepts connections before it accepts queries.

#### FR-DOCK-003 — The Worker Wakes on a Flag

Semesters have seasons: most months the sync driver absorbs everything inline, but certificate season buries response times under document jobs. One environment flag summons a real worker with sane retry behavior, and unsetting it returns the stack to its simple default. The default-needs-no-worker property matters most — it keeps the common deploy free of a process that would idle eleven months a year.

### 4.2 Scheduler and Queue Lifecycle

#### FR-DOCK-004 — The Scheduler Lives Indoors

On shared hosting the scheduler knocks from outside via webhook; on the VPS it lives inside as a resident daemon. `schedule:work` under the entrypoint's supervision means due jobs fire at minute granularity with no cron entry to maintain and no secret URL to rotate. The flag form keeps the escape hatch: an operator debugging runaway jobs can stop the daemon without stopping the app.

#### FR-DOCK-005 — Nginx in Front, PHP Behind

The web service does what it is good at — terminating HTTP, serving static assets from the shared volume, forwarding the rest — and PHP-FPM does what it is good at. Building nginx from the pinned Dockerfile keeps proxy behavior versioned with the repo instead of drifting with whatever image tag was latest on deploy day. The configurable port exists for VPS boxes already serving something else on 80, a common reality on shared school servers.

#### FR-DOCK-006 — MySQL 8 With a Memory

The database service pins its major version because minor-version drift across rebuilds is how collations silently change. Its named volume is the actual school record — everything else can be rebuilt from the repo, but that volume cannot. The healthcheck turns "database container exists" into "database answers queries," which is the only claim the app container is allowed to depend on.

### 4.3 Persistence and Drivers

#### FR-DOCK-007 — Redis Absent but Anticipated

Shipping Redis by default would force every small school to fund memory and attention for a service their load never justifies. Omitting it while bundling the client extension is the middle path: the day throughput demands Redis, the change is a compose override, not a rebuild and redeploy. Option value without carrying cost — the stack stays lean until evidence says otherwise.

#### FR-DOCK-008 — Volumes That Outlive Containers

A container is cattle; the storage volume is the school. Splitting `storage_data` (uploads, generated documents) from `app_data` (compiled assets) means asset rebuilds never threaten user files and user growth never pollutes the asset layer. Sharing both with the web container lets nginx serve files directly instead of proxying bytes through PHP. The incident this prevents is the classic one: a well-meaning `docker compose down -v` equivalent wiping a semester — named volumes make the persistent state visible and deliberate.

#### FR-DOCK-009 — Tier-1 Defaults on VPS Hardware

A fresh VPS boots with the same drivers as shared hosting, and that surprises people until they see the reasoning: correctness first, throughput later. Sync queue and file cache work with zero additional services, so the stack is fully functional before any Redis decision. Redis-backed values engage only alongside a real Redis service — drivers follow topology, never lead it, which keeps every intermediate state (override added but flag off, flag on but service down) diagnosable.

#### FR-DOCK-010 — Provisioning Runs Where Production Lives

Running setup and health inside the app container is not ceremony; it is environment fidelity. PHP version, extensions, file permissions, and network names inside the container are the ones that matter — the host's PHP may differ or not exist at all. An operator who provisions from the host is testing a different machine than the one serving students.

### 4.4 Health and Startup Safety

#### FR-DOCK-011 — Each Layer Proves Readiness

Startup order without health proof is just hope with a `depends_on` label. The FPM ping proves PHP actually serves; the web container waits for that proof before accepting traffic it would otherwise 502 on. During a slow first boot — cold volume, pending migrations — the chain holds each service until its dependency is genuinely ready instead of merely launched, which is the difference between a slow deploy and a failed one.

#### FR-DOCK-012 — Refuse to Boot Half-Configured

Missing secrets produce the worst production states: an app that boots, serves pages, then corrupts sessions or fails every login. Exiting immediately with a clear message converts a week of mysterious symptoms into five minutes of reading logs. The restart policy complements this — transient crashes recover on their own while configuration errors stay down and visible instead of crash-looping silently.

#### FR-DOCK-013 — Growth Without Rewrites

This row is the Tier-2 promise in stack form. Crossing from a few hundred to over a thousand users changes variables and adds one service; no class, migration, or business rule changes. The day an operator must edit application code to handle load is the day this architecture failed, so the spec forbids that day in advance. Autoscaling clusters, read replicas, and CDN sit beyond this boundary as explicitly deferred depth — the stack graduates by swaps, and stops there.

---

## 5. Non-Functional Requirements

Cross-cutting constraints on the VPS stack. `Target` holds the concrete SLO where one exists; `N/A` marks architecturally-enforced properties verified via tests and review.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-DOCK-001 | Docker services use healthchecks so Compose starts `app` after healthy `db`, and `web` after healthy `app` | N/A | P0 | F | Full |
| NFR-DOCK-002 | A deployment is reproducible from documented commands alone | N/A | P1 | F | Full |
| NFR-DOCK-003 | No secrets are committed; `.env` stays excluded via `.gitignore` | N/A | P0 | A | Full |
| NFR-DOCK-004 | Pre-release host staging.internara.web.id serves over HTTPS behind a reverse proxy forwarding X-Forwarded-Proto and enforces SESSION_SECURE_COOKIE=true | 100% HTTPS | P0 | F | Full |
| NFR-DOCK-005 | Docker build, deploy, and detection scripts expose no credentials or secrets in output or logs | N/A | P0 | F | Full |

### 5.1 Reliability and Security

#### NFR-DOCK-001 — The Chain Never Skips a Link

Anyone who has watched a proxy 502 during a slow database init knows why ordering must be proven, not declared. Each service gates on the next link's health signal, so a sluggish disk or a long migration delays traffic instead of erroring it. The property holds on every boot, not just clean ones — especially on the unclean reboot after a power cut, when everything is slow at once.

#### NFR-DOCK-002 — One Page Rebuilds the School

Disaster recovery for a school cannot depend on the one sysadmin who set it up originally. When the documented commands alone — clone, configure, compose up, provision, verify — reproduce the system, any competent successor recovers it. Every undocumented step is a bus-factor liability wearing a lab coat.

#### NFR-DOCK-003 — Secrets Never Enter History

Compose files are committed, shared, and sometimes posted publicly when asking for help. Keeping `.env` out of version control by rule means a pasted compose file leaks topology at worst, never credentials. Rotation stays effective because the old secret exists only on the server, not in every clone ever made.

---

## 6. API / Data Contracts

### 6.1 Docker Compose Services

| Service | Image | Purpose |
| ------- | ----- | ------- |
| `app` | Custom (Dockerfile) | PHP-FPM application server; entrypoint runs migrations plus scheduler (`RUN_SCHEDULER`) and optional queue worker (`RUN_QUEUE`) |
| `web` | Custom (.docker/nginx.Dockerfile) | Reverse proxy → `app` |
| `db` | mysql:8 | Database |

Redis stays optional (FR-DOCK-007): no service by default; add a `redis:7-alpine` service and switch `QUEUE_CONNECTION`/`CACHE_STORE`/`SESSION_DRIVER` when throughput demands it (FR-DOCK-013).

### 6.2 Entrypoint Environment Flags

| Flag | Default | Purpose |
| ---- | ------- | ------- |
| `RUN_SCHEDULER` | `true` | Start `php artisan schedule:work` daemon |
| `RUN_QUEUE` | `false` | Start `php artisan queue:work` (requires Redis) |
| `NGINX_PORT` | `80` | Exposed port for the web service |

---

## 7. Design Decisions

Recorded choices behind the stack. These rows carry no test layer — they explain intent so future maintainers change the topology without re-litigating the reasoning.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-DOCK-001 | Minimal three-service topology without Redis; scheduler and optional queue worker run inside the `app` container under entrypoint management | P0 | — | Full |
| DD-DOCK-002 | Scheduler and optional queue worker run as entrypoint-managed subprocesses, not as separate containers | P0 | — | Full |
| DD-DOCK-003 | Pre-release staging runs on staging.internara.web.id using the exact same three-service Docker topology to guarantee environmental fidelity before production | P0 | — | Full |

### 7.1 Topology

#### DD-DOCK-001 — Lean by Default, Expandable by Override

The stack was sized for its real customer: one school, hundreds of users, part-time operations. A sync queue executing jobs inline covers certificate generation and notifications at that scale while deleting Redis availability, memory sizing, and backup from the operator's worries. Consolidating background processes into the app container holds the service count at three with a single restart story. Bundling the Redis extension preserves the growth path — adding the service later is configuration, not surgery. The known cost is that heavy document jobs block their request under the sync driver; the documents queue pipeline exists to isolate exactly that work once a Redis worker joins.

#### DD-DOCK-002 — One Container, Supervised Internally

Separate worker containers would isolate crashes at the price of cross-container volume mounts, duplicated environment, and twice the restart policies to get right. A single supervised container shares the filesystem trivially and keeps the failure modes in one log stream. The accepted risk is correlated failure — if the entrypoint dies, scheduler and worker die with it — judged acceptable for a single-application box and revisit-worthy only if graceful worker shutdown ever becomes a real requirement rather than a theoretical one.

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
|------|------------------|
| [system-maintenance](E1MSJ-system-maintenance.md) (E1MSJ) | `system:health` command used as the deployment acceptance gate |

### Build Guide

After implementing this spec, `docker compose up -d` starts a fully operational instance with persistent storage, a scheduler daemon, and a healthcheck chain. The compose stack is the single source of truth for the Docker topology.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [shared-hosting-deployment](H9T4N-shared-hosting-deployment.md) (H9T4N) | Fallback deployment path for schools without VPS access |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| --- | --------------------------------- | ------ | ----- | -------- |
| R-1 | If the entrypoint process crashes, the scheduler and queue worker stop with it; accepted for a single-app container until graceful worker shutdown becomes a requirement | Open | Maintainer | — |
| A-1 | We assume Docker and Compose are pre-installed on the VPS and assets are built before `docker compose up` | Accepted | Maintainer | — |

## Quick References

- [Spec registry](index.md) — Maintenance phase: W8K2P (Full), H9T4N (Full), 3UOZP (Full)
- [Shared hosting deployment](H9T4N-shared-hosting-deployment.md) — companion shared hosting spec
- [System maintenance](E1MSJ-system-maintenance.md) — `system:health` acceptance gate
- [Job queue infrastructure](8FVZA-job-queue-infrastructure.md) — sync/redis driver contract
- [Self-hosted single-tenant ADR](../adr/adr-self-hosted-single-tenant.md) — tenancy foundation
- [Performance optimization ADR](../adr/adr-performance-optimization.md) — Tier-1 defaults and Tier-2 growth

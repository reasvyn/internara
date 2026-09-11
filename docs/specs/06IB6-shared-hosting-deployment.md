# Shared Hosting Deployment — Conventional cPanel-Style Server Operations

> **Spec ID:** 06IB8
> **Status:** Full
> **Owner:** Core
> **Depends on:** [deployment](06IB6-deployment.md) (06IB6), [installation](8NZAU-installation.md) (8NZAU), [system-maintenance](E1MSJ-system-maintenance.md) (E1MSJ)

## Description

Specifies the shared-hosting condition for Internara: conventional cPanel-style servers with PHP 8.4+, MySQL/MariaDB, and coarse cron. It pins the no-daemon constraints, the off-server build workflow, the `/cron/{secret}` webhook scheduler, and manual symlink creation. Profile selection, detection, and driver application belong to the parent [deployment](06IB6-deployment.md) spec (06IB6); this spec owns everything that makes the `shared-hosting` preset actually run on a constrained box.

---

## 1. Problem Statements

### PS-1 — Shared Hosting Has No Long-Running Daemons

Conventional shared hosting offers PHP 8.4, MySQL/MariaDB, and 5–15 minute cron — but no resident queue worker, no Redis/Memcached, no Composer/Node at runtime, and usually no SSH. Any design assuming a background process silently stops working here.
**→ Requirement:** FR-HOST-002/003 (Tier-1 no-daemon scope), FR-HOST-004/005 (sync queue, file/database drivers).

### PS-2 — Build Must Happen Off-Server

With no toolchain at runtime, compilation cannot occur on the box. The artifact must arrive fully built — dependencies installed, assets compiled — or it does not run at all.
**→ Requirement:** FR-HOST-007 (off-server build), FR-HOST-010 (uploadable artifact).

### PS-3 — Cron Intervals Are Limited

Host cron fires every 5–15 minutes while the application scheduler expects minute-level triggering. Without a webhook bridge, scheduled work either never runs or requires an external scheduler the school does not have.
**→ Requirement:** FR-HOST-006 (webhook scheduler trigger).

---

## 2. Goals & Non-Goals

### Goals

- **Full application with zero daemons** — no worker, no Redis, no SSH required at runtime. *Why:* the cheapest hosting tier must run the whole school, not a subset.
- **Webhook-driven scheduler** — the `/cron/{secret}` route fires scheduled work within host cron granularity. *Why:* coarse cron must still drive daily and weekly jobs reliably.
- **FTP-uploadable artifact** — a prebuilt bundle moves over FTP/cPanel with no server-side tooling. *Why:* the upload channel on shared hosting is a file manager, not a terminal.
- **SSH-free symlink path** — the `public/storage` link is creatable without a shell. *Why:* uploads break silently when storage is unreachable, and most plans offer no shell to fix it.
- **Tier-1 database freedom** — MySQL 8+, MariaDB 10.6+, and SQLite all work. *Why:* schools take whatever database their plan includes.

### Non-Goals

- **Minute-level cron precision**. *Why:* the host interval is the granularity ceiling; jobs are designed around daily/weekly cadence instead.
- **Queue worker daemon**. *Why:* jobs run synchronously by preset; a resident worker cannot survive on this hosting.
- **Redis/Memcached**. *Why:* cache is file and sessions are database by constraint, not preference.
- **On-server builds**. *Why:* no Composer or Node exists at runtime; building locally is faster and more reliable.

---

## 3. User Stories / Use Cases

The shared-hosting operator journey, verified end-to-end from upload through the health gate.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-HOST-001 | School IT staff deploys on cheap conventional shared hosting via off-server build, FTP upload, document root, symlink, env setup, provisioning, cron entry, and health gate | P0 | F | Full |

### 3.1 Deploying Without a Shell

#### UC-HOST-001 — An Evening Deploy Over FTP

The IT teacher's evening starts on their laptop: install dependencies, compile assets, and pack a bundle — because the server at the other end has no toolchain, only a file manager and a cron page. Upload, point the document root at `public/`, hand-create the storage link the shell would normally make, copy the example env with real credentials and a fresh cron secret, provision, add the single cron entry that knocks on the webhook every few minutes, and finish with the health check. By the last step the constraint set has disappeared into routine: five hundred users' worth of school system running on hosting that costs less than lunch.

---

## 4. Functional Requirements

Shared-hosting scope, drivers, scheduler bridge, build workflow, and compatibility. `Priority` ranks criticality P0–P3; `Layer` declares the verifying test layer; `Status` tracks this requirement independently of the spec's registry status.

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-HOST-001 | Shared hosting is the Tier-1 path: up to 500 users on sync queue, file cache, database sessions, and local disk, with every core feature functional and zero external services | P0 | F | Full |
| FR-HOST-002 | Shared hosting requires no queue worker daemon; jobs run synchronously via `QUEUE_CONNECTION=sync` | P0 | F | Full |
| FR-HOST-003 | Shared hosting requires no Redis or Memcached; cache is `file` and sessions are `database` | P0 | A | Full |
| FR-HOST-004 | `deploy:configure --profile=shared-hosting` writes `QUEUE_CONNECTION=sync` and `SESSION_DRIVER=database` to `.env` | P0 | F | Full |
| FR-HOST-005 | All core features (auth, registration, attendance, logbook, assignments, assessments, reports, certificates) work under the shared-hosting preset | P0 | F | Full |
| FR-HOST-006 | The scheduler is triggerable via the `/cron/{secret}` webhook route when minute-level cron is unavailable | P0 | F | Full |
| FR-HOST-007 | The deployable artifact is buildable off-server: `composer install --optimize-autoloader --no-dev` plus `npm run build` run before upload and are never required on the server | P0 | F | Full |
| FR-HOST-008 | Shared hosting supports a configurable document root pointed at `public/` | P0 | F | Full |
| FR-HOST-009 | The `public/storage` symlink is creatable manually when SSH is unavailable | P0 | F | Full |
| FR-HOST-010 | MySQL 8+ / MariaDB 10.6+ and SQLite are all supported as the database on shared hosting | P0 | F | Full |

### 4.1 Tier-1 Scope and Drivers

#### FR-HOST-001 — The Whole School on the Cheapest Plan

Five hundred users, one shared plan, every feature working — that sentence is the entire Tier-1 bargain. Registration, attendance, logbooks, grading, certificates: nothing degrades, nothing is stubbed, nothing waits for a bigger server. Throughput is the only thing money buys later. This row exists so no future optimization ever quietly gates a feature behind infrastructure a small school cannot afford.

#### FR-HOST-002 — Jobs That Finish Before the Response

Without a worker, background work has exactly one place to happen: inline, inside the request that queued it. The sync driver makes emails, conversions, and notifications complete before the response returns — slower per request, but with zero moving parts and zero lost jobs. A certificate generated during a parent's visit prints before they stand up, which is a feature, not a limitation, at this scale.

#### FR-HOST-003 — Memory and Sessions Without Services

File cache and database sessions look unglamorous next to Redis and outperform it on exactly one metric that matters here: services the operator does not run. Cache files sit on local disk where any file manager can see them; sessions live in a table migrations already created. Nothing to provision, nothing to monitor, nothing to run out of memory at midnight before report-card day.

#### FR-HOST-004 — The Preset in Two Keys

Two keys carry the heart of the shared-hosting contract, and the configure command writes both when the profile is chosen. An operator who hand-edits `.env` instead gets the same values from the guide — because both surfaces read one preset. The day these keys disagree with the running behavior, the preset definition wins and the file gets fixed, never the reverse.

#### FR-HOST-005 — No Feature Left Behind

Constraint must never become excuse. Every module journey — a student clocking in, a teacher scoring a rubric, an admin issuing certificates — passes under this preset in the functional suite. If a new feature only works with a worker or Redis, that feature is unfinished until it degrades gracefully onto Tier-1 drivers or the constraint is renegotiated at the architecture level.

### 4.2 Scheduler Bridge and Build Workflow

#### FR-HOST-006 — Knocking on the Webhook

Host cron cannot tick every minute, so it knocks every few instead: a cron entry curls the secret webhook, the webhook runs everything due, and daily jobs (cleanup, backups, digests) land well within their windows. Time-sensitive precision is surrendered openly — nothing here promises minute accuracy — in exchange for scheduled work that actually runs on hosting that offers nothing better. The secret in the URL keeps random crawlers from triggering job runs.

#### FR-HOST-007 — Built on a Laptop, Run on a Plan

The build happens where the tools live: dependencies installed with an optimized autoloader and dev packages stripped, frontend compiled to static assets, all before a single byte uploads. The server never sees Composer or Node because it never needs them — it receives a runnable bundle. Each deploy repeats the same local ritual, which is why the ritual is documented as commands rather than remembered as habit.

#### FR-HOST-008 — The Document Root Points Inward

Shared hosts serve whatever directory the panel says; pointing it at `public/` keeps the application root — env file, source, storage — out of the web-reachable tree. One mispointed root exposes configuration to the internet, so this row treats the pointer as a security control rather than a preference. The install guide states it as a numbered step, not a suggestion.

#### FR-HOST-009 — A Symlink Without a Shell

Normally one artisan command creates the storage link; on shell-less hosting that command has nowhere to run. The manual path — a small PHP script over the file manager, or a panel-native link tool per the installation spec — produces the identical link. Uploads that 404 after deploy almost always trace back here, which is why the health gate checks storage reachability instead of trusting the step was done.

#### FR-HOST-010 — Whatever Database the Plan Includes

MySQL 8 where offered, MariaDB 10.6 where that is what the panel ships, SQLite where the school wants zero database administration — migrations run clean on all three. UUID keys and portable schema discipline make this freedom possible; any migration using a database-specific trick breaks three schools at once and gets caught in review. The plan's database is a detail, never a blocker.

---

## 5. Non-Functional Requirements

Cross-cutting constraints on the shared-hosting condition. `Target` holds the concrete SLO where one exists; `N/A` marks architecturally-enforced properties verified via tests and review.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-HOST-001 | `CRON_SECRET` is required for the `/cron/{secret}` webhook; requests without a valid secret are rejected | 100% unsigned rejected | P0 | F | Full |
| NFR-HOST-002 | A deployment is reproducible from documented commands alone | N/A | P1 | F | Full |
| NFR-HOST-003 | The install guide describes the off-server build, FTP upload, symlink, and cron entry steps in order | N/A | P1 | A | Full |
| NFR-HOST-004 | The `public/storage` link resolves and storage directories are writable after deploy, confirmed by the health gate | N/A | P0 | F | Full |

### 5.1 Protection and Reproducibility

#### NFR-HOST-001 — The Secret the Cron Keeps

A webhook URL that runs scheduled jobs is a remote-control button, and URLs leak — into access logs, forwarded messages, browser histories. The secret path segment turns possession of the URL into proof of authorization, and anything without it gets rejected flatly rather than redirected helpfully. Rotation after any suspected exposure is a one-variable change, which is the only rotation story busy school staff will actually complete.

#### NFR-HOST-002 — Rebuildable From Paper

When the hosting account is suspended, migrated, or simply outgrown, recovery cannot depend on anyone's memory of the first deploy. Build, upload, point, link, configure, provision, schedule, verify — each step documented as runnable commands in order. The test is unsparing: a stranger with the guide and the credentials reproduces the system, or the guide is incomplete.

#### NFR-HOST-003 — The Guide Reads Like a Checklist

Shared-hosting operators follow guides the way pilots follow checklists: in order, under time pressure, without improvising. Build before upload, document root before provisioning, cron entry before verification — the sequence matters because each step assumes the last. Review treats step-order regressions as defects, since a reordered guide strands exactly the audience least equipped to recover.

#### NFR-HOST-004 — Storage Reachable and Writable

A deploy once wiped a semester's uploads because the storage link pointed at a release directory that the next FTP upload replaced — every photo, every signed letter, gone from the web while the rows still claimed they existed. The health gate now proves the link resolves and the directories accept writes before the deploy counts as done. Permissions and link target are deployment state, verified like any other, because the cost of assuming them is measured in irreplaceable files.

---

## 6. API / Data Contracts

### 6.1 Cron Webhook Route

```php
// routes/web/sysadmin.php
Route::get('/cron/{secret}', CronController::class)
    ->name('cron')
    ->middleware('throttle:...');
```

### 6.2 Runtime Drivers (Shared Hosting Preset)

| Driver | Value | Reason |
| ------ | ----- | ------ |
| `QUEUE_CONNECTION` | `sync` | No daemon allowed |
| `CACHE_STORE` | `file` | No Redis/Memcached |
| `SESSION_DRIVER` | `database` | No Redis; avoids cookie size limits |
| `BROADCAST_CONNECTION` | `log` | No WebSocket support |
| Scheduler | webhook (via `/cron/{secret}`) | Limited cron granularity |

---

## 7. Design Decisions

Recorded choices behind the shared-hosting condition. These rows carry no test layer — they explain intent so future maintainers change the workflow without re-litigating the reasoning.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-HOST-001 | The deployment artifact is built off-server before upload; no on-server Composer or Node, ever | P0 | — | Full |
| DD-HOST-002 | The scheduler fires through the `/cron/{secret}` webhook instead of a resident `schedule:work` daemon | P0 | — | Full |

### 7.1 Constraints as Design

#### DD-HOST-001 — The Server Receives, Never Compiles

Shared boxes lack toolchains, disk headroom for dependency trees, and process time for asset compilation — and even where a shell exists, building there is slower and flakier than building on the maintainer's machine. Making off-server builds a hard rule removes an entire class of "it works on my laptop" divergence: the uploaded bundle is byte-identical to what was verified locally. Each deploy pays a local build step, documented and reproducible, instead of paying debugging hours on a server with no tools.

#### DD-HOST-002 — Coarse Cron Driving a Patient Scheduler

Host cron at 5–15 minute intervals cannot deliver minute precision, so the design stops asking for it. The webhook lets any coarse trigger — host cron, or an external pinger where even that is missing — run everything due inside the application's own scheduler. Daily cleanup and weekly backups never notice the difference; only sub-hour precision is surrendered, and no school workflow needs it. Granularity follows the host rather than fighting it.

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
|------|------------------|
| [deployment](06IB6-deployment.md) (06IB6) | Deployment profile catalog, `DEPLOY_PROFILE` variable, `deploy:detect`, `deploy:configure` |
| [system-maintenance](E1MSJ-system-maintenance.md) (E1MSJ) | `system:health` command used as the deployment acceptance gate |
| [installation](8NZAU-installation.md) (8NZAU) | `setup:install` provisioning, environment audit, `.env` handling, manual symlink |

### Build Guide

After implementing this spec, school IT staff deploys by uploading a prebuilt artifact, setting the document root, configuring `.env`, and adding a single cron entry. No SSH, no Composer, no Node at runtime.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [deployment](06IB6-deployment.md) (06IB6) | Profile detection and the `DEPLOY_PROFILE` variable apply the `shared-hosting` preset drivers (FR-HOST-001/004) |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| --- | --------------------------------- | ------ | ----- | -------- |
| R-1 | If the host's cron granularity is coarser than 15 minutes, time-sensitive scheduled work slips; mitigated by designing jobs around daily/weekly cadence | Open | Maintainer | — |
| A-1 | We assume the host allows a `public/` document root, a manually created storage link, and an outbound webhook call for the scheduler trigger | Accepted | Maintainer | — |

## Quick References

- [Spec registry](index.md) — Maintenance phase: 06IB6 (Full), 06IB7 (Full), 06IB8 (Full), 3UOZP (Full)
- [Conditional deployment](06IB6-deployment.md) — parent spec: profile catalog, detection, `deploy:configure`, health gate
- [Docker VPS deployment](06IB6-docker-vps-deployment.md) — sibling 06IB7 per-condition spec
- [Installation](8NZAU-installation.md) — `setup:install` provisioning and manual symlink
- [System maintenance](E1MSJ-system-maintenance.md) — `system:health` acceptance gate
- [Self-hosted single-tenant ADR](../adr/adr-self-hosted-single-tenant.md) — why Tier-1 needs zero external services
- [Performance optimization ADR](../adr/adr-performance-optimization.md) — Tier-1 scope and Tier-2 triggers

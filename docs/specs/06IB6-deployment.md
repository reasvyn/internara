# Conditional Deployment — Profile Catalog, Detection & Configuration

> **Spec ID:** 06IB6
> **Status:** Full
> **Owner:** Core
> **Depends on:** [system-requirements](J68GZ-system-requirements.md) (J68GZ), [installation](8NZAU-installation.md) (8NZAU), [job-queue-infrastructure](8FVZA-job-queue-infrastructure.md) (8FVZA), [system-maintenance](E1MSJ-system-maintenance.md) (E1MSJ)

## Description

Defines the shared deployment infrastructure every Internara install stands on: a two-profile catalog, automatic environment detection, the `deploy:configure` applicator, and the post-deploy health gate. The two hosting conditions themselves live in their own specs — [docker-vps-deployment](06IB6-docker-vps-deployment.md) (06IB7) and [shared-hosting-deployment](06IB6-shared-hosting-deployment.md) (06IB8). This spec is the contract that binds presets, detection, and verification together so docs and Docker config cannot drift apart.

---

## 1. Problem Statements

### PS-1 — Two Environments, Mutually Exclusive Capabilities

Shared hosting provides PHP 8.4, MySQL/MariaDB, and limited-interval cron, but no long-running daemons, no Redis/Memcached, no Composer/Node at runtime, and usually no SSH. A Docker VPS provides daemon capability (scheduler, queue workers), Nginx, and optional Redis. No single static configuration serves both — a config tuned for one breaks silently on the other.
**→ Requirement:** FR-DEPL-001/003/004 (two canonical profiles with fixed presets), FR-DEPL-009 (Tier-1 vs Tier-2 paths).

### PS-2 — Manual Per-Environment Tuning Is Error-Prone

The deployment guide describes paths that ask operators to hand-edit `.env` driver keys (`QUEUE_CONNECTION`, `CACHE_STORE`, `SESSION_DRIVER`). Every key is a decision point; a wrong choice yields jobs that never run, mail that blocks requests, or sessions that vanish — discovered only after users complain.
**→ Requirement:** FR-DEPL-002 (single preset source), FR-DEPL-017/019/020 (`deploy:configure` writes drivers only, idempotently).

### PS-3 — No Authoritative Delivery Contract

Docker configuration exists (`docker-compose.yml`, `Dockerfile`, `docker/shared-hosting/`) and shared-hosting-optimized defaults exist in `.env.example`, but nothing binds them: which preset belongs to which environment, how the environment is detected, how an operator overrides detection, and how a deployment is verified.
**→ Requirement:** FR-DEPL-010/016 (detect + override precedence), FR-DEPL-023 (health gate), FR-DEPL-024/025/026 (docs contract).

---

## 2. Goals & Non-Goals

### Goals

- **Zero-tuning shared-hosting deploys** — the same codebase lands on cheap conventional hosting with drivers set by preset, not by operator judgment. *Why:* school IT staff should not need to understand queue drivers to get a working system.
- **Environment detection with a recommendation** — `deploy:detect` probes the server and names the matching profile before any `.env` edit. *Why:* removes the guesswork that causes silent misconfiguration.
- **Single-variable override** — an explicit `DEPLOY_PROFILE` always beats detection. *Why:* ambiguous environments (a small VPS that happens to expose Redis) still need a human decision.
- **Reuse, not rebuild** — `docker-compose.yml`, `Dockerfile`, `docker/shared-hosting/`, and `.env.example` stay the artifacts; this spec only governs them. *Why:* the delivery machinery already exists and works.
- **Health-gated acceptance** — every deployment ends with `php artisan system:health` passing. *Why:* a deploy without a verification gate is a hope, not a delivery.
- **Safe default on inconclusive detection** — shared-hosting wins ties. *Why:* the most constrained preset is the only fallback that never assumes an absent service.

### Non-Goals

- **Kubernetes / multi-node orchestration**. *Why:* single-node Docker Compose covers the Tier-2 scale band; orchestration ceremony has no MVP buyer.
- **Per-hosting-condition internals**. *Why:* owned by [docker-vps-deployment](06IB6-docker-vps-deployment.md) and [shared-hosting-deployment](06IB6-shared-hosting-deployment.md); duplicating them here would recreate the drift this spec exists to kill.
- **Full CI/CD pipeline automation**. *Why:* covered by the CI/CD guide; this spec governs what gets deployed, not the pipeline that ships it.
- **Migration tooling between hosting conditions**. *Why:* backup/restore already moves an instance; a dedicated migrator is post-MVP depth.
- **Multi-tenant or platform provisioning**. *Why:* single-tenant self-hosted by product definition.

---

## 3. User Stories / Use Cases

School and operator journeys across both hosting conditions. Detection and override are command-verified, so their rows carry a test layer; the two end-to-end deploys are verified through the per-condition specs' gates.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-DEPL-001 | School deploys on cheap conventional shared hosting with drivers set by preset and health gate passing | P0 | F | Full |
| UC-DEPL-002 | Sysadmin deploys on a Docker VPS with scheduler daemon inside the app container and health gate passing | P0 | F | Full |
| UC-DEPL-003 | Operator on an unknown server runs detection and receives a recommended profile with per-probe results | P0 | F | Full |
| UC-DEPL-004 | Operator overrides auto-detection with an explicit profile that takes precedence over any recommendation | P0 | F | Full |

### 3.1 Deploying on Either Condition

#### UC-DEPL-001 — School Lands on Shared Hosting

A school treasurer approved a $5/month shared plan, not a VPS — there is no root, no daemon budget, no Redis. The IT teacher uploads the prebuilt artifact, points the document root at `public/`, copies `.env.example`, and runs the two commands the guide names. Because the shared-hosting preset pins sync queue, file cache, and database sessions, the first student registration works the same evening without anyone learning what a queue driver is. The health gate at the end is the moment the deployment stops being a set of uploaded files and becomes a running school system.

#### UC-DEPL-002 — Sysadmin Lands on a Docker VPS

Picture the opposite school: enrollment doubled, the shared plan groans every morning, and the foundation approved a small VPS. The sysadmin clones the repo, brings up three containers, and the entrypoint handles what shared hosting cannot — migrations, then a scheduler daemon inside the app container, no cron webhook needed. The same health gate closes the loop, so both conditions share one definition of "deployed": `system:health` green, not "the homepage rendered once."

### 3.2 Detecting and Overriding

#### UC-DEPL-003 — Detection Names the Profile First

Nobody should edit driver keys on a hunch. The operator runs one command, and within seconds it reports what the server actually offers — container runtime present or absent, Redis reachable or not, daemon extensions loaded or missing — followed by a single recommendation. On a bare cPanel box the answer is unambiguous; on a minimal VPS with no Redis it still lands correctly, because the probes test capabilities rather than trusting hostnames or plan names. The operator proceeds to `deploy:configure` knowing which preset matches reality.

#### UC-DEPL-004 — The Operator Overrules the Probes

Detection reads capabilities, not intentions. A department running a small VPS may still want the simplicity of the shared-hosting preset — no Redis to babysit during exam season — while a well-provisioned host with Redis reachable might be earmarked for the lean stack anyway. One variable settles it: whatever `DEPLOY_PROFILE` names wins, unconditionally. Re-running detection later never silently flips a deliberate choice, which is exactly what makes the override trustworthy rather than advisory.

---

## 4. Functional Requirements

Shared catalog, detection, application, and verification behavior. `Priority` ranks criticality P0–P3; `Layer` declares the verifying test layer (`A` = Arch/structure, `F` = Feature with real environment); `Status` tracks this requirement independently of the spec's registry status.

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-DEPL-001 | System defines exactly two canonical deployment profiles: `shared-hosting` and `vps-docker` | P0 | A | Full |
| FR-DEPL-002 | Profile presets are declared in a single configuration source (`config/deployment.php`) | P0 | A | Full |
| FR-DEPL-003 | `shared-hosting` preset maps to `QUEUE_CONNECTION=sync`, `CACHE_STORE=file`, `SESSION_DRIVER=database`, `BROADCAST_CONNECTION=log`, scheduler mode `webhook`, no Redis | P0 | A | Full |
| FR-DEPL-004 | `vps-docker` preset maps to `QUEUE_CONNECTION=redis`, `CACHE_STORE=redis`, `SESSION_DRIVER=redis`, `BROADCAST_CONNECTION=log`, scheduler mode `daemon`, Redis required | P0 | A | Full |
| FR-DEPL-005 | Active profile is selectable via `DEPLOY_PROFILE` with values `shared-hosting`, `vps-docker`, or `auto` | P0 | F | Full |
| FR-DEPL-006 | When `DEPLOY_PROFILE` is `auto` or unset, the active profile resolves through automatic detection | P0 | F | Full |
| FR-DEPL-007 | When detection is inconclusive, the system defaults to the `shared-hosting` profile | P0 | F | Full |
| FR-DEPL-008 | `config/deployment.php` declares the default profile as `shared-hosting` | P0 | A | Full |
| FR-DEPL-009 | `shared-hosting` is the Tier-1 path (up to 500 users, sync/file/database drivers, zero external services) and `vps-docker` is the Tier-2 path (500–2000 users, Redis-backed drivers via environment swaps); every feature works on both paths | P1 | A | Full |
| FR-DEPL-010 | System provides a `deploy:detect` artisan command that probes the environment and recommends a profile | P0 | F | Full |
| FR-DEPL-011 | Detection probes at minimum container runtime presence, Redis reachability, daemon-capable extensions (pcntl/posix), and Composer availability at runtime | P0 | F | Full |
| FR-DEPL-012 | Detection recommends `vps-docker` when a container runtime is present, or Redis is reachable together with daemon capability | P1 | F | Full |
| FR-DEPL-013 | Detection recommends `shared-hosting` when no container, no Redis, or no daemon capability is found | P1 | F | Full |
| FR-DEPL-014 | Detection is non-destructive: it reads environment state and writes nothing | P0 | F | Full |
| FR-DEPL-015 | Detection supports a `--json` flag for machine-readable output | P2 | F | Full |
| FR-DEPL-016 | An explicitly set `DEPLOY_PROFILE` (non-`auto`) always takes precedence over the detection recommendation | P0 | F | Full |
| FR-DEPL-017 | System provides a `deploy:configure` artisan command that applies a profile preset to `.env` | P0 | F | Full |
| FR-DEPL-018 | `deploy:configure` accepts `--profile=shared-hosting\|vps-docker` and rejects unknown values | P0 | F | Full |
| FR-DEPL-019 | `deploy:configure` writes only driver keys (queue/cache/session/broadcast) and never secrets (`DB_PASSWORD`, `APP_KEY`, `MAIL_PASSWORD`) | P0 | F | Full |
| FR-DEPL-020 | `deploy:configure` is idempotent: repeated runs converge on the same `.env` state | P1 | F | Full |
| FR-DEPL-021 | `deploy:configure` without `--profile` applies the resolved profile from detection-or-default | P1 | F | Full |
| FR-DEPL-022 | Applying a profile never modifies `docker-compose.yml` or the `Dockerfile` | P1 | A | Full |
| FR-DEPL-023 | `php artisan system:health` is the final acceptance gate for both deployment conditions | P0 | F | Full |
| FR-DEPL-024 | The deployment guide presents the two profiles and their presets as the canonical deployment reference | P1 | A | Full |
| FR-DEPL-025 | `docker/README.md` documents the topology mapping: `docker-compose.yml` is the minimal 3-service stack, `docker/shared-hosting/` is the shared-hosting simulation | P1 | A | Full |
| FR-DEPL-026 | `.env.example` remains the shared-hosting-optimized default and documents `DEPLOY_PROFILE` | P1 | A | Full |
| FR-DEPL-027 | Adding a new profile requires no changes to application business code | P2 | A | Full |

### 4.1 Deployment Profiles and Tier Paths

#### FR-DEPL-001 — Two Profiles, No Third Door

Before this catalog, every deploy was a snowflake: one school's `.env` borrowed from a forum post, another's from a senior's memory. Two canonical names end that. Anything that is neither shared-hosting nor vps-docker is, by definition, undeployed-as-far-as-the-spec-is-concerned — an operator may still hand-tune keys, but they leave the governed path when they do, and the health gate is the only witness left.

#### FR-DEPL-002 — One File Holds the Truth

Ask where the shared-hosting queue driver is defined and the answer must be a file path, not a wiki search. `config/deployment.php` carries both presets as data, and every other surface — `.env.example`, the guide, the Docker README — points at it. The semester someone proposes a third profile, the diff touches one file first; everything downstream follows.

#### FR-DEPL-003 — The Shared-Hosting Preset

This preset is a portrait of constraint turned into virtue. Sync queue because no worker can survive; file cache because no memory service exists; database sessions because cookies cannot be trusted with size; webhook scheduler because cron arrives in coarse 5–15 minute waves. Each value encodes something the environment cannot do, which is why changing any one of them by hand is indistinguishable from choosing a different profile — and should be done through one.

#### FR-DEPL-004 — The VPS Preset

Where the shared preset apologizes for nothing and assumes nothing, this one assumes the VPS earns its keep: Redis reachable, daemons allowed, scheduler running as a resident process instead of a knocked door. Broadcast stays on log in both presets — real-time fan-out was never part of either hosting bargain. The asymmetry is deliberate: moving up a tier buys throughput, never new features.

#### FR-DEPL-005 — One Variable Steers

An operator juggling exam-week duties should not edit four driver keys under pressure. A single `DEPLOY_PROFILE` value names the intent and the tooling derives the rest. `auto` is the honest default for an unknown box; the two explicit values are how a deliberate operator records a decision that future re-runs must respect rather than recompute.

#### FR-DEPL-006 — Unset Means Detect

Nobody remembers to set every variable on a fresh clone at midnight before term starts. When the variable is missing or explicitly `auto`, the system does not guess from vibes — it runs the probes and resolves. This is the behavior that makes fresh-checkout deploys converge instead of failing open with whatever Laravel's defaults happen to be.

#### FR-DEPL-007 — Ties Break Toward Safety

A probe set can come back ambiguous: Redis answers on localhost of a box with no daemon extensions, or a container check misfires on an odd kernel. In that fog the only defensible answer is the preset that assumes the least. Shared-hosting drivers always function — slowly, perhaps, but functionally — while the VPS preset on a box without Redis fails loudly at the worst moment. Safe defaults are for exactly these nights.

#### FR-DEPL-008 — The Default Written Down

Defaults that live only in code comments get overridden by accident. Declaring `shared-hosting` as the config default means every consumer — detection fallback, configure-without-flags, documentation — reads the same value from the same place. If the project ever outgrows that default, the change is a one-line diff with a changelog entry, not archaeology.

#### FR-DEPL-009 — Tier-1 and Tier-2 Are Presets, Not Forks

Growth must never require a rewrite, and this row is where that promise becomes concrete. Tier-1 is the shared-hosting preset serving up to roughly five hundred users with zero external services; Tier-2 is the VPS preset past that line, where Redis absorbs queue, cache, and session load through environment swaps alone. The same binary runs both — a school crossing the boundary changes variables and adds a service, never branches code. No feature is gated behind a tier; the upper path buys headroom, and headroom only.

### 4.2 Automatic Detection

#### FR-DEPL-010 — A Command That Reads the Room

`deploy:detect` exists so the server describes itself instead of the operator describing the server. It runs where the app will run, sees what the app will see, and answers the only question that matters before configuration: which preset matches this reality. Schools without dedicated DevOps get a guided answer; experienced sysadmins get a second opinion they can still overrule.

#### FR-DEPL-011 — Four Probes, No More

Four signals separate the two worlds with minimum fuss: is this a container, does Redis answer, can this process daemonize, is Composer even here. Composer's presence is informational — its absence on shared hosting is expected, not a failure. Keeping the probe set small is a feature: every additional probe is another false positive waiting for exam week.

#### FR-DEPL-012 — When the Answer Is VPS

A container runtime is nearly conclusive — nobody runs Docker on $5 shared hosting. Redis plus daemon capability is the other sufficient signal: the box can both hold state in memory and run residents. Either condition earns the `vps-docker` recommendation, because both describe a machine that can honor the VPS preset's assumptions instead of merely tolerating them.

#### FR-DEPL-013 — When the Answer Is Shared Hosting

The mirror logic, and the more common outcome across Indonesian schools. No container, no Redis, no daemon extensions — any single absence collapses the VPS case, because the VPS preset needs all of them the way a motor needs fuel, spark, and air. Recommending shared-hosting here is not pessimism; it is the preset whose every assumption the probes just confirmed.

#### FR-DEPL-014 — Looking Without Touching

Detection runs on production boxes mid-semester, possibly during school hours. It must be safe the way reading a thermometer is safe: no file writes, no config edits, no side effects an operator discovers later. An admin should be able to run it ten times in a row while students submit logbooks without anything changing except terminal output.

#### FR-DEPL-015 — Machine-Readable When Asked

Humans read the plain-language report; provisioning scripts and CI pipelines read JSON. The flag changes only the rendering, never the probes or the recommendation logic, so a wrapper script parsing `deploy:detect --json` cannot drift from what the operator sees. Small surface, outsized usefulness for anyone automating ten school installs.

#### FR-DEPL-016 — Explicit Always Wins

This precedence rule is the constitutional clause of the whole spec. Detection advises; the operator decides. A set `DEPLOY_PROFILE` short-circuits every probe result, and nothing downstream — configure, health checks, docs — may second-guess it. Without this guarantee, detection would be a mandate wearing a recommendation's clothes.

### 4.3 Profile Configuration Application

#### FR-DEPL-017 — From Preset to `.env`

Knowing the right profile and applying it are different skills; the command bridges them. It reads the resolved preset from config, writes the driver keys into `.env`, and leaves everything else — secrets, URLs, mail credentials — exactly as the operator left them. The deployment story becomes three verbs: detect, configure, verify.

#### FR-DEPL-018 — Reject What You Do Not Know

A typo like `--profile=vps_docker` must fail fast with a clear error, not write a half-preset. Validating against the known profile list at the command boundary keeps `.env` from entering states no preset describes. Strictness here is kindness: the failure happens at the keyboard, not at 7 AM when attendance sync stalls.

#### FR-DEPL-019 — Drivers Only, Never Secrets

A configuration command that touches `APP_KEY` or database passwords is a credential incident waiting for a screen-shared terminal. The writer allowlist is exactly the driver keys and nothing else — secrets are created by the operator, rotated by the operator, and never rewritten as a side effect of choosing a profile. This boundary also keeps `.env` diffs reviewable: driver changes are boring by design.

#### FR-DEPL-020 — Safe to Run Twice

Operators re-run commands when nervous, and nervous operators deploy schools. Idempotency means the second run finds the keys already correct and changes nothing — no duplicated lines, no appended duplicates, no drift between runs. The practical test is mundane: run it twice, diff `.env`, see nothing.

#### FR-DEPL-021 — No Flag Means Resolved Profile

The common path should need no flags at all: detect (or default), then configure. When `--profile` is absent, the command uses whatever resolution produced — detection result or shared-hosting fallback — so the documented happy path is two bare commands. Flags remain for the deliberate override, not the routine deploy.

#### FR-DEPL-022 — Container Files Are Out of Reach

`.env` is runtime intent; compose files and the Dockerfile are delivery machinery. A driver preset must never rewrite the topology it runs on — otherwise applying the shared-hosting preset could silently edit the VPS stack definition sitting in the same repo. The wall between them is what lets both specs evolve independently.

### 4.4 Verification and Documentation

#### FR-DEPL-023 — Health Is the Definition of Done

Every deploy, either condition, ends the same way: `system:health` green. It checks PHP, extensions, memory, database, migrations, storage, queue, cache, and the app key — the full list of things that differ between a laptop and a school server. A deployment that skips this step has no acceptance criterion, and this spec refuses to recognize it as complete.

#### FR-DEPL-024 — The Guide Follows the Catalog

Documentation rots the moment it restates values instead of referencing them. The deployment guide presents the two profiles and their presets as the canonical reference, so a preset change propagates by editing config first and prose second. An operator reading the guide sees the same driver values the command writes — because both come from one source.

#### FR-DEPL-025 — The Docker README Knows Its Two Faces

The compose stack and the shared-hosting simulation look similar from a distance and behave differently up close. The README states the mapping plainly — three-service minimal topology versus local simulation — so nobody mistakes the simulation for production or vice versa. Misreading that mapping once costs a school its storage volume; the sentence preventing it is cheap.

#### FR-DEPL-026 — Example File, Working Default

`.env.example` is the first config most operators ever see, copied verbatim into `.env`. Keeping it shared-hosting-optimized means the default path works for the most constrained school, and documenting `DEPLOY_PROFILE` inside it advertises the escape hatch at the exact moment curiosity strikes. Defaults teach; this one teaches the safe path first.

#### FR-DEPL-027 — New Profiles Without Code Surgery

The catalog must outlive two profiles. When a third hosting shape arrives, adding it means declaring data in config and teaching the commands to read it — no business logic touched, no module edited. If a new profile ever requires an application code change, that is a design smell, not a deployment need, and it gets fixed at the architecture level instead.

---

## 5. Non-Functional Requirements

Cross-cutting constraints on the deployment machinery. `Target` holds the concrete SLO where one exists; `N/A` marks architecturally-enforced properties verified via tests and review.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-DEPL-001 | `CRON_SECRET` is required for the `/cron/{secret}` webhook; requests without a valid secret are rejected | 100% unsigned rejected | P0 | F | Full |
| NFR-DEPL-002 | Detection output exposes no credentials, hostnames, or internal paths beyond probe pass/fail status | N/A | P0 | F | Full |
| NFR-DEPL-003 | `deploy:configure` never writes secrets and never clears `APP_KEY` | N/A | P0 | F | Full |
| NFR-DEPL-004 | No secrets are committed; `.env` stays excluded via `.gitignore` | N/A | P0 | A | Full |
| NFR-DEPL-005 | Detection is safe to run repeatedly at any time: idempotent and non-mutating | N/A | P1 | F | Full |
| NFR-DEPL-006 | An explicit profile override survives `deploy:configure` re-runs | N/A | P1 | F | Full |
| NFR-DEPL-007 | A deployment is reproducible from documented commands alone (`composer install`, `npm run build`, `setup:install`, `system:health`) | N/A | P1 | F | Full |
| NFR-DEPL-008 | Docker services use healthchecks so Compose starts `app` after healthy `db`, and `web` after healthy `app` | N/A | P1 | F | Full |
| NFR-DEPL-009 | All new CLI output is bilingual through `__()` (English and Indonesian) | N/A | P1 | F | Full |
| NFR-DEPL-010 | `deploy:detect` explains each probe result in plain language | N/A | P2 | F | Full |
| NFR-DEPL-011 | Presets live only in `config/deployment.php`; docs and Docker files reference, never duplicate, the driver mapping | N/A | P1 | A | Full |
| NFR-DEPL-012 | New commands follow Action/Console conventions and carry `declare(strict_types=1)` | N/A | P1 | A | Full |
| NFR-DEPL-013 | All deployment behavior is covered by the Pest suite with no manual-only steps | N/A | P1 | F | Full |

### 5.1 Security

#### NFR-DEPL-001 — The Webhook Door Has One Key

The scheduler webhook is a URL that runs jobs, which makes it a target the moment it leaks into a proxy log or a forwarded chat message. Gating it on a long random secret — and rejecting everything else outright — turns a leaked URL into a useless string. Rotation is the operator's recourse after any suspected exposure, and the rejection must be total, not a redirect that confirms the route exists.

#### NFR-DEPL-002 — Probes Report Health, Not Anatomy

Detection output gets pasted into support chats and forum posts; that is precisely when hostnames, paths, and credential fragments must not be in it. Pass/fail per probe plus the recommendation is the entire vocabulary. Anything more verbose helps an attacker fingerprint the box while helping the operator not at all.

#### NFR-DEPL-003 — Secrets Survive Configuration

Imagine re-running `deploy:configure` during a maintenance window and finding `APP_KEY` blanked — every session dead, every encrypted value unreadable. The command's write path simply cannot name a secret key, which makes that disaster structurally impossible rather than merely unlikely. Absence from the allowlist is the whole protection, and it needs no test beyond asserting the allowlist's contents.

#### NFR-DEPL-004 — The Repository Never Sees `.env`

One committed `.env` with production mail credentials outlives every rotation, cached forever in git history and every fork. The `.gitignore` exclusion plus review discipline keeps secrets on servers, where rotation actually works. This row exists because the failure mode is permanent while the prevention is one line.

### 5.2 Reliability

#### NFR-DEPL-005 — Detection You Can Run Mid-Semester

An operator investigating a slow morning should be able to run detection without scheduling downtime or warning anyone. Non-mutating and repeatable means the tenth run looks exactly like the first, and none of them disturbs a single student session. Safety here is what makes the command a diagnostic tool instead of a deployment event.

#### NFR-DEPL-006 — Deliberate Choices Stick

Few things erode trust like a tool that forgets. Once `DEPLOY_PROFILE` names a profile explicitly, re-running configure — with or without flags — must preserve that choice rather than re-deriving it from probes that may have changed (a temporarily unreachable Redis, a maintenance-mode container). Persistence of intent is a reliability property, not a convenience.

#### NFR-DEPL-007 — Four Commands Rebuild the World

If a server evaporates the night before accreditation, the recovery story must fit on an index card: install dependencies, build assets, provision, verify health. Every step documented, every step scripted, no tribal knowledge about which order things run in. Reproducibility is the difference between an incident and a catastrophe.

#### NFR-DEPL-008 — Containers Start in Order

A web container proxying to an app that is still migrating, an app connecting to a database still initializing — these races produce the flakiest first-boot failures. Healthchecks serialize the startup chain so each service only starts when its dependency is genuinely ready, not merely launched. Slow disks make this essential rather than polite.

### 5.3 Usability

#### NFR-DEPL-009 — Every Word in Two Languages

The operator reading CLI output is an Indonesian school IT teacher first and an English-docs reader second. Routing every new string through the translation helper with both locales shipped keeps that teacher inside their working language at the most stressful moment — deployment night. Untranslated output is a usability defect with a compliance tail, since bilingual coverage is a project-level mandate.

#### NFR-DEPL-010 — Probes That Explain Themselves

`redis: FAIL` tells an operator nothing actionable; a sentence naming what was attempted, against which host and port, and what the recommendation implies, tells them what to check or whom to call. Plain language here respects the actual audience — staff who manage a school network alongside everything else, not infrastructure specialists.

### 5.4 Maintainability

#### NFR-DEPL-011 — The Mapping Lives Once

Duplicated driver values across config, example env, guide, and README are four clocks that will disagree. One canonical declaration with three references means updates are edits, not hunts. The link checker and review discipline guard the references; nothing guards four independent copies, which is why they are forbidden.

#### NFR-DEPL-012 — Commands Look Like the Codebase

A deploy command written in a foreign style becomes unmaintainable the moment its author graduates. Console conventions plus strict typing keep these commands reviewable by anyone who has touched the rest of the system. Consistency is not aesthetics here; it is the bus factor made concrete.

#### NFR-DEPL-013 — No Step Survives Only in Memory

Manual-only deployment steps die with staff turnover and cannot run in CI. Covering every behavior in the automated suite forces each step to be deterministic, scripted, and re-runnable — properties that serve the midnight operator exactly as much as they serve the pipeline. If a step resists automation, that resistance is itself the finding.

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

```text
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

### 6.5 Scheduler Webhook (existing)

```php
// routes/web/sysadmin.php
Route::get('/cron/{secret}', CronController::class)
    ->name('cron')
    ->middleware('throttle:...');
```

---

## 7. Design Decisions

Recorded choices behind the catalog. These rows carry no test layer — they explain intent so future maintainers change the code without re-litigating the reasoning.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-DEPL-001 | Profile presets are the single source of truth in `config/deployment.php`; example env, docs, and Docker files reference rather than restate driver values | P0 | — | Full |
| DD-DEPL-002 | Detection recommends while an explicit `DEPLOY_PROFILE` always wins | P0 | — | Full |
| DD-DEPL-003 | Shared hosting is the safe default when detection is inconclusive or unset | P0 | — | Full |
| DD-DEPL-004 | `deploy:configure` applies driver keys only and never touches secrets | P0 | — | Full |

### 7.1 Catalog and Detection

#### DD-DEPL-001 — One Preset File to Rule the References

For a long stretch the driver values lived as prose in the deployment guide and as comments in the example env — accurate until the first time someone updated one copy and not the other. Centralizing the mapping as config data turned drift from a certainty into a reviewable diff, and gave `deploy:configure` something data-driven to apply instead of a hardcoded script. The price is a new file whose defaults must track the example env; the single reference table in the guide pays that price down.

#### DD-DEPL-002 — Advice Plus Authority

Pure auto-detection would strand the operator whose environment is genuinely ambiguous; pure manual selection would abandon less experienced staff to four driver keys they never asked to understand. Pairing a recommending probe set with an overriding variable gives both audiences what they need from the same two commands. The maintenance cost of two paths stays small because the probe set is deliberately tiny and deterministic.

#### DD-DEPL-003 — Fall Back to the Least Assumption

Choosing the constrained preset as the default was decided by imagining the failure in each direction. A VPS operator who skips detection lands on the sync queue — slower under load, but working, and loudly improvable. A shared-hosting operator defaulted the other way would land on Redis-backed drivers pointing at nothing — broken at boot, mysteriously. Only one of those mistakes is survivable without understanding drivers, so only one of them is the default.

#### DD-DEPL-004 — Configuration Must Not Mint Credentials

Early drafts let the configure command write anything, including secrets "for convenience." Convenience here meant credentials appearing in terminal scrollback, shell history, and pasted support logs. Restricting writes to driver keys removed an entire category of incident at the cost of operators supplying their own secrets — which they must do anyway for rotation to remain meaningful. Fail-fast validation at container start covers the missing-secret case instead.

---

## 8. Success Metrics

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Zero manual driver tuning | All driver keys set by preset | `deploy:configure` output vs `.env` diff |
| Detection accuracy | Recommended profile matches environment in 100% of probes | `deploy:detect` against both reference environments |
| Post-deploy verification | `system:health` passes in both conditions | Health check exit code |

Per-hosting-condition metrics (deploy time, scheduler uptime) are tracked in [docker-vps-deployment](06IB6-docker-vps-deployment.md) and [shared-hosting-deployment](06IB6-shared-hosting-deployment.md).

---

## 9. Roadmap

### Prerequisites

This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|------------------|
| [system-requirements](J68GZ-system-requirements.md) (J68GZ) | PHP/extensions contract and database portability the presets rely on |
| [installation](8NZAU-installation.md) (8NZAU) | `setup:install` provisioning, environment audit, and `.env` handling used in both deployment paths |
| [job-queue-infrastructure](8FVZA-job-queue-infrastructure.md) (8FVZA) | Queue driver contract (sync/redis) and worker lifecycle consumed by `vps-docker` |
| [system-maintenance](E1MSJ-system-maintenance.md) (E1MSJ) | `system:health` command used as the deployment acceptance gate (FR-DEPL-023) |

### Build Guide

Implement the conditional deployment mechanism: add `config/deployment.php` with the two profile presets, `deploy:detect` (probes per §6.4, `--json` output), and `deploy:configure` (driver-only `.env` writer, idempotent). Then align the deployment guide and `docker/README.md` to the two-profile model and verify both paths end-to-end with `setup:install` plus `system:health`.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [backup-system](HBXCI-backup-system.md) (HBXCI) | Backup scheduling is configured per profile — webhook cron on shared hosting, daemon scheduler on VPS |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| --- | --------------------------------- | ------ | ----- | -------- |
| R-1 | If Redis answers transiently (maintenance, firewall flap), detection may recommend `vps-docker` on a box the operator intends as shared hosting; mitigated by the explicit-override precedence (FR-DEPL-016) | Open | Maintainer | — |
| A-1 | We assume shared hosts permit a writable `.env`, a `public/` document root, and an outbound cron/webhook call for the scheduler trigger | Accepted | Maintainer | — |

## Quick References

- [Spec registry](index.md) — Maintenance phase: 06IB6 (Full), 06IB7 (Full), 06IB8 (Full), 3UOZP (Full)
- [Docker VPS deployment](06IB6-docker-vps-deployment.md) — 06IB7 per-condition spec for the `vps-docker` profile
- [Shared hosting deployment](06IB6-shared-hosting-deployment.md) — 06IB8 per-condition spec for the `shared-hosting` profile
- [System requirements](J68GZ-system-requirements.md) — PHP/extensions contract the presets rely on
- [Installation](8NZAU-installation.md) — `setup:install` provisioning and `.env` handling
- [Job queue infrastructure](8FVZA-job-queue-infrastructure.md) — sync/redis driver contract
- [System maintenance](E1MSJ-system-maintenance.md) — `system:health` acceptance gate
- [Self-hosted single-tenant ADR](../adr/adr-self-hosted-single-tenant.md) — why two constrained presets
- [Performance optimization ADR](../adr/adr-performance-optimization.md) — Tier-1 defaults and Tier-2 growth

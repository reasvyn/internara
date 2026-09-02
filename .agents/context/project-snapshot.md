# Project Snapshot — Comprehensive Map

> **Curated mandatory known context** — must-read at start of every session and when releasing new version. SSOT remains in `docs/` — this is the agent-facing summary derived from AGENTS.md §Project Snapshot.

## Why & Problem Statement

Indonesian vocational schools (SMK) must run a compulsory 3–6 month industrial fieldwork (PKL) at partner companies (DUDI). A mid-to-large school manages **500–1,000 students × 150–300 companies per period** on paper/Excel/WhatsApp. Grade compilation takes 2–3 weeks and accreditation requires audit trails that paper cannot reliably produce. Internara replaces this with **real-time single source of truth, workflow enforcement (slot capacity, attendance windows, evaluation completeness), audit-ready records (GPS-tagged attendance, immutable submitted logbooks, activity log + IP, QR-verifiable certificates), instant reporting, and data sovereignty (self-hosted, no cloud dependency, works on a school LAN without internet)**.

Deep dive: `docs/project-vision.md` (personas, boundary, horizon 2026→2030) and `docs/philosophy.md` (3S Doctrine + 6 values).

## Identity — Scale & Stack (v0.15.9)

| Fact | Value |
|------|-------|
| **Scope** | 19 modules = 18 business + UI + Core (693 PHP files in `app/` — 691 in `app/Modules/` — 45 migrations, 61 spec files incl. 3 meta / 58 feature specs, 17 web route files incl. `web.php`) |
| **Single-tenant** | No `tenant_id` overhead — one instance per school |
| **DB** | SQLite default (zero-config) / MySQL 8 / MariaDB 10.6 / PG 15 |
| **Deploy** | Shared hosting ($5/mo, SQLite+file+sync), **VPS/VM recommended** (Nginx+SQLite/MySQL+optional Redis), Docker Compose (app+queue+scheduler+Redis) |
| **Status** | **v0.15.9** (`composer.json`) / **v0.15.9** (`package.json`) — Stabilization; suite ~98% pass, uneven coverage (core solid, domain needs work); P0 in `Assessment/Certification/Document` |
| **License** | MIT — schools may fork/customize (Dapodik/regional certificate templates) |

Full stack: `AGENTS.md` §Project Identity; philosophy: `docs/philosophy.md` §1–7.

## Architecture — 4-Layer + Action Triad (SSOT: `docs/architecture.md` + Spec `D2FT3`)

```
User → Livewire (validates, catches RejectedException) → Command Action::execute(DTO)
        → Entity::fromModel() → business rules → Model::create/update (from DTO)
        → $this->log() → $this->dispatchEvent() [queued, after commit] → ActionResponse → flash/redirect
```

*Livewire never calls `Model::create()` directly (C1). Read path has no transaction/log/event.*

| Layer | Role | Location |
|-------|------|----------|
| **4 Presentation/UI** | Livewire, Blade, Policies, Routes, Controllers, TallstackUI v4 + Alpine + Tailwind v4 | `{Module}/Livewire/`, `resources/views/{module}/`, `routes/web/{module}.php` |
| **3 Business/Domain Ops** | Command/Read/Process Actions, Events/Listeners/Notifications | `{Module}/Domain/{Domain}/Actions/`, `Events/`, `Listeners/` |
| **2 Data/Persistent** | Models (`BaseModel`, UUID PK, `#[Fillable]`), Entities (`final readonly` + `fromModel()`), DTOs (`BaseData`), Enums (`LabelEnum/StatusEnum`), DB/config/file/cache | `{Module}/Domain/{Domain}/{Models,Entities,Data,Enums}` + `database/migrations/` |
| **1 Framework/Infra** | Base classes, Contracts, Exceptions (`AppException`+`ModuleException`), Services (instance+DI), Support (static) | `app/Modules/Core/{Actions,Models,Entities,Data,...}` + `app/{Module}/Services/`, `Support/` |

**Triad:** `BaseCommandAction` (mutations, transaction+log required, event recommended) · `BaseReadAction` (complex queries, none of those) · `BaseProcessAction` (multi-step orchestration). **Dependency rule:** only downward (UI→Business→Data→Framework), DTOs are leaves (scalars/enums/Carbon only), Entities are pure (C5), cross-module direct import allowed but side effects via Event, Service (L1) must not call Action (L3).

## Module Landscape — 19 Modules (SSOT: `docs/refs/modules/index.md`)

| Module | Domain at `app/Modules/{M}/Domain/` | Role | Depends → Used By |
|--------|--------------------------------------|------|-------------------|
| **Core** | — (base classes, enums, DTO, exceptions, middleware) | Foundation | — → all |
| **UI** | — (app shell, navbar/sidebar/theme) | Presentation | Core → all (`ui::layouts.app`, `x-ui::`) |
| **Auth** | `Account,Login,Password,Permissions,SuperAdmin,AccountRecovery` | Login/RBAC/recovery | Core+User → all |
| **User** | `Profile,Notifications,AccountStatus,Dashboard,Mentor,UserManagement` | Identity & 8-state lifecycle | Core+SysAdmin → all |
| **SysAdmin** | `Announcement,Backups,Observability` | Admin, GDPR, Pulse, backup | User+Academics+Core → User |
| **Setup** | `Installation,SetupWizard` | `setup:install`, 6-step signed wizard | Core+Academics → — (one-time) |
| **Settings** | `Branding,Theme,Locale` | Key-value settings, theming, `setting()/brand()/app_info()` | Core+Academics → all |
| **Academics** | `Department,AcademicYear,School` | Academic structure | Core → Program/Enrollment/Assessment |
| **Partners** | `Company,Partnership` | DUDI registry, MoU, slot quota | Core → Program |
| **Program** | `Internship,InternshipGroup` | Internship lifecycle + cohort grouping | Academics+Partners+Core → Enrollment/Journals/Evaluation |
| **Enrollment** | `Registration,Placement,AccountApplication` | Registration wizard, slot-based placement, change request, CSV | User+Program+Academics+Core → Journals/Assessment |
| **Journals** | `Logbook,Attendance,AbsenceRequest,SupervisionLog,MonitoringVisit` | Daily logs, geotagged attendance, supervision | Enrollment+Program+Core → Evaluation/Reports |
| **Incident** | `IncidentReport` | Field incidents & issues | User+Program+Core → — |
| **Assessment** | `Rubric` | Rubrics & scoring (Needs Work) | Core → Evaluation |
| **Evaluation** | — (skeleton, only Models) | Generic feedback forms, weighted Q, polymorphic | User+Assessment+Program+Core → Certification |
| **Assignment** | `Submission` | Assignments & submissions | User+Program+Core → — |
| **Certification** | `Certificate` | PDF templates, batch issuance, QR verify (Needs Work) | User+Evaluation+Program+Core → — |
| **Reports** | `StudentReport` | Final grade-card, score aggregation, coordinator sign-off | User+Program+Assessment+Enrollment+Core → Certification |
| **Document** | `Handbook,OfficialDocument` | Letter templates, handbooks, renderer (Needs Work) | Core+User → — |

**Health tiers** (`.agents/context/module-health.md`): `Production-Ready: Core,Auth,User,Settings,Setup,SysAdmin,Academics` · `Stable-Needs Attention: Program,Partners,Enrollment,Journals,Incident,Assignment,Reports` (dead DTOs, `event()` inside transactions, broken Blade, wrong `user_id` in attendance, ActionResponse gaps) · `Needs Work P0: Assessment,Certification,Document` (Blade crashes, relation/migration mismatches, missing Entity) · `Skeleton: Evaluation` · `Infra: Jobs,Providers`. Fix order: schema mismatch → ActionResponse → `__()` → Entity → `dispatchEvent()` → dead code.

## Spec Build Order — 12 Phases, 61 Spec Files / 58 Feature Specs (SSOT: `docs/specs/index.md` + `implementation-matrix.md`)

```
P1 Foundation → P2 Configuration → P3 Identity&Auth → P4 Institutional → P5 Partnerships → P6 Programs
→ P7 Enrollment → P8 Daily Ops → P9 Assessment → P10 Certification → P11 Reporting → P12 Maintenance
```

**Spec-zero** `QLHDO Internara Project` (scope/lifecycle/roles/global NFR) parents all phases. Dependency order in `docs/specs/index.md` per-phase tables (e.g. `D2FT3 Arch → FB792 Tech Stack → ZT6VS Core Infra → SE5Q9 Base Classes → T4B26 RBAC → 89SRA Logging`; `81SMS School → 4HWSB Department+XW6F5 Year → XI3LB Company+NTHQA Partnership → 7C5WM Internship+IT0OE Groups → MBB5R Registration+J9GBH Placement → 1KSWL Daily+2EHSE Supervision … → ARDA6 Assessment+AXKZW Evaluation+T657Z Assignment → J0M04 Certification+PKYX6 Document → R6BMW Reports → HBXCI Backup/7HNCF GDPR/9YUUK Archiving`).

**Spec template (11 sections):** problem, goals/non-goals, user stories/UC, FR/NFR, API/Data contracts, DD, success metrics, roadmap, quick refs — via `spec-writing` skill (`docs/specs/spec-template.md`). Status/coverage legend in `implementation-matrix.md` (Impl: Not Started/In Progress/Implemented/Verified/Need Review; Coverage: None/Partial/Full/Spec-Gap). Mostly `Verified/Full` as of 2026-08-19 except `8XMYS Layout & UI` (Implemented/Spec-Gap) and `QLHDO` (Spec-Gap).

## Cross-Cutting Protocols

- **Cross-Role Proxy** (`docs/conventions.md` §8 + ADR): teacher proxies supervisor after 48h inactivity window (logbook/attendance → `FINALIZED/VERIFIED`, `proxy_role='supervisor'` in activity log), grading proxy with weight redistribution + `proxy_weights/proxy_scores` stamped on documents.
- **RBAC flat** — 5 flat roles + 3 functional (`admin-group/mentor/mentee`), `Gate::before`, `BasePolicy` + `AuthorizesRoles/Ownership`; `@hasrole('super_admin')` in Blade.
- **I18n:** `lang/{en,id}/{module}.php` flat per module/submodule (`{submodule}.php`), all user-facing strings via `__()`, add to both locales at once; shared `common.php/notifications.php/activity.php/log.php`.
- **Caching:** all keys registered in `config/cache-keys.php` (C4), event-driven invalidation (`Command → event → listener → Cache::forget()`), TTL Short/Med/Long/Forever.
- **Logging:** `SmartLogger` dual channel file+DB, PII masking (`PiiMasker`), `spatie/laravel-activitylog` (causer, `systemOnly`), no telemetry.
- **Media:** Spatie MediaLibrary (`registerMediaCollections()`, server-side MIME, `Str::slug` filename), DomPDF for certificates/reports.

## Deploy & Ops (SSOT: `.agents/context/deploy-topology.md` + `docs/guides/infra/deployment.md`)

- **Topology:** tag-pushed SemVer tags drive `.github/workflows/release.yml` (a 4-stage QA pipeline run on GitHub Actions: `-dev` → lint+build, `-beta` → lint+test+build, `-rc` → lint+test+guards+build+smoke, final `vX.Y.Z` → all of the above then deploy via SSH to VPS `VPS_HOST/USER/KEY` → `.github/scripts/deploy.sh`); VPS at `$HOME/apps/internara` (`$HOME=/home/andreas`) as 3 containers `app/db(mysql:8)/web(nginx:8080)`, host-level aaPanel reverse proxy → `https://internara.web.id`. Each QA stage calls a reusable helper: `lint.sh`, `test.sh`, `guards.sh`, `smoke.sh`.
- **Caveats:** `environment:` in `docker-compose.yml` determines which env vars reach the container (unmapped host `.env` keys are inert); a release must bump `composer.json` `version` AND create the matching `v*.*.*` tag — `deploy.sh` sets `GIT_URL` to `...git#${VERSION_TAG}` and `git reset --hard $VERSION_TAG` on every deploy destroys manual VPS edits; health gate waits for 200 within 60s (`HEALTH_URL`).
- **Release flow:** `development (-dev) → testing (-beta) → staging (-rc) → production (final vX.Y.Z)`; a final tag never deploys to the VPS unless every QA stage passes.

## Docs & Memory Map

| Need… | Open… |
|-------|-------|
| Product overview, features, install, status (human-facing) | `README.md` |
| Vision, horizon 2026-30, pillars, boundaries, metrics | `docs/project-vision.md` |
| Values, philosophy, trade-offs | `docs/philosophy.md` |
| Living architecture + triad + data flow | `docs/architecture.md` |
| Code rules, security, performance, naming | `docs/conventions.md` |
| 19 modules (conceptual vs reference per module) | `docs/refs/modules/{module}.md` + `{module}-reference.md`, index `docs/refs/modules/index.md` |
| Feature specs + build order | `docs/specs/index.md` → `docs/specs/{ID}-{feature}.md` |
| Implementation status + coverage per spec | `docs/specs/implementation-matrix.md` |
| Deep pattern guides (16 patterns) | `docs/guides/arch/{action,entity,model,data,enum,event,livewire,policy,exception,logging,cache,service,support,modular,testing,ui,ux}-pattern.md` |
| Operations (deploy, CI/CD, infra, health) | `docs/guides/infra/{deployment,infrastructure,configuration,ci-cd,database,cache,queue,filesystem,security,testing,scaling,tools}.md` |
| Architecture decisions (14 ADRs) | `docs/adr/index.md` → `docs/adr/adr-*.md` |
| Mandatory known context (must-read before tasks) | `.agents/context/index.md` → `module-health.md`, `deploy-topology.md`, `dependency-pins-tooling-quirks.md`, `dep-model-status-deprecated.md`, `ui-framework-coexistence.md`, `production-dummy-guard.md`, `codebase-intentional-states.md`, `testing-strategy.md`, `workflow-5step.md` |
| Autonomous agent memory (learnings, session captures) | `.agents/memory/index.md` → `learning-log.md`, session notes, promoted signals |
| Agent rules (150+ consolidated) | `.agents/rules/{rule}.md` — load on demand via Rules Index |

**Suggested reading (new to the project):** `CONTRIBUTING.md` → `README.md` → `docs/specs/index.md` → `docs/philosophy.md` → `docs/getting-started.md` → `docs/architecture.md` → `docs/conventions.md` → `docs/refs/modules/index.md` → `.agents/context/module-health.md` → `AGENTS.md` (5-step workflow).

---
*This file is **mandatory known context** in `.agents/context/`. Update when project scope, version, or health tiers change. Linked from `.agents/context/index.md` and must be read at start of every session and before releasing new version.*
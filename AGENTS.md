# AGENTS.md — Navigation Hub for AI Agents

Mental model and navigation map for AI agents.
**Does NOT duplicate `docs/`** — points there for rules, patterns, and depth.
**Rule bodies live in `.agents/rules/{rule}.md`** — this file indexes them; load a rule file when
a task reaches its concern.

> **Terminology (homespace vs workspace):** when the user says **agent homespace** they mean the
> local configuration at **`~/.agents/`** (user-level — contents vary per user); when they say
> **agent workspace** they mean the project-level overlay at **`./.agents/`** (this repo:
> `internara/.agents/`). Homespace is whatever the user keeps at `~/.agents/`; workspace is the
> project-specific instantiation. Paths below are workspace-relative (`.agents/...`) unless prefixed
> with `~/`.


## Agent Workflow — Canonical

**Every instruction MUST run the full cycle** (`UNDERSTAND → PLAN → IMPLEMENT → VERIFY → SUMMARIZE`)
— any instruction, in any form: a one-line question, a bug report, a feature request, a docs tweak,
or an audit.

The pipeline runs **silently**: the 5 steps are internal reasoning — never narrate them. Surface to
the user **only**:

1. Ambiguity that needs their input.
2. A decision that changes scope, structure, or behavior.
3. An L-size session plan (one short paragraph).
4. One checkpoint before commit (M-size) or per-session (L-size).
5. The final report (what changed, what was verified, caveats).

Every sentence must carry new information or a decision; if it does neither, drop it.

```
UNDERSTAND → PLAN → IMPLEMENT → VERIFY → SUMMARIZE
```

| Step | Purpose | Core questions answered | Key outputs |
|------|---------|-------------------------|-------------|
| **1. Understand** | Intent, scope, and constraints before any exploration | *What is asked? What is the governing spec? What is affected? How big is it?* | Governing spec + FR/NFR/UC IDs, phase & size (S/M/L), affected modules/layers/files, blockers, reordered instruction list |
| **2. Plan** | Context gathering, approach selection, and design | *What exists today? Which approach? What contracts?* | Read file/docs inventory, 2+ considered approaches, chosen design (Action triad, Entity/DTO/Model contracts, error & cache strategy), test & doc plan |
| **3. Implement** | Surgical execution + documentation | *What changes, minimally and cleanly?* | Code edits (preserving unrelated code), doc/PHPDoc updates, automation/scripts |
| **4. Verify** | Quality gates, batched once | *Is anything broken or lost?* | `git status`/`diff` review, style checks, targeted & arch-guard scans, full suite only on-demand |
| **5. Summarize** | Commit and report | *What was delivered and what remains?* | Staged commit `type(scope): desc`, final report (changes, verification, caveats, next steps) |

> **Mapping from legacy 9-step (for reference):** Understand absorbs `Understand + Define & Scope`; Plan absorbs `Explore + Plan + Design`; Implement absorbs `Develop + Document`; Verify = `Test & Verify`; Summarize = `Commit & Report`. All invariants and checks remain — only the grouping is simplified.

### Step 1 — Understand

Internalize intent, not literal words. Do all scoping before reading files.

- **Intent & constraints** — what the user actually wants, hidden requirements, non-goals, hard constraints (deadlines, scope limits, compatibility).
- **Spec-First Doctrine (non-negotiable)** — locate the governing spec in `docs/specs/` via `docs/specs/index.md` (foundation, module, or feature); read its FR/NFR/UC IDs. No behavior without a requirement ID; if none exists, write the spec first — spec-first, never fix-first. Spec outranks literal wording and existing code. If spec and code disagree, fix code to spec; if spec is demonstrably wrong, amend spec with a recorded decision first, then align code and tests.
- **Define & scope** — list affected modules, layers (Action/Entity/DTO/Model/Livewire), and files; identify blockers (pending migrations, config, service registration, permission/policy gaps).
- **Classify** — SDLC phase (see §Phase Classification) and size S/M/L (see §Size Triage). If **L** (>10 files / multi-module / cross-cutting), inform the user in one short paragraph and split into sessions before proceeding (L-size protocol).
- **Instruction ordering** — if the message batches 2+ instructions, decompose → score by impact-to-effort ratio → sort → honor dependencies → group same-area work → surface resulting order only when it differs from the user's sequence (see `.agents/rules/instruction-ordering.md`).
- **Task type** — bug fix / feature / refactor / docs / audit / review / tooling; determines which downstream skills to load next (load only what the task actually uses; an unneeded skill bloats context).

**Exit criteria:** governing spec identified (or explicit recorded decision to proceed without), scope bounded, phase & size classified, instruction order decided.

### Step 2 — Plan

Gather context, decide approach, design contracts — before touching code.

- **Explore (context gathering)** — read module docs, architecture docs (`docs/guides/arch/*.md`), conventions (`docs/conventions.md`), and the **full current content of every file you may touch**; survey `tools/` for existing devtools before manual work (Automation-First — reuse `scan_*.py` scanners instead of manual greps; if 3+ items would be touched repetitively, script it).
- **Plan (approach selection)** — consider **2+ approaches** and pick one; decide Action type (Command / Read / Process per `docs/guides/arch/action-pattern.md`), Entity boundaries (`final readonly` + `fromModel()`), DTO needs (C7: DTO for 3+ params), test strategy (spec-traceable, see Verification Strategy), doc changes, localization, and security implications.
- **Design (contracts)** — define class contracts up front:
  - Action signature and triad base class; `declare(strict_types=1)` (D1)
  - Entity `final readonly`, forbidden imports (C5), business rules delegated to Entity
  - DTO `BaseData`, forbidden imports (C6)
  - Model `#[Fillable]` (D4), FK with `onDelete`/`onUpdate` (D6)
  - Error handling: `RejectedException` not `RuntimeException` (C8)
  - Cache strategy: registered keys in `config/cache-keys.php`, no inline keys (C4)
  - Policy/authorization, validation (no raw request to create/update — D5), `__()` for user strings (D3)
- **Risk & verification plan** — how verification will run (change-type matrix in AGENTS.md §Verification Strategy), which arch-guard scanners apply, and whether full suite / PHPStan is justified (on-demand only).

**Exit criteria:** context inventoried, chosen approach documented (even if just internally), contracts sketched, test & doc plan clear. No code has been written yet.

### Step 3 — Implement

Execute surgically and keep code, specs, docs, and tests aligned.

- **Surgical edits** — smallest change that satisfies the requirement; never full-rewrite a large file by default; preserve unrelated code, comments, formatting, and context. Read before edit, edit → `git diff` sanity check, repeat.
- **Conventions (non-negotiable invariants):**
  - `declare(strict_types=1)` (D1), no debug calls `dd/dump/ray/var_dump` (D2), `__()` for every user-facing string (D3)
  - No Model mutations in Livewire — delegate to Actions (C1); constructor injection only, no `app()->make` (C2)
  - No raw SQL without bindings (C3); no raw `$request->all()` to create/update — use validated DTO/FormRequest (D5)
  - Cache keys via registry (C4), correct exception hierarchy (C8), `#[Fillable]` (D4), FK handling (D6)
  - No unescaped `{!! !!}` for user content; eager loading to avoid N+1; DRY extraction — prefer more, smaller, well-named modules over one dense blob
- **Automation-First in execution** — for repetitive / batch / pattern work (bulk renames, 3+ similar edits, seed data, mass scans), script it or reuse a devtool in `tools/`; batch your own edits into few passes instead of many round-trips.
- **Documentation-first** — update module docs, architecture docs, conventions, and PHPDoc on public methods **before/after** code as part of the same step; keep `docs/specs/*.md` as SSOT and align docs ↔ code ↔ tests (Clean Code & Dedup-Align Doctrine — deduplicate on sight, reuse or extract instead of copy-pasting). History is tracked via `git log --follow -- <file>` and `git diff`, not inline metadata.
- **Business rules in Entities** — Actions orchestrate; Entities own invariants; DTOs carry data; Models are persistence only.

**Exit criteria:** all planned changes applied, docs/PHPDoc in sync, no unrelated drift, `git status` shows only intended files.

### Step 4 — Verify

Batch all changes first, then verify **once**. Full suite is ~2GB+, 10+ min — never per-edit.

- **Version-control verification (Edit Policy, every change):** `git status` + `git diff` (and `git diff --stat`) before/after each edit — only intended files changed, nothing dropped; `git diff` is the lossless-edit proof.
- **Incremental style & build gates:**
  - `vendor/bin/pint --dirty --test --format agent` (PHP + Blade via `Pint/laravel_blade`)
  - `npx prettier --check <file>` for non-PHP (CSS/JS/JSON — `*.php`/`*.blade.php`/`*.md` ignored)
  - `npm run build` for Blade/CSS/JS changes; visual inspection for `*.md`/config
- **Targeted tests (spec-driven, not line-coverage):**
  - `vendor/bin/pest --testsuite={ModuleName}` or `php artisan test --compact --filter={ClassName}`
  - Every test traces to a spec FR/NFR/UC ID (`{SpecID}-{ReqID}: description`); no orphan tests, no spec gaps; coverage = requirements covered
- **Arch-guard scanners (run as a batch):**
  - `python3 tools/scan_violations/cli.py` (C1-C8, D1-D6)
  - `python3 tools/scan_class_contracts/cli.py` (Action/Entity/DTO/Model/Enum)
  - `python3 tools/scan_security/cli.py` (XSS, SQLi, CSRF, auth)
  - `python3 tools/scan_naming/cli.py` · `tools/scan_conventions/cli.py` · `tools/scan_doc_links/cli.py`
- **Full verification on-demand only** (merge-day or user explicitly asks):
  - `php artisan test --compact` (full suite, all modules)
  - `vendor/bin/phpstan analyse --no-progress` (level 8 / Larastan)
  - Change-type matrix in `AGENTS.md` §Verification Strategy decides what is required for the current change type; default is targeted checks, not full suite.

**Exit criteria:** change-type-appropriate gates pass; arch-guard clean or deviations explicitly justified/recorded; no silent tolerance of pre-existing warnings — fix safe adjacent issues or file a GitHub issue (`issue-writing` skill) before ending the session.

### Step 5 — Summarize

Close the loop: version-control checkpoint, commit, and a concise final report.

- **Final git review** — `git status` + `git diff` one last time; stage **only intended files**, never secrets or unrelated changes; confirm nothing was lost.
- **Commit** — format `type(scope): description` — types `feat`, `fix`, `refactor`, `docs`, `chore`, `test`, `perf`, `security`; scope = module name; one concern per commit (group quick wins / interdependent changes; split strategically-separate concerns).
- **Report (surface to user):** what changed (files/modules/specs), what was verified (which gates ran and their result), caveats / known limitations, and **recommended next steps** (pending work, follow-ups, or L-size session plans). Keep it short — narration discipline applies.
- **Session handling** — for M-size: one checkpoint before commit; for L-size: per-session report + `git status`/`diff` review at the end of each session, never attempting L-size in one pass.
- **Pre-commit checklist** (AGENTS.md) must pass: strict types, no debug calls, `__()` coverage, Action triad + DTO rule, Entity delegation, cache registry, N+1 check, escaped output, tests traceable to spec, pint/phpstan/arch-guard as appropriate.
- **Capture learnings (Self-Improvement Loop)** — record decisions, corrections, failures, patterns, constraints, gaps into `context/` (mandatory facts) or `memory/` (evolving learnings) — update in place, write a descriptive commit message, add a row to `context/index.md` or `memory/index.md` and append a one-liner to `memory/learning-log.md`. Promote a signal seen ≥2 times to `rules/` or a skill; durable decisions get an ADR in `docs/adr/`. Run `/self-improvement --deep` at session end to mine `git diff`.

**Exit criteria:** clean commit(s), report delivered, **learning captured** (memory updated, repeats promoted), repo left cleaner than found.

## Project Snapshot — Comprehensive Map (AI Orientation)

> Compressed navigation map so an AI agent can grasp the project without opening 20+ docs. SSOT remains in `docs/` — this section is a pointer, not a duplicate.

### Why & Problem Statement

Indonesian vocational schools (SMK) must run a compulsory 3–6 month industrial fieldwork (PKL) at partner companies (DUDI). A mid-to-large school manages **500–1,000 students × 150–300 companies per period** on paper/Excel/WhatsApp. Grade compilation takes 2–3 weeks and accreditation requires audit trails that paper cannot reliably produce. Internara replaces this with **real-time single source of truth, workflow enforcement (slot capacity, attendance windows, evaluation completeness), audit-ready records (GPS-tagged attendance, immutable submitted logbooks, activity log + IP, QR-verifiable certificates), instant reporting, and data sovereignty (self-hosted, no cloud dependency, works on a school LAN without internet).**

Deep dive: `docs/project-vision.md` (personas, boundary, horizon 2026→2030) and `docs/philosophy.md` (3S Doctrine + 6 values).

### Identity — Scale & Stack (v0.15.8)

| Fact | Value |
|------|-------|
| **Scope** | 19 modules = 18 business + UI + Core (693 PHP files in `app/` — 691 in `app/Modules/` — 45 migrations, 61 spec files incl. 3 meta / 58 feature specs, 17 web route files incl. `web.php`) |
| **Single-tenant** | No `tenant_id` overhead — one instance per school |
| **DB** | SQLite default (zero-config) / MySQL 8 / MariaDB 10.6 / PG 15 |
| **Deploy** | Shared hosting ($5/mo, SQLite+file+sync), **VPS/VM recommended** (Nginx+SQLite/MySQL+optional Redis), Docker Compose (app+queue+scheduler+Redis) |
| **Status** | **v0.15.8** (`composer.json`) / **v0.15.3** (`package.json`) — Stabilization; suite ~98% pass, uneven coverage (core solid, domain needs work); P0 in `Assessment/Certification/Document` |
| **License** | MIT — schools may fork/customize (Dapodik/regional certificate templates) |

Full stack: see `.agents/context/project-identity.md`; philosophy: `docs/philosophy.md` §1–7.

### Architecture — 4-Layer + Action Triad (SSOT: `docs/architecture.md` + Spec `D2FT3`)

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

### Module Landscape — 19 Modules (SSOT: `docs/refs/modules/index.md`)

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

**Health tiers (agent SSOT: `.agents/context/module-health.md`):** `Production-Ready: Core,Auth,User,Settings,Setup,SysAdmin,Academics` · `Stable-Needs Attention: Program,Partners,Enrollment,Journals,Incident,Assignment,Reports` (dead DTOs, `event()` inside transactions, broken Blade, wrong `user_id` in attendance, ActionResponse gaps) · `Needs Work P0: Assessment,Certification,Document` (Blade crashes, relation/migration mismatches, missing Entity) · `Skeleton: Evaluation` · `Infra: Jobs,Providers`. Fix order: schema mismatch → ActionResponse → `__()` → Entity → `dispatchEvent()` → dead code.

### Spec Build Order — 12 Phases, 61 Spec Files / 58 Feature Specs (SSOT: `docs/specs/index.md` + `implementation-matrix.md`)

```
P1 Foundation → P2 Configuration → P3 Identity&Auth → P4 Institutional → P5 Partnerships → P6 Programs
→ P7 Enrollment → P8 Daily Ops → P9 Assessment → P10 Certification → P11 Reporting → P12 Maintenance
```
**Spec-zero** `QLHDO Internara Project` (scope/lifecycle/roles/global NFR) parents all phases. Dependency order in `docs/specs/index.md` per-phase tables (e.g. `D2FT3 Arch → FB792 Tech Stack → ZT6VS Core Infra → SE5Q9 Base Classes → T4B26 RBAC → 89SRA Logging`; `81SMS School → 4HWSB Department+XW6F5 Year → XI3LB Company+NTHQA Partnership → 7C5WM Internship+IT0OE Groups → MBB5R Registration+J9GBH Placement → 1KSWL Daily+2EHSE Supervision … → ARDA6 Assessment+AXKZW Evaluation+T657Z Assignment → J0M04 Certification+PKYX6 Document → R6BMW Reports → HBXCI Backup/7HNCF GDPR/9YUUK Archiving`).

**Spec template (11 sections):** problem, goals/non-goals, user stories/UC, FR/NFR, API/Data contracts, DD, success metrics, roadmap, quick refs — via `spec-writing` skill (`docs/specs/spec-template.md`). Status/coverage legend in `implementation-matrix.md` (Impl: Not Started/In Progress/Implemented/Verified/Need Review; Coverage: None/Partial/Full/Spec-Gap). Mostly `Verified/Full` as of 2026-08-19 except `8XMYS Layout & UI` (Implemented/Spec-Gap) and `QLHDO` (Spec-Gap).

### Cross-Cutting Protocols

- **Cross-Role Proxy** (`docs/conventions.md` §8 + ADR): teacher proxies supervisor after 48h inactivity window (logbook/attendance → `FINALIZED/VERIFIED`, `proxy_role='supervisor'` in activity log), grading proxy with weight redistribution + `proxy_weights/proxy_scores` stamped on documents.
- **RBAC flat** — 5 flat roles + 3 functional (`admin-group/mentor/mentee`), `Gate::before`, `BasePolicy` + `AuthorizesRoles/Ownership`; `@hasrole('super_admin')` in Blade.
- **I18n:** `lang/{en,id}/{module}.php` flat per module/submodule (`{submodule}.php`), all user-facing strings via `__()`, add to both locales at once; shared `common.php/notifications.php/activity.php/log.php`.
- **Caching:** all keys registered in `config/cache-keys.php` (C4), event-driven invalidation (`Command → event → listener → Cache::forget()`), TTL Short/Med/Long/Forever.
- **Logging:** `SmartLogger` dual channel file+DB, PII masking (`PiiMasker`), `spatie/laravel-activitylog` (causer, `systemOnly`), no telemetry.
- **Media:** Spatie MediaLibrary (`registerMediaCollections()`, server-side MIME, `Str::slug` filename), DomPDF for certificates/reports.

### Deploy & Ops (SSOT: `.agents/context/deploy-topology.md` + `docs/guides/infra/deployment.md`)

- **Topology:** tag-pushed SemVer tags drive `.github/workflows/release.yml` (a 4-stage QA pipeline run on GitHub Actions: `-dev` → lint+build, `-beta` → lint+test+build, `-rc` → lint+test+guards+build+smoke, final `vX.Y.Z` → all of the above then deploy via SSH to VPS `VPS_HOST/USER/KEY` → `.github/scripts/deploy.sh`); VPS at `$HOME/apps/internara` (`$HOME=/home/andreas`) as 3 containers `app/db(mysql:8)/web(nginx:8080)`, host-level aaPanel reverse proxy → `https://internara.web.id`. Each QA stage calls a reusable helper: `lint.sh`, `test.sh`, `guards.sh`, `smoke.sh`.
- **Caveats:** `environment:` in `docker-compose.yml` determines which env vars reach the container (unmapped host `.env` keys are inert); a release must bump `composer.json` `version` AND create the matching `v*.*.*` tag — `deploy.sh` sets `GIT_URL` to `...git#${VERSION_TAG}` and `git reset --hard $VERSION_TAG` on every deploy destroys manual VPS edits; health gate waits for 200 within 60s (`HEALTH_URL`).
- **Release flow:** `development (-dev) → testing (-beta) → staging (-rc) → production (final vX.Y.Z)`; a final tag never deploys to the VPS unless every QA stage passes.

### Docs & Memory Map

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

## Context Awareness — Project Orientation

> **Prerequisite:** None — this is the orientation layer loaded after `agent-workflow`.

### When to Activate

Load this section at the start of every session. It provides the mental model all downstream skills depend on.

### Orientation Workflow

This is the **orientation layer** — it does NOT write code or run tests; it builds the mental model all downstream skills depend on. Follow the `agent-workflow` 5-step pipeline (Understand → Plan → Implement → Verify → Summarize) and **Size Triage** (S/M/L session splitting) for the overall instruction; this adds the orientation steps and memory-keeping duties below.

#### Construct — Orientation

- Read the user's instruction carefully; identify the **intent**, not just the literal request
- Determine scope: single file change, cross-module refactor, or new feature
- **Locate the governing spec** in `docs/specs/` (via `docs/specs/index.md`) — read the relevant FR/NFR/UC IDs; if no spec exists for the work, stop and raise it (write the spec first)
- Identify which module(s) are affected
- Read relevant docs: module docs, pattern docs, reference docs
- **Check mandatory known context** — read `.agents/context/index.md` and load any context file matching the task topic (intentional constraints, deploy caveats, dependency pins, known states). Context is **read-only curated knowledge** that every agent must know.
- **Check autonomous memory** — read `.agents/memory/index.md` (and `learning-log.md`) for prior session learnings relevant to the task. Memory is **agent-written evolving knowledge**.
- Verify paths, class names, signatures against actual code — never trust docs blindly; on code/doc mismatch, check git history before deciding which side is correct

#### Agent Memory — `.agents/context/` vs `.agents/memory/`

Two distinct stores with different lifecycles — do not conflate them:

| Store | Path | Nature | Who writes | When to read |
|-------|------|--------|------------|--------------|
| **Known Context (mandatory)** | `.agents/context/` | Curated, must-read before tasks; intentional constraints, deploy caveats, health tiers, deprecated states | Maintainers (human-approved) — agent updates only on proven inconsistency | **Every session start** — `index.md` + matching topic file |
| **Autonomous Memory** | `.agents/memory/` | Evolving, agent-owned learnings; decisions, corrections, failures, patterns, gaps discovered during sessions | Agents autonomously (every session) | **During orientation** if task overlaps prior learnings; **always write** at Summarize |

**Maintain Known Context (`.agents/context/`):**
- Context files are **normative**. Do not invent new facts — if a context file conflicts with reality (code/spec/docs/config changed), update it **directly in the same run** — fix the stale fact and commit with a descriptive message. Never defer.
- To add a new mandatory fact: create `.agents/context/{context}-{issue-name}.md` (flat, kebab-case) and register it in `.agents/context/index.md`. Record only facts where a future agent would make a costly wrong assumption without it; skip trivial/fluid facts.
- Keep each file self-contained (paths, commands, rationale); never duplicate a fact elsewhere — update the existing file instead.

**Maintain Autonomous Memory (`.agents/memory/`):**
- At Summarize, capture decisions/corrections/failures/patterns/constraints/gaps into `.agents/memory/` (self-contained topic files; register in `.agents/memory/index.md`) and append a one-liner to `.agents/memory/learning-log.md`.
- Promote a signal seen ≥2 times to `rules/` or a skill; durable decisions get an ADR in `docs/adr/`. One-offs stay in memory — no rule-bloat.
- Memory is append-evolving; context is curated-stable. Update `memory/` in place with a descriptive commit message; never duplicate a topic.

**House style (both stores):** `## Description`, plain language, an `## AI Agent Guides` decision table where helpful. No inline `Last updated` metadata — history lives in `git log`.

#### Verify — Orientation Completeness

Before handing off to any downstream skill, confirm:
- [ ] Which module(s) and layer(s) are affected
- [ ] Which class types need to be created or modified
- [ ] What invariants (C1-C8, D1-D6) apply to this task
- [ ] What existing code can be followed as a pattern
- [ ] What docs need to be read before writing code

#### Report — Hand Off to Downstream Skill

- Deliver orientation summary to the user:
  - Affected modules and layers
  - Architecture constraints that apply
  - Existing patterns to follow
  - Risks or edge cases identified
- Recommend the appropriate downstream skill(s) for execution
- If the task was classified **L**, present the proposed session plan (each session = one deliverable unit with its own verify + report) and get user approval before execution
- If the work is repetitive, batch, or pattern-based, note the reusable tools/devtools to use (Automation-First — check `tools/` and the Automation Scripts section below first)

### Navigation Patterns

| Need to find... | Look here |
|-----------------|-----------|
| Business logic | `app/Modules/{Module}/Domain/{Domain}/Actions/` |
| Business rules | `app/Modules/{Module}/Domain/{Domain}/Entities/` |
| Data structure | `app/Modules/{Module}/Domain/{Domain}/Models/` |
| Data transfer | `app/Modules/{Module}/Domain/{Domain}/Entities/` (DTOs) |
| State machines | `app/Modules/{Module}/Domain/{Domain}/Enums/` |
| UI components | `app/Modules/{Module}/Domain/{Domain}/Livewire/` |
| Authorization | `app/Modules/{Module}/Domain/{Domain}/Policies/` |
| Side effects | `app/Modules/{Module}/Domain/{Domain}/Events/` and `Listeners/` |
| Infrastructure | `app/Modules/{Module}/Domain/{Domain}/Services/` or `Support/` |
| Base contracts | `app/Modules/Core/Actions/`, `app/Modules/Core/Entities/`, `app/Modules/Core/Enums/` |
| Tests | `tests/{Module}/{Domain}/` |
| Config | `config/{module}.php` |
| Routes | `routes/web/{module}.php` (domains: `{domain}.php` in same dir) |
| Translations | `lang/en/{module}.php`, `lang/id/{module}.php` (domains: `{domain}.php` in same dir) |

### Pattern Recognition

| You see... | It should be... | Violation? |
|------------|-----------------|------------|
| `Model::create()` in a Livewire component | Command Action | **C1 violation** |
| `app()->make(SomeAction::class)` | Constructor injection | **C2 violation** |
| `DB::raw("...")` without binding | Eloquent query builder | **C3 violation** |
| `'cache_key'` string inline | Key in `config/cache-keys.php` | **C4 violation** |
| Entity importing `Action` or `Service` | Entity should be pure | **C5 violation** |
| DTO importing `Model` or `Entity` | DTO should carry scalars only | **C6 violation** |
| Action accepting raw `array` for 3+ params | Should accept DTO | **C7 violation** |
| `throw new RuntimeException('business rule')` | Should be `RejectedException` | **C8 violation** |
| `$fillable = [...]` property | `#[Fillable([...])]` attribute | **D4 violation** |
| `$request->all()` in create/update | `->only()` or `->toArray()` | **D5 violation** |

### Data Flow Tracing

Every mutation in the system follows this path:

```
User interaction
  → Livewire component (validates input, catches RejectedException)
    → Command Action::execute(DTO)
      → Entity::fromModel(model) → business rules
      → Model::create/update(values from DTO)
      → $this->log()
      → $this->dispatchEvent() [queued, fires after commit]
    ← ActionResponse
  ← Flash message / redirect / re-render
```

When debugging or reviewing code, trace this path. If any step is missing or out of order, there's likely a bug or architecture violation.

### Module Boundary Awareness

- Each module owns its full stack: Models, Actions, Livewire, Events, Policies, Services
- Each Domain lives under `app/Modules/{Module}/Domain/{Domain}/` and owns its domain's full stack
- Cross-module imports are **allowed** but prefer events for side effects
- If Module A needs to react to Module B's mutation, use an Event — don't import B's Actions
- Shared code (base classes, contracts, exceptions) lives in `app/Modules/Core/`

### Testing Senses

#### Spec-Driven Minimalism

**Write only the tests the spec requires, then stop.** This is deliberate, not lazy — it speeds up development and verification (spec-scoped tests run in seconds vs. 10+ minutes for the full suite), reduces resource usage (~2GB+ RAM for the full suite), and reduces cognitive overwhelm: a suite that maps 1:1 to requirement IDs is self-explaining. Every test answers "which `FR-*` / `NFR-*` / `UC-*` does this verify?"; if the answer is "none", don't write it.

#### Verification Strategy Selection

**Core principle:** Always ask "can I verify this without running tests?" before reaching for the test suite. The full suite consumes ~2GB+ RAM and 10+ minutes.

| Change type | Lightest verification |
|-------------|----------------------|
| Translation keys | `vendor/bin/pint --dirty --test` + tinker echo |
| Config / docs | Visual inspection |
| Blade / CSS / JS | `npm run build` |
| Single method refactor | `php artisan test --compact --filter={ClassName}` |
| Cross-module refactor | `vendor/bin/pest --testsuite={Module}` |
| New feature / business logic | Full suite ONCE, after all changes batched |

#### Test Pattern Recognition

**Spec first, always.** Before choosing a pattern, map the requirement: which `FR-*` / `NFR-*` / `UC-*` ID in `docs/specs/{ID}-{feature}.md` does this test verify? Test descriptions carry that ID.

| What you're testing | Pattern to follow |
|---------------------|-------------------|
| Command Action (spec-defined mutation) | Arrange (factory + DTO) → Act (execute) → Assert (assertModelExists + ActionResponse) |
| Read Action (spec-defined query) | Arrange (seed data) → Act (execute) → Assert (typed return, collection shape) |
| Entity | Test only the business-rule methods a requirement names; no DB needed |
| DTO | Test the shape the spec's §6 contract defines; no DB needed |
| Enum | Test `label()` / transitions only for the cases and rules the spec lists |
| Livewire | Test render, mount, form submission, authorization; use `actingAs()` |
| Policy | Test `allow` / `deny` for each role; no DB needed beyond the model |

#### Test Health Indicators

| Symptom | Diagnosis |
|---------|-----------|
| Test passes in isolation, fails in suite | Shared state or ordering issue — check `LazilyRefreshDatabase` |
| `Class "X" not found` | Autoload stale — `composer dump-autoload` |
| `SQLSTATE[HY000]` | Migration missing — `php artisan migrate:fresh` |
| Test times out | Infinite loop or queue not drained — add `Queue::fake()` |
| Flaky test (sometimes passes) | Race condition or missing `RefreshDatabase` — isolate the test |
| Test was failing before your change | Pre-existing issue — flag it, don't fix it unless asked |

#### Coverage = Spec Coverage

**Coverage is measured in spec requirements covered — never lines of code.** A requirement with no test is a **spec gap** (fill it). A test with no requirement is **orphan noise** (remove it). The old per-layer percentages (Enum/Entity/DTO 100%, Actions ≥90%, Livewire ≥80%) were removed because they produced padding tests; they may be used only as an internal diagnostic, never as a mandate.

| Question | Answer |
|----------|--------|
| Which spec does this test verify? | Read `docs/specs/index.md` → `docs/specs/{ID}-{feature}.md` |
| Does this test trace to a requirement ID? | If no → orphan, candidate for deletion |
| Does every requirement have a test? | If no → spec gap, write the test |
| Is the scenario beyond what the spec names? | If yes → noise, don't write it |

### Documentation Senses

#### Doc Drift Detection

Doc drift happens when code changes but docs don't. Detect it by asking:

| Question | How to check |
|----------|-------------|
| Does the doc's file listing match the actual directory? | `ls app/Modules/{Module}/Domain/{Domain}/` vs doc |
| Does the Actions table list all current Actions? | `find app/Modules/{Module}/Domain/{Domain}/Actions -name '*Action.php'` |
| Does the Entity description match the actual methods? | Read the Entity class |
| Do the enum cases in the doc match the code? | Read the Enum class |
| Do the migration descriptions match the actual migrations? | Check `database/migrations/` |
| Are the cross-references still valid? | Verify every `[text](path)` resolves |

#### Mismatch Resolution — Git History First

When code and docs disagree — or a claim cannot be confirmed in either — the discrepancy may be an **unrecorded change**. Do NOT assume the code is the source of truth just because it runs, nor that the doc is authoritative just because it was written first. **Both can be stale.**

Before picking a side:

1. **Check git history** (`git log -p -- {file}`, `git blame {file}`) for the code and the doc to see when each last changed
2. **Look for the intent** — does a commit message explain the change (e.g., a refactor that moved a file, or an intentional behavior change that skipped the docs)?
3. **If a commit explains it**, update the other side to match the documented intent
4. **If neither side explains it**, treat it as a finding: report it, don't silently decide

Only trust a claim after confirming it against the codebase **and** git history.

#### Tier Selection

| Content type | Tier | Example |
|-------------|------|---------|
| "Why does this module exist?" | Conceptual | `docs/refs/modules/{module}.md` |
| "What business rules govern enrollment?" | Conceptual | `docs/refs/modules/enrollment.md` |
| "Which files implement the Action?" | Reference | `docs/refs/modules/enrollment-reference.md` |
| "What's the table schema?" | Reference | `docs/refs/modules/enrollment-reference.md` |
| "Why did we choose Actions over Services?" | Conceptual (architecture) | `docs/guides/arch/action-pattern.md` |
| "What's the Action contract?" | Reference (architecture) | `docs/guides/arch/action-pattern.md` |

**Rule of thumb:** If it explains *why*, it's conceptual. If it explains *what* or *how*, it's reference.

#### GitHub Version Senses

Track the version that will be deployed — local, tag, and VPS often drift.

| Question | How to check |
|----------|-------------|
| What version is the code at? | `cat composer.json \| grep version` + `git describe --tags` + `git tag --sort=-v:refname \| head` |
| What version is on VPS? | `ssh internara-vps "cat ~/apps/internara/composer.json \| grep version; git -C ~/apps/internara describe --tags; git log --oneline -1"` |
| Did the latest tag reach the VPS? | `ssh internara-vps "git -C ~/apps/internara describe --tags"` vs `git describe --tags` (local) — must match the pushed `vX.Y.Z` |
| Is the Docker image stale? | `GIT_URL` in `docker-compose.yml` (`#main` vs `#vX.Y.Z`), `docker images internara-app` `CREATED`, `docker exec ... cat /app/public/index.php \| head` |
| Why is VPS on older version? | `composer.json` 0.15.x but the last pushed tag is behind `main` → bump `version`, create `git tag vX.Y.Z`, `git push origin vX.Y.Z` (release.yml deploys on tag push) |

#### Project Version Senses

Track the project version that will be deployed — local, tag, and VPS often drift. Read alongside `.agents/context/project-snapshot.md` at session start and before releasing new version.

| Question | How to check |
|----------|-------------|
| What version is the code at? | `cat composer.json \| grep version` + `git describe --tags` + `git tag --sort=-v:refname \| head` |
| What version is on VPS? | `ssh internara-vps "cat ~/apps/internara/composer.json \| grep version; git -C ~/apps/internara describe --tags; git log --oneline -1"` |
| Did the latest tag reach the VPS? | `ssh internara-vps "git -C ~/apps/internara describe --tags"` vs `git describe --tags` (local) — must match the pushed `vX.Y.Z` |
| Is the Docker image stale? | `GIT_URL` in `docker-compose.yml` (`#main` vs `#vX.Y.Z`), `docker images internara-app` `CREATED`, `docker exec ... cat /app/public/index.php \| head` |
| Why is VPS on older version? | `composer.json` version differs from the last pushed tag → bump `version`, create `git tag vX.Y.Z`, `git push origin vX.Y.Z` — `release.yml` deploys on tag push |

**Version Bump Guide (sync before `composer.json` version bump — not a hard ref):**

All places that mention `version` must be checked and kept in sync before bumping `composer.json:version` — use this as a pre-bump checklist, not a hard-coded list (grep to verify):

```bash
grep -r '"version"' --include="composer.json" --include="*.php" --include="*.md" | grep -E "0\.15|version"
# Check: composer.json, package.json, package-lock.json, config/app.php, AppInfo, docs/specs/index.md, docs/refs, README, .github/workflows
```

Checklist before `composer.json` bump:
1. `composer.json:version` + `package.json:version` (+ lock) — primary
2. `config/app.php:version` (if set) + `AppInfo::version()` fallback — verify `config('app.version')` vs `composer.json`
3. `docs/specs/index.md` Build Order + `docs/refs/modules/**` (if versioned) — no hard `0.14.0` left
4. `README.md` badges / `docs/project-vision.md` — if versioned
5. `AGENTS.md` & `.agents/rules/*` (this guide) — keep GitHub Version Senses table in sync, but *do not* hard-code the version number here — use `grep` above to find stragglers, then bump
6. After bump: `git tag vX.Y.Z` + `git push origin vX.Y.Z` (per `deploy-topology.md`), verify `git describe --tags` + `ssh internara-vps "git describe"`

#### When to Update Docs

| Code change | Doc to update |
|-------------|--------------|
| New Action added | Module reference doc (Actions table) |
| Entity method changed | Module conceptual doc (business rules) |
| Enum case added/removed | Module reference doc (enum table) |
| New migration | Module reference doc (schema section) |
| New module created | `docs/refs/modules/index.md` + conceptual + reference |
| Config key added | Module reference doc (config section) |
| Route added/changed | Module reference doc (Routes table) |
| Base class method changed | `docs/guides/arch/{pattern}-pattern.md` |
| Invariant added/changed | `AGENTS.md` |

#### History Discipline (Git as Source of Truth)

No inline `Last updated` metadata in markdown files. Document freshness and change history are tracked via git:

```bash
git log --follow -- <file>      # history of a doc
git diff -- <file>              # what changed in this branch
git log --since="14 days ago" --oneline -- docs/
```

Write a descriptive commit message (`type(scope): desc`); do not duplicate it inside the file.

#### Link Integrity

Before committing any doc change:
1. Every `[text](path)` resolves to an existing file
2. Every `[text](path#anchor)` matches an existing heading
3. No content is duplicated — cross-reference instead
4. `## Where to Find It` is the standard footer (not `## References`)

### Metacognitive Loop

```
CONSTRUCT → EVALUATE → VERIFY → DECIDE
```

1. **CONSTRUCT** — Read relevant docs and existing code; verify paths and signatures; consider multiple approaches
2. **EVALUATE** — Does it match requirements (FR/NFR/UC from the governing spec)? Respect layer boundaries? Do ONE thing?
3. **VERIFY** — Lint + static analysis + tests pass; no debug calls; `__()` for strings
4. **DECIDE** — Accept / Revise / Split / Escalate / Defer
   - **Split** when: task classified **L** (Size Triage) or scope grew beyond one session — inform the user, propose a session plan, never push through in one pass
   - **Escalate** when: the decision changes scope or architecture, or a governing spec is missing or ambiguous — surface it to the user rather than guessing

### Automation Scripts

| Script | What it does | Command |
|--------|-------------|---------|
| `tools/scan_files/cli.py` | File counts and lines of code per module | `python3 tools/scan_files/cli.py` |
| `tools/scan_architecture/cli.py` | Component counts per module, submodule structure | `python3 tools/scan_architecture/cli.py` |
| `tools/scan_violations/cli.py` | C1-C8, D1-D6 invariant violations | `python3 tools/scan_violations/cli.py` |
| `tools/scan_class_contracts/cli.py` | Action/Entity/DTO/Model/Enum class contracts | `python3 tools/scan_class_contracts/cli.py` |
| `tools/scan_security/cli.py` | XSS, SQLi, CSRF, auth patterns | `python3 tools/scan_security/cli.py` |
| `tools/scan_naming/cli.py` | Naming conventions | `python3 tools/scan_naming/cli.py` |
| `tools/scan_conventions/cli.py` | strict_types, Fillable, debug calls | `python3 tools/scan_conventions/cli.py` |
| `tools/scan_doc_links/cli.py` | Broken links in docs | `python3 tools/scan_doc_links/cli.py` |
| `tools/scan_tests/cli.py` | Per-module test results | `python3 tools/scan_tests/cli.py` |
| `tools/scan_skills/cli.py` | SKILL.md meta-framework consistency | `python3 tools/scan_skills/cli.py` |
| `tools/scan_issues/cli.py` | GitHub issues by module/severity | `python3 tools/scan_issues/cli.py` |
| `tools/scan_dead_code/cli.py` | Dead code detection | `python3 tools/scan_dead_code/cli.py` |

Output: `tools/outputs/{timestamp}-{description}.json`.

**Automation-First:** before doing manual or repeated work, check `tools/` and this table for an existing scanner or helper. Never redo by hand what a script does. If a recurring pattern has no script, load `script-automation` to add one.

---

---

> **AGENTS.md is now lean** — containing only the 5-step workflow, project snapshot, and context awareness.
> All supporting maps moved to `.agents/context/`: project-identity, phase-classification, instruction-ordering,
> pre-existing-defects, skill-rules, documentation-senses, metacognitive-loop, skill-map, quick-reference.
> See `.agents/context/index.md` for the full context index.

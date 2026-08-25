<p align="center">
    <img src="https://img.shields.io/badge/version-0.15.1-blue?style=flat-square" alt="Version 0.15.1">
    <img src="https://img.shields.io/badge/PHP-8.4-777BB4?style=flat-square&logo=php" alt="PHP 8.4">
    <img src="https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square&logo=laravel" alt="Laravel 13">
    <img src="https://img.shields.io/badge/Livewire-4-fb70a9?style=flat-square&logo=livewire" alt="Livewire 4">
    <img src="https://img.shields.io/badge/license-MIT-blue?style=flat-square" alt="MIT License">
    <img src="https://github.com/reasvyn/internara/actions/workflows/ci.yml/badge.svg" alt="CI">
    <img src="https://github.com/reasvyn/internara/actions/workflows/build-and-deploy.yml/badge.svg" alt="Deploy">
</p>

# Internara — Vocational Fieldwork Management System

> **Last updated:** 2026-08-25 **Changes:** bump to v0.15.1 — docs sync + dep removal final (FB792/J68GZ REMOVED, TallstackUI-only)

Self-hosted, single-tenant platform for managing compulsory industrial fieldwork programs (PKL —
_Praktik Kerja Lapangan_) at Indonesian SMA/SMK and technical education institutions. MIT-licensed,
designed to run on school-owned infrastructure with zero recurring costs, full data sovereignty,
and offline robustness.

> **SSOT note:** This README is the single source of truth for **product definition** (scope,
> personas, principles, system boundary) and **project status** (module landscape, tech debt,
> roadmap). The former `docs/foundation/product-definition.md` and `docs/foundation/project-overview.md`
> have been merged here and removed. Detailed requirements live in `docs/specs/QLHDO-internara-project.md`.

---

## Why Internara Must Exist

Indonesian vocational schools (SMK) are legally required (Kemendikbud) to run PKL — 3–6 months of
supervised fieldwork at a partner company (DUDI). A medium-to-large SMK manages **500–1,000 active
students across 150–300 companies** per period. Today this is run on paper, Excel, WhatsApp, and
ad-hoc email — and it breaks at scale.

| Problem | Scale | Why it hurts |
|---------|-------|--------------|
| **Scale** | 500 students × 90 days = 45,000 attendance records; ×12 logbooks = 6,000 entries; ×3–5 assignments = 1,500–2,500 submissions | No coordinator team can process this manually without errors or burnout |
| **Fragmentation** | Excel enrollment lists (version conflicts) · paper attendance (lost, no aggregate) · WhatsApp logbooks (unsearchable, no audit) · printed evaluations (slow aggregation) · email change requests (no SLA) · paper certificates (forgeable) | Compiling final grade cards takes **2–3 weeks** per cohort |
| **Visibility** | No real-time view of placements, slot capacity, missing logbooks, or completion rate | Problems discovered weeks late, when remediation is too late |
| **Compliance** | Permen Pendidikan requires auditable PKL records for accreditation (signed attendance, reflective logbooks, standardized rubrics, verifiable certificates) | Missing records → program suspension or grade penalties |
| **Equity** | Teachers spend 60–80% of time on admin; industry supervisors frequently disengage | Students at silent companies receive no feedback for weeks |

**Business value shipped by Internara:**

- **Single source of truth** — admin, teacher, supervisor, and student see the same real-time data
- **Automated workflow enforcement** — e.g. cannot register for closed internships, cannot backdate attendance beyond window, cannot compile grade cards until evaluations complete
- **Real-time visibility** — dashboards for enrollment, placement rate, slot capacity, attendance anomalies
- **Audit-ready records** — every action logged via SmartLogger (user, timestamp, IP), GPS-tagged attendance, immutable submitted logbooks, QR-verifiable certificates
- **Reduced burden** — grade-card compilation from 2–3 weeks → instant; certificate verification from phone call → QR scan; MoU expiry from spreadsheet memory → automated alerts
- **Data sovereignty** — self-hosted, no cloud dependency, no subscription, full DB access, works on school LAN without internet

---

## Design Principles — 3S Doctrine

| Principle | Definition |
|-----------|------------|
| **S1 — Secure** | Authorization at every layer, integrity & PII protection. Credentials and PII in distinct tables; GPA, location, and minors' data treated as sensitive. |
| **S2 — Sustain** | Module colocation — 18 modules each own their Models/Actions/Livewire/Events/Policies. Action Triad enforces single responsibility by construction; no 500-line God class. |
| **S3 — Scalable** | Single-tenant (no tenant-ID overhead), CQRS-inspired Action triad keeps read/write paths decoupled so optimizing one does not break the other. "Scalable" = does not collapse as features are added, not "millions of users". |

---

## Personas

| Persona | Who | Needs |
|---------|-----|-------|
| **Interns (Students)** | 16–18 y.o. SMK students, mobile-first, low digital literacy, intermittent connectivity | Register, daily GPS attendance, reflective logbook, assignments, report submission, grades, certificates, mentor evaluation |
| **Schools (Admin + Teachers)** | Admin team 2–5 managing 500+ students; 10–30 teachers mentoring 15–30 students each, full-time teaching + part-time mentoring | System config, enrollment/placement, company/partner admin, dashboards & batch CSV, logbook review, site-visit logs, grading, grade-card sign-off. Cross-Role Proxy when supervisors disengage |
| **Companies (Supervisors)** | Industry mentors, 3–10 students each, least time (secondary responsibility), weekly access at most | One-tap attendance verification, minimal-form logbook review, guided competency evaluation; self-explanatory without training |

---

## System Boundary

**In-scope — full PKL lifecycle:**

Institutional Setup (school, departments, academic years, branding) → Partnership Management (company registry, MoU lifecycle, slot quotas) → Program Management (periods, phases, requirements, cohort groups) → Enrollment (guest→student atomic provisioning, wizard, placement with capacity, change requests) → Daily Ops (GPS-geofenced attendance, absence approvals, dual-mentor logbook) → Assessment & Grading (rubrics, multi-evaluator, weighted aggregation) → Evaluation (feedback & surveys) → Certification (PDF templates, batch issuance, QR verification) → Reporting (grade-card compilation, coordinator sign-off, audit export) → Closure (readiness checks, archival, retention).

**Out-of-scope (explicitly excluded):**

| Excluded | Why |
|----------|-----|
| Multi-tenant SaaS (billing, tenant routing, partitioned DB) | Single-school deployment only; schools self-host |
| General HR/ATS (job boards, payroll, post-graduation) | Scope ends at PKL completion + certificate |
| Real-time chat / WebSocket messaging | Structured notifications + email only; avoids infra bloat |
| Government DB sync (Dapodik/NSP direct) | CSV import/export only — minimal integration surface |
| Native mobile apps | Responsive web only — no app-store fragmentation |

---

## Features

- **Student Lifecycle** — registration wizard, slot-based company placement, change requests
- **Daily Operations** — geotagged attendance, reflective logbooks, absence requests
- **Assessment & Evaluation** — competency rubrics, multi-evaluator grading, Google Forms-like surveys
- **Program Management** — internship periods, phases, cohort groups, document requirements
- **Partnerships** — company registry, MoU management, slot quota tracking
- **Reporting** — final grade card compilation, weight-based score aggregation, coordinator sign-off
- **Certification** — certificate templates, batch issuance, QR-code verification
- **Account Management** — RBAC with 5 flat roles, 8-state account lifecycle, recovery mechanisms
- **Observability** — Laravel Pulse, dual-channel SmartLogger (file + DB)
- **Localization** — English codebase, bilingual UI (EN/ID)

---

## Architecture

**Action-based MVC with vertical slicing.** Code organized by business module, not technical layer.
Each of the 18 modules owns its complete stack — persistence, business rules, UI, and authorization
— colocated under `app/{Module}/`.

```
app/
├── Core/           Base classes, contracts, exceptions, utilities
├── Auth/           Login, RBAC, account recovery, activation
├── User/           Profiles, notifications, status, dashboards
├── SysAdmin/       User management, audit, announcements, health
├── Setup/          Installation wizard, provisioning
├── Settings/       Config, branding, feature flags, localization
├── Academics/      Departments, academic years
├── Program/        Internship lifecycle, phases, groups
├── Enrollment/     Registration, placement, change requests
├── Assessment/     Rubrics, competency scoring
├── Evaluation/     Feedback forms, auto-scoring
├── Assignment/     Tasks, submissions, grading
├── Journals/       Logbooks, attendance, supervision logs, monitoring visits
├── Incident/       Issue reporting, resolution
├── Partners/       Companies, MoU agreements
├── Certification/  Certificates, templates, QR
├── Reports/        Final grade cards, aggregation
├── Document/       Templates, handbooks, rendering
├── Console/        Artisan commands
├── Jobs/           Queued jobs
└── Providers/      Service providers
```

**4-layer model** (strict downward dependency):

| Layer | Content | Location |
|-------|---------|----------|
| **Presentation/UI** | Livewire, Blade, Policies, Routes | `{Module}/Livewire/`, `resources/views/` |
| **Business/Domain Ops** | Command/Read/Process Actions, Events | `{Module}/Actions/`, `{Module}/Events/` |
| **Data/Persistent** | Models, Entities, DTOs, Enums | `{Module}/Models/`, `{Module}/Entities/` |
| **Framework/Infra** | Core base classes, Contracts, Services | `app/Core/`, `{Module}/Services/` |

```
Presentation (Layer 4)    Livewire → Blade → Alpine.js
         ↓
Business Ops (Layer 3)    Command/Read/Process Actions → Events
         ↓
Data (Layer 2)            Models ← Entities ← DTOs ← Enums
         ↓
Infrastructure (Layer 1)  Core base classes, Services, Support
```

Every mutation: **Livewire → Action → Entity → Model → DB**. Business rules in Entities, Actions orchestrate, Models persist.

**Key patterns:**

- **Action Triad** — every mutation is a Command Action (transaction + log + event); complex queries go in Read Actions; multi-step workflows in Process Actions
- **Entity separation** — business rules live in `final readonly` Entity classes, not in Models
- **DTO boundaries** — immutable `BaseData` objects carry data between layers; `ActionResponse` returns structured results
- **State machines** — status enums implement `StatusEnum` contract with explicit transition rules

---

## Project Status — Where We Are

**Phase: v0.15.1 — TallstackUI Complete** (in progress). 18 modules with full stack (models, actions,
livewire, events, policies, routes, translations). Architecture is sound — 4-layer model, Action
Triad, Entity boundaries, DTO contracts. Focus: fix P0 runtime errors → improve coverage → UI/UX polish.

### Module Landscape

| Health | Modules | Notes |
|--------|---------|-------|
| **Production-Ready** | **Core**, **Auth**, **User**, **Settings**, **Setup**, **SysAdmin**, **Academics** | Stable, well-tested, fully documented |
| **Stable — Needs Attention** | **Program**, **Partners**, **Enrollment**, **Journals**, **Incident**, **Assignment**, **Reports** | Work but known issues (dead DTOs, event dispatch violations, broken Blade, `user_id` bugs, ActionResponse gaps, empty Livewire dirs) — tracked in GitHub Issues |
| **Needs Work** | **Assessment**, **Certification**, **Document** | Structurally complete but runtime crashes (Blade array/multiple-root, broken relations, schema mismatches, missing migration columns, no Entity layer in Document) — multiple P0 issues |
| **Skeleton** | **Evaluation** | Models only — zero Actions/Entities/Livewire/Routes/Events |
| **Infrastructure** | **Jobs**, **Providers** | No business logic — queued jobs & service providers |

### Technical Debt (priority order)

1. **Schema mismatches** — migrations ≠ code refs (Document, Certification) → runtime crashes
2. **ActionResponse gaps** — Actions return `Model`/`void` instead of `ActionResponse` (violates Action Triad C7)
3. **Hardcoded strings** — user-facing text without `__()` → blocks localization
4. **Missing Entity layer** — business rules in Actions/Models instead of `final readonly` Entities
5. **Event dispatch violations** — `event()` inside transactions instead of `$this->dispatchEvent()` → race conditions
6. **Dead code** — unused DTOs, unregistered observers, events without listeners

### What's Next

Tracked in GitHub Issues:

- Fix P0 runtime errors in broken modules
- Complete Reports module purification
- Improve test coverage for domain modules (Assessment, Evaluation, Certification, Document)
- Documentation sync across all modules

Test suite passes at ~98% but coverage is uneven — Core/User/Settings/Setup/Academics are solid; domain modules need more tests.

---

## Deployment

| Model | Target | Stack | Trade-offs |
|-------|--------|-------|------------|
| **Shared Hosting** | Budget SMK (Rp 100K–500K/mo) | PHP 8.4, SQLite, sync queue, cron endpoint | Simplest, no background jobs, single-process |
| **VPS / VM** | Mid-range SMK (Rp 200K–1M/mo) | PHP 8.4-FPM, Nginx, SQLite/MySQL, optional Redis | Full async queue, scheduler, **recommended** |
| **Containerized** | Tech-savvy / Dinas PKL | Docker Compose: app + queue + scheduler + Redis | Most robust, easiest scaling, requires Docker |

Defaults to **SQLite** — zero-config (`php artisan setup:install` and go). Migrate to MySQL/MariaDB/PostgreSQL with a one-line `.env` change when beyond ~500 concurrent users — no code change needed. Offline-first: works on school LAN without internet, no cloud/CDN/third-party API in critical path.

---

## Localization

Bilingual **EN/ID** — English is code/docs, Indonesian is preferred UI and mandatory for government
reporting (Dapodik/NSP). Every user-facing string uses `__()` with keys in `lang/en/` + `lang/id/`.

| Concept | Local Term | DB Field | Regulatory Context |
|---------|------------|----------|--------------------|
| Student National ID | NISN | `student_id_number` | Dapodik submission |
| School Code | NPSN | `institutional_code` | Accreditation |
| Study Program | Jurusan | `department` | SMK-specific |
| Host Company | DUDI | `company` | Kemendikbud terminology |
| Fieldwork Program | PKL | `internship` | Mandatory for all SMK |
| School Mentor | Guru Pembimbing | `teacher` | Accreditation |
| Industry Supervisor | Pembimbing Lapangan | `supervisor` | Company-registered |

Locale togglable via `Settings`; language switcher respects `__()` everywhere.

---

## Quick Start

```bash
# Clone & install
git clone https://github.com/reasvyn/internara.git
cd internara
composer install
npm install && npm run build

# Configure & provision
cp .env.example .env
php artisan key:generate
php artisan setup:install          # audits env, runs migrations, seeds defaults

# Start development
composer run dev                   # serves app + queue + logs + vite concurrently
```

Complete the 6-step setup wizard by opening the signed URL output by `setup:install`.

### Prerequisites

- **PHP 8.4+** with extensions: `bcmath`, `ctype`, `curl`, `fileinfo`, `gd`, `intl`, `mbstring`,
  `openssl`, `pdo`, `tokenizer`, `xml`, `zip`
- **Composer 2.x**, **Node.js 20+**, **npm 10+**
- Database: SQLite (default, zero-config), MySQL 8+, MariaDB 10.6+, or PostgreSQL 15+

---

## Documentation

All documentation lives in `docs/`. Start here:

| Topic | Document |
|-------|----------|
| Getting started | [`docs/getting-started.md`](docs/getting-started.md) |
| Architecture | [`docs/architecture.md`](docs/architecture.md) |
| Module overviews | [`docs/modules/index.md`](docs/modules/index.md) |
| Coding conventions | [`docs/conventions.md`](docs/conventions.md) |
| Deployment | [`docs/infrastructure/deployment.md`](docs/infrastructure/deployment.md) |
| Testing guide | [`docs/infrastructure/testing.md`](docs/infrastructure/testing.md) |
| Full doc index | [`docs/index.md`](docs/index.md) |

Agent memory (intentional states, deploy topology, dependency pins): [`.agents/context/index.md`](.agents/context/index.md).

For full product scope and requirements, see `docs/specs/QLHDO-internara-project.md` (umbrella spec) and `docs/conventions.md`.

---

## Quality

```bash
composer run test                  # Full test suite
composer run analyse               # PHPStan static analysis
composer run quality               # Lint + analyse + feature tests
vendor/bin/pint --dirty --format agent  # Code style
```

- **Testing:** Pest 4 with `LazilyRefreshDatabase`, feature + unit tests for every Action
- **Static analysis:** PHPStan at level 8 (configured in `phpstan.neon`)
- **Code style:** Laravel Pint (PSR-12 + Laravel conventions)

Automation scripts (scope with `--module {Name}`, outputs in `scripts/outputs/`):

```bash
python3 scripts/scan_architecture.py    # Component counts per module
python3 scripts/scan_class_contracts.py # Action/Entity/DTO/Model contract compliance
python3 scripts/scan_conventions.py     # Convention compliance (strict_types, Fillable, debug)
python3 scripts/scan_dead_code.py       # Unused code detection
python3 scripts/scan_doc_links.py       # Broken links in docs + .agents/context/ + README
python3 scripts/scan_files.py           # File inventory and LOC
python3 scripts/scan_issues.py          # GitHub issue summary
python3 scripts/scan_naming.py          # Naming conventions
python3 scripts/scan_security.py        # XSS, SQLi, CSRF, auth patterns
python3 scripts/scan_tests.py           # Test results
python3 scripts/scan_violations.py      # Architecture invariants C1-C8, D1-D6
```

---

## Contributing

We welcome contributions from everyone — whether you are fixing a typo, adding a test, or
shipping a new module. See **[CONTRIBUTING.md](CONTRIBUTING.md)** for the full guide.

**Quick start for contributors:**

1. **Fork & branch** — `git clone` your fork, then `git checkout -b feat/short-description`
   (branch types: `feat/`, `fix/`, `refactor/`, `docs/`, `chore/`, `hotfix/` — see `CONTRIBUTING.md`)
2. **Read the foundations** — skim [`docs/architecture.md`](docs/architecture.md) and
   [`docs/conventions.md`](docs/conventions.md) for the 4-layer model, Action Triad, and invariants (C1–C8, D1–D6)
3. **Build & test locally** — `composer install && npm install && php artisan setup:install`, then iterate with
   `composer run dev`; keep changes focused on a single concern
4. **Quality gates** — before pushing, run `composer run quality` (tests + PHPStan), `vendor/bin/pint --dirty --format agent`, and `npm run build` for frontend changes; add tests that trace to a spec requirement (`FR-*`/`NFR-*`/`UC-*`)
5. **Commit & PR** — commit as `type(scope): description` (e.g. `fix(enrollment): validate slot capacity`), push, and open a PR that references the related issue — a maintainer will review within a few days

Language: English for code, comments, commits, and docs; Indonesian only in `lang/id/`.

---

## Security

If you discover a security vulnerability, please report it privately via
[reasvyn@gmail.com](mailto:reasvyn@gmail.com) rather than opening a public issue.

---

## License

[MIT](LICENSE) &mdash; &copy; 2025&ndash;2026 Reas Vyn · MIT removes all financial and legal barriers
for Indonesian vocational schools to fork, customize (e.g. Dapodik integration, regional certificate
templates), and deploy. Contributions back upstream create a shared resource for all SMKs.

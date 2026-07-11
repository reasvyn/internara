<p align="center">
    <img src="https://img.shields.io/badge/PHP-8.4-777BB4?style=flat-square&logo=php" alt="PHP 8.4">
    <img src="https://img.shields.io/badge/Laravel-13-FF2D20?style=flat-square&logo=laravel" alt="Laravel 13">
    <img src="https://img.shields.io/badge/Livewire-4-fb70a9?style=flat-square&logo=livewire" alt="Livewire 4">
    <img src="https://img.shields.io/badge/license-MIT-blue?style=flat-square" alt="MIT License">
</p>

# Internara — Vocational Fieldwork Management System

Self-hosted, single-tenant platform for managing compulsory industrial fieldwork programs (PKL —
_Praktik Kerja Lapangan_) at Indonesian SMA/SMK and technical education institutions.

---

## Features

- **Student Lifecycle** — registration wizard, slot-based company placement, change requests
- **Daily Operations** — geotagged attendance, reflective logbooks, absence requests
- **Assessment & Evaluation** — competency rubrics, multi-evaluator grading, Google Forms-like
  surveys
- **Program Management** — internship periods, phases, cohort groups, document requirements
- **Partnerships** — company registry, MoU management, slot quota tracking
- **Reporting** — final grade card compilation, weight-based score aggregation, coordinator sign-off
- **Certification** — certificate templates, batch issuance, QR-code verification
- **Account Management** — RBAC with 5 flat roles, 8-state account lifecycle, recovery mechanisms
- **Observability** — Laravel Pulse, dual-channel SmartLogger (file + DB)
- **Localization** — English codebase, bilingual UI (EN/ID)

---

## Architecture

**Action-based MVC with vertical slicing.** Code is organized by business module, not technical
layer. Each of the 19 modules owns its complete stack — persistence, business rules, UI, and
authorization — colocated under `app/{Module}/`.

```
app/
├── Core/         Base classes, contracts, exceptions, utilities
├── Auth/         Login, RBAC, account recovery
├── User/         Profiles, notifications, status
├── SysAdmin/     User management, audit, announcements
├── Setup/        Installation wizard, provisioning
├── Settings/     Config, branding, feature flags
├── Academics/    Departments, academic years
├── Program/      Internship lifecycle, phases, groups
├── Enrollment/   Registration, placement, change requests
├── Assessment/   Rubrics, competency scoring
├── Evaluation/   Feedback forms, auto-scoring
├── Assignment/   Tasks, submissions, grading
├── Journals/     Logbooks, attendance, absences
├── Guidance/     Supervision logs, mentoring
├── Incident/     Issue reporting, resolution
├── Partners/     Companies, MoU agreements
├── Certification/Certificates, templates, QR
├── Reports/      Final grade cards, aggregation
└── Document/     Templates, handbooks, rendering
```

The architecture follows a **4-layer model** with strict downward dependency:

| Layer                   | Content                                | Location                                 |
| ----------------------- | -------------------------------------- | ---------------------------------------- |
| **Presentation/UI**     | Livewire, Blade, Policies, Routes      | `{Module}/Livewire/`, `resources/views/` |
| **Business/Domain Ops** | Command/Read/Process Actions, Events   | `{Module}/Actions/`, `{Module}/Events/`  |
| **Data/Persistent**     | Models, Entities, DTOs, Enums          | `{Module}/Models/`, `{Module}/Entities/` |
| **Framework/Infra**     | Core base classes, Contracts, Services | `app/Core/`, `{Module}/Services/`        |

**Key patterns:**

- **Action Triad** — every mutation is a Command Action (transaction + log + event); complex queries
  go in Read Actions; multi-step workflows in Process Actions
- **Entity separation** — business rules live in `final readonly` Entity classes, not in Models
- **DTO boundaries** — immutable `BaseData` objects carry data between layers; `ActionResponse`
  returns structured results
- **State machines** — status enums implement `StatusEnum` contract with explicit transition rules

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

| Topic              | Document                                                                 |
| ------------------ | ------------------------------------------------------------------------ |
| Getting started    | [`docs/getting-started.md`](docs/getting-started.md)                     |
| Architecture       | [`docs/architecture.md`](docs/architecture.md)                           |
| Module overviews   | [`docs/modules/index.md`](docs/modules/index.md)                         |
| Coding conventions | [`docs/conventions.md`](docs/conventions.md)                             |
| Deployment         | [`docs/infrastructure/deployment.md`](docs/infrastructure/deployment.md) |
| Testing guide      | [`docs/infrastructure/testing.md`](docs/infrastructure/testing.md)       |
| Full doc index     | [`docs/index.md`](docs/index.md)                                         |

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

---

## Contributing

1. Read the [architecture](docs/architecture.md) and [conventions](docs/conventions.md) docs first
2. Load the `context-awareness` skill for project orientation
3. Follow the metacognitive loop: **Construct → Evaluate → Verify → Decide**
4. Ensure pre-commit checklist passes (tests, pint, phpstan)
5. Submit a PR with a descriptive title following `type(scope): description` format

---

## Security

If you discover a security vulnerability, please report it privately via
[reasvyn@gmail.com](mailto:reasvyn@gmail.com) rather than opening a public issue.

---

## License

[MIT](LICENSE) &mdash; &copy; 2025&ndash;2026 Reas Vyn

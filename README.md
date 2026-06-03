# Internara
> Last updated: 2026-06-03
> Changes: restructure 23 domains into 16 for SMA/SMK context


**Self-hosted vocational fieldwork management system** for upper-secondary schools and educational institutions running compulsory work placement programs. Internara manages the full program lifecycle — from registration through placement, daily operations, assessment, and certification.

Built for schools that manage mandatory on-site industry placements for their students. Installed on your own infrastructure, operated by your own staff. Not SaaS, not multi-tenant, not centrally managed.

## Features

- **For Schools** — manage academic years, departments, programs, and company partnerships
- **For Students** — log attendance, write daily journals, submit assignments and reports, track progress
- **For Teachers** — supervise students, review journals, grade assignments and reports, conduct assessments
- **For Industry Supervisors** — verify attendance, review journals, submit placement evaluations

## Architecture

Internara follows a **Domain-first, Action-based MVC** architecture. Business logic is organized by domain, not by technical layer.

```
app/Domain/
├── Core/           Base classes, contracts, cross-domain utilities
├── Auth/           Authentication, RBAC, account lifecycle
├── User/           User identity, profiles, dashboards
├── Academics/      School profile, departments, setup wizard
├── Partners/       Companies (DUDI), MoU agreements
├── Program/        PKL lifecycle, phases, groups, requirements
├── Enrollment/     Registration, placement, slot management
├── Guidance/       Supervision, mentoring, handbooks
├── Journals/       Logbook, attendance, scheduling
├── Assignments/    Tasks, submissions, grading
├── Reports/        Final reports, supervisor review
├── Assessment/     Rubrics, scoring, evaluations
├── Certification/  Certificates, document templates
├── Incidents/      Issue reporting, resolution
├── Settings/       Runtime configuration, branding
└── Administration/ User CRUD, announcements, GDPR
```

Each domain is self-contained with its own Models, Actions, Livewire components, Policies, Enums, Entities, and optional Http/Notifications/Events layers — all colocated for high cohesion and low coupling.

## Quick Start

```bash
git clone https://github.com/reasvyn/internara.git
cd internara
composer install && npm install
cp .env.example .env
php artisan key:generate
php artisan setup:install
```

Follow the setup wizard to configure your institution and admin account. Access the app at `http://localhost:8000`.

## Requirements

- PHP 8.4+ with extensions: bcmath, ctype, fileinfo, mbstring, openssl, pdo, tokenizer, xml, curl, gd, intl, zip
- Composer 2.x
- Node.js 20+ with npm
- Database: SQLite (default), MySQL 8+, MariaDB 10+, or PostgreSQL 15+

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 13 |
| Frontend | Livewire 4, Alpine.js, Tailwind CSS v4, DaisyUI 5, maryUI 2 |
| Build Tooling | Vite 8, Prettier 3 |
| Database | SQLite / MySQL / MariaDB / PostgreSQL |
| Realtime | Laravel Reverb (WebSockets) |
| Queue | Database / Redis |
| Monitoring | Laravel Pulse |
| Testing | Pest 4 (Feature, Unit, Architecture) |
| Code Quality | Laravel Pint, PHPStan, Prettier |

## Documentation

### Product
- [Product Definition](docs/product-definition.md)
- [Architecture](docs/architecture.md)
- [Coding Conventions](docs/conventions.md)

### Setup & Operation
- [Getting Started](docs/getting-started.md)
- [Installation](docs/installation.md)
- [Configuration](docs/configuration.md)
- [Infrastructure](docs/infrastructure.md)

### Security & Access
- [RBAC](docs/rbac.md)
- [Observability](docs/observability.md)

### Frontend & UI
- [UI/UX Design](docs/ui-ux.md)
- [Branding](docs/branding.md)

### Technical Reference
- [Database](docs/database.md)
- [Routes](docs/routes.md)
- [Session](docs/session.md)
- [Cache](docs/cache.md)
- [Filesystem](docs/filesystem.md)
- [Notifications](docs/notification.md)
- [Testing](docs/testing.md)
- [Known Issues](docs/known-issues.md)

### Domain Reference
- [Core](docs/domain/core.md)
- [Auth](docs/domain/auth.md)
- [User](docs/domain/user.md)
- [Academics](docs/domain/academics.md)
- [Partners](docs/domain/partners.md)
- [Program](docs/domain/program.md)
- [Enrollment](docs/domain/enrollment.md)
- [Guidance](docs/domain/guidance.md)
- [Journals](docs/domain/journals.md)
- [Assignments](docs/domain/assignments.md)
- [Reports](docs/domain/reports.md)
- [Assessment](docs/domain/assessment.md)
- [Certification](docs/domain/certification.md)
- [Incidents](docs/domain/incidents.md)
- [Settings](docs/domain/settings.md)
- [Administration](docs/domain/administration.md)

## License

MIT License. See [LICENSE](LICENSE) for details.

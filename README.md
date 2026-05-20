# Internara

**Self-hosted internship management system** for schools, vocational programs, and educational institutions. Manages the full internship lifecycle — from registration to final reporting.

## Features

- **For Schools** — manage departments, academic years, and company partnerships
- **For Students** — log attendance, journals, track competency progress, and submit assignments
- **For Teachers** — manage placements, assessments, supervision visits, and grading
- **For Supervisors** — evaluate students and provide industry feedback

## Architecture

Internara follows a **Domain-first, Action-based MVC** architecture. Business logic is organized by domain, not by technical layer.

```
app/Domain/
├── Admin/          System administration
├── Assessment/     Rubrics, competencies, grading
├── Assignment/     Tasks, submissions
├── Attendance/     Clock-in/out
├── Auth/           Authentication
├── Certificate/    Certificate management
├── Core/           Base classes, contracts
├── Document/       Templates, rendering
├── Evaluation/     Mentor evaluations
├── Guidance/       Handbooks
├── Incident/       Reporting
├── Internship/     Program management
├── Logbook/        Student journals
├── Mentee/         Student role
├── Mentor/         Supervision
├── Partnership/    Companies
├── Placement/      Slot assignment
├── Registration/   Applications
├── Schedule/       Events
├── School/         Institution
├── Settings/       Configuration
├── Setup/          Installation
├── Shared/         Cross-domain utilities
└── User/           Identity & profile
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

Follow the setup wizard to configure your school and admin account. Access the app at `http://localhost:8000`.

## Requirements

- PHP 8.4+ with extensions: bcmath, ctype, fileinfo, mbstring, openssl, pdo, tokenizer, xml, curl, gd, intl, zip
- Composer 2.x
- Node.js 20+ with npm
- Database: SQLite (default), MySQL 8+, MariaDB 10+, or PostgreSQL 15+

## Tech Stack

| Layer | Technology |
|-------|-----------|
| Framework | Laravel 13 |
| Frontend | Livewire 4, Alpine.js, Tailwind CSS v4, DaisyUI, maryUI |
| Database | SQLite / MySQL / MariaDB / PostgreSQL |
| Realtime | Laravel Reverb (WebSockets) |
| Queue | Database / Redis |
| Monitoring | Laravel Pulse |
| Testing | Pest 4 (Feature, Unit, Architecture) |
| Code Quality | Laravel Pint, PHPStan, Prettier |

## Documentation

### Core
- [Architecture](docs/en/architecture.md)
- [Coding Conventions](docs/en/conventions.md)
- [Database](docs/en/database.md)
- [Installation](docs/en/installation.md)
- [Configuration](docs/en/configuration.md)
- [Testing](docs/en/testing.md)
- [Infrastructure](docs/en/infrastructure.md)

### Security & Access
- [RBAC](docs/en/rbac.md)
- [Observability](docs/en/observability.md)

### Frontend & UI
- [UI/UX Design](docs/en/ui-ux.md)
- [Branding](docs/en/branding.md)

### Technical Reference
- [Routes](docs/en/routes.md)
- [Session](docs/en/session.md)
- [Cache](docs/en/cache.md)
- [Filesystem](docs/en/filesystem.md)
- [Notifications](docs/en/notification.md)
- [Known Issues](docs/en/known-issues.md)

### Domain Reference
- [Admin](docs/en/domain/admin.md)
- [Assessment](docs/en/domain/assessment.md)
- [Assignment](docs/en/domain/assignment.md)
- [Attendance](docs/en/domain/attendance.md)
- [Auth](docs/en/domain/auth.md)
- [Certificate](docs/en/domain/certificate.md)
- [Core](docs/en/domain/core.md)
- [Document](docs/en/domain/document.md)
- [Evaluation](docs/en/domain/evaluation.md)
- [Guidance](docs/en/domain/guidance.md)
- [Incident](docs/en/domain/incident.md)
- [Internship](docs/en/domain/internship.md)
- [Logbook](docs/en/domain/logbook.md)
- [Mentee](docs/en/domain/mentee.md)
- [Mentor](docs/en/domain/mentor.md)
- [Partnership](docs/en/domain/partnership.md)
- [Placement](docs/en/domain/placement.md)
- [Registration](docs/en/domain/registration.md)
- [Schedule](docs/en/domain/schedule.md)
- [School](docs/en/domain/school.md)
- [Settings](docs/en/domain/settings.md)
- [Setup](docs/en/domain/setup.md)
- [Shared](docs/en/domain/shared.md)
- [User](docs/en/domain/user.md)

## License

MIT License. See [LICENSE](LICENSE) for details.

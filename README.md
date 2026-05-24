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
- [Architecture](docs/architecture.md)
- [Coding Conventions](docs/conventions.md)
- [Database](docs/database.md)
- [Installation](docs/installation.md)
- [Configuration](docs/configuration.md)
- [Testing](docs/testing.md)
- [Infrastructure](docs/infrastructure.md)

### Security & Access
- [RBAC](docs/rbac.md)
- [Observability](docs/observability.md)

### Frontend & UI
- [UI/UX Design](docs/ui-ux.md)
- [Branding](docs/branding.md)

### Technical Reference
- [Routes](docs/routes.md)
- [Session](docs/session.md)
- [Cache](docs/cache.md)
- [Filesystem](docs/filesystem.md)
- [Notifications](docs/notification.md)
- [Known Issues](docs/known-issues.md)

### Domain Reference
- [Admin](docs/domain/admin.md)
- [Assessment](docs/domain/assessment.md)
- [Assignment](docs/domain/assignment.md)
- [Attendance](docs/domain/attendance.md)
- [Auth](docs/domain/auth.md)
- [Certificate](docs/domain/certificate.md)
- [Core](docs/domain/core.md)
- [Document](docs/domain/document.md)
- [Evaluation](docs/domain/evaluation.md)
- [Guidance](docs/domain/guidance.md)
- [Incident](docs/domain/incident.md)
- [Internship](docs/domain/internship.md)
- [Logbook](docs/domain/logbook.md)
- [Mentee](docs/domain/mentee.md)
- [Mentor](docs/domain/mentor.md)
- [Partnership](docs/domain/partnership.md)
- [Placement](docs/domain/placement.md)
- [Registration](docs/domain/registration.md)
- [Schedule](docs/domain/schedule.md)
- [School](docs/domain/school.md)
- [Settings](docs/domain/settings.md)
- [Setup](docs/domain/setup.md)
- [Shared](docs/domain/shared.md)
- [User](docs/domain/user.md)

## License

MIT License. See [LICENSE](LICENSE) for details.

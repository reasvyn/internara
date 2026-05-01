# Internara: Internship Management System

[![Laravel Version](https://img.shields.io/badge/Laravel-12.x-red.svg)](https://laravel.com)
[![PHP Version](https://img.shields.io/badge/PHP-8.4.x-blue.svg)](https://php.net)
[![License](https://img.shields.io/badge/license-MIT-green.svg)](LICENSE)

Internara is a self-hosted internship management system built for schools, vocational programs, and educational institutions. It manages the full internship lifecycle — from student registration and company placements to daily attendance, supervision visits, assessments, and final reporting.

## What It Does

- **For Schools** — Manage departments, academic years, internship programs, company partnerships, and student placements
- **For Students** — Register for internships, log daily attendance and journals, submit assignments, and track competency progress
- **For Teachers** — Create assignments, grade submissions, manage assessments, and monitor student competencies
- **For Mentors** — Log supervision visits, evaluate interns, and track assigned students

## Quick Start

### Prerequisites
- PHP 8.4+
- Node.js & PNPM (or npm)
- SQLite, MySQL, or PostgreSQL

### Install & Run
```bash
# 1. Clone and install dependencies
git clone https://github.com/reasvyn/internara.git
cd internara
composer install
pnpm install

# 2. Configure environment
cp .env.example .env
php artisan key:generate

# 3. Initialize database and start
php artisan setup:install
php artisan serve & pnpm dev
```

The setup wizard will guide you through school configuration, admin account creation, and first internship setup. After installation, access the application at `http://localhost:8000`.

## Features

### Core
- Role-based access control (SuperAdmin, Admin, Student, Teacher, Mentor)
- School, department, and academic year management
- Company partnerships and internship program configuration
- Student registration and placement (self-service or bulk admin assignment)
- Multi-language support (Indonesian & English, extensible to additional locales)
- Light and dark theme with system preference detection

### Daily Operations
- Clock in/out attendance with late threshold configuration
- Student journal entries with teacher/mentor verification
- Absence request workflow with approval chain
- Assignment creation, submission, and grading
- Competency tracking and skill progress monitoring

### Supervision & Assessment
- Mentor supervision visit logging
- Teacher assessment and grading with rubric support
- Internship evaluation and feedback
- Official document generation (certificates, reports, correspondence)

### Administration
- Async report generation with queued background jobs
- System health monitoring via Laravel Pulse
- Activity audit trail for all critical operations
- Database-backed settings — no code changes needed for configuration updates
- White-label branding (logo, favicon, color scheme configurable from admin panel)

## Tech Stack

| Layer | Technology |
|-------|------------|
| Backend | Laravel 12, PHP 8.4 |
| UI Components | maryUI (Blade), DaisyUI 5 |
| Interactivity | Livewire 3 (server-state), Alpine.js (instant client-side) |
| Styling | TailwindCSS 4, OKLCH color system |
| Database | SQLite / MySQL / PostgreSQL (UUID primary keys) |
| File Storage | Local or S3 (Spatie MediaLibrary) |
| Queue | Database (Redis-ready) |
| Monitoring | Laravel Pulse |

## Architecture Overview

Internara uses an **Action-Oriented MVC** pattern that separates business rules from application workflows:

```
HTTP Request → Form Request (validation)
             → Livewire/Controller (thin, delegates)
             → Action (stateless use case)
             → Model (business rules, UUID, relationships)
             → Response/View
```

- **Stateless Actions** — Each use case is a single `execute()` method, reusable across web, API, and CLI entry points
- **Rich Models** — Business rules live on the model (`canBeApproved()`, `isWithinRadius()`, etc.)
- **UUID Primary Keys** — All entities use UUIDs for global uniqueness and security
- **Event-Driven Side Effects** — Notifications, emails, and audit logs decoupled from core logic

See [Architecture](docs/architecture.md) for the full design documentation.

## Customization & Extension

Internara is designed to be adapted to any institution's needs:

- **Branding** — Change logo, favicon, colors, and site name from the admin settings panel — no code changes required
- **Languages** — Add new locales by creating translation files in `lang/` — the system automatically detects available languages
- **Settings** — All business rules (attendance thresholds, feature flags, academic year dates) are stored in the database and configurable from the UI
- **Themes** — Light/dark themes are built in; the DaisyUI token system in `resources/css/app.css` makes custom themes straightforward
- **Reports** — Document templates use Blade, making it easy to add custom report types
- **API** — The Action layer is API-ready — any Action can be called from a controller endpoint without modification

See [Configuration](docs/configuration.md) and [UI/UX](docs/ui-ux.md) for detailed customization guides.

## Documentation

| Document | For | Description |
|----------|-----|-------------|
| [Installation](docs/installation.md) | Everyone | CLI setup, web wizard, post-installation state |
| [Architecture](docs/architecture.md) | Developers | Layers, Actions, Models, extension points |
| [Configuration](docs/configuration.md) | Developers, Admins | Three-tier config system, settings, branding |
| [UI/UX](docs/ui-ux.md) | Developers, Designers | Theming, layouts, component patterns, interactions |
| [Database](docs/database.md) | Developers | Schema, migrations, models, factories |
| [RBAC](docs/rbac.md) | Developers, Admins | Roles, permissions, user lifecycle |
| [Testing Strategy](docs/testing.md) | Developers | Test types, coverage, running tests |
| [Engineering Standards](docs/standards.md) | Developers | 3S Doctrine, coding conventions, layer rules |
| [Filesystem](docs/filesystem.md) | Developers | Storage, Spatie MediaLibrary, S3 setup |
| [Notifications](docs/notification.md) | Developers | In-app notifications, email alerts |
| [Cache](docs/cache.md) | Developers | Cache drivers, invalidation patterns |
| [Session](docs/session.md) | Developers | Session management, security |
| [Logging & Monitoring](docs/logging.md) | Admins, Developers | Laravel Pulse, system observability |
| [System Audits](docs/audits.md) | Developers | Audit trail, forensic logging |
| [Known Issues](docs/known-issues.md) | Everyone | Active problems and resolution paths |

## Contributing

Contributions are welcome. Before submitting a pull request:

1. Read the [Engineering Standards](docs/standards.md) for coding conventions and architecture rules
2. Ensure all tests pass: `./vendor/bin/pest`
3. Run the CI/CD checks locally: code style (Pint), static analysis (PHPStan), architectural tests
4. Document any new features or changes to existing behavior in the relevant `docs/` file

## Author

- **Reas Vyn** — [github.com/reasvyn](https://github.com/reasvyn)

## License

MIT License. See [LICENSE](LICENSE) for details.

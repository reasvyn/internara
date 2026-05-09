# Internara: Internship Management System

Internara is a self-hosted internship management system built for schools, vocational programs, and educational institutions. It manages the full internship lifecycle — from registration to final reporting.

## What It Does

- **For Schools**: Manage departments, academic years, and company partnerships.
- **For Students**: Log attendance, journals, and track competency progress.
- **For Teachers & Mentors**: Manage placements, assessments, and supervision visits.

## Quick Start

### Prerequisites
- PHP 8.4+
- Node.js 20+
- SQLite, MySQL, or PostgreSQL

### Installation
```bash
git clone https://github.com/reasvyn/internara.git
cd internara
composer install && npm install
cp .env.example .env
php artisan key:generate
php artisan setup:install
```

Follow the setup wizard to configure your school and admin account. Access the application at `http://localhost:8000`.

## Architecture

Internara follows a **flat Action-Oriented MVC** architecture. Business logic is encapsulated in stateless **Actions** under `app/Actions/{Context}/`, business rules live in pure **Entities** under `app/Entities/{Context}/`, and persistence is handled by **Models** under `app/Models/`.

## Documentation

### Core
- [Architecture](docs/architecture.md)
- [Database](docs/database.md)
- [Installation](docs/installation.md)
- [Configuration](docs/configuration.md)
- [Testing](docs/testing.md)
- [Infrastructure](docs/infrastructure.md)

### Security & Access
- [RBAC & Lifecycle](docs/rbac.md)
- [Observability](docs/observability.md)

### Frontend & UI
- [UI/UX Design](docs/ui-ux.md)

### Technical Reference
- [Session](docs/session.md)
- [Cache](docs/cache.md)
- [Filesystem](docs/filesystem.md)
- [Notifications](docs/notification.md)
- [Known Issues](docs/known-issues.md)

## License
MIT License.
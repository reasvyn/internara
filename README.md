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

Internara follows a **Domain-Driven Structure** with an **Action-Oriented MVC** pattern. Business logic is encapsulated in stateless **Actions**, while business rules live in rich **Models**.

## Documentation

### Core
- [Architecture & Standards](docs/architecture.md)
- [Database](docs/database.md)
- [Installation](docs/installation.md)
- [Configuration](docs/configuration.md)
- [Testing](docs/testing.md)
- [Infrastructure & Dependencies](docs/infrastructure.md)

### Security & Access
- [RBAC & Lifecycle](docs/rbac.md)
- [Audits](docs/audits.md)
- [Logging & Observability](docs/logging.md)

### Frontend & UI
- [UI/UX Design](docs/ui-ux.md)

### Technical Reference
- [Session Management](docs/session.md)
- [Cache](docs/cache.md)
- [Filesystem](docs/filesystem.md)
- [Notifications](docs/notification.md)
- [Known Issues](docs/known-issues.md)

## License
MIT License.

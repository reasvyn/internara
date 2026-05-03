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

- [Architecture & Standards](docs/architecture.md)
- [Database](docs/database.md)
- [Installation](docs/installation.md)
- [Configuration](docs/configuration.md)
- [Testing](docs/testing.md)
- [RBAC & Lifecycle](docs/rbac.md)
- [UI/UX Design](docs/ui-ux.md)

## License
MIT License.

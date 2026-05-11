# Internara

Internara is a self-hosted internship management system for schools, vocational programs, and educational institutions. It manages the full internship lifecycle — from registration to final reporting.

- **For schools** — manage departments, academic years, and company partnerships
- **For students** — log attendance, journals, track competency progress, and submit assignments
- **For teachers** — manage placements, assessments, supervision visits, and grading
- **For supervisors** — evaluate students and provide industry feedback

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

## Documentation

### Core
- [Architecture](docs/architecture.md)
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

### Technical Reference
- [Session](docs/session.md)
- [Cache](docs/cache.md)
- [Filesystem](docs/filesystem.md)
- [Notifications](docs/notification.md)
- [Known Issues](docs/known-issues.md)

## License

MIT License.

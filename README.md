# Internara

> **Last updated:** 2026-06-24
> **Changes:** sync — file path fixes, update metadata consistent with documentation rules
>
> **Self-hosted, single-tenant vocational fieldwork management system** engineered for managing compulsory industrial fieldwork (PKL) programs at Indonesian vocational upper-secondary schools (SMA/SMK) and technical education institutions.

Internara manages the entire fieldwork (PKL - _Praktik Kerja Lapangan_) program lifecycle: from
initial student enrollment and slot-based company placement, to daily geofenced attendance,
reflective logs, competency assessments, final report revisions, and cryptographic certificate
issuance.

---

## Technical Architecture Overview

Internara is structured as an **Action-based MVC** application, grouping components by **business
module** (vertical slicing) rather than separating them into general technical layers. This
guarantees high domain cohesion and clean component encapsulation.

```
app/
├── Core/           Abstract base classes, contracts, middleware, and cross-module utilities
├── Auth/           Authentication, password management, account recovery, RBAC
├── User/           Identity, profiles, notifications, dashboards, account status
├── SysAdmin/       User management, announcements, audit logs, pulse monitoring
├── Setup/          One-time installation wizard, environment audit, super admin creation
├── Settings/       System configuration, dynamic branding, feature flags, locale
├── Academics/      School profile, departments, academic years
├── Program/        Internship lifecycle, phases, groups, document requirements
├── Enrollment/     Student registration, placement slots, change requests
├── Assessment/     Competency rubrics, evaluation, presentation scheduling
├── Evaluation/     Mentor feedback, program quality surveys
├── Assignment/     Task management, student submissions, grading workflow
├── Journals/       Logbook entries, attendance, absence requests, scheduling
├── Guidance/       Supervision logs, mentoring assignments
├── Incident/       Issue reporting, investigation, resolution
├── Partners/       Company profiles, partnership agreements
├── Certification/  Certificate templates, batch issuance, QR verification
├── Reports/        Final grade cards, score aggregation, coordinator sign-off
└── Document/       Document templates, handbooks, rendering pipeline
```

Each business module colocates its domain models, single-use Actions (Command/Process extend
`BaseAction`; Read actions extend `BaseReadAction`), policies (`BasePolicy`), and Livewire components,
keeping submodules isolated and highly maintainable.

---

## Tech Stack & Specifications

| Layer                   | Specifications                                              |
| ----------------------- | ----------------------------------------------------------- |
| **Language & Engine**   | PHP 8.4+, Node.js 20+                                       |
| **Framework**           | Laravel 13                                                  |
| **Frontend UI**         | Livewire 4, maryUI 2, DaisyUI 5, Tailwind CSS v4, Alpine.js |
| **Build System**        | Vite 8                                                      |
| **Database Support**    | SQLite (default), MySQL 8+, MariaDB 10+, PostgreSQL 15+     |
| **Background Services** | Redis / Database Queues, Scheduler Cron                     |
| **WebSockets**          | Laravel Reverb (optional — install separately)              |
| **Observability**       | Laravel Pulse, dual-channel SmartLogger                     |
| **Quality & Styling**   | Laravel Pint, PHPStan, Pest 4                               |

---

## Prerequisites

- **PHP 8.4+** with extensions: `bcmath`, `ctype`, `curl`, `fileinfo`, `gd`, `intl`, `mbstring`,
  `openssl`, `pdo`, `tokenizer`, `xml`, `zip`
- **Composer 2.x**
- **Node.js 20+** with npm

---

## Quick Start (Development Environment)

1. **Clone & Install Dependencies**:

    ```bash
    git clone https://github.com/reasvyn/internara.git
    cd internara
    composer install
    npm install
    ```

2. **Configure Environment**:

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

3. **Install System (Audits & Provisioning)**:

    ```bash
    php artisan setup:install
    ```

    _This command audits your environment, runs migrations, seeds defaults, and outputs a one-time
    signed setup URL._

4. **Start Dev Processes**: Run the dev server and queue workers in separate shells, or run:

    ```bash
    composer run dev
    ```

5. **Complete Setup**: Open the signed setup URL in your browser to complete the 6-step setup
   wizard.

---

## Documentation Index

Explore the complete system guides under `docs/`:

### Vision & Concepts

- [Product Definition & Scope](docs/foundation/product-definition.md)
- [Key Feature Catalog](docs/key-features.md)
- [Project Philosophy](docs/philosophy.md)
- [Action-based MVC Architecture](docs/architecture.md)
- [Coding Conventions](docs/conventions.md)

### Setup & Operation

- [Getting Started Guide](docs/getting-started.md)
- [Setup Wizard Walkthrough](docs/guide/02-setup-wizard.md)
- [Post-Setup Administrative Tasks](docs/guide/03-post-setup.md)
- [Infrastructure Overview](docs/infrastructure/infrastructure.md)
- [Installation Reference](docs/guide/01-installation.md)
- [Deployment Options](docs/infrastructure/deployment.md)
- [Configuration Settings](docs/infrastructure/configuration.md)

### Security & Access

- [Role-Based Access Control](docs/foundation/rbac.md)
- [Account Recovery Flows](docs/foundation/account-recovery.md)
- [Observability & Monitoring](docs/infrastructure/observability.md)
- [Backup & Disaster Recovery](docs/infrastructure/backup-recovery.md)

### Technical Reference

- [Database Schema](docs/infrastructure/database.md)
- [Cache Strategy & Invalidation](docs/infrastructure/cache.md)
- [Filesystem & Media Storage](docs/infrastructure/filesystem.md)
- [Media Library](docs/infrastructure/media-library.md)
- [Routes & Middleware](docs/infrastructure/routes.md)
- [Session Configuration](docs/infrastructure/session.md)
- [Notifications](docs/infrastructure/notification.md)
- [Queue & Workers](docs/infrastructure/queue.md)
- [Testing Guide](docs/infrastructure/testing.md)
- [Scaling Guide](docs/infrastructure/scaling.md)
- [Localization](docs/infrastructure/localization.md)

### Module References

- [Core](docs/modules/core.md) / [Reference](docs/modules/core-reference.md)
- [Auth](docs/modules/auth.md) / [Reference](docs/modules/auth-reference.md)
- [User](docs/modules/user.md) / [Reference](docs/modules/user-reference.md)
- [SysAdmin](docs/modules/sysadmin.md) / [Reference](docs/modules/sysadmin-reference.md)
- [Setup](docs/modules/setup.md) / [Reference](docs/modules/setup-reference.md)
- [Settings](docs/modules/settings.md) / [Reference](docs/modules/settings-reference.md)
- [Academics](docs/modules/academics.md) / [Reference](docs/modules/academics-reference.md)
- [Program](docs/modules/program.md) / [Reference](docs/modules/program-reference.md)
- [Enrollment](docs/modules/enrollment.md) / [Reference](docs/modules/enrollment-reference.md)
- [Assessment](docs/modules/assessment.md) / [Reference](docs/modules/assessment-reference.md)
- [Evaluation](docs/modules/evaluation.md) / [Reference](docs/modules/evaluation-reference.md)
- [Assignment](docs/modules/assignment.md) / [Reference](docs/modules/assignment-reference.md)
- [Journals](docs/modules/journals.md) / [Reference](docs/modules/journals-reference.md)
- [Guidance](docs/modules/guidance.md) / [Reference](docs/modules/guidance-reference.md)
- [Incident](docs/modules/incident.md) / [Reference](docs/modules/incident-reference.md)
- [Partners](docs/modules/partners.md) / [Reference](docs/modules/partners-reference.md)
- [Certification](docs/modules/certification.md) / [Reference](docs/modules/certification-reference.md)
- [Reports](docs/modules/reports.md) / [Reference](docs/modules/reports-reference.md)
- [Document](docs/modules/document.md) / [Reference](docs/modules/document-reference.md)

---

## License

MIT License. See [LICENSE](LICENSE) for details.

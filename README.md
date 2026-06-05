# Internara

> **Self-hosted, single-tenant vocational fieldwork management system** designed for schools and educational institutions running compulsory industrial work placement programs.

Internara manages the entire fieldwork (PKL - *Praktik Kerja Lapangan*) program lifecycle: from initial student enrollment and slot-based company placement, to daily geofenced attendance, reflective logs, competency assessments, final report revisions, and cryptographic certificate issuance.

---

## Technical Architecture Overview

Internara is structured as an **Action-based MVC** application, grouping components by **business module** (vertical slicing) rather than separating them into general technical layers. This guarantees high domain cohesion and clean component encapsulation.

```
app/
├── Core/           Abstract base classes, interfaces, and request infrastructure
├── Shared/         Cross-cutting utilities, DTOs, enums, global UI switchers
├── User/           Identity, profile management, and account recovery
├── SysAdmin/       System installation, global settings, pulse metrics, audit logs
├── Academics/      School records, department lists, academic cohorts
├── Program/        Internship cycles, phases, document requirements
├── Enrollment/     Student registrations, active placements, and slot quotas
├── Assessment/     Competency rubrics, student evaluations, presentation exams
├── Evaluation/     Mentor effectiveness and company quality feedback loops
├── Assignment/     Tasks management, student submissions, and grading workflow
├── Journals/       Geotagged attendance, absence logging, daily logbooks
├── Guidance/       Supervision records and training handbooks
├── Incident/       On-site student issue reporting and investigations
├── Partners/       Corporate contacts, agreement agreements, and MoUs
├── Certification/  Cryptographic certificate compilation and QR-verification
├── Reports/        Student final reports compilation and advisor review
└── Document/       Official PDF templates compilation
```

Each business module colocates its domain models, single-use actions (`BaseAction`), policies (`BasePolicy`), and Livewire components, keeping submodules isolated and highly maintainable.

---

## Tech Stack & Specifications

| Layer | Specifications |
|---|---|
| **Language & Engine** | PHP 8.4+, Node.js 20+ |
| **Framework** | Laravel 13 |
| **Frontend UI** | Livewire 4, maryUI 2, DaisyUI 5, Tailwind CSS v4, Alpine.js |
| **Build System** | Vite 8 |
| **Database Support** | SQLite (default), MySQL 8+, MariaDB 10+, PostgreSQL 15+ |
| **Background Services** | Redis / Database Queues, Scheduler Cron |
| **WebSockets** | Laravel Reverb |
| **Observability** | Laravel Pulse, dual-channel SmartLogger |
| **Quality & Styling** | Laravel Pint, PHPStan, Pest 4 |

---

## Prerequisites

- **PHP 8.4+** with extensions: `bcmath`, `ctype`, `curl`, `fileinfo`, `gd`, `intl`, `mbstring`, `openssl`, `pdo`, `tokenizer`, `xml`, `zip`
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
   *This command audits your environment, runs migrations, seeds defaults, and outputs a one-time signed setup URL.*

4. **Start Dev Processes**:
   Run the dev server and queue workers in separate shells, or run:
   ```bash
   composer run dev
   ```

5. **Complete Setup**:
   Open the signed setup URL in your browser to complete the 7-step setup wizard.

---

## Documentation Index

Explore the complete system guides under `docs/`:

### Vision & Concepts
- [Product Definition & Scope](docs/product-definition.md)
- [Key Feature Catalog](docs/key-features.md)
- [Action-based MVC Architecture](docs/architecture.md)
- [Coding Conventions](docs/conventions.md)

### Deployment & Operation
- [Getting Started Guide](docs/getting-started.md)
- [Installation Reference](docs/infrastructure/installation.md)
- [Deployment Options](docs/infrastructure/deployment.md)
- [Configuration Settings](docs/infrastructure/configuration.md)
- [Post-Setup Administative Tasks](docs/post-setup.md)
- [Setup Wizard Walkthrough](docs/setup-wizard.md)
- [Infrastructure Operations](docs/infrastructure/infrastructure.md)
- [Scaling Guide](docs/infrastructure/scaling.md)

### Systems & Security
- [Role-Based Access Control (RBAC)](docs/rbac.md)
- [Observability & Monitoring](docs/infrastructure/observability.md)
- [Account Recovery Flows](docs/account-recovery.md)
- [Backup & Disaster Recovery](docs/infrastructure/backup-recovery.md)
- [Database Schema & Engine Rules](docs/infrastructure/database.md)
- [Cache Strategy & Invalidation](docs/infrastructure/cache.md)
- [Filesystem & Media Storage](docs/infrastructure/filesystem.md)

### Module References
- [Core Foundations Reference](docs/modules/core.md)
- [Shared Utilities Reference](docs/modules/shared.md)
- [User Auth Reference](docs/modules/user.md)
- [SysAdmin Panel Reference](docs/modules/sysadmin.md)
- [Academic Structure Reference](docs/modules/academics.md)
- [Internship Programs Reference](docs/modules/program.md)
- [Student Placements Reference](docs/modules/enrollment.md)

---

## License

MIT License. See [LICENSE](LICENSE) for details.

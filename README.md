# Internara: Practical Work Management Information System

[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![Version](https://img.shields.io/badge/version-0.14.0-blue.svg)](CHANGELOG.md)
[![PHP](https://img.shields.io/badge/PHP-8.4-777bb4.svg)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-12-ff2d20.svg)](https://laravel.com/)

**Internara** is an enterprise-grade, human-centered Practical Work Management Information System (SIM-PKL) built with a **Domain-Aligned Modular Monolith** architecture. It orchestrates the entire internship lifecycle — from student registration and placement to daily monitoring, assessment, and reporting — for educational institutions and industry partners.

---

## ⚡ Quick Start

> **Prerequisites**: PHP 8.4, Composer 2.x, Node.js 20+

```bash
git clone https://github.com/reasnov/internara.git
cd internara
composer setup   # installs deps, generates key, migrates DB, builds assets
composer dev     # starts server, queue worker, log watcher, and Vite
```

Visit **http://localhost:8000** — the Setup Wizard will guide you through the rest.

---

## 🛠 Local Development

| Command | Description |
| :--- | :--- |
| `composer dev` | Starts all dev processes (server + queue + logs + Vite) |
| `composer test` | Runs the full Pest test suite |
| `composer lint` | Checks code style (Pint + Prettier) — no writes |
| `composer format` | Auto-formats code (Pint + Prettier) |
| `php artisan migrate:fresh` | Drops and re-runs all migrations |
| `php artisan db:seed` | Seeds reference data |
| `php artisan module:make {Name}` | Scaffolds a new domain module |

---

## 🏗 Architectural Doctrine: The 3S Principles

Internara is governed by the **3S Doctrine**:

1. **SECURE (S1)**: Rigorous RBAC, UUID identity, and PII encryption by default (ISO/IEC 27001).
2. **SUSTAINABLE (S2)**: Strict typing, modular isolation, and comprehensive TDD (ISO/IEC 25010).
3. **SCALABLE (S3)**: Contract-first inter-module communication and event-driven orchestration.

### Layer Overview

```
Browser / HTTP
    └── Livewire Components  (UI state, no business logic)
            └── Service Contracts  (interface boundary)
                    └── EloquentQuery / BaseService  (business rules)
                            └── Eloquent Models  (persistence)
```

All cross-module communication flows exclusively through **Service Contracts (interfaces)**. Physical foreign keys across module boundaries are forbidden.

---

## 🛠 Tech Stack

| Layer | Technology |
| :--- | :--- |
| Runtime | PHP 8.4 (strict types), Laravel 12 |
| UI | Livewire 3, Tailwind CSS 4, Mary UI |
| Database | SQLite (dev) / MySQL / PostgreSQL (production) |
| Cache / Queue | Database (dev) → Redis (production) |
| Testing | Pest 4 (TDD-first) |
| Standards | ISO/IEC 25010, 29148, 29119, 42010 |

---

## 📦 Module Ecosystem

| Module | Responsibility |
| :--- | :--- |
| **Core** | System bootstrap, academic scoping, app metadata |
| **Auth** | Authentication, session management |
| **User** | Unified identity, account lifecycle |
| **Profile** | National & institutional identifiers (NIS, NISN, NIP) |
| **Permission** | RBAC policies, roles, and access control |
| **School** | Institutional identity and branding |
| **Department** | Specialization/program structure |
| **Internship** | Programs, placements, registrations, requirements |
| **Registration** | Student enrollment and placement matching |
| **Student** | Student-specific data and dashboard |
| **Teacher** | Academic supervisor management |
| **Mentor** | Industry supervisor management |
| **Admin** | Administrative account management |
| **Journal** | Daily vocational activity logs |
| **Attendance** | Presence tracking and absence requests |
| **Assessment** | Rubric-based evaluation and competency scoring |
| **Assignment** | Task management and submission tracking |
| **Guidance** | Digital handbook and acknowledgements |
| **Schedule** | Supervision and visit scheduling |
| **Notification** | System-wide notification delivery |
| **Report** | Authoritative document synthesis (PDF/Excel) |
| **Media** | File and media management |
| **Log** | Audit log and activity tracking |
| **Status** | Auditable polymorphic state transitions |
| **Setting** | Global and modular configuration |
| **Setup** | Installation wizard |
| **Exception** | Unified exception types and handling |
| **Shared** | Cross-cutting base classes (`EloquentQuery`, `BaseService`) |
| **Support** | Stateless utility helpers |
| **UI** | Shared Livewire base classes (`RecordManager`), layout components |

---

## 📚 Documentation

| Document | Description |
| :--- | :--- |
| [System Architecture](docs/system-architecture.md) | Modular monolith design and layer governance |
| [Engineering Standards](docs/engineering-standards.md) | Coding conventions, naming, and patterns |
| [Module Construction Guide](docs/modular-construction-guide.md) | Step-by-step guide for building new modules |
| [Software Requirements](docs/software-requirements.md) | Authoritative SyRS (ISO/IEC 29148) |
| [Deployment Guide](docs/deployment-guide.md) | Production environment orchestration |
| [Test Strategy](docs/test-strategy.md) | Quality gates and verification models |
| [Performance Optimization](docs/advanced/performance-optimization.md) | Query tuning, caching patterns |
| [Wiki](docs/wiki/overview.md) | End-user and administrator guides |

---

## 🤝 Contributing

1. Fork → branch (`feature/module/description`) → implement → test → PR.
2. Every PHP file **must** declare `declare(strict_types=1);`.
3. All user-facing strings must use `__('module::file.key')` — no hard-coded text.
4. Run `composer test && composer lint` before pushing.

See **[CONTRIBUTING.md](CONTRIBUTING.md)** for the full workflow.

---

## ⚖️ License

Internara is open-source software licensed under the **[MIT License](LICENSE)**.


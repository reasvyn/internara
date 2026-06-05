# Documentation Index

> Last updated: 2026-06-03
> Changes: Aligned module tables with the restructured 16-module model

Complete catalog of all documentation files in the `docs/` directory.

---

## Product & Vision

| Document | Description |
|---|---|
| [Product Definition](product-definition.md) | What Internara is, core principles, user personas, scope, deployment model, localization, licensing |
| [Key Features](key-features.md) | Complete feature inventory across all 16 modules, organized by program lifecycle |
| [Architecture](architecture.md) | 12-layer architecture, Action Triad (Command/Read/Process), cross-module rules, exception hierarchy, caching, validation, testing strategy |
| [Coding Conventions](conventions.md) | Mandatory base classes, naming conventions, file structure, PHP rules, policy/enum conventions |

---

## Setup & Operation

| Document | Description |
|---|---|
| [Getting Started](getting-started.md) | End-to-end walkthrough from cloning to completing the setup wizard |
| [Installation](installation.md) | Detailed deployment reference: prerequisites, VPS/Docker/shared hosting, PHP-FPM, Supervisor, database config, troubleshooting |
| [Deployment](deployment.md) | Three deployment paths (VPS, Docker, shared hosting), production checklist, background processes |
| [Configuration](configuration.md) | Three-tier config system, environment variables, dev vs production, security settings, localization setup |
| [Post-Setup](post-setup.md) | First actions as administrator: foundation, people, program setup, placements, going live checklist |
| [Setup Wizard](setup-wizard.md) | Detailed walkthrough of all wizard steps |
| [Infrastructure](infrastructure.md) | Deployment options overview, background processes, database and storage considerations |

---

## Security & Access

| Document | Description |
|---|---|
| [RBAC](rbac.md) | Authentication flow, flat role hierarchy, functional roles, permission model, Gate::before bypass, CheckRoleMiddleware |
| [Observability](observability.md) | Monitoring categories, Laravel Pulse, SmartLogger dual-channel, log channels, health checks |
| [Account Recovery](account-recovery.md) | Account recovery flow, recovery codes, administrative recovery slip |

---

## Frontend & UI

| Document | Description |
|---|---|
| [UI/UX Design](ui-ux.md) | Design system (Tailwind + DaisyUI + maryUI), layouts, dark mode, responsive strategy, SPA navigation |
| [Branding](branding.md) | Dynamic theming, color system, presets, logo management, font strategy |

---

## Technical Reference

| Document | Description |
|---|---|
| [Database](database.md) | Database design philosophy, UUID PKs, SQLite default, engine comparison, index strategy, schema organization |
| [Cache](cache.md) | Caching strategy, invalidation, key naming, Redis configuration, OpCache, optimization commands |
| [Filesystem](filesystem.md) | Storage architecture, media library integration, file locations, image conversions, S3-compatible cloud storage |
| [Media Library](media-library.md) | Spatie Media Library: collections, conversions, file size limits, queue integration, S3 providers |
| [Routes](routes.md) | Route structure, module-split organization, middleware groups |
| [Session](session.md) | Session configuration, drivers, security considerations |
| [Notifications](notification.md) | Multi-channel notification system, CustomDatabaseChannel, mail deliverability, SPF/DKIM/DMARC, broadcast |
| [Queue](queue.md) | Queue drivers, worker management, Supervisor, job lifecycle, retry/backoff, scaling |
| [Testing](testing.md) | Testing philosophy, feature vs unit test distinction, LazilyRefreshDatabase, entity testing, code coverage |
| [Scaling Guide](scaling.md) | When and how to scale from MVP to 2000 users, tier transitions, load testing, monitoring thresholds |
| [Known Issues](known-issues.md) | Known limitations, caveats, and workarounds |
| [Backup & Recovery](backup-recovery.md) | Backup strategies, database dumps, file backup, restoration steps, point-in-time recovery, monitoring |
| [Localization](localization.md) | Supported languages, translation structure, locale resolution, community contribution guide for adding new languages |

---

## Module Reference

Each module has two documents under `docs/modules/`:
- **Overview** (`docs/modules/{module}.md`) — purpose, boundary, features, lifecycle
- **Reference** (`docs/modules/{module}-reference.md`) — Actions, Models, Enums, Entities, Policies, Livewire components, Routes, Views, Tests, Factories, and Migrations

See the [Module Index](modules/module-index.md) for more details on operational flow.

| # | Module | Overview | Reference |
|---|---|---|---|---|
| 1 | **Core** | [Overview](modules/core.md) | [Reference](modules/core-reference.md) |
| 2 | **User** | [Overview](modules/user.md) | [Reference](modules/user-reference.md) |
| 3 | **SysAdmin** | [Overview](modules/sysadmin.md) | [Reference](modules/sysadmin-reference.md) |
| 4 | **Academics** | [Overview](modules/academics.md) | [Reference](modules/academics-reference.md) |
| 5 | **Program** | [Overview](modules/program.md) | [Reference](modules/program-reference.md) |
| 6 | **Enrollment** | [Overview](modules/enrollment.md) | [Reference](modules/enrollment-reference.md) |
| 7 | **Assessment** | [Overview](modules/assessment.md) | [Reference](modules/assessment-reference.md) |
| 8 | **Evaluation** | [Overview](modules/evaluation.md) | [Reference](modules/evaluation-reference.md) |
| 9 | **Assignment** | [Overview](modules/assignment.md) | [Reference](modules/assignment-reference.md) |
| 10 | **Journals** | [Overview](modules/journals.md) | [Reference](modules/journals-reference.md) |
| 11 | **Guidance** | [Overview](modules/guidance.md) | [Reference](modules/guidance-reference.md) |
| 12 | **Incident** | [Overview](modules/incident.md) | [Reference](modules/incident-reference.md) |
| 13 | **Partners** | [Overview](modules/partners.md) | [Reference](modules/partners-reference.md) |
| 14 | **Certification** | [Overview](modules/certification.md) | [Reference](modules/certification-reference.md) |
| 15 | **Reports** | [Overview](modules/reports.md) | [Reference](modules/reports-reference.md) |
| 16 | **Document** | [Overview](modules/document.md) | [Reference](modules/document-reference.md) |

---

## Architecture Decision Records (ADR)

### Foundation

| Record | Decision |
|---|---|
| [UUID Primary Keys](adr/adr-uuid-primary-keys.md) | UUID primary keys over auto-increment |
| [Module-First Architecture](adr/adr-module-first-architecture.md) | Module-first over flat layering |
| [Action Pattern over Services](adr/adr-action-pattern-over-services.md) | Action pattern with Command/Read/Process triad |
| [Entity-Model Separation](adr/adr-entity-model-separation.md) | Entity/Model separation for business rules |

### Behavior

| Record | Decision |
|---|---|
| [SmartLogger Dual-Channel](adr/adr-smartlogger-dual-channel.md) | Fluent dual-channel logger with PII masking |

### Quality

| Record | Decision |
|---|---|
| [Base Class Mandate](adr/adr-base-class-mandate.md) | Mandatory base classes from Core module |
| [Exception Hierarchy](adr/adr-exception-hierarchy.md) | Dual exception hierarchy (AppException + DomainException) |
| [Flat RBAC with Functional Roles](adr/adr-flat-rbac-with-functional-roles.md) | Flat RBAC with derived functional roles |

### Governing Decisions

| Record | Decision |
|---|---|
| [Self-Hosted Single-Tenant](adr/adr-self-hosted-single-tenant.md) | Self-hosted, single-tenant deployment model |
| [Cross-Module Communication](adr/adr-cross-module-communication.md) | 4-pattern communication discipline (contracts, events, delegation, forbidden) |
| [Gradual Migration](adr/adr-gradual-migration.md) | Optional complexity with phased migration paths |
| [Program Closure & Archival](adr/adr-program-closure-archival.md) | Hard archive with immutable data snapshot |

---

## Reading Order

New to Internara? Follow this sequence:

```
product-definition.md       → What is this?
  ↓
key-features.md             → What can it do?
  ↓
getting-started.md          → How do I run it?
  ↓
architecture.md             → How is it built?
  ↓
conventions.md              → How do I write code?
  ↓
modules/{module}.md          → Specific module I'm working on
```

For deployment:

```
deployment.md               → Choose your path
  ↓
installation.md             → Detailed setup
  ↓
configuration.md            → Configure environment
  ↓
backup-recovery.md          → Protect your data
```

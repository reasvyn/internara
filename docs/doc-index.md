# Documentation Index
> Last updated: 2026-05-27
> Changes: docs: comprehensive infrastructure, architecture, and conventions overhaul


Complete catalog of all documentation files in the `docs/` directory.

---

## Product & Vision

| Document | Description |
|---|---|
| [Product Definition](product-definition.md) | What Internara is, core principles, user personas, scope, deployment model, localization, licensing |
| [Key Features](key-features.md) | Complete feature inventory across all 24 domains, organized by program lifecycle |
| [Architecture](architecture.md) | 12-layer architecture, Action Triad (Command/Read/Process), cross-domain rules, exception hierarchy, caching, validation, testing strategy |
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
| [Setup Wizard](setup-wizard.md) | Detailed walkthrough of all 7 wizard steps |
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
| [Routes](routes.md) | Route structure, domain-split organization, middleware groups |
| [Session](session.md) | Session configuration, drivers, security considerations |
| [Notifications](notification.md) | Multi-channel notification system, CustomDatabaseChannel, mail deliverability, SPF/DKIM/DMARC, broadcast |
| [Queue](queue.md) | Queue drivers, worker management, Supervisor, job lifecycle, retry/backoff, scaling |
| [Testing](testing.md) | Testing philosophy, feature vs unit test distinction, LazilyRefreshDatabase, entity testing, code coverage |
| [Known Issues](known-issues.md) | Known limitations, caveats, and workarounds |
| [Backup & Recovery](backup-recovery.md) | Backup strategies, database dumps, file backup, restoration steps, point-in-time recovery, monitoring |
| [Localization](localization.md) | Supported languages, translation structure, locale resolution, community contribution guide for adding new languages |

---

## Domain Reference

Each domain has two documents:
- **Overview** (`docs/domain/{domain}.md`) — purpose, boundary, features, lifecycle
- **Reference** (`docs/domain/{domain}-reference.md`) — Actions, Models, Enums, Entities, Policies, Livewire components

### Foundation

| Domain | Overview | Reference |
|---|---|---|
| **Core** | [Overview](domain/core.md) | [Reference](domain/core-reference.md) |
| **Shared** | [Overview](domain/shared.md) | [Reference](domain/shared-reference.md) |

### Identity & Access

| Domain | Overview | Reference |
|---|---|---|
| **Auth** | [Overview](domain/auth.md) | [Reference](domain/auth-reference.md) |
| **User** | [Overview](domain/user.md) | [Reference](domain/user-reference.md) |

### Institution

| Domain | Overview | Reference |
|---|---|---|
| **School** | [Overview](domain/school.md) | [Reference](domain/school-reference.md) |
| **Settings** | [Overview](domain/settings.md) | [Reference](domain/settings-reference.md) |
| **Setup** | [Overview](domain/setup.md) | [Reference](domain/setup-reference.md) |

### Internship Lifecycle

| Domain | Overview | Reference |
|---|---|---|
| **Partnership** | [Overview](domain/partnership.md) | [Reference](domain/partnership-reference.md) |
| **Placement** | [Overview](domain/placement.md) | [Reference](domain/placement-reference.md) |
| **Registration** | [Overview](domain/registration.md) | [Reference](domain/registration-reference.md) |
| **Mentee** | [Overview](domain/mentee.md) | [Reference](domain/mentee-reference.md) |
| **Internship** | [Overview](domain/internship.md) | [Reference](domain/internship-reference.md) |

### Execution

| Domain | Overview | Reference |
|---|---|---|
| **Mentor** | [Overview](domain/mentor.md) | [Reference](domain/mentor-reference.md) |
| **Attendance** | [Overview](domain/attendance.md) | [Reference](domain/attendance-reference.md) |
| **Logbook** | [Overview](domain/logbook.md) | [Reference](domain/logbook-reference.md) |
| **Schedule** | [Overview](domain/schedule.md) | [Reference](domain/schedule-reference.md) |
| **Assignment** | [Overview](domain/assignment.md) | [Reference](domain/assignment-reference.md) |
| **Guidance** | [Overview](domain/guidance.md) | [Reference](domain/guidance-reference.md) |
| **Incident** | [Overview](domain/incident.md) | [Reference](domain/incident-reference.md) |

### Evaluation & Completion

| Domain | Overview | Reference |
|---|---|---|
| **Assessment** | [Overview](domain/assessment.md) | [Reference](domain/assessment-reference.md) |
| **Evaluation** | [Overview](domain/evaluation.md) | [Reference](domain/evaluation-reference.md) |
| **Document** | [Overview](domain/document.md) | [Reference](domain/document-reference.md) |
| **Certificate** | [Overview](domain/certificate.md) | [Reference](domain/certificate-reference.md) |

### Administration

| Domain | Overview | Reference |
|---|---|---|
| **Admin** | [Overview](domain/admin.md) | [Reference](domain/admin-reference.md) |

---

## Architecture Decision Records (ADR)

### Foundation

| Record | Decision |
|---|---|
| [UUID Primary Keys](adr/adr-uuid-primary-keys.md) | UUID primary keys over auto-increment |
| [Domain-First Architecture](adr/adr-domain-first-architecture.md) | Domain-first over flat layering |
| [Action Pattern over Services](adr/adr-action-pattern-over-services.md) | Action pattern with Command/Read/Process triad |
| [Entity-Model Separation](adr/adr-entity-model-separation.md) | Entity/Model separation for business rules |

### Behavior

| Record | Decision |
|---|---|
| [SmartLogger Dual-Channel](adr/adr-smartlogger-dual-channel.md) | Fluent dual-channel logger with PII masking |

### Quality

| Record | Decision |
|---|---|
| [Base Class Mandate](adr/adr-base-class-mandate.md) | Mandatory base classes from Core domain |
| [Exception Hierarchy](adr/adr-exception-hierarchy.md) | Dual exception hierarchy (AppException + DomainException) |
| [Flat RBAC with Functional Roles](adr/adr-flat-rbac-with-functional-roles.md) | Flat RBAC with derived functional roles |

### Governing Decisions

| Record | Decision |
|---|---|
| [Self-Hosted Single-Tenant](adr/adr-self-hosted-single-tenant.md) | Self-hosted, single-tenant deployment model |
| [Cross-Domain Communication](adr/adr-cross-domain-communication.md) | 4-pattern communication discipline (contracts, events, delegation, forbidden) |
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
domain/{domain}.md          → Specific domain I'm working on
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

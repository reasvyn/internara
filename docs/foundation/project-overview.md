# Project Overview

> **Last updated:** 2026-07-11 **Changes:** rewrite — qualitative dashboard, remove stale-prone numbers

---

## What Is Internara

Self-hosted, single-tenant internship management system (PKL) for Indonesian SMA/SMK schools.
MIT license. Built with Laravel 13, PHP 8.4, Livewire 4, Pest 4.

Handles the full internship lifecycle: school setup, student enrollment, company partnerships,
placement, daily attendance/logbook, supervisor guidance, assessment, evaluation, and final
reporting.

---

## Where We Are

**Phase: v0.2.0 — Polish & Test Coverage** (in progress)

The foundation is complete. All 22 modules exist with their full stack: models, actions,
livewire components, events, policies, routes, and translations. The architecture is sound —
4-layer model, Action Triad, Entity boundaries, DTO contracts.

The current phase focuses on three things:

1. **Stabilizing broken modules** — several modules have runtime errors from schema mismatches,
   missing relationships, or incorrect method calls. These are tracked as GitHub Issues with
   severity labels.

2. **Improving test coverage** — the test suite passes at ~98% but coverage is uneven. Core
   modules (User, Settings, Setup, Academics) are well-tested. Domain modules (Assessment,
   Evaluation, Certification, Document) need more tests.

3. **UI/UX polish** — guides, form icons, dark mode consistency, responsive layouts.

---

## Module Landscape

### Production-Ready

These modules are stable, well-tested, and have complete documentation:

- **Core** — base classes, contracts, exceptions, shared infrastructure
- **Auth** — login, RBAC, password recovery, super admin
- **User** — profiles, notifications, dashboards
- **Settings** — system configuration, branding, themes
- **Setup** — installation wizard, environment audit
- **SysAdmin** — user CRUD, announcements, audit trail
- **Academics** — schools, departments, academic years

### Stable But Needs Attention

These modules work but have known issues (tracked in GitHub Issues):

- **Program** — internship lifecycle, groups. Dead DTOs, documentation drift.
- **Partners** — companies, partnerships. Event dispatch violations, service locator usage.
- **Enrollment** — registration, placement. Broken Blade template, DTO gaps.
- **Journals** — attendance, logbook. Wrong user_id in attendance creation, undefined method.
- **Guidance** — supervisor mentoring. Missing computed property, DTO gaps.
- **Incident** — incident reporting. ActionResponse and DTO non-compliance.
- **Assignment** — thesis/supervisor assignment. Runtime crash, ActionResponse gaps.
- **Reports** — grade cards. ActionResponse gaps, dead code. Being purified (removing thesis concepts).

### Needs Work

These modules have significant structural issues:

- **Assessment** — Blade array errors, multiple root elements, broken relationships. Multiple P0 issues.
- **Certification** — schema mismatches, missing columns in migration. Runtime errors.
- **Document** — non-existent columns referenced everywhere. SQL errors, undefined properties.

### Skeleton

- **Evaluation** — models only, zero Actions/Entities/Livewire/Routes. Structurally incomplete.

### Infrastructure (No Business Logic)

- **Console** — artisan commands
- **Jobs** — queued jobs
- **Providers** — service providers

---

## Architecture at a Glance

```
Presentation (Layer 4)    Livewire → Blade → Alpine.js
         ↓
Business Ops (Layer 3)    Command/Read/Process Actions → Events
         ↓
Data (Layer 2)            Models ← Entities ← DTOs ← Enums
         ↓
Infrastructure (Layer 1)  Core base classes, Services, Support
```

Every mutation follows: **Livewire → Action → Entity → Model → DB**.
Business rules live in Entities. Actions orchestrate. Models persist.

---

## Technical Debt

The main categories of debt, in priority order:

1. **Schema mismatches** — migrations don't match code references (Document, Certification).
   Causes runtime crashes.

2. **ActionResponse gaps** — many Actions return `Model` or `void` instead of `ActionResponse`.
   Violates the Action Triad contract (C7).

3. **Hardcoded strings** — user-facing text not wrapped in `__()` translation helper.
   Blocks localization.

4. **Missing Entity layer** — some modules skip Entities entirely, putting business rules
   in Actions or Models.

5. **Event dispatch violations** — using `event()` inside transactions instead of
   `$this->dispatchEvent()`. Can cause race conditions.

6. **Dead code** — unused DTOs, unregistered observers, events without listeners.

---

## What's Next

See [roadmap.md](roadmap.md) for the full release timeline.

Current priorities:
- Fix P0 runtime errors in broken modules
- Complete the Reports module purification
- Improve test coverage for domain modules
- Documentation sync across all modules

---

## For Developers

### Getting Started

1. Read `docs/architecture.md` for the 4-layer model and Action Triad
2. Read `docs/conventions.md` for coding standards
3. Read `docs/modules/index.md` for module map
4. Load the `context-awareness` skill for project orientation

### Running Scripts

Automation scripts produce timestamped JSON reports in `scripts/outputs/`:

```bash
python3 scripts/scan_architecture.py    # Component counts per module
python3 scripts/scan_conventions.py     # Convention compliance
python3 scripts/scan_tests.py           # Test results
python3 scripts/scan_dead_code.py       # Unused code detection
python3 scripts/scan_doc_links.py       # Broken links in docs
python3 scripts/scan_issues.py          # GitHub issue summary
python3 scripts/scan_files.py           # File inventory and LOC
```

Use `--module {Name}` to scope to a single module. See `scripts/README.md`.

### Key Files

| What | Where |
|------|-------|
| Architecture rules | `docs/architecture.md`, `AGENTS.md` |
| Coding conventions | `docs/conventions.md` |
| Module docs | `docs/modules/{name}.md` (conceptual), `docs/modules/{name}-reference.md` (reference) |
| Agent skills | `.agents/skills/` |
| Test suite | `tests/{Module}/` |
| Routes | `routes/web/{module}.php` |
| Translations | `lang/en/`, `lang/id/` |

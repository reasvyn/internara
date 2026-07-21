# Project Overview

> **Last updated:** 2026-07-21 **Changes:** sync — remove Guidance module from known issues

---

## What Is Internara

Self-hosted, single-tenant internship management system (PKL) for Indonesian SMA/SMK schools.
MIT license. Built with Laravel 13, PHP 8.4, Livewire 4, Pest 4.

Handles the full internship lifecycle: school setup, student enrollment, company partnerships,
placement, daily attendance/logbook, supervisor guidance, assessment, evaluation, and final
reporting.

---

## Where We Are

**Phase: v0.14.0 — Stabilization** (in progress)

18 modules in `app/` with their stack: models, actions,
livewire components, events, policies, routes, and translations. Architecture is sound —
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
- **Journals** — attendance, logbook, supervision logs, monitoring visits. Wrong user_id in attendance creation, undefined method.
- **Incident** — incident reporting. ActionResponse gaps. Structurally lean (no Entities, no Events).
- **Assignment** — thesis/supervisor assignment. Runtime crash, ActionResponse gaps.
- **Reports** — grade cards. ActionResponse gaps, dead code. Empty Livewire directory. Being purified.

### Needs Work

These modules have significant issues — some structural, some runtime:

- **Assessment** — structurally complete (Actions, Entities, Events, Livewire, Policies) but has
  runtime errors: Blade array errors, multiple root elements, broken relationships. Multiple P0 issues.
- **Certification** — structurally reasonable (Actions, Livewire, Events, Policies) but has runtime
  errors from schema mismatches and missing columns in migration.
- **Document** — has Models, Enums, Services, Support, and an OfficialDocument submodule with
  Actions/Livewire. No Entities layer. Non-existent columns referenced in code cause SQL errors.

### Skeleton

- **Evaluation** — models only. Zero Actions, Entities, Livewire, Routes, Events.

### Infrastructure (No Business Logic)

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

See [roadmap.md](../roadmap.md) for the full release timeline.

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
python3 scripts/scan_class_contracts.py # Action/Entity/DTO/Model contract compliance
python3 scripts/scan_conventions.py     # Convention compliance (strict_types, Fillable, debug)
python3 scripts/scan_dead_code.py       # Unused code detection
python3 scripts/scan_doc_links.py       # Broken links in docs
python3 scripts/scan_files.py           # File inventory and LOC
python3 scripts/scan_issues.py          # GitHub issue summary
python3 scripts/scan_naming.py          # Naming conventions
python3 scripts/scan_security.py        # XSS, SQLi, CSRF, auth patterns
python3 scripts/scan_tests.py           # Test results
python3 scripts/scan_violations.py      # Architecture invariants C1-C8, D1-D6
```

Use `--module {Name}` to scope to a single module. See `docs/infrastructure/tools.md`.

### Key Files

| What | Where |
|------|-------|
| Architecture rules | `docs/architecture.md`, `AGENTS.md` |
| Coding conventions | `docs/conventions.md` |
| Module docs | `docs/modules/{name}.md` (conceptual), `docs/modules/{name}-reference.md` (reference) |
| Agent skills | `.agents/skills/` |
| Test suite | `tests/{Module}/` |
| Routes | `routes/web/{module}.php` (+ `{submodule}.php` for split submodules) |
| Translations | `lang/en/`, `lang/id/` |

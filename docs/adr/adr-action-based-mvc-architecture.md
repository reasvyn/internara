# ADR-002: Action-based MVC Architecture

> **Status:** Accepted
> **Last updated:** 2026-06-10

## Context

Internara manages vocational fieldwork across 19 modules, each owning a complete business vertical. Traditional flat layering (`app/Models/`, `app/Controllers/`, `app/Livewire/`) scatters a single feature across many directories, making boundaries unclear, encapsulation impossible to enforce, and refactoring expensive.

An alternative — module colocation — ensures everything related to "Enrollment" lives under `app/Enrollment/`. Each module is a vertical slice through 12 architectural layers, from infrastructure at the bottom to business logic at the top.

The 12 layers are:

```
Layer 12 — Business Modules (19 modules, each a vertical slice)
Layer 11 — UI / Presentation (Livewire, Blade, Tailwind)
Layer 10 — HTTP Layer (Controllers, Middleware, Routes)
Layer  9 — Communication (Events, Listeners, Notifications, Console)
Layer  8 — Authorization (Policies, RBAC)
Layer  7 — Business Operations (Command, Read, Process Actions)
Layer  6 — Domain Rules (Entities, Enums, DTOs)
Layer  5 — Module Models (Eloquent)
Layer  4 — Core Base Classes (BaseModel, BaseAction, etc.)
Layer  3 — Core Contracts (LabelEnum, StatusEnum)
Layer  2 — Persistence (Database, Config, Cache, Queue, Files)
Layer  1 — Infrastructure (PHP 8.4, Laravel 13, Spatie packages)
```

## Decision

Code is organized by **business module**, not by technical layer. Each module at `app/{Module}/` is a vertical slice crossing layers 5-11. Layers 1-4 are shared infrastructure provided by the **Core** module.

### Module Directory Layout

```
app/{Module}/
├── {Submodule}/         → One per submodule (Actions, Models, Policies, Livewire)
├── Types/               → Shared value objects, flat enums
├── Http/                → Cross-submodule controllers & middleware
├── Console/             → Cross-submodule artisan commands
├── Livewire/            → Cross-submodule UI components
├── Support/             → Module utilities
└── Services/            → Infrastructure services
```

### Auto-Discovery

`AppServiceProvider` automatically discovers and registers Livewire components (scans `app/*/Livewire/`), policies (auto-links by naming convention), and Blade namespaces (scans `resources/views/*/`). Cross-module policies and event listeners are registered manually.

### Path Convention

- Module-specific: `app/{Module}/{Submodule}/{Component}/{ClassName}.php`
- Shared (cross-module): `app/{Component}/{ClassName}.php`
- Views: `resources/views/{module}/{submodule}/{component-name}.blade.php`
- Tests: `tests/{Feature,Unit}/{Module}/{Submodule}/{Name}Test.php`

No redundant namespace segments — the class name must never repeat in the path.

### Cross-Module Communication

Cross-module imports are **allowed**. Four patterns are available: direct import (simplest), Core contracts (shared interfaces), module events (fire-and-forget), and action delegation (cross-module Action calls).

### Enforcement

Architecture tests that previously enforced module boundaries were removed due to a `pest-plugin-arch` compatibility bug. Until restored, boundary enforcement relies on PHPStan custom rules and code review.

## Consequences

- **Positive**: A feature touches exactly one directory tree — high cohesion, low coupling.
- **Positive**: Module boundaries are explicit. Adding a new module is mechanical — create the directory, add subdirectories, register routes.
- **Positive**: Each module can be developed, tested, and reasoned about independently. Team members own entire modules without stepping on each other.
- **Negative**: Slightly more boilerplate than flat layering for very simple modules.

## References

- `app/` — 19 business module directories
- `app/Core/` — Base classes, contracts, exceptions
- `app/Providers/AppServiceProvider.php` — Auto-discovery and manual registrations
- `docs/architecture.md` — 12-layer architecture, dependency rules
- `docs/conventions.md` — Coding conventions

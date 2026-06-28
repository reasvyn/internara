# ADR-002: Action-based MVC Architecture

> **Last updated:** 2026-06-10
> **Changes:** sync — initial metadata sync with new format


## Description

Code is organized by business module (vertical slicing) rather than by technical layer, with each module owning its complete stack from persistence to UI.

## Context

Internara manages vocational fieldwork across 19 modules, each owning a complete business vertical. Traditional flat layering (`app/Models/`, `app/Controllers/`, `app/Livewire/`) scatters a single feature across many directories, making boundaries unclear, encapsulation impossible to enforce, and refactoring expensive.

An alternative — module colocation — ensures everything related to "Enrollment" lives under `app/Enrollment/`. Each module is a vertical slice through 4 architectural layers, from framework/infrastructure at the bottom to presentation/UI at the top.

The 4 layers are:

```
Layer 4 — Presentation/UI (Livewire, Blade, Controllers, Middleware, Policies, Routes, Console)
Layer 3 — Business/Domain Ops (Command, Read, Process Actions, Events, Listeners, Notifications)
Layer 2 — Data/Persistent (Models, Entities, DTOs, Enums, Database, Config, Cache, Queue)
Layer 1 — Framework/Infrastructure/Utilities (PHP 8.4, Laravel 13, Core base classes, Contracts, Services, Support, packages)
```

## Decision

Code is organized by **business module**, not by technical layer. Each module at `app/{Module}/` is a vertical slice crossing layers 2-4. Layer 1 (Framework/Infrastructure) is shared infrastructure provided by the **Core** module.

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
- `docs/architecture.md` — 4-layer architecture, dependency rules
- `docs/conventions.md` — Coding conventions

# Action-based MVC Architecture

| Field | Value |
|-------|-------|
| Status | Accepted |
| Deciders | Reas Vyn |
| Date | 2026-08-16 |
| Technical Story | [Architecture Overview](../architecture.md) and [Modular Pattern](../guides/arch/modular-pattern.md) |

## Context and Problem Statement

Internara manages vocational fieldwork across 18 business modules, each owning a complete
vertical slice from persistence to UI. Traditional flat layering (`app/Models/`,
`app/Controllers/`, `app/Livewire/`) scatters a single feature across many directories,
making module boundaries unclear, encapsulation impossible to enforce, and refactoring
expensive.

Colocating by module ensures everything related to "Enrollment" lives under `app/Modules/Enrollment/`,
but requires a shared layering model so each module remains internally coherent while the
system as a whole stays navigable.

**Decision Drivers:**

* Feature cohesion — one directory tree per business capability
* Explicit module boundaries that can be reasoned about and tested
* Mechanical addition of new modules without cross-cutting edits
* Navigability: predictable path conventions across 18 modules

The 4 architectural layers framing every slice:

```
Layer 4 — Presentation/UI (Livewire, Blade, Controllers, Middleware, Policies, Routes, Console)
Layer 3 — Business/Domain Ops (Command, Read, Process Actions, Events, Listeners, Notifications)
Layer 2 — Data/Persistent (Models, Entities, DTOs, Enums, Database, Config, Cache, Queue)
Layer 1 — Framework/Infrastructure/Utilities (PHP 8.4, Laravel 13, Core base classes, Contracts, Support, packages)
```

## Considered Options

* **Flat layering by technical layer** — `app/Models/`, `app/Http/Controllers/`, `app/Livewire/`
  scatters each business feature. *Pros:* familiar Laravel default. *Cons:* boundaries invisible,
  refactoring crosses many directories, no module ownership.
* **Vertical slicing by business module (chosen)** — `app/{Module}/` as a slice through
  layers 2–4; Layer 1 shared via Core. *Pros:* high cohesion, explicit ownership, localized
  change. *Cons:* slight boilerplate for trivial modules.
* **Micro-modules / microservices** — isolate modules as independent deployables. *Pros:*
  independent scaling. *Cons:* operational overhead unjustified for a single-tenant
  school deployment; transaction and auth complexity far exceeds benefit.

## Decision Outcome

**Chosen option: Vertical slicing by business module** — code is organized by business
module, not by technical layer. Each module at `app/{Module}/` is a vertical slice through
layers 2–4; Layer 1 (Framework/Infrastructure) is shared infrastructure provided by the
**Core** module.

**Module Directory Layout:**

```
app/{Module}/
├── {Submodule}/  → Actions, Models, Policies, Livewire (one per submodule)
├── Types/        → Shared value objects, flat enums
├── Http/         → Cross-submodule controllers & middleware
├── Console/      → Cross-submodule artisan commands
├── Livewire/     → Cross-submodule UI components
├── Support/      → Module utilities
└── Services/     → Infrastructure services
```

**Auto-Discovery** — `AppServiceProvider` discovers Livewire components (`app/*/Livewire/`),
policies (by naming convention), and Blade namespaces (`resources/views/*/`). Cross-module
policies and event listeners are registered manually.

**Path Convention:**
* Module-specific: `app/{Module}/{Submodule}/{Component}/{ClassName}.php`
* Shared (cross-module): `app/{Component}/{ClassName}.php`
* Views: `resources/views/{module}/{submodule}/{component-name}.blade.php`
* Tests: `tests/{Module}/{Submodule}/{Name}Test.php` — no redundant namespace segments.

**Cross-Module Communication** — direct imports are allowed. Four patterns are available
in ranked guidance: direct import (simplest), Core contracts, module events, Action delegation.

**Enforcement** — architecture tests that once enforced boundaries were removed due to a
`pest-plugin-arch` compatibility bug. Until restored, boundary enforcement relies on PHPStan
custom rules and code review.

### Positive Consequences

* A feature touches exactly one directory tree — high cohesion, low coupling
* Module boundaries are explicit; adding a new module is mechanical
* Each module can be developed, tested, and reasoned about independently

### Negative Consequences

* Slightly more boilerplate than flat layering for very simple modules

## Links

* [Architecture Overview](../architecture.md) — 4-layer model and dependency rules
* [Modular Pattern](../guides/arch/modular-pattern.md) — colocation and SRP enforcement
* [Cross-Module Communication Discipline](adr-cross-module-communication.md) — how slices interact at runtime

# Modular Pattern Reference — Design Patterns, Conventions & Architecture Rules

> **Last updated:** 2026-08-27 **Changes:** rewrite — integrate global standards (Modular Monolith, Clean Architecture, Hexagonal, SOLID, DRY) with anti-pattern table, Quick References

## Description

Complete catalog of design patterns, conventions, and architectural rules used across all 19
business modules. Grounded in **Modular Monolith**, **Clean Architecture**, **Hexagonal Architecture**,
**SOLID**, and **DRY** — all mapped to Internara's Laravel stack.

---

## Non-Negotiable

Hard rules. Violations are architecture violations.

1. **Module-Colocated Vertical Slicing.** Code organized by business domain, not by technical layer. Each module groups all components under a single root. No cross-module imports of internals.

2. **4-Layer Architecture — downward-only.** Strict downward-only dependency graph. Core (Layer 1) depends on nothing except Laravel and Spatie. No business module may be imported by Core. This is the Dependency Rule (Clean Architecture).

3. **Action Triad — one public method.** Three distinct contracts: **Command** mutations with transactions and logging, **Read** queries without transactions, **Process** orchestration of multiple Commands. Each Action has exactly one public `execute()` method.

4. **Entity-Model Separation.** Entities are `final readonly`, pure business rules. Models are persistence. Bridge pattern connects them: `Entity::fromModel()` and `Model::as{Entity}()`.

5. **No business logic in Livewire (C1).** Livewire components handle only UI state and delegation. All mutations go through Actions.

6. **No DTO forbidden imports (C6).** DTOs carry scalars, DTOs, and enums only — no Model/Entity/Service/Action imports.

7. **DTO for 3+ params (C7).** Command and Process Actions with 3+ parameters MUST use a DTO.

8. **Localization via `__()`.** All user-facing strings MUST use the `__()` helper. Dual locale requirement: every key in both `lang/en/` and `lang/id/`.

---

## How to Apply

### 1. Modular Monolith

A modular monolith decomposes a monolithic application into self-contained modules with explicit boundaries. Each module owns its full stack (Actions, Entities, Models, Livewire, Policies). Modules communicate through public surfaces only — Actions, contracts, events. This gives the architectural benefits of microservices without the deployment complexity.

**Reference:** [Modular Monolith by Povilas Korop](https://laravelnews.com/modular-pattern)

### 2. Clean Architecture (Uncle Bob)

The Dependency Rule: source code dependencies must point inward toward higher-level policies. In Internara:
- **Core** (Layer 1) = Framework/Infrastructure — depends on nothing business
- **Business** (Layer 2) = Domain Operations — depends only on Core
- **UI** (Layer 3) = Presentation — depends on Business + Core
- **Presentation** (Layer 4) = Blade/Assets — depends on UI + Core

### 3. Hexagonal Architecture (Ports & Adapters)

The core application logic (Entities, Actions) is isolated from external concerns (database, UI, cache) via ports and adapters. In Internara, the Entity is the core, Actions are the application services, and Livewire/Controllers are the adapters. The Entity never knows about Eloquent, HTTP, or cache.

### 4. SOLID Principles

| Principle | Application |
|-----------|------------|
| **Single Responsibility** | Each module, Action, Entity, DTO has one reason to change |
| **Open/Closed** | Extend via new Actions/Entities, never modify existing ones |
| **Liskov Substitution** | Actions can be swapped (Command → Process) without breaking callers |
| **Interface Segregation** | Small, focused contracts (LabelEnum, StatusEnum, BaseData) |
| **Dependency Inversion** | Depend on abstractions (contracts), not concretions |

### 5. DRY (Don't Repeat Yourself)

Extract repeated logic into shared utilities:
- Shared UI components → `resources/views/core/`
- Cross-module utilities → `app/Core/Support/`
- Shared Livewire concerns → `app/Core/Livewire/Concerns/`
- Common test helpers → `tests/Pest.php`

---

## Anti-Patterns

| You see... | It should be... | Violation |
|-----------|----------------|-----------|
| Module A importing Module B's internal Action | Use events or cross-module contracts | 4-layer violation |
| `Model::create()` in Livewire | Inject and call Command Action | C1 — mutation in UI |
| Entity importing `Model` or `Action` | Remove — Entity is pure | C5 — forbidden imports |
| DTO importing `User` Model | Remove — DTO carries scalar ID | C6 — forbidden imports |
| Action with 5 array params, no DTO | Create `VerbEntityData` | C7 — DTO for 3+ params |
| Business rule in Blade `@if` | Compute in component, expose as property | Business logic in presentation |
| Module `Support/` with 20+ classes | Split: pure → Support, framework → Service | God Module |
| `config('module.*')` read outside ModuleManager | Use `ModuleManager` gateway | Centralized config bypass |
| Raw `app()->make()` in Livewire | Inject via method parameter | Manual resolution |
| `Cache::forget('inline-key')` | Use `config('cache-keys.key')` | C4 — inline cache key |

---

## Quick References

- `docs/conventions.md` — project conventions (C1-C10, D1-D11)
- `docs/architecture.md` — 4-Layer Architecture
- `docs/adr/` — Architecture Decision Records
- `docs/specs/index.md` — Feature specifications
- `docs/refs/modules/index.md` — Module catalog
- `app/Core/` — Core layer (Base classes, contracts, support)
- [Modular Monolith](https://laravel.com/docs/modular) — Laravel modularity
- [Clean Architecture](https://blog.cleancoder.com/clean-code-review/2016/10/03/Clean-Architecture.html) — Uncle Bob
- [Hexagonal Architecture](https://en.wikipedia.org/wiki/Hexagonal_software_architecture) — Ports & Adapters
- [SOLID Principles](https://en.wikipedia.org/wiki/SOLID) — object-oriented design
- [DRY Principle](https://en.wikipedia.org/wiki/Don%27t_repeat_yourself) — code reuse

---
name: feature-building
description: "Apply when building any new feature, component, or modifying existing code in the Internara codebase. This skill encodes the project's architecture, conventions, standards, and lifecycle knowledge into a step-by-step feature building workflow."
license: MIT
metadata:
  author: internara
---

# Feature Building: Internara Development Standards

Complete reference for building features that follow Internara's architecture, conventions, and quality standards.

## Quick Start

```
1. Understand the domain → read docs/en/ for lifecycle context
2. Plan the layers → Action, Entity, Model, Livewire, View
3. Write Actions (business logic) + Entities (business rules)
4. Write Livewire (thin) + Blade (maryUI + Tailwind)
5. Write tests (Pest)
6. Add translations (EN + ID)
7. Register routes + sidebar menu (config/menu.php)
8. Run lint (Pint) + build (Vite)
```

## Architecture Overview

```
User Input → Livewire → Action → Model → Database
                              ↓
                        Audit / Event / Flash
```

| Layer | Directory | Responsibility |
|---|---|---|
| **Action** | `app/Actions/{Domain}/` | Business logic, validation, persistence, side effects |
| **Entity** | `app/Entities/{Domain}/` | Business rules, pure PHP |
| **Model** | `app/Models/` | Data queries, relationships, scopes |
| **Livewire** | `app/Livewire/{Domain}/` | UI state, form binding, delegation |
| **View** | `resources/views/livewire/{domain}/` | Blade templates, maryUI, Tailwind |
| **Support** | `app/Support/` | Static utilities, helpers |
| **Enum** | `app/Enums/{Domain}/` | Constants with business logic |

## Rules

| # | Rule | File | Key Points |
|---|------|------|------------|
| 1 | [Action Pattern](rules/01-action-pattern.md) | Actions | Single `execute()`, validation, transactions, audit |
| 2 | [Entity Rules](rules/02-entity-rules.md) | Entities | `final readonly`, no Eloquent/Facades, `fromModel()` bridge |
| 3 | [Model Conventions](rules/03-model-conventions.md) | Models | UUIDs, `#[Fillable]`, `HasFactory`, `as{Name}()` accessor |
| 4 | [Livewire Components](rules/04-livewire-components.md) | Livewire | Thin, delegate to Actions, `WithFileUploads` |
| 5 | [Blade & UI](rules/05-blade-ui.md) | Views | maryUI, Tailwind v4, daisyUI, dual-language |
| 6 | [Database & Migrations](rules/06-database-migrations.md) | DB | UUID PKs, `foreignUuid()`, anonymous migrations |
| 7 | [Routing & Menus](rules/07-routing-menus.md) | Routes | Named routes, sidebar in `config/menu.php`, middleware |
| 8 | [Testing](rules/08-testing.md) | Tests | Pest 4, `LazilyRefreshDatabase`, Entity tests sans DB |
| 9 | [Translations](rules/09-translations.md) | Lang | EN + ID, `__()` helper, domain-key convention |
| 10 | [Notifications & Events](rules/10-notifications-events.md) | Events | ShouldQueue, 3 channels, domain events for state changes |
| 11 | [Exceptions & Errors](rules/11-exceptions-errors.md) | Errors | `AppException` hierarchy, `HandlesActionErrors` trait |
| 12 | [Caching & Settings](rules/12-caching-settings.md) | Cache | `Cache::rememberForever()`, invalidation on write |
| 13 | [Lifecycle Context](rules/13-lifecycle-context.md) | Domain | Internship lifecycle phases, entity state machines |

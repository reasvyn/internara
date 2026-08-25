---
name: laravel-best-practices
description: "SDLC Phase: IMPLEMENTATION (Cross-cutting). Context-aware Laravel guidance that overrides default conventions where they conflict with the Module-first Action-based MVC architecture. Referenced by all implementation sub-skills."
upstream:
  - feature-building
  - code-refactoring
  - livewire-development
downstream:
  - feature-building
  - livewire-development
  - medialibrary-development
  - pulse-development
  - tailwindcss-development
---

# Laravel Best Practices (Internara Edition)

> **Last updated:** 2026-08-17 **Changes:** extracted inline rules (§Key Differences, §Key Conventions, §Common Pitfalls) into `rules/` rule assets with a `## Skill Rules` mapping section

> **Prerequisite:** Load `context-awareness` for project orientation.

## When to Activate

Use this as a cross-cutting reference when implementing any feature. It documents where Internara's
conventions diverge from standard Laravel practices. Only covers the decisions that are non-standard
or commonly misunderstood.

## Workflow

Follow the `agent-workflow` skill for the canonical 5-step pipeline (Understand → Plan → Implement → Verify → Summarize): spec-first
doctrine (**governing spec** FR/NFR/UC IDs), **Size Triage** (S/M/L session splitting), verification
strategy, and commit format. This skill is a cross-cutting reference — it documents where Internara's
conventions diverge from stock Laravel. Apply the conventions below during implementation.

## Key Differences from Stock Laravel

### Module-First, Not Layer-First

| Stock Laravel                                | Internara                                               |
| -------------------------------------------- | ------------------------------------------------------- |
| `app/Models/` contains all models            | Models live in `app/{Module}/{SubModule}/Models/`       |
| `app/Http/Livewire/` contains all components | Components live in `app/{Module}/{SubModule}/Livewire/` |
| `app/Policies/` contains all policies        | Policies live in `app/{Module}/{SubModule}/Policies/`   |

### Actions Replace Services

- Business logic goes in Actions (Command/Read/Process), not Services
- Services are for infrastructure logic only (environment checks, system utilities)
- Support classes are for static utilities with zero side effects

### No FormRequest Classes

- Use Livewire Form Objects (`Livewire\Form`) for validation
- Shared validation goes in Entity static `rules()` or a dedicated Rules class

### DTO Over Array

- 3+ params to an Action → use a `BaseData` DTO
- Never pass raw `array` to `execute()`

## Key Conventions

| Concern         | Rule                                                                                             |
| --------------- | ------------------------------------------------------------------------------------------------ |
| Mass assignment | `#[Fillable]` attribute on every Model. Never `$fillable` or `$guarded`. Never `$request->all()` |
| Query scopes    | Define on Model for reuse. Complex queries → Read Action                                         |
| Relationships   | Define on Model. Eager load with `->with()`                                                      |
| Validation      | Form Objects for Livewire; Entity static methods for shared rules                                |
| Authorization   | Policies extending `BasePolicy` for CRUD; Gate::before for super admin bypass                    |
| Caching         | All keys in `config/cache-keys.php`. Event-driven invalidation                                   |
| File uploads    | Spatie MediaLibrary only. Never `Storage::put()`                                                 |
| Localization    | `__('module.key')` — both EN and ID. Never hardcode display text                                 |
| Exceptions      | `RejectedException` for business rules. Specific types for specific scenarios                    |

## Common Pitfalls

1. **Calling `Model::create()` in Livewire** — use Command Action via method injection
2. **Using `app()->make()`** — inject via constructor (Services) or method parameter (Livewire)
3. **Hardcoding cache keys** — register in `config/cache-keys.php` first
4. **Missing `declare(strict_types=1)`** — required in every PHP file except migrations/config
5. **Skipping lang files** — every `__()` call needs keys in both `lang/en/` and `lang/id/`

## Skill Rules

| Rule | Asset | Applies when |
|------|-------|--------------|
| Module-first architecture (placement, Actions vs Services, no FormRequest, DTO over array) | `rules/module-first-architecture.md` | Creating files or classes, or reviewing where logic lives |
| Data & cache conventions (mass assignment, scopes, relationships, caching) | `rules/data-and-cache-conventions.md` | Touching Models, queries, or cache |
| Validation, authorization & exceptions (form objects, policies, super-admin bypass, RejectedException) | `rules/validation-authorization-exceptions.md` | Adding validation, authorization, or exception handling |
| Cross-cutting conventions (uploads, localization, structural guards) | `rules/cross-cutting-conventions.md` | Any feature using uploads, `__()` strings, or stock-Laravel habits |

## References

| Topic                        | Doc                                                          |
| ---------------------------- | ------------------------------------------------------------ |
| Coding conventions (full)    | `docs/conventions.md`                                        |
| Architecture                 | `docs/architecture.md`                                       |
| Model conventions            | `docs/guides/arch/model-pattern.md`                         |
| Action Triad                 | `docs/guides/arch/action-pattern.md`                        |
| Entity separation            | `docs/guides/arch/entity-pattern.md`                        |
| DTO / Data                   | `docs/guides/arch/data-pattern.md`                          |
| Exception patterns           | `docs/guides/arch/exception-pattern.md`                     |
| Cache patterns               | `docs/guides/arch/cache-pattern.md`                         |
| Service vs Action vs Support | `docs/guides/arch/service-pattern.md`, `support-pattern.md` |

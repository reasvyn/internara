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

> **Prerequisite:** Load `context-awareness` for project orientation.

## When to Activate

Use this as a cross-cutting reference when implementing any feature. It documents where Internara's
conventions diverge from standard Laravel practices. Only covers the decisions that are non-standard
or commonly misunderstood.

## Agent Workflow

Using this skill follows 4 phases:

### 1. Construct — Knowledge, Context & Scope

- Load `context-awareness` skill for project orientation
- Read relevant docs: module docs, pattern docs, reference docs
- Understand task scope: what needs to be done, which files are affected
- Verify paths, class names, signatures against actual code (don't trust docs blindly)
- Determine approach: at least 2 options before deciding

### 2. Execute — Apply Internara Conventions

- Apply Internara conventions that differ from stock Laravel
- Module colocation: logic in `app/{Module}/`, not `app/Models/`
- Actions for business logic, Services for infrastructure logic
- #[Fillable] attribute, foreignUuid(), declare(strict_types=1)
- DTO for 3+ params input, ActionResponse for output
- Cache keys in config/cache-keys.php, event-driven invalidation
- Output: verified convention compliance — code follows Internara-specific Laravel patterns

### 3. Verify — Quality Gates

- Run linter: `vendor/bin/pint --dirty --format agent`
- Run static analysis: `vendor/bin/phpstan analyse --no-progress`
- Run unit/feature tests: `php artisan test --compact --filter={TestName}`
- Ensure pre-commit checklist is satisfied
- Check no debug calls (`dd/dump/ray`) were left behind

### 4. Report & Commit

- Deliver a comprehensive report to the user:
    - Summary of conventions applied
    - Key deviations from stock Laravel noted
    - Common pitfalls avoided
- Feeds into: feature-building, livewire-development (cross-cutting reference during implementation)
- Commit using format: `type(scope): description`
- Push if requested

## Phase Context

| Role           | Skill                                                              |
| -------------- | ------------------------------------------------------------------ |
| **Upstream**   | `feature-building`, `code-refactoring`, `livewire-development`     |
| **This skill** | **IMPLEMENTATION (Cross-cutting)** — overrides default conventions |
| **Downstream** | All implementation skills                                          |

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

## References

| Topic                        | Doc                                                          |
| ---------------------------- | ------------------------------------------------------------ |
| Coding conventions (full)    | `docs/conventions.md`                                        |
| Architecture                 | `docs/architecture.md`                                       |
| Model conventions            | `docs/architecture/model-pattern.md`                         |
| Action Triad                 | `docs/architecture/action-pattern.md`                        |
| Entity separation            | `docs/architecture/entity-pattern.md`                        |
| DTO / Data                   | `docs/architecture/data-pattern.md`                          |
| Exception patterns           | `docs/architecture/exception-pattern.md`                     |
| Cache patterns               | `docs/architecture/cache-pattern.md`                         |
| Service vs Action vs Support | `docs/architecture/service-pattern.md`, `support-pattern.md` |

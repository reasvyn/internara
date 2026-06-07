# Laravel Best Practices Skill

## When to Activate

Apply this skill whenever writing, reviewing, or refactoring any Laravel PHP code — including
controllers, models, migrations, form requests, policies, jobs, scheduled commands, service classes,
Eloquent queries, Blade views, and route definitions. This skill is context-aware of the
Module-first Action-based MVC architecture and overrides default Laravel conventions where they
conflict.

## Core Principles

The primary architectural rule: all code lives under `app/{Module}/` instead of the default
`app/Models/`, `app/Http/Controllers/`, etc. This module-first structure keeps every concept
self-contained. Views mirror at `resources/views/{domain}/`, routes are split into
`routes/web/{module}.php`.

### Key Overrides vs Standard Laravel

Models are in `app/{Module}/Models/` not `app/Models/`. Business logic lives in Action classes, not
in Models or Controllers. Livewire components are in `app/{Module}/Livewire/` (auto-discovered).
Policies are in `app/{Module}/Policies/`. Enums live in their domain's Enums directory and implement
`LabelEnum` or `StatusEnum`.

### Action-Oriented MVC

Controllers and Livewire components are thin — they handle UI state and delegate to Actions. Actions
are single-responsibility classes with one `execute()` method. They validate input, delegate rule
checks to Entities, persist in transactions, and emit side effects.

### Data Access vs Business Logic

Models handle data access only: relationships, scopes, casts, attributes. Entities handle business
rules as pure PHP objects (`final readonly`, no framework dependencies). The Model exposes an
`as{EntityName}()` accessor that bridges to the Entity.

## Verification Points

- Does the code follow Module-first structure? (check sibling files for convention)
- Is business logic in Actions, not Models or Livewire?
- Are business rule checks delegated to Entities?
- Are UUID primary keys used (BaseModel)?
- Are translations used instead of hardcoded strings?
- Is `declare(strict_types=1)` on every PHP file?
- Are array validation rules used over pipe syntax?

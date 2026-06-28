# Architecture

> **Last updated:** 2026-06-28
> **Changes:** sync — update data flow layer naming to match new 4-layer model

## What It Enforces

All code lives under `app/{Module}/` — no top-level `app/Models/`, `app/Http/Controllers/`, etc.
Views mirror in `resources/views/{domain}/`. Every business operation is an Action class with a
single `execute()` method. Dependencies are injected via constructor promotion. Interfaces define
system boundaries.

The data flow with DTO boundaries prevents circular dependencies:
- **Presentation/UI** (Livewire/Controller/Console/Policies) → builds DTO from validated input
- **Business/Domain Ops** (Action/Event/Middleware) → receives DTO only, delegates rules to Entity
- **Data/Persistent** (Model/Entity/DTO/Enum/Database) → Eloquent persistence, knows nothing about layers above

**Key boundary rules:**
- Command/Process Actions SHOULD accept `BaseData` DTO for 3+ params — simple ops may use typed scalars
- Command/Process Actions SHOULD return `ActionResponse` for structured feedback — simple returns may use Model directly
- Livewire may access Entity methods for READ-ONLY UI checks (show/hide buttons). WRITE decisions go through Actions.
- Entities MUST NOT import Actions, Services, Livewire, or Controllers
- DTOs MUST NOT import Models, Actions, Entities, or Livewire — only scalars, enums, Carbon

## Why It Matters

Module-first grouping keeps every concept self-contained. When working on "Academic Year," all its
code — Model, Action, Entity, Livewire, Policy, Enum — is in `app/Academics/` (or whichever module
owns it). This is faster to navigate than Laravel's default layer-first structure where related code
is scattered across `app/Models/`, `app/Http/Controllers/`, etc.

Constructor injection over `app()` or `resolve()` makes dependencies explicit and testable.
Interfaces at system boundaries (PaymentGateway, NotificationService) allow swapping implementations
without changing business logic.

The DTO-boundary architecture prevents circular dependencies by ensuring data flows in one direction:
UI → Business → Domain → Data. No layer ever reaches upward.

## When It Applies

Always. This is the foundational architectural rule. The Action layer replaces what would be inline
logic in Controllers or Service classes.

Additional practices:

- Default sort order must be explicit: use `latest()`, `oldest()`, or specific `orderBy` calls.
  Never rely on row insertion order.
- Atomic locks prevent race conditions on critical operations
- `defer()` for post-response work (logging, analytics) that doesn't need queue overhead
- `Concurrency::run()` for parallel independent operations
- `Context` for request-scoped data (tenant ID, request ID)

Exceptions: The User model extends `Illuminate\Foundation\Auth\User` rather than `BaseModel`, placed
in `app/User/Models/`.

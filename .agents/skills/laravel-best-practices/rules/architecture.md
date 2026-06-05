# Architecture

## What It Enforces

All code lives under `app/Domain/{Domain}/` — no top-level `app/Models/`, `app/Http/Controllers/`, etc. Views mirror in `resources/views/{domain}/`. Every business operation is an Action class with a single `execute()` method. Dependencies are injected via constructor promotion. Interfaces define system boundaries.

## Why It Matters

Module-first grouping keeps every concept self-contained. When working on "Academic Year," all its code — Model, Action, Entity, Livewire, Policy, Enum — is in `app/Domain/School/` (or whichever module owns it). This is faster to navigate than Laravel's default layer-first structure where related code is scattered across `app/Models/`, `app/Http/Controllers/`, etc.

Constructor injection over `app()` or `resolve()` makes dependencies explicit and testable. Interfaces at system boundaries (PaymentGateway, NotificationService) allow swapping implementations without changing business logic.

## When It Applies

Always. This is the foundational architectural rule. The Action layer replaces what would be inline logic in Controllers or Service classes.

Additional practices:
- Default sort order must be explicit: use `latest()`, `oldest()`, or specific `orderBy` calls. Never rely on row insertion order.
- Atomic locks prevent race conditions on critical operations
- `defer()` for post-response work (logging, analytics) that doesn't need queue overhead
- `Concurrency::run()` for parallel independent operations
- `Context` for request-scoped data (tenant ID, request ID)

Exceptions: The User model extends `Illuminate\Foundation\Auth\User` rather than `BaseModel`, placed in `app/User/Models/`.

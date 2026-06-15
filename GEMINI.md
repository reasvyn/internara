<laravel-boost-guidelines>
> **Last updated:** 2026-06-15
>
> **Purpose:** Thin agentic instruction layer for Gemini. All authoritative docs live under `docs/`.
> Do NOT duplicate content from `docs/` — refer to it instead.

=== foundation ===

# Project Context

PHP 8.4, Laravel v13, Livewire v4, Boost v2.

- `docs/` is the SSOT for architecture (`docs/architecture.md`), conventions (`docs/conventions.md`),
  modules (`docs/modules/`), and infrastructure (`docs/infrastructure/`).
- Always read relevant docs before making changes. See `docs/doc-index.md` for the complete catalog.

## Skills Activation

Activate `.agents/skills/` when working in that domain:

- `livewire-development`, `livewire-refactoring`
- `action-refactoring`, `entity-refactoring`
- `feature-building`, `laravel-best-practices`
- `pest-testing`, `pulse-development`
- `medialibrary-development`, `tailwindcss-development`
- `audit-protocol` — comprehensive multi-layer codebase audit
- `sync-docs` — synchronize ALL markdown documentation with actual implementation

## Documentation (NOT Duplicated Here)

See `AGENTS.md` for the complete list of topics covered in `docs/`. Key locations:

| Topic | Location |
|-------|----------|
| Architecture & 12 layers | `docs/architecture.md` |
| Action Triad | `docs/architecture.md` |
| Base class mandate | `docs/architecture.md` (Base Class Mandate §) |
| Naming conventions | `docs/conventions.md` (§3 Naming Conventions) |
| Models & Entities | `docs/architecture/entity-pattern.md`, `docs/architecture/model-pattern.md` |
| Livewire components | `docs/architecture/livewire-pattern.md` |
| Events & Notifications | `docs/architecture/event-pattern.md` |
| Testing | `docs/architecture/testing-pattern.md`, `docs/infrastructure/testing.md` |

## Module Invariants (DO NOT VIOLATE)

- **Super Admin name** is ALWAYS `Administrator` (config `setup.defaults.admin_name`).
- **Super Admin username** is ALWAYS `superadmin` (config `setup.defaults.admin_username`).
- `SetupSuperAdminAction::execute()` accepts ONLY `(string $email, string $password)`.
- `InitializeSuperAdminAction` must use config defaults, not caller-provided values.
- `FinalizeSetupAction` must extract only `email` and `password` from `adminData` array.

## Quick-Reference Rules

- `declare(strict_types=1)` in all PHP files except migrations and config.
- No `dd()`, `dump()`, `ray()`, `var_dump()`, `die()` in committed code.
- All user-facing strings use `__()` helper.
- Foreign keys use `foreignUuid()->constrained()`.
- Run `vendor/bin/pint --format agent` after modifying PHP files.

=== boost ===

# Laravel Boost Tools

Prefer Boost tools over manual alternatives:

- `database-query` — read-only SQL
- `database-schema` — inspect table structure
- `get-absolute-url` — resolve project URLs
- `browser-logs` — read browser errors

Use `search-docs` before code changes with multiple broad queries for OR logic.

=== php ===

- Curly braces for all control structures.
- Constructor property promotion.
- Explicit return types and parameter type hints.
- `===` over `==`, `match()` over `switch()`.
- Null-safe `?->` and null coalescing `??`.

=== deployments ===

- Queue worker: `php artisan queue:work`
- Scheduler: `* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1`
- Storage link: `php artisan storage:link`

=== tests ===

- Every change must be tested: `php artisan test --compact --filter=TestName`.
- `LazilyRefreshDatabase` over `RefreshDatabase`.
- `assertModelExists()` over `assertDatabaseHas()`.


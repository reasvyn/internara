<laravel-boost-guidelines>
> **Last updated:** 2026-06-16
> **Changes:** sync — align with AGENTS.md: add Documentation Quality section, complete NOT Duplicated Here table, fix constrained/pint/print_r rules, add targeted test command
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

The following topics are fully covered in `docs/` and MUST NOT be duplicated here:

| Topic | Location |
|-------|----------|
| Architecture & 12 layers | `docs/architecture.md` |
| Action Triad (Command/Read/Process) | `docs/architecture.md` |
| Base class mandate | `docs/architecture.md` (Base Class Mandate §) |
| File structure conventions | `docs/architecture/modular-pattern.md` |
| PHP language rules | `docs/conventions.md` (§2 General PHP) |
| Naming conventions | `docs/conventions.md` (§3 Naming Conventions) |
| Models & Entities | `docs/architecture/entity-pattern.md`, `docs/architecture/model-pattern.md` |
| Enums (LabelEnum, StatusEnum) | `docs/architecture/enum-pattern.md` |
| Livewire components | `docs/architecture/livewire-pattern.md` |
| Events & Notifications | `docs/architecture/event-pattern.md` |
| Routes & Controllers | `docs/architecture/modular-pattern.md` |
| Migrations, Factories, Seeders | `docs/conventions.md` (§5 Migrations, Factories & Seeders) |
| Testing conventions | `docs/architecture/testing-pattern.md` |

## Module Invariants (DO NOT VIOLATE)

These are critical project-specific invariants that exist in the codebase but are enforced at the agent level:

- **Super Admin name** is ALWAYS `Administrator` (config `setup.defaults.admin_name`).
- **Super Admin username** is ALWAYS `superadmin` (config `setup.defaults.admin_username`).
- `SetupSuperAdminAction::execute()` accepts ONLY `(string $email, string $password)`.
- `InitializeSuperAdminAction` must use config defaults, not caller-provided values.
- `FinalizeSetupAction` must extract only `email` and `password` from `adminData` array.

## Documentation Quality

**Avoid brittle content.** Numbers, states, statuses, and enumerated lists become stale the moment code changes. The only exception is documents explicitly designed as catalogs (e.g. `docs/doc-index.md`, `docs/modules/module-index.md`).

When writing or editing docs, prefer:
- **Structural statements** over counts: "Models extend `BaseModel`" not "There are 42 models"
- **Locational statements** over listings: "Actions live under `app/{Module}/*/Actions/`" not "Auth has 10 actions"
- **Factual statements** over status: describe what actually exists, not what phase the project is in

For derivative docs (this file, AGENTS.md, README.md), do NOT duplicate version numbers or counts — reference `composer.json`, `package.json`, or `docs/` instead.

## Quick-Reference Rules

- `declare(strict_types=1)` in all PHP files except migrations and config.
- No `dd()`, `dump()`, `ray()`, `var_dump()`, `print_r()`, `die()` in committed code.
- All user-facing strings use `__()` helper.
- Foreign keys use `foreignUuid()->constrained('{table}')`.
- Run `vendor/bin/pint --dirty --format agent` after modifying PHP files.
- Run `php artisan test --compact --filter=TestName` for targeted tests.

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


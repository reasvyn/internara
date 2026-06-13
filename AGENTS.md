<laravel-boost-guidelines>
> **Last updated:** 2026-06-10
>
> **Purpose:** Thin agentic instruction layer. All authoritative docs live under `docs/`. This file
> provides quick-reference essentials and project-specific rules that cannot wait for doc lookups.
> Do NOT duplicate content already covered in `docs/` — refer to it instead.

=== foundation ===

# Project Context

PHP 8.4, Laravel v13, Livewire v4, Boost v2.

- `docs/` is the SSOT for architecture (`docs/architecture.md`), conventions (`docs/conventions.md`),
  modules (`docs/modules/`), and infrastructure (`docs/infrastructure/`).
- Always read relevant docs before making changes. See [doc-index](docs/doc-index.md) for the
  complete catalog.

## Skills Activation

Project skills are in `.agents/skills/`. Activate the relevant skill when working in that domain:

- `livewire-development` — building/editing Livewire components
- `livewire-refactoring` — extracting business logic from Livewire
- `action-refactoring` — creating/modifying Action classes
- `entity-refactoring` — creating/modifying Models and Entities
- `feature-building` — full feature lifecycle workflow
- `laravel-best-practices` — general Laravel patterns
- `pest-testing` — writing/modifying tests
- `pulse-development` — Laravel Pulse setup
- `medialibrary-development` — file uploads, media collections
- `tailwindcss-development` — styling, daisyUI, maryUI
- `audit-protocol` — comprehensive multi-layer codebase audit against conventions, patterns, security, and industry standards
- `sync-docs` — synchronize ALL markdown documentation with actual implementation

## Documentation (NOT Duplicated Here)

The following topics are fully covered in `docs/` and MUST NOT be duplicated here:

| Topic | Location |
|-------|----------|
| Architecture & 12 layers | `docs/architecture.md` |
| Action Triad (Command/Read/Process) | `docs/architecture.md` |
| Base class mandate | `docs/conventions.md` (#1) |
| File structure conventions | `docs/conventions.md` (#2) |
| PHP language rules | `docs/conventions.md` (#3) |
| Naming conventions | `docs/conventions.md` (#4) |
| Models & Entities | `docs/conventions.md` (#5-7) |
| Enums (LabelEnum, StatusEnum) | `docs/conventions.md` (#8) |
| Livewire components | `docs/conventions.md` (#10) |
| Events & Notifications | `docs/conventions.md` (#12-13) |
| Routes & Controllers | `docs/conventions.md` (#14) |
| Migrations, Factories, Seeders | `docs/conventions.md` (#17) |
| Testing conventions | `docs/conventions.md` (#22) |

## Module Invariants (DO NOT VIOLATE)

These are critical project-specific invariants that exist in the codebase but are enforced at the
agent level:

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
- Run `php artisan test --compact --filter=TestName` for targeted tests.

=== boost ===

# Laravel Boost Tools

Prefer Boost tools over manual alternatives:

- `database-query` — read-only SQL queries (not raw SQL in tinker)
- `database-schema` — inspect table structure before migrations/models
- `get-absolute-url` — resolve correct scheme/host/port for project URLs
- `browser-logs` — read browser errors (only recent logs useful)

## Searching Documentation

Always use `search-docs` before code changes. Pass `packages` array to scope results. Use multiple
broad, topic-based queries for OR logic.

## Artisan & Tinker

```bash
php artisan list                    # Discover commands
php artisan route:list --method=GET # Inspect routes
php artisan config:show app.name    # Read config values
php artisan tinker --execute 'User::count();'
```

Prefer existing Artisan commands and tests over custom tinker code.

=== php ===

# PHP Essentials

- Curly braces for all control structures (even single-line).
- Constructor property promotion: `public function __construct(protected readonly X $x) {}`.
- Explicit return types and parameter type hints on all methods.
- `===` over `==` unless loose comparison is intentional.
- `match()` over long `switch()` blocks.
- `str_contains()` / `str_starts_with()` / `str_ends_with()` over `strpos() === 0`.
- Null-safe `?->` and null coalescing `??` over explicit null checks.
- Trailing commas on multiline arrays, function calls, constructor params.

See `docs/conventions.md` (#3) for the complete PHP style guide.

=== deployments ===

# Deployment Essentials

- Queue worker: `php artisan queue:work`
- Scheduler: `* * * * * cd /path && php artisan schedule:run >> /dev/null 2>&1`
- Storage link: `php artisan storage:link`
- See `docs/infrastructure/` for full deployment reference.

=== tests ===

# Testing Essentials

- Every change must be tested. Run `php artisan test --compact --filter=TestName`.
- Tests follow module-first structure: `tests/{Feature,Unit}/{Module}/{Submodule}/{Name}Test.php`.
- Use `LazilyRefreshDatabase` over `RefreshDatabase`.
- Use `assertModelExists()` over `assertDatabaseHas()`.
- See `docs/infrastructure/testing.md` for the complete testing guide.


# ADR-014: Unified Health Check over Separate Commands

## Status
Accepted

## Context
The system had multiple commands serving similar purposes:

1. `system:health` — runtime health check (12 checks: PHP, extensions, memory, database,
   storage, disk, queue, cache, app key, storage link, maintenance mode)
2. `setup:install` — pre-install readiness audit via `EnvironmentAuditor` (PHP, extensions,
   recommended extensions, directory permissions, database connectivity, terminal support)
3. `setup:reset` — token regeneration for setup wizard

A developer evaluating the system needed to run `setup:install` to see the environment audit,
but this command also ran provisioning (migrations, seeding, cache clear) which modified the
database and required confirmation. There was no read-only way to check system readiness.

Proposals considered:
1. **New `setup:check` command**: Clean separation but adds another command to remember.
   Overlaps significantly with `system:health` (both check PHP, extensions, database).
2. **Extend `system:health`**: Add the missing checks (setup status, migration count,
   recommended extensions). One command covers both pre-install and runtime health. Add
   `--check-only` to `setup:install` for the full setup-specific audit without provisioning.
3. **Extend `setup:install` with `--audit-only`**: Same as option 2 but keeps everything
   in setup domain.

## Decision
Option 2 — extend `system:health` with the missing checks and add `--check-only` to
`setup:install`. This gives developers:

- **One command to learn**: `system:health` works before and after installation.
- **One flag for pre-flight**: `setup:install --check-only` runs the full setup audit
  without modifying anything.
- **No new commands**: Both existing commands are enhanced, not replaced.

New checks added to `system:health`:

| Check | Source | Pre-install | Post-install |
|-------|--------|:---:|:---:|
| Setup Status | `setups` table | ✅ Shows "not installed" | ✅ Shows "installed (N steps)" |
| Migration Status | `migrations` table vs files | ✅ Shows pending count | ✅ Shows "up to date" |
| Recommended Extensions | `config/setup.requirements.recommended_extensions` | ✅ Warns if missing | ✅ Warns if missing |

## Consequences
- **Positive**: One command (`system:health`) works before installation, after setup, and in
  production. New developers run one command to understand system state.
- **Positive**: `setup:install --check-only` is safe to run at any time — no side effects.
- **Positive**: `setup:reset` now suggests `system:health` when run on an installed system,
  reducing user confusion.
- **Positive**: No new commands to learn or maintain.
- **Negative**: `system:health` depends on the `setups` and `migrations` tables existing.
  Before any database setup, these checks degrade gracefully with warnings.
- **Negative**: The migration check compares file names against the database. Custom or
  third-party migration files (e.g., from packages) could produce false positives.

## References
- `app/Domain/Core/Console/Commands/HealthCommand.php`
- `app/Domain/Setup/Console/Commands/SetupInstallCommand.php`
- `app/Domain/Setup/Console/Commands/SetupResetCommand.php`
- `docs/domain/setup.md`
- `docs/domain/core.md`

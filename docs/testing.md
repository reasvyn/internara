# Testing

## Framework

Internara uses **Pest 4** for testing. Feature tests use `LazilyRefreshDatabase`. PHPStan (level 8) handles static analysis, and Laravel Pint enforces code style.

## Test Structure

```
tests/
├── Arch/          Architecture enforcement tests
├── Unit/          Isolated logic tests (no database by default)
│   ├── Casts/
│   ├── Entities/  Pure PHP domain objects — no database needed
│   ├── Exceptions/
│   ├── Models/    Eloquent model behavior
│   ├── Services/
│   └── Support/
└── Feature/       End-to-end workflow tests grouped by domain
```

### Suite Configuration

- **Feature** — `TestCase` + `LazilyRefreshDatabase` (database refreshed per test)
- **Unit** — `TestCase` only (individual tests can opt in with `RefreshDatabase`)
- **Arch** — Standalone, no database configuration

### Base TestCase

`tests/TestCase.php` sets up two things:

1. Creates a `.installed` lock file at the project root — this bypasses the setup wizard during tests
2. Registers `Gate::before` — grants all permissions to users with the `super_admin` role

## Commands

```bash
composer test              # Clear cache + run all tests
composer test:arch         # Architecture tests only
composer test:feature      # Feature tests only
composer test:unit         # Unit tests only
composer quality           # Lint + static analysis + architecture tests
```

## Conventions

- `declare(strict_types=1)` at the top of every test file
- Test files follow: `tests/{Suite}/{Domain}/{Name}Test.php`
- Feature tests are grouped by domain context (e.g. `AppSettings/`, `UserManager/`)

## Factories

Every model has a corresponding factory in `database/factories/`. Use `fake()` or `$this->faker` for test data.

| State | Purpose |
|---|---|
| `->requiresSetup()` | Sets `setup_required = true` |
| `->locked($reason)` | Sets `locked_at` and `locked_reason` |
| `->unverified()` | Sets `email_verified_at = null` |
| `->withPassword($pw)` | Hashes and sets a custom password |

## Test Environment

See `phpunit.xml` for the full test environment configuration. Feature tests use `LazilyRefreshDatabase` with SQLite in-memory, and all external services (cache, queue, mail, session) are replaced with `array`/`sync` drivers. Pulse is disabled during tests.

# Testing

## Framework
Pest 4 for testing. Feature tests use `LazilyRefreshDatabase`. Laravel Pint enforces code style.

## Test Structure
```
tests/
├── Arch/          Architecture enforcement tests
├── Unit/          Isolated logic tests (no database by default)
│   ├── Actions/   Action business logic
│   ├── Casts/
│   ├── Entities/  Pure PHP domain objects — no database needed
│   ├── Exceptions/
│   ├── Models/    Eloquent model behavior
│   ├── Services/
│   └── Support/
└── Feature/       End-to-end workflow tests grouped by domain
```

## Suite Configuration
- Feature — `TestCase` + `LazilyRefreshDatabase` (database refreshed per test)
- Unit — `TestCase` only (individual tests can opt in with `RefreshDatabase`)
- Arch — Standalone, no database configuration

## Base TestCase
`tests/TestCase.php` sets up:
1. Creates or updates a `Setup` record with `is_installed = true` — bypasses setup wizard
2. Registers `Gate::before` — grants all permissions to users with `super_admin` role

## Commands
| Command | Purpose |
|---|---|
| `composer test` | Clear cache + run all tests |
| `composer test:arch` | Architecture tests only |
| `composer test:feature` | Feature tests only |
| `composer test:unit` | Unit tests only |
| `composer quality` | Lint + static analysis + architecture tests |

## Conventions
- `declare(strict_types=1)` at the top of every test file
- Test files follow: `tests/{Suite}/{Domain}/{Name}Test.php`
- Feature tests are grouped by domain context (e.g. `AppSettings/`, `UserManager/`)

## Factory States
| State | Purpose |
|---|---|
| `->requiresSetup()` | Sets `setup_required = true` |
| `->locked($reason)` | Sets `locked_at` and `locked_reason` |
| `->withPassword($pw)` | Hashes and sets a custom password |

## Test Environment
See `phpunit.xml` for the full test environment configuration. Feature tests use `LazilyRefreshDatabase` with SQLite in-memory, and all external services (cache, queue, mail, session) are replaced with `array`/`sync` drivers. Pulse is disabled during tests.

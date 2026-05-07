# Testing

## Framework

Pest PHP is the primary testing framework. PHPStan (level 8) provides static analysis. Laravel Pint enforces code style.

## Directory Structure

```
tests/
├── Arch/                              # Automated architecture enforcement
│   └── {Name}ArchTest.php
│
├── Unit/{Layer}/                      # Isolated business logic
│   └── {TestName}Test.php             # Layer = Casts, Exceptions, Models, Support, ...
│
├── Feature/{FeatureName}/             # End-to-end user workflows
│   └── {TestName}Test.php             # FeatureName = AdminRecovery, AppSettings, ...
│
├── Pest.php                           # Pest config & global helpers
└── TestCase.php                       # Base TestCase
```

## Test Categories

### Architectural Tests (`tests/Arch/{Name}ArchTest.php`)

Enforce layer separation and coding standards automatically:

- All models use UUIDs
- All Actions have an `execute()` method
- UI components contain no business logic
- Models do not depend on Actions
- Controllers are thin (request/response only)

### Unit Tests (`tests/Unit/{Layer}/{TestName}Test.php`)

Test a single class or function in isolation. Grouped by the layer under test:

| Directory | Tests for |
|---|---|
| `Unit/Models/` | Eloquent model behavior |
| `Unit/Support/` | Support utilities (Settings, AppInfo, Locale, etc.) |
| `Unit/Casts/` | Custom Eloquent casts |
| `Unit/Exceptions/` | Exception classes |

### Feature Tests (`tests/Feature/{FeatureName}/{TestName}Test.php`)

Test a complete user-facing workflow end-to-end. Grouped by feature name:

| Directory | Tests for |
|---|---|
| `Feature/AdminRecovery/` | Admin account recovery workflows |
| `Feature/AppInstaller/` | Setup installation & reset |
| `Feature/AppSettings/` | Application settings CRUD |
| `Feature/Logger/` | Logger dual-channel behavior |

## Running Tests

```bash
# All tests
php artisan test

# Specific suite
vendor/bin/pest tests/Arch

# Static analysis
composer analyse           # PHPStan level 8
composer analyse:strict    # PHPStan level max

# Code quality
composer quality           # lint + analyse + arch tests
composer quality:full      # format + strict analyse + coverage
```

## Database

Tests use SQLite `:memory:` with the `LazilyRefreshDatabase` trait. Every model has a corresponding factory in `database/factories/`.

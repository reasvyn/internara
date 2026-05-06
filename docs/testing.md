# Testing

## Framework

Pest PHP is the primary testing framework. PHPStan (level 8) provides static analysis. Laravel Pint enforces code style.

## Test Categories

### Architectural Tests (`tests/Arch/`)

Enforce layer separation and coding standards automatically:

- All models use UUIDs
- All Actions have an `execute()` method
- UI components contain no business logic
- Models do not depend on Actions
- Controllers are thin (request/response only)

### Quality Tests (`tests/Quality/`)

Detect pitfalls static analysis misses:

- N+1 query patterns
- Missing pagination on large result sets
- Insecure mass assignment
- Hardcoded paths or raw SQL

### Feature & Unit Tests

- **Feature** — end-to-end user workflows
- **Unit** — isolated business logic

## Running Tests

```bash
# All tests
php artisan test

# Specific suite
vendor/bin/pest tests/Arch
vendor/bin/pest tests/Quality

# Static analysis
composer analyse           # PHPStan level 8
composer analyse:strict    # PHPStan level max

# Code quality
composer quality           # lint + analyse + arch tests
composer quality:full      # format + strict analyse + coverage
```

## Database

Tests use SQLite `:memory:` with the `LazilyRefreshDatabase` trait. Every model has a corresponding factory in `database/factories/`.

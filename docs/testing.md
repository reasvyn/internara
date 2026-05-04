# Testing Documentation

## 1. Strategy

Internara uses [Pest PHP](https://pestphp.com/) for automated testing. Our strategy focuses on both **Functional Correctness** and **Architectural Integrity**.

## 2. Test Categories

### Architectural Tests (`tests/Arch/`)
Enforces the 3S Doctrine automatically by verifying layer separation and coding standards.
- **Layer Separation**: Ensures UI components don't contain business logic and Models don't depend on Actions.
- **Standard Enforcement**: Ensures all models use UUIDs and all Actions have an `execute()` method.

### Quality Tests (`tests/Quality/`)
Checks for common pitfalls that static analysis might miss:
- **Performance**: Detects potential N+1 queries or missing pagination.
- **Security**: Checks for insecure mass assignment or raw SQL usage.
- **Stability**: Detects hardcoded paths or unhandled failure states.

### Feature & Unit Tests
- **Feature**: Verifies end-to-end user workflows (e.g., Student clock-in).
- **Unit**: Verifies isolated business logic (e.g., Status calculation logic).

## 3. Tooling

- **Pest PHP**: Primary testing framework.
- **PHPStan**: Static analysis (Level 8) to ensure type safety.
- **Laravel Pint**: Automatic code style enforcement.
- **LazilyRefreshDatabase**: Laravel built-in trait for test database isolation.

## 4. CI/CD Workflow

Every Pull Request triggers a GitHub Actions workflow that runs:
1. **Linting**: Pint style check.
2. **Static Analysis**: PHPStan strict analysis.
3. **Arch Tests**: Structural validation.
4. **Feature/Unit Tests**: Functional validation.

## 5. Execution

```bash
# Run all tests
php artisan test

# Run specific suite
./vendor/bin/pest tests/Arch
./vendor/bin/pest tests/Quality

# Static Analysis
composer analyse
```

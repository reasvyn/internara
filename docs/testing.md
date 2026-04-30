# Testing Documentation

## Overview
Internara utilizes [Pest PHP](https://pestphp.com/) for high-velocity, readable testing. Our strategy focuses on both **Functional Correctness** and **Architectural Integrity**.

## 1. Test Categories

### Architectural Tests (`tests/Arch`)
Enforces the 3S Doctrine automatically. These tests ensure:
- Controllers stay thin.
- Actions remain stateless (no instance properties beyond constructor-injected dependencies).
- Models use UUIDs and contain business rules.
- Proper layer separation is maintained.

#### Test Structure (Split by Concern)
```
tests/Arch/
├── GlobalCodingStandardsTest.php    # Strict types, no debug functions
├── Layers/
│   └── LayerSeparationTest.php     # Layer dependency rules
├── Models/
│   └── ModelStandardsTest.php      # UUIDs, traits, no side effects
├── Actions/
│   ├── ActionStandardsTest.php     # execute() method, stateless
│   └── ActionStatelessTest.php    # No mutable state
├── Controllers/
│   └── ControllerStandardsTest.php # Thin controllers, delegation
├── OptionalLayers/
│   ├── RepositoryStandardsTest.php # Read-only, eloquent returns
│   ├── EventStandardsTest.php      # Past tense naming, Dispatchable
│   └── ListenerStandardsTest.php  # Handle method, no models
├── Requests/
│   └── RequestStandardsTest.php   # FormRequest, rules method
└── Services/
    └── ServiceStandardsTest.php    # No business rules
```

### Quality Tests (`tests/Quality`)
Ensures code stability, performance, and security:
- **CodeStabilityTest**: Hardcoded paths, SQL injection, silent failures
- **PerformanceTest**: N+1 queries, missing pagination, inefficient checks
- **SecurityTest**: Mass assignment, input validation, sensitive data in logs

### Feature Tests (`tests/Feature`)
Verifies end-to-end workflows (Use Cases). Every `Action` must have a corresponding feature test.

### Unit Tests (`tests/Unit`)
Verifies pure business logic within Models or Support classes.

## 2. Test Tools

### AppTestOrchestrator
The `App\Support\Testing\AppTestOrchestrator` handles the lifecycle of the test environment.
- **bootstrap()**: Prepares the database (migrations + seeding).
- **teardown()**: Cleans up after testing.

## 3. Running Tests
```bash
# Run all tests
./vendor/bin/pest

# Run only arch tests
./vendor/bin/pest tests/Arch

# Run quality tests (stability, performance, security)
./vendor/bin/pest tests/Quality

# Run with coverage (requires Xdebug)
./vendor/bin/pest --coverage

# Run specific test suite
./vendor/bin/pest --testsuite=Quality
```

## 4. Composer Scripts
```bash
# Quick quality check (lint + static analysis + arch tests)
composer quality

# Full quality check (format + strict analysis + coverage)
composer quality:full

# Test with coverage
composer test:coverage

# Run only architectural tests
composer test:arch

# Run only feature tests
composer test:feature

# Run only unit tests
composer test:unit
```

## 5. Static Analysis
```bash
# Run PHPStan (level 8)
composer analyse

# Run PHPStan with max level
composer analyse:strict
```

## 6. Mandatory Regression (Workflow 4)
According to `AGENTS.md`, every bug fix **must** include a reproduction test that prevents recurrence.

## 7. Mandatory Regression (Workflow 4)
According to `AGENTS.md`, every bug fix **must** include a reproduction test that prevents recurrence.

## 8. CI Pipeline
The project uses GitHub Actions for continuous integration:
- **Quality job**: Pint (code style) + PHPStan (static analysis)
- **Architecture job**: Architectural tests (layer separation)
- **Tests job**: Feature & Unit tests with coverage (min 80%)
- **Security job**: Trivy vulnerability scanner

All jobs must pass before merging to main/develop branches.

## 9. Known Issue
Tests currently cannot execute due to a fatal error from legacy module code. See `.agents/todo/2026-04-30-fix-checklist-accuracy-and-test-blocker.md` — Step 1.

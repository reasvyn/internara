# Testing Documentation

## Overview
Internara utilizes [Pest PHP](https://pestphp.com/) for high-velocity, readable testing. Our strategy focuses on both **Functional Correctness** and **Architectural Integrity**.

## 1. Test Categories

### Architectural Tests (`tests/Arch`)
Enforces the 3S Doctrine automatically. These tests ensure:
- Controllers stay thin.
- Actions remain stateless.
- Models use UUIDs and contain business rules.
- Proper layer separation is maintained.

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

# Run with coverage (requires Xdebug)
./vendor/bin/pest --coverage
```

## 4. Mandatory Regression (Workflow 4)
According to `AGENTS.md`, every bug fix **must** include a reproduction test that prevents recurrence.

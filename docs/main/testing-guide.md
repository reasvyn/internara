# Testing Guide

This document outlines the philosophy, conventions, and workflow for writing tests within the
Internara project. It acts as the central entry point for our testing infrastructure, which is
supported by a comprehensive library of technical deep-dives.

## Table of Contents

- [Testing Philosophy](#testing-philosophy)
    - [Unit vs. Feature Tests](#unit-vs-feature-tests)
- [Architecture & Directory Structure](#architecture--directory-structure)
    - [Layer-Based Placement (Mandatory)](#layer-based-placement-mandatory)
- [The Internara Testing Stack](#the-internara-testing-stack)
- [Creating & Running Tests](#creating--running-tests)
- [Technical Reference Catalog](#technical-reference-catalog)
- [Pest Deep-Dive Catalog](#pest-deep-dive-catalog)
- [Project-Specific Patterns](#project-specific-patterns)
    - [Asserting Exceptions & Localization](#asserting-exceptions--localization)

---

## Testing Philosophy

- **TDD First**: Embrace writing tests alongside feature development.
- **Comprehensive Coverage**: Every new feature, bug fix, or modification must be accompanied by
  relevant tests.
- **Sustainable Suites**: Tests are primary artifacts. Maintain and update them rather than
  deleting.

### Unit vs. Feature Tests

| Type        | Focus                 | Goal                                      | Characteristics                           |
| :---------- | :-------------------- | :---------------------------------------- | :---------------------------------------- |
| **Unit**    | Isolated components   | Verify logic in absolute isolation.       | Fast, no DB, mocks all dependencies.      |
| **Feature** | Component integration | Verify end-to-end user stories/processes. | Slower, uses DB, simulates HTTP/Livewire. |

---

## Architecture & Directory Structure

Tests are organized into the root `/tests` directory for application-wide concerns and module-level
`tests` directories for domain-specific logic.

### Layer-Based Placement (Mandatory)

**Crucially, test files must always reside in a subdirectory within `Feature/` or `Unit/`,
reflecting the architectural layer being tested.** Placing tests directly under the parent directory
is prohibited.

- **Correct**: `modules/User/tests/Feature/Services/UserServiceTest.php`
- **Correct**: `modules/Assessment/tests/Unit/Models/AssessmentTest.php`
- **Incorrect**: `modules/User/tests/Feature/UserTest.php`

**Structure Overview:**

- `/tests/Feature/{Layer}`: App-level feature tests (e.g., `Http/Controllers`).
- `/tests/Unit/{Layer}`: App-level unit tests (e.g., `Models`).
- `modules/{Module}/tests/Feature/{Layer}`: Module feature tests (e.g., `Services`, `Livewire`).
- `modules/{Module}/tests/Unit/{Layer}`: Module unit tests (e.g., `Models`, `Support`).

---

## The Internara Testing Stack

- **Core Framework**: [Pest v4+](https://pestphp.com/) (Functional style).
- **Mocking**: Mockery & Laravel Fakes.
- **Browser Testing**: Pest Browser (Playwright-based).
- **Architecture**: Pest Arch Plugin.

---

## Creating & Running Tests

### Creating Tests

```bash
# Application-Level (Always specify subdirectory)
php artisan make:test Feature/Http/Controllers/ExampleTest
php artisan make:test Unit/Models/ExampleTest --unit

# Module-Level
php artisan module:make-test Services/ExampleTest User --feature
php artisan module:make-test Models/ExampleTest User
```

### Running Tests

```bash
# All tests
php artisan test --parallel

# Filter by Module
php artisan test --filter=User

# Filter by Layer/File
php artisan test modules/User/tests/Feature/Services/UserServiceTest.php
```

---

## Technical Reference Catalog

For detailed technical guidance on specific testing domains, refer to our localized documentation:

- [**General Testing Overview**](../tests/testing.md): Environment setup and basic concepts.
- [**HTTP & API Testing**](../tests/http-tests.md): Making requests, assertions, and JSON APIs.
- [**Database Testing**](../tests/database-testing.md): Factories, seeders, and DB assertions.
- [**Livewire Testing**](../tests/livewire-tests.md): Testing reactive components and state.
- [**Console Testing**](../tests/console-tests.md): Verifying Artisan commands and I/O.
- [**Mocking & Fakes**](../tests/mocking.md): Isolating dependencies and time manipulation.

---

## Pest Deep-Dive Catalog

Explore the full power of Pest through these focused guides:

- [**Writing Tests**](../tests/pestphp/writing-tests.md): Basic structure and the `it()` syntax.
- [**Expectations API**](../tests/pestphp/expectations.md): The comprehensive list of assertions.
- [**Hooks**](../tests/pestphp/hooks.md): `beforeEach`, `afterEach`, and setup logic.
- [**Datasets**](../tests/pestphp/datasets.md): Matrix testing and shared data.
- [**Architecture Testing**](../tests/pestphp/arch-testing.md): Enforcing code rules.
- [**Browser Testing**](../tests/pestphp/browser-testing.md): Full browser automation.
- [**Mutation Testing**](../tests/pestphp/mutation-testing.md): Verifying test quality.
- [**Stress Testing**](../tests/pestphp/stress-testing.md): Performance and reliability.

---

## Project-Specific Patterns

### Asserting Exceptions & Localization

Internara uses a custom `AppException` that leverages translation keys. Tests must verify both the
logic and the localization integrity.

```php
// modules/User/tests/Feature/Services/UserServiceTest.php

use Modules\User\Services\UserService;
use Modules\Exception\AppException;

uses(RefreshDatabase::class);

test('it throws translated exception when deleting super-admin', function () {
    $superAdmin = User::factory()->create()->assignRole('super-admin');
    $service = app(UserService::class);

    // Retrieve the expected translation
    $expectedMessage = __('user::exceptions.super_admin_cannot_be_deleted');

    expect(fn() => $service->delete($superAdmin))->toThrow(AppException::class, $expectedMessage);
});
```

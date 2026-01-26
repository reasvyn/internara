# Testing Guide

This document outlines the philosophy, conventions, and workflow for writing tests within the
Internara project. It acts as the central entry point for our testing infrastructure.

> **Spec Alignment:** Testing must verify the fulfillment of requirements defined in the
> **[Internara Specs](../internal/internara-specs.md)**, including **Mobile-First** UI behavior,
> **Multi-Language** integrity, and **Role-Based** access control.

---

## Testing Philosophy

- **TDD First**: Embrace writing tests alongside feature development.
- **Comprehensive Coverage**: Every new feature, bug fix, or modification must be accompanied by
  relevant tests.
- **Modular Isolation**: Tests must respect the same isolation boundaries as the application code. A
  test in `Module A` must not directly instantiate or assert against concrete models in `Module B`.
  Use **Service Contracts** or specific module factories to maintain decoupling.
- **Verification & Validation (V&V)**: Tests serve as the technical proof that the system meets the
  authoritative specs.

### Unit vs. Feature Tests

| Type        | Focus                 | Goal                                      | Characteristics                           |
| :---------- | :-------------------- | :---------------------------------------- | :---------------------------------------- |
| **Unit**    | Isolated components   | Verify logic in absolute isolation.       | Fast, no DB, mocks all dependencies.      |
| **Feature** | Component integration | Verify end-to-end user stories/processes. | Slower, uses DB, simulates HTTP/Livewire. |

---

## Layer-Based Placement (Mandatory)

Tests must reflect the architectural layer being tested. Placing tests directly under `Feature/` or
`Unit/` is prohibited.

- **Feature Tests:** `modules/{Module}/tests/Feature/{Livewire|Services|Api}`
- **Unit Tests:** `modules/{Module}/tests/Unit/{Models|Enums|Support}`

---

## Mandatory Testing Patterns

### 1. Multi-Language Verification

Every user-facing output must be tested for localization across all supported locales (ID/EN).

```php
test('it returns localized error for unauthorized access', function () {
    app()->setLocale('id');
    expect(__('exception::messages.unauthorized'))->toBe('Akses tidak diizinkan.');

    app()->setLocale('en');
    expect(__('exception::messages.unauthorized'))->toBe('Unauthorized access.');
});
```

### 2. Role-Based Access Control (RBAC)

Verify that access is restricted to the specific user roles defined in the specs.

```php
test('staff can access administration but student cannot', function () {
    $staff = User::factory()->create()->assignRole('staff');
    $student = User::factory()->create()->assignRole('student');

    actingAs($staff)->get(route('admin.dashboard'))->assertOk();
    actingAs($student)->get(route('admin.dashboard'))->assertForbidden();
});
```

### 3. Asserting Exceptions

Internara uses standardized exception naming. Tests must verify the translation key and message.

```php
test('it throws translated exception using module-specific key', function () {
    $service = app(JournalService::class);
    $lockedJournal = Journal::factory()->create(['is_locked' => true]);

    $expectedMessage = __('journal::exceptions.locked');

    expect(fn() => $service->update($lockedJournal, []))->toThrow(
        JournalLockedException::class,
        $expectedMessage,
    );
});
```

### 4. Cross-Module Isolation

To maintain modular portability, tests must strictly respect domain boundaries.

- **No Concrete Imports**: Avoid importing concrete classes (Models, Services, Actions) from other
  modules.
- **Contract Resolution**: Always resolve cross-module dependencies via their **Service Contracts**
  using `app(Contract::class)`.
- **State Setup**: Use **Factories** from other modules to set up necessary database state for
  Feature tests. Avoid direct instantiation (`new Model()`) of foreign models.
- **Assertion Boundaries**: Assertions should focus on the behavior of the module being tested.
  Avoid asserting against the internal state of foreign models directly; instead, verify outcomes
  through the current module's logic or public contracts.

---

## The Internara Testing Stack

- **Core Framework**: [Pest v4+](https://pestphp.com/).
- **Mocking**: Mockery & Laravel Fakes.
- **Architecture**: Pest Arch Plugin (Enforcing modular isolation).

---

## Running Tests

```bash
# All tests (Parallel)
php artisan test --parallel

# Filter by Module
php artisan test --filter=User
```

---

_Tests are not just a safety net; they are the executable documentation of our commitment to the
Internara Specs._

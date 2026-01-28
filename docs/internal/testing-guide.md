# Testing & Verification Guide: V&V Framework

This document formalizes the **Verification & Validation (V&V)** protocols for the Internara
project, adhering to **IEEE Std 1012** and **ISO/IEC 29119** (Software Testing). It defines the
methodologies for ensuring that software artifacts are technically correct (Verification) and
fulfill the authoritative specifications (Validation).

> **Spec Alignment:** Every test suite must validate the requirements defined in the
> **[Internara Specs](../internal/internara-specs.md)**, including **Mobile-First** responsiveness,
> **Multi-Language** integrity, and **Role-Based** security invariants.

---

## 1. V&V Philosophy: TDD-Driven Engineering

Internara adopts a **TDD-First** methodology, treating tests as executable behavioral
specifications.

- **Traceability**: Every test must be traceable to a specific requirement in the SSoT or an
  architectural invariant.
- **Independence**: Tests must verify behavior without knowledge of the internal implementation
  details of other modules.
- **Continuous Verification**: Tests are executed automatically within the CI pipeline to prevent
  architectural or functional regression.

---

## 2. Testing Levels (The V-Model View)

| Level           | Focus                    | Objective                                | Implementation                      |
| :-------------- | :----------------------- | :--------------------------------------- | :---------------------------------- |
| **Unit**        | Component Isolation      | Verify logic in mathematical isolation.  | Pest (Mocking all dependencies)     |
| **Integration** | Service/Module Contract  | Verify inter-module communication.       | Feature tests via Service Contracts |
| **System**      | End-to-End User Flow     | Validate fulfillment of user stories.    | Feature tests (HTTP/Livewire)       |
| **Architecture**| Structural Invariants    | Enforce modular isolation policies.      | Pest Arch Plugin                    |

---

## 3. Mandatory Testing Patterns

### 3.1 Architecture Invariants (Isolation Enforcement)

To maintain the **Modular Monolith** integrity, we utilize **Architecture Testing** to prevent
unauthorized cross-module coupling.

```php
arch('module isolation')
    ->expect('Modules\Internship')
    ->not->toUse('Modules\User\Models')
    ->because('Domain modules must never interact with external models directly.');
```

### 3.2 Localization (i11n) Validation

Every user-facing artifact must be verified across all supported locales (ID/EN).

```php
test('it returns localized validation error', function () {
    app()->setLocale('id');
    expect(__('validation.required', ['attribute' => 'nama']))->toBe('nama wajib diisi.');

    app()->setLocale('en');
    expect(__('validation.required', ['attribute' => 'name']))->toBe('The name field is required.');
});
```

### 3.3 Access Control (RBAC) Verification

Access rights must be verified for every role defined in the **[Internara Specs](../internal/internara-specs.md)**.

```php
test('unauthorized roles are forbidden from accessing management', function () {
    $student = User::factory()->create()->assignRole('student');
    $instructor = User::factory()->create()->assignRole('instructor');

    actingAs($student)->get(route('admin.dashboard'))->assertForbidden();
    actingAs($instructor)->get(route('admin.dashboard'))->assertOk();
});
```

### 3.4 Exception & Fault Handling

Tests must verify that the system fails securely and provides translated feedback.

```php
test('it throws translated domain exception', function () {
    $service = app(JournalService::class);
    $lockedJournal = Journal::factory()->create(['status' => 'locked']);

    expect(fn() => $service->update($lockedJournal, []))
        ->toThrow(JournalLockedException::class, __('journal::exceptions.locked'));
});
```

---

## 4. Test Construction Standards

### 4.1 Layered Placement
Tests must be located within the module they verify, following the internal 3-tier structure:
- **Feature Tests**: `modules/{Module}/tests/Feature/{Livewire|Services|Api}`
- **Unit Tests**: `modules/{Module}/tests/Unit/{Models|Enums|Support}`

### 4.2 Cross-Module State Setup
- **No Direct Model Instantiation**: Do not use `new Model()` for foreign entities.
- **Factory Resolution**: Use factories resolved from the target module to ensure valid state
  initialization without coupling.

---

## 5. Execution & Quality Gates

Verification is mandatory before any repository synchronization.

```bash
# Parallel Execution (Verification Gate)
php artisan test --parallel

# Coverage Audit (Validation Gate)
php artisan test --coverage --min=90
```

---

_Tests are the formal proof of our engineering rigor. An artifact without verification is
considered incomplete and non-compliant with the Internara standards._
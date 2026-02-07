# Livewire Verification: Presentation Layer Standards

This document formalizes the **Presentation Verification** protocols for the Internara project,
standardized according to **ISO 9241-210** (Human-Centered Design). Livewire tests ensure that
interactive interfaces, reactive state transitions, and logic delegation invariants behave
deterministically.

> **Governance Mandate:** All presentation logic must demonstrate compliance with the
> **[Thin Component Mandate](../architecture.md)** through rigorous automated verification.

---

## 1. Localization & UI Invariants

Verification artifacts must ensure that the user interface correctly implements the
**Multi-Language** and **Identity** requirements defined in the System Requirements Specification.

### 1.1 Multi-Language Verification [SYRS-NF-403]

```php
test('it renders the authoritative localized baseline', function () {
    app()->setLocale('id');

    Livewire::test(Dashboard::class)->assertSee(__('core::ui.welcome'));
});
```

---

## 2. Functional & Transactional Verification

Verification must confirm that user interactions are correctly delegated to the **Service Layer**
and result in valid systemic state transitions.

### 2.1 Orchestration Verification

```php
test('it delegates domain orchestration to the service contract', function () {
    Livewire::test(CreateInternship::class)
        ->set('placement_id', 'uuid-string')
        ->call('orchestrate')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('internships', ['placement_id' => 'uuid-string']);
});
```

---

## 3. Access Control Verification (RBAC)

Verification must confirm that the presentation layer strictly enforces the **Least Privilege**
protocol defined in the System Requirements Specification.

```php
test('unauthorized roles are prohibited from executing restricted actions', function () {
    $student = User::factory()->create()->assignRole('student');

    actingAs($student)->livewire(AdministrativeDashboard::class)->assertForbidden();
});
```

---

## 4. Construction Invariants for Livewire Tests

- **Logic Invariant**: Direct verification of business rules within Livewire tests is prohibited.
  Verification must focus on the correct **Delegation** to the service layer.
- **Responsiveness Audit**: Verification of state-dependent UI visibility (e.g., Drawer toggling).
- **V&V Mandatory**: All presentation verification must pass the **`composer test`** gate.

---

_Livewire verification ensures that the Internara presentation layer remains resilient, accessible,
and architecturally pure across its modular ecosystem._

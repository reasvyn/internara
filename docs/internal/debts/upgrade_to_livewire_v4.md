# Migration Manual: Evolutionary Upgrade to Livewire v4

This document constitutes the definitive technical guide for the migration of the Internara
presentation baseline from Livewire v3 to v4. This evolution is a prioritized technical debt item
targeted for stabilization in the **v0.8.x** developmental series.

> **Governance Mandate:** This migration must demonstrate 100% preservation of the **Mobile-First**
> and **Localization** invariants defined in the authoritative
> **[System Requirements Specification](../../internal/system-requirements-specification.md)**.

---

## 🛠 Phase 1: Environment & Dependency Decommissioning

Livewire v4 introduces native support for structural patterns previously orchestrated via external
dependencies.

1.  **Baseline Synchronization**:
    ```bash
    composer require livewire/livewire:^4.0
    ```
2.  **Modular Cleanup**: Decommission the **Volt** dependency, as SFC (Single File Component) logic
    is now integrated into the core engine.
    - **Remove Package**: `composer remove livewire/volt`
    - **Retire Provider**: `rm app/Providers/VoltServiceProvider.php`
    - **Config Audit**: Ensure removal of `VoltServiceProvider` from `bootstrap/providers.php`.

---

## ⚡ Phase 2: Configuration Baseline Mapping

Synchronize the `config/livewire.php` baseline to reflect the v4 technical nomenclature.

| Attribute (v3)       | Attribute (v4)            | Engineering Note           |
| :------------------- | :------------------------ | :------------------------- |
| `'layout'`           | `'component_layout'`      | Baseline: `'layouts::app'` |
| `'lazy_placeholder'` | `'component_placeholder'` |                            |
| `'smart_wire_keys'`  | (Implicit Invariant)      | Enabled by default.        |

---

## 💻 Phase 3: Construction-Level Refactoring

### 3.1 Namespace Convergence

- **Action**: Globally migrate from `Livewire\Volt\Component` to the unified `Livewire\Component`.

### 3.2 Routing Orchestration

Utilization of the specialized `Route::livewire` macro is mandatory for full-page component
declaration to ensure optimized lifecycle management.

### 3.3 Localization (i18n) Invariant

Verification of the translation bridge reactivity is mandatory following the adoption of
`wire:navigate` and asynchronous actions.

---

## 🧪 Phase 4: Verification & Validation (V&V)

The migration is only considered certified when it satisfies the following gates:

1.  **Presentation Audit**: Verification of the `UI` module components, specifically the hydration
    logic of the `x-ui::file` orchestrator.
2.  **Mobile-First Validation**: Ergonomic verification of the responsive sidebar and drawer
    behavior under the new navigation baseline.
3.  **Full Verification Suite**:
    ```bash
    composer test
    ```

---

## ✨ Phase 5: Capability Adoption

Evolutionary adoption of new engine capabilities to satisfy performance requirements:

- **Async Interactions**: Implementation of `wire:click.async` for non-blocking UI orchestration.
- **Component Islands**: Refactoring of dashboard analytics using the `@island` directive.

---

_This technical record must be updated upon the identification of any architectural regressions
during the migration lifecycle._

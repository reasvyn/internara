# Migration Manual: Upgrading to Livewire v4

This document serves as the definitive, step-by-step technical guide for migrating the Internara
stack from Livewire v3 to v4. This is a high-priority technical debt item targeted for the
**v0.8.x** series.

> **Spec Alignment:** This migration must preserve the **Mobile-First** responsiveness and 
> **Multi-Language** integrity mandated by the **[Internara Specs](../../internara-specs.md)**.
> All UI refactors must be validated against the specs.

---

## 🛠 Phase 1: Dependency & Cleanup

Livewire v4 brings native support for patterns previously requiring external packages.

1.  **Update Composer**:
    ```bash
    composer require livewire/livewire:^4.0
    ```
2.  **Decommission Volt**: Livewire v4 SFC (Single File Components) uses the same syntax as Volt.
    Volt is no longer needed.
    - Remove package: `composer remove livewire/volt`
    - Delete Provider: `rm app/Providers/VoltServiceProvider.php`
    - Cleanup Config: Remove `VoltServiceProvider` from `bootstrap/providers.php`.
3.  **Clear Artifacts**:
    ```bash
    php artisan optimize:clear
    ```

---

## ⚡ Phase 2: Configuration Mapping

Update `config/livewire.php` to reflect the new internal naming conventions.

| Old Key (v3)         | New Key (v4)              | Note                      |
| :------------------- | :------------------------ | :------------------------ |
| `'layout'`           | `'component_layout'`      | Default: `'layouts::app'` |
| `'lazy_placeholder'` | `'component_placeholder'` |                           |
| `'smart_wire_keys'`  | (Implicit)                | Now `true` by default.    |

---

## 💻 Phase 3: Code-Level Refactoring

### 3.1 Namespace & Imports
- **Action**: Globally replace `use Livewire\Volt\Component;` with `use Livewire\Component;`.

### 3.2 Routing (Mandatory)
Standard `Route::get()` for full-page components is deprecated in favor of the new macro.
```php
// BEFORE: Route::get('/dashboard', Dashboard::class);
// AFTER: Route::livewire('/dashboard', Dashboard::class);
```

### 3.3 Localization & i11n
Ensure that new v4 features (like `wire:navigate` or async actions) do not break the translation 
bridge.
- **Audit**: Verify that `{{ __('key') }}` calls remain reactive after navigation.

---

## 🧪 Phase 4: Modular Verification (QA)

1.  **UI Core Audit**: Verify the `UI` module components. Check if `x-ui::file` handles hydration 
    correctly in v4.
2.  **Mobile-First Check**: Test the responsive sidebar and drawer behavior under the new 
    Livewire navigation system.
3.  **Boundary Testing**: Run the Pest suite:
    ```bash
    php artisan test --parallel
    ```

---

## ✨ Phase 5: New Feature Adoption

1.  **Async Actions**: Optimize non-critical buttons with `wire:click.async`.
2.  **Islands**: Refactor dashboard widgets (e.g., At-Risk Monitoring) using `@island`.
3.  **Loading States**: Use native `data-loading` attributes.

---

_This guide must be updated if any breaking changes are discovered during the initial migration spike._
# Migration Manual: Upgrading to Livewire v4

This document serves as the definitive, step-by-step technical guide for migrating the Internara
stack from Livewire v3 to v4. This is a high-priority technical debt item targeted for the
**v0.8.x** series.

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

Since we are removing Volt, all components using Volt's base class must be updated.

- **Action**: Globally replace `use Livewire\Volt\Component;` with `use Livewire\Component;`.

### 3.2 Routing (Mandatory)

Standard `Route::get()` for full-page components is deprecated in favor of the new macro.

```php
// Search for modular routes: modules/*/routes/web.php
// BEFORE:
Route::get('/dashboard', Dashboard::class);

// AFTER:
Route::livewire('/dashboard', Dashboard::class);
```

### 3.3 The `wire:model` Behavior Shift

In v4, `wire:model` ignores bubbled events from children by default (equivalent to `.self`).

- **Audit**: Check components where `wire:model` is placed on a parent `div` to capture input from
  children.
- **Fix**: Add the `.deep` modifier: `<div wire:model.deep="value">`.

### 3.4 Navigation Directives

- **Action**: Replace all `wire:scroll` with `wire:navigate:scroll`.

### 3.5 View Transitions API

If using `wire:transition`, remove Alpine modifiers like `.opacity` or `.scale`.

- **Action**: Use plain `wire:transition`. Customize via CSS using the native View Transitions API
  if needed.

---

## 🧪 Phase 4: Modular Verification (QA)

1.  **UI Core Audit**: Verify the `UI` module components first. Check if `x-ui::file` and
    `x-ui::choices` handle hydration correctly in v4.
2.  **Auto-Discovery Check**: Run `php artisan app:refresh-bindings`. Ensure that the automatic
    mapping of **Contracts** to implementations still functions during Livewire's hydration cycles.
3.  **Boundary Testing**: Run the Pest suite with focus on cross-module event dispatching:
    ```bash
    php artisan test --parallel
    ```

---

## ✨ Phase 5: New Feature Adoption (Optional but Recommended)

1.  **Async Actions**: Optimize non-critical buttons with `wire:click.async`.
2.  **Islands**: Refactor dashboard widgets (e.g., Statistics) using `@island` to allow independent
    refreshing.
3.  **Loading States**: Replace custom spinners with the native `data-loading` attributes styled via
    Tailwind.

---

_This guide must be updated if any breaking changes are discovered during the initial migration
spike._

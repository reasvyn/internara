# Permission UI Components: Shared Elements

The `Permission` module provides several pre-built UI components to simplify the management of
access control within the Internara dashboard. These components are designed to be reused across
different administrative interfaces.

---

## 1. Role & Permission Selectors

Used in user creation or editing forms to assign access levels.

### 1.1 `x-permission::role-select`

A multi-select component that lists all available system roles.

- **Props**: `wire:model`, `label`, `placeholder`.

### 1.2 `x-permission::permission-list`

A checkbox-style list for selecting individual granular permissions.

- **Usage**: Typically used in advanced role management views.

---

## 2. Conditional Display

While not a standalone "component," we utilize custom Blade directives to hide or show UI elements
based on access.

### 2.1 The `@can` Directive

The standard way to protect buttons or sections.

```blade
@can('user.delete')
    <x-ui::button label="Delete" icon="tabler.trash" color="error" />
@endcan
```

### 2.2 The `x-permission::gate` Component

A specialized component for wrapping larger sections of the UI that require complex authorization
checks.

---

## 3. Best Practices for UI Authorization

1.  **Fail Silently**: If a user doesn't have access, hide the element entirely rather than showing
    a "Disabled" state (unless necessary for UX clarity).
2.  **Breadcrumbs & Nav**: Ensure that sidebar links are also protected via `@can`.
3.  **Localize**: Always use translation keys for any error messages or labels related to
    permissions.

---

_Leveraging these shared components ensures that Internara's administrative interface remains
consistent and that security logic is not duplicated across views._

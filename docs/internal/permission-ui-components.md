# Permission UI Components: Shared Elements

The `Permission` module provides several pre-built UI components to simplify the management of
access control within the Internara dashboard.

> **Spec Alignment:** All UI components must adhere to the **Mobile-First** and **Multi-Language**
> requirements defined in the **[Internara Specs](../internal/internara-specs.md)**.

---

## 1. Role & Permission Selectors

Used in user creation or editing forms to assign access levels.

### 1.1 `x-permission::role-select`

A multi-select component that lists all available system roles (localized).

- **Props**: `wire:model`, `label`.
- **i11n**: Role names are automatically translated via `__('core::roles.{name}')`.

### 1.2 `x-permission::permission-list`

A checkbox-style list for selecting individual granular permissions.

---

## 2. Conditional Display

### 2.1 The `@can` Directive

The standard way to protect buttons or sections.

```blade
@can('user.delete')
    <x-ui::button label="{{ __('ui::actions.delete') }}" icon="tabler.trash" color="error" />
@endcan
```

---

## 3. Best Practices for UI Authorization

1.  **Fail Silently**: If a user doesn't have access, hide the element entirely.
2.  **Breadcrumbs & Nav**: Ensure that sidebar links are also protected via `@can`.
3.  **Localize**: Always use translation keys for any error messages or labels.

---

_Leveraging these shared components ensures that Internara's administrative interface remains consistent._
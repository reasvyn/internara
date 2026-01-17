# Permission UI Components

The `Permission` module provides shared UI components to standardize how authorization data (roles
and permissions) is presented across the application.

## Available Components

### 1. `RoleBadge` (Livewire)

A small, reactive component to display a user's role as a stylized badge.

#### Features:

- **Color Coding:** Automatically assigns colors based on role (e.g., Error for `super-admin`,
  Primary for `admin`).
- **Size Support:** Supports standard DaisyUI sizes (`xs`, `sm`, `md`, `lg`).
- **Flexible Input:** Accepts either a `Role` model instance or a string name.

#### Usage:

```blade
{{-- Passing a string --}}
<livewire:permission::role-badge role="super-admin" size="sm" />

{{-- Passing a Role model --}}
<livewire:permission::role-badge :role="$user->roles->first()" size="md" />
```

---

## Technical Details

- **Namespace:** `Modules\Permission\Livewire\RoleBadge`
- **Blade View:** `permission::livewire.role-badge`
- **Styles:** Uses **DaisyUI** `badge` classes.

## Customization

To add color support for new roles, modify the `getRoleColor` method within the `RoleBadge` class.

---

**Navigation**

[← Previous: Policy Patterns](policy-patterns.md) |
[Next: Development Workflow →](development-workflow.md)

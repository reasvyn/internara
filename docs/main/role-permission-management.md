# Role-Based Access Control (RBAC)

Internara implements a robust RBAC system powered by `spatie/laravel-permission`, encapsulated
within the `Permission` module. This system ensures secure and granular access control across all
domain modules.

## Core Concepts

- **Roles:** Groups of permissions (e.g., `super-admin`, `admin`, `teacher`, `student`).
- **Permissions:** Granular abilities mapped to module actions (e.g., `user.create`,
  `internship.approve`).
- **Isolation:** Domain modules do not interact with the `Permission` models directly. They use
  standard Laravel `Gate` and `Policy` systems.

---

## Technical Implementation

### 1. Modular Configuration

The `Permission` module overrides Spatie's default models at runtime within its
`PermissionServiceProvider` to use custom, modularized models:

```php
config([
    'permission.models.role' => \Modules\Permission\Models\Role::class,
    'permission.models.permission' => \Modules\Permission\Models\Permission::class,
]);
```

### 2. UUID Support

Both `Role` and `Permission` models use the `HasUuid` trait, allowing for secure, non-sequential
identifiers if configured via `permission.model_key_type`.

### 3. SuperAdmin Bypass

The system grants absolute access to the `super-admin` role using a global `Gate::before` check
defined in the `PermissionServiceProvider`:

```php
Gate::before(function (User $user, string $ability) {
    return $user->hasRole('super-admin') ? true : null;
});
```

## Role Definitions (v0.3.x Scope)

### 1. SuperAdmin

- **Purpose:** System Owner.
- **Scope:** Absolute access to all modules.
- **Protection:** Cannot be deleted or have its role changed via standard UI.
- **Verification:** Automatically marked as **Verified** upon creation/registration.

### 2. Admin

- **Purpose:** School/Organization Administrator.
- **Scope:** Manages users, school settings, departments, and foundational data.
- **Limitations:** Cannot manage SuperAdmin accounts.
- **Verification:** Automatically marked as **Verified** upon creation by another administrator.

### 3. Teacher

- **Purpose:** Internship Supervisor.
- **Scope:** Manages assigned students, reviews journals, and performs assessments.
- **Special Fields:** Requires **NIP** (Nomor Induk Pegawai).

### 4. Student

- **Purpose:** Internship Participant.
- **Scope:** Manages own internship application, logs daily journals, and views assessments.
- **Special Fields:** Requires **NISN** (Nomor Induk Siswa Nasional).

---

## Development Workflow

### Defining Permissions

Permissions should follow the `{module}.{action}` naming convention. Example: `school.update`,
`department.delete`.

### Protecting Routes

Use Laravel's built-in middleware in your module's `RouteServiceProvider` or route files:

```php
Route::get('/settings', Settings::class)->middleware('permission:system.settings');
```

### Protecting Actions (Policies)

Always create a Policy for your domain models.

```php
namespace Modules\Internship\Policies;

use Modules\User\Models\User;
use Modules\Internship\Models\Internship;

class InternshipPolicy
{
    public function update(User $user, Internship $internship): bool
    {
        return $user->can('internship.update');
    }
}
```

### Checking Permissions in UI (Livewire/Blade)

```blade
@can('user.create')
    <button wire:click="create">Add User</button>
@endcan
```

---

## Best Practices

1.  **Check Permissions, Not Roles:** Always use `$user->can('permission.name')` instead of
    `$user->hasRole('role.name')` in your business logic. Roles should only be used for high-level
    grouping.
2.  **Seed Centrally:** Define all foundational roles and permissions in the `Permission` module
    seeders to maintain a single source of truth.
3.  **Modular Ownership:** When creating permissions, assign them to a `module` (using the `module`
    column in the permissions table) to keep the system organized.

---

**Navigation**

[← Previous: Development Workflow](development-workflow.md) |
[Next: Exception Handling Guidelines →](exception-handling-guidelines.md)

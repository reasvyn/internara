# Role & Permission Management: Security RBAC

Internara implements a robust **Role-Based Access Control (RBAC)** system using the
`spatie/laravel-permission` package. We have customized this implementation to support **UUIDs** and
modular isolation, ensuring that access management is both granular and scalable.

> **Spec Alignment:** The roles defined below are strictly aligned with the User Roles mandated in
> **[Internara Specs](../internal/internara-specs.md)**. No other fundamental roles may be introduced
> without updating the specs.

---

## 1. Roles vs. Permissions

We distinguish between roles (who you are) and permissions (what you can do).

### 1.1 Fundamental Roles (from Specs)

Defined in the `Core` module seeders:

- **Instructor**: (Equivalent to Teacher) Academic supervision, grading, and student monitoring.
- **Staff**: (Practical Work Staff) Administrative management, scheduling, and document verification.
- **Student**: Daily journals, attendance, and accessing schedules/reports.
- **Industry Supervisor**: (Equivalent to Mentor) Industry-side monitoring, assessment, and feedback.
- **Administrator**: (System Admin) Full system access, configuration, and user management.

### 1.2 Granular Permissions

Permissions follow a **Module-Action** naming convention:

- `user.view`, `user.create`, `user.update`
- `assessment.create`, `report.generate`

---

## 2. Implementation in Modules

Access control must be enforced at every layer of the application.

### 2.1 The UI Layer (Livewire)

Use the `@can` Blade directive or `$this->authorize()` in Livewire components.

```blade
@can('assessment.create')
    <x-ui::button label="{{ __('assessment::ui.create') }}" />
@endcan
```

### 2.2 The Service Layer

Services should check permissions before performing destructive operations.

```php
if (!auth()->user()->can('user.delete')) {
    throw new AuthorizationException(__('exception::messages.unauthorized'));
}
```

---

## 3. Shared UI Components

The `Permission` module provides pre-built UI components to simplify access control management.

### 3.1 Role & Permission Selectors
Used in user creation or editing forms to assign access levels.

- **`x-permission::role-select`**: A multi-select component that lists all available system roles (localized). Role names are automatically translated via `__('core::roles.{name}')`.
- **`x-permission::permission-list`**: A checkbox-style list for selecting individual granular permissions.

### 3.2 Conditional Display Rules
1. **Fail Silently**: If a user doesn't have access, hide the element entirely.
2. **Breadcrumbs & Nav**: Ensure that sidebar links are also protected via `@can`.
3. **Localize**: Always use translation keys for any error messages or labels.

---

## 4. Mandatory Policies

Every Eloquent model **must** have a corresponding Policy class. This centralizes authorization
logic and allows for complex ownership checks.

**Example: `JournalPolicy`**

```php
public function update(User $user, Journal $journal)
{
    // Check both permission AND ownership
    return $user->can('journal.update') && $user->id === $journal->user_id;
}
```

---

## 5. Seeding & Synchronization

Permissions are distributed across the modules that "own" the functionality.

### 5.1 Implementing a Module Seeder
Every module that introduces new permissions should have a seeder class in its `database/seeders` directory. Use the `PermissionService` (via Contract) to ensure that permissions are created idempotently.

```php
namespace Modules\Attendance\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Permission\Services\Contracts\PermissionServiceInterface;

class AttendancePermissionSeeder extends Seeder
{
    public function run(PermissionServiceInterface $service): void
    {
        $permissions = ['attendance.view', 'attendance.check-in', 'attendance.report'];

        foreach ($permissions as $name) {
            $service->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }
}
```

### 5.2 Synchronization
After adding new permissions to a seeder, run the synchronization command:
```bash
php artisan permission:sync
```

---

_Security is a shared responsibility. Always default to "Deny" and explicitly grant only the
permissions necessary for a role to perform its function._
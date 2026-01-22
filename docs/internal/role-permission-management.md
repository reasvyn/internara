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

## 3. Mandatory Policies

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

## 4. Seeding & Syncing

Permissions are defined within each module's `Database/Seeders` directory.

- Use the **`PermissionService`** to safely register and assign permissions during installation.
- **Command**: `php artisan permission:sync` (Custom command to refresh all modular permissions).

---

_Security is a shared responsibility. Always default to "Deny" and explicitly grant only the
permissions necessary for a role to perform its function._
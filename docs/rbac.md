# Access Control (RBAC) and Account Lifecycle

## Overview

Internara utilizes a robust Role-Based Access Control (RBAC) system powered by
`spatie/laravel-permission`. Access is governed by **Roles** (grouping of capabilities) and
**Permissions** (specific granular actions).

## 1. Core Roles (5 Roles)

- **SuperAdmin**: Full system access, infrastructure management, and setup orchestration.
- **Admin**: School-level management, department control, and mentor oversight.
- **Student**: Internship participant (mentee in domain model) — daily journals, clock-in/out, assignment submissions, competency tracking.
- **Teacher**: School supervisor — classroom management, monitoring visits, journal verification, academic assessment.
- **Supervisor**: Industry supervisor — company-side oversight, technical evaluation, internship performance.

**Domain Model Note**: In code structure, Students are referred to as **Mentees** and Teachers/Supervisors are unified as **Mentors** with specializations (`teacher` or `supervisor`). One mentee can have multiple mentors with different specializations.

## 2. Implementation

Permissions are verified at the **HTTP Layer** (Middleware) and within **Livewire Components**
(Authorization checks).

### Middleware Usage:

```php
Route::middleware(['role:admin'])->group(function () {
    Route::get('/admin/school', SchoolProfile::class);
});
```

### Livewire Authorization Usage:

```php
// app/Livewire/Admin/User/AdminManager.php
$this->authorize('viewAny', User::class);
```

### Management Components (Verified):

- `app/Livewire/Admin/User/AdminManager.php` ✓
- `app/Livewire/Admin/User/StudentManager.php` ✓
- `app/Livewire/Admin/User/TeacherManager.php` ✓
- `app/Livewire/Admin/User/SupervisorManager.php` ✓
- `app/Livewire/Admin/User/MentorManager.php` ✓

## 3. Account Lifecycle Management

User accounts transition through several states to ensure security and GDPR compliance:

- **Pending**: Account created but not yet claimed by the user.
- **Active**: Fully functional account.
- **Idle**: Automatically flagged after X days of inactivity (monitored by Laravel Pulse).
- **Archived**: Restricted access after long-term inactivity.
- **Deactivated**: Explicitly disabled by an administrator.

## 4. Management Interfaces

Administrators manage identity and authority through specialized Livewire components:

- **User Management**: `App\Livewire\Admin\UserManager` - General user oversight.
- **Access Management**: `App\Livewire\Admin\AccessManager` - Granular permission/role syncing.
- **Specialized Managers**:
    - **Admin Manager**: `App\Livewire\Admin\User\AdminManager`
    - **Student Manager**: `App\Livewire\Admin\User\StudentManager` (NISN/NIS integration).
    - **Teacher Manager**: `App\Livewire\Admin\User\TeacherManager` (NIP integration).
    - **Supervisor Manager**: `App\Livewire\Admin\User\SupervisorManager` (Company/Phone integration).

## 5. Security Standards (S1)

1. **Direct Object Reference**: Every Action must verify that the authenticated user has the right
   to act on the specific UUID provided.
2. **Audit Integration**: All role/permission changes are automatically logged via `LogAuditAction`.
3. **RBAC Coverage**: 5 roles (admin, student, teacher, supervisor) with 62 permissions verified.

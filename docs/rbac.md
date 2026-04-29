# Access Control (RBAC) and Account Lifecycle

## Overview
Internara utilizes a robust Role-Based Access Control (RBAC) system powered by `spatie/laravel-permission`. Access is governed by **Roles** (grouping of capabilities) and **Permissions** (specific granular actions).

## 1. Core Roles
- **Super Admin**: Full system access, infrastructure management, and setup orchestration.
- **Admin**: School-level management, department control, and teacher oversight.
- **Teacher**: Classroom management, student monitoring, and journal verification.
- **Mentor**: Company-side oversight, student attendance verification, and assessment.
- **Student**: Daily journals, clock-in/out, and personal profile management.

## 2. Implementation
Permissions are verified at the **HTTP Layer** (Middleware) and within **Livewire Components** (Authorization checks).

### Middleware Usage:
```php
Route::middleware(['role:admin'])->group(function () {
    Route::get('/admin/school', SchoolProfile::class);
});
```

### Blade/Livewire Usage:
```html
<x-mary-button 
    label="Delete" 
    wire:click="delete" 
    @can('delete_department') class="btn-error" @else disabled @endcan 
/>
```

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
    - **Mentor Manager**: `App\Livewire\Admin\User\MentorManager` (Company/Phone integration).

## 5. Security Standards (S1)
- **Direct Object Reference**: Every Action must verify that the authenticated user has the right to act on the specific UUID provided.
- **Audit Integration**: All role/permission changes are automatically logged via `LogAuditAction`.

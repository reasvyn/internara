# Access Control (RBAC) & Lifecycle

## 1. Core Roles

Internara uses 5 core roles to govern system access:

- **SuperAdmin**: Infrastructure management, global configuration, and user lifecycle oversight.
- **Admin**: School-level management (departments, schools, settings).
- **Student**: Daily operations (attendance, logbooks, assignments).
- **Teacher**: Academic supervision (verification, monitoring, assessment).
- **Supervisor**: Technical supervision (industry evaluation, site visits).

**Domain Note**: In code, Students are often associated with the **Mentee** domain, while Teachers and Supervisors are associated with the **Mentor** domain. However, the UI and business rules must use the specific roles: `Student`, `Teacher`, and `Supervisor`.

## 2. Implementation

Access is enforced at multiple layers:
- **Middleware**: Used for route-level protection (`Route::middleware(['role:admin'])`).
- **Livewire Authorization**: Components verify permissions via Policies or Gates.
- **Action Level**: Actions verify that the authenticated user has authority over the specific data being modified.

## 3. Account Lifecycle

Accounts transition through states to ensure security and compliance:
- **Pending**: Created but not yet claimed by the user.
- **Active**: Fully functional account.
- **Idle**: Automatically flagged after a period of inactivity.
- **Archived**: Restricted access for long-term inactive accounts.
- **Deactivated**: Explicitly disabled by an administrator.

## 4. Security Principles (S1)

- **IDOR Protection**: Every request must verify ownership of the target resource.
- **Audit Trail**: All role and permission changes are logged via the Audit system.
- **Least Privilege**: Users are granted only the permissions necessary for their role.

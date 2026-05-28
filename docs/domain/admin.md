# Admin Domain

## Purpose

Admin provides system-level management across all domains — user CRUD, announcements,
GDPR compliance, and system oversight.

---

## Design Principles

### 1. User CRUD with Role Enforcement

User management gates role assignment. Only super_admin can assign the super_admin role.
Reserved authoritative names are blocked for non-super-admin users.

### 2. Announcement Lifecycle

Announcements flow through DRAFT → SCHEDULED → PUBLISHED. Scheduled announcements are
auto-published by scheduler every minute.

### 3. Bulk Operations

Mass user creation and bulk archive operations support result summaries and error
handling without blocking the entire batch.

---

## Actions

| Action | Type |
|---|---|
| `CreateUserAction` | Command |
| `UpdateUserAction` | Command |
| `DeleteUserAction` | Command |
| `ToggleUserStatusAction` | Command |
| `SendAnnouncementAction` | Command |
| `ArchiveStudentAccountsAction` | Process |
| `GetAdminDashboardStatsAction` | Read |
| `SaveRecoveryKeyAction` | Command |
| `ReadRecoveryKeyAction` | Read |

## Where to Find It

- `app/Domain/Admin/Models/`
- `app/Domain/Admin/Actions/` — 9 Actions
- `app/Domain/Admin/Console/Commands/` — CLI admin tools
- `app/Domain/Admin/Livewire/` — user managers, announcement manager

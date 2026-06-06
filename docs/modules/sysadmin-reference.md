# SysAdmin — Technical Reference

> Last updated: 2026-06-06
> Changes: Removed Settings submodule, actions, model, policy, Livewire component, and directories following Settings module extraction

Detailed structural and implementation reference for the **SysAdmin** module.

---

## Overview

Handles user administration, announcements, system health monitoring, audit logging, and GDPR compliance

### Module Statistics
- **Actions**: 14 business logic operations
- **Models**: 2 data entities
- **Livewire Components**: 12 UI components
- **Policies**: 1 authorization rule
- **Submodules**: 3 module submodules

### Submodules
- `Account`
- `Announcement`
- `Observability` (contains `GdprDeletionLog`, `Recorders`, `Services`)

> **Note**: The `Settings` submodule has been extracted into its own standalone module. See [settings-reference.md](settings-reference.md).

---

## Dependency Graph

This module depends on:
- **Academics**
- **Certification**
- **Core**
- **Enrollment**
- **Guidance**
- **Journals**
- **Partners**
- **Program**
- **User**

---

## Actions

| File | Class | Extends |
|---|---|---|
| `Actions/GetAdminDashboardStatsAction.php` | `GetAdminDashboardStatsAction` | `BaseAction` |
| `Account/Actions/ArchiveStudentAccountsAction.php` | `ArchiveStudentAccountsAction` | `BaseAction` |
| `Account/Actions/BatchDeleteUserAction.php` | `BatchDeleteUserAction` | `BaseAction` |
| `Account/Actions/CreateUserAction.php` | `CreateUserAction` | `BaseAction` |
| `Account/Actions/DeleteUserAction.php` | `DeleteUserAction` | `BaseAction` |
| `Account/Actions/GenerateAccountSlipAction.php` | `GenerateAccountSlipAction` | `BaseAction` |
| `Account/Actions/GetUserManagerStatsAction.php` | `GetUserManagerStatsAction` | `BaseAction` |
| `Account/Actions/ReadRecoveryKeyAction.php` | `ReadRecoveryKeyAction` | `BaseAction` |
| `Account/Actions/RevokeUserActivationTokensAction.php` | `RevokeUserActivationTokensAction` | `BaseAction` |
| `Account/Actions/SaveRecoveryKeyAction.php` | `SaveRecoveryKeyAction` | `BaseAction` |
| `Account/Actions/SetUserStatusAction.php` | `SetUserStatusAction` | `BaseAction` |
| `Account/Actions/ToggleUserStatusAction.php` | `ToggleUserStatusAction` | `BaseAction` |
| `Account/Actions/UpdateUserAction.php` | `UpdateUserAction` | `BaseAction` |
| `Announcement/Actions/SendAnnouncementAction.php` | `SendAnnouncementAction` | `BaseAction` |

---

## Models

| File | Class |
|---|---|
| `Announcement/Models/Announcement.php` | `Announcement` |
| `Observability/GdprDeletionLog/Models/GdprDeletionLog.php` | `GdprDeletionLog` |

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Account/Livewire/AdminManager.php` | `AdminManager` | `BaseRecordManager` |
| `Account/Livewire/StudentManager.php` | `StudentManager` | `BaseRecordManager` |
| `Account/Livewire/SupervisorManager.php` | `SupervisorManager` | `BaseRecordManager` |
| `Account/Livewire/TeacherManager.php` | `TeacherManager` | `BaseRecordManager` |
| `Account/Livewire/UserManager.php` | `UserManager` | `BaseRecordManager` |
| `Announcement/Livewire/AnnouncementManager.php` | `AnnouncementManager` | `Component` |
| `Observability/GdprDeletionLog/Livewire/GdprDeletionLogs.php` | `GdprDeletionLogs` | `Component` |
| `Livewire/AccountCloneDetector.php` | `AccountCloneDetector` | `Component` |
| `Livewire/ApplicationReview.php` | `ApplicationReview` | `Component` |
| `Livewire/AuditLogManager.php` | `AuditLogManager` | `Component` |
| `Livewire/Pulse/RegistrationsCard.php` | `RegistrationsCard` | `Component` |
| `Livewire/Pulse/SystemCard.php` | `SystemCard` | `Component` |

---

## Livewire Concerns

| File | Concern / Trait | Description |
|---|---|---|
| `Account/Livewire/Concerns/DownloadsAccountSlips.php` | `DownloadsAccountSlips` | Shared concern for downloading PDF account slips and access credentials for newly created users |

---

## Authorization Policies

| File | Policy |
|---|---|
| `Observability/GdprDeletionLog/Policies/GdprDeletionLogPolicy.php` | `GdprDeletionLogPolicy` |

---

## Console Commands

| Command Signature | Class | Description |
|---|---|---|
| `system:health` | `SystemHealthCommand` | Comprehensive system health check with JSON output support. (not implemented) |
| `system:cleanup` | `SystemCleanupCommand` | Routine maintenance: prune resets, cache tags, failed jobs, activity logs, media, and old log files. (not implemented) |
| `system:cache-warm` | `SystemCacheWarmCommand` | Pre-warms application caches (config, views, events, settings, brand). (not implemented) |
| `admin:create` | `CreateAdminCommand` | Creates the initial superadmin account when none exists. |
| `admin:recover` | `RecoverAdminCommand` | Interactive command to reset a superadmin's password or re-create it. |
| `admin:recovery-show` | `ShowRecoveryKeyCommand` | Displays the current recovery key after confirmation. |
| `admin:recovery-path` | `ShowRecoveryPathCommand` | Displays the absolute file path of the recovery key. |
| `notifications:prune` | `PruneNotificationsCommand` | Prunes old notification records. |

> [!NOTE]
> - `admin:promote` has been removed — role mappings and promotions are handled directly by functional/standard roles logic or user-management interfaces.
- `system:health`, `system:cleanup`, and `system:cache-warm` are planned but not yet implemented.
> **Note**: `setup:install` and `setup:reset-token` have been moved to the Setup module. See [setup-reference.md](setup-reference.md).

---

## File Organization

```
app/SysAdmin/
├──            ← Submodule roots
│   ├── Account/
│   │   ├── Actions/
│   │   ├── Console/
│   │   ├── Livewire/
│   │   │   ├── Concerns/
│   │   │   └── Forms/
│   │   └── Notifications/
│   ├── Announcement/
│   │   ├── Actions/
│   │   ├── Console/
│   │   ├── Enums/
│   │   ├── Livewire/
│   │   │   └── Forms/
│   │   ├── Models/
│   │   └── Notifications/
│   ├── Observability/
│   │   ├── Console/
│   │   │   └── Commands/
│   │   ├── GdprDeletionLog/
│   │   │   ├── Livewire/
│   │   │   ├── Models/
│   │   │   └── Policies/
│   │   ├── Recorders/
│   │   └── Services/
├── Actions/              ← Cross-submodule actions
├── Console/              ← Cross-submodule artisan commands
│   └── Commands/
│       ├── CreateAdminCommand.php          ← admin:create
│       ├── PruneNotificationsCommand.php   ← notifications:prune
│       ├── RecoverAdminCommand.php         ← admin:recover
│       ├── ShowRecoveryKeyCommand.php      ← admin:recovery-show
│       └── ShowRecoveryPathCommand.php     ← admin:recovery-path
├── Livewire/             ← Cross-submodule UI (audit, pulse)
│   └── Pulse/
├── Observability/        ← Observability submodule (Console/Commands, GdprDeletionLog, Recorders, Services)
└── Services/             ← Infrastructure services
```

---

## Architectural Integration

This module integrates with the system across the following directories and resources:

- **Submodules**: `Account`, `Announcement`, `Observability`
- **Business Logic (`app/`)**: Located in [app/SysAdmin/](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/app/SysAdmin/)
- **Routing (`routes/`)**: [routes/web/sysadmin.php](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/routes/web/sysadmin.php)
- **Views (`views/`)**: Blade templates and layouts are in [resources/views/sysadmin/](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/resources/views/sysadmin/)
- **Testing (`tests/`)**: Feature `tests/Feature/SysAdmin/`, Unit `tests/Unit/SysAdmin/`


*For overview and business context, see [sysadmin.md](sysadmin.md)*

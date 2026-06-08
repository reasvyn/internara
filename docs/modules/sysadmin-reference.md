# SysAdmin — Technical Reference

> Last updated: 2026-06-08

Detailed structural and implementation reference for the **SysAdmin** module.

---

## Overview

Handles user administration, announcements, super admin recovery, system health monitoring, audit logging, Pulse observability, and GDPR compliance.

### Submodules

- `Account` — User account lifecycle
- `Announcement` — System announcements
- `Observability` — Health monitoring, Pulse, audit logs, GDPR

---

## Actions

| File | Class | Extends |
| ---- | ----- | ------- |
| `Actions/GetAdminDashboardStatsAction.php` | `GetAdminDashboardStatsAction` | `BaseAction` |
| `UserManagement/Actions/CreateUserAction.php` | `CreateUserAction` | `BaseAction` |
| `UserManagement/Actions/UpdateUserAction.php` | `UpdateUserAction` | `BaseAction` |
| `UserManagement/Actions/DeleteUserAction.php` | `DeleteUserAction` | `BaseAction` |
| `UserManagement/Actions/BatchDeleteUserAction.php` | `BatchDeleteUserAction` | `BaseAction` |
| `UserManagement/Actions/SetUserStatusAction.php` | `SetUserStatusAction` | `BaseAction` |
| `UserManagement/Actions/ToggleUserStatusAction.php` | `ToggleUserStatusAction` | `BaseAction` |
| `UserManagement/Actions/GenerateAccountSlipAction.php` | `GenerateAccountSlipAction` | `BaseAction` |
| `UserManagement/Actions/SaveRecoveryKeyAction.php` | `SaveRecoveryKeyAction` | `BaseAction` |
| `UserManagement/Actions/ReadRecoveryKeyAction.php` | `ReadRecoveryKeyAction` | `BaseAction` |
| `UserManagement/Actions/RevokeUserActivationTokensAction.php` | `RevokeUserActivationTokensAction` | `BaseAction` |
| `UserManagement/Actions/ArchiveStudentAccountsAction.php` | `ArchiveStudentAccountsAction` | `BaseAction` |
| `UserManagement/Actions/GetUserManagerStatsAction.php` | `GetUserManagerStatsAction` | Read |
| `Announcement/Actions/SendAnnouncementAction.php` | `SendAnnouncementAction` | Process `BaseAction` |

---

## Models

| File | Class | Extends |
| ---- | ----- | ------- |
| `Announcement/Models/Announcement.php` | `Announcement` | `BaseModel` |
| `Observability/GdprDeletionLog/Models/GdprDeletionLog.php` | `GdprDeletionLog` | `BaseModel` |

---

## Enums

| File | Enum | Implements | Values |
| ---- | ---- | ---------- | ------ |
| `Announcement/Enums/AnnouncementStatus.php` | `AnnouncementStatus` | `LabelEnum`, `StatusEnum` | draft, scheduled, published, archived |

---

---

## Policies

| File | Policy | Extends |
| ---- | ------ | ------- |
| `Observability/GdprDeletionLog/Policies/GdprDeletionLogPolicy.php` | `GdprDeletionLogPolicy` | `BasePolicy` |

---

## Livewire Components

| File | Component | Extends |
| ---- | --------- | ------- |
| `UserManagement/Livewire/UserManager.php` | `UserManager` | `BaseRecordManager` |
| `UserManagement/Livewire/StudentManager.php` | `StudentManager` | `BaseRecordManager` |
| `UserManagement/Livewire/TeacherManager.php` | `TeacherManager` | `BaseRecordManager` |
| `UserManagement/Livewire/SupervisorManager.php` | `SupervisorManager` | `BaseRecordManager` |
| `UserManagement/Livewire/AdminManager.php` | `AdminManager` | `BaseRecordManager` |
| `Announcement/Livewire/AnnouncementManager.php` | `AnnouncementManager` | `Component` |
| `Livewire/ApplicationReview.php` | `ApplicationReview` | `Component` |
| `Observability/Livewire/AuditLogManager.php` | `AuditLogManager` | `Component` |
| `Observability/Livewire/AccountCloneDetector.php` | `AccountCloneDetector` | `Component` |
| `Observability/GdprDeletionLog/Livewire/GdprDeletionLogs.php` | `GdprDeletionLogs` | `Component` |
| `Observability/Livewire/Pulse/SystemCard.php` | `SystemCard` | `Component` |
| `Observability/Livewire/Pulse/RegistrationsCard.php` | `RegistrationsCard` | `Component` |

## Livewire Concerns

| File | Trait | Purpose |
| ---- | ----- | ------- |
| `UserManagement/Livewire/Concerns/DownloadsAccountSlips.php` | `DownloadsAccountSlips` | PDF account slip download utility |

## Livewire Forms

| File | Form |
| ---- | ---- |
| `UserManagement/Livewire/Forms/UserForm.php` | `UserForm` |
| `UserManagement/Livewire/Forms/StudentForm.php` | `StudentForm` |
| `UserManagement/Livewire/Forms/TeacherForm.php` | `TeacherForm` |
| `UserManagement/Livewire/Forms/SupervisorForm.php` | `SupervisorForm` |
| `UserManagement/Livewire/Forms/AdminUserForm.php` | `AdminUserForm` |
| `Announcement/Livewire/Forms/AnnouncementForm.php` | `AnnouncementForm` |

## Notifications

| File | Notification |
| ---- | ------------ |
| `UserManagement/Notifications/ActivationCodeNotification.php` | `ActivationCodeNotification` |
| `Announcement/Notifications/AnnouncementNotification.php` | `AnnouncementNotification` |

## Console Commands

| Command Signature | Class | Description |
| ----------------- | ----- | ----------- |
| `system:health` | `SystemHealthCommand` | Comprehensive system health check with JSON output |
| `system:cleanup` | `SystemCleanupCommand` | Routine maintenance (prune resets, cache, logs) |
| `system:cache-warm` | `SystemCacheWarmCommand` | Pre-warms config, views, events, settings, brand caches |
| `pulse:record-snapshots` | `PulseRecordSnapshotsCommand` | Records Pulse metric snapshots |
| `announcements:publish` | `PublishScheduledAnnouncementsCommand` | Publishes scheduled announcements |
| `accounts:auto-inactivate` | `AutoInactivateAccounts` | Inactivates accounts inactive 90+ days |
| `admin:create` | `CreateAdminCommand` | Creates initial superadmin |
| `admin:recover` | `RecoverAdminCommand` | Interactive superadmin password reset |
| `admin:recovery-show` | `ShowRecoveryKeyCommand` | Displays current recovery key |
| `admin:recovery-path` | `ShowRecoveryPathCommand` | Shows recovery key file path |
| `notifications:prune` | `PruneNotificationsCommand` | Prunes old notifications |

## Pulse Recorders

| File | Recorder | Purpose |
| ---- | -------- | ------- |
| `Observability/Recorders/SystemRecorder.php` | `SystemRecorder` | System health Pulse recording |
| `Observability/Recorders/RegistrationRecorder.php` | `RegistrationRecorder` | Registration metrics Pulse recording |

## Services

| File | Service | Purpose |
| ---- | ------- | ------- |
| `Observability/Services/EnvironmentAuditor.php` | `EnvironmentAuditor` | Environment health assessment |
| `Observability/Services/PulseGuard.php` | `PulseGuard` | Pulse monitoring guard |

---

## Routes

File: `routes/web/sysadmin.php`
Naming pattern: `sysadmin.{resource}.{action}`

---

## File Organization

```
app/SysAdmin/
├── Actions/GetAdminDashboardStatsAction.php
├── UserManagement/
│   ├── Actions/ (12 actions)
│   ├── Console/Commands/AutoInactivateAccounts.php
│   ├── Livewire/
│   │   ├── Concerns/DownloadsAccountSlips.php
│   │   ├── Forms/ (UserForm, StudentForm, TeacherForm, SupervisorForm, AdminUserForm)
│   │   ├── UserManager.php
│   │   ├── StudentManager.php
│   │   ├── TeacherManager.php
│   │   ├── SupervisorManager.php
│   │   └── AdminManager.php
│   └── Notifications/ActivationCodeNotification.php
├── Announcement/
│   ├── Actions/SendAnnouncementAction.php
│   ├── Console/Commands/PublishScheduledAnnouncementsCommand.php
│   ├── Enums/AnnouncementStatus.php
│   ├── Livewire/
│   │   ├── Forms/AnnouncementForm.php
│   │   └── AnnouncementManager.php
│   ├── Models/Announcement.php
│   └── Notifications/AnnouncementNotification.php
├── Console/Commands/
│   ├── CreateAdminCommand.php
│   ├── PruneNotificationsCommand.php
│   ├── RecoverAdminCommand.php
│   ├── ShowRecoveryKeyCommand.php
│   └── ShowRecoveryPathCommand.php
├── Livewire/ApplicationReview.php
├── Observability/
│   ├── Console/Commands/
│   │   ├── PulseRecordSnapshotsCommand.php
│   │   ├── SystemCacheWarmCommand.php
│   │   ├── SystemCleanupCommand.php
│   │   └── SystemHealthCommand.php
│   ├── GdprDeletionLog/
│   │   ├── Livewire/GdprDeletionLogs.php
│   │   ├── Models/GdprDeletionLog.php
│   │   └── Policies/GdprDeletionLogPolicy.php
│   ├── Livewire/
│   │   ├── Pulse/
│   │   │   ├── RegistrationsCard.php
│   │   │   └── SystemCard.php
│   │   ├── AccountCloneDetector.php
│   │   └── AuditLogManager.php
│   ├── Recorders/
│   │   ├── RegistrationRecorder.php
│   │   └── SystemRecorder.php
│   └── Services/
│       ├── EnvironmentAuditor.php
│       └── PulseGuard.php
```

---

## Architectural Integration

- **Submodules**: `Account`, `Announcement`, `Observability`
- **Business Logic**: `app/SysAdmin/`
- **Routing**: `routes/web/sysadmin.php`
- **Views**: `resources/views/sysadmin/`
- **Testing**: `tests/Feature/SysAdmin/`, `tests/Unit/SysAdmin/`
- **Dependencies**: User, Academics, Core

*For overview and business context, see [sysadmin.md](sysadmin.md).*

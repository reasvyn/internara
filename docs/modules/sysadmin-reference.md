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
| `Account/Actions/CreateUserAction.php` | `CreateUserAction` | `BaseAction` |
| `Account/Actions/UpdateUserAction.php` | `UpdateUserAction` | `BaseAction` |
| `Account/Actions/DeleteUserAction.php` | `DeleteUserAction` | `BaseAction` |
| `Account/Actions/BatchDeleteUserAction.php` | `BatchDeleteUserAction` | `BaseAction` |
| `Account/Actions/SetUserStatusAction.php` | `SetUserStatusAction` | `BaseAction` |
| `Account/Actions/ToggleUserStatusAction.php` | `ToggleUserStatusAction` | `BaseAction` |
| `Account/Actions/GenerateAccountSlipAction.php` | `GenerateAccountSlipAction` | `BaseAction` |
| `Account/Actions/SaveRecoveryKeyAction.php` | `SaveRecoveryKeyAction` | `BaseAction` |
| `Account/Actions/ReadRecoveryKeyAction.php` | `ReadRecoveryKeyAction` | `BaseAction` |
| `Account/Actions/RevokeUserActivationTokensAction.php` | `RevokeUserActivationTokensAction` | `BaseAction` |
| `Account/Actions/ArchiveStudentAccountsAction.php` | `ArchiveStudentAccountsAction` | `BaseAction` |
| `Account/Actions/GetUserManagerStatsAction.php` | `GetUserManagerStatsAction` | Read |
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
| `Account/Livewire/UserManager.php` | `UserManager` | `BaseRecordManager` |
| `Account/Livewire/StudentManager.php` | `StudentManager` | `BaseRecordManager` |
| `Account/Livewire/TeacherManager.php` | `TeacherManager` | `BaseRecordManager` |
| `Account/Livewire/SupervisorManager.php` | `SupervisorManager` | `BaseRecordManager` |
| `Account/Livewire/AdminManager.php` | `AdminManager` | `BaseRecordManager` |
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
| `Account/Livewire/Concerns/DownloadsAccountSlips.php` | `DownloadsAccountSlips` | PDF account slip download utility |

## Livewire Forms

| File | Form |
| ---- | ---- |
| `Account/Livewire/Forms/UserForm.php` | `UserForm` |
| `Account/Livewire/Forms/StudentForm.php` | `StudentForm` |
| `Account/Livewire/Forms/TeacherForm.php` | `TeacherForm` |
| `Account/Livewire/Forms/SupervisorForm.php` | `SupervisorForm` |
| `Account/Livewire/Forms/AdminUserForm.php` | `AdminUserForm` |
| `Announcement/Livewire/Forms/AnnouncementForm.php` | `AnnouncementForm` |

## Notifications

| File | Notification |
| ---- | ------------ |
| `Account/Notifications/ActivationCodeNotification.php` | `ActivationCodeNotification` |
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
├── Account/
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

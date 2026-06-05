# SysAdmin — Technical Reference

> Last updated: 2026-06-05
> Changes: Removed Setup submodule, policies, console commands, and directories following Setup module extraction

Detailed structural and implementation reference for the **SysAdmin** module.

---

## Overview

Handles user administration, announcements, system health monitoring, audit logging, and GDPR compliance

### Module Statistics
- **Actions**: 20 business logic operations
- **Models**: 3 data entities
- **Livewire Components**: 13 UI components
- **Policies**: 2 authorization rules
- **Submodules**: 4 module submodules

### Submodules
- `Account`
- `Announcement`
- `GdprDeletionLog`
- `Setting`

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
| `Setting/Actions/BatchSetSettingAction.php` | `BatchSetSettingAction` | `BaseAction` |
| `Setting/Actions/GetAcademicYearsAction.php` | `GetAcademicYearsAction` | `Base` |
| `Setting/Actions/SaveSystemSettingsAction.php` | `SaveSystemSettingsAction` | `BaseAction` |
| `Setting/Actions/SetSettingAction.php` | `SetSettingAction` | `BaseAction` |
| `Setting/Actions/TestMailSettingsAction.php` | `TestMailSettingsAction` | `BaseAction` |
| `Setting/Actions/UploadBrandAssetAction.php` | `UploadBrandAssetAction` | `BaseAction` |

---

## Models

| File | Class |
|---|---|
| `Announcement/Models/Announcement.php` | `Announcement` |
| `GdprDeletionLog/Models/GdprDeletionLog.php` | `GdprDeletionLog` |
| `Setting/Models/Setting.php` | `Setting` |

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
| `GdprDeletionLog/Livewire/GdprDeletionLogs.php` | `GdprDeletionLogs` | `Component` |
| `Setting/Livewire/SystemSetting.php` | `SystemSetting` | `Component` |
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
| `GdprDeletionLog/Policies/GdprDeletionLogPolicy.php` | `GdprDeletionLogPolicy` |
| `Setting/Policies/SettingPolicy.php` | `SettingPolicy` |

---

## Console Commands

| Command Signature | Class | Description |
|---|---|---|
| `system:health` | `SystemHealthCommand` | Comprehensive system health check with JSON output support. |
| `system:cleanup` | `SystemCleanupCommand` | Routine maintenance: prune resets, cache tags, failed jobs, activity logs, media, and old log files. |
| `system:cache-warm` | `SystemCacheWarmCommand` | Pre-warms application caches (config, views, events, settings, brand). |
| `admin:create` | `CreateAdminCommand` | Creates the initial superadmin account when none exists. |
| `admin:recover` | `RecoverAdminCommand` | Interactive command to reset a superadmin's password or re-create it. |
| `admin:recovery-show` | `ShowRecoveryKeyCommand` | Displays the current recovery key after confirmation. |
| `admin:recovery-path` | `ShowRecoveryPathCommand` | Displays the absolute file path of the recovery key. |
| `notifications:prune` | `PruneNotificationsCommand` | Prunes old notification records. |
| `pulse:record-snapshots` | `PulseRecordSnapshotsCommand` | Records Pulse monitoring snapshots. |

> [!NOTE]
> The legacy `admin:promote` command has been removed because role mappings and promotions are handled directly by functional/standard roles logic or user-management interfaces.

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
│   ├── GdprDeletionLog/
│   │   ├── Livewire/
│   │   ├── Models/
│   │   └── Policies/
│   └── Setting/
│       ├── Actions/
│       ├── Casts/
│       ├── Enums/
│       ├── Http/
│       │   └── Middleware/
│       ├── Livewire/
│       │   │   └── Forms/
│       │   └── Models/
│       │   └── Policies/
│       │   └── Rules/
│       └── Support/
├── Actions/              ← Cross-submodule actions
├── Console/              ← Cross-submodule artisan commands
│   └── Commands/
│       ├── SystemHealthCommand.php         ← system:health
│       ├── SystemCleanupCommand.php        ← system:cleanup
│       ├── SystemCacheWarmCommand.php      ← system:cache-warm
│       ├── CreateAdminCommand.php          ← admin:create
│       ├── RecoverAdminCommand.php         ← admin:recover
│       ├── ShowRecoveryKeyCommand.php      ← admin:recovery-show
│       ├── ShowRecoveryPathCommand.php     ← admin:recovery-path
│       ├── PruneNotificationsCommand.php   ← notifications:prune
│       └── PulseRecordSnapshotsCommand.php ← pulse:record-snapshots
├── Livewire/             ← Cross-submodule UI (audit, pulse)
│   └── Pulse/
├── Recorders/            ← Pulse recorders
└── Services/             ← Infrastructure services
```

---

*For overview and business context, see [sysadmin.md](sysadmin.md)*

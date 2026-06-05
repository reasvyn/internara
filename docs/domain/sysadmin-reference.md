# SysAdmin — Technical Reference

> Last updated: 2026-06-04
> Changes: Added system:* commands (system:health, system:cleanup, system:cache-warm) moved from Core; updated file tree with all 11 console commands

Detailed structural and implementation reference for the **SysAdmin** domain.

---

## Overview

Handles system setup, configuration, account administration, announcements, system health monitoring, audit logging, and GDPR compliance

### Domain Statistics
- **Actions**: 27 business logic operations
- **Models**: 4 data entities
- **Livewire Components**: 14 UI components
- **Policies**: 3 authorization rules
- **Aggregates**: 5 domain aggregates

### Aggregates
- `Account`
- `Announcement`
- `GdprDeletionLog`
- `Setting`
- `Setup`

---

## Dependency Graph

This domain depends on:
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
| `Aggregates/Account/Actions/ArchiveStudentAccountsAction.php` | `ArchiveStudentAccountsAction` | `BaseAction` |
| `Aggregates/Account/Actions/BatchDeleteUserAction.php` | `BatchDeleteUserAction` | `BaseAction` |
| `Aggregates/Account/Actions/CreateUserAction.php` | `CreateUserAction` | `BaseAction` |
| `Aggregates/Account/Actions/DeleteUserAction.php` | `DeleteUserAction` | `BaseAction` |
| `Aggregates/Account/Actions/GenerateAccountSlipAction.php` | `GenerateAccountSlipAction` | `BaseAction` |
| `Aggregates/Account/Actions/GetUserManagerStatsAction.php` | `GetUserManagerStatsAction` | `BaseAction` |
| `Aggregates/Account/Actions/ReadRecoveryKeyAction.php` | `ReadRecoveryKeyAction` | `BaseAction` |
| `Aggregates/Account/Actions/RevokeUserActivationTokensAction.php` | `RevokeUserActivationTokensAction` | `BaseAction` |
| `Aggregates/Account/Actions/SaveRecoveryKeyAction.php` | `SaveRecoveryKeyAction` | `BaseAction` |
| `Aggregates/Account/Actions/SetUserStatusAction.php` | `SetUserStatusAction` | `BaseAction` |
| `Aggregates/Account/Actions/ToggleUserStatusAction.php` | `ToggleUserStatusAction` | `BaseAction` |
| `Aggregates/Account/Actions/UpdateUserAction.php` | `UpdateUserAction` | `BaseAction` |
| `Aggregates/Announcement/Actions/SendAnnouncementAction.php` | `SendAnnouncementAction` | `BaseAction` |
| `Aggregates/Setting/Actions/BatchSetSettingAction.php` | `BatchSetSettingAction` | `BaseAction` |
| `Aggregates/Setting/Actions/GetAcademicYearsAction.php` | `GetAcademicYearsAction` | `Base` |
| `Aggregates/Setting/Actions/SaveSystemSettingsAction.php` | `SaveSystemSettingsAction` | `BaseAction` |
| `Aggregates/Setting/Actions/SetSettingAction.php` | `SetSettingAction` | `BaseAction` |
| `Aggregates/Setting/Actions/TestMailSettingsAction.php` | `TestMailSettingsAction` | `BaseAction` |
| `Aggregates/Setting/Actions/UploadBrandAssetAction.php` | `UploadBrandAssetAction` | `BaseAction` |
| `Aggregates/Setup/Actions/FinalizeSetupAction.php` | `FinalizeSetupAction` | `BaseAction` |
| `Aggregates/Setup/Actions/GenerateSetupTokenAction.php` | `GenerateSetupTokenAction` | `BaseAction` |
| `Aggregates/Setup/Actions/InstallSystemAction.php` | `InstallSystemAction` | `BaseAction` |
| `Aggregates/Setup/Actions/SetupDepartmentAction.php` | `SetupDepartmentAction` | `BaseAction` |
| `Aggregates/Setup/Actions/SetupSchoolAction.php` | `SetupSchoolAction` | `BaseAction` |
| `Aggregates/Setup/Actions/ValidateSetupTokenAction.php` | `ValidateSetupTokenAction` | `BaseAction` |

---

## Models

| File | Class |
|---|---|
| `Aggregates/Announcement/Models/Announcement.php` | `Announcement` |
| `Aggregates/GdprDeletionLog/Models/GdprDeletionLog.php` | `GdprDeletionLog` |
| `Aggregates/Setting/Models/Setting.php` | `Setting` |
| `Aggregates/Setup/Models/Setup.php` | `Setup` |

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Aggregates/Account/Livewire/AdminManager.php` | `AdminManager` | `BaseRecordManager` |
| `Aggregates/Account/Livewire/StudentManager.php` | `StudentManager` | `BaseRecordManager` |
| `Aggregates/Account/Livewire/SupervisorManager.php` | `SupervisorManager` | `BaseRecordManager` |
| `Aggregates/Account/Livewire/TeacherManager.php` | `TeacherManager` | `BaseRecordManager` |
| `Aggregates/Account/Livewire/UserManager.php` | `UserManager` | `BaseRecordManager` |
| `Aggregates/Announcement/Livewire/AnnouncementManager.php` | `AnnouncementManager` | `Component` |
| `Aggregates/GdprDeletionLog/Livewire/GdprDeletionLogs.php` | `GdprDeletionLogs` | `Component` |
| `Aggregates/Setting/Livewire/SystemSetting.php` | `SystemSetting` | `Component` |
| `Aggregates/Setup/Livewire/SetupWizard.php` | `SetupWizard` | `Component` |
| `Livewire/AccountCloneDetector.php` | `AccountCloneDetector` | `Component` |
| `Livewire/ApplicationReview.php` | `ApplicationReview` | `Component` |
| `Livewire/AuditLogManager.php` | `AuditLogManager` | `Component` |
| `Livewire/Pulse/RegistrationsCard.php` | `RegistrationsCard` | `Component` |
| `Livewire/Pulse/SystemCard.php` | `SystemCard` | `Component` |

---

## Livewire Concerns

| File | Concern / Trait | Description |
|---|---|---|
| `Aggregates/Account/Livewire/Concerns/DownloadsAccountSlips.php` | `DownloadsAccountSlips` | Shared concern for downloading PDF account slips and access credentials for newly created users |

---

## Authorization Policies

| File | Policy |
|---|---|
| `Aggregates/GdprDeletionLog/Policies/GdprDeletionLogPolicy.php` | `GdprDeletionLogPolicy` |
| `Aggregates/Setting/Policies/SettingPolicy.php` | `SettingPolicy` |
| `Aggregates/Setup/Policies/SetupPolicy.php` | `SetupPolicy` |

---

## Console Commands

| Command Signature | Class | Description |
|---|---|---|
| `system:health` | `SystemHealthCommand` | Comprehensive system health check with JSON output support. |
| `system:cleanup` | `SystemCleanupCommand` | Routine maintenance: prune resets, cache tags, failed jobs, activity logs, media, and old log files. |
| `system:cache-warm` | `SystemCacheWarmCommand` | Pre-warms application caches (config, views, events, settings, brand). |
| `setup:install` | `SetupInstallCommand` | Provisions the system, seeds Roles and AcademicYear via `SetupSeeder`, and generates a setup token. |
| `setup:reset-token` | `SetupResetTokenCommand` | Generates a new setup token (usable only if installation is incomplete). |
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
app/Domain/SysAdmin/
├── Aggregates/           ← Aggregate roots
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
│   ├── Setting/
│   │   ├── Actions/
│   │   ├── Casts/
│   │   ├── Enums/
│   │   ├── Http/
│   │   │   └── Middleware/
│   │   ├── Livewire/
│   │   │   └── Forms/
│   │   ├── Models/
│   │   ├── Policies/
│   │   ├── Rules/
│   │   └── Support/
│   └── Setup/
│       ├── Actions/
│       ├── Entities/
│       ├── Livewire/
│       │   └── Forms/
│       ├── Models/
│       ├── Policies/
│       ├── Services/
│       └── Support/
├── Actions/              ← Cross-aggregate actions
├── Console/              ← Cross-aggregate artisan commands
│   └── Commands/
│       ├── SystemHealthCommand.php         ← system:health
│       ├── SystemCleanupCommand.php        ← system:cleanup
│       ├── SystemCacheWarmCommand.php      ← system:cache-warm
│       ├── SetupInstallCommand.php         ← setup:install
│       ├── SetupResetTokenCommand.php      ← setup:reset-token
│       ├── CreateAdminCommand.php          ← admin:create
│       ├── RecoverAdminCommand.php         ← admin:recover
│       ├── ShowRecoveryKeyCommand.php      ← admin:recovery-show
│       ├── ShowRecoveryPathCommand.php     ← admin:recovery-path
│       ├── PruneNotificationsCommand.php   ← notifications:prune
│       └── PulseRecordSnapshotsCommand.php ← pulse:record-snapshots
├── Livewire/             ← Cross-aggregate UI (audit, pulse)
│   └── Pulse/
├── Recorders/            ← Pulse recorders
└── Services/             ← Infrastructure services
```

---

*For overview and business context, see [sysadmin.md](sysadmin.md)*

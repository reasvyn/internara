# Admin — Technical Reference

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Complete technical reference for the Admin domain.

Detailed structural and implementation reference for the **Admin** domain.

---

## Overview

Handles system setup, configuration, announcements, and account administration

### Domain Statistics
- **Actions**: 20 business logic operations
- **Models**: 3 data entities
- **Livewire Components**: 11 UI components
- **Policies**: 2 authorization rules
- **Aggregates**: 4 domain aggregates

### Aggregates
- `Account`
- `Announcement`
- `GdprDeletionLog`
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
- **Settings**
- **User**

---

## Actions

| File | Class | Extends |
|---|---|---|
| `Aggregates/Account/Actions/ArchiveStudentAccountsAction.php` | `ArchiveStudentAccountsAction` | `BaseAction` |
| `Aggregates/Account/Actions/BatchDeleteUserAction.php` | `BatchDeleteUserAction` | `BaseAction` |
| `Aggregates/Account/Actions/CreateUserAction.php` | `CreateUserAction` | `BaseAction` |
| `Aggregates/Account/Actions/DeleteUserAction.php` | `DeleteUserAction` | `BaseAction` |
| `Aggregates/Setup/Actions/FinalizeSetupAction.php` | `FinalizeSetupAction` | `BaseAction` |
| `Aggregates/Account/Actions/GenerateAccountSlipAction.php` | `GenerateAccountSlipAction` | `BaseAction` |
| `Aggregates/Setup/Actions/GenerateSetupTokenAction.php` | `GenerateSetupTokenAction` | `BaseAction` |
| `Actions/GetAdminDashboardStatsAction.php` | `GetAdminDashboardStatsAction` | `BaseAction` |
| `Aggregates/Account/Actions/GetUserManagerStatsAction.php` | `GetUserManagerStatsAction` | `BaseAction` |
| `Aggregates/Setup/Actions/InstallSystemAction.php` | `InstallSystemAction` | `BaseAction` |
| `Aggregates/Account/Actions/ReadRecoveryKeyAction.php` | `ReadRecoveryKeyAction` | `BaseAction` |
| `Aggregates/Account/Actions/RevokeUserActivationTokensAction.php` | `RevokeUserActivationTokensAction` | `BaseAction` |
| `Aggregates/Account/Actions/SaveRecoveryKeyAction.php` | `SaveRecoveryKeyAction` | `BaseAction` |
| `Aggregates/Announcement/Actions/SendAnnouncementAction.php` | `SendAnnouncementAction` | `BaseAction` |
| `Aggregates/Account/Actions/SetUserStatusAction.php` | `SetUserStatusAction` | `BaseAction` |
| `Aggregates/Setup/Actions/SetupDepartmentAction.php` | `SetupDepartmentAction` | `BaseAction` |
| `Aggregates/Setup/Actions/SetupSchoolAction.php` | `SetupSchoolAction` | `BaseAction` |
| `Aggregates/Account/Actions/ToggleUserStatusAction.php` | `ToggleUserStatusAction` | `BaseAction` |
| `Aggregates/Account/Actions/UpdateUserAction.php` | `UpdateUserAction` | `BaseAction` |
| `Aggregates/Setup/Actions/ValidateSetupTokenAction.php` | `ValidateSetupTokenAction` | `BaseAction` |

---

## Models

| File | Class |
|---|---|
| `Aggregates/Announcement/Models/Announcement.php` | `Announcement` |
| `Aggregates/GdprDeletionLog/Models/GdprDeletionLog.php` | `GdprDeletionLog` |
| `Aggregates/Setup/Models/Setup.php` | `Setup` |

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Livewire/AccountCloneDetector.php` | `AccountCloneDetector` | `Component` |
| `Aggregates/Account/Livewire/AdminManager.php` | `AdminManager` | `BaseRecordManager` |
| `Aggregates/Announcement/Livewire/AnnouncementManager.php` | `AnnouncementManager` | `Component` |
| `Livewire/ApplicationReview.php` | `ApplicationReview` | `Component` |
| `Livewire/AuditLogManager.php` | `AuditLogManager` | `Component` |
| `Aggregates/GdprDeletionLog/Livewire/GdprDeletionLogs.php` | `GdprDeletionLogs` | `Component` |
| `Aggregates/Setup/Livewire/SetupWizard.php` | `SetupWizard` | `Component` |
| `Aggregates/Account/Livewire/StudentManager.php` | `StudentManager` | `BaseRecordManager` |
| `Aggregates/Account/Livewire/SupervisorManager.php` | `SupervisorManager` | `BaseRecordManager` |
| `Aggregates/Account/Livewire/TeacherManager.php` | `TeacherManager` | `BaseRecordManager` |
| `Aggregates/Account/Livewire/UserManager.php` | `UserManager` | `BaseRecordManager` |

---

## Authorization Policies

| File | Policy |
|---|---|
| `Aggregates/GdprDeletionLog/Policies/GdprDeletionLogPolicy.php` | `GdprDeletionLogPolicy` |
| `Aggregates/Setup/Policies/SetupPolicy.php` | `SetupPolicy` |

---

## Console Commands

| Command Signature | Class | Description |
|---|---|---|
| `setup:install` | `SetupInstallCommand` | Provisions the system, runs database seeds, and initializes roles. |
| `setup:reset-token` | `SetupResetTokenCommand` | Generates a new setup token (usable only if installation is incomplete). |
| `admin:create` | `CreateAdminCommand` | Creates the initial superadmin account when none exists. |
| `admin:recover` | `RecoverAdminCommand` | Interactive command to reset a superadmin's password or re-create it. |
| `admin:recovery-show` | `ShowRecoveryKeyCommand` | Displays the current recovery key after confirmation. |
| `admin:recovery-path` | `ShowRecoveryPathCommand` | Displays the absolute file path of the recovery key. |

> [!NOTE]
> The legacy `admin:promote` command has been removed because role mappings and promotions are handled directly by functional/standard roles logic or user-management interfaces.

---

## File Organization

```
app/Domain/Admin/
├── Aggregates/           ← Aggregate roots
│   └── {Aggregate}/
│       ├── Actions/
│       ├── Models/
│       ├── Policies/
│       └── Livewire/
├── Http/
├── Livewire/
├── Types/
├── Services/
└── Support/
```

---

*For overview and business context, see [admin.md](admin.md)*

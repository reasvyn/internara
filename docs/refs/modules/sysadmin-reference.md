# SysAdmin — Technical Reference

## Description

Detailed structural and implementation reference for the **SysAdmin** module.

---

## Overview

Handles user administration, announcements, super admin recovery, system health monitoring, audit
logging, Pulse observability, and GDPR compliance.

## Actions

| File | Class | Extends |
|---|---|---|
| `Actions/ReadAdminDashboardAction.php` | `ReadAdminDashboardAction` | `BaseReadAction` |
| `Domain/Announcement/Actions/DeleteAnnouncementAction.php` | `DeleteAnnouncementAction` | `BaseCommandAction` |
| `Domain/Announcement/Actions/PublishAnnouncementAction.php` | `PublishAnnouncementAction` | `BaseCommandAction` |
| `Domain/Announcement/Actions/SendAnnouncementAction.php` | `SendAnnouncementAction` | `BaseCommandAction` |
| `Domain/Announcement/Actions/SendAnnouncementNotificationsAction.php` | `SendAnnouncementNotificationsAction` | `BaseCommandAction` |
| `Domain/Backups/Actions/CleanupBackupsAction.php` | `CleanupBackupsAction` | `BaseCommandAction` |
| `Domain/Backups/Actions/CreateBackupAction.php` | `CreateBackupAction` | `BaseCommandAction` |
| `Domain/Backups/Actions/DeleteBackupAction.php` | `DeleteBackupAction` | `BaseCommandAction` |
| `Domain/Backups/Actions/ReadBackupStatsAction.php` | `ReadBackupStatsAction` | `BaseReadAction` |

## Models

| File | Class | Extends |
|---|---|---|
| `Domain/Announcement/Models/Announcement.php` | `Announcement` | `BaseModel` |
| `Domain/Backups/Models/Backup.php` | `Backup` | `BaseModel` |
| `Domain/Observability/GdprDeletionLog/Models/GdprDeletionLog.php` | `GdprDeletionLog` | `BaseModel` |

## Enums

| File | Enum | Implements | Values |
|---|---|---|---|
| `Domain/Announcement/Enums/AnnouncementStatus.php` | `AnnouncementStatus` | `LabelEnum` | — |
| `Domain/Backups/Enums/BackupStatus.php` | `BackupStatus` | `LabelEnum` | — |
| `Domain/Backups/Enums/BackupType.php` | `BackupType` | `LabelEnum` | — |

## Policies

| File | Policy | Extends |
|---|---|---|
| `Domain/Backups/Policies/BackupPolicy.php` | `BackupPolicy` | `BasePolicy` |
| `Domain/Observability/GdprDeletionLog/Policies/GdprDeletionLogPolicy.php` | `GdprDeletionLogPolicy` | `BasePolicy` |

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Domain/Announcement/Livewire/AnnouncementManager.php` | `AnnouncementManager` | `BaseRecordManager` |
| `Domain/Announcement/Livewire/Forms/AnnouncementForm.php` | `AnnouncementForm` | `BaseFormView` |
| `Domain/Backups/Livewire/BackupManager.php` | `BackupManager` | `BaseRecordManager` |
| `Domain/Observability/GdprDeletionLog/Livewire/GdprDeletionLogs.php` | `GdprDeletionLogs` | `Component` |
| `Domain/Observability/Livewire/AccountCloneDetector.php` | `AccountCloneDetector` | `Component` |
| `Domain/Observability/Livewire/AuditLogManager.php` | `AuditLogManager` | `BaseRecordManager` |
| `Domain/Observability/Livewire/Pulse/RegistrationsCard.php` | `RegistrationsCard` | `Component` |
| `Domain/Observability/Livewire/Pulse/SystemCard.php` | `SystemCard` | `Component` |
| `Livewire/ApplicationReview.php` | `ApplicationReview` | `Component` |

## Events

| File | Event | Extends |
|---|---|---|
| `Domain/Backups/Events/BackupCompleted.php` | `BackupCompleted` | `BaseEvent` |
| `Domain/Backups/Events/BackupFailed.php` | `BackupFailed` | `BaseEvent` |

## Services

| File                                            | Service              | Purpose                       |
| ----------------------------------------------- | -------------------- | ----------------------------- |
| `Backups/Services/BackupRunner.php`             | `BackupRunner`       | Backup execution orchestration |
| `Observability/Services/EnvironmentAuditor.php` | `EnvironmentAuditor` | Environment health assessment |
| `Observability/Services/PulseGuard.php`         | `PulseGuard`         | Pulse monitoring guard        |

## Notifications

| File                                                      | Notification               |
| --------------------------------------------------------- | -------------------------- |
| `Announcement/Notifications/AnnouncementNotification.php` | `AnnouncementNotification` |
| `Backups/Notifications/BackupFailedNotification.php`      | `BackupFailedNotification` |

## Listeners

| File | Listener | Listens To |
|---|---|---|
| `Domain/Backups/Listeners/SendBackupFailedNotification.php` | `SendBackupFailedNotification` | — |

## Console Commands

| Command Signature        | Class                                  | Description                                             |
| ------------------------ | -------------------------------------- | ------------------------------------------------------- |
| `system:health`          | `SystemHealthCommand`                  | Comprehensive system health check with JSON output      |
| `system:cleanup`         | `SystemCleanupCommand`                 | Routine maintenance (prune resets, cache, logs)         |
| `system:cache-warm`      | `SystemCacheWarmCommand`               | Pre-warms config, views, events, settings, brand caches |
| `pulse:record-snapshots` | `PulseRecordSnapshotsCommand`          | Records Pulse metric snapshots                          |
| `announcements:publish`  | `PublishScheduledAnnouncementsCommand` | Publishes scheduled announcements                       |
| `admin:recover`          | `RecoverAdminCommand`                  | Interactive superadmin password reset                   |
| `admin:recovery-show`    | `ShowRecoveryKeyCommand`               | Displays current recovery key                           |
| `admin:recovery-path`    | `ShowRecoveryPathCommand`              | Shows recovery key file path                            |
| `notifications:prune`    | `PruneNotificationsCommand`            | Prunes old notifications                                |
| `backups:run`            | `SystemBackupCommand`                  | Runs manual database/storage backup                     |

## Pulse Recorders

| File                                               | Recorder               | Purpose                              |
| -------------------------------------------------- | ---------------------- | ------------------------------------ |
| `Observability/Recorders/SystemRecorder.php`       | `SystemRecorder`       | System health Pulse recording        |
| `Observability/Recorders/RegistrationRecorder.php` | `RegistrationRecorder` | Registration metrics Pulse recording |

## HTTP Controllers

| File | Controller | Extends |
|---|---|---|
| `Http/Controllers/AccountSlipController.php` | `AccountSlipController` | `BaseController` |
| `Http/Controllers/CronController.php` | `CronController` | `BaseController` |

## Routes

File: `routes/web/sysadmin.php` Named routes: `admin.users.index`, `admin.users.admins`,
`admin.users.students`, `admin.users.teachers`, `admin.users.supervisors`,
`admin.users.account-slip`, `admin.users.account-slips.batch`, `admin.gdpr-logs`, `admin.audit-log`,
`admin.accounts.clones`, `admin.backups`, `sysadmin.applications`, `sysadmin.announcements`, `cron`

## Views

Views are located in `resources/views/sysadmin/`. See [UI/UX](../../guides/ui-ux.md) for the design
system.

## Tests

Tests are located in `tests/SysAdmin/`. See [Testing](../../guides/infra/testing.md)
for the testing conventions.

## Factories

| Factory                  | Model             |
| ------------------------ | ----------------- |
| `AnnouncementFactory`    | `Announcement`    |
| `BackupFactory`          | `Backup`          |
| `GdprDeletionLogFactory` | `GdprDeletionLog` |

## Migrations

| Migration                         | Table                |
| --------------------------------- | -------------------- |
| `create_announcements_table`      | `announcements`      |
| `create_backups_table`            | `backups`            |
| `create_gdpr_deletion_logs_table` | `gdpr_deletion_logs` |

---

## Architectural Integration

- **Submodules**: `Announcement`, `Backups`, `Observability`
- **Business Logic**: `app/Modules/SysAdmin/`
- **Routing**: `routes/web/sysadmin.php`
- **Views**: `resources/views/sysadmin/`
- **Testing**: `tests/SysAdmin/`
- **Dependencies**: User, Academics, Core

_For overview and business context, see [sysadmin.md](sysadmin.md)._

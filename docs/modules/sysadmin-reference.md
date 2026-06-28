# SysAdmin — Technical Reference

> **Last updated:** 2026-06-17
> **Changes:** sync — add Backup model to Models table; add Backups Events and Support sections

## Description
Detailed structural and implementation reference for the **SysAdmin** module.

---


## Overview

Handles user administration, announcements, super admin recovery, system health monitoring, audit logging, Pulse observability, and GDPR compliance.

## Actions

| File | Class | Extends |
| ---- | ----- | ------- |
| `Actions/ReadAdminDashboardAction.php` | `ReadAdminDashboardAction` | `BaseReadAction` |
| `Announcement/Actions/DeleteAnnouncementAction.php` | `DeleteAnnouncementAction` | `BaseCommandAction` |
| `Announcement/Actions/PublishAnnouncementAction.php` | `PublishAnnouncementAction` | `BaseCommandAction` |
| `Announcement/Actions/SendAnnouncementAction.php` | `SendAnnouncementAction` | `BaseCommandAction` |
| `Backups/Actions/CreateBackupAction.php` | `CreateBackupAction` | `BaseCommandAction` |
| `Backups/Actions/DeleteBackupAction.php` | `DeleteBackupAction` | `BaseCommandAction` |
| `Backups/Actions/CleanupBackupsAction.php` | `CleanupBackupsAction` | `BaseCommandAction` |
| `Backups/Actions/ReadBackupHistoryAction.php` | `ReadBackupHistoryAction` | `BaseReadAction` |
| `Backups/Actions/ReadBackupStatsAction.php` | `ReadBackupStatsAction` | `BaseReadAction` |

---

## Models

| File | Class | Extends |
| ---- | ----- | ------- |
| `Announcement/Models/Announcement.php` | `Announcement` | `BaseModel` |
| `Backups/Models/Backup.php` | `Backup` | `BaseModel` |
| `Observability/GdprDeletionLog/Models/GdprDeletionLog.php` | `GdprDeletionLog` | `BaseModel` |

---

## Enums

| File | Enum | Implements | Values |
| ---- | ---- | ---------- | ------ |
| `Announcement/Enums/AnnouncementStatus.php` | `AnnouncementStatus` | `LabelEnum`, `StatusEnum` | draft, scheduled, published |
| `Backups/Enums/BackupStatus.php` | `BackupStatus` | `LabelEnum` | pending, running, completed, failed |
| `Backups/Enums/BackupType.php` | `BackupType` | `LabelEnum` | database, storage, both |

---

## Entities

| File | Class | Extends |
| ---- | ----- | ------- |
| `Announcement/Entities/AnnouncementState.php` | `AnnouncementState` | `BaseEntity` |
| `Backups/Entities/BackupState.php` | `BackupState` | `BaseEntity` |

---

## Policies

| File | Policy | Extends |
| ---- | ------ | ------- |
| `Backups/Policies/BackupPolicy.php` | `BackupPolicy` | `BasePolicy` |
| `Observability/GdprDeletionLog/Policies/GdprDeletionLogPolicy.php` | `GdprDeletionLogPolicy` | `BasePolicy` |

---

## Livewire Components

| File | Component | Extends |
| ---- | --------- | ------- |
| `Announcement/Livewire/AnnouncementManager.php` | `AnnouncementManager` | `Component` |
| `Backups/Livewire/BackupManager.php` | `BackupManager` | `Component` |
| `Livewire/ApplicationReview.php` | `ApplicationReview` | `Component` |
| `Observability/Livewire/AuditLogManager.php` | `AuditLogManager` | `Component` |
| `Observability/Livewire/AccountCloneDetector.php` | `AccountCloneDetector` | `Component` |
| `Observability/GdprDeletionLog/Livewire/GdprDeletionLogs.php` | `GdprDeletionLogs` | `Component` |
| `Observability/Livewire/Pulse/SystemCard.php` | `SystemCard` | `Component` |
| `Observability/Livewire/Pulse/RegistrationsCard.php` | `RegistrationsCard` | `Component` |

## Livewire Forms

| File | Form |
| ---- | ---- |
| `Announcement/Livewire/Forms/AnnouncementForm.php` | `AnnouncementForm` |

## Events

| File | Event | Dispatched By |
| ---- | ----- | ------------- |
| `Backups/Events/BackupCompleted.php` | `BackupCompleted` | `CreateBackupAction` |
| `Backups/Events/BackupFailed.php` | `BackupFailed` | `CreateBackupAction` |

## Services

| File | Service | Purpose |
| ---- | ------- | ------- |
| `Backups/Support/BackupRunner.php` | `BackupRunner` | Backup execution orchestration |

## Notifications

| File | Notification |
| ---- | ------------ |
| `Announcement/Notifications/AnnouncementNotification.php` | `AnnouncementNotification` |
| `Backups/Notifications/BackupFailedNotification.php` | `BackupFailedNotification` |

## Listeners

| File | Listener | Listens To |
| ---- | -------- | ---------- |
| `Backups/Listeners/SendBackupFailedNotification.php` | `SendBackupFailedNotification` | `BackupFailed` |

## Console Commands

| Command Signature | Class | Description |
| ----------------- | ----- | ----------- |
| `system:health` | `SystemHealthCommand` | Comprehensive system health check with JSON output |
| `system:cleanup` | `SystemCleanupCommand` | Routine maintenance (prune resets, cache, logs) |
| `system:cache-warm` | `SystemCacheWarmCommand` | Pre-warms config, views, events, settings, brand caches |
| `pulse:record-snapshots` | `PulseRecordSnapshotsCommand` | Records Pulse metric snapshots |
| `announcements:publish` | `PublishScheduledAnnouncementsCommand` | Publishes scheduled announcements |
| `admin:recover` | `RecoverAdminCommand` | Interactive superadmin password reset |
| `admin:recovery-show` | `ShowRecoveryKeyCommand` | Displays current recovery key |
| `admin:recovery-path` | `ShowRecoveryPathCommand` | Shows recovery key file path |
| `notifications:prune` | `PruneNotificationsCommand` | Prunes old notifications |
| `backups:run` | `SystemBackupCommand` | Runs manual database/storage backup |

## Pulse Recorders

| File | Recorder | Purpose |
| ---- | -------- | ------- |
| `Observability/Recorders/SystemRecorder.php` | `SystemRecorder` | System health Pulse recording |
| `Observability/Recorders/RegistrationRecorder.php` | `RegistrationRecorder` | Registration metrics Pulse recording |

## HTTP Controllers

| File | Controller | Extends | Purpose |
| ---- | ---------- | ------- | ------- |
| `Http/Controllers/AccountSlipController.php` | `AccountSlipController` | `BaseController` | Account slip downloads |
| `Http/Controllers/CronController.php` | `CronController` | `BaseController` | Health check cron endpoint |

## Services

| File | Service | Purpose |
| ---- | ------- | ------- |
| `Observability/Services/EnvironmentAuditor.php` | `EnvironmentAuditor` | Environment health assessment |
| `Observability/Services/PulseGuard.php` | `PulseGuard` | Pulse monitoring guard |

---

## Routes

File: `routes/web/sysadmin.php`
Naming pattern: `sysadmin.{resource}.{action}`

## Views

Views are located in `resources/views/sysadmin/`. See [UI/UX](../foundation/ui-ux.md) for the design system.

## Tests

Tests are located in `tests/{Feature,Unit}/SysAdmin/`. See [Testing](../infrastructure/testing.md) for the testing conventions.

## Factories

| Factory | Model |
| ------- | ----- |
| `AnnouncementFactory` | `Announcement` |
| `GdprDeletionLogFactory` | `GdprDeletionLog` |

## Migrations

| Migration | Table |
| --------- | ----- |
| `create_announcements_table` | `announcements` |
| `create_gdpr_deletion_logs_table` | `gdpr_deletion_logs` |

---

---

## Architectural Integration

- **Submodules**: `Announcement`, `Backups`, `Observability`
- **Business Logic**: `app/SysAdmin/`
- **Routing**: `routes/web/sysadmin.php`
- **Views**: `resources/views/sysadmin/`
- **Testing**: `tests/Feature/SysAdmin/`, `tests/Unit/SysAdmin/`
- **Dependencies**: User, Academics, Core

*For overview and business context, see [sysadmin.md](sysadmin.md).*

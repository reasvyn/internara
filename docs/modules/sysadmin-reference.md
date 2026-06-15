# SysAdmin — Technical Reference

> Last updated: 2026-06-15
> Changes: sync — remove stale UserManagement actions (belong to User module), fix action list with actual SysAdmin actions (ReadAdminDashboard, Backups)

Detailed structural and implementation reference for the **SysAdmin** module.

---

## Overview

Handles user administration, announcements, super admin recovery, system health monitoring, audit logging, Pulse observability, and GDPR compliance.

### Submodules

- `Announcement` — System announcements
- `Backups` — Database and storage backup management
- `Observability` — Health monitoring, Pulse, audit logs, GDPR

---

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

## Notifications

| File | Notification |
| ---- | ------------ |
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

## File Organization

```
app/SysAdmin/
├── Actions/ReadAdminDashboardAction.php
├── Announcement/
│   ├── Actions/
│   │   ├── DeleteAnnouncementAction.php
│   │   ├── PublishAnnouncementAction.php
│   │   └── SendAnnouncementAction.php
│   ├── Console/Commands/PublishScheduledAnnouncementsCommand.php
│   ├── Enums/AnnouncementStatus.php
│   ├── Entities/AnnouncementState.php
│   ├── Livewire/
│   │   ├── Forms/AnnouncementForm.php
│   │   └── AnnouncementManager.php
│   ├── Models/Announcement.php
│   └── Notifications/AnnouncementNotification.php
├── Backups/
│   ├── Actions/
│   │   ├── CleanupBackupsAction.php
│   │   ├── CreateBackupAction.php
│   │   ├── DeleteBackupAction.php
│   │   ├── ReadBackupHistoryAction.php
│   │   └── ReadBackupStatsAction.php
│   ├── Enums/BackupStatus.php
│   ├── Enums/BackupType.php
│   ├── Entities/BackupState.php
│   ├── Events/BackupCompleted.php
│   ├── Events/BackupFailed.php
│   ├── Listeners/
│   ├── Livewire/BackupManager.php
│   ├── Models/Backup.php
│   ├── Notifications/
│   ├── Policies/BackupPolicy.php
│   └── Support/BackupRunner.php
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

- **Submodules**: `Announcement`, `Backups`, `Observability`
- **Business Logic**: `app/SysAdmin/`
- **Routing**: `routes/web/sysadmin.php`
- **Views**: `resources/views/sysadmin/`
- **Testing**: `tests/Feature/SysAdmin/`, `tests/Unit/SysAdmin/`
- **Dependencies**: User, Academics, Core

*For overview and business context, see [sysadmin.md](sysadmin.md).*

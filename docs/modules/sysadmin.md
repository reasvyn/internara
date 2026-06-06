# SysAdmin — Documentation Overview

> Last updated: 2026-06-06
> Changes: Removed Settings submodule following Settings module extraction

Handles user administration, announcements, system health monitoring, audit logging, and GDPR compliance.

For complete technical reference including API, models, actions, and components, see [sysadmin-reference.md](sysadmin-reference.md).

---

## Key Principles

- Account management controls user lifecycle
- Announcements broadcast system-wide messages
- GDPR deletion logs ensure compliance
- Audit logging tracks all administrative changes
- Pulse monitoring provides system health visibility

---

## Context Boundary

Manages user account lifecycle, announcements, GDPR compliance, and system health monitoring. Works with User module for authentication and Core for base services. System-wide configuration is handled by the [Settings](settings.md) module.

---

## Module Rules

- **Super Admin Integrity & Constraints**: The root-level account (`superadmin`) is protected by strict integrity rules:
  *   **Uniqueness**: Only one superadmin account is allowed in the database.
  *   **Permanence**: The name is permanently locked to `Administrator` (or config default) and the username is locked to `superadmin`. Updates via standard actions are blocked.
  *   **Undeletability**: The superadmin account is completely undeletable. Model deletions or `deleting` events throw a `RuntimeException`.
  *   **Status**: The superadmin account must maintain the `PROTECTED` account lifecycle status.
- **Account Suspension**: Suspension preserves account data but blocks active user login sessions.
- **Auditing**: All account changes, status toggles, and recovery attempts are fully audit-logged via `SmartLogger`.

---

## Submodules

- **Account**: User account management — CRUD, status toggles, archiving, recovery keys
- **Announcement**: System-wide message broadcasting and scheduling
- **GdprDeletionLog**: GDPR-compliant deletion logging and compliance tracking

> **Note**: The **Settings** submodule has been extracted into its own standalone module. See [Settings](settings.md).

---

## CLI Commands

| Command | Purpose |
|---|---|
| `php artisan system:health` | Comprehensive system health check with JSON output support |
| `php artisan system:cleanup` | Routine maintenance: prune resets, cache tags, failed jobs, activity logs, media, and old log files |
| `php artisan system:cache-warm` | Pre-warms application caches (config, views, events, settings, brand) |
| `php artisan admin:create` | Creates the initial superadmin account when none exists |
| `php artisan admin:recover` | Interactive command to reset a superadmin's password or re-create it |
| `php artisan admin:recovery-show` | Displays the current recovery key after confirmation |
| `php artisan admin:recovery-path` | Displays the absolute file path of the recovery key |
| `php artisan notifications:prune` | Prunes old notification records |
| `php artisan pulse:record-snapshots` | Records Pulse monitoring snapshots |

---

## Error Handling & Failure Modes

- **Super admin integrity violation**: Any attempt to delete, rename, or duplicate the superadmin account throws a `RuntimeException`. Account status is locked to `PROTECTED`.
- **Account suspension on active user**: Suspending an account with active sessions logs out the user immediately. Failed suspension due to integrity constraints returns a `RejectedException`.
- **GDPR deletion compliance**: Deletion logs are append-only. Attempts to modify or delete a GDPR log entry are blocked at the policy layer (403).

---

## Quick References

### Actions & Business Logic
- **14** actions across all submodules
- Account lifecycle management, announcement CRUD + scheduling, GDPR deletion logging, health checks

### Data & Persistence
- **2** models: `Announcement`, `GdprDeletionLog`
- UUID PKs, `HasFactory`

### User Interface
- **12** Livewire components
- User manager, admin manager, announcement manager, audit log viewer, pulse dashboard

### Authorization
- **1** policy (plus BasePolicy inheritance)
- Superadmin has unrestricted access. Admin has most management access

---

For complete technical reference, see [sysadmin-reference.md](sysadmin-reference.md).

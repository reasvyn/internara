# SysAdmin — Documentation Overview

> Last updated: 2026-06-05
> Changes: Removed Setup submodule overview, wizard, and related metrics following Setup module extraction

Handles user administration, announcements, system health monitoring, audit logging, GDPR compliance, and system-wide configuration

For complete technical reference including API, models, actions, and components, see [sysadmin-reference.md](sysadmin-reference.md).

---

## Key Principles

- Account management controls user lifecycle
- Announcements broadcast system-wide messages
- GDPR deletion logs ensure compliance
- Settings provide system-wide configuration, cached for performance
- Audit logging tracks all administrative changes
- Pulse monitoring provides system health visibility

---

## Context Boundary

Manages user account lifecycle, announcements, runtime configuration, GDPR compliance, and system health monitoring. Works with User module for authentication and Core for base services. Provides configuration to all modules via the Setting submodule.

---

## Module Rules

- **Super Admin Integrity & Constraints**: The root-level account (`superadmin`) is protected by strict integrity rules:
  *   **Uniqueness**: Only one superadmin account is allowed in the database.
  *   **Permanence**: The name is permanently locked to `Administrator` (or config default) and the username is locked to `superadmin`. Updates via standard actions are blocked.
  *   **Undeletability**: The superadmin account is completely undeletable. Model deletions or `deleting` events throw a `RuntimeException`.
  *   **Status**: The superadmin account must maintain the `PROTECTED` account lifecycle status.
- **Account Suspension**: Suspension preserves account data but blocks active user login sessions.
- **Auditing**: All account changes, status toggles, and recovery attempts are fully audit-logged via `SmartLogger`.
- **Settings Access**: Only admin can modify settings.
- **Settings Propagation**: Setting changes propagate system-wide.
- **Sensitive Settings**: Sensitive settings require confirmation before modification.
- **Settings Audit**: Audit log records all setting changes.

---

## Submodules

- **Account**: User account management — CRUD, status toggles, archiving, recovery keys
- **Announcement**: System-wide message broadcasting and scheduling
- **GdprDeletionLog**: GDPR-compliant deletion logging and compliance tracking
- **Setting**: System-wide configuration — branding, mail, localization, academic years

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
- **Sensitive setting modification**: Changing sensitive settings requires explicit confirmation. Unconfirmed changes are rejected with a `ValidationFailedException`.
- **Account suspension on active user**: Suspending an account with active sessions logs out the user immediately. Failed suspension due to integrity constraints returns a `RejectedException`.
- **GDPR deletion compliance**: Deletion logs are append-only. Attempts to modify or delete a GDPR log entry are blocked at the policy layer (403).

---

## Quick References

### Actions & Business Logic
- **20** actions across all submodules
- Account lifecycle management, announcement CRUD + scheduling, GDPR deletion logging, settings CRUD + cache invalidation, health checks

### Data & Persistence
- **3** models: `Announcement`, `GdprDeletionLog`, `Setting`
- UUID PKs, `HasFactory`. Settings use key-value store with type enforcement (boolean, text, numeric, JSON, image, color)

### User Interface
- **13** Livewire components
- User manager, admin manager, announcement manager, audit log viewer, settings editor, pulse dashboard

### Authorization
- **2** policies
- Superadmin has unrestricted access. Admin has most management access. Settings modification is superadmin-only

---

For complete technical reference, see [sysadmin-reference.md](sysadmin-reference.md).

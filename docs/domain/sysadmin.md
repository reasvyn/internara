# SysAdmin — Documentation Overview

> Last updated: 2026-06-04
> Changes: Converted Status metadata to Changes format; updated Quick References with specific descriptions

Handles system setup, configuration, account administration, announcements, system health monitoring, audit logging, and GDPR compliance

For complete technical reference including API, models, actions, and components, see [sysadmin-reference.md](sysadmin-reference.md).

---

## Key Principles

- Setup wizard guides initial system configuration
- Account management controls user lifecycle
- Announcements broadcast system-wide messages
- GDPR deletion logs ensure compliance
- Settings provide system-wide configuration, cached for performance
- Audit logging tracks all administrative changes
- Pulse monitoring provides system health visibility

---

## Context Boundary

Manages system initialization, user account lifecycle, announcements, runtime configuration, and system health monitoring. Works with User domain for authentication and Core for base services. Provides configuration to all domains via the Setting aggregate.

---

## Domain Rules

- **Setup Execution**: The setup installation runs exactly once per system instance.
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

## Aggregates

- **Account**: User account management — CRUD, status toggles, archiving, recovery keys
- **Announcement**: System-wide message broadcasting and scheduling
- **GdprDeletionLog**: GDPR-compliant deletion logging and compliance tracking
- **Setting**: System-wide configuration — branding, mail, localization, academic years
- **Setup**: One-time system installation wizard and provisioning

---

## Quick References

### Actions & Business Logic
- **27** actions across all aggregates
- Setup installation, account lifecycle management, announcement CRUD + scheduling, GDPR deletion logging, settings CRUD + cache invalidation, health checks

### Data & Persistence
- **4** models: `Admin`, `Announcement`, `GdprDeletionLog`, `Setting`
- UUID PKs, `HasFactory`. Settings use key-value store with type enforcement (boolean, text, numeric, JSON, image, color)

### User Interface
- **14** Livewire components
- Setup wizard (7 steps), user manager, admin manager, announcement manager, audit log viewer, settings editor, pulse dashboard

### Authorization
- **3** policies
- Superadmin has unrestricted access. Admin has most management access. Settings modification is superadmin-only

---

For complete technical reference, see [sysadmin-reference.md](sysadmin-reference.md).

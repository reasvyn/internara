# Admin — Documentation Overview

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Comprehensive overview of the Admin domain.

Handles system setup, configuration, announcements, and account administration

For complete technical reference including API, models, actions, and components, see [admin-reference.md](admin-reference.md).

---

## Key Principles

- Setup wizard guides initial system configuration
- Account management controls user lifecycle
- Announcements broadcast system-wide messages
- GDPR deletion logs ensure compliance

---

## Context Boundary

Manages system initialization, user account lifecycle, and announcements. Works with User domain for authentication and Core for base services.

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

---

## Aggregates

- **Account**: Core business entity for account management
- **Announcement**: Core business entity for announcement management
- **GdprDeletionLog**: Core business entity for gdprdeletionlog management
- **Setup**: Core business entity for setup management

---

## Quick References

### Actions & Business Logic
- **23** actions across all aggregates
- Business logic operations for admin domain

### Data & Persistence
- **3** models managing core data
- Eloquent relationships and queries

### User Interface
- **11** Livewire components for real-time interaction
- Views in `resources/views/admin/`

### Authorization
- **2** authorization policies
- Role-based access control per resource

---

For complete technical reference, see [admin-reference.md](admin-reference.md).

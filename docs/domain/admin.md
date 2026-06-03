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

- Setup runs once per instance
- Super admin account (superadmin/Administrator) is immutable
- Account suspension preserves data but blocks login
- All account changes are audit-logged

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

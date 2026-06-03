# User — Documentation Overview

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Comprehensive overview of the User domain.

Handles authentication, user profiles, notifications, and account recovery

For complete technical reference including API, models, actions, and components, see [user-reference.md](user-reference.md).

---

## Key Principles

- Users are application accounts with roles
- Profiles contain personal information
- Authentication is secure and session-based
- Notifications provide in-app and email alerts
- Account recovery enables password reset

---

## Context Boundary

Core identity system. All other domains depend on User. Admin manages lifecycle. Settings configuration affects behavior.

---

## Domain Rules

- Unique usernames are generated from the email address local part (only lowercase alphanumeric) and incremented on collision (e.g., usertest, usertest1, usertest2)
- Unique emails required
- Passwords hashed using bcrypt with salting
- Account lockable by admin (preserves data)
- Recovery codes are cryptographically secure and single-use
- Super admin (superadmin/Administrator) immutable

---

## Aggregates

- **AccountRecovery**: Core business entity for accountrecovery management
- **AccountStatus**: Core business entity for accountstatus management
- **ActivationToken**: Core business entity for activationtoken management
- **Login**: Core business entity for login management
- **Notification**: Core business entity for notification management
- **Password**: Core business entity for password management
- **Profile**: Core business entity for profile management

---

## Quick References

### Actions & Business Logic
- **22** actions across all aggregates
- Business logic operations for user domain

### Data & Persistence
- **5** models managing core data
- Eloquent relationships and queries

### User Interface
- **15** Livewire components for real-time interaction
- Views in `resources/views/user/`

### Authorization
- **3** authorization policies
- Role-based access control per resource

---

For complete technical reference, see [user-reference.md](user-reference.md).

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

- **Username Generation**: Unique usernames are generated from the email address local part. Only lowercase alphanumeric characters are allowed (spaces and symbols are removed). In case of collisions, numeric suffix increments are appended (e.g., `usertest` -> `usertest1` -> `usertest2`). This is managed by `UserIdentifierGenerator`.
- **Email Requirements**: Unique email addresses are mandatory for all user accounts.
- **Password Security**: Passwords are securely hashed using Bcrypt with salting.
- **Account Lifecycles**: Accounts can be locked or suspended by administrators, which blocks active login sessions while preserving operational data.
- **Recovery Codes**: Generated recovery codes are cryptographically secure, hashed in the database, and single-use.
- **Super Admin Account**: The superadmin account is immutable, undeletable, and limited to a single instance.
- **Superadmin Role Mapping**: The superadmin role was renamed from `super_admin` to `superadmin`. To prevent breaking third-party packages (e.g., Spatie) and legacy code, the `User` model overrides role-related methods (`hasRole`, `assignRole`, `syncRoles`, etc.) to map `super_admin` to `superadmin` automatically.

---

## Aggregates

- **AccountRecovery**: Core business entity for accountrecovery management
- **AccountStatus**: Core business entity for accountstatus management
- **ActivationToken**: Core business entity for activationtoken management
- **Login**: Core business entity for login management
- **Notification**: Core business entity for notification management
- **Password**: Core business entity for password management
- **Profile**: Core business entity for profile management
- **SuperAdmin**: Core business entity for superadmin management and integrity constraints

---

## Quick References

### Actions & Business Logic
- **25** actions across all aggregates
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

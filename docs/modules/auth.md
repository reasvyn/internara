# Auth — Documentation Overview

> Last updated: 2026-06-06 Changes: refactor: extract Auth from User module into standalone Auth
> module

Authentication, authorization, account lifecycle, and recovery.

For complete technical reference including API, models, actions, and components, see
[auth-reference.md](auth-reference.md).

---

## Key Principles

- **Session-based auth** — Laravel's session driver with rate-limited login (5 attempts per 60s). No
  API tokens or JWT.
- **RBAC via Spatie** — Flat role hierarchy with `spatie/laravel-permission`. 5 system roles:
  superadmin, admin, teacher, student, supervisor.
- **Super Admin is immutable** — Name always "Administrator", username always "superadmin". Cannot
  be deleted, locked, or modified.
- **Accounts activate via token** — New users receive an activation code to set their initial
  password.
- **Recovery codes for lockout** — 10 single-use, cryptographically random codes. Admin generates,
  user redeems offline.

---

## Context Boundary

Auth owns all authentication-related concerns: login, password management, account activation,
recovery, RBAC, and super admin integrity. It depends on User for the `User` model but manages all
auth-specific data (activation tokens, recovery codes) within its own models.

---

## Module Rules

- **Login throttling**: 5 attempts per 60s per IP+identifier. Auto-lock after 10 failures.
- **Password reset**: 60-minute token expiry, single-use. Rate limited to 3 req/3600s.
- **Account recovery**: 3 attempts per 300s per username+IP.
- **Recovery codes**: Bcrypt hashed in storage, displayed once on generation, single-use.
- **Super admin**: Exactly one instance, immutable name/username, cannot be deleted.
- **Role mapping**: `super_admin` → `superadmin` transparently via `User` model overrides.

---

## Submodules

- **Permissions**: RBAC — `Role` enum, `CheckRoleMiddleware`, `UserPolicy`, `RoleRequest`.
- **SuperAdmin**: Integrity constraints — enforces single-instance, immutable name/username,
  prohibits deletion.
- **Login**: Email/username authentication with 4-step sequential validation.
- **ActivationToken**: Email verification tokens for new accounts.
- **AccountRecovery**: Recovery slip generation (admin), recovery code redemption, self-service
  unlock + password reset.
- **Password**: Hashing, validation, reset tokens (60 min expiry, single-use).

---

## Quick References

### Actions & Business Logic

- **13** actions across all submodules
- Login validation, password reset, account recovery, super admin management

### Data & Persistence

- **2** models: `ActivationToken`, `AccountRecoveryCode`
- UUID primary keys, `HasFactory` on all models

### User Interface

- **7** Livewire components
- Login page, activation form, password forms, recovery slip UI

### Authorization

- **1** policy: `UserPolicy`
- Role-based middleware via `CheckRoleMiddleware`

---

For complete technical reference, see [auth-reference.md](auth-reference.md).

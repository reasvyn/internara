# Auth

> **Last updated:** 2026-06-10

Authentication, authorization (RBAC), account lifecycle, login throttling, password management, account activation, and recovery.

## Purpose & Boundary

Auth owns all authentication and authorization concerns. It manages session-based login with rate limiting, role-based access control via Spatie, account activation via email tokens, password policies and reset, and account recovery via single-use recovery codes. It enforces super admin integrity constraints (immutable identity, non-deletable).

Out of scope: user profiles (User), account CRUD (SysAdmin), system configuration (Settings).

## Submodules

### Permissions
RBAC implementation using `spatie/laravel-permission`. Defines the `Role` string enum with 5 flat roles: `super_admin`, `admin`, `teacher`, `student`, `supervisor`. Provides `CheckRoleMiddleware` for route-level role enforcement and `UserPolicy` for model-level authorization. The `User` model transparently maps `super_admin` to Spatie's `superadmin` guard name.

### SuperAdmin
Integrity constraints enforcing exactly one super admin instance. Name is locked to `Administrator` (from config), username to `superadmin`. The account cannot be deleted, renamed, or duplicated. Status is permanently `PROTECTED`.

### Login
Email/username authentication with a 4-step sequential validation pipeline: identifier format, existence, account status, password hash. Rate-limited to 5 attempts per 60 seconds per IP+identifier combination. Auto-lock after 10 failures.

### Account
User account activation (migrated from `ActivationToken`). Email-based verification for newly provisioned accounts. Uses `ApiToken` model for token storage with secure hashing and soft-revocation.

### ApiTokens
General-purpose token management (`api_tokens` table). Supports activation tokens, recovery codes, and extensible token types. Tokens are hashed via `Hash::make()`, support soft-revocation (`revoked_at`), scoping, and expiry. Entity bridge `ApiToken::asActivationToken()` follows the `$model->asEntity()` pattern.

### AccountRecovery
Self-service unlock and password reset mechanism. Admin generates 10 single-use, cryptographically random recovery codes (Bcrypt hashed in storage, displayed once). Users redeem codes to unlock accounts and reset passwords without email dependency. Rate-limited to 3 attempts per 300 seconds per username+IP.

### Password
Password hashing and validation against configurable policy (minimum length, complexity rules). Reset tokens with 60-minute expiry, single-use. Rate-limited to 3 requests per 3600 seconds.

## Key Concepts

### Flat Role Hierarchy

Unlike hierarchical RBAC, Internara uses 5 flat roles with explicit permission checks. Super admin has unrestricted access via `BasePolicy::before()`. All other roles are checked by policy methods and middleware. Composite roles (mentor, mentee) are derived from group membership, not assigned via Spatie.

### Super Admin Immutability

The super admin is a system-level invariant. It cannot be deleted, have its name/username changed, or have its `PROTECTED` status altered. Enforcement happens at both the Action layer (`SetupSuperAdminAction` only accepts email+password) and the Model layer (blocking `deleting` events).

## Dependencies

- Core (base classes, contracts)
- User (User model for authentication identity)

## Used By

- SysAdmin (account lifecycle management)
- Every module that requires authorization checks

# User — Documentation Overview

> Last updated: 2026-06-04
> Changes: Rewrote overview with developer-friendly content, added error handling, failure modes, and CLI commands

Identity and access management: user accounts, authentication, profiles, notifications, account lifecycle, and recovery.

For complete technical reference including API, models, actions, and components, see [user-reference.md](user-reference.md).

---

## Key Principles

- **Accounts are not profiles** — `User` handles authentication (email, password, status). `Profile` holds personal data (phone, address, bio, avatar, emergency contact). The separation lets auth logic stay lean while profile data can be extended independently.
- **Session-based auth** — Laravel's session driver with rate-limited login (5 attempts per 60s). No API tokens or JWT. The system is single-tenant, self-hosted, and browser-based.
- **Role-driven routing** — After login, users are redirected to their role-specific dashboard: admin, teacher, supervisor, or student. Role priority determines which dashboard wins when a user has multiple roles.
- **Notifications are multi-channel** — in-app (custom database channel) + email (configurable SMTP). Broadcast for real-time bell counter updates.

---

## Context Boundary

User is the identity hub. Every domain references users via `morphToMany` or `foreignIdFor`. SysAdmin manages the account lifecycle (lock, suspend, archive). Auth owns the login/password boundary. User owns profiles, notifications, and recovery.

---

## Domain Rules

- **Unique emails** are mandatory. Duplicate detection runs on create and update.
- **Passwords** are hashed with Bcrypt. Minimum 8 characters, confirmed on registration.
- **Username generation** is automatic: derived from email local part, lowercase, alphanumeric only. Collisions append numeric suffix (`user` → `user1` → `user2`).
- **Account lifecycle**: 8 states — `provisioned`, `activated`, `verified`, `restricted`, `suspended`, `inactive`, `archived`, `protected`. State transitions are guarded: a suspended account cannot be archived without first being activated.
- **Superadmin is immutable**: name is always "Administrator", username is always "superadmin". Cannot be deleted, locked, or modified. Only one instance exists.
- **Superadmin role mapping**: The `User` model overrides Spatie's `hasRole()`, `assignRole()`, `syncRoles()` to map `super_admin` → `superadmin` automatically, preserving backward compatibility with third-party packages.
- **Recovery codes** are single-use, cryptographically random, and hashed in storage. Displayed once on generation.
- **Account lockout**: 10 failed login attempts triggers auto-lock. Admin can unlock via the Recovery Slip system.
- **Notifications**: in-app notifications are stored in a custom `notifications` table (not Laravel's default). The `CustomDatabaseChannel` writes to this table. Real-time unread count updates via Livewire polling and optional broadcast.

---

## Aggregates

- **Login**: Email/username authentication with 4-step sequential validation (account exists → not locked → password correct → active). 5 req/60s rate limit.
- **Password**: Hashing, validation, reset tokens (60 min expiry, single-use). 5 req/300s rate limit on reset.
- **ActivationToken**: Email verification tokens for new accounts. Expires after configurable duration.
- **AccountRecovery**: Recovery slip generation (admin), recovery code redemption, self-service unlock + password reset. 3 req/300s rate limit.
- **AccountStatus**: State machine with 8 states, guarded transitions, and immutable audit log of all transitions.
- **Profile**: Personal data editor, avatar upload (200x200 WebP via media library), emergency contact info.
- **Notification**: Full-page notification center with read/unread filter, bulk actions, and a navbar bell with live counter.
- **Dashboard**: Role-specific portals (admin, teacher, supervisor, student) displaying stats, metrics, and workflows tailored to each role's administrative context.
- **SuperAdmin**: Integrity constraints — enforces single-instance, immutable name/username, prohibits deletion.

---

## CLI Commands

| Command | Purpose |
|---|---|
| `php artisan user:recover-admin` | Emergency CLI recovery when all superadmins are locked out |
| `php artisan user:create` | Create a new user from the command line |
| `php artisan user:sync-roles` | Re-sync role assignments after migrations |

---

## Error Handling & Failure Modes

- **Locked out admin**: If all superadmins are locked out, use `php artisan user:recover-admin` to generate a one-time recovery link.
- **Email already exists**: The system enforces unique emails. Attempting to create a duplicate returns a `ConflictException`.
- **Superadmin deletion**: Attempting to delete or modify the superadmin account throws a `RejectedException` (domain invariant violation).
- **Notification delivery failure**: Failed email notifications are logged but do not block the user operation. The system uses Laravel's failed jobs table for retry.

---

## Quick References

### Actions & Business Logic
- **25** actions across all aggregates
- Login validation, password reset, profile updates, notification dispatch, account lifecycle transitions, recovery code management

### Data & Persistence
- **5** models: `User`, `Profile`, `LoginAttempt`, `AccountRecovery`, `Notification`
- UUID primary keys, `HasFactory` on all models, `SoftDeletes` on User and Profile

### User Interface
- **15** Livewire components
- Dashboards (admin, teacher, supervisor, student), profile editor, notification center, login page, password forms, recovery slip UI

### Authorization
- **3** policies: `UserPolicy`, `ProfilePolicy`, `NotificationPolicy`
- Role-based access: users can edit own profiles, admins can manage all users, superadmin bypasses all checks

---

For complete technical reference, see [user-reference.md](user-reference.md).

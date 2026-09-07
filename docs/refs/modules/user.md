# User — Profiles, Notifications & Dashboards

## Description

User identity, personal profiles, notification system, role-specific dashboards, and account
lifecycle status management.

## Purpose & Boundary

User is the identity hub of the application. It owns the `User` model (authentication identity), the
`Profile` model (personal data), the notification system (in-app + email), and role-specific
dashboards. Every module references users via `morphToMany` or `foreignIdFor` relationships.

Out of scope: authentication logic (Auth), account CRUD and lifecycle management (SysAdmin), RBAC
permission definitions (Auth).

## Submodules

### AccountStatus

Eight-state account lifecycle machine: `provisioned` → `activated` → `verified` → `restricted` /
`suspended` / `inactive` / `archived` / `protected`. State transitions are guarded — a suspended
account cannot be archived without reactivation. All transitions are immutably logged for audit.

### Profile

Personal data store separated from authentication identity. Handles: full name, phone, address, bio,
emergency contact, and avatar upload (200×200 WebP via Spatie Media Library). Soft-deletes
independently of User.

### Notification

Multi-channel notification system combining in-app (custom `notifications` table via
`CustomDatabaseChannel`), email (configurable SMTP), and real-time broadcast for bell counter
updates. Features: full-page notification center, read/unread filter, bulk mark-as-read, navbar bell
with live counter via Livewire polling.

### Dashboard

Role-specific portals rendered post-login: admin, teacher, supervisor, and student dashboards. Each
displays relevant metrics, pending actions, and workflow shortcuts. Role priority determines which
dashboard renders when a user holds multiple roles.

## Key Concepts

### Account-Profile Separation

The `User` model handles authentication concerns only (email, password, status). The `Profile` model
holds personal data. This separation keeps auth logic lean and allows profile schema to evolve
independently. Both are UUID-keyed (no soft deletes).

### Username Generation

Usernames are auto-derived from the email local part, lowercased and alphanumeric-only. Collisions
are resolved by appending numeric suffixes (`user` → `user1` → `user2`). Usernames are globally unique.

### Super Admin Role Mapping

The `User` model overrides Spatie's `hasRole()`, `assignRole()`, and `syncRoles()` to transparently
map `super_admin` → `superadmin`. This preserves backward compatibility with third-party packages
that expect the standard Spatie guard name.

## Dependencies

- Core (base classes, contracts, SmartLogger)
- Auth (role mappings)

## Used By

All modules (via user foreign keys, morph relationships, or policy checks).

## Design Principles

- **User and Profile are separate concerns** — the `User` model handles authentication (email, password, status); the `Profile` model holds personal data (name, phone, avatar, bio). This separation keeps auth logic lean and allows the profile schema to evolve independently. Both are UUID-keyed and independently soft-deletable.
- **Account status is a state machine with guarded transitions** — the eight states (`provisioned` → `activated` → `verified` → `restricted` / `suspended` / `inactive` / `archived` / `protected`) have explicit transition rules. A suspended account cannot be archived without reactivation. All transitions are immutably logged. The state machine is enforced at the Action layer, not guessed at the call site.
- **Username is auto-derived and collision-resolved** — usernames are lowercased alphanumeric derived from the email local part. Collisions append numeric suffixes (`user` → `user1` → `user2`). No manual username entry means no user-controlled collision or profanity.
- **Notifications are dual-channel with real-time counter updates** — in-app (database) and email channels fire together via the notification pipeline. The navbar bell counter uses Livewire polling for near-real-time updates. Bulk mark-as-read operations target the notification set, not individual rows.

## How It Works

*Content to be added — verify against actual implementation.*

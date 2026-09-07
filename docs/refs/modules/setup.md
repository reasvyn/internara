# Setup — One-Time Install Wizard

## Description

One-time system installation wizard, environment auditing, initial database provisioning (roles,
academic years, super admin), and setup token lifecycle management.

## Purpose & Boundary

Setup handles the system's first boot experience. It runs exactly once per installation lifetime,
performing environment readiness checks, seeding foundational database records, collecting school
and admin information through a multi-step wizard, and finalizing the system for production use.
After finalization, all setup routes and actions are permanently disabled.

Out of scope: runtime system administration (SysAdmin), ongoing configuration (Settings), daily
operations.

## Submodules

### Installer

Core installation orchestration via `php artisan setup:install`. Provisions the database schema
(migrations), seeds base roles (`super_admin`, `admin`, `teacher`, `student`, `supervisor` via
Spatie), creates the initial academic year, generates a cryptographically secure setup token, and
marks the system as installed. With `--with-dummy`, the demo dataset (`DummySeeder`) is seeded
after provisioning in any environment when explicitly requested (installation spec FR-C10, NFR-S13;
dummy-data spec FR-E6).

### Wizard

6-step browser-based setup wizard: environment audit → super admin account (email + password) →
school profile (name, NPSN, address) → department (name, description) → finalization & recovery key
→ complete. Each step validates before proceeding.

### Setup Token

Single-use, time-limited (default 60 minutes), cryptographically random token stored encrypted in the
database. Required to access any setup route. Can be regenerated via
`php artisan setup:reset-token` only if installation is not yet finalized.

### SystemProvisioner

Handles the atomic seeding of initial records: Spatie roles, default academic year, and admin user
placeholder. All seeding occurs within a single database transaction. Failure rolls back the entire
provisioning.

## Key Concepts

### Single Execution Guarantee

The `is_installed` flag in settings permanently disables all setup actions and routes once
finalization completes. Running `php artisan setup:install` on an installed system throws a
`ModuleException`. This is the primary security boundary between setup and runtime.

### Token Security

The setup token follows a strict lifecycle: generated encrypted → stored in database → one-time
redeem during finalization → invalidated on completion. The token file (`.setup-token`) is created
during CLI installation for headless environments and must be secured appropriately.

### Super Admin

The super admin account has a fixed, immutable identity provisioned during setup:

| Rule | Value |
|------|-------|
| Name | Always `Super Admin` (from `config('setup.defaults.admin_name')`) — not editable |
| Username | Always `superadmin` — not editable |
| Status | `PROTECTED` — cannot be deleted, locked, or suspended |
| Role | `superadmin` — single account invariant |

Creation flows: `SetupSuperAdminAction` (wizard) and `InitializeSuperAdminAction` (CLI) both read
the name/username from config. Integrity is enforced by `SuperAdminIntegrityRules` — the name and
username must match the config values, so a config change alone does not rename an existing account
(spec 8NZAU DD-4).

## Dependencies

- Core (base classes, SmartLogger)
- Auth (role seeding, super admin creation)
- Academics (initial academic year)
- Settings (school profile storage as `school.*` keys)

## Used By

- SysAdmin (recovery commands reference setup token)

## Design Principles

- **Setup runs exactly once** — the `is_installed` flag permanently disables all setup routes and actions after finalization. Re-running `php artisan setup:install` on an installed system throws `ModuleException`. This is the primary security boundary between the installation phase and production runtime.
- **System provisioning is fully atomic** — role seeding, initial academic year creation, and admin placeholder provisioning happen in a single database transaction. If any step fails, the entire provision rolls back. No partial state is left behind.
- **The super admin is provisioned from config, not wizard input** — `SetupSuperAdminAction` reads name and username from `config('setup.defaults.*')`. Config changes after provisioning do not retroactively rename the super admin account; the invariant is enforced by `SuperAdminIntegrityRules`.
- **Setup token is one-time and time-limited** — the token is generated cryptographically, stored encrypted, redeemed once during finalization, and then invalidated. Regeneration is blocked after finalization. The token is the access key to the setup wizard, not a long-lived credential.

## How It Works

*Content to be added — verify against actual implementation.*

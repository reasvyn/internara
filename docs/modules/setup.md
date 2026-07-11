# Setup — One-Time Install Wizard

> **Last updated:** 2026-07-11 **Changes:** sync — initial metadata sync with new format

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
marks the system as installed.

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

## Dependencies

- Core (base classes, SmartLogger)
- Auth (role seeding, super admin creation)
- Academics (initial academic year)
- Settings (school profile storage as `school.*` keys)

## Used By

- SysAdmin (recovery commands reference setup token)

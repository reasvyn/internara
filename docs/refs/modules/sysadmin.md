# SysAdmin — User Management, Announcements & Audit

## Description

User account administration, system-wide announcements, observability and health monitoring, audit
logging, and GDPR compliance tracking.

## Purpose & Boundary

SysAdmin provides the administrative tooling for managing user accounts across the system (CRUD,
status toggles, archiving), broadcasting system-wide announcements, monitoring system health via
Laravel Pulse, and ensuring GDPR compliance through deletion logging. It is the operational control
panel for superadmin and admin users.

Out of scope: individual user profiles (User), authentication logic (Auth), system configuration
key-value store (Settings).

## Submodules

### Account

User account lifecycle management: create, read, update, lock, suspend, archive, and delete user
accounts (with super admin integrity safeguards). Includes recovery key generation for admin-level
account recovery. All mutations are audit-logged via SmartLogger.

### Announcement

System-wide message broadcasting with scheduling support. Announcements can target specific roles or
all users (`scheduled_at`, `target_roles`, status flow).

### Observability

System health monitoring via Laravel Pulse dashboards, audit log viewer, GDPR deletion compliance
logs, and environment auditing. Contains the `GdprDeletionLog` model for tracking data deletion
requests. Provides CLI commands for health checks, cache warming, and system cleanup.

## Key Concepts

### Super Admin Integrity

The super admin account is protected at multiple enforcement points: uniqueness (exactly one),
permanence (undeletable), immutability (name/username locked), and status (locked to `PROTECTED`).
These safeguards prevent accidental or malicious lockout.

### GDPR Compliance

Deletion logs are append-only. GDPR data deletion requests are tracked with timestamps, deleted
record summaries, and operator identity. Logs cannot be modified or deleted after creation.

### Audit Trail

All administrative actions — account creation, suspension, role changes, announcement publishing —
are dual-logged via SmartLogger to both the system channel (detailed debug) and the activity channel
(immutable, PII-masked audit records).

## Dependencies

- Core (base classes, SmartLogger)
- Auth (permissions, super admin constraints)
- User (User model, account status state machine)

## Used By

- Setup (initial admin creation)

## Design Principles

- **All administrative mutations are dual-logged** — SmartLogger writes to both the system channel (detailed debug) and the activity channel (immutable, PII-masked audit records). Account creation, suspension, role changes, and announcement publishing are all traceable to an operator and a timestamp.
- **GDPR deletion logs are append-only** — deletion requests are tracked with timestamps, deleted record summaries, and operator identity. Logs cannot be modified or deleted after creation. This makes the deletion record legally meaningful for compliance audits.
- **Super admin integrity is enforced at multiple layers** — uniqueness (exactly one), permanence (undeletable), immutability (name/username locked), and `PROTECTED` status are enforced at the Model layer (blocking events) and the Action layer (no update path). No single point of failure can bypass these safeguards.
- **Pulse health counters are passive, not blocking** — Pulse metrics (incident counters, operational dashboards) are updated by background events, never by synchronous health checks that could slow down workflows. A Pulse failure does not block user-facing operations.

## How It Works

SysAdmin is the module a school administrator lives in, and its unifying concern is accountability
rather than feature delivery. Every consequential action it takes — creating an account, locking
one, publishing an announcement, running or deleting a backup — is written to the audit trail
through the shared logger, so the answer to "who changed this" exists independently of anyone's
memory.

Account management is deliberately more cautious than a generic CRUD screen would be. The super
admin is protected from deletion and duplication, admin-level accounts carry a recovery key so the
school can get back in without a server console, and destructive actions are distinguished from
ordinary ones rather than sitting in the same list as "edit".

Operational safety is the other half. Backups are created on demand, retained on a schedule, and
purged on a policy that an administrator can see and adjust, because a backup strategy nobody
understands is not a backup strategy. Announcements are role-targeted and can be scheduled, which
is how a school communicates a deadline without mailing every parent. And the compliance surfaces
— audit logs, GDPR deletion records, account review — exist because accreditation and data
protection both ask questions the system should be able to answer directly.

# Admin Domain

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — Consolidated Admin operations, System Oversight, and Setup Bootstrap

## Purpose

The **Administration** domain manages system-wide operations that span across multiple domains. This includes administrator oversight, system-wide role-based user CRUD, GDPR compliance logging, broadcast announcements, audit log viewers, custom dashboard monitoring (Laravel Pulse metrics), and the first-run installation and setup process.

It is the control room of the system. System administrators use the Administration panels to monitor server health, check security logs, review system backup paths, orchestrate user accounts, and bootstrap or recover the system.

---

## Design Principles

### 1. Unified User Management and Role Control
- Provides unified CRUD managers for all five base user roles: Students, Teachers, Supervisors, Admins, and Super Admins.
- Assignment check rules prevent non-super-admins from assigning the `super_admin` role.
- Enforces system username guidelines and blocks reserved authoritative names (`root`, `sysadmin`, etc.) for normal accounts.

### 2. GDPR Compliance and Right to Erasure
- The system logs all user deletion operations in `GdprDeletionLog` to maintain an audit trail of user data removal.
- Deleting a user records the acting administrator, target email address, date of deletion, and deletion reason. The records are permanent and read-only.

### 3. Role-Targeted Announcement Lifecycle
Announcements are broadcast messages targeted at specific user roles:
- Lifecycle follows `AnnouncementStatus`: `DRAFT` ➔ `SCHEDULED` ➔ `PUBLISHED`.
- **Scheduled Broadcasts**: Administrators write announcements and select target roles. Setting a future date schedules the broadcast. The system scheduler runs every minute (`PublishScheduledAnnouncementsCommand`) to publish scheduled announcements.
- **Delivery Channels**: Announcements are sent via database alerts and queued emails using `AnnouncementNotification`.

### 4. Real-time Security Monitoring and Pulse Analytics
- **Audit Logs**: Provides a central, read-only list of activity log records (via Core's `ActivityLog`) allowing search, level filters, and target user matching.
- **Pulse Integration**: Captures registration rates and system resource metrics using customized recorders (`RegistrationRecorder`, `SystemRecorder`) displayed in dashboard cards.

### 5. System Bootstrap as Administration Foundation
- **Setup Wizard**: The domain manages the first-run installation process via a 7-step wizard: environment check, school details, department setup, initial admin account credentials, and finalization.
- **Dormancy and Recovery**: Once successfully installed, the setup route is locked out and throws a 404. CLI-based recovery is provided to recreate the default super admin or reset setup tokens.

---

## Domain Boundary

### Technical Ownership
- **User CRUD Oversight**: CRUD forms for all roles, bulk archiving, account status overrides.
- **Announcement Engine**: Broadcast creations, scheduling calendars, role-targeted notification dispatches.
- **GDPR Logs**: Storing, retrieving, and sealing account deletion trails.
- **Recovery Infrastructure**: Saving recovery private keys to isolated private storage and exposing path summaries.
- **Livewire Pulse Cards**: Visual card components displaying memory, disk, database, and enrollment rates.
- **System Setup Bootstrap**: First-run installation flow, environment checks, and admin credentials bootstrapping.

### Dependencies
- **Core**: Uses base actions, base policies, `SmartLogger`, and activity models.
- **User**: Interfaces directly with the base `User` and `Profile` models to execute administrative updates.
- **Enrollment / Guidance / Program**: Integrates dashboard cards with statistics from these domains.
- **Academics**: Provisions school details and study programs during setup.

---

## Domain Rules & Invariants

- **R1 — Deletion Auditing**: Deleting any user account must create a corresponding `GdprDeletionLog` entry before committing the deletion transaction.
- **R2 — Role Promotion Restrictions**: Only users with the `super_admin` role can promote other users to `super_admin` or edit super admin accounts.
- **R3 — Super Admin Protection**: The Super Admin account is protected from being locked or deleted by any user (including themselves).
- **R4 — Future Schedule Boundaries**: Scheduled announcements must have a `scheduled_at` timestamp in the future. Draft announcements have a null `scheduled_at` timestamp.
- **R5 — Immutable Deletion Logs**: GDPR deletion log records are read-only; no updates or deletions are allowed via policies or actions.
- **R6 — Setup Dormancy**: Once setup is committed, further HTTP setup access is permanently blocked (returning 404).

---

## Key Features

- **Centralized User Workspace**: Integrated manager combining filters, text searches, bulk actions, and CSV imports/exports for all user accounts.
- **Admin Setup & CLI Promoter**: Artisan command to promote users (`system:promote`) and interactive command to create new admins (`system:admin:create`).
- **Markdown Announcement Broadcaster**: Composer supporting Markdown syntax, scheduled publishing, and role-based audience targets.
- **GDPR Auditor Panel**: Admin logs page showing data deletion histories.
- **Pulse Server Health Grid**: Live charts displaying database queries, CPU usages, memory levels, and student registration curves.
- **CSV User Import Utility**: Upload template CSVs to bulk-create students, teachers, or supervisors with inline row validation and errors summary.
- **Offline Account Slips Generator**: Admin tool generating printable PDFs with login credentials and recovery instructions to deliver offline to new users.
- **Audit Log Viewer**: High-performance grid displaying system operations, filterable by date, action category, or actor IP.
- **7-step Setup Wizard**: Environment audit, school profile setup, study program registration, initial administrator credentials setup, and installation finalize.
- **CLI Installer and Recovery**: CLI recovery commands to regenerate setup tokens or hard-provision super admin passwords.

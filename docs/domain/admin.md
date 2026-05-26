# Admin Domain

## Purpose

Admin provides the system-level management interface for operators — super
admins and admins who need oversight across all business domains. Unlike every
other domain, Admin owns no primary business data of its own. Instead, it
provides the tools to inspect, modify, and manage data that belongs to other
domains: creating users (User domain), reviewing registrations (Registration
domain), managing mentor assignments (Mentor domain), broadcasting system-wide
announcements, fulfilling GDPR data subject requests, and browsing the
centralized audit log. Admin exists because operational users need a unified,
cross-domain view that no single business domain can provide.

## Boundary

**In scope:** Cross-domain user CRUD (create accounts in any role, lock, unlock,
archive, reassign roles), system-wide announcements with role-targeted delivery,
GDPR data export assembly (gathering personal data from all domains), GDPR
erasure requests (anonymization preserving audit integrity), centralized audit
log browsing with cross-domain filtering, registration review and override for
edge cases, mentorship assignment oversight, system configuration overrides,
bulk operations across cohorts.

**Out of scope:** Authentication and login (Auth domain owns identity
verification), password management (Auth domain handles password rules and
resets), profile editing by the user themselves (User domain), business domain
decisions like placement allocation (Placement domain), content authoring beyond
announcements (Internship domain owns reports), day-to-day
mentoring activities (Mentor domain).

## Key Concepts

**User Management.** Admins create and manage user accounts across all five roles
(super_admin, admin, teacher, student, supervisor). The creation process
triggers the Auth domain's provisioning workflow — new accounts start in a
PROVISIONED state and require the user to activate them via email verification.
Locking and unlocking invokes Auth's account state machine, which records the
reason and the acting admin's identity. Role reassignment is supported with a
critical guard: only a super_admin can grant or revoke the super_admin role,
preventing privilege escalation. Account archival moves the user to Auth's
ARCHIVED state — data preserved, login permanently blocked. All user management
operations produce detailed audit log entries.

**Announcements.** A broadcast system for time-sensitive messages with a
three-state lifecycle: DRAFT → SCHEDULED → PUBLISHED. Announcements have a
title, body text (Markdown supported), type (info/success/warning/error),
optional link, and targeting rules by role. DRAFT announcements are saved
without sending notifications. SCHEDULED announcements are set to publish
automatically at a future date (checked every minute by the scheduler).
PUBLISHED announcements send notifications to targeted users immediately.
Even after publishing, an announcement can be deleted for emergency takedowns
(e.g., incorrect or panic-inducing content). There is no reply or discussion
threading.

**GDPR Compliance.** Two data subject request workflows are supported. Data
export: when a user requests their personal data, an admin triggers a
cross-domain collection process. The system gathers all personal data from every
domain that holds it — User records, Profile details, Registration data, Logbook
entries, Assignment submissions, Attendance records, Evaluation results, and
Incident reports involving the user. The data is assembled into a structured,
portable package (typically JSON or CSV) and delivered to the requesting user.
Erasure: when a right-to-erasure request is received, the admin processes it by
anonymizing personal data across all domains. Personal fields are replaced with
anonymized or placeholder values while foreign keys, relationships, and audit
references are preserved. Hard deletion is avoided to maintain referential
integrity and the audit trail.

**Audit Log Browsing.** A centralized, read-only view of every significant
action logged through Core's SmartLogger. The interface provides powerful
filtering: by user (who performed the action), by action type (user_created,
registration_approved, etc.), by domain module, by date range, and by severity
level. Each log entry shows the timestamp, acting user, action description,
target entity, and any associated payload data. No entries in the audit log can
be deleted or modified through the UI — the log is append-only by design. The
only mechanism for log removal is Core's scheduled cleanup command, which prunes
entries older than a configurable retention period.

**Registration Oversight.** Admins have a cross-program view of all registrations
with filtering by status, program, and department. This view enables exception
handling: manually approving registrations that need human intervention,
overriding placement assignments in edge cases, handling late registrations that
missed automated windows, managing withdrawals and reinstatements, and reviewing
registrations flagged by automated checks (suspicious documents, incomplete
requirements). This oversight capability ensures that automated workflows have
an administrative escape hatch for situations that don't fit the standard
process.

## Requirements

### User Stories & Rules

**User Management**
- **Admin:** As an admin, I want to create user accounts in any role so that teachers, supervisors, and students can access the system
- **Admin:** As an admin, I want to update user details so that accounts stay accurate
- **Admin:** As an admin, I want to lock or unlock user accounts so that I can respond to security concerns
- **Admin:** As an admin, I want to archive inactive accounts so that the user list stays manageable
- Bulk operations (batch user creation, mass operations) must provide result summaries: counts of succeeded, failed, and skipped
- Account lock and unlock must record the acting admin's identity and the reason in the audit log

**Role & Permissions**
- **Super Admin:** As a super admin, I want to assign or revoke roles so that access levels are properly managed
- Only super_admin can assign or revoke the super_admin role — enforced at the policy level, not just the UI
- No admin can edit their own role, permissions, or account status through the admin interface (self-service prevention)

**Announcements**
- **Admin:** As an admin, I want to create draft announcements so that I can prepare content before publishing
- **Admin:** As an admin, I want to schedule announcements for future delivery so that they publish automatically at the right time
- **Admin:** As an admin, I want to publish announcements targeted by role so that I can communicate with specific user groups
- **Admin:** As an admin, I want to delete published announcements so that I can remove incorrect or panic-inducing messages
- **User:** As a user, I want to view system announcements so that I stay informed about important updates
- Announcements follow a DRAFT → SCHEDULED → PUBLISHED lifecycle enforced by the `AnnouncementStatus` enum
- Notifications are only sent when an announcement reaches PUBLISHED status (via `SendAnnouncementAction`)
- Scheduled announcements are published automatically by `announcements:publish` command (runs every minute)
- Published announcements can be deleted — this is intentional for emergency takedowns
- Targeting is by role; announcements sent to specific roles exclude users who share the sender's roles

**GDPR Compliance**
- **Admin:** As an admin, I want to fulfil GDPR data export requests so that users can obtain their personal data
- **Admin:** As an admin, I want to process GDPR erasure requests so that user data is anonymized upon request
- GDPR erasure anonymizes rather than hard-deleting: personal fields are overwritten with anonymized values while foreign keys and record relationships are preserved for system integrity

**Audit & Oversight**
- **Admin:** As an admin, I want to browse the centralized audit log with filtering so that I can investigate actions across all domains
- **Admin:** As an admin, I want to review registrations across all programs so that I can handle edge cases and exceptions
- All admin CRUD operations are logged at a higher audit retention priority than standard user actions
- Livewire components must return `: View` for type safety

### Key Operations

| Action | Description |
|--------|-------------|
| `CreateUserAction` | Creates a new user account in any role |
| `UpdateUserAction` | Updates an existing user's details |
| `DeleteUserAction` | Deletes a user account |
| `ToggleUserStatusAction` | Toggles user account active/inactive status |
| `ArchiveStudentAccountsAction` | Batch archives student accounts |
| `SendAnnouncementAction` | Creates and sends a system-wide announcement (supports draft/scheduled/published) |
| `SaveRecoveryKeyAction` | Saves a recovery key for emergency access |
| `ReadRecoveryKeyAction` | Reads a stored recovery key |
| `GetAdminDashboardStatsAction` | Computes admin dashboard statistics |

### Technical Reference

| Layer | Artifacts |
|-------|-----------|
| **Models** | `Announcement`, `GdprDeletionLog` |
| **Enums** | `AnnouncementStatus` (DRAFT, SCHEDULED, PUBLISHED) |
| **Livewire** | `UserManager`, `AdminManager`, `StudentManager`, `TeacherManager`, `SupervisorManager`, `MentorManager`, `MenteeManager`, `AnnouncementManager`, `AuditLogManager`, `ApplicationReview`, `AccountCloneDetector`, `GdprDeletionLogs` |
| **Console** | `PublishScheduledAnnouncementsCommand` (runs every minute via scheduler) |

## Dependencies

| Dependency | Reason |
|---|---|
| Auth | Role enum consumed for role assignment UI; account state machine for lock/unlock/archive 
|
| User | User and Profile models are the primary targets for CRUD operations |
| Mentee | Student enrollment data displayed in registration oversight views |
| Mentor | Mentor assignment data for management and reassignment |
| Registration | Registration records reviewed and overridden during exceptional workflows |
| Core | BaseAction for operation consistency, SmartLogger for audit, BaseRecordManager for admin 
CRUD patterns |



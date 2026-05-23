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
announcements (Internship domain owns briefings and reports), day-to-day
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

**Announcements.** A simple broadcast system for time-sensitive messages.
Announcements have a subject, body text, optional file attachments, and
targeting rules. Targeting can be by role (all students, all teachers, all
users), by department, by program, or by individual user. Announcements support
scheduling (compose now, publish later) and expiration (auto-hide after a
specified date). Once published, an announcement is immutable — corrections
require publishing a new announcement that can reference the original.
Announcements appear as banners or list items on targeted users' dashboards.
There is no reply or discussion threading.

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

- **Admin:** As an admin, I want to create and manage user accounts across all roles so that the right people have system access
- **Admin:** As an admin, I want to lock or unlock user accounts so that I can respond to security concerns
- **Super Admin:** As a super admin, I want to assign or revoke roles so that access levels are properly managed
- **Admin:** As an admin, I want to publish announcements targeted by role so that I can communicate with specific user groups
- **Admin:** As an admin, I want to fulfil GDPR data export requests so that users can obtain their personal data
- **Admin:** As an admin, I want to process GDPR erasure requests so that user data is anonymized upon request
- **Admin:** As an admin, I want to browse the centralized audit log with filtering so that I can investigate actions across all domains
- **Admin:** As an admin, I want to review registrations across all programs so that I can handle edge cases and exceptions
- **User:** As a user, I want to view system announcements so that I stay informed about important updates
- **User:** As a user, I want to manage my notification preferences so that I receive relevant alerts
- Only super_admin can assign or revoke the super_admin role — enforced at the
  policy level, not just the UI.
- No admin can edit their own role, permissions, or account status through the
  admin interface (self-service prevention).
- Account lock and unlock MUST record the acting admin's identity and the reason
  in the audit log.
- GDPR erasure anonymizes rather than hard-deleting: personal fields are
  overwritten with anonymized values while foreign keys and record relationships
  are preserved for system integrity.
- Announcements are immutable after publishing — corrections require a new
  announcement that can reference the superseded original.
- All admin CRUD operations are logged at a higher audit retention priority than
  standard user actions.
- Bulk operations (batch user creation, mass registration approval) must provide
  result summaries: counts of succeeded, failed, and skipped operations.
- All Livewire components return `: View` for type safety.

### Key Operations

| Action | Description |
|--------|-------------|
| `CreateUserAction` | Creates a new user account in any role |
| `UpdateUserAction` | Updates an existing user's details |
| `DeleteUserAction` | Deletes a user account |
| `ToggleUserStatusAction` | Toggles user account active/inactive status |
| `ArchiveStudentAccountsAction` | Batch archives student accounts |
| `SendAnnouncementAction` | Creates and sends a system-wide announcement |
| `SendNotificationAction` | Sends a notification to a specific user |
| `GetNotificationsAction` | Retrieves pending notifications for a user |
| `MarkAsReadAction` | Marks a single notification as read |
| `MarkAllAsReadAction` | Marks all notifications as read |
| `DeleteNotificationAction` | Deletes a notification |
| `GetAdminDashboardStatsAction` | Computes admin dashboard statistics |

### Technical Reference

| Layer | Artifacts |
|-------|-----------|
| **Models** | `Notification`, `GdprDeletionLog` |
| **Livewire** | `UserManager`, `AdminManager`, `StudentManager`, `TeacherManager`, `SupervisorManager`, `MentorManager`, `MenteeManager`, `AnnouncementManager`, `AuditLogManager`, `ActivityFeedManager`, `NotificationCenter`, `NotificationBell`, `ApplicationReview`, `AccountCloneDetector`, `GdprDeletionLogs` |

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



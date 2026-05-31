# Admin Domain

## Purpose

Admin provides system-level management across all domains — user CRUD, announcements,
GDPR compliance, and system oversight.

---

## Design Principles

### 1. User CRUD with Role Enforcement

User management gates role assignment. Only super_admin can assign the super_admin role.
Reserved authoritative names are blocked for non-super-admin users.

### 2. Announcement Lifecycle

Announcements flow through DRAFT → SCHEDULED → PUBLISHED. Scheduled announcements are
auto-published by scheduler every minute.

### 3. Bulk Operations

Mass user creation and bulk archive operations support result summaries and error
handling without blocking the entire batch.

---

## Domain Boundary

The Admin domain owns system-level management across all business domains — the administrative interface for creating, updating, locking, unlocking, and archiving users across all roles (students, teachers, supervisors, and fellow administrators). It manages announcements with a full lifecycle (draft, scheduled, published) including role-targeted delivery and automatic scheduled publishing. It provides the centralized audit log viewer for compliance, the GDPR deletion log for data erasure requests, and account clone detection for identifying potential duplicates. Bulk operations for mass user creation and student archival are also handled here.

Admin does not own the underlying business logic of any operational domain. It does not define program structures (Internship), manage placement slots (Placement), evaluate students (Assessment), issue certificates (Certificate), or control authentication and authorization (Auth). Admin provides the management interface across all domains — reading, creating, and modifying data — but delegates business rules and validation to each domain's own Actions and Entities.

The domain references every other domain through its management interfaces: User data for role-based CRUD, Auth for account lifecycle operations, Internship for program closure and archival access, and School for department management context. It does not own the data in those domains — it orchestrates administrative operations across them while each domain retains ownership of its own business rules.

---

## Key Features

- Create, update, lock, unlock, and mark users as alumni across all five base user roles.
- Manage administrator accounts with role-enforced creation and super-admin-only role assignment.
- Manage student accounts with support for bulk archiving of completed program participants.
- Manage teacher and supervisor accounts including profile data and role assignment.
- Broadcast announcements with a draft, scheduled, and published lifecycle supporting Markdown content and role-targeted audiences.
- Publish scheduled announcements automatically every minute through the system scheduler.
- View a centralized, read-only audit log with comprehensive filters for compliance monitoring.
- Detect potential duplicate accounts by matching email, phone, or national identifier across the user base.
- Review guest applications and approve or reject them, auto-creating user accounts on approval.
- Perform bulk user creation operations with detailed result summaries and per-record error handling.
- Access read-only archived program data including grades, attendance summaries, and certificate records.
- Search users across name, email, and username with a text search bar that filters results in real time.
- Sort the user list by clicking on column headers for name, role, status, or creation date.
- Filter users by role, account status, or department with dropdown selectors above the table.
- Select multiple users for batch archive, batch lock, or batch unlock operations with a confirmation dialog.
- Import users from a CSV file with validation and a per-row result summary showing successes and failures.
- Export the current filtered and sorted user list to CSV respecting the active search and filter state.
- Download a CSV template with the correct column headers and example data for user import.
- Configure the number of rows displayed per page via a pagination size selector.

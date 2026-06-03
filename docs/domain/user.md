# User & Auth Domain

> Last updated: 2026-06-03
> **Status:** ✅ **Fully Implemented** — merged Auth and User domains into a unified Identity and Security layer

## Purpose

The **User & Auth** domain serves as the universal identity, authentication, and authorization layer for the system. It handles who can enter the application, what they are allowed to do, how they prove their identity, and how their profile and security status are maintained.

Every person in the system (Student, Teacher, Supervisor, Admin, Super Admin) is represented as a `User` model, which acts as the core authentication subject. Extendable profile details, role-based dashboards, notification delivery, rate limiting, and the 8-state account lifecycle are all managed within this boundary.

---

## Design Principles

### 1. Unified Identity and Profiling
- Every actor is a `User` first. Role-specific entities like `Mentee` and `Mentor` reference the `User` record via UUID foreign keys to prevent data duplication.
- Extended profile details (National ID, address, phone, avatar, or professional credentials) live in the `Profile` model, which is created on-demand when a user first edits their profile (`updateOrCreate`). This ensures lightweight user creation.

### 2. Defense in Depth
Security and authentication are checked at multiple layers:
1. **Network & Middleware Layer**: Global rate limits (via `AuthThrottleMiddleware` allowing 30 requests/minute/IP) and route-level role gates (via `CheckRoleMiddleware`).
2. **Session Security**: Session regeneration upon login and logout to block session fixation attacks.
3. **Application Logic**: 5-step sequential validation during credentials verification.
4. **Account State**: Automatic account locking after 10 failed login attempts, governed by the account status state machine.

### 3. Account Lifecycle as an Enforced State Machine
The account status is managed by the `AccountStatus` status enum, which validates and enforces state transitions at the database level:
- **Terminal States**: States like `ARCHIVED` and `PROTECTED` block any further transitions.
- **Super Admin Protection**: The Super Admin account is permanently `PROTECTED`. It cannot be deleted, suspended, or modified by other accounts, and its name remains immutable.
- **Login Gating**: The system queries `AccountStatus::allowsLogin()` to allow or deny login attempts based on the current state.

### 4. Functional Role Indirection
The system defines functional roles (`Mentor` and `Mentee`) which are dynamically resolved from the physical database roles (`Role` enum):
- `MENTOR` resolves from `TEACHER` and `SUPERVISOR` roles.
- `MENTEE` resolves from the `STUDENT` role.
This indirection decouples mentoring logic from concrete user models, making it easy to add new mentor-like roles in the future without changing database schemas.

### 5. Persistent Auditing and Logging
Every authentication, lock/unlock, password change, and status transition is recorded in the system audit trail using `SmartLogger`. This creates a reliable security footprint for compliance and issue investigation.

---

## Domain Boundary

### Technical Ownership
- **User Authentication**: Login execution, credentials verification, lockout timers, password re-confirmation.
- **Authorization**: RBAC (5 roles: `super_admin`, `admin`, `teacher`, `student`, `supervisor`) and functional role resolution (`mentor`, `mentee`).
- **Account Recovery**: One-time recovery slips containing 10 recovery codes (single-use, timing-attack resistant), password reset emails, and CLI-based super admin recovery.
- **In-App Notifications**: Custom notification delivery, read/unread status, bulk actions, and count caching.
- **Avatars**: Avatar uploads integrated with Spatie Media Library, converting images to WebP thumbnails immediately.
- **Dashboard Routing**: Priority-based dashboard controller routing.

### Dependencies
- **Core**: Relies on `BaseModel`, `BaseAction`, `BasePolicy`, `BaseEntity`, `SmartLogger`, and `PiiMasker`.
- **Academics**: Affiliations such as study program and school are stored as nullable references inside `Profile`.
- **Partners**: Supervisor users link to `Company` records.
- **Program / Journals / Assessment / Evaluation**: Dashboards read stats from these domains to show relevant progress summaries.

---

## Domain Rules & Invariants

### User & Account Lifecycle Rules
- **R1 — Authentication Base**: The `User` model extends Laravel's `Authenticatable` and implements UUID v4 primary keys via `HasUuids`.
- **R2 — Strict Mass Assignment**: Only safe attributes are fillable (`name`, `email`, `username`, `password`, `setup_required`, `locked_at`, `locked_reason`).
- **R3 — Failed Login Lockout**: Exceeding 10 failed login attempts updates `locked_at` and transitions the account state to locked, requiring an admin recovery slip or reset.
- **R4 — Super Admin Immutability**: The Super Admin account (username `superadmin`, name `Administrator`) cannot be locked, deleted, or renamed.
- **R5 — Active User Definition**: A user is considered active and queryable only if `locked_at` is null and `setup_required` is false.
- **R6 — Username Validation**: System usernames follow the pattern `u` + 8 alphanumeric characters. Custom usernames must match `/^[a-z][a-z0-9]{2,29}$/`.
- **R7 — Reserved Names**: Authoritative usernames (e.g., `admin`, `administrator`, `root`, `sysadmin`) are reserved and rejected for normal accounts.
- **R8 — Duplicate Account Detection**: The system flag duplicates (clones) if there is an exact match on email, phone, or national identifier.

### Profile Rules
- **R9 — On-Demand Creation**: `Profile` is not created at signup. The first update implicitly invokes `updateOrCreate`.
- **R10 — Role-Restricted Profile Fields**: Non-staff roles (students and supervisors) are restricted from seeing or editing professional staff fields (`employment_status`, `employee_id_number`, `educator_id_number`, `competence_field`, `job_title`).
- **R11 — Avatar Thumbnail Conversion**: All avatar uploads are limited to 2048 KB, validated as images, and auto-converted to WebP formats sized 200x200 pixels.

### Notification Rules
- **R12 — Scoped Operations**: Every notification query filters by `user_id` to prevent cross-tenant leakages.
- **R13 — Unread Count Caching**: Unread counts are cached per user under `CacheKeys::NOTIFICATION_UNREAD.{userId}` for 60 seconds and busted on any modification.

### Dashboard Resolution
- **R14 — Routing Hierarchy**: Dashboard Controller redirects users via the following prioritised list:
  1. `super_admin` or `admin` → `/admin/dashboard`
  2. `student` → `/student/dashboard`
  3. `teacher` → `/teacher/dashboard`
  4. `supervisor` → `/supervisor/dashboard`

---

## Key Features

- **Sequential Login Validation**: Sequential check of user existence, lockout state, password matching, and status evaluation.
- **Rate-Throttled Auth**: Enforces endpoint-level rate limits on logins and password confirmations.
- **Progressive Profiling**: Multi-tab profile management allowing avatar cropping and staff-specific data entry.
- **Universal Notification Center**: A full-page Livewire management component supporting search, filter, and batch actions.
- **MFA & Recovery Codes**: Offline printable PDF recovery codes with secure single-use token consumption.
- **Duplicate Account Reporting**: Detects overlapping identifiers across the database and alerts admins.
- **CLI Management**: Includes Artisan commands to promote users (`system:promote`), create admins, and recover lost super admin credentials.

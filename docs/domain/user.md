# User Domain
> Last updated: 2026-06-02
> Changes: user: comprehensive rewrite — added domain rules, invariants, data model, security model, complete feature inventory
> **Status:** ✅ **Fully Implemented** — all 40 files in [reference](user-reference.md) exist

## Purpose

User is the **identity persistence layer** of the system. While the Auth domain handles authentication (login, MFA, password management) and authorization (roles, permissions), User owns the static identity data: who a person is, their extended profile, their avatar, their notifications, and their dashboard routing.

Every person in the system — student, teacher, supervisor, admin — is represented as a `User` record first. Domain-specific roles (mentee, mentor, apprentice) reference this record rather than duplicating identity data.

---

## Design Principles

### 1. User is the Universal Identity

All persons share a single `User` model regardless of role. The User model extends Laravel's `Authenticatable` (not `BaseModel`) because the authentication system requires it. UUID consistency is maintained via `HasUuids`.

### 2. Profile is On-Demand

A `Profile` record is NOT created at user registration. It is created on first edit via `updateOrCreate`. This keeps signup lightweight and allows progressive data collection. Users can exist without a Profile but with limited functionality.

### 3. Role-Based Dashboard Routing

After login, users are redirected based on a priority match:
`super_admin` / `admin` > `teacher` / `supervisor` > `student`

Only the first matching role determines the destination. There is no fallback chain — each user maps to exactly one dashboard route.

### 4. Notification Center is Universal

Every authenticated user has access to in-app notifications regardless of role. There is no role-based gating on notification read/manage operations (only ownership scoping).

### 5. Avatar is a Single-File Media Collection

The avatar uses Spatie Media Library's `singleFile()` constraint — uploading a new avatar replaces the previous one automatically. There is no versioning or history.

---

## Domain Boundary

### What User Owns

| Concern | Details |
|---------|---------|
| Identity record | `User` model: name, email, username, lock state, setup completeness |
| Extended profile | `Profile` model: phone, address, gender, blood type, emergency contact, national ID, school/department affiliation, company affiliation (supervisors), staff credentials (employee ID, educator ID) |
| Avatar | Single image per user, auto-converted to 200×200 WebP thumbnail |
| In-app notifications | Persistent notification records with read/unread tracking, search, filter, bulk operations |
| Username generation | System-generated `u`-prefixed unique usernames with collision avoidance |
| Dashboard routing | Role-to-dashboard resolution and role-appropriate dashboards |
| Activity feed | Read-only paginated activity log (data owned by Core's ActivityLog model) |

### What User Does NOT Own

| Concern | Owned By |
|---------|----------|
| Authentication (login, MFA, password rules) | Auth domain |
| Role and permission definitions | Auth domain |
| Account lifecycle (activation, suspension, recovery) | Auth domain |
| MFA recovery codes (generation, storage) | Auth domain (`RecoveryCode` Livewire, routed in user.php) |
| Password change (logic, validation) | Auth domain (`UpdateUserPasswordAction`) |
| School, department, and company definitions | School domain (school/department), Partnership domain (company) |
| Internship program definitions | Internship domain |
| Registration workflows | Registration domain |

> **Note:** The `recovery-codes` page and password change form are rendered inside `ProfileEditor` but owned by Auth domain. User provides the UI surface; Auth provides the business logic.

### Cross-Domain Dependencies

```
User Domain
├── Core       → BaseModel, BaseAction, BaseEntity, SmartLogger,
│                 PasswordRules, HandlesActionErrors, BasePolicy, PiiMasker,
│                 CacheKeys, ActivityLog, SendsNotifications
├── Auth       → Role definitions, SuperAdminIntegrityRules, UpdateUserPasswordAction,
│                 Apprentice entity, AccountStatus
├── School     → School, Department (profile affiliations)
├── Admin      → GetAdminDashboardStatsAction (admin dashboard stats)
├── Logbook    → Logbook model (student dashboard journal counts)
├── Evaluation → Evaluation model (supervisor dashboard evaluation counts)
├── Registration → Registration model (dashboard enrollment data)
└── Mentee/Mentor → Mentee/Mentor models (user relationships)
```

---

## Domain Rules & Invariants

### User Identity

- **R1 — User extends Authenticatable:** The `User` model MUST extend Laravel's `Authenticatable`, not `BaseModel`, because authentication requires it. UUID support is added via the `HasUuids` trait.
- **R2 — UUID primary keys:** All User domain models (`User`, `Profile`, `Notification`) use UUID v4 primary keys. Foreign keys are UUID type.
- **R3 — Fillable fields are restricted:** `User` only allows mass-assignment of: `name`, `email`, `username`, `password`, `setup_required`, `locked_at`, `locked_reason`. Other mutations require explicit property assignment.
- **R4 — Password is always hashed:** The `password` attribute is cast to `hashed` — plaintext passwords are never stored or exposed.
- **R5 — Locking mechanism:** A user is "locked" when `locked_at` is not null. The `locked` and `unlocked` scopes allow query filtering. Locked users are excluded from the `active` scope.
- **R6 — Active user definition:** A user is considered "active" when both conditions are true: `locked_at IS NULL` AND `setup_required = false`.
- **R7 — Setup gate:** `setup_required = true` means the user has not completed initial configuration. These users are excluded from normal `active` queries.
- **R8 — Super Admin integrity:** The Super Admin's name is immutable after creation. `SuperAdminIntegrityRules::canChangeName()` returns `false` for super_admin, and `UpdateProfileAction` throws `RejectedException` if an attempt is made.
- **R9 — Super Admin is undeletable:** `UserPolicy::delete()`, `forceDelete()`, and `restore()` all return `false` for users with `super_admin` role.
- **R10 — Self-deletion is forbidden:** `UserPolicy::delete()` and `forceDelete()` return `false` when the target user is the authenticated user.
- **R11 — User update authorization:** A user can update their own record. Others require the `users.edit` permission. Exception: a super_admin can ONLY be edited by themselves.
- **R12 — User update permission model:** `viewAny` requires `users.view` permission. `viewAdmin` requires `super_admin` role. `create` requires `users.create` permission. `delete`/`forceDelete` requires `users.delete` permission.

### Username

- **R13 — Auto-generated format:** System-generated usernames follow the pattern `u` + 8 lowercase alphanumeric characters (e.g., `ua1b2c3d4`).
- **R14 — Collision avoidance:** Username generation retries up to 100 times if a collision is detected. If no unique username can be generated within that limit, a `RuntimeException` is thrown.
- **R15 — Validation rule:** User-provided usernames must match `/^[a-z][a-z0-9]{2,29}$/` — lowercase alphanumeric, starting with a letter, 3-30 characters.
- **R16 — Reserved names blocked:** The `ReservedAuthoritativeName` rule blocks these names for non-super-admin users: `admin`, `administrator`, `superadmin`, `superadministrator`, `super_admin`, `root`, `sysadmin`, `system`.

### Profile (Extended Data)

- **R17 — Profile is optional:** A `Profile` record is NOT created at user registration time. It is created on first profile edit via `updateOrCreate`.
- **R18 — Profile creation is implicit:** `UpdateProfileAction::execute()` calls `$user->profile()->updateOrCreate(...)`, so profile is auto-created when any profile field is saved for the first time.
- **R19 — Casted enum fields:** `gender` casts to `Gender` enum (MALE, FEMALE), `blood_type` casts to `BloodType` enum (A, B, AB, O), `employment_status` casts to `EmploymentStatus` enum (full_time, part_time, contract, temporary, volunteer).
- **R20 — Staff-only fields:** `employment_status`, `employee_id_number`, `educator_id_number`, `competence_field`, and `job_title` are only shown/editable for staff roles (`super_admin`, `admin`, `teacher`). Students and supervisors only see basic fields (name, email, phone, address, bio).
- **R21 — Staff field uniqueness:** `employee_id_number` and `educator_id_number` have unique constraints at the database level within the `profiles` table.
- **R22 — Emergency contact structure:** Emergency contact is stored as three separate fields: `emergency_contact_name` (max 255), `emergency_contact_phone` (max 20), `emergency_contact_address` (max 500).
- **R23 — School/department affiliation:** Profile may reference `school_id` and `department_id` as foreign keys into the School domain. These are optional.
- **R24 — National identifier:** `national_id_number` (max 50) and `student_id_number` (max 50) are stored per profile.

### Avatar

- **R25 — Single file:** The avatar media collection uses `singleFile()` — uploading a new avatar replaces the previous one automatically. No file versioning is kept.
- **R26 — Auto-conversion:** Each uploaded avatar is automatically converted to a 200×200 WebP thumbnail named `thumb` via `registerMediaConversions()`.
- **R27 — Immediate upload:** Avatar is uploaded immediately when selected via `updatedAvatar()` hook — there is no separate "save" step for the avatar.
- **R28 — Upload validation:** Avatar must be an image (png, jpeg, webp) and at most 2048 KB.
- **R29 — Preview:** A temporary URL is generated via `avatarPreviewUrl()` for live preview before the page re-renders.
- **R30 — Removal:** Avatar can be removed via `clearMediaCollection('avatar')`.

### Notifications (In-App)

- **R31 — Notification model:** In-app notifications are stored in the `Notification` model (distinct from Laravel's database notifications — this is a custom model within the User domain).
- **R32 — Fields:** Each notification has: `user_id`, `type` (string, max 50), `title` (max 255), `message` (nullable text), `data` (nullable JSON), `link` (nullable string), `is_read` (boolean), `read_at` (nullable datetime).
- **R33 — Ownership scoping:** All notification queries filter by `user_id`. The `MarkBatchAsReadAction` explicitly scopes by `user_id` to prevent cross-user access.
- **R34 — Create-only for admins:** `NotificationPolicy::create()` only allows admins to create notifications.
- **R35 — Read/update owned only:** `NotificationPolicy::view()` and `update()` only allow the notification owner. `delete()` only allows admins.
- **R36 — Unread cache:** The unread notification count is cached under `CacheKeys::NOTIFICATION_UNREAD.{userId}` with a 60-second TTL. The cache is busted on every mutation (create, mark-read, mark-all-read, delete).
- **R37 — SendsNotifications contract:** `SendNotificationAction` implements the Core `SendsNotifications` interface, allowing it to be injected polymorphically by other domains.
- **R38 — Bulk operations supported:** The notification center supports mark-all-read, mark-selected-read, and delete-selected (bulk via `BaseRecordManager` infrastructure).

### Dashboard Routing

- **R39 — Priority-based resolution:** `DashboardService::getDashboardForUser()` resolves the dashboard route using `match (true)` with this priority order:
  1. `super_admin` or `admin` → `admin.dashboard`
  2. `student` → `student.dashboard`
  3. `teacher` → `teacher.dashboard`
  4. `supervisor` → `supervisor.dashboard`
  5. Default → `user.dashboard`
- **R40 — Role-gated routes:** Each dashboard route is protected by a role middleware:
  - `/admin/dashboard` → `role:super_admin|admin`
  - `/student/dashboard` → `role:student`
  - `/teacher/dashboard` → `role:teacher`
  - `/supervisor/dashboard` → `role:supervisor`
- **R41 — Dashboard data is cross-domain:** Each dashboard aggregates data from multiple domains (Admin, Logbook, Evaluation, Registration). Dashboard Actions are READ-only queries.
- **R42 — Admin readiness checks:** The admin dashboard performs 5 system health checks: database connection, mail configuration, cache store, queue connection, and storage link.

### Activity Feed

- **R43 — Activity data is owned by Core:** The `ActivityLog` model lives in Core domain. User domain only queries it via `GetActivityLogsAction` (paginated read) and `RecentActivityList` (last 10).
- **R44 — Scoped to current user:** Activity feeds are always filtered to `causedBy(auth()->user())` — users can only see their own activity.

### Homepage / Root Routing

- **R45 — Install gate:** The root URL (`/`) first checks if the system is installed. If not, it redirects to the Setup wizard.
- **R46 — Auth gate:** If the system is installed but the user is not authenticated, it redirects to the login page.
- **R47 — Dashboard redirect:** If the system is installed and the user is authenticated, it redirects to the role-appropriate dashboard.

### Profile Editing (UI Layer)

- **R48 — Super Admin name locked in UI:** The profile editor shows a non-editable `display-field` for the Super Admin's name instead of an input.
- **R49 — Password change throttled:** Password changes are rate-limited to 5 attempts per user+IP combination via Laravel's `RateLimiter`.
- **R50 — Staff toggle:** The profile editor conditionally shows staff fields based on the user's role — non-staff users never see `employment_status`, `employee_id_number`, `educator_id_number`, `competence_field`, or `job_title` fields.

---

## Security & Authorization Summary

| Resource | View Any | View | Create | Update | Delete |
|----------|----------|------|--------|--------|--------|
| User | `users.view` perm | Self or `users.view` | `users.create` perm | Self or `users.edit` | Cannot self, cannot super_admin, requires `users.delete` |
| Profile | Admin only | Admin or owner | N/A | Admin or owner | N/A |
| Notification | All auth users | Owner only | Admin only | Owner only | Admin only |

**User-specific constraints:**
- Super Admin cannot be deleted, force-deleted, or restored by anyone (including themselves).
- Super Admin can only be edited by themselves.
- Non-super-admin users cannot change the Super Admin's name (enforced at Action level via `SuperAdminIntegrityRules`).

---

## Technical Characteristics

| Attribute | Value |
|-----------|-------|
| Models | 3 (`User`, `Profile`, `Notification`) |
| Actions | 11 (5 mutators, 6 readers) |
| Livewire Components | 10 (6 main + 4 dashboards) |
| Livewire Form Objects | 2 |
| Controllers | 2 (invokable) |
| Policies | 3 |
| Enums | 4 (`Gender`, `BloodType`, `EmploymentStatus`, `StructuralPosition`) |
| Rules | 2 (`SystemUsername`, `ReservedAuthoritativeName`) |
| Services | 1 (`DashboardService`) |
| Support | 1 (`UserIdentifierGenerator`) |
| Notifications | 1 (`TestMailNotification` — mail channel only) |
| Routes | 10 named routes in `routes/web/user.php` |
| Views | 13 Blade files in `resources/views/user/` |
| Tests | 10 (8 Feature, 2 Unit) |
| Database tables | 3 (`users`, `profiles`, `notifications`) + media tables via Spatie |
| Factories | 3 (`UserFactory`, `ProfileFactory`, `NotificationFactory`) |
| Migrations | 3 (`create_users_table`, `create_profiles_table`, `create_notifications_table`) |

## Key Features

- Edit personal profile data (name, email, phone, address, bio) through self-service forms, with staff-only fields shown conditionally.
- Upload a single avatar image that is immediately saved and auto-converted to a 200×200 WebP thumbnail.
- Route authenticated users to their correct dashboard based on role priority after login.
- Display system-wide statistics, readiness checklists, and quick links on the administrator dashboard.
- Show supervised students, pending journal entries, and active companies on the teacher dashboard.
- Present active participants, pending evaluations, and verified journals on the supervisor dashboard.
- Provide registration status, journal progress, and quick actions on the student dashboard.
- View, search, filter, sort, and bulk-manage (mark-read, delete) all notifications in a dedicated notification center.
- Generate unique system usernames automatically with collision avoidance when new users are created.
- Upload a profile photo with a live preview before saving.
- View unread notification counts as a cached badge on the navigation bar bell icon.
- Mark all notifications as read in a single bulk action from the notification center.
- Change password from the profile page with rate-limited attempts (5 per user+IP).
- View paginated activity log and recent activity list scoped to the current user.

# User Domain
> Last updated: 2026-05-27

## Purpose

User is the identity persistence layer. While Auth handles authentication and authorization,
User owns the static data: the User model, Profile model, dashboard routing, username generation,
notification center, and avatar management.

---

## Design Principles

### 1. User is the Universal Identity

Every person in the system — student, teacher, supervisor, admin — is a User first. The User
model extends `Authenticatable` (not `BaseModel`) because Laravel's authentication system
requires it. UUID consistency is maintained via manual `HasUuids` trait.

### 2. Profile is On-Demand

Users can exist without a Profile, but with limited functionality. Profile is created on first
edit, not at user creation. This keeps signup lightweight and allows progressive data collection.

### 3. Role-Based Dashboard Routing

After login, users are redirected based on role priority:
SUPER_ADMIN > ADMIN > TEACHER = SUPERVISOR > STUDENT.

### 4. Notification Center is Universal

Every authenticated user has access to notifications regardless of role.

---

## Actions

### Command Actions

| Action | Description |
|---|---|
| `UpdateProfileAction` | Updates profile with personal data; blocks super admin name changes |
| `SendNotificationAction` | Sends in-app notification via `SendsNotifications` contract |
| `MarkAsReadAction` | Marks single notification as read |
| `MarkAllAsReadAction` | Marks all notifications as read |
| `MarkBatchAsReadAction` | Marks selected notifications as read |
| `DeleteNotificationAction` | Deletes a single notification |

### Read Actions

| Action | Description |
|---|---|
| `GetStudentDashboardDataAction` | Aggregates student dashboard from multiple domains |
| `GetTeacherDashboardStatsAction` | Teacher stats (supervised students, pending journals) |
| `GetSupervisorDashboardStatsAction` | Supervisor stats (active interns, pending evaluations) |
| `GetProfileFormDataAction` | Role-appropriate profile fields |
| `GetActivityLogsAction` | User activity log with filtering |

---

## Models

| Model | Base | Key Fields |
|---|---|---|
| `User` | `Authenticatable` + `HasUuids` | name, email, username, password, locked_at, setup_required |
| `Profile` | `BaseModel` | phone, address, gender, blood_type, emergency_contact, school_id, department_id |
| `Notification` | `BaseModel` | type, title, message, data, link, is_read |

---

## Enums

| Enum | Values |
|---|---|
| `Gender` | MALE, FEMALE |
| `BloodType` | A, B, AB, O |
| `EmploymentStatus` | Keyed translations via `user.employment.*` |
| `StructuralPosition` | Organizational positions |

---

## Where to Find It

- `app/Domain/User/Models/User.php` — central identity model
- `app/Domain/User/Models/Profile.php` — extended personal data
- `app/Domain/User/Actions/UpdateProfileAction.php` — profile editing
- `app/Domain/User/Actions/SendNotificationAction.php` — notification dispatch
- `app/Domain/User/Livewire/` — dashboards, profile editor, notification center
- `app/Domain/User/Support/UserIdentifierGenerator.php` — username generation
- `app/Domain/User/Rules/` — username and reserved name validation

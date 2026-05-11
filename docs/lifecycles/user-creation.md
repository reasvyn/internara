# User Creation

**Event:** Creating user accounts and assigning roles.

**Phase:** 1 — Foundation

**Previous Event:** [School Configuration](school-configuration.md)

**Next Events:** [Account Lifecycle](account-lifecycle.md), [Internship Creation](internship-creation.md)

---

## Overview

Users are the actors of the system. Each user has an identity (name, email, username), a profile (phone, address, bio), roles (one or more), and an account status that determines what they can do.

## Trigger

- Institution needs new staff/teacher accounts (admin-initiated)
- New student enrollment period (admin or self-initiated)
- Industry supervisor onboarding (admin-initiated)

## Pre-conditions

- [School Configuration](school-configuration.md) is complete
- Roles exist in the database (seeded during installation)
- User is logged in as Super Admin or Admin
- Email is not already registered

## Actors

| Actor | Role | Can create |
|---|---|---|
| Super Admin | System administrator | Any role, override all restrictions |
| Admin | School administrator | Teachers, students, supervisors |
| System | Automatic | Users created via account application approval |

---

## Event A: Admin-Created Users

### Flow

#### Creating a User

```
Admin → User Manager → Select Role → Fill Form → Save
```

1. Navigate to the relevant user manager:
   - **Admin → Users → All Users** — all users, any role
   - **Admin → Users → Admins** — admin and super admin accounts
   - **Admin → Users → Teachers** — teacher accounts
   - **Admin → Users → Students** — student accounts
   - **Admin → Users → Mentors** — mentor/supervisor accounts
   - **Admin → Users → Mentees** — mentee records
2. Click **Create**
3. Fill in the form:

| Field | Validation | Required for |
|---|---|---|
| **Name** | Required, max 255 | All roles |
| **Email** | Required, valid email, unique | All roles |
| **Password** | Required, min 8 chars | New accounts (auto-generated 12 chars if not provided) |
| **Roles** | Required, min 1 | All users manager only |
| **National Identifier** | Required, max 20 | Students |
| **Registration Number** | Optional, max 50 | Students, Teachers |
| **Department** | Required, exists | Students |

4. Submit — `CreateUserAction` executes:
   - Generates a username if not provided
   - Generates a 12-character random password if not provided
   - Hashes the password
   - Creates the User record with UUID, name, email, username
   - Creates a Profile record (if profile data provided)
   - Assigns Spatie roles via `syncRoles()`
   - Sends `WelcomeNotification` with the plaintext password (unless password was provided manually)
   - Logs audit trail

#### Editing a User

```
Admin → Find User → Edit → Update → Save
```

- `UpdateUserAction` handles updating identity fields, profile data, and role assignments
- Email uniqueness is enforced (excluding the current user's email)
- Username uniqueness enforced via `SystemUsername` rule

#### Deleting a User

- `DeleteUserAction` performs a hard delete with pre-deletion guards:
  - **Cannot delete self** — throws `RuntimeException`
  - **Cannot delete last super admin** — checks if this is the only `super_admin` account
  - No explicit check for dependent records (database foreign keys may block the delete)

### Specialized Managers

Each role has a dedicated manager with role-specific fields:

| Manager | Role(s) | Extra Fields | Actions |
|---|---|---|---|
| AdminManager | admin, super_admin | None | Create, edit, delete, bulk delete |
| TeacherManager | teacher | Registration number (NIP) | Create, edit, delete, bulk delete |
| StudentManager | student | NISN, NIS, Department | Create, edit, delete, bulk delete, archive filtered |
| MentorManager | mentor/supervisor | Type (school_teacher / industry_supervisor), is_active | Create, edit, delete, bulk delete |
| MenteeManager | mentee | internal_notes, is_active | Create, edit, delete, bulk delete |
| UserManager | All | All roles, status toggle, password reset | Create, edit, delete, bulk delete, toggle status, filter by role/status |

---

## Event B: Self-Created Users (Account Application)

Prospective students can apply for an account without existing credentials. See [Student Registration](student-registration.md) for the full flow.

### Simplified Flow

1. Student fills out public application form (`/apply`)
2. Admin reviews pending applications
3. `VerifyAccountAction::approve()` creates User + Profile + Mentee + Registration in a single transaction
4. Account is created with `setup_required = true`
5. Student receives login notification

---

## Event C: Bulk Operations

### Bulk Delete

Selected users can be deleted in bulk via `performBulkAction`:
- Self-deletion is prevented
- Each user is deleted individually (not a mass query) to respect per-item guards
- Last super admin guard applies to each deletion

### Bulk Archive (Student Manager)

Filtered students can be mass-archived via `performMassAction`:
- Changes account status to `archived`
- Useful for graduating cohorts

---

## User Model Structure

```
User
├── id (UUID, primary key)
├── name
├── email (unique)
├── password (hashed via casting)
├── username (auto-generated or manual, unique)
├── setup_required (boolean, default: false)
├── locked_at (timestamp, nullable)
├── locked_reason (string, nullable)
├── timestamps
├── email_verified_at (timestamp, nullable)
│
├── Profile (1:1)
│   ├── phone, address, bio
│   ├── gender, blood_type
│   ├── national_identifier, registration_number
│   ├── emergency_contact_name/phone/address
│   └── department_id (FK)
│
├── Roles (N:N via Spatie Permission)
│   └── super_admin, admin, teacher, student, supervisor
│
├── Account Status (polymorphic via Spatie model-status)
│   └── provisioned, activated, verified, protected, restricted, suspended, inactive, archived
│
├── Mentees (1:N)
├── Mentors (1:N)
├── Registrations (HasManyThrough via Mentee)
├── Handbook Acknowledgements (1:N)
└── Activity Log (1:N via Spatie activitylog)
```

## State Changes

| Component | Before | After |
|---|---|---|
| Users table | No user or fewer users | New user record with hashed password |
| Profile | Not created | Created (if profile data provided) |
| Roles | Unassigned | User has one or more roles via `syncRoles()` |
| Welcome notification | — | Sent (if password was auto-generated) |
| Activity log | — | Audit entry for user creation |

## Error Handling

| Failure | Detection Point | Behavior |
|---|---|---|
| Duplicate email | `CreateUserAction` validator | Validation error, form not submitted |
| Invalid username format | `SystemUsername` rule | Validation error |
| Self-deletion attempt | `DeleteUserAction` | `RuntimeException`, caught by UI as error message |
| Last super admin deletion | `DeleteUserAction` | `RuntimeException`, blocked with message |
| Notification send failure | `CreateUserAction` after user created | Logged as warning, user creation not rolled back |
| Database transaction failure | Any action → `withErrorHandling` | Rollback, `RuntimeException` rethrown |

## Post-conditions

- User exists in the system with assigned roles
- Profile is created (if profile data was provided)
- Password is hashed and stored
- Welcome notification is sent (if password was auto-generated)
- User appears in the relevant role manager for further management
- User can log in (unless account status blocks login)

## Seamless Connection

Once users exist in the system:

- **Teachers and Admins** can proceed to [Internship Creation](internship-creation.md)
- **Students** flow through [Student Registration](student-registration.md) to join an internship
- **Account status changes** are managed in [Account Lifecycle](account-lifecycle.md)

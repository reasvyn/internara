# Setup Wizard

**Event:** Multi-step configuration wizard for initial school setup.

**Phase:** 0 — System Setup

**Previous Event:** [System Installation](system-installation.md)

**Next Event:** [School Configuration](school-configuration.md)

---

## Overview

The Setup Wizard is a browser-based, multi-step form that guides the first-time administrator through configuring the institution's profile, departments, admin account, internship program, and finalization. It is accessible only once via the one-time setup token generated during installation.

## Trigger

The administrator opens the one-time setup URL in a browser:

```
http://localhost:8000/setup?setup_token={64-char-token}
```

## Pre-conditions

- [System Installation](system-installation.md) has completed successfully
- Setup token is valid (not expired, not yet used)
- No setup record with `is_installed = true` exists yet
- No existing setup session is in progress
- Database is migrated and seeded

## Actors

| Actor | System Role | Real World |
|---|---|---|
| Setup Administrator | Super Admin (being created) | School IT admin, principal, or designated setup person |

## Flow

The wizard uses a Livewire component (`SetupWizard`) with multiple steps. Form data persists across steps via session storage (`session()->put('setup.form_data', ...)`). The wizard auto-generates a username from the admin's name.

### Step 1: Welcome & Environment Audit

The system performs a read-only check of the server environment (same checks as CLI installation). If any critical check fails, the wizard displays the issue and blocks progression.

### Step 2: School Profile

Configure the institution's identity. Calls `SetupSchoolAction` on finalization, which prevents duplicate schools via `SchoolState::canBeCreated()`.

| Field | Validation | Description | Mapped to |
|---|---|---|---|
| **Institution Name** | Required, max 255 | Official school name | `name` |
| **Institutional Code** | Required, max 50 | NPSN or institutional identifier | `institutional_code` |
| **Address** | Not validated in wizard | Street address | `address` (defaults to `-` if empty) |
| **Email** | Required, valid email | Official contact email | `email` |
| **Phone** | Not validated in wizard | Office phone number | `phone` |
| **Website** | Not validated in wizard | School website | `website` |
| **Principal Name** | Not validated in wizard | Head of institution | `principal_name` |

> Only `schoolName`, `institutionalCode`, and `schoolEmail` are validated at this step. Other fields are stored if provided but not required.

### Step 3: Department

Create one academic department. Calls `SetupDepartmentAction` on finalization.

| Field | Validation | Description |
|---|---|---|
| **Department Name** | Required, max 255 | e.g., "Computer and Informatics Engineering" |
| **Description** | Not validated (optional) | Department description |

> Only one department is created during setup. Additional departments can be added later through the admin panel.

### Step 4: Admin Account

Create the first administrator account. Calls `SetupSuperAdminAction` on finalization.

| Field | Validation | Description |
|---|---|---|
| **Full Name** | Required, max 255 | Administrator's full name |
| **Email** | Required, valid email, unique | Login email |
| **Password** | Required, min 8 chars, confirmed | Login password |
| **Username** | Auto-generated from name | Lowercase, no spaces, max 20 chars |

The action (`SetupSuperAdminAction`):
1. Creates the User with `setup_required = false`
2. Marks email as verified
3. Assigns the `super_admin` role
4. Logs audit entry

### Step 5: Internship Program

Create an initial internship program for the upcoming period. This step is optional in the sense that it can be skipped by admin choice, but the form must pass validation if visible.

| Field | Validation | Description |
|---|---|---|
| **Internship Name** | Required, max 255 | e.g., "PKL 2025/2026" |
| **Description** | Not validated (optional) | Program description |
| **Start Date** | Required, date | When the internship period begins |
| **End Date** | Required, date, after start date | When the internship period ends |

### Step 6: Finalize & Confirmation

Before the system finalizes, the administrator must confirm two checkboxes:

| Checkbox | Purpose |
|---|---|
| **Data Verified** | Administrator confirms all entered data is correct |
| **Security Aware** | Administrator acknowledges the security responsibilities |

(The exact confirmation fields are defined in the `SetupWizard` Livewire component.)

### Step 7: Complete

After successful finalization, the wizard shows a completion screen with:
- Login link (administrator can now log in with the created credentials)
- The generated recovery key (displayed once, cannot be retrieved later)

Clicking **Finish** redirects to the login page.

## Finalization Sequence

When the administrator submits Step 6, the `finish()` method executes this sequence:

```
1. SetupSchoolAction::execute($schoolData)
   └── Creates School record, prevents duplicates
   └── Logs audit: 'school_setup_completed'
   └── Stores school_id in Setup model

2. SetupDepartmentAction::execute($schoolId, $deptData)
   └── Creates Department record linked to the school
   └── Logs audit: 'department_setup_completed'
   └── Stores department_id in Setup model

3. SetupSuperAdminAction::execute($adminData)
   └── Creates User with super_admin role
   └── Account status set to VERIFIED (fully operational)
   └── Logs audit: 'super_admin_created'

4. Mark steps completed
   └── Setup::markStepCompleted('school')
   └── Setup::markStepCompleted('department')
   └── Setup::markStepCompleted('account')

5. FinalizeSetupAction::execute()
   └── Setup::update(['is_installed' => true])
   └── Setup::generateRecoveryKey() → encrypted key
   └── Setup::invalidateToken() → setup URL no longer works
   └── Dispatches SetupFinalized domain event
   └── Clears setup session data
   └── Returns the plaintext recovery key
```

The recovery key is displayed once:

```
╔══════════════════════════════════════════════════════════╗
║                   RECOVERY KEY                          ║
║                                                         ║
║  Save this key in a secure location.                    ║
║  It is the only way to recover admin access             ║
║  if all administrator accounts are locked out.          ║
║                                                         ║
║  KEY: Xa7p... (auto-generated)                          ║
║                                                         ║
╚══════════════════════════════════════════════════════════╝
```

## State Changes

| Component | Before | After |
|---|---|---|
| School | Not created | Created with profile data |
| Departments | Not created | 1 department created (more can be added later) |
| Super Admin User | Not created | Created with VERIFIED status, super_admin role |
| `setups.is_installed` | `false` | `true` |
| Setup token | Valid (1-hour window) | Invalidated (preventing re-use) |
| Recovery key | Not generated | Generated (encrypted in DB, shown once) |
| Setup session data | Present in session | Cleared |
| Audit log | — | 3 audit entries: school, department, admin creation |

## Authentication Behavior

The setup wizard does NOT set account status to `PROTECTED`. The super admin account is created with full access (email verified, no setup required). The `PROTECTED` status is reserved for accounts created through the admin panel or recovery flow.

## Security

- The setup token is encrypted and time-limited (1-hour expiry, enforced by `SetupState::validateToken()`)
- After finalization, the `/setup` URL returns HTTP 404 after a 5-minute grace window (`ProtectSetupRouteMiddleware`)
- The recovery key is displayed **once** and cannot be retrieved later
- The `is_installed` flag prevents re-running the wizard
- Rate limiting: 20 attempts per 60 seconds per IP on the setup route
- Session-based authorization: once the token is validated, the session is authorized for subsequent requests
- If forced reinstallation is needed: `php artisan setup:reset`

## Error Handling

| Failure | Detection Point | Behavior |
|---|---|---|
| Invalid/expired token | `ProtectSetupRouteMiddleware` → `ValidateSetupTokenAction` | HTTP 403 or redirect to login |
| Duplicate school | `SetupSchoolAction` → `SchoolState::canBeCreated()` | RuntimeException, caught by wizard error handler |
| Email already exists | `SetupSuperAdminAction` → Validator | Validation error on the form |
| Weak password | `nextStep()` → `min:8` + `confirmed` | Form validation error |
| Database write failure | Any action → DB::transaction | Rollback, error message displayed |
| `setups.is_installed = true` | `mount()` → `Setup::state()->isInstalled()` | Redirect to login page |
| Rate limit exceeded | `ProtectSetupRouteMiddleware` → RateLimiter | HTTP 429 with retry message |

## Post-conditions

- School profile is configured with name, code, and email
- One department exists (more can be added through admin panel)
- Super administrator account is active and ready (email verified, no setup required)
- Recovery key has been generated and displayed on screen (save immediately)
- Setup is locked: `is_installed = true`, setup token invalidated, setup route returns 404
- System is ready for regular operation

## Seamless Connection

The super administrator can now log in with the created credentials and proceed to [School Configuration](school-configuration.md) — adding academic years, creating additional users (teachers, students, supervisors), and fine-tuning the system settings.

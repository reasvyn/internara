# Setup Wizard
> Last updated: 2026-05-26
> Changes: fix(setup): comprehensive security hardening, self-destruction, and single-use token


## Accessing the Wizard

Run the installer to generate a signed URL with an expiring token:

```bash
php artisan setup:install
```

The command outputs a URL like:

```
https://internara.sekolah.sch.id/setup?setup_token=a1b2c3d4e5f6...
```

Open this URL in your browser. The `ProtectSetupRouteMiddleware` validates
the token and authorizes access. The token expires in 60 minutes.

> If the token expires, run `php artisan setup:reset-token` to generate a new
> one (only works before the wizard is completed).

The wizard has **7 steps**, displayed as a progress bar at the top. Data
is persisted to the session so you can navigate forward and backward
without losing input. If you refresh the page or reopen the browser, your
progress is restored from the session automatically.

> Session persistence: form data (school, department, admin email, internship)
> is saved to the session on every field change. Passwords are excluded from
> session storage. When the wizard completes successfully, all session data
> is cleared. If the browser tab is closed, a cleanup request is sent
> automatically. Otherwise, session data expires after 120 minutes.

---

## Step 1: Welcome & Environment Audit

The wizard begins by running an automatic environment audit via
`EnvironmentAuditor`. This checks:

| Category | Checks |
|---|---|
| **Requirements** | PHP version ≥ 8.4.0, all required extensions installed |
| **Permissions** | Storage and bootstrap/cache directories writable |
| **Database** | Connection works, migrations are pending |
| **Terminal** | Required CLI commands available (composer, node, npm) |
| **Recommendations** | Optional extensions (opcache, redis, imagick), queue config |

Each check passes (green), warns (yellow), or fails (red).

**The wizard cannot proceed to step 2 unless all critical checks pass.**
If a critical check fails, resolve the issue (e.g., install a missing
extension, fix permissions) and refresh the page to re-run the audit.

---

## Step 2: Super Admin Account

Create the initial administrator account with full system access:

| Field | Required | Rules |
|---|---|---|
| Name | No | defaults to "Administrator" (immutable) |
| Username | No | defaults to "superadmin" (immutable) |
| Email | Yes | valid email, max 255 |
| Password | Yes | min 8 chars, must contain uppercase, lowercase, and digit |
| Confirm Password | Yes | must match password |

> The super admin **name** and **username** are permanently locked to
> "Administrator" and "superadmin" respectively. They cannot be changed
> through any interface — only via direct database modification.

> **Remember these credentials.** The super admin account has unrestricted
> access to all system features. If the password is lost, the recovery key
> (shown in Step 7) is the only way to regain access.

---

## Step 3: School Information

Configure your institution's details:

| Field | Required | Rules |
|---|---|---|
| School Name | Yes | string, max 255 |
| Institutional Code | Yes | string, max 50 (e.g., NPSN) |
| Email | Yes | valid email, max 255 |
| Address | No | string |
| Phone | No | string, max 20 |
| Website | No | valid URL, max 255 |
| Principal Name | No | string, max 255 |

---

## Step 4: Department

Create the first department / study program (jurusan):

| Field | Required | Rules |
|---|---|---|
| Department Name | Yes | string, max 255 (e.g., "Software Engineering") |
| Description | No | string |

Additional departments can be added later from the School → Departments
admin page.

---

## Step 5: Internship Period (Optional)

Configure the first internship period if you are ready:

| Field | Required | Rules |
|---|---|---|
| Internship Name | No | string, max 255 |
| Description | No | string |
| Start Date | No | valid date |
| End Date | No | valid date, must be after start date |

This step is optional. If left blank, you can create internship periods
later from the Internship management page.

---

## Step 6: Finalize & Confirm

Review all entered data. The finalization step requires:

- **Data verification checkbox** — confirm all information is correct
- **Security awareness checkbox** — acknowledge that you understand the
  responsibility of the super admin account

Clicking "Finish" triggers `FinalizeSetupAction`, which executes the
following operations:

```
DB Transaction                      Outside Transaction
┌─────────────────────────────┐     ┌──────────────────────────┐
│ FinalizeSetupAction         │     │ SaveRecoveryKeyAction    │
│ ├── Pre-check is_installed  │     │ (try-catch, never        │
│ │   (with lockForUpdate)    │     │  rolls back DB)          │
│ ├── SetupSchoolAction       │     └──────────────────────────┘
│ │   └── School::updateOrCreate                    │
│ ├── SetupDepartmentAction   │                     ▼
│ │   └── Department::create  │              Return plaintext
│ ├── SetupSuperAdminAction   │
│ │   └── User::create + role │
│ ├── CreateInternshipAction  │
│ │   (if data provided)      │
│ ├── Mark is_installed=true  │
│ │   + recovery key (hashed) │
│ ├── Dispatch SetupFinalized │
│ ├── Send notification       │
│ └── Clear session data      │
└─────────────────────────────┘
```

The DB operations are wrapped in a transaction with `lockForUpdate()`
on the setups table to prevent race conditions under concurrent requests.
If the pre-check detects an already-installed system, it throws
`RuntimeException` before any writes occur.

If the recovery key file save fails (disk full, permission error), the
DB transaction is **not** affected — the error is logged via SmartLogger
and the setup completes successfully. The recovery key is still available
on screen and in the database.

---

## Step 7: Complete — Recovery Key

The final screen displays your **recovery key** — a 64-character random
string.

```
╔══════════════════════════════════════════════════════╗
║                   RECOVERY KEY                       ║
║                                                      ║
║   a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3   ║
║                                                      ║
║   ⚠  Save this key somewhere safe and offline.      ║
║   It will NOT be shown again after this screen.     ║
╚══════════════════════════════════════════════════════╝
```

### Why the Recovery Key Matters

- The recovery key is the **only way to restore super admin access** if
  all admin passwords are lost.
- It is stored hashed in the database — the application never stores the
  plain text in the database.
- The system **automatically saves** the recovery key to a secure file:

  ```
  storage/app/private/.recovery-key
  ```

  This file is readable only by the server owner (permission `0600`) and
  is not accessible via the web. A server administrator can run the
  recovery command without needing to know the key:

  ```bash
  php artisan admin:recover
  ```

  The command automatically reads the key from the file. Use the `--key`
  option only if the file is unavailable.

  Additional commands:

  ```bash
  # Show the file path
  php artisan admin:recovery-path

  # Display the key (requires confirmation)
  php artisan admin:recovery-show

  # Regenerate the file from a known key
  php artisan admin:recover --key=<recovery-key> --regenerate-file
  ```

  Use `--reset` to change an existing admin's password instead of creating
  a duplicate.

### Auto-Redirect

The system automatically redirects you to the login page after **60 seconds**.
A countdown timer is displayed at the bottom of the screen. You can also
click "Go to Login" at any time to skip the countdown.

### Next Step

Use the super admin credentials you created in Step 2 to sign in for the
first time.

From here, follow the [Post-Setup Guide](post-setup.md) to configure
your school for daily operations.

---

## Architecture Overview

```
Route: GET /setup
Middleware: setup.protected (ProtectSetupRouteMiddleware)
Component: App\Admin\Submodules\Setup\Livewire\SetupWizard
Layout: resources/views/administration/setup/layouts/setup.blade.php
View: resources/views/administration/setup/setup-wizard.blade.php
       └── includes step components from administration/setup/components/
               ├── welcome-step.blade.php
               ├── school-step.blade.php
               ├── department-step.blade.php
               ├── admin-step.blade.php
               ├── internship-step.blade.php
               ├── finalize-step.blade.php
               └── complete-step.blade.php
```

### Middleware System

Two middleware classes protect the setup wizard, each with a distinct responsibility:

#### `RequireSetupAccessMiddleware` (Global)

Registered in the global web middleware stack via `bootstrap/app.php`. Runs on **every** request.

| State | Action |
|---|---|
| System installed | `$next($request)` — pass through, no redirect |
| Not installed, accessing `/setup` | `$next($request)` — let `ProtectSetupRouteMiddleware` validate |
| Not installed, Livewire subrequest | `$next($request)` — bypass (prevents redirect loop on AJAX) |
| Not installed, any other route | `redirect()->route('setup')` — force visitor to setup wizard |

**Purpose:** Prevent access to the application when it has not been initialized. Without this
middleware, visitors would see error pages or unconfigured homepages before setup.

#### `ProtectSetupRouteMiddleware` (Route-specific, alias `setup.protected`)

Applied only to the `/setup` route group. Provides three layers of protection:

| Layer | Mechanism | Configuration |
|---|---|---|
| Rate limiting | 20 attempts per 60 seconds per IP | `config/setup.php:security` |
| Token validation | Query string `?setup_token=` or POST input | `ValidateSetupTokenAction` decrypts and compares |
| Session authorization | `Session::get('setup.authorized')` | Set after first successful validation |

When the system is **already installed**:

```
isInstalled = true
├── Within finalization window (30 sec) AND setup.completed flag?
│   ├── Yes → allow (grace period for complete screen only, step 7)
│   └── No  → force-clear ALL setup session data → abort 404 (self-destruct)
```

The finalization window uses `setup.completed`, NOT `setup.authorized`. The flag is set by
`SetupWizard.finish()` after finalization succeeds, and cleared when the user clicks
"Go to Login" (`finishSession()`). During this window, **only the complete page** is accessible —
the wizard is locked and returns 404 for any other step.

When the system is **not installed**:

```
isInstalled = false
├── Session authorized?
│   ├── Yes → allow (already validated in this session)
│   └── No  → check for token
│       ├── Token in query string? → validate → if valid: consume token (single-use), authorize session
│       ├── Token in POST body?    → validate → if valid: consume token (single-use), authorize + redirect GET
│       ├── No token?              → check rate limit → render code entry form or 429
│       └── Token invalid          → rate-limit and reject (403 / Livewire JSON error)
```

Token validation uses `ValidateSetupTokenAction` inside `lockForUpdate()` transaction.
After successful validation, the token is immediately nullified in the database —
it cannot be replayed. Valid tokens also clear the rate limiter for the IP.

The code entry form (`views/administration/setup/enter-code.blade.php`) allows administrators to enter
the setup token manually without exposing it in a URL — mitigating server log and browser
history leakage.

#### `POST /setup/cleanup` (No middleware)

A simple route that clears setup session data. Called via `navigator.sendBeacon()` when
the browser tab is closed (`beforeunload` event). Prevents PII (admin email, school email, phone,
address) from persisting in session storage longer than necessary.

### Key Classes

| Class | Location | Purpose |
|---|---|---|
| `SetupWizard` | `app/Setup/Livewire/SetupWizard.php` | Livewire component, 7-step state machine |
| `SetupState` | `app/Setup/Entities/SetupState.php` | Read-only value object for setup status |
| `Setup` | `app/Setup/Models/Setup.php` | Eloquent model (single-row, singleton) |
| `FinalizeSetupAction` | `app/Setup/Actions/FinalizeSetupAction.php` | Orchestrates all finalization sub-actions |
| `SetupSchoolAction` | `app/Setup/Actions/SetupSchoolAction.php` | Creates/updates School record |
| `SetupDepartmentAction` | `app/Setup/Actions/SetupDepartmentAction.php` | Creates first Department |
| `SetupSuperAdminAction` | `app/User/SuperAdmin/Actions/SetupSuperAdminAction.php` | Creates User + assigns super_admin role |
| `EnvironmentAuditor` | `app/Setup/Services/EnvironmentAuditor.php` | Runs pre-installation system checks |
| `RequireSetupAccessMiddleware` | `app/Academics/Http/Middleware/RequireSetupAccessMiddleware.php` | Global: redirects to /setup if not installed |
| `ProtectSetupRouteMiddleware` | `app/Academics/Http/Middleware/ProtectSetupRouteMiddleware.php` | Route: validates token, rate-limits, self-destructs |

### End-to-End Flow

```
CLI: setup:install                 Browser: setup?token=...
┌──────────────────────┐           ┌──────────────────────────────┐
│ 1. Environment audit │           │ Step 1: Welcome (audit pass) │
│ 2. Provision system  │  ───→     │ Step 2: Super admin account  │
│ 3. Generate token    │  signed   │ Step 3: School details       │
│ 4. Print URL         │   URL     │ Step 4: First department     │
└──────────────────────┘           │ Step 5: Internship (opt)     │
                                   │ Step 6: Finalize transaction │
                                   │ Step 7: Recovery key         │
                                   └──────────────┬───────────────┘
                                                  │
                                                  ▼
                                        System installed
                                        is_installed = true
                                        Recovery key saved (hashed)
```

The `SetupState` entity (`SetupState.php`) tracks:
- `isInstalled()` — whether setup has been completed
- `isStepCompleted(step)` — whether a specific step was finished
- `isWithinFinalizationWindow(minutes)` — time limit for finalization (minutes)
- `isWithinFinalizationWindowSeconds(seconds)` — time limit for finalization (seconds, default 30)
- `hasRecoveryKey()` — whether a recovery key exists
- `validateToken()` — validates setup access tokens

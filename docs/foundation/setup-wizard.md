# Setup Wizard

> **Last updated:** 2026-06-10

Detailed walkthrough of the 6-step guided setup wizard.

---

## Accessing the Wizard

Run the installer to generate a signed URL with an expiring token:

```bash
php artisan setup:install
```

The command outputs a URL like:
```
https://internara.sekolah.sch.id/setup?setup_token=a1b2c3d4e5f6...
```

Open this URL in your browser. The `ProtectSetupRouteMiddleware` validates the token and
authorizes access. The token expires in **60 minutes**.

> If the token expires, run `php artisan setup:reset-token` to generate a new one (only works
> before the wizard is completed).

The wizard has **6 steps**, displayed as a progress bar. Data is persisted to the session so you
can navigate forward and backward without losing input. Form data (except passwords) is saved to
session on every field change. After completion, all session data is cleared.

---

## Step 1: Welcome & Environment Audit

The wizard runs an automatic environment audit via `EnvironmentAuditor`:

| Category | Checks |
|----------|--------|
| **Requirements** | PHP ≥ 8.4.0, all required extensions installed |
| **Permissions** | Storage and bootstrap/cache directories writable |
| **Database** | Connection works, migrations pending |
| **Terminal** | Required CLI commands available (composer, node, npm) |
| **Recommendations** | Optional extensions (opcache, redis, imagick), queue config |

Each check passes (green), warns (yellow), or fails (red). **The wizard cannot proceed to step 2
unless all critical checks pass.** Resolve issues and refresh the page to re-run the audit.

---

## Step 2: Super Admin Account

Create the initial administrator with full system access:

| Field | Required | Rules |
|-------|----------|-------|
| Name | No | Defaults to "Administrator" (immutable) |
| Username | No | Defaults to "superadmin" (immutable) |
| Email | Yes | Valid email, max 255 |
| Password | Yes | Min 8 chars, uppercase + lowercase + digit |
| Confirm Password | Yes | Must match password |

> **Name and username are permanently locked.** They cannot be changed through any interface.
> See [Module Invariants](../architecture.md#module-invariants-do-not-violate).

---

## Step 3: School Information

Configure your institution's details:

| Field | Required | Rules |
|-------|----------|-------|
| School Name | Yes | String, max 255 |
| Institutional Code | Yes | String, max 50 (e.g., NPSN) |
| Email | Yes | Valid email, max 255 |
| Address | No | String |
| Phone | No | String, max 20 |
| Website | No | Valid URL, max 255 |
| Principal Name | No | String, max 255 |

---

## Step 4: Department

Create the first department (jurusan):

| Field | Required | Rules |
|-------|----------|-------|
| Department Name | Yes | String, max 255 |
| Description | No | String |

Additional departments can be added later from **School → Departments**.

---

## Step 5: Finalize & Confirm

Review all entered data. Requires:

- **Data verification checkbox** — confirm all information is correct
- **Security awareness checkbox** — acknowledge super admin responsibility

Clicking "Finish" triggers `FinalizeSetupAction`:

```
DB Transaction (lockForUpdate on settings)
┌─────────────────────────────────────┐
│ FinalizeSetupAction                 │
│ ├── Pre-check is_installed (locked) │
│ ├── SetupSchoolAction               │
│ │   └── settings table update       │
│ ├── SetupDepartmentAction           │
│ │   └── Department::create          │
│ ├── SetupSuperAdminAction           │
│ │   └── User::create + role assign  │
│ ├── Mark is_installed = true        │
│ │   + recovery key (hashed) in DB   │
│ ├── Dispatch SetupFinalized event   │
│ └── Clear session data              │
└─────────────────────────────────────┘

Outside Transaction (try-catch, never rolls back DB):
  └── SaveRecoveryKeyAction → writes plaintext to .recovery-key
```

If the recovery key file save fails (disk full), the DB transaction is not affected — the error
is logged and setup completes. The key remains available on screen.

---

## Step 6: Complete — Recovery Key

Final screen displays your **64-character recovery key**:

```
╔══════════════════════════════════════════════════════╗
║                   RECOVERY KEY                       ║
║                                                      ║
║   a1b2c3d4e5f6g7h8i9j0k1l2m3n4o5p6q7r8s9t0u1v2w3   ║
║                                                      ║
║   ⚠  Save this key somewhere safe and offline.      ║
║   It will NOT be shown again after this screen.      ║
╚══════════════════════════════════════════════════════╝
```

### Why It Matters

- The recovery key is the **only way to restore super admin access** if all passwords are lost
- Stored hashed in database — plaintext never persisted
- Auto-saved to `storage/app/private/.recovery-key` (permission `0600`)

### Recovery Commands

```bash
# Automatic (reads from storage file):
php artisan admin:recover

# Manual (explicit key):
php artisan admin:recover --key=<64-char-key>

# Reset existing admin password:
php artisan admin:recover --reset

# Show file path:
php artisan admin:recovery-path

# Display key (requires confirmation):
php artisan admin:recovery-show
```

### Auto-Redirect

Automatically redirects to login after **60 seconds**. Countdown timer displayed. Click "Go to
Login" to skip.

---

## Architecture Overview

```
Route: GET /setup
Middleware: setup.protected (ProtectSetupRouteMiddleware)
Component: App\Setup\SetupWizard\Livewire\SetupWizard
Layout: resources/views/setup/layouts/setup.blade.php
View: resources/views/setup/setup-wizard/setup-wizard.blade.php
       └── includes step components from setup/components/
               ├── welcome-step.blade.php
               ├── school-step.blade.php
               ├── department-step.blade.php
               ├── admin-step.blade.php

               ├── finalize-step.blade.php
               └── complete-step.blade.php
```

### Middleware System

| Middleware | Scope | Purpose |
|-----------|-------|---------|
| `RequireSetupAccessMiddleware` | Global | Redirects all routes to /setup before installation |
| `ProtectSetupRouteMiddleware` | Route alias `setup.protected` | Token validation, rate limiting (20/60s/IP), self-destruct |

After installation, the wizard self-destructs — `/setup` returns 404 unless within the 30-second
finalization window.

### Key Classes

| Class | Location | Purpose |
|-------|----------|---------|
| `SetupWizard` | `app/Setup/SetupWizard/Livewire/SetupWizard.php` | Livewire component, 6-step state machine |
| `SetupEntity` | `app/Setup/Entities/SetupEntity.php` | Setup status entity |
| `EnvironmentAuditor` | `app/SysAdmin/Observability/Services/EnvironmentAuditor.php` | System checks |
| `RequireSetupAccessMiddleware` | `app/Setup/Installation/Http/Middleware/RequireSetupAccessMiddleware.php` | Global redirect |
| `ProtectSetupRouteMiddleware` | `app/Setup/Installation/Http/Middleware/ProtectSetupRouteMiddleware.php` | Token + rate limit |

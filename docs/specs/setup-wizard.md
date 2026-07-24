# Setup Wizard — Feature Specification

> **Last updated:** 2026-07-22 **Changes:** feat — split from install-and-setup.md; browser
> wizard initiative

## Description

Specification for the browser-based setup wizard of Internara. Covers the 6-step wizard UI,
token validation, access control during setup, and post-finalization lifecycle. CLI provisioning
is a separate initiative — see [installation.md](installation.md).

---

## 1. Problem Statements

### PS-1 — Access Control During Setup

The setup wizard creates the super admin account and writes sensitive configuration. Untrusted
parties must not be able to access the wizard, guess the setup URL, or replay expired sessions.

### PS-2 — Recovery Key Lifecycle

After setup, the super admin may lose access (forgotten password, account lockout). The wizard
must display the recovery key at finalization and provide a clear way to save it.

### PS-3 — Guided Configuration

School IT staff may not be technical. The wizard must guide them through super admin creation,
school profile, and department setup with clear instructions and validation at each step.

### PS-4 — Auto-Redirect for Uninstalled Systems

Any visitor to an uninstalled Internara instance must be automatically redirected to the setup
wizard rather than seeing a broken or empty application.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal                                                               |
| --- | ------------------------------------------------------------------ |
| G1  | Complete wizard in under 5 minutes from Step 1 to Step 6          |
| G2  | Prevent unauthorized access to setup wizard via cryptographic token |
| G3  | Auto-redirect non-installed instances to setup wizard              |
| G4  | Provide bilingual UI (English/Indonesian) throughout the wizard    |
| G5  | Ensure idempotent finalization — running twice causes no harm      |
| G6  | Display recovery key with one-click copy at finalization           |

### Non-Goals

| ID   | Non-Goal                                                         |
| ---- | ---------------------------------------------------------------- |
| NG1  | CLI wizard (use `setup:install` for CLI path)                   |
| NG2  | Multi-step progress persistence across browser sessions          |
| NG3  | Import/export of setup configuration                             |
| NG4  | Custom theme/branding during setup (post-setup only)             |

---

## 3. User Stories / Use Cases

### UC-1 — Browser Wizard (Primary Path)

**Actor:** Installer (may be same person as CLI installer, or different)

**Preconditions:** Token generated via CLI or `setup:reset-token`, browser access to application.

**Flow:**
1. Installer opens the setup URL with `?setup_token=XXXX` parameter
2. `ProtectSetupRouteMiddleware` validates token, stores authorization in session, regenerates
   session ID
3. **Step 1 — Welcome:** Environment audit results displayed. Installer must see all checks pass
   (or click "Recheck") before "Start Setup" button enables.
4. **Step 2 — Super Admin:** Name ("Administrator") and username ("superadmin") are locked. Installer
   enters email and password. Password requires 8+ chars, mixed case, numbers.
5. **Step 3 — School:** Installer enters school name, NPSN code, email, phone (optional), website
   (optional), address (optional), principal name (optional).
6. **Step 4 — Department:** Installer enters first department name and optional description.
7. **Step 5 — Finalize:** Two checkboxes (data verified + security aware). Summary display of
   entered data. "Finish" button disabled until both checked.
8. **Step 6 — Complete:** Recovery key displayed (64-char random string) with copy button. Access
   credentials summary. Auto-redirect to login after 20 seconds.

**Postconditions:** System is fully operational, super admin can log in, recovery key saved to
`storage/app/private/.recovery-key` (chmod 0600).

### UC-2 — Auto-Redirect to Setup

**Actor:** Any visitor

**Preconditions:** System not yet installed.

**Flow:**
1. Visitor navigates to any page (e.g., `/dashboard`)
2. `RequireSetupAccessMiddleware` detects `is_installed = false`
3. Visitor is redirected to `/setup` with appropriate middleware handling

**Postconditions:** Visitor sees setup token entry page (or wizard if authorized).

### UC-3 — Post-Finalization Window

**Actor:** Installer completing setup

**Preconditions:** Setup wizard completed, session has `setup.completed` flag.

**Flow:**
1. Installer sees Step 6 (Complete) with recovery key
2. Within 30 seconds, installer can still view the setup page (for copying recovery key)
3. After 30 seconds, session setup data is cleared and setup route returns 404

**Postconditions:** Setup route is permanently inaccessible.

### UC-4 — Backward Navigation

**Actor:** Installer in wizard

**Preconditions:** Wizard started, at least Step 2 completed.

**Flow:**
1. Installer clicks "Back" or a step indicator
2. System navigates to the selected completed step
3. Form data is preserved from session

**Postconditions:** Installer can review/modify previous entries.

---

## 4. Functional Requirements

### 4.1 Setup Wizard

| ID   | Requirement                                                              |
| ---- | ------------------------------------------------------------------------ |
| FR-W1 | Wizard must have exactly 6 steps: welcome, account, school, department, finalize, complete |
| FR-W2 | Step 1 (Welcome) must show environment audit results                     |
| FR-W3 | "Start Setup" button must be disabled until audit passes                 |
| FR-W4 | Step 2 (Super Admin): name and username are immutable, read from config  |
| FR-W5 | Super Admin password requires 8+ characters, mixed case, numbers         |
| FR-W6 | Super Admin email is required and validated                              |
| FR-W7 | Step 3 (School): name and institutional_code are required               |
| FR-W8 | School website must be valid URL if provided                             |
| FR-W9 | Step 4 (Department): name is required                                    |
| FR-W10 | Step 5 (Finalize): requires both "data verified" and "security aware" checkboxes |
| FR-W11 | Step 6 (Complete): displays recovery key with copy button and auto-redirect |
| FR-W12 | Form data must persist in session across step navigation                 |
| FR-W13 | Installer must be able to navigate backward to completed steps           |
| FR-W14 | Installer must be able to navigate backward to any incomplete step       |

### 4.2 Finalization

| ID   | Requirement                                                              |
| ---- | ------------------------------------------------------------------------ |
| FR-F1 | Finalization must be atomic — all-or-nothing: school, department, admin, settings |
| FR-F2 | System must create school profile in settings (`school.*` keys)          |
| FR-F3 | System must create first department in database                          |
| FR-F4 | System must create super admin: role=superadmin, status=PROTECTED, email verified |
| FR-F5 | Super admin `setup_required` flag must be set to `false`                 |
| FR-F6 | System must generate 64-char recovery key, store hashed in DB and plaintext in file |
| FR-F7 | Recovery key file must be saved to `storage/app/private/.recovery-key` with chmod 0600 |
| FR-F8 | System must set `is_installed = true` in settings                        |
| FR-F9 | System must save `brand_name` and `site_title` from school name          |
| FR-F10 | System must dispatch `SetupFinalized` event                              |
| FR-F11 | System must send welcome notification to super admin                     |
| FR-F12 | System must clear all caches after finalization                          |
| FR-F13 | System must clear setup session data after finalization                  |
| FR-F14 | Running finalization on an already-installed system must throw `RejectedException` |

### 4.3 Access Control

| ID   | Requirement                                                              |
| ---- | ------------------------------------------------------------------------ |
| FR-AC1 | `RequireSetupAccessMiddleware` must redirect to `/setup` when not installed (globally applied) |
| FR-AC2 | `ProtectSetupRouteMiddleware` must validate token for all setup routes   |
| FR-AC3 | Authorized session must store `setup.authorized=true` and `setup.token_version` |
| FR-AC4 | Post-finalization: setup route accessible for 30 seconds (configurable), then 404 |
| FR-AC5 | Post-finalization outside window: clear session setup data, abort 404    |
| FR-AC6 | Installed system: any `/setup` access without valid session → 404        |
| FR-AC7 | Requests for real files in `public/` must pass through (Vite assets, etc.) |
| FR-AC8 | Livewire header requests must pass through (prevent redirect during updates) |

---

## 5. Non-Functional Requirements

### 5.1 Security

| ID    | Requirement                                                          |
| ----- | -------------------------------------------------------------------- |
| NFR-S1 | Token must be cryptographically random (64 chars, via `Str::random`) |
| NFR-S2 | Token must be encrypted at rest (`Crypt::encryptString`)             |
| NFR-S3 | Token must be single-use — cleared after validation                  |
| NFR-S4 | Session ID must be regenerated after token validation                |
| NFR-S5 | Rate limiting: 20 attempts/IP/60s on token validation                |
| NFR-S6 | Super admin password must meet Laravel Password rules (8+ chars, mixed case, numbers) |
| NFR-S7 | Super admin account status must be PROTECTED (non-deletable, non-lockable) |
| NFR-S8 | All setup actions must be logged via SmartLogger for audit trail     |

### 5.2 Performance

| ID    | Requirement                                                          |
| ----- | -------------------------------------------------------------------- |
| NFR-P1 | Wizard step navigation must respond within 1 second                  |
| NFR-P2 | Finalization (all DB writes) must complete within 5 seconds          |

### 5.3 Reliability

| ID    | Requirement                                                          |
| ----- | -------------------------------------------------------------------- |
| NFR-R1 | Finalization failures must roll back the entire transaction          |
| NFR-R2 | Recovery key file write failure must not block finalization          |

### 5.4 Usability

| ID    | Requirement                                                          |
| ----- | -------------------------------------------------------------------- |
| NFR-U1 | Wizard must show progress bar with step indicators                   |
| NFR-U2 | Wizard must support backward/forward navigation                     |
| NFR-U3 | Form data must persist across step navigation (session)              |
| NFR-U4 | Recovery key must be displayed with one-click copy button            |
| NFR-U5 | Auto-redirect from Complete step must countdown (20 seconds)         |
| NFR-U6 | Environment audit must show pass/fail/warn icons per check           |
| NFR-U7 | All wizard text must be available in English and Indonesian          |

### 5.5 Accessibility

| ID    | Requirement                                                          |
| ----- | -------------------------------------------------------------------- |
| NFR-A1 | Setup wizard must meet WCAG 2.1 Level AA                             |
| NFR-A2 | Step indicators must be keyboard-accessible and announced to screen readers |
| NFR-A3 | Environment audit results must include non-color indicators (icons alongside pass/fail colors) |
| NFR-A4 | All form inputs in setup wizard must have associated labels          |
| NFR-A5 | Recovery key display must be accessible (copy button has `aria-label`) |

### 5.6 Localization

| ID    | Requirement                                                          |
| ----- | -------------------------------------------------------------------- |
| NFR-L1 | All wizard text must use `__()` translation helper                   |
| NFR-L2 | Translation keys must exist in both `lang/en/` and `lang/id/` locale files |
| NFR-L3 | Environment audit status must be translatable via `__()`             |

### 5.7 Maintainability

| ID    | Requirement                                                          |
| ----- | -------------------------------------------------------------------- |
| NFR-M1 | Setup state must be stored in the shared `settings` table (no separate migrations) |
| NFR-M2 | Setup actions must follow Action Triad pattern (Command/Read/Process) |
| NFR-M3 | Setup entity must be `final readonly` with zero I/O                  |
| NFR-M4 | All setup behavior must be testable via Pest test suite              |

---

## 6. API / Data Contracts

### 6.1 Settings Keys

School profile stored with `group = 'school'`:

| Key                  | Type   | Required | Description              |
| -------------------- | ------ | -------- | ------------------------ |
| `school.name`        | string | yes      | Institution name         |
| `school.institutional_code` | string | yes | NPSN code               |
| `school.email`       | string | yes      | Contact email            |
| `school.address`     | string | no       | Physical address         |
| `school.phone`       | string | no       | Contact phone            |
| `school.website`     | string | no       | Institution website      |
| `school.principal_name` | string | no   | Principal name           |

### 6.2 Setup Wizard Form Contracts

```php
// SuperAdminForm — name/username locked from config
class SuperAdminForm extends LivewireForm
{
    public string $name = '';           // Locked, from config
    public string $username = '';       // Locked, from config
    public string $email = '';          // Required, email validation
    public string $password = '';       // Required, Password::min(8)->mixedCase()->numbers()
    public string $password_confirmation = '';
}

// SchoolForm
class SchoolForm extends LivewireForm
{
    public string $name = '';           // Required, max:255
    public string $institutional_code = ''; // Required, max:50
    public string $address = '';        // Nullable
    public string $email = '';          // Required, email
    public string $phone = '';          // Nullable, max:20
    public ?string $website = null;     // Nullable, URL validation
    public ?string $principal_name = null; // Nullable, max:255
}

// DepartmentForm
class DepartmentForm extends LivewireForm
{
    public string $name = '';           // Required, max:255
    public string $description = '';    // Nullable
}
```

### 6.3 Action Contracts

```php
// FinalizeSetupAction
class FinalizeSetupAction extends BaseCommandAction
{
    public function execute(
        array $schoolData,
        array $departmentData,
        array $adminData,
        array $stepsToComplete = ['account', 'school', 'department'],
    ): string; // Returns plaintext recovery key
    // @throws RejectedException when already installed
}

// SetupSchoolAction
class SetupSchoolAction extends BaseCommandAction
{
    public function execute(array $data): void;
}

// SetupDepartmentAction
class SetupDepartmentAction extends BaseCommandAction
{
    public function execute(array $data): Department;
}
```

### 6.4 Routes

| Method | URI            | Handler                | Name            | Middleware          |
| ------ | -------------- | ---------------------- | --------------- | ------------------- |
| GET    | `/setup`       | `SetupWizard` (Livewire) | `setup`       | `setup.protected`   |
| POST   | `/setup`       | `SetupController@redirect` | —           | `setup.protected`   |
| POST   | `/setup/cleanup` | `SetupController@cleanup` | `setup.cleanup` | `setup.protected` |

### 6.5 Config

```php
// config/setup.php (wizard-specific)
[
    'wizard' => [
        'step_keys' => ['welcome', 'account', 'school', 'department', 'finalize', 'complete'],
        'finalize_steps' => ['account', 'school', 'department'],
    ],
    'security' => [
        'finalization_window_seconds' => 30,
    ],
]
```

---

## 7. Design Decisions

### DD-1 — Session-Based Wizard State

**Decision:** Persist form data in session across step navigation rather than database writes.

**Rationale:** Setup is a transient process — if the session expires, the installer simply restarts
from Step 1. No orphaned partial records in the database. The 30-second post-finalization window
allows copying the recovery key without re-entering data.

### DD-2 — Global Middleware for Setup Redirect

**Decision:** Apply `RequireSetupAccessMiddleware` globally (all routes) rather than only on
specific routes.

**Rationale:** Every page in an uninstalled system should redirect to setup. Applying globally
ensures no page is accidentally accessible before provisioning. The middleware passes through
real files (Vite assets), Livewire requests, and setup routes themselves.

### DD-3 — Atomic Finalization

**Decision:** `FinalizeSetupAction` creates school, department, admin, and settings in a single
database transaction.

**Rationale:** Partial setup creates an unusable state — database seeded but no admin, or admin
created but `is_installed` still false. Atomicity ensures the system either fully works or
remains in setup mode.

### DD-4 — 30-Second Post-Finalization Window

**Decision:** Keep setup route accessible for 30 seconds after finalization, then 404.

**Rationale:** Installer needs time to copy the recovery key. After that, the setup route should
be permanently inaccessible to prevent re-entry. The window is configurable via
`config/setup.php`.

---

## 8. Success Metrics

### 8.1 Wizard Completeness

| Metric                          | Target      | Measurement                           |
| ------------------------------- | ----------- | ------------------------------------- |
| Wizard completion rate          | 100%        | All 6 steps reachable and completable |
| Recovery key display            | 100%        | 64-char key with copy button          |
| Auto-redirect                   | Always      | Uninstalled system redirects to setup |

### 8.2 Security Properties

| Metric                          | Target      | Measurement                           |
| ------------------------------- | ----------- | ------------------------------------- |
| Unauthorized wizard access      | Always      | Token validation enforced             |
| Session hijacking               | Always      | Session ID regenerated after auth     |
| Post-finalization lockout       | Always      | Setup route 404s after 30s window     |

### 8.3 Usability

| Metric                          | Target      | Measurement                           |
| ------------------------------- | ----------- | ------------------------------------- |
| Time to complete wizard         | < 5 min     | From Step 1 to Step 6                 |
| Step navigation                 | Always      | Backward/forward works correctly      |
| Form data persistence           | Always      | Data preserved across step changes    |

---

## 9. Roadmap

### Prerequisites
This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [installation.md](installation.md) | Setup token, provisioned database, seeded roles, `setup.is_installed` flag |

### Build Guide
After implementing this spec, the system has a 6-step browser wizard that creates the super admin account, school profile, department, and generates the recovery key. The wizard is the entry point for all configuration. The next step is to build the recovery ecosystem, which enables emergency super admin access via the recovery key generated during finalization.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [recovery-ecosystem.md](recovery-ecosystem.md) | Recovery key hash stored in `setup.recovery_key` during finalization; `admin:recover` verifies against this hash |

---

## Quick References

- `docs/modules/setup.md` — Module conceptual overview
- `docs/modules/setup-reference.md` — Technical reference (Actions, Entity, Routes)
- `docs/specs/installation.md` — CLI provisioning initiative
- `docs/foundation/project-requirements.md` — High-level feature specs
- `docs/foundation/setup-wizard.md` — Wizard walkthrough
- `docs/foundation/post-setup.md` — Post-wizard configuration guide
- `config/setup.php` — Setup configuration values
- `app/Setup/` — Full module source code

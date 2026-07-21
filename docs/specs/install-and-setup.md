# Installation & Setup — Feature Specification

> **Last updated:** 2026-07-21 **Changes:** feat — initial spec based on Setup module codebase
> exploration

## Description

Comprehensive specification for the one-time installation and setup system of Internara. Covers the
full lifecycle from CLI provisioning through browser-based wizard to system finalization, including
security properties, data contracts, and operational constraints.

---

## 1. Problem Statements

### PS-1 — First-Boot Experience

A freshly deployed Internara instance has no database schema, no seed data, and no administrator
account. The system must guide an installer through environment validation, database provisioning,
and initial configuration without requiring manual SQL or config file editing.

### PS-2 — Access Control During Setup

The setup wizard creates the super admin account and writes sensitive configuration. Untrusted
parties must not be able to access the wizard, guess the setup URL, or replay expired sessions.

### PS-3 — Single-Execution Guarantee

Setup must execute exactly once. Running installation on an already-configured system must not
corrupt data, create duplicate accounts, or reset configuration.

### PS-4 — Recovery Key Lifecycle

After setup, the super admin may lose access (forgotten password, account lockout). A recovery
mechanism must exist that does not rely on email (which may not be configured) or web UI (which
requires login).

### PS-5 — Environment Readiness

Different servers have different PHP versions, extensions, directory permissions, and database
configurations. The system must detect and report incompatibilities before attempting provisioning
to prevent partial or failed installations.

### PS-6 — Deployment Flexibility

The installer may be a school IT staff using shared hosting, a developer using Docker, or a
sysadmin with CLI access. The system must support both CLI-first and browser-based installation
paths.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal                                                               |
| --- | ------------------------------------------------------------------ |
| G1  | Complete system provisioning from zero to operational in under 5 minutes |
| G2  | Prevent unauthorized access to setup wizard via cryptographic token |
| G3  | Provide clear, actionable environment audit results before provisioning |
| G4  | Ensure idempotent setup — running twice causes no harm             |
| G5  | Support both CLI (`setup:install`) and browser-based (6-step wizard) paths |
| G6  | Generate a recovery key for emergency super admin access            |
| G7  | Auto-redirect non-installed instances to setup wizard              |
| G8  | Provide bilingual UI (English/Indonesian) throughout the wizard    |

### Non-Goals

| ID   | Non-Goal                                                         |
| ---- | ---------------------------------------------------------------- |
| NG1  | Multi-tenant provisioning (Internara is single-tenant)          |
| NG2  | Remote/SSH-based installation (use CLI directly)                 |
| NG3  | Automatic server configuration (Apache/Nginx setup)             |
| NG4  | Migration between hosting environments (use standard backup/restore) |
| NG5  | Web-based database selection (SQLite default, configure via .env)|
| NG6  | Self-update during setup (updates are post-install)             |

---

## 3. User Stories / Use Cases

### UC-1 — CLI Installation (Primary Path)

**Actor:** Server administrator (sysadmin, IT staff)

**Preconditions:** PHP 8.4+, Composer dependencies installed, `.env` file exists (or will be
created), storage directory writable.

**Flow:**
1. Administrator runs `php artisan setup:install`
2. System runs environment audit and displays results (pass/fail/warning per check)
3. If audit fails, administrator sees specific remediation steps and can `--force` in dev environments
4. System provisions: creates `.env` if missing, generates APP_KEY, runs migrations, seeds roles
   and defaults, creates storage symlink, clears caches
5. System generates a 64-char cryptographic token and displays a signed URL
6. Administrator opens URL in browser to continue wizard (or uses token manually)

**Postconditions:** Database is provisioned, token is valid for 60 minutes, setup routes are
accessible via token.

### UC-2 — Browser Wizard (Completion Path)

**Actor:** Installer (may be same person as UC-1, or different)

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

### UC-3 — Token Reset (Lost Token)

**Actor:** Server administrator

**Preconditions:** System not yet finalized, CLI access.

**Flow:**
1. Administrator runs `php artisan setup:reset-token`
2. System checks settings table exists and system is not installed
3. New token generated (old token invalidated via version increment)
4. New signed URL displayed

**Postconditions:** Previous token is invalid, new token is valid for 60 minutes.

### UC-4 — Emergency Super Admin Recovery

**Actor:** Server administrator

**Preconditions:** System installed, super admin account inaccessible, CLI access.

**Flow:**
1. Administrator runs `php artisan admin:recover`
2. System generates new password, displays it
3. Administrator uses new password to log in

**Postconditions:** Super admin account is accessible with new password.

### UC-5 — Auto-Redirect to Setup

**Actor:** Any visitor

**Preconditions:** System not yet installed.

**Flow:**
1. Visitor navigates to any page (e.g., `/dashboard`)
2. `RequireSetupAccessMiddleware` detects `is_installed = false`
3. Visitor is redirected to `/setup` with appropriate middleware handling

**Postconditions:** Visitor sees setup token entry page (or wizard if authorized).

### UC-6 — Post-Finalization Window

**Actor:** Installer completing setup

**Preconditions:** Setup wizard completed, session has `setup.completed` flag.

**Flow:**
1. Installer sees Step 6 (Complete) with recovery key
2. Within 30 seconds, installer can still view the setup page (for copying recovery key)
3. After 30 seconds, session setup data is cleared and setup route returns 404

**Postconditions:** Setup route is permanently inaccessible.

---

## 4. Functional Requirements

### 4.1 Environment Audit

| ID   | Requirement                                                              |
| ---- | ------------------------------------------------------------------------ |
| FR-A1 | System must check PHP version >= 8.4.0                                 |
| FR-A2 | System must check 11 required extensions: bcmath, ctype, fileinfo, mbstring, openssl, pdo, tokenizer, xml, curl, gd, intl, zip |
| FR-A3 | System must warn about recommended extensions: redis, pcntl, posix      |
| FR-A4 | System must verify directory permissions: storage/, bootstrap/cache/     |
| FR-A5 | System must verify database connectivity                                |
| FR-A6 | System must verify terminal access (for artisan commands)               |
| FR-A7 | Audit results must be categorized: REQUIREMENTS, PERMISSIONS, DATABASE, TERMINAL, RECOMMENDATIONS |
| FR-A8 | Each check must show pass/fail/warning status with specific details      |
| FR-A9 | Audit must be re-runnable (Recheck button in wizard)                    |

### 4.2 System Provisioning

| ID   | Requirement                                                              |
| ---- | ------------------------------------------------------------------------ |
| FR-P1 | System must create `.env` from `.env.example` if missing, set chmod 0600 |
| FR-P2 | System must generate APP_KEY if empty                                    |
| FR-P3 | System must run all database migrations (`migrate` or `migrate:fresh` with `--force`) |
| FR-P4 | System must seed: roles (Spatie), default settings, academic year        |
| FR-P5 | System must create storage symlink if missing                            |
| FR-P6 | System must clear all caches: config, application, routes, views         |
| FR-P7 | All provisioning tasks must execute in order within a single transaction (where applicable) |
| FR-P8 | `--force` flag must only work in local, dev, development, testing environments |

### 4.3 Setup Token

| ID   | Requirement                                                              |
| ---- | ------------------------------------------------------------------------ |
| FR-T1 | Token must be 64-character cryptographically random string               |
| FR-T2 | Token must be encrypted with `Crypt::encryptString` before storage       |
| FR-T3 | Token must expire after 60 minutes (configurable via `config/setup.php`) |
| FR-T4 | Token must be single-use — cleared after successful validation           |
| FR-T5 | Token version must increment on each generation to invalidate stale sessions |
| FR-T6 | Rate limiting: max 20 validation attempts per IP per 60 seconds          |
| FR-T7 | Failed validation must log the attempt and throttle the client            |
| FR-T8 | Token generation must use cache lock (`setup.token.generation`) to prevent races |

### 4.4 Setup Wizard

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

### 4.5 Finalization

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

### 4.6 Access Control

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

### 4.7 CLI Commands

| ID   | Requirement                                                              |
| ---- | ------------------------------------------------------------------------ |
| FR-C1 | `setup:install` — provisions system, generates token, displays URL       |
| FR-C2 | `setup:install --check-only` — runs audit only, no provisioning         |
| FR-C3 | `setup:install --force` — wipes DB and re-provisions (restricted envs)   |
| FR-C4 | `setup:install --url=URL` — sets APP_URL and generates URL with token    |
| FR-C5 | `setup:reset-token` — generates new token, invalidates old              |
| FR-C6 | `admin:recover` — generates new password for super admin                |
| FR-C7 | `setup:install` on installed system must fail unless `--force`           |
| FR-C8 | `--force` on non-local environments must be rejected                    |

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
| NFR-S6 | Recovery key must be hashed in database, plaintext only in file      |
| NFR-S7 | Recovery key file must have permissions 0600 (owner-only read/write) |
| NFR-S8 | `.env` file must be created with permissions 0600                    |
| NFR-S9 | Super admin password must meet Laravel Password rules (8+ chars, mixed case, numbers) |
| NFR-S10 | Super admin account status must be PROTECTED (non-deletable, non-lockable) |
| NFR-S11 | `--force` must be restricted to non-production environments          |
| NFR-S12 | All setup actions must be logged via SmartLogger for audit trail     |

### 5.2 Performance

| ID    | Requirement                                                          |
| ----- | -------------------------------------------------------------------- |
| NFR-P1 | Environment audit must complete within 5 seconds                     |
| NFR-P2 | Full provisioning (migrations + seeders) must complete within 30 seconds |
| NFR-P3 | Token generation must use cache lock to prevent race conditions      |
| NFR-P4 | Post-install cache invalidation must complete within 2 seconds       |

### 5.3 Reliability

| ID    | Requirement                                                          |
| ----- | -------------------------------------------------------------------- |
| NFR-R1 | Provisioning failures must roll back the entire transaction          |
| NFR-R2 | Recovery key file write failure must not block finalization          |
| NFR-R3 | Token generation lock must have 10s lock duration, 15s block timeout |
| NFR-R4 | System must handle concurrent token generation attempts gracefully   |

### 5.4 Usability

| ID    | Requirement                                                          |
| ----- | -------------------------------------------------------------------- |
| NFR-U1 | CLI output must include a visual banner and formatted sections       |
| NFR-U2 | Wizard must show progress bar with step indicators                   |
| NFR-U3 | Wizard must support backward/forward navigation                     |
| NFR-U4 | Form data must persist across step navigation (session)              |
| NFR-U5 | Recovery key must be displayed with one-click copy button            |
| NFR-U6 | Auto-redirect from Complete step must countdown (20 seconds)         |
| NFR-U7 | Environment audit must show pass/fail/warn icons per check           |
| NFR-U8 | All wizard text must be available in English and Indonesian          |

### 5.5 Maintainability

| ID    | Requirement                                                          |
| ----- | -------------------------------------------------------------------- |
| NFR-M1 | Setup state must be stored in the shared `settings` table (no separate migrations) |
| NFR-M2 | Setup actions must follow Action Triad pattern (Command/Read/Process) |
| NFR-M3 | Setup entity must be `final readonly` with zero I/O                  |
| NFR-M4 | All setup behavior must be testable via Pest test suite              |

---

## 6. API / Data Contracts

### 6.1 Settings Keys

All setup state is stored in the `settings` table with `group = 'setup'`:

| Key                      | Type     | Description                              |
| ------------------------ | -------- | ---------------------------------------- |
| `setup.is_installed`     | boolean  | Master flag — `true` after finalization  |
| `setup.install_token`    | string   | Encrypted token (null after use)         |
| `setup.token_expires_at` | datetime | Token expiry timestamp (null after use)  |
| `setup.token_version`    | integer  | Increments on each generation            |
| `setup.completed_steps`  | JSON     | Array of completed wizard step keys      |
| `setup.recovery_key`     | string   | Hashed recovery key (bcrypt)             |
| `setup.updated_at`       | datetime | Last setup state modification            |

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

### 6.2 Setup Entity Contract

```php
final readonly class SetupEntity extends BaseEntity
{
    // Constructor
    public function __construct(
        bool $dbInstalled,
        ?string $setupToken,
        ?Carbon $tokenExpiresAt,
        array $completedSteps,
        ?string $recoveryKey,
        ?Carbon $updatedAt = null,
        int $tokenVersion = 0,
    );

    // Query methods
    public function isInstalled(): bool;
    public function hasStoredToken(): bool;
    public function isTokenExpired(?Carbon $now): bool;
    public function validateToken(string $decrypted, string $input, ?Carbon $now): bool;
    public function isStepCompleted(string $step): bool;
    public function allStepsCompleted(): bool;
    public function isWithinFinalizationWindow(int $minutes = 5): bool;
    public function isWithinFinalizationWindowSeconds(int $seconds = 30): bool;
    public function hasRecoveryKey(): bool;

    // Accessors
    public function setupToken(): ?string;
    public function tokenExpiresAt(): ?Carbon;
    public function recoveryKey(): ?string;
    public function completedSteps(): array;
    public function updatedAt(): ?Carbon;
    public function tokenVersion(): int;

    // Static
    public static function get(): static;
    public static function keys(): array;
    public static function toSettingsEntries(array $attributes): array;
}
```

### 6.3 Setup Token Data

```php
final readonly class SetupTokenData extends BaseData
{
    public function __construct(
        public string $plaintext,
        public Carbon $expiresAt,
    );
}
```

### 6.4 Setup Wizard Form Contracts

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

### 6.5 Action Contracts

```php
// GenerateSetupTokenAction
class GenerateSetupTokenAction extends BaseCommandAction
{
    public function execute(): SetupTokenData;
}

// ValidateSetupTokenAction
class ValidateSetupTokenAction extends BaseCommandAction
{
    public function execute(string $token): void;
    // @throws RejectedException when token missing/expired/malformed/mismatch
}

// InstallSystemAction
class InstallSystemAction extends BaseProcessAction
{
    public function execute(bool $force = false, ?AuditReport $report = null): SetupTokenData;
    // @throws RejectedException when audit fails
}

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

// SetupSuperAdminAction
class SetupSuperAdminAction extends BaseCommandAction
{
    public function execute(string $email, string $password): User;
    // @throws RejectedException when super admin immutable
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

### 6.6 Routes

| Method | URI            | Handler                | Name            | Middleware          |
| ------ | -------------- | ---------------------- | --------------- | ------------------- |
| GET    | `/setup`       | `SetupWizard` (Livewire) | `setup`       | `setup.protected`   |
| POST   | `/setup`       | `SetupController@redirect` | —           | `setup.protected`   |
| POST   | `/setup/cleanup` | `SetupController@cleanup` | `setup.cleanup` | `setup.protected` |

### 6.7 Events

```php
// SetupFinalized — dispatched after successful finalization
class SetupFinalized extends BaseEvent
{
    public function __construct(
        public ?string $departmentId,
        public DateTimeImmutable $installedAt,
    );
    public function eventName(): string; // 'setup.finalized'
}
```

### 6.8 Config

```php
// config/setup.php
[
    'requirements' => [
        'php_version' => '8.4.0',
        'extensions' => ['bcmath', 'ctype', 'fileinfo', 'mbstring', 'openssl', 'pdo', 'tokenizer', 'xml', 'curl', 'gd', 'intl', 'zip'],
        'recommended_extensions' => ['redis', 'pcntl', 'posix'],
    ],
    'token' => [
        'length' => 64,
        'expiry_minutes' => 60,
    ],
    'recovery_key' => [
        'length' => 64,
    ],
    'wizard' => [
        'step_keys' => ['welcome', 'account', 'school', 'department', 'finalize', 'complete'],
        'finalize_steps' => ['account', 'school', 'department'],
    ],
    'defaults' => [
        'admin_name' => 'Administrator',
        'admin_username' => 'superadmin',
        'username_max_length' => 20,
    ],
    'security' => [
        'rate_limit_attempts' => 20,
        'rate_limit_decay_seconds' => 60,
        'finalization_window_seconds' => 30,
    ],
    'provisioning' => [
        'paths' => ['env' => base_path('.env'), 'env_example' => base_path('.env.example'), 'storage_link' => public_path('storage')],
    ],
    'force_allowed_environments' => ['local', 'dev', 'development', 'testing'],
]
```

---

## 7. Design Decisions

### DD-1 — Settings Table for Setup State

**Decision:** Store all setup state (`is_installed`, `token`, `completed_steps`, etc.) in the
shared `settings` table rather than a dedicated `setup_states` table.

**Rationale:** Reduces schema complexity. Setup is a one-time event — after finalization, these
rows become read-only historical data. The `settings` table already has group-based organization,
type enforcement, and caching infrastructure.

**Trade-off:** Slightly less type safety (JSON column for `completed_steps`) but eliminates a
migration and table for a one-time use case.

### DD-2 — Token-Based Wizard Access

**Decision:** Use a cryptographically random token (64 chars, encrypted at rest) rather than
signed URLs or session-only auth.

**Rationale:** Tokens survive server restarts, can be communicated verbally or via printout (for
schools with limited IT), and provide a clear audit trail. Encryption at rest prevents token
exposure if the database is compromised.

**Trade-off:** Requires CLI access to generate tokens, but this is acceptable since installation
always starts with CLI or server access.

### DD-3 — Single-Use Token with Versioning

**Decision:** Invalidate token immediately after validation, and use version numbers to invalidate
stale session authorizations.

**Rationale:** Prevents token replay attacks. Version numbers avoid the need to store session state
server-side — the session's `token_version` is compared against the stored version.

### DD-4 — Super Admin Immutability

**Decision:** Super admin name ("Administrator") and username ("superadmin") are hardcoded from
config and cannot be changed during setup.

**Rationale:** These invariants are enforced system-wide (other modules check for them). Making
them configurable would create edge cases in authorization, recovery, and audit logging.

### DD-5 — Atomic Finalization

**Decision:** `FinalizeSetupAction` creates school, department, admin, and settings in a single
database transaction.

**Rationale:** Partial setup creates an unusable state — database seeded but no admin, or admin
created but `is_installed` still false. Atomicity ensures the system either fully works or
remains in setup mode.

### DD-6 — Recovery Key Dual Storage

**Decision:** Store recovery key hashed in database (for verification) and plaintext in a private
file (for retrieval).

**Rationale:** The recovery key is the only way to regain admin access without email. The file is
the "break glass" mechanism — readable by server administrators with filesystem access. The hash
prevents database compromise from revealing the key.

### DD-7 — Session-Based Wizard State

**Decision:** Persist form data in session across step navigation rather than database writes.

**Rationale:** Setup is a transient process — if the session expires, the installer simply restarts
from Step 1. No orphaned partial records in the database. The 30-second post-finalization window
allows copying the recovery key without re-entering data.

### DD-8 — Global Middleware for Setup Redirect

**Decision:** Apply `RequireSetupAccessMiddleware` globally (all routes) rather than only on
specific routes.

**Rationale:** Every page in an uninstalled system should redirect to setup. Applying globally
ensures no page is accidentally accessible before provisioning. The middleware passes through
real files (Vite assets), Livewire requests, and setup routes themselves.

### DD-9 — Force Restriction by Environment

**Decision:** Restrict `--force` flag to non-production environments (`local`, `dev`, `development`,
`testing`).

**Rationale:** `--force` runs `migrate:fresh` which destroys all data. Production systems must
use manual database migration. This is a safety net, not a security boundary — the real protection
is operational discipline.

---

## 8. Success Metrics

### 8.1 Installation Completeness

| Metric                          | Target      | Measurement                           |
| ------------------------------- | ----------- | ------------------------------------- |
| CLI install success rate        | 100%        | All provisioning tasks complete       |
| Wizard completion rate          | 100%        | All 6 steps reachable and completable |
| Recovery key generation         | 100%        | 64-char key, hashed in DB, file saved |
| Super admin login success       | 100%        | Post-setup login works immediately    |

### 8.2 Security Properties

| Metric                          | Target      | Measurement                           |
| ------------------------------- | ----------- | ------------------------------------- |
| Token replay blocked            | Always      | Second validation attempt rejected    |
| Stale session blocked           | Always      | Post-finalization 404                 |
| Force restricted to dev         | Always      | Production CLI rejects `--force`      |
| Audit trail                     | 100%        | All setup actions logged via SmartLogger |

### 8.3 Environment Compatibility

| Metric                          | Target      | Measurement                           |
| ------------------------------- | ----------- | ------------------------------------- |
| PHP 8.4+ detection              | Always      | Audit correctly identifies version    |
| Extension check coverage        | 11 required | All required extensions verified      |
| Permission check accuracy       | Always      | Correctly detects writable/unwritable |

### 8.4 Operational

| Metric                          | Target      | Measurement                           |
| ------------------------------- | ----------- | ------------------------------------- |
| Time to provision (CLI)         | < 30s       | From `setup:install` to token display |
| Time to complete wizard         | < 5 min     | From Step 1 to Step 6                 |
| Post-finalization lockout       | Always      | Setup route 404s after 30s window     |
| Token expiry enforcement        | Always      | Expired tokens rejected               |

---

## Quick References

- `docs/modules/setup.md` — Module conceptual overview
- `docs/modules/setup-reference.md` — Technical reference (Actions, Entity, Routes)
- `docs/key-features.md` — Feature inventory (Setup section)
- `docs/foundation/project-requirements.md` — High-level feature specs (§3.1)
- `docs/foundation/product-definition.md` — Scope, personas, system boundary
- `docs/infrastructure/deployment.md` — Deployment paths (CLI install steps)
- `docs/guide/01-installation.md` — Detailed server prep guide
- `docs/guide/02-setup-wizard.md` — Wizard walkthrough
- `docs/guide/03-post-setup.md` — Post-wizard configuration guide
- `docs/foundation/account-recovery.md` — Recovery key lifecycle
- `config/setup.php` — Setup configuration values
- `app/Setup/` — Full module source code

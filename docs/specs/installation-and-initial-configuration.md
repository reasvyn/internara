# 8NZAU-VEJCX — Installation and Initial Data Configuration

**Document Version:** 1.0.0  
**Status:** Approved Specification  
**Module:** `Setup`  
**Phase:** `Phase 2 — Configuration & Provisioning`  
**Governing Specs:** #457 (`[8NZAU]` Installation), #458 (`[VEJCX]` Setup Wizard), #459 (`[C9ZB6]` Recovery Ecosystem)  

---

## Description

This specification governs the end-to-end installation and initial institutional provisioning pipeline of the Internara platform. It defines how a freshly deployed codebase transitions from an unconfigured repository to a fully operational, secured educational environment: encompassing automated runtime environment auditing, database schema provisioning, time-limited token-gated access control, a 6-step guided browser setup wizard, and atomic finalization with emergency recovery key handoff.

---

## 1. Problem Statements

- **PS-1 — Cold-Start Technical Barrier**: Deploying Laravel applications on budget shared hosting ($5/mo) or bare-metal VPS frequently fails due to missing PHP extensions, directory permission misconfigurations (`storage/`, `bootstrap/cache/`), or unconfigured database connections. Non-technical school IT staff need an automated environment auditor with actionable diagnostic feedback before database migrations execute.
- **PS-2 — Unprotected Pre-Installation Attack Surface**: An unconfigured web application exposed to the public internet presents a severe vulnerability: unauthorized external actors could access the setup interface, register themselves as the root super administrator, and hijack the institutional database. Initial setup must be cryptographically gated.
- **PS-3 — Incomplete or Fragmented Initial Data**: Schools cannot operate PKL programs without foundational institutional metadata (School Profile, NPSN, Vocational Departments, and an authenticated Super Admin). Manual configuration across disparate settings screens leads to orphaned entities and incomplete onboarding.
- **PS-4 — Catastrophic Super Admin Lockout**: After initial provisioning, the super administrator may lose access due to forgotten credentials, misconfigured local SMTP preventing email resets, or locked sessions. A hardware-independent, offline emergency recovery mechanism must be minted at installation and verified via CLI.

---

## 2. Goals & Non-Goals

### Goals

- **Automated Environment Verification** — Run comprehensive pre-flight diagnostics (PHP version, PDO drivers, mandatory extensions, directory write permissions) via CLI and web wizard. *Why:* Prevents mid-migration runtime crashes on resource-constrained servers.
- **Cryptographic Setup Token Gate** — Gate browser access to `/setup` using a high-entropy 64-character token with a 2-hour TTL minted exclusively via server CLI (`php artisan setup:install`). *Why:* Guarantees that only authorized server administrators can initialize the institution.
- **Step-by-Step Guided Initial Data Wizard** — Provide an intuitive 6-step wizard (`welcome`, `account`, `school`, `department`, `finalize`, `complete`) capturing all mandatory school parameters in a unified flow. *Why:* Ensures zero orphaned entities and immediate operational readiness.
- **Atomic Setup Finalization & Lockout** — Execute school profile, department creation, super admin creation, and state persistence within a single database transaction, immediately sealing the setup route with an immutable lock flag. *Why:* Eliminates partial installation states and guarantees single-execution idempotency.
- **Offline Emergency Recovery Key Minting** — Generate a secure 32-character recovery key during finalization, displayed once with one-click copy and downloadable PDF/slip before redirecting to login. *Why:* Prevents permanent system lockout when email services are unconfigured.

### Non-Goals

- **Multi-Tenant Database Provisioning**. *Why:* Internara is strictly single-tenant and self-hosted; each school owns its dedicated database instance.
- **Direct Dapodik/Government API Sync during Setup**. *Why:* Educational data schemas vary; external API integration during initial cold-start creates fragile dependencies. Initial data is seeded via clean web forms and bulk CSV templates.
- **Remote Web-Based Installation without Server Access**. *Why:* Bypassing server CLI access compromises root security. The installer must have shell or cPanel terminal access to mint the setup token.

---

## 3. User Stories / Use Cases

| ID | Requirement | Priority | Layer | Status |
|---|---|---|---|---|
| UC-INST-001 | Server Administrator runs CLI installation command to provision database and mint setup token | P0 | F | Full |
| UC-INST-002 | Server Administrator resets an expired or misplaced setup token via CLI command | P0 | F | Full |
| UC-WIZ-001 | Administrator accesses setup wizard in browser by presenting valid setup token URL query | P0 | F | Full |
| UC-WIZ-002 | Administrator completes the 6-step guided setup wizard to configure school metadata, department, and superadmin | P0 | F | Full |
| UC-WIZ-003 | Administrator finalizes setup, receives emergency recovery key, and is locked out of setup routes permanently | P0 | F | Full |

### 3.1 Provisioning & Access

#### UC-INST-001 — Server Administrator Runs CLI Installation
- **Preconditions**: Codebase deployed, `.env` file present with valid database credentials.
- **Flow**:
  1. Administrator executes `php artisan setup:install` in terminal.
  2. System audits runtime environment (PHP 8.5+, PDO SQLite/MySQL, writable directories).
  3. System provisions database schema via fresh migrations and seeds default roles.
  4. System mints 64-character setup token (valid for 2 hours) and outputs access URL: `https://school.domain/setup?token={token}`.
- **Postconditions**: Database schema ready; `setup.install_token` saved; browser wizard accessible.

#### UC-INST-002 — Resetting Expired Setup Token
- **Preconditions**: Database provisioned; setup token expired or lost prior to finalization.
- **Flow**:
  1. Administrator executes `php artisan setup:reset-token` in terminal.
  2. System verifies system is not yet finalized (`is_installed = false`).
  3. System regenerates a fresh 64-character token with new 2-hour TTL and invalidates previous token.
  4. System outputs the refreshed setup wizard URL.
- **Postconditions**: New setup token activated; expired token permanently rejected.

### 3.2 Guided Configuration Wizard

#### UC-WIZ-001 — Token Validation & Wizard Access Gate
- **Preconditions**: Installer navigates to `/setup?token={token}`.
- **Flow**:
  1. System checks `setup.is_installed`. If true, redirects immediately to `/login`.
  2. Middleware validates token presence and signature against `setup.install_token`.
  3. If token is valid and unexpired, session receives `setup_access_granted = true`.
- **Postconditions**: Wizard step 1 (`welcome`) renders.

#### UC-WIZ-002 — Completing the 6-Step Setup Wizard
- **Preconditions**: Valid setup session established.
- **Flow**:
  1. **Step 1 (Welcome)**: Inspects live environment report; displays green checkmarks.
  2. **Step 2 (Account)**: Administrator inputs Super Admin name, email, username, and password.
  3. **Step 3 (School)**: Administrator inputs School Name, NPSN, address, contact, and logo.
  4. **Step 4 (Department)**: Administrator inputs initial vocational department (Code, Name, Head).
  5. **Step 5 (Finalize)**: Administrator reviews summary and clicks "Finalize Installation".
- **Postconditions**: Data validated and staged for atomic commit.

#### UC-WIZ-003 — Atomic Finalization & Emergency Key Handoff
- **Preconditions**: Step 5 completed and validated.
- **Flow**:
  1. System executes `FinalizeSetupAction` inside database transaction:
     - Persists school profile and department.
     - Creates superadmin user with `superadmin` role.
     - Generates 32-character recovery key; saves hash in settings and user profile.
     - Sets `setup.is_installed = true` and `storage/installed` lockfile.
  2. Wizard advances to **Step 6 (Complete)**:
     - Displays raw emergency recovery key in plain text with one-click copy button.
     - Provides "Download Recovery Slip" action.
     - Displays "Proceed to Login" button.
- **Postconditions**: `/setup` permanently deactivated; instance operational.

---

## 4. Functional Requirements

| ID | Requirement | Priority | Layer | Status |
|---|---|---|---|---|
| FR-INST-001 | Automated runtime environment auditor validating PHP version, PDO drivers, mandatory extensions, and writable paths | P0 | U | Full |
| FR-INST-002 | CLI installation command orchestrating audit, database schema migration, and role seeding | P0 | F | Full |
| FR-INST-003 | Secure token generator minting 64-character cryptographic token with configurable 2-hour TTL | P0 | U | Full |
| FR-INST-004 | CLI reset-token command to regenerate setup tokens prior to finalization | P0 | F | Full |
| FR-INST-005 | Token validation middleware intercepting `/setup` and rejecting expired, malformed, or missing tokens | P0 | F | Full |
| FR-INST-006 | Optional CLI flag `--seed-dummy` for provisioning sample testing datasets in staging | P1 | F | Full |
| FR-WIZ-001 | Multi-step Livewire wizard navigation supporting forward progression and validation gating | P0 | F | Full |
| FR-WIZ-002 | Super Admin account validation enforcing RFC-compliant email, unique username, and strong password | P0 | U | Full |
| FR-WIZ-003 | School profile configuration storing verified NPSN, official name, address, and letterhead branding | P0 | F | Full |
| FR-WIZ-004 | Initial vocational department setup associating department code, name, and department head | P0 | F | Full |
| FR-WIZ-005 | Atomic finalization transaction executing school, department, user, and settings updates in a single rollback-safe unit | P0 | F | Full |
| FR-WIZ-006 | Emergency recovery key generation minting a 32-character high-entropy secret hashed before storage | P0 | U | Full |
| FR-WIZ-007 | Display of emergency recovery key on Step 6 with one-click clipboard copy and printable recovery slip | P0 | F | Full |
| FR-WIZ-008 | Setup completion lockout writing `setup.is_installed = true` and redirecting any subsequent `/setup` requests to `/login` | P0 | F | Full |

### 4.1 Environment Audit & CLI Provisioning

#### FR-INST-001 — Runtime Environment Auditor
- The `EnvironmentAuditor` inspects:
  - PHP version: `^8.4` or `^8.5` (returns fail if `< 8.4`).
  - Required extensions: `pdo`, `mbstring`, `openssl`, `tokenizer`, `xml`, `ctype`, `json`, `curl`, `fileinfo`.
  - Directory write permissions: `storage/framework/*`, `storage/logs`, `bootstrap/cache`.
  - Database connectivity: verified via test PDO ping using `.env` parameters.
- Outputs structured `AuditReport` containing pass/fail flags and diagnostic advice for failed requirements.

#### FR-INST-002 — CLI Installation Command (`setup:install`)
- Syntax: `php artisan setup:install [--force] [--seed-dummy]`
- If system is already installed (`SetupEntity::get()->isInstalled()`), command aborts with error unless `--force` is supplied.
- Calls `SystemProvisioner::executeAll()`:
  - Runs pending database migrations (`Artisan::call('migrate')`).
  - Seeds default system roles (`superadmin`, `school_admin`, `school_teacher`, `industry_supervisor`, `student`).
  - Mints and displays the initial setup token URL.

### 4.2 Setup Token Lifecycle & Access Control

#### FR-INST-003 — Setup Token Minting & Data Structure
- Generates 64-character random hexadecimal token via `Str::random(64)`.
- Persists token into `settings` table with key `setup.install_token` and expiry `setup.token_expires_at` set to `now()->addHours(2)`.
- Increments `setup.token_version` to invalidate any pre-existing browser sessions.

#### FR-INST-005 — Token Gate Middleware (`ProtectSetupRouteMiddleware`)
- Intercepts all requests targeting `/setup*`.
- If `setup.is_installed` is true, immediately redirects to `/login` with HTTP 302.
- Inspects query parameter `?token=...` or active session `setup_token`.
- Validates token against database store:
  - If token does not match: aborts with HTTP 403 Forbidden.
  - If `now() > token_expires_at`: aborts with HTTP 401 Unauthorized (`setup.token_expired`).
  - If valid: binds token to current session.

### 4.3 Browser-Based Guided Wizard

#### FR-WIZ-001 — Multi-Step Navigation & Step Constraints
- Implemented as a Livewire component (`SetupWizard`) inheriting `BaseWizard`.
- Step progression is linear: `welcome` (1) → `account` (2) → `school` (3) → `department` (4) → `finalize` (5) → `complete` (6).
- Navigation to subsequent steps is disabled until the current step's Form Object passes all validation rules.

#### FR-WIZ-002 — Super Admin Validation (`SuperAdminForm`)
- Validation constraints:
  - Name: Required, string, 2–100 characters.
  - Email: Required, valid RFC format, unique in `users` table.
  - Username: Required, alphanumeric + dashes/underscores, 3–30 characters, unique.
  - Password: Required, minimum 8 characters, mixed case, numbers, symbols, matching confirmation.

#### FR-WIZ-003 — School Profile Form (`SchoolForm`)
- Validation constraints:
  - School Name: Required, string, max 255 characters.
  - NPSN: Required, 8 numeric digits, valid Indonesian national school identifier format.
  - Address: Required, minimum 10 characters.
  - Official Email: Required, valid email format.
  - Letterhead Logo: Optional image file (JPG, PNG), max 2MB, validated via MIME allowlist.

#### FR-WIZ-004 — Initial Department Form (`DepartmentForm`)
- Validation constraints:
  - Department Code: Required, uppercase alphanumeric, 2–10 characters (e.g., `RPL`, `TKJ`).
  - Department Name: Required, string, 3–100 characters (e.g., `Rekayasa Perangkat Lunak`).
  - Head of Department: Required, string, 2–100 characters.

### 4.4 Atomic Finalization & Emergency Key Handoff

#### FR-WIZ-005 — Atomic Finalization Execution (`FinalizeSetupAction`)
- Wraps the entire state transition in `DB::transaction()`:
  1. Persists school settings via `SetupSchoolAction`.
  2. Creates initial department record via `SetupDepartmentAction`.
  3. Creates `User` record with hashed password and assigns `superadmin` role via `SetupSuperAdminAction`.
  4. Generates emergency recovery key, hashes with `Hash::make()`, and stores hash in `users.recovery_key_hash`.
  5. Updates `setup.is_installed = true` and `setup.install_token = null`.
  6. Dispatches `SetupFinalized` event.
- If any action fails, all changes roll back; system remains uninstalled.

#### FR-WIZ-006 & FR-WIZ-007 — Emergency Key Handoff
- Displays raw plaintext recovery key (32-character base64/hex string) on Step 6.
- Raw key is never stored in plaintext in the database (only bcrypt/argon2 hash).
- Provides "Copy to Clipboard" with visual confirmation toast and "Download Recovery Slip" PDF download.

#### FR-WIZ-008 — Post-Finalization Lockout
- Once `setup.is_installed` is set to `true`:
  - `ProtectSetupRouteMiddleware` blocks all further access to `/setup`.
  - Session tokens are cleared.
  - User is guided to `/login`.

---

## 5. Non-Functional Requirements

| ID | Requirement | Target | Priority | Layer | Status |
|---|---|---|---|---|---|
| NFR-SET-001 | High-entropy cryptographic setup token strength | 256 bits (64 hex characters) | P0 | U | Full |
| NFR-SET-002 | Setup token time-to-live expiration window | Exactly 120 minutes (2 hours) | P0 | F | Full |
| NFR-SET-003 | Atomic finalization transaction execution latency | P95 < 800ms on 1 vCPU / 2GB RAM | P1 | F | Full |
| NFR-SET-004 | Emergency recovery key entropy | 128+ bits (32 characters alphanumeric) | P0 | U | Full |
| NFR-SET-005 | Post-finalization route lockout guarantee | 100% rejection (0 bypass exceptions) | P0 | A | Full |
| NFR-SET-006 | Zero external cloud dependency for installation | 100% local execution (SQLite/MySQL + local FS) | P0 | A | Full |

### 5.1 Security & Access Guarding
- **NFR-SET-001 & NFR-SET-002 (Token Security)**: Tokens are generated via CSPRNG (`random_bytes`). After 120 minutes, the token expires automatically. Brute-force attacks against `/setup?token=...` are throttled at 5 requests/minute per IP address.
- **NFR-SET-004 (Key Protection)**: The emergency recovery key is displayed exactly once during Step 6. It cannot be recovered from the database afterward, mirroring SSH private key security practices.
- **NFR-SET-005 (Lockout Verification)**: Verified via automated architecture tests ensuring that when `setup.is_installed === true`, the `/setup` endpoint unconditionally returns HTTP 302 redirecting to `login`.

### 5.2 Performance & Reliability
- **NFR-SET-003 (Transaction Speed)**: Database schema setup, role bindings, superadmin creation, and setting persistence must execute in under 800ms to avoid web request gateway timeouts (e.g., FastCGI 30s timeout).
- **NFR-SET-006 (Offline & Shared Hosting Compatibility)**: Installation works on shared hosting with no daemon worker, no Redis, and no external internet access required during execution.

---

## 6. API / Data Contracts

### 6.1 State Entity Schema (`SetupEntity`)

```php
namespace App\Modules\Setup\Entities;

final readonly class SetupEntity extends BaseEntity
{
    private const array KEYS = [
        'is_installed'         => 'setup.is_installed',         // boolean
        'install_token'        => 'setup.install_token',        // string (64 chars)
        'token_expires_at'     => 'setup.token_expires_at',     // datetime
        'completed_steps'      => 'setup.completed_steps',      // json array ['welcome', 'account', ...]
        'install_recovery_key' => 'setup.install_recovery_key', // string (hashed)
        'token_version'        => 'setup.token_version',        // integer
        'updated_at'           => 'setup.updated_at',           // datetime
    ];
}
```

### 6.2 Data Transfer Objects (DTO)

#### `SetupTokenData`
```php
namespace App\Modules\Setup\Domain\Installation\Data;

final readonly class SetupTokenData
{
    public function __construct(
        public string $token,
        public Carbon $expiresAt,
        public int $version,
        public string $setupUrl,
    ) {}
}
```

#### `FinalizeSetupData`
```php
namespace App\Modules\Setup\Domain\SetupWizard\Data;

final readonly class FinalizeSetupData
{
    public function __construct(
        public array $schoolData,       // ['name' => ..., 'npsn' => ..., 'address' => ..., ...]
        public array $departmentData,   // ['code' => ..., 'name' => ..., 'head_of_department' => ...]
        public array $adminData,        // ['name' => ..., 'email' => ..., 'username' => ..., 'password' => ...]
        public array $stepsToComplete,  // ['welcome', 'account', 'school', 'department', 'finalize']
    ) {}
}
```

### 6.3 Command Actions & Method Signatures

```php
// 1. Technical Installation Action
namespace App\Modules\Setup\Domain\Installation\Actions;

final class InstallSystemAction extends BaseCommandAction
{
    public function execute(bool $force = false, ?AuditReport $report = null): SetupTokenData;
}

// 2. Token Validation Action
namespace App\Modules\Setup\Domain\Installation\Actions;

final class ValidateSetupTokenAction extends BaseReadAction
{
    public function execute(string $token): bool;
}

// 3. Setup Finalization Action
namespace App\Modules\Setup\Domain\SetupWizard\Actions;

final class FinalizeSetupAction extends BaseCommandAction
{
    /**
     * Executes atomic installation commit.
     * @return string Plaintext 32-character recovery key (displayed once to installer)
     * @throws RejectedException If instance is already installed or validation fails
     */
    public function execute(FinalizeSetupData $data): string;
}
```

### 6.4 CLI Commands

- `php artisan setup:install {--force : Overwrite existing installation} {--seed-dummy : Seed demo test datasets}`
- `php artisan setup:reset-token`

### 6.5 HTTP Endpoints & Middleware Pipeline

- **`GET /setup`**: Routed to `SetupController@index` rendered by `SetupWizard` Livewire component.
  - Middleware: `web`, `ProtectSetupRouteMiddleware`.
- **`POST /setup/reset`**: Internal token reset trigger (authenticated CLI only).

---

## 7. Design Decisions

| ID | Requirement | Priority | Layer | Status |
|---|---|---|---|---|
| DD-SET-001 | CLI Token as Cryptographic Bridge between server provisioning and browser wizard | P0 | A | Full |
| DD-SET-002 | Atomic Single-Transaction Finalization ensuring rollback safety | P0 | F | Full |
| DD-SET-003 | Single-Display Emergency Recovery Key with Bcrypt Hashing | P0 | U | Full |
| DD-SET-004 | Dual Lockout Mechanism via Database Flag and Storage File | P0 | F | Full |

### 7.1 Key Architecture Decisions

#### DD-SET-001 — CLI Token as Cryptographic Bridge
- *Decision*: The web installation wizard `/setup` is completely inaccessible until a server administrator runs `php artisan setup:install` on the CLI. The command mints a single-use 64-character token required as a query parameter.
- *Rationale*: Prevents automated internet web scanners from discovering freshly deployed Internara installations and claiming the superadmin account.

#### DD-SET-002 — Atomic Single-Transaction Finalization
- *Decision*: School profile, department, super admin user, settings flags, and emergency keys are committed in a single `DB::transaction()` inside `FinalizeSetupAction`.
- *Rationale*: If department creation fails after school settings are saved, the entire database reverts. A system cannot exist in a half-configured state.

#### DD-SET-003 — Single-Display Emergency Recovery Key
- *Decision*: The raw recovery key is minted, returned once from `FinalizeSetupAction`, displayed in Step 6, and only its cryptographic hash is stored in `users.recovery_key_hash`.
- *Rationale*: Adheres to zero-knowledge security principles. If the database is compromised, the attacker cannot read the plaintext emergency recovery key.

#### DD-SET-004 — Dual Lockout Mechanism
- *Decision*: Setup completion is secured by both a database key `setup.is_installed = true` and a physical lock file `storage/installed`.
- *Rationale*: Defense-in-depth: even if a database connection temporarily fails or is misrouted, the existence of the physical lock file on disk immediately triggers setup route lockout.

---

## 8. Success Metrics

- **Zero-Failure First Boots**: 100% of installations passing the `EnvironmentAuditor` complete database provisioning without manual SQL intervention.
- **Onboarding Speed**: Median time to complete the 6-step browser wizard is under 3 minutes for a standard school administrator.
- **Zero Orphaned Deployments**: 0 instances registered without a valid root superadmin and initial department.
- **Zero Setup Hijacks**: 0 security incidents involving unauthorized superadmin account claiming on unconfigured instances.

---

## 9. Roadmap

- **Phase 2 (Current Baseline)**: CLI installation, token-gated 6-step wizard, atomic finalization, emergency recovery keys, and dummy data seeder.
- **Phase 3 (Next)**: Automated school logo cropping during wizard Step 3, CSV department batch pre-loader during Step 4, and automated email SMTP test ping button during wizard finalization.

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
|---|---|---|---|---|
| R-1 | If the server loses internet connection, can initial installation proceed? Mitigated by zero external cloud API dependencies. | Resolved | Backend Lead | [#457](https://github.com/reasvyn/internara/issues/457) |
| R-2 | If the installer forgets to copy the emergency recovery key before closing the browser, can they recover? Mitigated by CLI command `php artisan setup:rescue-token`. | Resolved | Security Lead | [#459](https://github.com/reasvyn/internara/issues/459) |
| A-1 | We assume the installer has SSH, terminal, or hosting control panel access to execute `php artisan setup:install`. | Accepted | Product Lead | — |
| A-2 | We assume SQLite with WAL mode is the default local engine, while MySQL 8.0+ is configured for multi-user production. | Accepted | Backend Lead | — |

---

## Quick References

- [`docs/index.md`](../index.md) — Documentation index
- [`docs/prd.md`](../prd.md) — Product Requirement Document (PRD)
- [`docs/templates/spec-template.md`](../templates/spec-template.md) — Spec template contract
- [`docs/refs/modules/setup.md`](../refs/modules/setup.md) — Setup module conceptual reference
- [`docs/refs/modules/setup-reference.md`](../refs/modules/setup-reference.md) — Setup module technical API reference
- [GitHub Issue #457](https://github.com/reasvyn/internara/issues/457) — Installation Spec `[8NZAU]`
- [GitHub Issue #458](https://github.com/reasvyn/internara/issues/458) — Setup Wizard Spec `[VEJCX]`

# Installation & Provisioning — Feature Specification

> **Spec ID:** 8NZAU
> **Last updated:** 2026-08-16 **Changes:** amend — super admin default name is `Super Admin`; align to implementation: fix extension count
> (12), recovery key setting name (`setup.install_recovery_key`), replace module-discovery FRs with
> cross-reference to I1BCV, add HTTP middleware/routes/controller contracts (FR-H1–H9), complete
> `config/setup.php` contract, record ADR for dead `InstallSystemAction`

## Description

Specification for the CLI-based installation and provisioning system of Internara. Covers environment
audit, database provisioning, token generation, and super admin recovery. The browser-based setup
wizard is a separate initiative — see [setup-wizard.md](VEJCX-setup-wizard.md).

---

## 1. Problem Statements

### PS-1 — First-Boot Experience

A freshly deployed Internara instance has no database schema, no seed data, and no administrator
account. The system must guide an installer through environment validation, database provisioning,
and initial configuration without requiring manual SQL or config file editing.

### PS-2 — Single-Execution Guarantee

Setup must execute exactly once. Running installation on an already-configured system must not
corrupt data, create duplicate accounts, or reset configuration.

### PS-3 — Environment Readiness

Different servers have different PHP versions, extensions, directory permissions, and database
configurations. The system must detect and report incompatibilities before attempting provisioning
to prevent partial or failed installations.

### PS-4 — Deployment Flexibility

The installer may be a school IT staff using shared hosting, a developer using Docker, or a
sysadmin with CLI access. The system must support both CLI-first and browser-based installation
paths. CLI provisioning generates a token that bridges to the browser wizard.

### PS-5 — Recovery Key Lifecycle

After setup, the super admin may lose access (forgotten password, account lockout). A recovery
mechanism must exist that does not rely on email (which may not be configured) or web UI (which
requires login).

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal                                                               |
| --- | ------------------------------------------------------------------ |
| G1  | Complete system provisioning from zero to operational in under 30 seconds (CLI) |
| G2  | Provide clear, actionable environment audit results before provisioning |
| G3  | Ensure idempotent setup — running twice causes no harm             |
| G4  | Support CLI-first installation path (`setup:install`)              |
| G5  | Generate a setup token that bridges CLI provisioning to browser wizard |
| G6  | Generate a recovery key for emergency super admin access            |

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

### UC-2 — Token Reset (Lost Token)

**Actor:** Server administrator

**Preconditions:** System not yet finalized, CLI access.

**Flow:**
1. Administrator runs `php artisan setup:reset-token`
2. System checks settings table exists and system is not installed
3. New token generated (old token invalidated via version increment)
4. New signed URL displayed

**Postconditions:** Previous token is invalid, new token is valid for 60 minutes.

### UC-3 — Emergency Super Admin Recovery

**Actor:** Server administrator

**Preconditions:** System installed, super admin account inaccessible, CLI access.

**Flow:**
1. Administrator runs `php artisan admin:recover`
2. System generates new password, displays it
3. Administrator uses new password to log in

**Postconditions:** Super admin account is accessible with new password.

### UC-4 — Environment Audit Only

**Actor:** Server administrator

**Preconditions:** PHP 8.4+, Composer dependencies installed.

**Flow:**
1. Administrator runs `php artisan setup:install --check-only`
2. System runs environment audit and displays results
3. No provisioning occurs

**Postconditions:** Administrator knows environment readiness status.

### UC-5 — CLI Installation with Demo Dataset

**Actor:** Server administrator / developer

**Preconditions:** Same as UC-1.

**Flow:**
1. Administrator runs `php artisan setup:install --with-dummy`
2. System provisions as in UC-1 (audit → provision → token)
3. After provisioning, system seeds the demo dataset via `DummySeeder`
   (see [dummy-data.md](3UOZP-dummy-data.md))
4. In `APP_ENV=production`, the demo seed is refused — a warning is shown, the seed is skipped,
   and the installation continues normally

**Postconditions:** Database provisioned with demo data (non-production) or skip-warned
(production); token valid for 60 minutes.

---

## 4. Functional Requirements

### 4.1 Environment Audit

| ID   | Requirement                                                              |
| ---- | ------------------------------------------------------------------------ |
| FR-A1 | System must check PHP version >= 8.4.0                                 |
| FR-A2 | System must check 12 required extensions: bcmath, ctype, fileinfo, mbstring, openssl, pdo, tokenizer, xml, curl, gd, intl, zip |
| FR-A3 | System must warn about recommended extensions: redis, pcntl, posix      |
| FR-A4 | System must verify directory permissions: storage/, bootstrap/cache/     |
| FR-A5 | System must verify database connectivity                                |
| FR-A6 | System must verify terminal access (for artisan commands)               |
| FR-A7 | Audit results must be categorized: REQUIREMENTS, PERMISSIONS, DATABASE, TERMINAL, RECOMMENDATIONS |
| FR-A8 | Each check must show pass/fail/warning status with specific details      |
| FR-A9 | Audit must be re-runnable (Recheck button in wizard)                    |
| FR-A10 | Frontend assets check: verify `public/build/manifest.json` exists (Vite build output) |
| FR-A11 | TERMINAL checks: `pcntl_fork` (animations) and `posix_isatty` (interactive terminal) — WARN if missing |
| FR-A12 | Post-install template `.env` warnings: detect placeholder values in APP_URL, DB_PASSWORD, MAIL_USERNAME, MAIL_PASSWORD, MAIL_FROM_ADDRESS |

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

### 4.4 CLI Commands

| ID   | Requirement                                                              |
| ---- | ------------------------------------------------------------------------ |
| FR-C1 | `setup:install` — provisions system, generates token, displays URL       |
| FR-C2 | `setup:install --check-only` — runs audit only, no provisioning         |
| FR-C3 | `setup:install --force` — wipes DB and re-provisions (restricted envs)   |
| FR-C4 | `setup:install --url=URL` — sets APP_URL in `.env` and generates token URL |
| FR-C5 | `setup:install --optimize` — caches config, routes, views, events (production) |
| FR-C6 | `setup:reset-token` — generates new token, invalidates old              |
| FR-C7 | `admin:recover` — generates new password for super admin (see [recovery-ecosystem.md](C9ZB6-recovery-ecosystem.md)) |
| FR-C8 | `setup:install` on installed system must fail unless `--force`           |
| FR-C9 | `--force` on non-local environments must be rejected                    |
| FR-C10 | `setup:install --with-dummy` — after provisioning, seeds the demo dataset via `DummySeeder` (see [dummy-data.md](3UOZP-dummy-data.md)) |

### 4.5 Module Discovery

| ID   | Requirement                                                              |
| ---- | ------------------------------------------------------------------------ |
| FR-D1 | `setup:install` must run `module:discover` after provisioning            |
| FR-D2 | Boot-time discovery (Livewire, policies, view namespaces) is governed by [module-discovery.md](I1BCV-module-discovery.md) — not re-specified here |

Full discovery requirements (FR-V*, FR-CLI*, FR-R*, FR-T*, NFR-*) live in
[module-discovery.md](I1BCV-module-discovery.md). This spec only adds the installation
integration point (FR-D1) and defers the rest to that spec.

### 4.6 HTTP Middleware, Routes & Controller

| ID    | Requirement                                                              |
| ----- | ------------------------------------------------------------------------ |
| FR-H1 | Before installation, all non-setup web routes must redirect to `/setup` (`RequireSetupAccessMiddleware`) |
| FR-H2 | `RequireSetupAccessMiddleware` must allow Livewire requests and static assets through |
| FR-H3 | Setup routes must be protected by `setup.protected` alias → `ProtectSetupRouteMiddleware` |
| FR-H4 | When installed, setup routes must be unreachable (404) except during the post-finalization window |
| FR-H5 | `GET/POST /setup` and `POST /setup/cleanup` (name `setup.cleanup`) must exist under the protected group |
| FR-H6 | The `setup` route must be excluded from CSRF verification (token-based auth instead) |
| FR-H7 | Session authorization (`setup.authorized` + `token_version`) must gate access instead of re-validating the token on every request |
| FR-H8 | Session ID must be regenerated after successful token validation |
| FR-H9 | `/setup/cleanup` must purge all setup session state (`authorized`, `token`, `token_input`, `form_data`, `completed`) |

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
| NFR-S13 | `--with-dummy` must be refused in production: the demo seed is skipped with a warning and the installation continues; `DummySeeder` keeps its own independent production guard (dummy-data NFR-S1) as defense in depth |

### 5.2 Performance

| ID    | Requirement                                                          |
| ----- | -------------------------------------------------------------------- |
| NFR-P1 | Environment audit must complete within 5 seconds                     |
| NFR-P2 | Full provisioning (migrations + seeders) must complete within 30 seconds |
| NFR-P3 | Token generation must use cache lock to prevent race conditions      |
| NFR-P4 | Post-install cache invalidation must complete within 2 seconds       |
| NFR-P5 | Module discovery performance is governed by [module-discovery.md](I1BCV-module-discovery.md) (NFR-P1–P5) — not re-specified here |

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
| NFR-U2 | All CLI output must be available in English and Indonesian           |

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
| `setup.install_recovery_key` | string   | Hashed recovery key (bcrypt)         |
| `setup.updated_at`       | datetime | Last setup state modification            |

### 6.2 Setup Entity Contract

```php
final readonly class SetupEntity extends BaseEntity
{
    public function __construct(
        bool $dbInstalled,
        ?string $setupToken,
        ?Carbon $tokenExpiresAt,
        array $completedSteps,
        ?string $recoveryKey,
        ?Carbon $updatedAt = null,
        int $tokenVersion = 0,
    );

    public function isInstalled(): bool;
    public function hasStoredToken(): bool;
    public function isTokenExpired(?Carbon $now): bool;
    public function validateToken(string $decrypted, string $input, ?Carbon $now): bool;
    public function isStepCompleted(string $step): bool;
    public function allStepsCompleted(): bool;
    public function hasRecoveryKey(): bool;

    public function setupToken(): ?string;
    public function tokenExpiresAt(): ?Carbon;
    public function recoveryKey(): ?string;
    public function completedSteps(): array;
    public function updatedAt(): ?Carbon;
    public function tokenVersion(): int;

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

### 6.4 Action Contracts

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

// SetupSuperAdminAction
class SetupSuperAdminAction extends BaseCommandAction
{
    public function execute(string $email, string $password): User;
    // @throws RejectedException when super admin immutable
}
```

> **ADR — `InstallSystemAction` removed.** An earlier revision of this spec defined
> `InstallSystemAction extends BaseProcessAction` as the orchestration entry point
> (audit → provision → token). The implementation never wired it: `setup:install`
> performs that orchestration inline (`SetupInstallCommand` composes `EnvironmentAuditor`,
> `SystemProvisioner`, `GenerateSetupTokenAction`). The class shipped as dead code and has
> been removed from this contract. If a reusable process action is desired later, extract
> it from `SetupInstallCommand::handle()` rather than re-adding a standalone action.

### 6.5 Module Registry Config

```php
// config/module.php
return [
    'list' => ['Core', 'Setup', 'Settings', /* ... */],
    'registry' => [
        'Core' => ['Channels', 'Console', 'Contracts', 'Exceptions'],
        'Journals' => ['AbsenceRequest', 'Attendance', 'Logbook', 'MonitoringVisit', 'SupervisionLog'],
        // ...
    ],
    'test_dirs' => ['Providers', 'Stubs', 'Support'],
    // ...
];
```

### 6.6 Events

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

### 6.7 Config

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
        'admin_name' => 'Super Admin',
        'admin_username' => 'superadmin',
        'username_max_length' => 20,
    ],
    'security' => [
        'rate_limit_attempts' => 20,
        'rate_limit_decay_seconds' => 60,
        'finalization_window_seconds' => 30,
    ],
    'provisioning' => [
        'paths' => ['env' => '.env', 'env_example' => '.env.example', 'storage_link' => 'storage'],
    ],
    'audit_categories' => [
        AuditCategory::REQUIREMENTS,
        AuditCategory::PERMISSIONS,
        AuditCategory::DATABASE,
        AuditCategory::TERMINAL,
        AuditCategory::RECOMMENDATIONS,
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

**Decision:** Super admin name ("Super Admin") and username ("superadmin") are hardcoded from
config and cannot be changed during setup.

**Rationale:** These invariants are enforced system-wide (other modules check for them). Making
them configurable would create edge cases in authorization, recovery, and audit logging.

### DD-5 — Recovery Key Dual Storage

**Decision:** Store recovery key hashed in database (for verification) and plaintext in a private
file (for retrieval).

**Rationale:** The recovery key is the only way to regain admin access without email. The file is
the "break glass" mechanism — readable by server administrators with filesystem access. The hash
prevents database compromise from revealing the key.

### DD-6 — Force Restriction by Environment

**Decision:** Restrict `--force` flag to non-production environments (`local`, `dev`, `development`,
`testing`).

**Rationale:** `--force` runs `migrate:fresh` which destroys all data. Production systems must
use manual database migration. This is a safety net, not a security boundary — the real protection
is operational discipline.

### DD-7 — Config-Driven Module Registry

**Decision:** Store module list and submodule mapping in `config/module.php` as the single source
of truth, consumed by `ModuleManager`/`ModuleService`, `routes/web.php`, and `tests/Pest.php`.

**Rationale:** Previously, module lists were hardcoded in 5+ locations (config, routes, tests,
Python scripts). drift between these caused bugs. Centralizing in config ensures one place to
update when adding/removing modules.

**Trade-off:** `tests/Pest.php` must maintain a parallel hardcoded list because `config()` is
not available at Pest discovery time. Comment explains the sync requirement.

### DD-8 — Optional Optimization During Install

**Decision:** Provide `--optimize` flag to run `config:cache`, `route:cache`, `view:cache`,
`event:cache` during installation. Not enabled by default.

**Rationale:** Caching improves first-request performance (~60% faster bootstrap). However,
caching during install can cause issues in development (cached config prevents `.env` changes
from taking effect). Making it opt-in lets production deployments enable it while keeping
development smooth.

**Trade-off:** After caching, the Container and Facade instances must be rebound (via
`Container::setInstance()` and `Facade::setFacadeApplication()`) to prevent stale references.
This is an implementation detail documented in the command.

### DD-9 — Localhost URL Detection

**Decision:** When `--url` is not provided and `APP_URL` contains `localhost`, `127.0.0.1`,
or `your-domain.com`, display a warning suggesting the administrator set a proper URL.

**Rationale:** The default `.env.example` has `APP_URL=http://localhost`. Production deployments
must set a real URL for password reset links, notification URLs, and asset loading. Detecting
this early prevents broken links in production.

**Trade-off:** The check is heuristic (string matching), not authoritative. A deployment on
`localhost` (e.g., Docker health checks) would see a false warning. Acceptable because the
warning is informational, not blocking.

### DD-10 — Demo Dataset Seed on Install

**Decision:** Provide `--with-dummy` flag to seed the demo dataset (`DummySeeder`) immediately
after provisioning. In `APP_ENV=production` the seed is skipped with a warning and the install
continues; it never fails the installation.

**Rationale:** A fresh install is immediately demo-able for developers, presenters, and QA,
removing the extra `db:seed --class=DummySeeder` step (dummy-data DD-3 trade-off). Skipping
rather than failing in production keeps `setup:install`'s single-execution guarantee intact
while `DummySeeder`'s own guard (dummy-data NFR-S1) remains the last line of defense.

**Trade-off:** The production check is duplicated at orchestration level (the seeder also
guards) — deliberate defense in depth: the command must not fail an otherwise-successful
production install.

---

## 8. Success Metrics

### 8.1 Installation Completeness

| Metric                          | Target      | Measurement                           |
| ------------------------------- | ----------- | ------------------------------------- |
| CLI install success rate        | 100%        | All provisioning tasks complete       |
| Recovery key generation         | 100%        | 64-char key, hashed in DB, file saved |
| Super admin login success       | 100%        | Post-setup login works immediately    |

### 8.2 Security Properties

| Metric                          | Target      | Measurement                           |
| ------------------------------- | ----------- | ------------------------------------- |
| Token replay blocked            | Always      | Second validation attempt rejected    |
| Force restricted to dev         | Always      | Production CLI rejects `--force`      |
| Audit trail                     | 100%        | All setup actions logged via SmartLogger |

### 8.3 Environment Compatibility

| Metric                          | Target      | Measurement                           |
| ------------------------------- | ----------- | ------------------------------------- |
| PHP 8.4+ detection              | Always      | Audit correctly identifies version    |
| Extension check coverage        | 12 required | All required extensions verified      |
| Permission check accuracy       | Always      | Correctly detects writable/unwritable |

### 8.4 Operational

| Metric                          | Target      | Measurement                           |
| ------------------------------- | ----------- | ------------------------------------- |
| Time to provision (CLI)         | < 30s       | From `setup:install` to token display |
| Module discovery (cached)       | < 2s        | See [module-discovery.md](I1BCV-module-discovery.md) NFR-P1 |

---

## 9. Roadmap

### Prerequisites
This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [base-classes.md](SE5Q9-base-classes.md) (SE5Q9) | `BaseCommandAction`, `BaseWizard`, exception hierarchy for error handling |

### Build Guide
After implementing this spec, the system can provision itself from zero: environment audit, database schema, APP_KEY, seeded roles, and a setup token that bridges CLI to browser wizard. The next step is to build the setup wizard, which consumes the setup token and guides the user through super admin creation, school profile, and department setup.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [setup-wizard.md](VEJCX-setup-wizard.md) | Uses setup token from §6.3, reads `setup.is_installed` flag, extends `BaseWizard` |
| 2 | [recovery-ecosystem.md](C9ZB6-recovery-ecosystem.md) | Verifies recovery key hash stored in `setup.install_recovery_key` during finalization |

---

## Quick References

- `docs/modules/setup.md` — Module conceptual overview
- `docs/modules/setup-reference.md` — Technical reference (Actions, Entity, Routes)
- `docs/specs/setup-wizard.md` — Browser-based wizard initiative
- `docs/foundation/project-requirements.md` — High-level feature specs
- `docs/foundation/product-definition.md` — Scope, personas, system boundary
- `docs/foundation/installation.md` — Detailed server prep guide
- `docs/foundation/account-recovery.md` — Recovery key lifecycle
- **Related specs:** [recovery-ecosystem.md](C9ZB6-recovery-ecosystem.md) — Super admin emergency access, CLI commands, OTP
- `config/setup.php` — Setup configuration values
- `config/module.php` — Module registry (SSOT)
- `app/Setup/Installation/` — CLI installation module (commands, services, middleware, token actions)
- `app/SysAdmin/Observability/Services/EnvironmentAuditor.php` — cross-module dependency: environment audit lives in the SysAdmin/Observability module, not under `app/Setup/`
- `app/Core/Services/ModuleService.php` — Module discovery implementation

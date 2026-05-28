# Setup Domain
> Last updated: 2026-05-28
> Changes: docs: add ideal setup domain design


## Purpose

Setup manages the first-run experience — the one-time process of turning a blank database and
unconfigured server into a fully operational application. It is the only domain that runs
before authentication exists, before any business data exists, and before the system is
considered "installed."

Once setup completes successfully, the domain permanently locks itself. Every route, action,
and middleware in Setup becomes inert — returning 404 for web routes and rejecting CLI
commands — until explicitly reset via `--force` in development environments.

---

## Design Principles

### 1. Single-Use, Replay-Proof Token

Access to the setup wizard is gated by a cryptographically random token. The token is:

- **Encrypted at rest** — stored using Laravel's encryption
- **Time-limited** — configurable expiry (default 60 minutes)
- **Single-use** — consumed atomically on first successful validation via `lockForUpdate()`
- **Rate-limited** — 20 attempts per 60 seconds per IP
- **Timing-attack resistant** — validated with `hash_equals()`

After successful validation, the token is immediately nullified in the database. A second
request with the same token is rejected. This prevents replay attacks even if the token
is intercepted after validation.

### 2. Fail-Fast Environment Audit

Before any database write occurs, the system performs a comprehensive audit of the server
environment. Each check is independent and produces PASS, FAIL, or WARN:

| Level | Blocks Installation | Examples |
|---|---|---|
| FAIL | ✅ Yes | PHP version < 8.4, missing required extensions, unwritable storage, no database |
| WARN | ❌ No | Missing optional extensions, no frontend assets built, no terminal support |

FAIL-level checks block progression unconditionally. WARN-level checks are advisory —
the installer can proceed but should be aware of limitations.

The audit is designed to be re-runnable with zero side effects. The installer can fix
issues and re-run the audit without concern.

### 3. Self-Destructing Lock

Once the wizard completes successfully:

1. The `is_installed` flag is set to `true` in the database
2. All setup session data is force-cleared
3. Setup routes return 404 for all requests
4. CLI commands (except `--force` in non-production) are blocked

A short **finalization window** (default 30 seconds) allows the completion screen
(step 7) to render after installation. During this window, only the `setup.completed`
session flag grants access — the `setup.authorized` flag is insufficient.

The lock can only be reset via `php artisan setup:install --force`, which runs
`migrate:fresh` and regenerates a token. This is restricted to non-production
environments.

### 4. Recovery Key as Last Resort

At finalization, a cryptographically random recovery key (default 64 characters) is
generated. It is:

- **Shown exactly once** — on the completion screen
- **Hashed with bcrypt** before storage — never stored in plaintext
- **Irretrievable** — no admin panel, no CLI command can display it again

The recovery key serves as an emergency credential for the `RecoverSuperAdminAction`,
enabling admin account recovery if all super admin access is lost. The recovery
command (`admin:recover`) prompts for the key and verifies it against the stored
hash.

### 5. Shared Actions Between Web and CLI

Both the web wizard and the CLI installer delegate to the same underlying Actions.
The `FinalizeSetupAction`, `SetupSchoolAction`, `SetupDepartmentAction`, and
`SetupSuperAdminAction` are used identically whether invoked from the browser or
the command line. This ensures consistency — the CLI path produces the same result
as the web wizard.

---

## Layer Structure

```
app/Domain/Setup/
├── Actions/         → Command and Process Actions for installation
├── Console/         → Artisan commands (CLI installation)
├── Entities/        → SetupState — immutable installation state
├── Events/          → SetupFinalized — dispatched on completion
├── Http/
│   └── Middleware/   → Token gating and installation redirect
├── Listeners/       → Side effects on setup completion
├── Livewire/        → 7-step wizard component
│   └── Forms/       → Form Objects per wizard step
├── Models/          → Setup — installation state persistence
├── Policies/        → Authorization guard for setup operations
└── Support/         → Environment auditor and system provisioner
```

---

## Actions

### Command Actions

| Action | Input | Side Effects | Description |
|---|---|---|---|
| `SetupSchoolAction` | School data array | Creates School record, audit log | Creates the institution record during setup |
| `SetupDepartmentAction` | Department data array | Creates Department record, audit log | Creates the first department/study program |
| `SetupSuperAdminAction` | `(string email, string password)` | Creates User + assigns super_admin role, audit log | Creates the first admin. Name and username are ALWAYS from config defaults — only email and password are accepted |
| `SetupDepartmentAction` | Department data | Creates department, audit log | Creates the first department |
| `GenerateSetupTokenAction` | None | Encrypted token in DB, cache invalidation | Generates a time-limited, single-use setup token |
| `RecoverSuperAdminAction` | Recovery key, new credentials | Validates hash, creates new super_admin | Emergency admin recovery when all access is lost |

### Process Actions

| Action | Coordinates | Description |
|---|---|---|
| `FinalizeSetupAction` | `SetupSchoolAction` + `SetupDepartmentAction` + `SetupSuperAdminAction` + optionally `CreateInternshipAction` | Orchestrates the complete finalization: school, department, admin, optional internship, recovery key generation, setup lock |
| `InstallSystemAction` | `SystemProvisioner` tasks | Non-interactive CLI installation: `.env` setup, key generation, migrations, seeding, storage link, cache clear |

### Hybrid Action (Read + Command)

| Action | Read Phase | Command Phase |
|---|---|---|
| `ValidateSetupTokenAction` | Validates token (check expiry, hash_equals) | Consumes token (nullifies in DB with `lockForUpdate()`) |

---

## Domain Invariants

### Super Admin Creation

`SetupSuperAdminAction` accepts exactly two parameters:

```
execute(string $email, string $password): User
```

Name and username are ALWAYS derived from config:
- `config('setup.defaults.admin_name')` — always `"Administrator"`
- `config('setup.defaults.admin_username')` — always `"superadmin"`

These are canonical, non-customizable values enforced at the Action signature level.
Callers cannot pass name or username — the type signature prevents it.

### Token Lifecycle

```
Generated (encrypted, time-limited, stored in DB)
    │
    ├── Expired (no access — must regenerate)
    │     └── setup:reset or setup:install
    │
    └── Presented (in query param or POST body)
          │
          ├── Invalid/Expired → rate limit hit → rejected
          │
          └── Valid
                └── Consumed (nullified in DB via lockForUpdate)
                      └── Session authorized → wizard access granted
                            └── Finalized → setup locked permanently
```

### Setup Lock States

```
Pre-install:
  └── is_installed = false
  └── Setup routes: accessible with valid token
  └── setup:reset: works

Post-install (finalization window):
  └── is_installed = true
  └── setup.completed in session: step 7 accessible (30s window)
  └── Without setup.completed: 404

Post-install (locked):
  └── is_installed = true
  └── Setup routes: 404
  └── setup:reset: blocked (suggests system:health)
  └── setup:install --force: works only in non-production
```

---

## Entities

### SetupState

An immutable readonly entity that represents the current state of the installation.
All methods are pure — no database queries, no side effects.

| Method | Returns | Description |
|---|---|---|
| `isInstalled()` | `bool` | Whether installation has been completed |
| `hasStoredToken()` | `bool` | Whether a token exists in storage |
| `isTokenExpired(?Carbon)` | `bool` | Whether the token has exceeded its expiry |
| `validateToken(string, string, ?Carbon)` | `bool` | Constant-time token comparison using `hash_equals()` |
| `isStepCompleted(string)` | `bool` | Whether a specific wizard step is done |
| `allStepsCompleted()` | `bool` | Whether all required steps are done |
| `updatedAt()` | `?Carbon` | Timestamp of last state change |
| `isWithinFinalizationWindowSeconds(int)` | `bool` | Whether within the post-install display window |
| `hasRecoveryKey()` | `bool` | Whether a recovery key hash exists |

SetupState extends `BaseEntity` and is constructed via `fromModel(Model)`. The
constructor receives all values as primitives — no database access in any method.

---

## Services

### EnvironmentAuditor

Performs a read-only audit of the server environment. All checks are independent and
side-effect-free. Returns an `AuditReport` containing `AuditCheck` results grouped
by category.

| Check Category | Level | Checks |
|---|---|---|
| REQUIREMENTS | FAIL | PHP version ≥ 8.4, required extensions (12), storage permissions, database connectivity |
| RECOMMENDATIONS | WARN | Optional extensions (redis, pcntl, posix), frontend assets built |
| PERMISSIONS | FAIL | Storage and bootstrap/cache writability |
| DATABASE | FAIL | Database connection with configured credentials |
| TERMINAL | WARN | pcntl and posix availability |

The auditor raises no exceptions — every failure is captured as an `AuditCheck`
with FAIL status. The caller decides how to respond.

### SystemProvisioner

Executes provisioning tasks for CLI installation. Each task is independent and
reported separately:

| Task | Command | Side Effect |
|---|---|---|
| `ensure_env` | Copy `.env.example` → `.env` | File creation (0600 permissions) |
| `generate_key` | `key:generate` | `.env` modification |
| `run_migrations` | `migrate --force` | Database schema |
| `run_seeders` | `db:seed --force` | Database data |
| `storage_link` | `storage:link` | Symlink creation |
| `clear_cache` | `config:clear`, `cache:clear`, `route:clear`, `view:clear` | Cache flush |

---

## Middleware

### RequireSetupAccessMiddleware

Applied globally to all web routes via `bootstrap/app.php`. Responsibilities:

1. Check the cached `setup.is_installed` flag
2. If installed: pass through (normal application operation)
3. If not installed: redirect ALL non-setup routes to `/setup`
4. Allow static assets (files in `public/`) and Livewire subrequests to pass through

This middleware ensures that before installation, every page visit redirects to the
setup wizard — there is no way to access any application route before setup completes.

### ProtectSetupRouteMiddleware

Applied only to `routes/web/setup.php`. Responsibilities:

1. Check `is_installed` flag
2. If installed: enforce finalization window or return 404
3. If not installed: check session authorization or validate token
4. Rate-limit token validation attempts (20/60s per IP)
5. On success: authorize the session, forward to wizard
6. On failure: throttle or reject

This middleware is the primary security gate for the setup process.

---

## Events

### SetupFinalized

Dispatched when the setup process completes successfully.

```php
final readonly class SetupFinalized
{
    public function __construct(
        public ?string $schoolId,
        public \DateTimeImmutable $installedAt,
    ) {}
}
```

**Listener:** `LogSetupFinalized` — logs completion via SmartLogger.

The event carries minimal data (schoolId and timestamp) to avoid coupling the event to
domain models. Listeners that need additional context should query the Setup model.

---

## Livewire Components

### SetupWizard

A single Livewire component managing the 7-step installation wizard. Uses 4 Form Objects
for step-specific validation:

| Step | Key | Form Object | Validation |
|---|---|---|---|
| 1 | `welcome` | — | Environment audit results (pass/fail) |
| 2 | `school` | `SchoolForm` | Name, institutional code, email required |
| 3 | `department` | `DepartmentForm` | Name required |
| 4 | `account` | `AdminForm` | Email, password required. Password ≥ 8 chars, mixed case + number |
| 5 | `internship` | `InternshipForm` | Optional — skipped if name empty |
| 6 | `finalize` | — | Two checkboxes: data verified, security aware |
| 7 | `complete` | — | Recovery key display, proceed to login |

**Session persistence:** Form data is saved to the session on every change. If the user
closes and reopens the browser, the wizard resumes from the last incomplete step.

---

## Models

### Setup

A singleton model (single record) that tracks installation state:

| Column | Type | Purpose |
|---|---|---|
| `is_installed` | boolean | Whether setup completed successfully |
| `setup_token` | text, nullable | Encrypted setup token (nullified after use) |
| `token_expires_at` | datetime, nullable | Token expiry timestamp |
| `completed_steps` | JSON array | Wizard steps that have been completed |
| `recovery_key` | text, nullable | Bcrypt hash of the recovery key |
| `school_id` | UUID FK, nullable | Reference to created school |
| `department_id` | UUID FK, nullable | Reference to created department |

The `state()` static method provides read-once access with `lockForUpdate()` to
prevent race conditions during concurrent setup requests. It gracefully degrades when
the setups table does not yet exist (before first migration).

---

## Console Commands

| Command | Signature | Purpose |
|---|---|---|
| `setup:install` | `{--check-only} {--force}` | Full installation or pre-flight audit |
| `setup:reset` | — | Regenerate setup token (pre-install only) |

`setup:install` performs:
1. Environment audit (halt on FAIL if not `--check-only`)
2. System provisioning (`.env`, key, migrations, seeders, storage, cache)
3. Token generation with signed URL output

`--check-only` runs only the audit — no writes, no provisioning.
`--force` allows re-installation in non-production environments (runs `migrate:fresh`).

---

## Policies

### SetupPolicy

Super admin only:

| Method | Access |
|---|---|
| `viewAny`, `view` | super_admin, admin |
| `create`, `update`, `delete` | super_admin only |

Setup management is restricted to prevent unauthorized re-configuration.

---

## Dependency Graph

```
Setup Domain
├── Core            → BaseAction, BaseEntity, BaseState, SmartLogger,
│                      AuditReport, AuditCheck, CacheKeys
├── Auth            → Role enum, AccountStatus enum
├── School          → School, Department models (school/dept creation)
├── User            → User model (admin account creation)
├── Internship      → CreateInternshipAction (optional program creation)
├── Admin           → SendNotificationAction (system notification)
└── Settings        → AppInfo (version display in wizard)
```

Setup has the widest dependency graph of any domain — it touches almost every other
domain to create initial records. This is intentional and unavoidable: installation
must bootstrap the entire system.

---

## What Setup Does NOT Cover

| Excluded | Reason | Handled By |
|---|---|---|
| Ongoing configuration | Runtime changes after install | Settings domain |
| User management | Beyond initial admin | Admin domain |
| School changes | Academic years, departments after setup | School domain |
| Partnerships, placements, etc. | Operational domains | Respective business domains |
| Data migration | Schema changes | Laravel migrations (`database/migrations/`) |
| Multi-tenant provisioning | Single-institution only | Out of scope |

---

## Where to Find It

- `app/Domain/Setup/Actions/FinalizeSetupAction.php` — orchestrated finalization
- `app/Domain/Setup/Actions/SetupSuperAdminAction.php` — super admin creation
- `app/Domain/Setup/Actions/GenerateSetupTokenAction.php` — token generation
- `app/Domain/Setup/Actions/ValidateSetupTokenAction.php` — token validation
- `app/Domain/Setup/Actions/RecoverSuperAdminAction.php` — emergency recovery
- `app/Domain/Setup/Entities/SetupState.php` — immutable installation state
- `app/Domain/Setup/Services/EnvironmentAuditor.php` — pre-install audit
- `app/Domain/Setup/Support/SystemProvisioner.php` — CLI provisioning
- `app/Domain/Setup/Models/Setup.php` — installation state model
- `app/Domain/Setup/Livewire/SetupWizard.php` — 7-step wizard
- `app/Domain/Setup/Http/Middleware/ProtectSetupRouteMiddleware.php` — token gate
- `app/Domain/Setup/Http/Middleware/RequireSetupAccessMiddleware.php` — install redirect
- `config/setup.php` — requirements, defaults, security thresholds
- `routes/web/setup.php` — setup route definitions

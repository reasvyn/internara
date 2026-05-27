# Setup Domain

## Purpose

Setup handles the first-run experience — the one-time installation wizard that runs when the
application is deployed for the first time to a new environment. Its job is to take a blank
database and an unconfigured server and turn them into a fully operational, configured
application. It performs an environment audit (checking PHP version, required extensions,
directory permissions, database connectivity), runs database migrations and seeders, creates a
setup token (gate for wizard access), guides through initial configuration (school, department,
admin account, optional internship program), generates a recovery key, and locks itself
permanently upon completion. All setup functionality is also available via CLI commands for
automated deployments.

## Boundary

**In scope:** Multi-step interactive installation wizard with 7 guided steps (welcome/audit,
school info, department, admin account, optional internship program, finalize/confirm,
complete), server environment requirements audit (PHP version, PHP extensions, filesystem
permissions, database connectivity), database initialization (full migration execution and
foundational data seeding), setup token generation with configurable expiry and encryption,
initial super admin account creation (the first User with super_admin role), school
configuration (institution name, code, address, email, phone, website, principal name),
department creation (name and description), optional internship program initialization,
recovery key generation (hashed+salted, shown once), setup completion lock (permanently
prevents re-running), session-based wizard progress persistence, CLI-based non-interactive
commands (setup:install and setup:reset), recovery admin account creation for emergency
access restoration.

**Out of scope:** Ongoing system configuration after setup (Settings domain handles all
runtime configuration changes), user management beyond the initial admin account (Admin domain
handles ongoing user CRUD), school management after initial setup (School domain manages
departments, academic years, and school profile changes), partnership management (Partnership
domain), any operational business domain logic — registrations, placements, assignments,
attendance, evaluations, certificates, etc., data migration or upgrades for existing
installations (covered by Laravel's standard migration system), multi-tenant or organization
setup beyond single-institution configuration.

## Key Concepts

**Installation Wizard.** A 7-step guided process presented through a clean, branded interface
with session persistence (progress is maintained if the user closes and reopens the browser):
1. WELCOME/ENVIRONMENT CHECK — automated audit of the server environment. Each check produces
PASS, FAIL, or WARNING: PHP version must be 8.4 or higher; required PHP extensions (BCMath,
Ctype, Mbstring, OpenSSL, PDO, Tokenizer, XML, GD, Curl, Intl, Zip) must all be present;
storage/ and bootstrap/cache directories must be writable by the web server; database
connection must be reachable and credentials valid; terminal support (pcntl and posix
extensions). FAIL checks block progression; WARNING checks are advisory. A "Recheck" button
allows re-running the audit after fixing issues.
2. SCHOOL — set up the institution: name, institutional code, email, phone, website, address,
and principal name. Email and institutional code are required.
3. DEPARTMENT — create the first department with name and optional description.
4. ADMIN ACCOUNT — create the first user with super_admin role. Displays the configured
name and username from config defaults (overrides form input to enforce canonical credentials),
requires email, password, and password confirmation. Email is verified automatically on creation.
Password must be at least 8 characters with mixed case and at least one digit.
5. INTERNSHIP (optional) — optionally create an initial internship program with name,
description, start date, and end date. This step can be skipped by leaving the name empty.
6. FINALIZE — confirmation step requiring two checkboxes: data verification and security
awareness acknowledgement. Shows a summary of all entered data. Upon submission, executes all
actions (school creation, department creation, super admin creation, optional internship
creation), generates a recovery key, dispatches SetupFinalized event, sends a system
notification to the new admin, and cleans up session data.
7. COMPLETE — success screen showing the admin's username and email, the recovery key (shown
once, must be saved by the admin), and a "Proceed to Login" button. Clicking the button
boards the admin to the login page — the setup wizard is permanently locked after this step.

**Setup Token.** A cryptographically random token that gates access to the setup wizard route.
The token is generated via CLI (`php artisan setup:install`) or via the `GenerateSetupTokenAction`,
encrypted with Laravel's encryption (Crypt::encryptString), and stored with a configurable
expiration (default 60 minutes). The token can be passed as a query parameter (`?setup_token=...`)
or submitted via the code entry form. **The token is single-use** — after successful validation,
`ValidateSetupTokenAction` immediately nullifies `setup_token` and `token_expires_at` in the
database inside a `lockForUpdate()` transaction, preventing replay attacks. Access is rate-limited
(20 attempts per 60 seconds per IP). The middleware `ProtectSetupRouteMiddleware` (at
`Http/Middleware/`) handles all token validation and session management.

**Environment Audit.** Before any database writes occur, the system performs a comprehensive
audit of the server environment. Each check is independent and self-contained — the auditor
runs all checks, collects all results, and presents them together. FAIL-level checks: PHP
version minimum, required PHP extensions, directory permissions (storage, bootstrap/cache),
database connectivity. WARNING-level checks: optional extensions (redis, pcntl, posix) and
terminal support. Each check result includes the expected value, the actual value, and specific
guidance on how to resolve failures. The audit is designed to be re-runnable — the installer
can fix issues and re-run without side effects.

**Setup Lock (Self-Destruction).** Once the wizard completes successfully, the application is
permanently locked. The setup routes become inaccessible — attempting to access them returns a
404 response. All setup session data (`setup.authorized`, `setup.token`, `setup.form_data`,
`setup.completed`) is force-cleared on any post-install access attempt.

The lock is stored in the database as `is_installed = true` on the Setup model. A short
finalization window (configurable, default **30 seconds**) allows the **complete step only**
(step 7, showing the recovery key) to display after installation before the lock fully engages.
During this window, the `setup.completed` session flag must be present — the old `setup.authorized`
flag is NOT sufficient to bypass the lock. Once the user clicks "Go to Login," the
`setup.completed` flag is cleared, and the window closes immediately.

The lock can only be reset through `php artisan setup:install --force`, which runs
`migrate:fresh` and regenerates a setup token. This requires the environment to be in the
`force_allowed_environments` list (default: `local`, `dev`, `development`, `testing`).
`php artisan setup:reset` only works before installation (`is_installed = false`).

**Recovery Key.** After finalization, a cryptographically random recovery key (default 64
characters) is generated. The plaintext key is shown once on the complete screen and hashed
with Hash::make() before storage. It serves as an emergency credential for the
`RecoverSuperAdminAction` — allowing admin account recovery if all super admin access is lost.

**CLI Commands.** Three Artisan commands manage the setup lifecycle:
- `php artisan setup:install` — performs the full non-interactive installation: audits the
environment, provisions the system (ensures .env, generates app key, runs migrations, runs
seeders, creates storage symlink, clears caches), and generates a setup token. Outputs a
signed URL with the token. The `--force` flag allows re-installation in non-production
environments. The `--check-only` flag runs the environment audit only without provisioning
or token generation — useful for pre-flight verification before committing to installation.
Requires confirmation before proceeding (unless `--force` is set).
- `php artisan setup:reset` — regenerates the setup token. Only works when the system is NOT
yet installed (is_installed = false). Outputs the new signed URL and token. If the system
is already installed, prompts the user to run `php artisan system:health` instead.
- `php artisan system:health` — comprehensive runtime health check that also serves as a
pre-install readiness report. Checks include: environment, PHP version, required extensions,
recommended extensions, PHP memory, database connection, migration status (pending count),
storage writability, disk space, queue, cache, app key, storage link, and maintenance mode.
Also reports whether the system has been set up via the Setup wizard.

## Requirements

### User Stories & Rules

- **Installer:** As an installer, I want to run a guided setup wizard so that I can configure the application for first use
- **Installer:** As an installer, I want an environment audit so that I can verify my server meets requirements
- **Developer:** As a developer, I want to run a pre-flight audit without provisioning so that I can check readiness before committing to installation
- **Developer:** As a developer, I want a single health check command so that I can see system status, setup phase, and pending migrations at a glance
- **Installer:** As an installer, I want to create the school, first department, and admin account in one flow so that the system is ready to use
- **Installer:** As an installer, I want to receive a recovery key at completion so that I can restore admin access if needed
- **Admin:** As an admin, I want to run setup via CLI for automated deployments so that installation is repeatable
- **System:** As the system, I want to permanently lock setup after completion so that no one can reinstall
- Setup can only run once per installation — the setup lock is applied at completion and is
irremovable through the web interface. The lock self-destructs: all setup routes return 404,
all setup session data is cleared.
- The setup token is **single-use**. After successful validation, it is immediately consumed
(nullified in the database) inside a `lockForUpdate()` transaction to prevent replay attacks.
- Environment checks MUST pass before any write operations (database migrations, user
creation) are executed — no writes on a failing environment. The wizard blocks at step 1
if critical checks fail.
- The initial admin account MUST be created with the super_admin role — this guarantees at
least one super_admin exists in the system from the very beginning.
- No default or well-known credentials are ever created under any circumstances; the wizard
requires setting a password with minimum 8 characters.
- The setup token is encrypted at rest, validated with hash_equals to prevent timing attacks,
and **consumed after first successful validation** (single-use, replay-proof).
- The finalization window uses `setup.completed` session flag (set only by `SetupWizard.finish()`
on step 6 submission), NOT `setup.authorized`. This ensures only the complete page (step 7)
is accessible during the window, not the full wizard.
- All setup operations are logged via SmartLogger even though no authenticated user exists.
- The wizard supports session persistence — if closed and reopened, it resumes from the last
incomplete step (form data is saved to the session on every change).
- The recovery key is shown exactly once (on the complete screen) and stored as a bcrypt hash.
There is no way to retrieve it later.
- The `setup:reset` command only works when `is_installed` is false — it cannot bypass the
setup lock. If the system is already installed, it prompts the user to run `system:health`.
- The `setup:install --check-only` flag runs the full environment audit without any database
writes or provisioning — safe to run at any time, even after installation.
- `system:health` is the recommended first command for any developer joining the project —
it provides a comprehensive overview of system readiness including setup phase and migration
status.
- The setup wizard route is protected by rate limiting (20 attempts per 60 seconds per IP)
to prevent brute-force token guessing.
- `Setup::state()` is cached via `CacheKeys::SETUP_INSTALLED` (1h TTL). The cache is invalidated
by `FinalizeSetupAction` and `GenerateSetupTokenAction`.

### Process Flow

```
Setup Wizard Steps:

1. WELCOME / ENVIRONMENT CHECK → 2. SCHOOL INFO → 3. DEPARTMENT
→ 4. ADMIN ACCOUNT → 5. INTERNSHIP (optional) → 6. FINALIZE
→ 7. COMPLETE (setup locked)
```

### Key Operations

| Action | Description |
|--------|-------------|
| `GenerateSetupTokenAction` | Generates an encrypted, time-limited setup token |
| `ValidateSetupTokenAction` | Validates a setup token for wizard access |
| `SetupSchoolAction` | Creates the school record during setup |
| `SetupDepartmentAction` | Creates the first department |
| `SetupSuperAdminAction` | Creates the first super admin account. Accepts only `(string $email, string $password)` — name and username are ALWAYS from config defaults, enforced by type signature |
| `InitializeSuperAdminAction` | Initializes the super admin with proper roles. Name and username always from config defaults |
| `FinalizeSetupAction` | Completes setup, generates recovery key, locks installation |
| `InstallSystemAction` | Non-interactive CLI installation — caches `CacheKeys::SETUP_INSTALLED` via `RequireSetupAccessMiddleware` |
| `RecoverSuperAdminAction` | Emergency super admin account recovery |

### Technical Reference

| Layer | Artifacts |
|-------|-----------|
| **Models** | `Setup` (installation state, token, recovery key; `belongsTo` School and Department) |
| **Entity** | `SetupState` (installation checks, token validation, step completion, finalization window in minutes and seconds) |
| **Enums** | *(none — installation state tracked via `Setup` model boolean fields)* |
| **Core/States** | `BaseState` (abstract readonly base for state entities, extends `BaseEntity`) |
| **Livewire** | `SetupWizard` (7-step guided installation) |
| **Livewire/Forms** | `SchoolForm`, `DepartmentForm`, `AdminForm`, `InternshipForm` |
| **Http/Middleware** | `ProtectSetupRouteMiddleware`, `RequireSetupAccessMiddleware` |
| **Console/Commands** | `SetupInstallCommand`, `SetupResetCommand` |
| **Events** | `SetupFinalized` |
| **Listeners** | `LogSetupFinalized` |
| **Services** | `EnvironmentAuditor` (pre-installation environment validation) |
| **Policies** | `SetupPolicy` |
| **Support** | `SystemProvisioner` (migrations, seeding, storage link) |

## Dependencies

| Dependency | Reason |
|---|---|
| Every domain | Setup runs the complete migration set (all domains) and seeds foundational data |
| Core | BaseAction, BaseEntity, SmartLogger, HandlesActionErrors, AuditReport |
| Auth | Role enum, AccountStatus enum for super admin creation |
| School | School and Department model creation |
| User | User model creation for the admin account |
| Internship | CreateInternshipAction for optional program creation |
| Admin | SendNotificationAction for system notifications |
| Settings | AppInfo support class for version display |



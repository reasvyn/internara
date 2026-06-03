# Setup Domain
> Last updated: 2026-05-31
> **Status:** ✅ **Fully Implemented** — all 26 files in [reference](setup-reference.md) exist


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
| FAIL | Yes | PHP version < 8.4, missing required extensions, unwritable storage, no database |
| WARN | No | Missing optional extensions, no frontend assets built, no terminal support |

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
    │     └── setup:reset-token or setup:install
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
  └── setup:reset-token: works

Post-install (finalization window):
  └── is_installed = true
  └── setup.completed in session: step 7 accessible (30s window)
  └── Without setup.completed: 404

Post-install (locked):
  └── is_installed = true
  └── Setup routes: 404
  └── setup:reset-token: blocked (suggests system:health)
  └── setup:install --force: works only in non-production
```

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

## Domain Boundary

The Setup domain owns the entire first-run installation experience — the one-time, guided process that transforms an empty database and unconfigured server into a fully operational application. It encompasses a multi-step wizard (environment audit, school creation, department setup, super admin account creation, optional program configuration, finalization, and completion), a cryptographically random single-use setup token that gates all wizard access, and a one-time recovery key generated at finalization for emergency super admin recovery. The domain also provides CLI installation and recovery commands with environment check and force-reset capabilities.

Setup does not own any runtime operations. Once installation completes, the domain permanently locks itself — all routes return 404, CLI commands are blocked (except force-reset in development), and the system transitions to normal operation. It does not own ongoing configuration (Settings), user management beyond the initial super admin (Admin), school modifications after setup (School), or any operational domain like Partnership, Internship, or Registration.

The domain delegates user creation to the Auth domain for account lifecycle setup and identity persistence to the User domain. It reads configuration defaults from application config files but does not manage runtime settings. After the super admin is provisioned and the database is seeded, Setup's role ends and all further actions are handled by their respective business domains.

---

## Key Features

- Guide installers through a step-by-step wizard covering environment audit, school creation, admin account setup, and finalization.
- Audit the server environment for PHP version, required extensions, directory permissions, database connectivity, and terminal support.
- Generate a cryptographically random, encrypted, time-limited, single-use token that gates all setup wizard access.
- Create the initial school profile with all institutional details during installation.
- Provision the first super administrator account with canonical name and username derived from configuration.
- Generate a one-time recovery key displayed only at finalization for emergency super admin account recovery.
- Run the full installation process from the command line with environment check and force-reset support.
- Recover the super administrator account via a CLI command when all super admin access has been lost.
- Progress through a visual multi-step wizard with a progress bar and step indicators showing completed, current, and pending stages.
- View a detailed environment audit report with pass, fail, and warning results color-coded for scannability.
- Enter the setup token in a secure input field with clear error messages for invalid or expired tokens.
- Copy the one-time recovery key to clipboard from the completion screen with a prominent copy button.
- Navigate back through wizard steps without losing previously entered data.

# Installation & Provisioning — Feature Specification

> **Spec ID:** 8NZAU
> **Status:** Full
> **Owner:** Setup
> **Depends on:** FB792, SE5Q9

## Description

Defines how a freshly deployed Internara instance goes from an empty directory to an operational system: environment audit, database provisioning, and the setup token that bridges CLI provisioning to the browser wizard. The wizard itself belongs to [VEJCX-setup-wizard.md](VEJCX-setup-wizard.md); emergency access after install belongs to [C9ZB6-recovery-ecosystem.md](C9ZB6-recovery-ecosystem.md).

---

## 1. Problem Statements

### PS-1 — First-Boot Experience

A freshly deployed Internara instance has no database schema, no seed data, and no administrator account. The system must guide an installer through environment validation, database provisioning, and initial configuration without requiring manual SQL or config file editing.
**→ Requirement:** FR-INST-015 (one-command install), FR-INST-006 (migration-driven schema).

### PS-2 — Single-Execution Guarantee

Setup must execute exactly once. Running installation on an already-configured system must not corrupt data, create duplicate accounts, or reset configuration.
**→ Requirement:** FR-INST-014 (idempotent re-runs), FR-INST-016 (installed-system guard).

### PS-3 — Environment Readiness

Different servers have different PHP versions, extensions, directory permissions, and database configurations. The system must detect and report incompatibilities before attempting provisioning to prevent partial or failed installations.
**→ Requirement:** FR-INST-001/002/003 (audit gates), FR-INST-004 (categorized, re-runnable audit).

### PS-4 — Deployment Flexibility

The installer may be school IT staff on shared hosting, a developer in Docker, or a sysadmin over SSH. The system must support a CLI-first path whose generated token bridges into the browser wizard.
**→ Requirement:** FR-INST-015 (CLI contract), FR-INST-011/012 (token bridge), FR-INST-009 (zero-service Tier-1 defaults).

### PS-5 — Recovery Key Lifecycle

After setup, the super admin may lose access through a forgotten password or a locked account, on hosting where SMTP was never configured. A recovery mechanism must exist that depends on neither email nor the web UI.
**→ Requirement:** FR-INST-019 (recovery bridge), FR-INST-023 (backup-capturable state).

---

## 2. Goals & Non-Goals

### Goals

- **Zero-to-operational provisioning in one command** — audit, schema, seeds, and token from `setup:install`. *Why:* school IT staff get exactly one shot at first boot; a checklist of manual steps is where installs die.
- **Actionable audit before any write** — every failure names its remediation. *Why:* a red wall of missing extensions without guidance sends a non-technical installer back to WhatsApp support.
- **Idempotent setup** — running install twice causes no harm. *Why:* installers retry; the second run must be a no-op, not a second super admin.
- **CLI-to-browser bridge via token** — provisioning ends with a signed URL the wizard consumes. *Why:* the person with SSH access and the person filling school data are often two different people.
- **Tier-1 zero-service defaults** — a fresh install runs on MySQL/MariaDB plus file cache and sync queue, nothing else. *Why:* per the [self-hosted single-tenant ADR](../adr/adr-self-hosted-single-tenant.md), every feature works in the default configuration.
- **Auditable install flows** — every setup action logs through SmartLogger with PII masked. *Why:* first boot writes the most sensitive rows the system will ever hold.

### Non-Goals

- **Multi-tenant provisioning**. *Why:* single-tenant by product definition; one school, one instance.
- **Remote or SSH-driven installation automation**. *Why:* the installer runs the CLI directly where the code lives.
- **Web-server configuration (Apache/Nginx)**. *Why:* host-specific; documented in guides, not code.
- **Migration between hosting environments**. *Why:* standard backup and restore covers moves; see [HBXCI-backup-system.md](HBXCI-backup-system.md).
- **Web-based database selection**. *Why:* SQLite by default for dev, MySQL/MariaDB via `.env` for production — no picker needed.
- **Self-update during setup**. *Why:* updates are a post-install operation.
- **One-time-password verification during install or recovery**. *Why:* shared hosting frequently lacks SMTP, which would brick emergency access; deferred post-MVP (see §10 R-1).

---

## 3. User Stories / Use Cases

One table holds every use case; the groups below (§3.1–§3.2) carry the free-form detail for each row.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-INST-001 | Sysadmin provisions a fresh instance with `setup:install` and receives a signed wizard URL | P0 | F | Full |
| UC-INST-002 | Installer runs the audit alone with `--check-only` and fixes the environment before provisioning | P0 | F | Full |
| UC-INST-003 | Sysadmin invalidates a lost setup token with `setup:reset-token` and continues in the browser | P0 | F | Full |
| UC-INST-004 | Locked-out school regains super admin access through the CLI recovery path | P0 | F | Full |
| UC-INST-005 | Developer or presenter provisions an instance pre-loaded with the demo dataset | P1 | F | Full |

### 3.1 Provisioning Journeys

#### UC-INST-001 — Provision a Fresh Instance

The evening before term starts, the school's IT teacher opens SSH on a $5 shared host, clones the release, and runs a single command. The screen fills with grouped checks — PHP version, extensions, permissions, database — each passing in turn, then provisioning scrolls past: environment file, application key, migrations, seeds, symlink, cache clearing. Ninety seconds later a signed URL appears. The teacher copies it into a WhatsApp message to the vice principal, who opens it on a laptop and meets the welcome step of the wizard. Nobody edited SQL, nobody hand-wrote config, and the whole handoff between the two people was one URL with a one-hour life.

#### UC-INST-002 — Audit Before Committing

A school technician inherits a five-year-old cPanel account and suspects nothing on it is current. Before touching the database, they run the install command in check-only mode. The audit reports PHP 8.1 against a required 8.4 floor, two missing extensions, and a non-writable storage path — each with the exact remediation beside it. They upgrade PHP, enable the extensions, fix permissions, and re-run the same command until every gate is green. Only then do they provision. The value of the dry run is what it prevented: a migration half-applied against the wrong PHP minor, discovered at midnight with students arriving in the morning.

### 3.2 Token and Recovery Journeys

#### UC-INST-003 — Replace a Lost Token

The vice principal never opened the WhatsApp message, and the hour expired. Nothing is broken and nothing needs reinstalling: the sysadmin runs the reset command, the stored token version increments so the dead link stays dead, and a fresh signed URL appears. The old token cannot be replayed from browser history because validation already cleared it and the version moved on. This is deliberately a three-second operation — token loss during a busy setup day should feel like a paper cut, not a reinstall.

#### UC-INST-004 — Regain a Locked-Out School

Mid-semester, the only super admin forgets the password, and the school never configured SMTP, so email recovery is fiction. The principal calls the district technician, who logs in over SSH and runs the recovery command. The system reads the recovery key from its private file, verifies it against the stored hash, and prompts for the account email and a new password with a deliberate re-type confirmation. Roles re-sync, sessions rotate, and a fresh recovery key is issued because the used one is now potentially exposed. The school is unlocked in under a minute, and the full choreography lives in [C9ZB6-recovery-ecosystem.md](C9ZB6-recovery-ecosystem.md).

#### UC-INST-005 — Provision a Demo Instance

A teacher-training workshop needs thirty working instances by 8 a.m., each believable enough to click through. The organizer appends the demo flag to the same install command and gets provisioning plus the full dummy dataset in one pass — departments, companies, placements, journals — instead of a second seeding step per machine. The flag is explicit and never default, because a production school must never wake up with demo accounts holding known credentials.

---

## 4. Functional Requirements

One table holds every functional requirement; each row's detail lives under its group (§4.1–§4.5). `Layer F` rows are verified against a real database; `Layer A` rows by scan or review.

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-INST-001 | Audit gates on PHP >= 8.4.0 and the 12 required extensions before any provisioning write | P0 | F | Full |
| FR-INST-002 | Advisory checks (recommended extensions, terminal capability, Vite manifest) warn without blocking | P1 | F | Full |
| FR-INST-003 | Audit gates on directory permissions and database connectivity | P0 | F | Full |
| FR-INST-004 | Audit results are categorized with per-check detail, remediation text, and are re-runnable | P0 | F | Full |
| FR-INST-005 | Provisioning creates `.env` (0600) with a generated APP_KEY and flags placeholder values | P0 | F | Full |
| FR-INST-006 | Schema is created exclusively by migrations; `--force` re-provisions via `migrate:fresh` | P0 | F | Full |
| FR-INST-007 | Provisioning seeds roles, default settings, and the academic year baseline | P0 | F | Full |
| FR-INST-008 | Provisioning finishes with storage symlink, cache clearing, and module discovery | P0 | F | Full |
| FR-INST-009 | A fresh install runs on Tier-1 zero-service defaults with no external dependency | P0 | F | Full |
| FR-INST-010 | Provisioning is atomic; any failure rolls back and surfaces a `RejectedException` | P0 | F | Full |
| FR-INST-011 | Setup token is a 64-character random string, encrypted at rest, expiring after 60 minutes | P0 | F | Full |
| FR-INST-012 | Token is single-use with versioned invalidation of stale sessions | P0 | F | Full |
| FR-INST-013 | Token validation is rate-limited at 20 attempts per IP per 60 seconds with throttled logging | P0 | F | Full |
| FR-INST-014 | Token generation is lock-guarded and safe to re-run without duplicating state | P0 | F | Full |
| FR-INST-015 | `setup:install` provisions the system, generates the token, and prints the signed wizard URL | P0 | F | Full |
| FR-INST-016 | `--force` and re-installation on an installed system are rejected outside dev environments | P0 | F | Full |
| FR-INST-017 | `--optimize` caches config, routes, views, and events for production first requests | P1 | F | Full |
| FR-INST-018 | `--with-dummy` seeds the demo dataset after provisioning, only when explicitly passed | P2 | F | Full |
| FR-INST-019 | `setup:reset-token` rotates the token and `admin:recover` bridges to emergency access | P0 | F | Full |
| FR-INST-020 | Uninstalled instances redirect to `/setup`; setup routes are token-gated with asset passthrough | P0 | F | Full |
| FR-INST-021 | Token validation establishes a versioned session authorization with a regenerated session ID | P0 | F | Full |
| FR-INST-022 | Installed systems return 404 on setup routes except inside the post-finalization window | P0 | F | Full |
| FR-INST-023 | A provisioned instance is left in a backup-capturable state supporting the RPO/RTO basis | P1 | F | Full |

### 4.1 Environment Audit

#### FR-INST-001 — Platform Gates

A migration that starts on PHP 8.1 and dies on a missing `intl` function leaves a schema half-built and an installer who no longer trusts the tool. So the audit refuses to write anything until the platform floor holds: PHP at or above 8.4.0 and the twelve required extensions — bcmath, ctype, fileinfo, mbstring, openssl, pdo, tokenizer, xml, curl, gd, intl, zip — every one present. These are gates, not suggestions; a single missing item stops provisioning with the remediation beside it. The check reads from `config/setup.php`, so the day the floor moves, one config edit moves it everywhere.

#### FR-INST-002 — Advisory Checks That Warn

Earlier installers treated every check as fatal, which meant a headless cron container without `posix_isatty` could never provision even though nothing in the install needs an interactive terminal. The advisory tier exists because of that over-blocking: recommended extensions (redis, pcntl, posix), terminal animation capability, and the Vite manifest presence all report as warnings. The manifest check deserves a note — a missing `public/build/manifest.json` means assets were never compiled, and the wizard would render unstyled; warning here saves a confused phone call later, without stopping a CLI-only provisioning run that will build assets afterwards.

#### FR-INST-003 — Permissions and Connectivity

Shared hosting is where this check earns its keep. Storage and the bootstrap cache must be writable by the web user, and the configured database must answer — not in theory, with a real connection attempt. An SMK technician once provisioned against a database host that resolved but refused authentication, and the failure surfaced eleven steps later as a cryptic query error. Now the audit fails fast at the connectivity gate, naming the host it tried. Permissions and connectivity are the two gates most likely to fail on real school servers, so they report first and loudest.

#### FR-INST-004 — Categorized, Re-Runnable Results

The audit speaks in five categories — requirements, permissions, database, terminal, recommendations — because a flat list of twenty checks is unreadable on a phone screen over SSH. Each check carries its own detail line and, on failure, the concrete fix rather than a shrug. And the whole audit re-runs on demand, from the CLI flag or the wizard's Recheck button, so fixing one item and re-verifying is a loop of seconds. An audit you can only run once is a snapshot; an audit you can re-run is a tool.

### 4.2 Provisioning and Schema

#### FR-INST-005 — Environment File and Key

When no `.env` exists, provisioning copies the example, locks it to owner-only permissions, and generates the application key if empty. Then it does something the original installer never did: it scans for placeholder values left behind — a localhost URL, empty database password, template mail credentials — and warns before they become production mysteries. Password-reset links built on `http://localhost` are the classic symptom; this warning at birth prevents that entire class of support ticket.

#### FR-INST-006 — Migrations Own the Schema

Nobody hand-writes schema for this system, ever. Every table arrives through migrations, and the destructive path — `migrate:fresh` behind `--force` — exists only for development re-provisioning. The reason is forensic as much as practical: six months later, `migrate:status` tells the exact story of how the database came to be, which no amount of `CREATE TABLE` pasted into a terminal can do. Foreign keys use `foreignUuid()->constrained()`, primary keys are UUID v7 from `BaseModel`, and the migration history is the schema's changelog.

#### FR-INST-007 — Seeds That Make a System

An empty schema is not an installed system. Provisioning seeds the Spatie roles, the default settings rows every helper expects, and the academic-year baseline the rest of the domain hangs from. Order matters here — settings before anything that reads them, roles before anything that assigns them — and the seeder sequence encodes that order so installers never think about it. A fresh instance answers `setting()` and resolves roles correctly from the first request, because the seeds ran as part of birth, not as an afterthought.

#### FR-INST-008 — Finishing Moves

The last mile of provisioning is unglamorous and load-bearing: the storage symlink so uploaded files resolve, full cache clearing so no stale config shadows the new environment, and module discovery so routes, Livewire components, and policies register. Skipping the cache clear once caused a memorable incident where the setup pages served pre-install route state and the wizard 404'd on a system that was, by every other measure, installed. Discovery itself is governed by [I1BCV-module-discovery.md](I1BCV-module-discovery.md); this row is only the integration point that guarantees it runs.

#### FR-INST-009 — Zero-Service Tier-1 Defaults

The same binary serves a 400-student school on shared hosting and an 1,800-student school on a VPS — that promise from the [performance ADR](../adr/adr-performance-optimization.md) starts here, at install. Out of the box the instance uses MySQL/MariaDB (SQLite for dev), file cache, sync queue, database sessions, and local disk. No Redis to provision, no daemon to supervise, no object storage to pay for. Growth later is a configuration swap, never a reinstall, because every framework call already goes through the drivers. An installer on $5 hosting should never meet a screen demanding infrastructure they do not have.

#### FR-INST-010 — Atomic Provisioning

A power cut halfway through provisioning must not leave a school with tables but no roles, or settings but no admin. The provisioning sequence runs transactionally where the store allows, and any failure rolls the whole attempt back while the command surfaces a `RejectedException` carrying a translatable explanation — never a raw stack trace, never a half-born system. The installer fixes the cause and re-runs against a clean slate, because the failed run left no debris behind.

### 4.3 Setup Token

#### FR-INST-011 — Token Birth and Death

The bridge between the CLI and the browser is a 64-character cryptographic random string, encrypted before it ever touches the settings table, with a sixty-minute life configurable in `config/setup.php`. Sixty minutes is a human number: long enough for the SSH person to message the URL to the form-filling person, short enough that a leaked chat log has a brief half-life. Encryption at rest means a database dump alone never yields a live token.

#### FR-INST-012 — Single Use With Versioning

The moment a token validates, it is cleared — replaying it from browser history buys nothing. And every generation bumps a version counter that authorized sessions carry, so when an admin rotates the token, every session minted from the old one silently stops working. This pairing quietly kills an entire class of replay bugs: without versioning, a rotated token would leave ghost sessions authorized forever.

#### FR-INST-013 — Throttled Guessing

Twenty validation attempts per IP per minute is generous to a human mistyping from a printed URL and brutal to a script guessing a 64-character secret. Past the limit the client is throttled, and every failure is logged — not with the attempted token, which would turn the log into an oracle, but with the fact of the attempt. The log line exists for the morning-after forensics, when someone asks whether that burst of 404s at 3 a.m. was an attack.

#### FR-INST-014 — Lock-Guarded, Re-Runnable Generation

Two admins provisioning side by side — one over SSH, one in a deploy script — must not mint competing tokens where the second silently kills the first's wizard session. Generation holds a short cache lock, so concurrent attempts serialize instead of interleaving. And re-running generation on an uninstalled system is safe by construction: same state shape, bumped version, exactly one live token. Idempotency here is what lets installers retry without fear.

### 4.4 CLI Contract

#### FR-INST-015 — The One Command

`setup:install` is the entire provisioning story in one verb: audit, provision, token, signed URL on screen. An optional `--url` pins the application URL used to build that link, for hosts where the default would otherwise bake in `localhost`. Everything else in this section is a flag on this command, because an installer should learn one command and discover the rest through `--help`, not through documentation archaeology.

#### FR-INST-016 — Guards Around Destruction

`--force` runs `migrate:fresh`, which is a polite way of saying it destroys all data — so outside local, dev, development, and testing environments the flag is refused outright. Symmetrically, running install on an already-installed system fails unless `--force` is given, and that refusal arrives as a `RejectedException` with a message a human can act on. These two guards are the reason no tired admin has ever wiped a production semester at 11 p.m.

#### FR-INST-017 — Opt-In Production Caching

Passing `--optimize` caches configuration, routes, views, and events during install, buying roughly twice-as-fast first requests on production. It stays opt-in because cached config in development is a trap: `.env` edits silently stop taking effect and the developer blames the framework. Production deployments pass the flag; developers never think about it. After caching, container and facade instances rebind so nothing serves stale references — an implementation detail with real symptoms when skipped.

#### FR-INST-018 — Demo Data on Request

The `--with-dummy` flag seeds the full demo dataset through `DummySeeder` immediately after provisioning, in any environment, but only when explicitly passed — it never fires by itself and never fails an otherwise successful install. A provisioned demo that errors on seeding still counts as installed; the demo is garnish, not foundation. Ownership of the dataset shape lives in [3UOZP-dummy-data.md](3UOZP-dummy-data.md).

#### FR-INST-019 — Token Rotation and Recovery Bridge

`setup:reset-token` mints a fresh token and invalidates the old one for systems still mid-setup, while `admin:recover` is the doorway to the emergency access flow owned by [C9ZB6-recovery-ecosystem.md](C9ZB6-recovery-ecosystem.md). Keeping both commands in this spec is deliberate: token lifecycle and recovery entry are installation concerns even though recovery's choreography lives elsewhere. An installer reading this file learns every CLI verb that can touch a system before it has users.

### 4.5 Access Gating and Lifecycle

#### FR-INST-020 — Redirect and Token Gate

Before installation completes, every non-setup web route redirects to `/setup` — a visitor to a half-born system meets the installer, never a stack trace. Setup routes themselves sit behind token validation, while real files under `public/` and Livewire update requests pass through untouched, so styling and interactivity survive the gate. The setup route is exempt from CSRF verification precisely because token-based authorization replaces it there; the token is the credential, checked per request until the session takes over.

#### FR-INST-021 — Session Authorization

Once the token validates, the session carries the authorization — a flag plus the token version — instead of re-validating the secret on every request, and the session identifier regenerates at that moment to cut off fixation. From here on the wizard steps read session state, not the token, which is why the token could be single-use without breaking multi-step navigation. The version binding is the quiet part: rotate the token elsewhere and this session's authorization silently stops matching.

#### FR-INST-022 — Post-Install Lockout

After finalization the setup routes must disappear — an installed system answers 404 on `/setup`, with exactly one exception: a short post-finalization window (thirty seconds, configurable) so the installer can still copy the recovery key from the completion screen. Outside that window the session's setup state is purged and the route is gone for good. A setup page reachable on a live school system would be a standing invitation to re-provision; this row is what revokes it.

#### FR-INST-023 — Backup-Capturable From Birth

Installation is also the moment the system becomes worth backing up, so provisioning leaves everything the backup story needs: settings rows the backup policy captures, the recovery-key hash persisted (not just displayed), and storage paths in their canonical locations. This row is the installation side of the RPO/RTO contract owned by [HBXCI-backup-system.md](HBXCI-backup-system.md) — a provisioned instance must be restorable within the same targets from its first day, not eventually.

---

## 5. Non-Functional Requirements

One table holds every non-functional constraint; `Target` carries the concrete number or SLO, or `N/A` where enforcement is architectural and verified by scan or test.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-INST-001 | Setup token is cryptographically random, encrypted at rest, and single-use | 64 chars, encrypted | P0 | F | Full |
| NFR-INST-002 | Session identifier regenerates on token validation; validation throttled per IP | 20 attempts / 60 s | P0 | F | Full |
| NFR-INST-003 | Secrets on disk (`.env`, recovery-key file) are owner-only | 0600 | P0 | F | Full |
| NFR-INST-004 | Super admin credentials meet password rules and the account is PROTECTED | 8+ chars, mixed case, numbers | P0 | F | Full |
| NFR-INST-005 | Destructive flags are refused outside development environments | local/dev/testing only | P0 | F | Full |
| NFR-INST-006 | Every setup action logs through SmartLogger with PII masked before any sink | 100% of setup actions | P0 | F | Full |
| NFR-INST-007 | Provisioning rolls back on failure; concurrent token generation serializes | 10 s lock, 15 s block wait | P0 | F | Full |
| NFR-INST-008 | All installer-facing strings are translatable at runtime | en + id, 100% via `__()` | P1 | F | Full |
| NFR-INST-009 | Setup state lives in the shared settings table with no dedicated migration | 0 setup migrations | P1 | A | Full |
| NFR-INST-010 | Setup code follows the Action Triad with a pure readonly entity, covered by Pest | 0 triad violations | P0 | A | Full |
| NFR-INST-011 | CLI provisioning completes quickly on school hardware | < 30 s | P1 | F | Full |
| NFR-INST-012 | Default install requires zero external services beyond the database | 0 external services | P0 | A | Full |

### 5.1 Secrets and Access

#### NFR-INST-001 — Token Secrecy Properties

Randomness, encryption, and single-use are three independent properties and the token needs all three the way a door needs a lock, a frame, and hinges. Randomness defeats guessing, encryption defeats database theft, single-use defeats replay — remove any one and the other two stop mattering. Sixty-four characters from the cryptographic generator is the concrete floor; anything shorter would be negotiating with brute force.

#### NFR-INST-002 — Session and Throttle Discipline

Session fixation is an old attack that still works against installers who click links over shared networks, so the identifier regenerates the instant the token validates. The throttle beside it — twenty attempts per IP per minute — exists for the opposite threat: automation probing the setup endpoint. Together they cover the human and the script, which is the whole threat model of a publicly reachable setup page.

#### NFR-INST-003 — Owner-Only Secrets

The environment file holds database credentials and the application key; the recovery-key file holds the keys to the kingdom in plaintext. Both ship at mode 0600 because shared hosting is multi-user by nature and world-readable secrets there are public secrets. This is enforced, not advised — provisioning sets the mode itself rather than hoping the umask cooperates.

#### NFR-INST-004 — First Account Hardening

The first account is the most privileged and the least supervised, created in a hurry on setup day. Laravel's password rules (length, mixed case, numbers) apply without exception, and the account lands in PROTECTED status — non-deletable, non-lockable — so no later admin action can accidentally orphan the system. A school with zero working super admins is a school filing paper again.

#### NFR-INST-005 — Destruction Stays in Dev

The `--force` restriction is a guardrail, not a vault: environment names can be lied about, and operations discipline remains the real protection. But guardrails still stop the common accident — the admin who aliases the wrong host and wipes a semester. Refusing destructive flags outside development environments converts a catastrophe into an error message, which is an excellent trade.

#### NFR-INST-006 — Logged and Masked

Setup writes emails, passwords-adjacent material, and tokens within minutes of first boot, so every setup action routes through SmartLogger with PII masking applied before either channel — system log or activity table — ever sees the payload. Passwords, tokens, and secrets mask fully; emails and names partially. Per the [SmartLogger ADR](../adr/adr-smartlogger-dual-channel.md), activity-channel failure degrades to the system log rather than silently dropping the audit. The install you cannot audit is an install you cannot trust.

### 5.2 Reliability and Operations

#### NFR-INST-007 — Rollback and Serialization

Two numbers govern the worst minutes of an install: the failed provision that must leave nothing behind, and the double-generated token that must not fork. Rollback covers the first — the transaction boundary is the difference between retrying and rebuilding. The cache lock covers the second, held ten seconds with a fifteen-second block wait, so the losing process pauses instead of racing. Neither number is tunable decoration; both were chosen so concurrent deploys serialize and failed deploys vanish.

#### NFR-INST-008 — Bilingual From the First Screen

The installer may be an English-reading developer or an Indonesian-reading school technician, and both meet this system before any locale preference exists. Every installer-facing string passes through the translation helper with mirrored keys in both language files, enforced by the same D3 scan that guards the rest of the application. Setup day is the worst possible moment to present someone with a language they cannot read.

#### NFR-INST-011 — Thirty-Second Provisioning

From command to token URL in under thirty seconds on ordinary school hardware — the target is about respect for the installer's context, which is usually a borrowed laptop and a narrow maintenance window. Anything slower invites interruption, and an interrupted provision is how half-built systems happen. The demo dataset is excluded from this budget; garnish may take its time.

#### NFR-INST-012 — Nothing Else to Buy

Zero external services in the default configuration is a procurement property disguised as a technical one: no Redis invoice, no mail provider, no storage bucket required before the first student registers. The deployment matrix in the [self-hosted ADR](../adr/adr-self-hosted-single-tenant.md) is the authority; this row is its installation-side enforcement. A feature that silently needs Redis at install time is a bug against this row.

### 5.3 Structural Conventions

#### NFR-INST-009 — Settings Table, No New Migration

Setup state — installed flag, token, version, completed steps, recovery hash — lives as grouped rows in the shared settings table, adding zero migrations to a one-time event. A dedicated table for data that becomes read-only history after day one would be schema vanity. The settings infrastructure's typing and caching already cover everything setup needs.

#### NFR-INST-010 — Triad Shape, Tested

Setup behavior follows the Action Triad — commands for mutation, reads for queries — with the setup entity as a pure readonly object carrying no I/O, and the Pest suite covers the behavior end to end. The one structural liberty is documented, not hidden: the install command orchestrates audit, provisioning, and token steps inline rather than through a process action (see DD-INST-009), because no second caller ever needed that orchestration extracted.

---

## 6. API / Data Contracts

Non-negotiable precision — precise enough to implement against without asking.

### 6.1 Settings Keys

All setup state is stored in the `settings` table with `group = 'setup'`:

| Key | Type | Description |
|-----|------|-------------|
| `setup.is_installed` | boolean | Master flag — `true` after finalization |
| `setup.install_token` | string | Encrypted token (null after use) |
| `setup.token_expires_at` | datetime | Token expiry timestamp (null after use) |
| `setup.token_version` | integer | Increments on each generation |
| `setup.completed_steps` | JSON | Array of completed wizard step keys |
| `setup.install_recovery_key` | string | Hashed recovery key (bcrypt) |
| `setup.updated_at` | datetime | Last setup state modification |

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

### 6.5 Events

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

### 6.6 Config

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

One table holds every design decision; detail prose below states each decision with its history and accepted cost.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-INST-001 | Setup state lives in the shared settings table, not a dedicated table | P1 | — | — |
| DD-INST-002 | Wizard access uses an encrypted single-use token with session versioning | P0 | — | — |
| DD-INST-003 | Super admin name and username are immutable config values | P0 | — | — |
| DD-INST-004 | Recovery key is stored twice: bcrypt hash in settings, plaintext in a private file | P0 | — | — |
| DD-INST-005 | Destructive install flags are confined to development environments | P0 | — | — |
| DD-INST-006 | Production caching during install is opt-in via `--optimize` | P1 | — | — |
| DD-INST-007 | Localhost-style URLs trigger an informational warning, never a block | P2 | — | — |
| DD-INST-008 | Demo seeding is an explicit flag, never automatic | P1 | — | — |
| DD-INST-009 | Install orchestration lives inline in the command, not in a process action | P1 | — | — |

### 7.1 State and Access

#### DD-INST-001 — Settings Table Instead of a Setup Table

A dedicated setup-states table was the obvious design for exactly one afternoon — then someone asked what it would hold after day one, and the answer was read-only history nobody queries. The shared settings table already offered grouping, typing, and caching, so setup state became `setup.*` rows and the migration count stayed at zero. The accepted cost is softer typing on the completed-steps JSON, which is a fair price for one less table guarding a one-time event.

#### DD-INST-002 — Token With a Shelf Life

Signed URLs were considered and dropped: they die on server restart, they cannot be dictated over the phone to a school with one laptop, and they leave no version to invalidate. A random token survives restarts, travels through any channel including paper, and pairs naturally with the version counter that kills stale sessions on rotation. The price is CLI dependence for minting — acceptable, because every install begins with someone at a terminal anyway.

#### DD-INST-003 — The Unchangeable Super Admin

Letting the installer pick the super admin's name and username felt friendly until authorization, recovery, and audit code all started special-casing configurable values. Hardcoding both from config removed a whole family of edge cases: the recovery command always knows whom to restore, policies always know whom to exempt, and no rename can orphan the audit trail. The singleton is the system's fixed point, and fixed points should not wobble.

#### DD-INST-004 — Two Copies of the Last Resort

The recovery key's dual storage answers the chicken-and-egg at the heart of lockout: the database needs a running application, but the application needs an admin. The private file breaks the cycle — readable over SSH with no application at all — while the bcrypt hash in settings enables cryptographic verification without storing the secret where dumps can find it. If the two ever disagree after regeneration, the on-screen key wins and the administrator re-saves; displaying the fresh key regardless of file outcome is what makes that disagreement survivable.

### 7.2 Safety and Operations

#### DD-INST-005 — Force Fenced Into Dev

`migrate:fresh` in production is not a feature, it is an incident with a flag, so the flag refuses to run there. The fence reads the environment name, which a determined operator can spoof — this is openly a guardrail against accidents, not a vault against malice. Real protection remains operational discipline; the fence just makes sure the accident requires deliberate lying first.

#### DD-INST-006 — Caching as an Explicit Choice

Caching config, routes, views, and events during install roughly halves bootstrap time on first requests, which matters on shared hosting — but cached config in development turns every `.env` edit into a mystery. Making it opt-in keeps both worlds sane: production passes the flag once, developers never learn it exists. The container and facade rebinding after caching is the kind of detail that only gets documented because someone once debugged it for a day.

#### DD-INST-007 — The Localhost Warning

The example environment ships with a localhost URL, and password-reset links built on it fail silently in production — the user clicks, nothing resolves, nobody understands why. Detecting localhost-style values and warning is heuristic string matching, not authority, and a Docker health check on localhost will see a false positive. That is fine: the warning informs without blocking, and the one admin it saves from broken reset links pays for a hundred false positives.

#### DD-INST-008 — Demo Data Only on Request

A fresh install with demo accounts is a gift to workshops and a landmine for production, so the seeder runs only behind an explicit flag and never by default. The administrator who passes it opts in knowingly, demo credentials are documented rather than surprising, and cleanup before go-live is their responsibility. Automation that guesses whether you wanted fake users is automation that will eventually guess wrong.

#### DD-INST-009 — Orchestration Stays in the Command

A process action orchestrating audit, provisioning, and token generation was specified, implemented nowhere, and deleted as dead code. The command composes the auditor, the provisioner, and the token action directly, and that is where the sequence lives until a second caller appears. Extracting an abstraction for one caller is how codebases collect beautiful, unused architecture; if reuse ever demands it, the extraction starts from the command's handler, not from a resurrected ghost.

---

## 8. Success Metrics

| Metric | Target | How to measure |
|--------|--------|---------------|
| Fresh install to token URL | < 30 s CLI wall time | Timed run on reference shared host |
| Token replay blocked | 100% of second validations rejected | Re-validate a consumed token in tests |
| `--force` refused outside dev | 100% of production attempts rejected | Invoke with production environment set |
| Audit catches platform mismatch | PHP < 8.4 and each missing extension flagged | Matrix run against staged bad environments |
| Setup routes unreachable after install | 404 outside the finalization window | HTTP assertions post-finalization |
| Install auditable | Every setup action has a masked log entry | Log review of a full install run |

---

## 9. Roadmap

### Prerequisites

This spec builds after its dependencies are complete:

| Spec | What It Provides |
|------|-----------------|
| [FB792-tech-stack.md](FB792-tech-stack.md) | Pinned platform versions the audit enforces |
| [SE5Q9-base-classes.md](SE5Q9-base-classes.md) | `BaseCommandAction`, entity and exception contracts |

### Build Guide

After this spec, the system provisions itself from zero: audited environment, migrated schema, seeded roles and settings, and a setup token bridging CLI to browser. The database exists, the routes are gated, and the wizard has everything it needs to take over.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [VEJCX-setup-wizard.md](VEJCX-setup-wizard.md) | Consumes the setup token; reads `setup.is_installed`; walks the six steps |
| 2 | [C9ZB6-recovery-ecosystem.md](C9ZB6-recovery-ecosystem.md) | Verifies against the recovery-key hash stored at finalization |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
|----|----------------------------------|--------|-------|----------|
| R-1 | Production recovery without OTP relies on SSH trust alone; if SMTP becomes universally available, revisit second-factor recovery | Open | Maintainer | — |
| R-2 | The setup route's CSRF exemption is safe only while token validation stays the sole credential there; any additional setup endpoint must re-prove this | Open | Maintainer | — |
| A-1 | We assume the installer has shell access to the host; fully panel-only hosting without terminal access cannot run the CLI path | Accepted | Maintainer | — |
| A-2 | We assume `config/setup.php` remains the single source for audit thresholds; no check reads hardcoded values elsewhere | Accepted | Maintainer | — |

---

## Quick References

- [Setup wizard](VEJCX-setup-wizard.md) — the six-step browser flow this spec hands off to
- [Recovery ecosystem](C9ZB6-recovery-ecosystem.md) — emergency access via the recovery key
- [Module discovery](I1BCV-module-discovery.md) — owns discovery semantics; this spec only invokes it
- [Dummy data](3UOZP-dummy-data.md) — owns the `--with-dummy` dataset shape
- [Backup system](HBXCI-backup-system.md) — owns the RPO/RTO targets this spec prepares for
- [ADR: Self-hosted single-tenant](../adr/adr-self-hosted-single-tenant.md) — Tier-1 defaults rationale
- [ADR: Performance optimization](../adr/adr-performance-optimization.md) — no-regret tiers and `.env`-only growth
- [ADR: SmartLogger dual-channel](../adr/adr-smartlogger-dual-channel.md) — audit channel and PII masking
- [ADR: Exception hierarchy](../adr/adr-exception-hierarchy.md) — `RejectedException` for failed preconditions
- [ADR: UUID primary keys](../adr/adr-uuid-primary-keys.md) — key strategy migrations must follow
- [ADR: Base class mandate](../adr/adr-base-class-mandate.md) — triad and entity shape requirements
- [Spec registry](index.md) — all specs grouped in 12 phases

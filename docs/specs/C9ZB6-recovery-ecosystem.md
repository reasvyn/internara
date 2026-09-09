# Recovery Ecosystem — Super Admin Emergency Access

> **Spec ID:** C9ZB6
> **Status:** Full
> **Owner:** Setup
> **Depends on:** FB792, 8NZAU

## Description

Defines the full lifecycle of the super admin recovery key — minted once at setup finalization, verified over CLI when every account is locked, and regenerated after each use — including dual storage, the recovery commands, rate limiting, and integrity guards. Provisioning that creates the key belongs to [8NZAU-installation.md](8NZAU-installation.md); the wizard screen that first displays it belongs to [VEJCX-setup-wizard.md](VEJCX-setup-wizard.md).

---

## 1. Problem Statements

### PS-1 — Super Admin Lockout

After setup, the super admin may lose access through a forgotten password, a locked account, or a corrupted session. The web UI requires login, and email-based recovery may never have been configured. A path must exist that depends on neither the browser nor SMTP.
**→ Requirement:** FR-RECOV-004 (CLI recovery), FR-RECOV-007 (credential reset).

### PS-2 — Key Lifecycle Without Loss or Misuse

The key is generated once, shown once, and must survive until needed without leaking in between. Generation, storage, verification, reset, and regeneration form one chain, and a gap anywhere — an unverified copy, an unrotated reuse — breaks the whole promise.
**→ Requirement:** FR-RECOV-001/002 (minting and dual storage), FR-RECOV-009 (single-use regeneration).

### PS-3 — Dual Storage Trade-Off

Database-only storage recreates the lockout it is meant to solve: reaching the database needs a working application. File-only storage risks silent deletion with no verification anchor. Dual storage covers both, at the price of keeping the copies in sync.
**→ Requirement:** FR-RECOV-002 (hash plus file), FR-RECOV-005 (file restoration).

### PS-4 — Recovery Without a Second Factor

A CLI password reset guarded only by key possession is single-factor by construction. Email OTP would add a factor, but only where mail is configured — which, on the shared hosting this system targets, is frequently nowhere.
**→ Requirement:** FR-RECOV-010 (attempt budget as the compensating control); OTP deferred per §10 R-1.

### PS-5 — Audit Trail for Recovery Actions

Recovery is the most security-sensitive operation in the system. Every attempt, successful or failed, must log through SmartLogger with PII masked, or forensics after an incident is guesswork.
**→ Requirement:** NFR-RECOV-002 (masked audit of all outcomes).

---

## 2. Goals & Non-Goals

### Goals

- **Emergency access with no working account** — key plus CLI restores the super admin when everything else is locked. *Why:* a school with zero admins is a school back on paper.
- **Single-use keys** — every successful recovery mints a fresh key. *Why:* a used key was on a screen, possibly photographed; it must not remain valid.
- **File-first retrieval** — the key reads from a private file without a running application. *Why:* the database cannot be the rescue path for a system whose application layer is untrusted.
- **Manual-key fallback** — a printed or written-down key works when the file is gone. *Why:* files get deleted during migrations; paper survives.
- **Attempt budget against guessing** — strict rate limits on verification. *Why:* without a second factor, the attempt budget is the brute-force defense.
- **Complete masked audit** — every outcome logged with PII masked. *Why:* recovery events are exactly the rows an investigator reads first.

### Non-Goals

- **Web-based super admin recovery**. *Why:* CLI-only by design — the browser requires the login that is broken (see DD-RECOV-005).
- **Recovery of non-super-admin accounts**. *Why:* ordinary accounts use password reset and recovery slips; see [D9TKW-password-reset.md](D9TKW-password-reset.md).
- **One-time-password or multi-factor verification**. *Why:* needs configured mail, absent on much target hosting; deferred post-MVP (see §10 R-1).
- **Automatic key rotation on a schedule**. *Why:* rotation on use is the meaningful event; calendar rotation adds operations without threat-model benefit.

---

## 3. User Stories / Use Cases

One table holds every use case; the groups below (§3.1–§3.2) carry the free-form detail for each row.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-RECOV-001 | Locked-out school restores super admin access via the key file and CLI prompts | P0 | F | Full |
| UC-RECOV-002 | Administrator recovers with a manually supplied key and restores a deleted key file | P0 | F | Full |
| UC-RECOV-003 | Administrator inspects the key file path and, with confirmation, views the key | P1 | F | Full |
| UC-RECOV-004 | Setup finalization mints the recovery key into both stores and displays it once | P0 | F | Full |

### 3.1 Emergency Journeys

#### UC-RECOV-001 — Unlock the School

It is Monday morning, report week, and the only super admin password belongs to a teacher on leave with no phone signal. The district technician SSHes in and runs the recovery command with no flags. The system finds the key file, verifies its contents against the stored hash, and walks the technician through prompts: target email, new password with confirmation, then a deliberate re-type of the email as a final brake. The reset executes — password hashed, lock cleared, roles re-synced, sessions rotated — and a brand-new recovery key appears on screen because the old one is now retired. Total time under a minute, and the school makes its reporting deadline.

#### UC-RECOV-002 — The File Is Gone

A server migration copied the database but skipped the hidden key file, and the new admin holds only a photograph of the original key taken on setup day. Recovery accepts the photographed secret through the manual flag, verifies it against the unchanged hash, and proceeds exactly as the file path would have — then the regenerate-file variant rewrites the missing file from the verified key, restoring dual storage in the same motion. Paper plus flag covers every way a file can die: deletion, migration, permission loss.

### 3.2 Stewardship Journeys

#### UC-RECOV-003 — Look Before a Crisis

Between emergencies, an admin wants to confirm the safety net exists: one command prints the key file's path and whether it is present, another — after an explicit confirmation prompt, because displaying the secret is itself sensitive — shows the plaintext and logs that it did. These are audit-hygiene commands, the equivalent of checking the fire extinguisher's gauge. Their existence is also what makes the migration story survivable: the admin who checks before moving servers discovers the missing file on their own schedule.

#### UC-RECOV-004 — Minting at Birth

Behind the wizard's completion screen, finalization generates the 64-character secret, hashes it into settings, writes the plaintext file with its timestamped header, and hands the plaintext up to the screen exactly once. The administrator copies it into the school's vault and the moment passes. Everything this spec operates — verification, rotation, restoration — depends on that one careful minute, which is why minting rules are requirements here even though the wizard triggers them.

---

## 4. Functional Requirements

One table holds every functional requirement; each row's detail lives under its group (§4.1–§4.3).

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-RECOV-001 | Recovery key is a 64-character cryptographically random string | P0 | F | Full |
| FR-RECOV-002 | Key persists twice: bcrypt hash in settings and plaintext file at 0600 with header | P0 | F | Full |
| FR-RECOV-003 | Key file reads skip comments and blank lines and return null when absent or empty | P0 | F | Full |
| FR-RECOV-004 | `admin:recover` accepts file or manual key, prompts for email and password, confirms by re-type | P0 | F | Full |
| FR-RECOV-005 | `--regenerate-file` rewrites a missing key file from a verified key | P1 | F | Full |
| FR-RECOV-006 | Inspection commands show the key path and, after confirmation, the plaintext | P1 | F | Full |
| FR-RECOV-007 | Reset hashes the new password, clears the lock, re-syncs the role, and rotates sessions | P0 | F | Full |
| FR-RECOV-008 | Interactive passwords enforce minimum length with confirmation match | P0 | F | Full |
| FR-RECOV-009 | Successful recovery mints a fresh key into both stores and displays it; file failure warns on | P0 | F | Full |
| FR-RECOV-010 | Verification allows 3 attempts per email per 15 minutes, then throws `RejectedException` | P0 | F | Full |
| FR-RECOV-011 | Recovery targets PROTECTED super admin accounts only; violations throw `RejectedException` | P0 | F | Full |
| FR-RECOV-012 | Every failed precondition — unknown key, unknown email, exhausted budget — fails as `RejectedException` | P0 | F | Full |

### 4.1 Key Lifecycle

#### FR-RECOV-001 — A Secret Worth 64 Characters

Sixty-four characters from the cryptographic generator is the floor, not a flourish: the key must survive offline guessing by anyone who ever glimpsed part of it, and shorter secrets invite partial-knowledge attacks. Generation happens exactly twice in the key's life — once at setup, once per recovery — so the randomness source is exercised rarely and every output carries full entropy. There is no user-chosen recovery secret anywhere in this design, because humans choose birthdays.

#### FR-RECOV-002 — Two Copies, Two Jobs

The bcrypt hash in `setup.install_recovery_key` is the verification anchor: it proves a presented key without storing anything an attacker can reuse. The plaintext file at `storage/app/private/.recovery-key` is the retrieval path: readable over SSH with no application running, locked to owner-only permissions, headed by comments and an ISO 8601 generation timestamp so a human opening it knows what they hold. Hash without file means verification with no rescue; file without hash means rescue with no verification. The design refuses to choose.

#### FR-RECOV-003 — Forgiving Reads

The file reader skips comment lines and blanks and returns null for a missing or empty file instead of throwing — because a deleted key file is a supported state with its own recovery path, not a crash. Null propagates to the command as "no file key available," which is what routes the administrator toward the manual flag rather than a stack trace. Strictness belongs at verification; the reader's job is to report what exists, calmly.

### 4.2 Command Contract

#### FR-RECOV-004 — The Recovery Conversation

`admin:recover` takes an optional email argument and two flags — manual key and file regeneration — then conducts the rest interactively: key sourcing (file first, flag when given), email and new password prompts, and finally the email re-type that forces a pause before the irreversible write. The re-type is the cheapest safety device in the spec: it converts "oops, wrong account" from a post-incident discovery into a pre-execution catch. Every prompt is localized, because emergencies do not wait for translators.

#### FR-RECOV-005 — Restoring the Missing File

When verification succeeds against a manually supplied key but the file is absent, the regenerate-file variant rewrites it through the save action — same header, same permissions, verified content. This closes the loop that migrations open: the database moved, the file did not, and now they agree again. Treating restoration as a flag on recovery rather than a separate ceremony keeps the tool count at three commands an admin can actually remember.

#### FR-RECOV-006 — Inspect With Intent

The path command reports where the file should be and whether it is there — read-only, no confirmation needed, safe to run in any audit. The show command goes further and therefore asks first: an explicit confirmation before the plaintext appears, plus a log entry that it did. Displaying a secret is a security event even when the operator is legitimate, and the confirmation plus the audit line are what keep it an accountable one.

### 4.3 Reset and Integrity

#### FR-RECOV-007 — What Reset Actually Does

The reset action performs four writes in one transaction: the new password hashed, the lock timestamp and reason cleared, the role re-synced to super admin in case it drifted, and the remember token rotated so every existing session dies. Each write answers a real incident — the drifted role that would otherwise leave a "recovered" admin unable to act, the surviving session on a compromised laptop, the lock flag nobody cleared. A recovery that restores the password but not the authority is a second lockout wearing a success message.

#### FR-RECOV-008 — Passwords at the Prompt

Interactive entry enforces a minimum length with confirmation match before the action ever runs, rejecting weak or mismatched input at the prompt rather than inside the transaction. Emergency pressure is exactly when humans choose `admin123`, so the prompt refuses to be hurried: type it twice, meet the floor, or try again. The rules here are intentionally simpler than account-creation strength — length plus match — because the operator is authenticated by key possession, not by password creativity.

#### FR-RECOV-009 — Retire Every Used Key

Success mints a fresh 64-character key into both stores and shows it on screen, because the used key has now appeared in terminal scrollback, possibly on a shared screen, certainly in human memory. If the file rewrite fails, recovery still counts — the password already changed — and the command warns loudly while displaying the key for manual safekeeping. Blocking success on the file would strand the admin with a new password and no key anywhere; warning instead strands nothing.

#### FR-RECOV-010 — Three Tries Per Quarter Hour

Three verification attempts per email per fifteen minutes, counted in cache and cleared on success, is deliberately tight: legitimate recovery needs one careful attempt, maybe two with a transcription slip, while guessing needs thousands. The counter keys on a hash of the email so the cache never stores the address itself. Past the budget the action throws `RejectedException`, and the waiting period — not an administrator — is what reopens the door.

#### FR-RECOV-011 — Only the Protected Singleton

Recovery refuses to touch anything but a PROTECTED super admin account: the integrity check runs before any write, and ordinary admin, teacher, or student accounts are unreachable through this path by construction. Those accounts have their own recovery flows with their own threat models. Funneling every privileged reset through the singleton check is what keeps an emergency tool from becoming a privilege-escalation tool.

#### FR-RECOV-012 — One Failure Voice

Unknown key, unknown email, exhausted budget, integrity mismatch — every failed precondition surfaces as `RejectedException` with a translatable, user-safe message, per the [exception hierarchy ADR](../adr/adr-exception-hierarchy.md). A single failure type means the command's error handling is one catch with full context, and it means callers never branch on failure taxonomy to decide what to log. The messages stay generic where enumeration would help an attacker and specific where the legitimate operator needs direction — that calibration, not the exception type, is where the security judgment lives.

---

## 5. Non-Functional Requirements

One table holds every non-functional constraint; `Target` carries the concrete number or SLO, or `N/A` where enforcement is architectural.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-RECOV-001 | Key file is owner-only outside the web root; the database holds a hash, never plaintext | 0600, private dir | P0 | F | Full |
| NFR-RECOV-002 | Every recovery attempt logs through SmartLogger with PII masked, keyed by outcome event | 100% of attempts | P0 | F | Full |
| NFR-RECOV-003 | Reset mutations run inside a single database transaction | 0 partial resets | P0 | F | Full |
| NFR-RECOV-004 | Key-file write failure warns without blocking the completed reset | 0 blocked recoveries | P0 | F | Full |
| NFR-RECOV-005 | Brute-force budget holds per email within its window | 3 attempts / 900 s | P0 | F | Full |
| NFR-RECOV-006 | All recovery output is localized with prominent key display and a rotation reminder | en + id, 100% via `__()` | P1 | F | Full |
| NFR-RECOV-007 | Emergency access restores within the backup/restore RTO basis | supports 1 h RTO | P1 | F | Full |

### 5.1 Secrecy and Audit

#### NFR-RECOV-001 — Secrets Stay Secret

Owner-only file mode, a directory the web server never serves, and a database column holding an irreversible hash form three independent barriers: filesystem disclosure yields the file only to the owner, web probing cannot reach the path at all, and database theft yields a bcrypt string, not a key. Each barrier assumes the others have failed, which is the correct way to think about a secret whose compromise means total system control.

#### NFR-RECOV-002 — Every Attempt Leaves a Trace

Invalid keys, unknown emails, successes, failures, even file-regeneration warnings — each outcome logs through SmartLogger under its own event name with PII masked before either sink, so the activity table tells the complete story of who tried what, without ever recording the key or password involved. An investigator opening these rows after a suspected incident should find a timeline, not a puzzle. Missing log coverage on any outcome is a defect against this row, not a gap in diligence.

### 5.2 Reliability and Operations

#### NFR-RECOV-003 — Transactional Reset

Password, lock, role, and session writes commit together or not at all, because a reset that changes the password but dies before rotating sessions leaves the account simultaneously recovered and compromised. The transaction is also what makes the rate-limit clearing safe to bundle: success clears the counter in the same commit, so a crashed recovery never burns the legitimate operator's remaining attempts.

#### NFR-RECOV-004 — File Failure Is a Warning

The file write sits outside the transaction's critical path by design: once the database commits, the human is safe, and a filesystem problem must not retroactively endanger them. The warning is prominent rather than silent because an unwritten key file is a real degradation — the next recovery will need the manual flag — but degradation is not failure, and conflating them would manufacture lockouts out of permission errors.

#### NFR-RECOV-005 — The Attempt Budget as SLO

Three attempts per email per nine-hundred-second window is stated here as the measurable SLO behind FR-RECOV-010: bursts beyond it are rejected, the counter survives process restarts in cache, and success resets it. The number is small enough to make guessing futile and large enough to forgive one transcription slip. Any proposal to loosen it trades directly against the single-factor threat model and must say so openly.

#### NFR-RECOV-006 — Panic-Readable Output

Recovery runs during emergencies, so every string is localized, the key renders with unmissable styling, and a rotation reminder follows every success telling the operator to change the password and vault the new key. The banner with version info matters more than it looks: pasted into a support thread, it tells the helper exactly which release misbehaved. Design for the operator whose hands are shaking — clarity is a safety feature.

#### NFR-RECOV-007 — Access Recovery Inside RTO

The maintenance contract promises under-one-hour restoration from backup; access recovery must fit inside the same envelope, not beside it. A key-verified CLI reset completes in about a minute, which keeps the human-access leg of any disaster drill negligible against the hour. This row is the recovery side of the basis owned by [HBXCI-backup-system.md](HBXCI-backup-system.md): restorable data plus recoverable access, together, inside one target.

---

## 6. API / Data Contracts

Non-negotiable precision — precise enough to implement against without asking.

### 6.1 Recovery Key File Format

```
# INTERNARA RECOVERY KEY
# This key grants super admin access. Keep it secret, keep it safe.
# Only the server owner can read this file.
# Generated: {ISO 8601 timestamp}

{64-char plaintext key}
```

### 6.2 Action Contracts

```php
// RecoverSuperAdminAction — password reset with integrity check
final class RecoverSuperAdminAction extends BaseCommandAction
{
    public function execute(string $email, string $password): User;
    // Throws: RejectedException (rate limit, integrity violation)
    // Side effects: password reset, lock cleared, roles synced, remember_token rotated
}

// ReadRecoveryKeyAction — reads plaintext from file
final class ReadRecoveryKeyAction extends BaseReadAction
{
    public function execute(): ?string;
    // Returns null if file missing or empty
}

// SaveRecoveryKeyAction — writes plaintext to file
final class SaveRecoveryKeyAction extends BaseCommandAction
{
    public function execute(string $plaintext): string;
    // Returns file path on success
    // Throws: RejectedException on write failure
}
```

### 6.3 Command Signatures

```php
// RecoverAdminCommand
'admin:recover {email?} {--key=} {--regenerate-file}'

// ShowRecoveryKeyCommand
'admin:recovery-show'

// ShowRecoveryPathCommand
'admin:recovery-path'
```

### 6.4 Cache Keys

| Key | Config Reference | Purpose |
|-----|------------------|---------|
| `recovery_otp_hash` | — (removed with OTP; reserved name, do not reuse) | Deferred post-MVP, see §10 R-1 |
| `recover_admin_attempts` | `config('cache-keys.recover_admin_attempts')` | Rate limit counter (15 min TTL) |

### 6.5 Settings Keys

| Key | Type | Description |
|-----|------|-------------|
| `setup.install_recovery_key` | string | Bcrypt hash of recovery key (set during setup finalization) |

### 6.6 Events

```php
// SuperAdminRecovered — dispatched after successful recovery
class SuperAdminRecovered extends BaseEvent
{
    public function __construct(
        public User $user,
        public string $email,
    );
    public function eventName(): string; // 'super_admin.recovered'
}
```

---

## 7. Design Decisions

One table holds every design decision; detail prose below states each decision with its history and accepted cost.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-RECOV-001 | Key lives twice: bcrypt hash in settings, plaintext in a private file | P0 | — | — |
| DD-RECOV-002 | Every successful recovery retires the used key and mints a fresh one | P0 | — | — |
| DD-RECOV-003 | Recovery requires re-typing the target email before execution | P1 | — | — |
| DD-RECOV-004 | Key-file write failure warns and continues instead of aborting recovery | P0 | — | — |
| DD-RECOV-005 | Super admin recovery is CLI-only; no web recovery path exists by design | P0 | — | — |

### 7.1 Key Stewardship

#### DD-RECOV-001 — Two Copies, Deliberately Unsynchronized in Kind

Storing the plaintext only in the file and only the hash in the database means the two copies can never leak the same way: a file read needs host access, a database read yields an unverifiable string. The synchronization risk on regeneration — one write landing without the other — is real and accepted, mitigated by displaying the fresh key regardless so the human becomes the tiebreaker. A design with no failure mode would be preferable; no such design exists for a secret that must be both retrievable offline and verifiable online.

#### DD-RECOV-002 — Use Means Retire

A recovery key that survives its own use is a secret with an unknown audience — terminal history, shoulder surfers, screen shares during the emergency call. Minting fresh on every success converts each incident's exposure into a non-event, at the price of demanding the admin vault the new key while the adrenaline is still up. Forgetting the new key only costs another recovery with the new key, which the file already holds; keeping the old one could cost the system.

### 7.2 Interaction Safety

#### DD-RECOV-003 — The Re-Typed Email

Password prompts already confirm by repetition; the target email gets the same treatment because recovering the wrong account is the one mistake no audit log can undo gracefully. The re-type forces the operator to read the warning, hold the address in mind, and produce it twice — three cognitive checkpoints for the cost of ten seconds. Automation that skips the prompt must supply the argument explicitly, which preserves deliberateness in scripts too.

#### DD-RECOV-004 — Mercy Over Symmetry

Aborting a completed password reset over a failed file write would convert a filesystem hiccup into a full lockout: new password set, new key nowhere, admin told the operation failed. Continuing with a warning instead treats the database as the system of record and the file as a convenience to be restored — which is exactly what the regenerate flag then does. The ordering encodes a hierarchy: human access first, key hygiene second, transactional elegance third.

#### DD-RECOV-005 — No Web Door to the Kingdom

A browser recovery flow would need to authenticate someone with no credentials, over a channel that may be observed, to manufacture the highest privilege in the system — every design for that page ends in either theater or vulnerability. The CLI inherits the host's own access control: whoever can SSH as the owner already owns the data, so granting them the admin is no escalation. The inconvenience to panel-only hosting without terminal access is real, acknowledged, and cheaper than a public key-reset endpoint.

---

## 8. Success Metrics

| Metric | Target | How to measure |
|--------|--------|---------------|
| Recovery with a valid key | 100% complete with new password working | End-to-end CLI run in tests |
| Key retired after use | Old key fails verification post-recovery | Verify-then-recover-then-verify sequence |
| Missing file restored | 100% via `--regenerate-file` with correct mode | Delete file, recover, assert 0600 and content |
| Attempt budget enforced | 4th attempt in window rejected | Burst verification attempts in tests |
| All outcomes audited | Log entry for success, failure, invalid key, unknown email | Review activity entries per scenario |
| Recovery inside RTO | Access restored well under the hour | Timed drill alongside backup restore |

---

## 9. Roadmap

### Prerequisites

This spec builds after its dependencies are complete:

| Spec | What It Provides |
|------|-----------------|
| [8NZAU-installation.md](8NZAU-installation.md) | `setup.install_recovery_key` hash, `SetupEntity` verification basis |
| [VEJCX-setup-wizard.md](VEJCX-setup-wizard.md) | Key minted at finalization, saved to the private file |

### Build Guide

After this spec, the system owns emergency super admin recovery over CLI: key read and write actions, three commands, single-use rotation, attempt budgets, and integrity guards. The school that loses every password still has exactly one door, and it is guarded by a secret, a budget, and an audit trail.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [YB22J-settings-infrastructure.md](YB22J-settings-infrastructure.md) | `BatchSetSettingAction` persists key-hash rotation; the settings table holds all recovery state |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
|----|----------------------------------|--------|-------|----------|
| R-1 | Deferred: email OTP as a second recovery factor — needs universally configured mail, absent on much target hosting; revisit post-MVP | Deferred | Maintainer | — |
| R-2 | CLI error output distinguishes unknown-key from unknown-email cases in logs; confirm messages stay generic toward untrusted observers while guiding the legitimate operator | Open | Maintainer | — |
| A-1 | We assume whoever holds shell access as the file owner is authorized to recover; host-level access control is the outer perimeter | Accepted | Maintainer | — |
| A-2 | We assume the recovery-key file backup rides with storage backups; a restore that drops hidden files silently disarms file-first recovery until `--regenerate-file` runs | Accepted | Maintainer | — |

---

## Quick References

- [Installation](8NZAU-installation.md) — provisioning and token lifecycle that precede recovery
- [Setup wizard](VEJCX-setup-wizard.md) — first display of the key at finalization
- [Password reset](D9TKW-password-reset.md) — ordinary account recovery, explicitly out of scope here
- [Authentication](YB7RG-authentication.md) — login that recovered credentials flow back into
- [Settings infrastructure](YB22J-settings-infrastructure.md) — owns the settings store holding the key hash
- [Backup system](HBXCI-backup-system.md) — owns the RTO target this spec fits inside
- [ADR: SmartLogger dual-channel](../adr/adr-smartlogger-dual-channel.md) — masked audit for every attempt
- [ADR: Exception hierarchy](../adr/adr-exception-hierarchy.md) — `RejectedException` as the single failure voice
- [ADR: Base class mandate](../adr/adr-base-class-mandate.md) — command and read action shapes
- [ADR: UUID primary keys](../adr/adr-uuid-primary-keys.md) — key strategy for all touched rows
- [Spec registry](index.md) — all specs grouped in 12 phases

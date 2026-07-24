# Recovery Ecosystem — Super Admin Emergency Access

> **Last updated:** 2026-07-24 **Changes:** feat — new spec; recovery key lifecycle, CLI commands,
> OTP flow, key file operations

## Description

Specification for Internara's super admin recovery ecosystem. Covers the full lifecycle from
recovery key generation during setup through emergency access via CLI, including key storage,
retrieval, OTP verification, password reset, and key regeneration. Other recovery mechanisms
(password reset, recovery slips) are separate initiatives — see
[authentication.md](authentication.md) and `docs/foundation/account-recovery.md`.

---

## 1. Problem Statements

### PS-1 — Super Admin Lockout

After setup, the super admin may lose access through forgotten password, account lockout, or
corrupted session. The web UI requires login, and email-based recovery may not be configured
(shared hosting often lacks SMTP). A non-email, non-web recovery path must exist.

### PS-2 — Recovery Key Lifecycle

The recovery key is generated once during setup (Step 7), displayed once, and stored in two
forms: plaintext in a private file (for CLI retrieval) and bcrypt hash in the database (for
verification). The lifecycle spans generation → storage → verification → reset → regeneration,
and must be fully documented to prevent key loss or misuse.

### PS-3 — Dual Storage Trade-Off

Storing the recovery key only in the database creates a chicken-and-egg problem: database access
requires a running application, which requires a working super admin account. Storing only in a
file risks silent file deletion. Dual storage (file + hashed DB) provides both offline retrieval
and cryptographic verification, but introduces synchronization risk on regeneration.

### PS-4 — OTP Verification in Production

In production environments, CLI-based password reset without any secondary verification is a
single-factor operation. An attacker with SSH access could reset any super admin account. OTP
via email adds a second factor, but only when mail is configured. The system must degrade
gracefully when mail is unavailable.

### PS-5 — Audit Trail for Recovery Actions

Super admin recovery is a security-sensitive operation. Every attempt — successful or failed —
must be logged via SmartLogger with PII masking, providing an audit trail for forensic analysis.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Enable emergency super admin access via CLI when all accounts are locked |
| G2  | Regenerate recovery key after each successful recovery (key is single-use) |
| G3  | Support key retrieval from file (`storage/app/private/.recovery-key`) |
| G4  | Support manual key input via `--key` flag |
| G5  | Verify OTP in production environments for second-factor protection |
| G6  | Log all recovery attempts (success and failure) via SmartLogger |
| G7  | Regenerate recovery key file from known key (`--regenerate-file`) |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Web-based super admin recovery (CLI-only by design) |
| NG2  | Recovery of non-super-admin accounts (use password reset or recovery slips) |
| NG3  | Multi-factor recovery beyond OTP (hardware keys, TOTP) |
| NG4  | Automatic key rotation on a schedule |

---

## 3. User Stories / Use Cases

### UC-1 — Emergency Super Admin Recovery

**Actor:** Server administrator (via SSH/CLI)
**Preconditions:** System installed, super admin account inaccessible, recovery key available.
**Flow:**
1. Administrator runs `php artisan admin:recover`
2. System detects recovery key file and displays key-detected notice
3. System verifies recovery key against stored bcrypt hash in database
4. In production: system generates 6-digit OTP, sends via `RecoveryOtpNotification` (mail channel)
5. Administrator enters OTP from email
6. System prompts for target email and new password (with confirmation)
7. System displays warning and requires email re-entry for confirmation
8. `RecoverSuperAdminAction` executes: rate limit check, password reset, lock clearing, role sync, remember token rotation
9. System regenerates recovery key (new 64-char string, new hash, new file)
10. System displays new recovery key and advises password change
**Postconditions:** Super admin account accessible, old recovery key invalidated, new key generated.

### UC-2 — Recovery with Manual Key

**Actor:** Server administrator
**Recovery key file missing or deleted, but key is known (written down, printed, etc.)
**Flow:**
1. Administrator runs `php artisan admin:recover --key=<64-char-key>`
2. System verifies key against stored hash
3. Flow continues from UC-1 step 4 (OTP in production) or step 6 (non-production)
**Postconditions:** Same as UC-1.

### UC-3 — Recovery Key File Regeneration

**Actor:** Server administrator
**Recovery key file was deleted, but administrator knows the key from database hash verification
**Flow:**
1. Administrator runs `php artisan admin:recover --key=<key> --regenerate-file`
2. System verifies key, regenerates file via `SaveRecoveryKeyAction`
3. Flow continues from UC-1
**Postconditions:** Recovery key file restored.

### UC-4 — View Recovery Key

**Actor:** Server administrator
**Recovery key file exists, administrator needs to read the plaintext key
**Flow:**
1. Administrator runs `php artisan admin:recovery-show`
2. System reads key from file via `ReadRecoveryKeyAction`
3. System prompts for confirmation before displaying
4. On confirmation, system displays key and logs the event
**Postconditions:** Key displayed on screen, SmartLogger entry created.

### UC-5 — Check Recovery Key File Path

**Actor:** Server administrator
**Administrator wants to verify the recovery key file exists and its location
**Flow:**
1. Administrator runs `php artisan admin:recovery-path`
2. System displays path (`storage/app/private/.recovery-key`) and existence status
**Postconditions:** Path and status displayed.

### UC-6 — Recovery Key Generation (During Setup)

**Actor:** System (automated during setup finalization)
**Preconditions:** Setup wizard completing Step 6 (finalization)
**Flow:**
1. `FinalizeSetupAction` generates 64-char random string
2. Hash with `Hash::make()`, store in `setup.recovery_key` setting
3. Save plaintext to `storage/app/private/.recovery-key` via `SaveRecoveryKeyAction`
4. Display key on finalization screen with copy button
**Postconditions:** Key in two locations, user has copied/printed it.

---

## 4. Functional Requirements

### 4.1 Recovery Key Operations

| ID    | Requirement |
| ----- | ----------- |
| FR-K1 | `ReadRecoveryKeyAction` reads plaintext from `storage/app/private/.recovery-key`, skips comments (`#`) and blank lines |
| FR-K2 | `SaveRecoveryKeyAction` writes plaintext to `storage/app/private/.recovery-key` with header comments and `chmod 0600` |
| FR-K3 | Recovery key is 64-character cryptographically random string (via `Str::random`) |
| FR-K4 | Recovery key hash stored in `setup.recovery_key` setting (bcrypt via `Hash::make()`) |
| FR-K5 | Recovery key file path is `storage/app/private/.recovery-key` (not web-accessible) |
| FR-K6 | File header includes generation timestamp in ISO 8601 format |

### 4.2 CLI Commands

| ID    | Requirement |
| ----- | ----------- |
| FR-C1 | `admin:recover {email?} {--key=} {--regenerate-file}` — main recovery command |
| FR-C2 | `admin:recovery-show` — displays stored recovery key (requires confirmation) |
| FR-C3 | `admin:recovery-path` — displays recovery key file path and existence status |
| FR-C4 | `admin:recover` without `--key` reads key from file via `ReadRecoveryKeyAction` |
| FR-C5 | `admin:recover` with `--key` uses provided key for verification |
| FR-C6 | `admin:recover` with `--regenerate-file` rewrites file via `SaveRecoveryKeyAction` after verification |
| FR-C7 | `admin:recover` prompts for email (argument or interactive), password, and confirmation |
| FR-C8 | `admin:recover` requires re-typing email as confirmation before executing recovery |
| FR-C9 | `admin:recovery-show` reads key, prompts confirmation, then displays plaintext |
| FR-C10 | All CLI commands display formatted banners and localized messages |

### 4.3 OTP Verification

| ID    | Requirement |
| ----- | ----------- |
| FR-O1 | OTP is 6-digit random integer (`random_int(100000, 999999)`) |
| FR-O2 | OTP sent via `RecoveryOtpNotification` (mail channel, queued via `ShouldQueue`) |
| FR-O3 | OTP hash stored in cache with key `config('cache-keys.recovery_otp_hash') . email`, TTL 300 seconds |
| FR-O4 | OTP verified via `Hash::check()` against stored hash |
| FR-O5 | OTP cache entry cleared after successful verification |
| FR-O6 | OTP verification only required in production environments (`app()->environment('production')`) |
| FR-O7 | Non-production environments skip OTP (CLI-only access considered sufficient) |

### 4.4 Password Reset

| ID    | Requirement |
| ----- | ----------- |
| FR-P1 | `RecoverSuperAdminAction` sets new password via `Hash::make()` |
| FR-P2 | `RecoverSuperAdminAction` clears `locked_at` and `locked_reason` fields |
| FR-P3 | `RecoverSuperAdminAction` syncs roles to `super_admin` (ensures role assignment) |
| FR-P4 | `RecoverSuperAdminAction` rotates `remember_token` (invalidates all sessions) |
| FR-P5 | Password must be minimum 8 characters (interactive validation) |
| FR-P6 | Password confirmation required (must match) |

### 4.5 Recovery Key Regeneration

| ID    | Requirement |
| ----- | ----------- |
| FR-R1 | After successful recovery, system generates new 64-char recovery key |
| FR-R2 | New key hashed and stored in `setup.recovery_key` setting via `BatchSetSettingAction` |
| FR-R3 | New key saved to file via `SaveRecoveryKeyAction` |
| FR-R4 | File write failure does not block recovery (warning displayed, key still shown) |
| FR-R5 | New plaintext key displayed to administrator after successful recovery |

### 4.6 Rate Limiting

| ID    | Requirement |
| ----- | ----------- |
| FR-RL1 | `RecoverSuperAdminAction` enforces max 3 recovery attempts per email per 15 minutes |
| FR-RL2 | Attempt counter stored in cache with key `config('cache-keys.recover_admin_attempts') . md5(email)` |
| FR-RL3 | Counter TTL is 900 seconds (15 minutes) |
| FR-RL4 | Counter cleared on successful recovery |
| FR-RL5 | Exceeded limit throws `RejectedException` |

### 4.7 Super Admin Integrity

| ID    | Requirement |
| ----- | ----------- |
| FR-I1 | `RecoverSuperAdminAction` verifies super admin has PROTECTED status before reset |
| FR-I2 | Integrity violation throws `RejectedException` |
| FR-I3 | Recovery only targets accounts with `super_admin` role |

---

## 5. Non-Functional Requirements

### 5.1 Security

| ID     | Requirement |
| ------ | ----------- |
| NFR-S1 | Recovery key file permissions: `0600` (owner-only read/write) |
| NFR-S2 | Recovery key storage directory: `storage/app/private/` (not web-accessible) |
| NFR-S3 | Database stores only bcrypt hash — plaintext never persisted to DB |
| NFR-S4 | OTP sent via mail channel only (not displayed in CLI output) |
| NFR-S5 | All recovery attempts logged via SmartLogger with PII masking |
| NFR-S6 | Invalid key attempts logged as warnings with event `super_admin.recovery.invalid_key` |
| NFR-S7 | Not-found email attempts logged as warnings with event `super_admin.recovery.blocked_not_found` |
| NFR-S8 | Successful recovery logged as success with event `super_admin.recovery.succeeded` |
| NFR-S9 | Failed recovery logged as error with event `super_admin.recovery.failed` |
| NFR-S10 | Recovery key file regeneration failure logged as warning (non-blocking) |

### 5.2 Performance

| ID     | Requirement |
| ------ | ----------- |
| NFR-P1 | OTP verification uses cache (not database) for storage |
| NFR-P2 | Recovery key read is a single file read (no database query for plaintext) |
| NFR-P3 | `RecoveryOtpNotification` queued via `ShouldQueue` (non-blocking mail send) |

### 5.3 Reliability

| ID     | Requirement |
| ------ | ----------- |
| NFR-R1 | Recovery key file write failure does not block password reset |
| NFR-R2 | OTP send failure displays error and aborts (does not proceed without OTP in production) |
| NFR-R3 | `RecoverSuperAdminAction` wraps all mutations in a database transaction |

### 5.4 Usability

| ID     | Requirement |
| ------ | ----------- |
| NFR-U1 | CLI output includes formatted banner with version info |
| NFR-U2 | All user-facing strings use `__()` translation helper |
| NFR-U3 | Recovery key displayed with prominent visual styling (yellow background) |
| NFR-U4 | Warning to change password displayed after successful recovery |

---

## 6. API / Data Contracts

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

### 6.3 Notification Contract

```php
// RecoveryOtpNotification — 6-digit OTP via mail
class RecoveryOtpNotification extends Notification implements ShouldQueue
{
    public function __construct(public string $otp);

    public function via($notifiable): array;    // ['mail']
    public function toMail($notifiable): MailMessage;
    // Subject, greeting, OTP line, expiry warning, security note
}
```

### 6.4 Command Signatures

```php
// RecoverAdminCommand
'admin:recover {email?} {--key=} {--regenerate-file}'

// ShowRecoveryKeyCommand
'admin:recovery-show'

// ShowRecoveryPathCommand
'admin:recovery-path'
```

### 6.5 Cache Keys

| Key | Config Reference | Purpose |
| --- | --------------- | ------- |
| `recovery_otp_hash` | `config('cache-keys.recovery_otp_hash')` | OTP hash storage (5min TTL) |
| `recover_admin_attempts` | `config('cache-keys.recover_admin_attempts')` | Rate limit counter (15min TTL) |

### 6.6 Settings Keys

| Key | Type | Description |
| --- | ---- | ----------- |
| `setup.recovery_key` | string | Bcrypt hash of recovery key (set during setup finalization) |

### 6.7 Events

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

### DD-1 — Dual Storage (File + Hashed DB)

**Decision:** Store recovery key as plaintext in a private file AND as a bcrypt hash in the
database.

**Rationale:** The file is the "break glass" mechanism — readable by server administrators with
filesystem access (SSH), independent of the application. The hash enables cryptographic
verification without exposing the plaintext in the database. If the database is compromised,
the attacker gets only a hash. If the file is deleted, the administrator can still verify
via `--key` flag (but cannot retrieve the key).

**Trade-off:** Synchronization risk on regeneration — if file write succeeds but DB write fails
(or vice versa), the key becomes inconsistent. Mitigated by wrapping in transaction and
displaying the new key regardless of file write outcome.

### DD-2 — OTP Only in Production

**Decision:** OTP verification via email is only required in production environments.

**Rationale:** In non-production environments (local, dev, testing), SSH access already
implies server-level trust. Adding OTP would require configured mail, which is often absent
in development. In production, SSH access + email OTP provides two-factor protection.

**Trade-off:** An attacker with SSH access in non-production can reset without OTP. Acceptable
because non-production environments should not contain sensitive data.

### DD-3 — Key Regeneration After Every Recovery

**Decision:** Generate a new recovery key after every successful recovery operation.

**Rationale:** A recovery key used to gain access should not remain valid — it may have been
exposed during the recovery process (screen capture, shoulder surfing, log files). Fresh
key forces the administrator to securely store the new key.

**Trade-off:** If the administrator fails to save the new key and loses access again, they
must repeat the recovery process with the new key. Acceptable because the alternative
(retaining a potentially compromised key) is worse.

### DD-4 — Confirmation by Email Re-Entry

**Decision:** Require the administrator to re-type the target email address before executing
recovery.

**Rationale:** Prevents accidental recovery of the wrong account. The warning message explicitly
states the consequences, and email re-entry forces deliberate action.

### DD-5 — File Write Failure is Non-Blocking

**Decision:** If `SaveRecoveryKeyAction` fails during key regeneration, display a warning but
do not abort the recovery.

**Rationale:** The password reset has already succeeded. Blocking on file write would leave the
administrator with a new password but no recovery key (neither in file nor displayed). By
continuing, the administrator gets the new key on screen and can manually save it or fix the
file permissions.

---

## 8. Success Metrics

### 8.1 Recovery Completeness

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Recovery success rate | 100% with valid key | `php artisan admin:recover` completes |
| Key regeneration | Always after recovery | New key displayed, hash updated in DB |
| File restoration | 100% with `--regenerate-file` | File written with correct permissions |

### 8.2 Security Properties

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| OTP enforcement | Always in production | `app()->environment('production')` check |
| Rate limiting | Max 3 attempts / 15min | Cache counter per email |
| Audit coverage | 100% of attempts | SmartLogger entries for all outcomes |
| Key file permissions | Always 0600 | `chmod` after write |

### 8.3 Operational

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Recovery time | < 30 seconds (non-production) | SSH command to password display |
| OTP delivery | < 30 seconds | Mail queue processing |
| Key read latency | < 100ms | Single file read |

---

## Quick References

- `docs/modules/setup.md` — Setup module conceptual overview
- `docs/modules/setup-reference.md` — Setup module technical reference
- `docs/specs/installation.md` — CLI installation and provisioning initiative
- `docs/specs/setup-wizard.md` — Browser-based wizard initiative
- `docs/specs/authentication.md` — Login, password reset, session management
- `docs/foundation/account-recovery.md` — All three recovery mechanisms
- `app/SysAdmin/Console/Commands/RecoverAdminCommand.php` — Main recovery command
- `app/SysAdmin/Console/Commands/ShowRecoveryKeyCommand.php` — Key display command
- `app/SysAdmin/Console/Commands/ShowRecoveryPathCommand.php` — Path display command
- `app/Auth/SuperAdmin/Actions/RecoverSuperAdminAction.php` — Password reset action
- `app/Auth/SuperAdmin/Notifications/RecoveryOtpNotification.php` — OTP mail notification
- `app/User/UserManagement/Actions/ReadRecoveryKeyAction.php` — Key file reader
- `app/User/UserManagement/Actions/SaveRecoveryKeyAction.php` — Key file writer
- `app/Setup/Entities/SetupEntity.php` — Setup state entity (recovery key hash)

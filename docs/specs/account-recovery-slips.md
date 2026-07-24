# Account Recovery Slips — User-Facing Backup Access

> **Last updated:** 2026-07-24 **Changes:** feat — new spec for Phase 3 Identity & Auth;
> recovery slip generation, redemption, admin management

## Description

User-facing account recovery system using one-time recovery codes. Authenticated users
generate 10 uppercase 12-character codes, stored as hashed `AccessToken` rows with
`token_type = 'account_recovery'`. If locked out, a user (or admin) redeems a code to
reset the password. Codes are single-use, with 100-year expiry. Separate from the super
admin recovery ecosystem (see `recovery-ecosystem.md`).

---

## 1. Problem Statements

### PS-1 — Users Without Configured Email Recovery

Some users may not have a working email configured or the school's mail server may be
down. Email-based password reset (see `password-reset.md`) fails in these cases. A
non-email recovery mechanism provides a fallback.

### PS-2 — Account Lockout Without Admin Help

When a user forgets their password and email recovery is unavailable, they must contact
an admin to manually reset their account. Recovery slips empower users to self-serve
account recovery without admin intervention.

### PS-3 — Admin-Assisted Recovery

Admins may need to generate recovery codes for users who never generated their own
(e.g., new hires who forgot during onboarding). An admin slip manager provides this
capability without exposing password reset to admins.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Provide `GenerateRecoverySlipAction` — generates 10 hashed one-time codes for a user |
| G2  | Provide `RedeemRecoverySlipAction` — verifies code, sets new password, marks code used |
| G3  | Provide `RecoveryCode` Livewire — authenticated user generates views, and downloads codes as PDF |
| G4  | Provide `AccountRecovery` Livewire — guest redemption page with username + code + new password |
| G5  | Provide `RecoverySlipManager` Livewire — admin generates codes for any user |
| G6  | Log all generation and redemption events via SmartLogger |
| G7  | Revokes all existing codes when new codes are generated (single active set per user) |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Super admin CLI recovery — see `recovery-ecosystem.md` (#13) |
| NG2  | Email-based password reset — see `password-reset.md` (#20) |
| NG3  | Multi-factor authentication (MFA/TOTP) |
| NG4  | Recovery code regeneration on redemption (codes are consumed, not rotated) |

---

## 3. User Stories / Use Cases

### UC-1 — User Generates Recovery Codes

**Actor:** Authenticated user
**Preconditions:** User is logged in; navigates to `/profile/recovery`
**Flow:**
1. User clicks "Generate Recovery Codes"
2. `GenerateRecoverySlipAction` revokes all existing `account_recovery` tokens for user
3. Action generates 10 random 12-character uppercase codes
4. Each code hashed and stored as `AccessToken` with `token_type = 'account_recovery'`, 100-year expiry
5. Dispatches `RecoverySlipGenerated` event
6. `RecoveryCode` Livewire stores plaintext codes in session, displays them once
7. User can download codes as PDF via DomPDF
**Postconditions:** 10 codes active; old codes revoked; user has seen/downloaded codes

### UC-2 — User Redeems Recovery Code

**Actor:** User (unauthenticated, locked out)
**Preconditions:** User has a valid recovery code; navigates to `/recover-account`
**Flow:**
1. User enters username, recovery code, new password + confirmation
2. `RedeemRecoverySlipAction` throttles: max 3 attempts per 300 seconds
3. Looks up user by username
4. Iterates over valid (non-revoked, non-used, non-expired) `account_recovery` tokens
5. `Hash::check()` against each stored hash
6. On match: updates user's password via `Hash::make()`, marks token as `last_used_at`
7. On no match: throws `RejectedException`
**Postconditions:** Password reset; code consumed; user can log in with new password

### UC-3 — Admin Generates Codes for User

**Actor:** Super admin or admin
**Preconditions:** Admin navigates to `/admin/recovery-slips`
**Flow:**
1. Admin searches for user by name/username/email
2. Selects user from search results
3. `RecoverySlipManager` Livewire calls `GenerateRecoverySlipAction` for selected user
4. Codes displayed to admin (who should share securely with user)
**Postconditions:** 10 codes generated; admin views them once

### UC-4 — User Downloads Codes as PDF

**Actor:** Authenticated user
**Preconditions:** Codes were just generated (stored in session)
**Flow:**
1. User clicks "Download PDF" on recovery codes page
2. `RecoveryCode` Livewire renders `auth.account-recovery.pdf.recovery-codes` Blade view
3. DomPDF generates PDF with numbered codes, warning banner, timestamp
4. PDF streamed as download
**Postconditions:** User has PDF backup of recovery codes

---

## 4. Functional Requirements

| ID      | Requirement |
| ------- | ----------- |
| FR-GR1  | `GenerateRecoverySlipAction` must revoke all existing `account_recovery` tokens for the user before generating new ones |
| FR-GR2  | Action must generate exactly 10 random 12-character uppercase alphanumeric codes |
| FR-GR3  | Each code must be stored as a hashed `AccessToken` with `token_type = 'account_recovery'` and 100-year expiry |
| FR-GR4  | Action must dispatch `RecoverySlipGenerated` event with user and code count |
| FR-GR5  | Action must return array of plaintext codes + first `RecoveryCodeData` DTO |
| FR-RD1  | `RedeemRecoverySlipAction` must throttle: max 3 attempts per 300 seconds (by IP) |
| FR-RD2  | Action must look up user by username (throw `RejectedException` if not found) |
| FR-RD3  | Action must iterate valid tokens and `Hash::check()` against each |
| FR-RD4  | On match: update user's `password` field with `Hash::make($newPassword)` |
| FR-RD5  | On match: set `last_used_at` on the matched token |
| FR-RD6  | On no match: throw `RejectedException` with generic message |
| FR-RD7  | Redemption must run in a database transaction |
| FR-RC1  | `RecoveryCode` Livewire must store plaintext codes in session (display once only) |
| FR-RC2  | Component must render codes as a numbered list with security warnings |
| FR-RC3  | Component must support PDF download via DomPDF |
| FR-RC4  | Component must clear session data when user navigates away or clicks "Done" |
| FR-AR1  | `AccountRecovery` (guest) Livewire must show username, code, password, password confirmation fields |
| FR-AR2  | Component must throttle: max 3 attempts per 300 seconds (by IP) |
| FR-AR3  | On success: flash success message and redirect to login |
| FR-RS1  | `RecoverySlipManager` (admin) must be authorized via `viewAny` User policy |
| FR-RS2  | Component must allow user search by name/username/email |
| FR-RS3  | Component must call `GenerateRecoverySlipAction` for selected user |

---

## 5. Non-Functional Requirements

| ID      | Requirement |
| ------- | ----------- |
| NFR-L1  | All generation and redemption events must be logged via SmartLogger with PII masking |
| NFR-S1  | Recovery codes must be stored as bcrypt hashes (not plaintext) |
| NFR-S2  | Codes displayed only once after generation (session-based) |
| NFR-S3  | PDF download must include security warning about code storage |
| NFR-M1  | All actions must declare `strict_types=1` |

---

## 6. API / Data Contracts

### Actions

```php
// app/Auth/AccountRecovery/Actions/GenerateRecoverySlipAction.php
final class GenerateRecoverySlipAction extends BaseCommandAction
{
    public function execute(User $user): ActionResponse;
    // Revokes all existing account_recovery tokens
    // Generates 10 hashed codes with 100-year expiry
    // Returns ActionResponse::ok(['codes' => [...], 'recoveryCode' => RecoveryCodeData])
}

// app/Auth/AccountRecovery/Actions/RedeemRecoverySlipAction.php
final class RedeemRecoverySlipAction extends BaseCommandAction
{
    public function execute(string $username, string $recoveryCode, string $newPassword): ActionResponse;
    // Throttle: max 3 per 300s by IP
    // Hash::check against each valid token
    // Updates password on match, marks token used
}
```

### Entities & DTOs

```php
// app/Auth/AccountRecovery/Entities/RecoveryCodeState.php
final readonly class RecoveryCodeState extends BaseEntity
{
    public function __construct(
        public ?string $lastUsedAt,
        public string $expiresAt,
    );
    public function isValid(): bool;
}

// app/Auth/AccountRecovery/Data/RecoveryCodeData.php
final readonly class RecoveryCodeData extends BaseData
{
    public function __construct(
        public string $plainText,
        public string $hashedToken,
        public ?string $expiresAt,
    );
}
```

### Livewire Components

```php
// app/Auth/AccountRecovery/Livewire/AccountRecovery.php (guest)
class AccountRecovery extends BaseFormView
{
    public string $username = '';
    public string $recoveryCode = '';
    public string $password = '';
    public string $passwordConfirmation = '';
    public function redeem(RedeemRecoverySlipAction $action): void;
    // Throttle: 3 per 300s
}

// app/Auth/AccountRecovery/Livewire/RecoveryCode.php (auth)
class RecoveryCode extends BaseFormView
{
    public function generate(GenerateRecoverySlipAction $action): void;
    public function downloadPdf(): StreamedResponse;
    public function resetCode(): void;
}

// app/Auth/AccountRecovery/Livewire/RecoverySlipManager.php (admin)
class RecoverySlipManager extends BaseFormView
{
    public ?User $selectedUser = null;
    public string $search = '';
    public function searchUsers(): Collection;
    public function generate(GenerateRecoverySlipAction $action): void;
}
```

### Model (Shared)

```php
// app/Auth/AccessTokens/Models/AccessToken.php
class AccessToken extends BaseModel
{
    // token_type = 'account_recovery' for recovery codes
    // Columns: id (uuid), user_id (FK), token (hashed), token_type,
    //          name, scopes, expires_at, attempts, last_attempt_at,
    //          last_used_at, revoked_at, timestamps
    public function asRecoveryCodeState(): RecoveryCodeState;
}
```

### Routes

| Route | Component | Middleware |
| ----- | --------- | ---------- |
| `GET /recover-account` | `AccountRecovery` (Livewire) | `guest`, `auth.throttle` |
| `GET /profile/recovery` | `RecoveryCode` (Livewire) | `auth` |
| `GET /admin/recovery-slips` | `RecoverySlipManager` (Livewire) | `auth`, `role:super_admin\|admin` |

---

## 7. Design Decisions

### DD-1 — Single Active Set Per User

**Decision:** GenerateRecoverySlipAction revokes all existing codes before generating new ones.
**Rationale:** Prevents accumulation of valid codes over time. If a user generates codes,
forgets about them, generates again — the old set is invalid. Only the most recent set is
active. This limits the attack surface.
**Trade-off:** If a user generates new codes and loses the old ones before using them, no
regret. If they generate new codes and lose the new ones, they lose all codes. Acceptable —
the "download PDF" feature mitigates this.

### DD-2 — 100-Year Expiry

**Decision:** Recovery codes have a 100-year expiry (effectively infinite).
**Rationale:** Recovery codes are a "break glass" mechanism. They should not expire during
the user's lifetime of using the system. Short expiry would force periodic regeneration,
which users would forget to do.
**Trade-off:** Old unused codes accumulate in the database. Mitigated by revocation on
regeneration and the fact that `account_recovery` tokens are lightweight rows.

### DD-3 — Guest Redemption (No Login Required)

**Decision:** The redemption page (`/recover-account`) is accessible without authentication.
**Rationale:** The entire point is to recover a locked-out account. Requiring login would
create a circular dependency. The throttle (3 per 300s) and the knowledge requirement
(username + 12-char code) provide sufficient abuse prevention.

### DD-4 — Codes Stored Hashed, Displayed Once

**Decision:** Codes are bcrypt-hashed in the database and shown only once after generation.
**Rationale:** Following the same pattern as passwords — if the database is compromised,
the attacker gets only hashes, not usable codes. Session-based display ensures codes are
not retrievable after the generation page is closed.

---

## 8. Success Metrics

| Metric | Target |
| ------ | ------ |
| Code generation | < 2 seconds for 10 codes |
| Redemption success | 100% with valid code + username |
| Hash verification | < 500ms for 10 token iteration |
| PDF generation | < 3 seconds |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|-----------------|
| [base-classes.md](base-classes.md) (#2) | `BaseCommandAction`, `ActionResponse`, `RejectedException`, `BaseEntity`, `BaseData` |
| [authentication.md](authentication.md) (#17) | `User` model, `AccessToken` model, auth infrastructure |
| [file-uploads-media.md](file-uploads-media.md) (#46) | DomPDF for PDF generation |

### Build Guide
Implement `GenerateRecoverySlipAction` and `RedeemRecoverySlipAction` first (business logic),
then the 3 Livewire components. The `AccessToken` model and `access_tokens` table already
exist from Phase 3 authentication — recovery codes reuse this infrastructure with
`token_type = 'account_recovery'`.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | (No downstream) | Recovery slips are a standalone auth recovery mechanism |

---

## Quick References

- `app/Auth/AccountRecovery/Actions/GenerateRecoverySlipAction.php` — Code generation (58 lines)
- `app/Auth/AccountRecovery/Actions/RedeemRecoverySlipAction.php` — Code redemption (64 lines)
- `app/Auth/AccountRecovery/Entities/RecoveryCodeState.php` — Code validity entity
- `app/Auth/AccountRecovery/Data/RecoveryCodeData.php` — Code DTO
- `app/Auth/AccountRecovery/Events/RecoverySlipGenerated.php` — Generation event
- `app/Auth/AccountRecovery/Livewire/AccountRecovery.php` — Guest redemption page
- `app/Auth/AccountRecovery/Livewire/RecoveryCode.php` — Auth user code page
- `app/Auth/AccountRecovery/Livewire/RecoverySlipManager.php` — Admin slip manager
- `app/Auth/AccountRecovery/Livewire/Forms/AccountRecoveryForm.php` — Redemption form validation
- `app/Auth/AccessTokens/Models/AccessToken.php` — Shared token model (reused for recovery)
- `resources/views/auth/account-recovery/` — Blade views (recovery page, codes page, admin manager, PDF template, guides)
- **Related spec:** [recovery-ecosystem.md](recovery-ecosystem.md) (#13) — Super admin CLI recovery
- **Related spec:** [password-reset.md](password-reset.md) (#20) — Email-based password reset

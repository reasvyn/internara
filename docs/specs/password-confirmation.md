# Password Confirmation — Re-Authentication Gate

> **Last updated:** 2026-07-24 **Changes:** feat — new spec for Phase 3 Identity & Auth;
> password confirmation for sensitive actions

## Description

Re-authentication gate that requires users to confirm their current password before performing
sensitive actions (changing email, deleting account, etc.). Stores confirmation timestamp in
session; sensitive actions check `auth.password_confirmed_at` freshness. Standard Laravel
security pattern implemented as a standalone Livewire component at `/user/confirm-password`.

---

## 1. Problem Statements

### PS-1 — Sensitive Actions Require Re-Authentication

Actions like changing email, changing password, or deleting an account are security-sensitive.
If a user walks away from an unlocked browser, someone else could change their email and
take over the account. A password confirmation gate ensures the person performing the action
is the legitimate account holder, even if the session is already authenticated.

### PS-2 — Session Freshness Enforcement

Laravel's `ConfirmPasswordMiddleware` checks `auth.password_confirmed_at` against a configurable
max age. Without this, a confirmed session remains valid indefinitely. The confirmation
timestamp must be refreshed each time the user confirms.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Provide `ConfirmPasswordAction` — validates current password, sets session timestamp |
| G2  | Provide `ConfirmPassword` Livewire component — password entry with throttle |
| G3  | Log all confirmation attempts (success and failure) via SmartLogger |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Two-factor authentication (2FA) |
| NG2  | Biometric confirmation |
| NG3  | Confirmation for every action — only for actions gated by `password.confirm` middleware |

---

## 3. User Stories / Use Cases

### UC-1 — User Confirms Password for Sensitive Action

**Actor:** Authenticated user
**Preconditions:** User is logged in; user navigates to a password-confirmation-requiring route
**Flow:**
1. System redirects to `/user/confirm-password` (via `password.confirm` middleware)
2. `ConfirmPassword` Livewire component shows password input
3. User enters current password
4. `ConfirmPasswordAction` verifies password via `Hash::check()`
5. On match: sets `session(['auth.password_confirmed_at' => time()])`
6. On mismatch: throws `RejectedException`
7. System redirects back to the original intended route
**Postconditions:** Session marked as confirmed; sensitive action accessible

---

## 4. Functional Requirements

| ID      | Requirement |
| ------- | ----------- |
| FR-PC1  | `ConfirmPasswordAction` must verify password via `Hash::check()` against authenticated user's password |
| FR-PC2  | On success, action must set `session(['auth.password_confirmed_at' => time()])` |
| FR-PC3  | On failure, action must throw `RejectedException` with `auth.password_confirmation_failed` message |
| FR-PC4  | Action must log `password_confirmed` event via SmartLogger on success |
| FR-PC5  | `ConfirmPassword` Livewire must throttle: max 5 attempts per 300 seconds per user+IP |
| FR-PC6  | Component must show password input field with validation |
| FR-PC7  | On success, component must redirect to intended URL or `/profile` |
| FR-PC8  | `password.confirm` middleware must check `auth.password_confirmed_at` freshness (configurable max age, default: configurable in `config/auth.php`) |

---

## 5. Non-Functional Requirements

| ID      | Requirement |
| ------- | ----------- |
| NFR-L1  | Confirmation success and failure events must be logged via SmartLogger |
| NFR-S1  | Password must never be stored or logged in plaintext |
| NFR-M1  | Action must declare `strict_types=1` |

---

## 6. API / Data Contracts

### Actions

```php
// app/Auth/Password/Actions/ConfirmPasswordAction.php
final class ConfirmPasswordAction extends BaseCommandAction
{
    public function execute(User $user, string $password): ActionResponse;
    // Verifies Hash::check($password, $user->password)
    // Sets session auth.password_confirmed_at
    // Throws: RejectedException on mismatch
}
```

### Livewire Component

```php
// app/Auth/Password/Livewire/ConfirmPassword.php
class ConfirmPassword extends BaseFormView
{
    public string $password = '';
    public function confirm(ConfirmPasswordAction $action): void;
    // Throttle: max 5 attempts per 300s
    // On success: redirect to intended URL
}
```

### Route

| Route | Component | Middleware |
| ----- | --------- | ---------- |
| `GET/POST /user/confirm-password` | `ConfirmPassword` (Livewire) | `auth`, `throttle:5,300` |

---

## 7. Design Decisions

### DD-1 — Session-Based Confirmation, Not Token-Based

**Decision:** Use `session(['auth.password_confirmed_at' => time()])` rather than a
one-time token.
**Rationale:** Session-based confirmation is the standard Laravel pattern. It works for
multiple sensitive actions within the same session without requiring re-confirmation for
each action. The `ConfirmPasswordMiddleware` checks freshness against a configurable max age.
**Trade-off:** Confirmation persists for the session lifetime (or until max age expires).
If the session is hijacked, the attacker inherits the confirmation. Acceptable — session
hijacking is mitigated by other security measures (HTTPS, session cookies).

---

## 8. Success Metrics

| Metric | Target |
| ------ | ------ |
| Confirmation accuracy | 0 false positives (wrong password accepted) |
| Throttle bypass | 0 throttle bypasses |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|-----------------|
| [base-classes.md](base-classes.md) (#2) | `BaseCommandAction`, `ActionResponse`, `RejectedException` |
| [authentication.md](authentication.md) (#17) | `User` model, auth infrastructure |

### Build Guide
Single action + single Livewire component. Wire `password.confirm` middleware in
`bootstrap/app.php` or `Kernel.php`.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | (No downstream) | Consumed by any action requiring `password.confirm` middleware |

---

## Quick References

- `app/Auth/Password/Actions/ConfirmPasswordAction.php` — Password verification (30 lines)
- `app/Auth/Password/Livewire/ConfirmPassword.php` — Confirmation page
- `app/Auth/Password/Livewire/Forms/ConfirmPasswordForm.php` — Validation
- **Related spec:** [authentication.md](authentication.md) (#17) — Login, activation
- **Related spec:** [password-reset.md](password-reset.md) (#20) — Forgot/reset flow

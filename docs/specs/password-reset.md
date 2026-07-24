# Password Reset — Forgot/Reset Flow

> **Last updated:** 2026-07-24 **Changes:** feat — new spec for Phase 3 Identity & Auth;
> forgot password link, reset via token, throttling

## Description

Forgotten password recovery flow: user requests a reset link via email, receives a token-based
URL, and sets a new password. Includes rate limiting on both request and reset actions, email
notification via Laravel's `Password` broker, and SmartLogger audit trail. Covers the
`/forgot-password` and `/reset-password/{token}` routes.

---

## 1. Problem Statements

### PS-1 — Users Lock Themselves Out

Students, teachers, and supervisors frequently forget passwords. Without a self-service reset
flow, they must contact the admin to manually reset credentials — creating support overhead
and downtime. A standard email-based reset flow lets users recover access independently.

### PS-2 — Reset Abuse Prevention

An unrestricted reset endpoint could be abused for email enumeration (probing which emails
exist) or denial-of-service (flooding inboxes with reset emails). Rate limiting on both the
request and the reset action is required to prevent abuse.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Provide `SendPasswordResetLinkAction` — throttled email-based reset link dispatch |
| G2  | Provide `ResetPasswordAction` — throttled token-based password reset with confirmation |
| G3  | Provide `ForgotPassword` Livewire component — email submission with link-sent feedback |
| G4  | Provide `ResetPassword` Livewire component — token validation, new password entry |
| G5  | Log all reset events (requested, throttled, succeeded, failed) via SmartLogger |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Password reset via SMS or phone — email only |
| NG2  | Security question-based reset |
| NG3  | Admin-initiated password reset for other users — see user-crud-and-status.md |
| NG4  | Account recovery via recovery slips — see account-recovery-slips.md |

---

## 3. User Stories / Use Cases

### UC-1 — User Requests Password Reset

**Actor:** User (unauthenticated)
**Preconditions:** User has a registered email; app mail is configured
**Flow:**
1. User navigates to `/forgot-password`
2. Enters email address in `ForgotPassword` Livewire component
3. `SendPasswordResetLinkAction` checks throttle: max 3 requests per email+IP per hour
4. If throttled: returns `RESET_LINK_SENT` anyway (prevents email enumeration)
5. If not throttled: calls `Password::sendResetLink()` which generates token, sends email
6. Email contains link: `/reset-password/{token}?email={email}`
7. Component shows "link sent" confirmation state
**Postconditions:** Reset email dispatched; throttle counter incremented

### UC-2 — User Resets Password via Token

**Actor:** User (unauthenticated, has reset link)
**Preconditions:** Valid reset token in URL; token not expired
**Flow:**
1. User clicks link from email, arrives at `/reset-password/{token}`
2. `ResetPassword` Livewire component pre-fills email from query string
3. User enters new password + confirmation
4. `ResetPasswordAction` checks throttle: max 5 attempts per email+IP per 5 minutes
5. Validates password confirmation match
6. Calls `Password::reset()` with credentials
7. On success: password hashed and saved, token consumed
8. On failure: throws `RejectedException` with appropriate message
**Postconditions:** Password changed; old sessions may remain valid (not invalidated here)

---

## 4. Functional Requirements

| ID      | Requirement |
| ------- | ----------- |
| FR-PR1  | `SendPasswordResetLinkAction` must throttle by email+IP: max 3 attempts per 3600 seconds |
| FR-PR2  | When throttled, action must return `RESET_LINK_SENT` status (not reveal throttling to prevent enumeration) |
| FR-PR3  | Action must call `Password::sendResetLink()` with `['email' => $email]` |
| FR-PR4  | Action must log `password_reset_link_requested` and `password_reset_link_throttled` events via SmartLogger |
| FR-PR5  | `ResetPasswordAction` must throttle by email+IP: max 5 attempts per 300 seconds |
| FR-PR6  | When throttled, action must throw `RejectedException` with throttle message |
| FR-PR7  | Action must validate password === passwordConfirmation before calling `Password::reset()` |
| FR-PR8  | On confirmation mismatch, action must log `password_reset_confirmation_mismatch` and throw `RejectedException` |
| FR-PR9  | Action must call `Password::reset()` with email, token, password, password_confirmation |
| FR-PR10 | On `Password::PASSWORD_RESET` success, action must log `password_reset_success` and return `ActionResponse::ok()` |
| FR-PR11 | On invalid token/user, action must throw `RejectedException` with localized message |
| FR-PR12 | `ForgotPassword` Livewire must show email input and "link sent" confirmation state |
| FR-PR13 | `ResetPassword` Livewire must accept token from URL, show email (pre-filled), password, confirmation fields |
| FR-PR14 | Reset token must expire per Laravel default (60 minutes) |
| FR-PR15 | Successful reset must consume the token (single-use) |

---

## 5. Non-Functional Requirements

| ID      | Requirement |
| ------- | ----------- |
| NFR-L1  | All reset events must be logged via SmartLogger with module `Auth` |
| NFR-L2  | PII (email) must be masked in logs via `withPiiMasking()` |
| NFR-S1  | Throttle responses must not reveal whether the email exists in the system |
| NFR-S2  | Reset tokens must be stored as hashed values (Laravel default) |
| NFR-M1  | All actions must declare `strict_types=1` |

---

## 6. API / Data Contracts

### Actions

```php
// app/Auth/Password/Actions/SendPasswordResetLinkAction.php
final class SendPasswordResetLinkAction extends BaseCommandAction
{
    public function execute(string $email): ActionResponse;
    // Throttle: 'forgot-password:{email}|{ip}', max 3, decay 3600
    // Returns: ActionResponse::ok(Password::RESET_LINK_SENT)
}

// app/Auth/Password/Actions/ResetPasswordAction.php
final class ResetPasswordAction extends BaseCommandAction
{
    public function execute(
        string $email,
        string $token,
        string $password,
        string $passwordConfirmation,
    ): ActionResponse;
    // Throttle: 'reset-password:{email}|{ip}', max 5, decay 300
    // Throws: RejectedException on throttle, mismatch, invalid token
}
```

### Livewire Components

```php
// app/Auth/Password/Livewire/ForgotPassword.php
class ForgotPassword extends BaseFormView
{
    public string $email = '';
    public bool $emailSent = false;
    public function sendLink(SendPasswordResetLinkAction $action): void;
}

// app/Auth/Password/Livewire/ResetPassword.php
class ResetPassword extends BaseFormView
{
    public string $token;
    public string $email;
    public string $password = '';
    public string $passwordConfirmation = '';
    public function resetPassword(ResetPasswordAction $action): void;
}
```

### Routes

| Route | Component | Middleware |
| ----- | --------- | ---------- |
| `GET /forgot-password` | `ForgotPassword` (Livewire) | `guest` |
| `GET /reset-password/{token}` | `ResetPassword` (Livewire) | `guest` |

---

## 7. Design Decisions

### DD-1 — Throttled Response Returns Success

**Decision:** `SendPasswordResetLinkAction` returns `RESET_LINK_SENT` even when throttled.
**Rationale:** Laravel's Password broker already does this. Returning a failure or "too many
requests" message when throttled reveals whether the email exists in the system (email
enumeration attack). Always returning success prevents this.
**Trade-off:** Abuser cannot be notified they are throttled. Acceptable — the throttle still
blocks actual email sends.

---

## 8. Success Metrics

| Metric | Target |
| ------ | ------ |
| Reset email delivery | < 5 seconds from request to email dispatched |
| Throttle accuracy | 0 bypassed throttle attempts |
| Token single-use | 0 password resets using the same token twice |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|-----------------|
| [base-classes.md](base-classes.md) (#2) | `BaseCommandAction`, `ActionResponse`, `RejectedException` |
| [authentication.md](authentication.md) (#17) | `User` model, auth infrastructure |

### Build Guide
Implement `SendPasswordResetLinkAction` and `ResetPasswordAction` first (business logic),
then wire Livewire components. Routes are `guest`-only middleware.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | (No downstream) | Password reset is a standalone auth flow |

---

## Quick References

- `app/Auth/Password/Actions/SendPasswordResetLinkAction.php` — Reset link dispatch (41 lines)
- `app/Auth/Password/Actions/ResetPasswordAction.php` — Token-based reset (83 lines)
- `app/Auth/Password/Livewire/ForgotPassword.php` — Forgot password page
- `app/Auth/Password/Livewire/ResetPassword.php` — Reset password page
- `app/Auth/Password/Livewire/Forms/ForgotPasswordForm.php` — Email validation
- `app/Auth/Password/Livewire/Forms/ResetPasswordForm.php` — Token+password validation
- **Related spec:** [authentication.md](authentication.md) (#17) — Login, activation, credential changes
- **Related spec:** [password-confirmation.md](password-confirmation.md) (#22) — Re-authentication gate

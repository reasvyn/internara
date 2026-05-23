# Account Recovery

## Purpose

Account recovery restores access to the system when a user cannot log in through
normal authentication. Internara provides **three** recovery mechanisms, each
designed for a different loss scenario:

| Mechanism | When to Use | Who Initiates | Requirements |
|---|---|---|---|
| **Password Reset** | User forgot password but still has email access | User (self-service) | Registered email, access to inbox |
| **Recovery Slip** | User locked out and cannot access email | Admin generates, user redeems | Username, physical delivery of code |
| **Super Admin Recovery** | All super admin accounts inaccessible | Server admin via CLI | Server SSH access, recovery key |

---

## 1. Password Reset (Self-Service)

The standard Laravel password reset flow. Used when the user can still access
their registered email address.

### Flow

```
User clicks "Forgot Password" on login page
  → ForgotPassword Livewire (email input)
    → SendPasswordResetLinkAction::execute(email)
      → Password::sendResetLink()  [Laravel notification]
      → Email sent with signed URL
  → User checks email, clicks link
    → ResetPassword Livewire (token from URL)
      → User enters email + new password
        → ResetPasswordAction::execute(email, token, password)
          → Password::reset()  [Laravel broker]
          → Password updated in DB
          → SmartLogger: password_reset_success
  → Redirect to login
```

### Rate Limiting

| Endpoint | Limit | Decay |
|---|---|---|
| Forgot password form submission | 3 attempts per email+IP | 3600 seconds |
| Password reset form submission | 5 attempts per email+IP | 300 seconds |

### Key Classes

| Class | Location | Purpose |
|---|---|---|
| `ForgotPassword` | `Auth/Livewire/ForgotPassword.php` | Email input form with rate limiting |
| `ResetPassword` | `Auth/Livewire/ResetPassword.php` | New password form with token validation |
| `ForgotPasswordForm` | `Auth/Livewire/Forms/ForgotPasswordForm.php` | Email field with validation |
| `ResetPasswordForm` | `Auth/Livewire/Forms/ResetPasswordForm.php` | Token, email, password fields |
| `SendPasswordResetLinkAction` | `Auth/Actions/SendPasswordResetLinkAction.php` | Sends reset link via Laravel broker |
| `ResetPasswordAction` | `Auth/Actions/ResetPasswordAction.php` | Resets password via Laravel broker |

---

## 2. Recovery Slip (Admin-Mediated)

Used when the user is locked out of their account and cannot access their email.
An admin generates a set of one-time recovery codes and delivers them to the
user offline (in person, via phone, etc.). The user redeems a code to unlock
their account and set a new password.

### Admin Flow

```
Admin → RecoverySlipManager (admin/recovery-slips)
  ├── Search user by name/username/email
  ├── Select user
  ├── Generate Recovery Slip
  │   └── GenerateRecoverySlipAction::execute(user)
  │       ├── 10 random codes (12 chars, uppercase)
  │       ├── Each hashed with Hash::make()
  │       ├── Expiry: 24 hours
  │       └── Logged: recovery_slips_generated
  └── Deliver codes offline (copy/screenshot/verbal)
```

### User Flow

```
User → /recover-account
  ├── Step 1: Enter username
  ├── Step 2: Enter recovery code
  ├── Step 3: Set new password + confirm
  ├── Submit → AccountRecovery Livewire
  │   └── RedeemRecoverySlipAction::execute(username, code, password)
  │       ├── Find user by username
  │       ├── Find unused, non-expired recovery code
  │       ├── Hash::check(code, code_hash)
  │       ├── Update user password
  │       ├── Mark code as used (used_at = now)
  │       └── Logged: recovery_slip_redeemed
  └── Redirect to login
```

### Rate Limiting

| Endpoint | Limit | Decay |
|---|---|---|
| Recovery slip redemption | 3 attempts per username+IP | 300 seconds |

### Key Classes

| Class | Location | Purpose |
|---|---|---|
| `RecoverySlipManager` | `Auth/Livewire/RecoverySlipManager.php` | Admin UI for generating slips |
| `RecoveryCode` | `Auth/Livewire/RecoveryCode.php` | User profile: view/download codes |
| `AccountRecovery` | `Auth/Livewire/AccountRecovery.php` | User-facing code redemption form |
| `AccountRecoveryForm` | `Auth/Livewire/Forms/AccountRecoveryForm.php` | Username, code, password fields |
| `GenerateRecoverySlipAction` | `Auth/Actions/GenerateRecoverySlipAction.php` | Generates 10 codes, stores hashed |
| `RedeemRecoverySlipAction` | `Auth/Actions/RedeemRecoverySlipAction.php` | Validates and redeems a code |
| `AccountRecoveryCode` | `Auth/Models/AccountRecoveryCode.php` | Eloquent model for recovery codes |
| `RecoveryCodeState` | `Auth/Entities/RecoveryCodeState.php` | Value object for code validity |

### Database

**Table:** `account_recovery_codes`

| Column | Type | Purpose |
|---|---|---|
| `user_id` | FK → users(id) | Code owner |
| `code_hash` | varchar(255) | Bcrypt hash of the plaintext code |
| `generated_at` | timestamp | When the batch was generated |
| `used_at` | timestamp | Null = unused; set on redemption |
| `expires_at` | timestamp | Codes invalid after this time (24h) |

Each code is single-use. Multiple codes per user (10 per batch). Codes are
validated via `RecoveryCodeState::isValid()` which checks:
- `used_at === null` (not yet redeemed)
- `expires_at > now()` (not expired)

---

## 3. Super Admin Recovery (CLI)

Used when all super admin accounts are inaccessible (passwords lost, accounts
locked, or the sole super admin has left the institution). This is a server-level
operation requiring SSH access.

### How the Recovery Key Works

When the system is first installed via the setup wizard, two things happen:

1. A **64-character recovery key** is generated and displayed on screen
2. The key is automatically saved to `storage/app/private/.recovery-key`
   with permission `0600` (readable only by the server owner)
3. The key is hashed with `Hash::make()` and stored in the database

### Recovery Commands

```bash
# Automatic recovery (reads key from storage file):
php artisan admin:recover

# Manual recovery (provide key explicitly):
php artisan admin:recover --key=<64-char-recovery-key>

# Reset existing admin password (instead of creating duplicate):
php artisan admin:recover --reset

# Regenerate the storage file from a known key:
php artisan admin:recover --key=<key> --regenerate-file

# Show the recovery key file location:
php artisan admin:recovery-path

# Display the stored recovery key (requires confirmation):
php artisan admin:recovery-show
```

### Flow

```
Server admin SSH into the machine
  → php artisan admin:recover
    ├── Read recovery key from storage/app/private/.recovery-key
    │   (or use --key flag if file is unavailable)
    ├── Hash::check(key, stored_hash)  [validate against DB]
    ├── If valid:
    │   ├── Interactive: enter email for new/existing admin
    │   ├── Set new password
    │   └── Confirmation: type email to confirm
    └── On success:
        ├── SmartLogger: super_admin_recovered
        └── Super admin access restored
```

### Security

| Measure | Detail |
|---|---|
| File permission | `0600` — only readable by server owner |
| Storage location | `storage/app/private/` — not web-accessible |
| DB storage | Hashed with `Hash::make()` — plaintext never stored |
| Auto-save | Key saved during setup finalization |
| Clipboard | Copy button on setup complete screen |
| Audit | Every recovery attempt logged via SmartLogger |

### Key Classes

| Class | Location | Purpose |
|---|---|---|
| `RecoverAdminCommand` | `Admin/Console/Commands/RecoverAdminCommand.php` | CLI interactive recovery |
| `ShowRecoveryPathCommand` | `Admin/Console/Commands/ShowRecoveryPathCommand.php` | Display key file path |
| `ShowRecoveryKeyCommand` | `Admin/Console/Commands/ShowRecoveryKeyCommand.php` | Display key (with confirmation) |
| `RecoverSuperAdminAction` | `Setup/Actions/RecoverSuperAdminAction.php` | Creates/resets super admin |
| `SaveRecoveryKeyAction` | `Admin/Actions/SaveRecoveryKeyAction.php` | Saves key to storage file |
| `ReadRecoveryKeyAction` | `Admin/Actions/ReadRecoveryKeyAction.php` | Reads key from storage file |

---

## Rate Limiting Summary

All auth endpoints are protected by a multi-layer rate limiting system:

```
Global: AuthThrottleMiddleware (30 req/min/IP)
  ├── Login: 5 attempts/60s + auto-lock after 10 failures
  ├── ForgotPassword: 3 attempts/3600s
  ├── ResetPassword: 5 attempts/300s
  ├── ConfirmPassword: 5 attempts/300s
  └── AccountRecovery: 3 attempts/300s
```

---

## References

| Document | Contents |
|---|---|---|
| [Auth Domain](domain/auth.md) | Authentication, RBAC, account lifecycle |
| [Auth API Reference](domain/auth-reference.md) | Complete class listing |
| [Setup Wizard](setup-wizard.md) | Installation, recovery key generation |
| [Post-Setup](post-setup.md) | First admin actions |
| [ERD: Identity & Access](erd/01-auth.md) | Database schema for auth tables |
| [RBAC](rbac.md) | Role and permission system |

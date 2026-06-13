# Account Recovery

> **Last updated:** 2026-06-10

Internara provides three account recovery mechanisms, each designed for a different loss scenario:

| Mechanism | When to Use | Initiator | Requirements |
|-----------|-------------|-----------|--------------|
| **Password Reset** | User forgot password but has email access | User (self-service) | Registered email, inbox access |
| **Recovery Slip** | User locked out, cannot access email | Admin generates, user redeems | Username, offline delivery of codes |
| **Super Admin Recovery** | All super admin accounts inaccessible | Server admin via CLI | SSH access, recovery key |

---

## 1. Password Reset (Self-Service)

Standard Laravel password reset flow.

### Flow

```
User clicks "Forgot Password" on login page
  → ForgotPassword Livewire (email input)
    → SendPasswordResetLinkAction::execute(email)
      → Password::sendResetLink() → Email with signed URL
  → User clicks link
    → ResetPassword Livewire (token from URL)
      → User enters email + new password
        → ResetPasswordAction::execute(email, token, password)
          → Password::reset() → Password updated
          → SmartLogger: password_reset_success
  → Redirect to login
```

### Rate Limiting

| Endpoint | Limit | Decay |
|----------|-------|-------|
| Forgot password form | 3 attempts per email+IP | 3600s |
| Password reset form | 5 attempts per email+IP | 300s |

### Key Classes

| Class | Location | Purpose |
|-------|----------|---------|
| `ForgotPassword` | `Auth/Password/Livewire/ForgotPassword.php` | Email input form |
| `ResetPassword` | `Auth/Password/Livewire/ResetPassword.php` | New password form |
| `SendPasswordResetLinkAction` | `Auth/Password/Actions/SendPasswordResetLinkAction.php` | Sends reset link |
| `ResetPasswordAction` | `Auth/Password/Actions/ResetPasswordAction.php` | Resets password |

---

## 2. Recovery Slip (Admin-Mediated)

Used when user is locked out and cannot access email. Admin generates one-time recovery codes
and delivers them offline.

### Admin Flow

```
Admin → RecoverySlipManager (admin/recovery-slips)
  ├── Search user by name/username/email
  ├── Select user
  ├── Generate Recovery Slip
  │   └── GenerateRecoverySlipAction::execute(user)
  │       ├── 10 random codes (12 chars, uppercase, alphanumeric)
  │       ├── Each hashed with Hash::make()
  │       ├── No expiry (valid indefinitely until used)
  │       └── Logged: recovery_slips_generated
  └── Deliver codes offline (in person, phone, etc.)
```

### User Flow

```
User → /recover-account
  ├── Step 1: Enter username
  ├── Step 2: Enter recovery code
  ├── Step 3: Set new password + confirm
  └── RedeemRecoverySlipAction::execute(username, code, password)
      ├── Find user by username
      ├── Validate code (Hash::check)
      ├── Update password
      ├── Mark code used (last_attempt_at = now)
      └── Logged: recovery_slip_redeemed
```

### Database

**Table:** `api_tokens` (with `token_type = 'account_recovery'`)

| Column | Type | Purpose |
|--------|------|---------|
| `user_id` | FK → users(id) | Code owner |
| `token` | varchar(255) | Bcrypt hash of plaintext code |
| `token_type` | varchar(20) | Always `'account_recovery'` |
| `expires_at` | datetime | Non-expiring = far-future date |
| `last_attempt_at` | datetime | Null = unused; set on redemption |

Each code is single-use. Multiple codes per user (10 per batch). Validity checked via
`RecoveryCodeState::isValid()`: `last_attempt_at === null` AND `expires_at > now`.

### Rate Limiting

| Endpoint | Limit | Decay |
|----------|-------|-------|
| Recovery slip redemption | 3 attempts per username+IP | 300s |

### Key Classes

| Class | Location | Purpose |
|-------|----------|---------|
| `RecoverySlipManager` | `Auth/AccountRecovery/Livewire/RecoverySlipManager.php` | Admin UI for generating slips |
| `AccountRecovery` | `Auth/AccountRecovery/Livewire/AccountRecovery.php` | User code redemption form |
| `GenerateRecoverySlipAction` | `Auth/AccountRecovery/Actions/GenerateRecoverySlipAction.php` | Generates 10 codes |
| `RedeemRecoverySlipAction` | `Auth/AccountRecovery/Actions/RedeemRecoverySlipAction.php` | Validates and redeems code |
| `RecoveryCodeState` | `Auth/AccountRecovery/Entities/RecoveryCodeState.php` | Value object for code validity |

---

## 3. Super Admin Recovery (CLI)

Used when all super admin accounts are inaccessible. Server-level operation requiring SSH access.

### Recovery Key

Generated during initial setup (Step 7):

- **64-character random string**
- Displayed on screen once (copy button available)
- Automatically saved to `storage/app/private/.recovery-key` (permission `0600`)
- Hashed with `Hash::make()` and stored in database
- `SaveRecoveryKeyAction` saves plaintext to file; DB only stores hash

### Commands

```bash
# Automatic recovery (reads key from storage file):
php artisan admin:recover

# Manual recovery (provide key explicitly):
php artisan admin:recover --key=<64-char-key>

# Reset existing admin password instead of creating duplicate:
php artisan admin:recover --reset

# Regenerate storage file from known key:
php artisan admin:recover --key=<key> --regenerate-file

# Show recovery key file path:
php artisan admin:recovery-path

# Display stored recovery key (requires confirmation):
php artisan admin:recovery-show
```

### Flow

```
Server admin SSH into machine
  → php artisan admin:recover
    ├── Read key from .recovery-key (or --key flag)
    ├── Hash::check(key, stored_hash) → validate
    ├── Interactive prompts:
    │   ├── Enter email for new/existing admin
    │   ├── Set new password
    │   └── Confirm: type email
    └── On success: SmartLogger: super_admin_recovered
```

### Security

| Measure | Detail |
|---------|--------|
| File permission | `0600` — server owner only |
| Storage location | `storage/app/private/` — not web-accessible |
| DB storage | Hashed only — plaintext never stored |
| Auto-save | During setup finalization |
| Clipboard | Copy button on setup complete screen |
| Audit | Every recovery attempt logged via SmartLogger |

---

## Rate Limiting Summary

```
Global: AuthThrottleMiddleware (30 req/min/IP)
  ├── Login: 5/60s + auto-lock after 10 failures
  ├── ForgotPassword: 3/3600s
  ├── ResetPassword: 5/300s
  ├── ConfirmPassword: 5/300s
  └── AccountRecovery: 3/300s
```

---

## Key Locations

| Component | Path |
|-----------|------|
| RecoverAdminCommand | `SysAdmin/Console/Commands/RecoverAdminCommand.php` |
| ShowRecoveryPathCommand | `SysAdmin/Console/Commands/ShowRecoveryPathCommand.php` |
| ShowRecoveryKeyCommand | `SysAdmin/Console/Commands/ShowRecoveryKeyCommand.php` |
| RecoverSuperAdminAction | `Auth/SuperAdmin/Actions/RecoverSuperAdminAction.php` |
| SaveRecoveryKeyAction | `User/UserManagement/Actions/SaveRecoveryKeyAction.php` |
| ReadRecoveryKeyAction | `User/UserManagement/Actions/ReadRecoveryKeyAction.php` |
| SuperAdminIntegrityRules | `Auth/SuperAdmin/Entities/SuperAdminIntegrityRules.php` |

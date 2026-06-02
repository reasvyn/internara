# Auth — API Reference
> Last updated: 2026-06-02
> Changes: cleanup: remove AccessManager, UpdateRolePermissionsAction (RBAC role-only)

> **Legend:** ✅ Implemented = code exists | ⏳ Planned = not yet implemented

Total: 38 files — ✅ 38 Implemented

## Actions

| File | Class | Extends | Description |
|---|---|---|---|
| `Auth/Actions/ConfirmPasswordAction.php` | `ConfirmPasswordAction` | `BaseAction` | Validates user's current password |
| `Auth/Actions/DetectUserAccountCloneAction.php` | `DetectUserAccountCloneAction` | `BaseAction` | Detects potential duplicate/cloned user accounts |
| `Auth/Actions/GenerateRecoverySlipAction.php` | `GenerateRecoverySlipAction` | `BaseAction` | Generates 10 recovery codes (no expiry, single-use each) |
| `Auth/Actions/LockUserAccountAction.php` | `LockUserAccountAction` | `BaseAction` | Locks a user account with reason (blocks super_admin) |
| `Auth/Actions/LoginAction.php` | `LoginAction` | `BaseAction` | Authenticates user with credentials |
| `Auth/Actions/RedeemRecoverySlipAction.php` | `RedeemRecoverySlipAction` | `BaseAction` | Redeems a recovery slip to reset password |
| `Auth/Actions/ResetPasswordAction.php` | `ResetPasswordAction` | `BaseAction` | Resets password using token |
| `Auth/Actions/ResetUserPasswordAction.php` | `ResetUserPasswordAction` | `BaseAction` | Admin-initiated password reset |
| `Auth/Actions/SendPasswordResetLinkAction.php` | `SendPasswordResetLinkAction` | `BaseAction` | Sends password reset link email |
| `Auth/Actions/UnlockUserAccountAction.php` | `UnlockUserAccountAction` | `BaseAction` | Unlocks a previously locked user account |
| `Auth/Actions/UpdateUserPasswordAction.php` | `UpdateUserPasswordAction` | `BaseAction` | Updates user's password with current password validation |

## Entities

| File | Class | Extends | Description |
|---|---|---|---|
| `Auth/Entities/Apprentice.php` | `Apprentice` | `BaseEntity` | Read-only DTO for user/student profile info |
| `Auth/Entities/RecoveryCodeState.php` | `RecoveryCodeState` | `BaseEntity` | Read-only DTO for recovery code state |
| `Auth/Entities/SuperAdminIntegrityRules.php` | `SuperAdminIntegrityRules` | `BaseEntity` | Enforces super admin invariants: fixed name/username, immutability, deletion/lock protection |

## Enums

| File | Class | Implements | Description |
|---|---|---|---|
| `Auth/Enums/AccountStatus.php` | `AccountStatus` | `ColorableEnum`, `StatusEnum` | User account status states |
| `Auth/Enums/Role.php` | `Role` | `LabelEnum` | System roles (super_admin, admin, teacher, student, supervisor) + functional roles (mentor, mentee) |

## Middleware

| File | Class | Description |
|---|---|---|
| `Auth/Http/Middleware/CheckRoleMiddleware.php` | `CheckRoleMiddleware` | Middleware that checks user has required role |
| `Auth/Http/Middleware/AuthThrottleMiddleware.php` | `AuthThrottleMiddleware` | Global rate limit (30 req/min/IP) for all auth endpoints |

## Form Requests

| File | Class | Extends | Description |
|---|---|---|---|
| `Auth/Http/Requests/RoleRequest.php` | `RoleRequest` | `FormRequest` | Validation for role/permission operations |

## Livewire Components

| File | Class | Extends | Description |
|---|---|---|---|
| `Auth/Livewire/AccountLifecycleManager.php` | `AccountLifecycleManager` | `Component` | Manages account lock/unlock and clone detection |
| `Auth/Livewire/AccountRecovery.php` | `AccountRecovery` | `Component` | Account recovery via recovery slip |
| `Auth/Livewire/ConfirmPassword.php` | `ConfirmPassword` | `Component` | Password confirmation form |
| `Auth/Livewire/ForgotPassword.php` | `ForgotPassword` | `Component` | Forgot password form with rate limiting |
| `Auth/Livewire/Login.php` | `Login` | `Component` | Login form with rate limiting |
| `Auth/Livewire/RecoveryCode.php` | `RecoveryCode` | `Component` | Generates and displays recovery code PDF |
| `Auth/Livewire/RecoverySlipManager.php` | `RecoverySlipManager` | `Component` | Manages recovery slip generation |
| `Auth/Livewire/ResetPassword.php` | `ResetPassword` | `Component` | Password reset form |
| `Auth/Livewire/ActivateAccount.php` | `ActivateAccount` | `Component` | Account activation via token |

### Livewire Form Objects

| File | Class | Extends | Fields | Used By |
|---|---|---|---|---|
| `Auth/Livewire/Forms/LoginForm.php` | `LoginForm` | `Form` | identifier, password, remember | `Login` |
| `Auth/Livewire/Forms/ForgotPasswordForm.php` | `ForgotPasswordForm` | `Form` | email | `ForgotPassword` |
| `Auth/Livewire/Forms/ResetPasswordForm.php` | `ResetPasswordForm` | `Form` | token, email, password, password_confirmation | `ResetPassword` |
| `Auth/Livewire/Forms/ConfirmPasswordForm.php` | `ConfirmPasswordForm` | `Form` | password | `ConfirmPassword` |
| `Auth/Livewire/Forms/AccountRecoveryForm.php` | `AccountRecoveryForm` | `Form` | username, recoveryCode, password, password_confirmation | `AccountRecovery` |

## Models

| File | Class | Extends | Description |
|---|---|---|---|
| `Auth/Models/AccountRecoveryCode.php` | `AccountRecoveryCode` | `BaseModel` | Eloquent model for recovery codes |
| `Auth/Models/ActivationToken.php` | `ActivationToken` | `BaseModel` | Eloquent model for account activation tokens |

## Notifications

| File | Class | Extends/Implements | Description |
|---|---|---|---|
| `Auth/Notifications/AccountStatusNotification.php` | `AccountStatusNotification` | `Notification`, `ShouldQueue` | Queued notification for account status changes |
| `Auth/Notifications/SuperAdminRecoveredNotification.php` | `SuperAdminRecoveredNotification` | `Notification`, `ShouldQueue` | Queued notification for super admin recovery |
| `Auth/Notifications/WelcomeNotification.php` | `WelcomeNotification` | `Notification`, `ShouldQueue` | Queued welcome notification for new users |

## Policies

Auth does not own policies — `UserPolicy` lives in the [User domain](user-reference.md).

## Where to Find It

- `app/Domain/Auth/Enums/AccountStatus.php` — 8-state account lifecycle
- `app/Domain/Auth/Enums/Role.php` — role definitions with functional mapping
- `app/Domain/Auth/Entities/Apprentice.php` — account access checks
- `app/Domain/Auth/Entities/SuperAdminIntegrityRules.php` — super admin invariants
- `app/Domain/Auth/Actions/LoginAction.php` — 5-step authentication
- `app/Domain/Auth/Actions/LockUserAccountAction.php` — atomic lock with super admin protection
- `app/Domain/Auth/Http/Middleware/AuthThrottleMiddleware.php` — per-endpoint rate limiting
- `app/Domain/Auth/Http/Middleware/CheckRoleMiddleware.php` — route-level role gating
- `app/Domain/Auth/Notifications/` — notification classes
- `app/Domain/Auth/Livewire/` — auth UI components
- `docs/rbac.md` — RBAC design and role hierarchy
- `docs/account-recovery.md` — recovery mechanisms

## Dependency Graph

```
Auth Domain
├── Core      → BaseAction, BaseEntity, CacheKeys, SmartLogger, PasswordRules,
│                HandlesActionErrors, LabelEnum, StatusEnum, ColorableEnum
├── User      → User model (subject of authentication), Profile accessed
│                for identity confirmation during recovery
├── Setup     → SetupSuperAdminAction (recovery flow), FinalizeSetupAction
└── Admin     → AdminPromoteCommand (super admin promotion)
```

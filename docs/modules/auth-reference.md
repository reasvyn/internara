# Auth — Technical Reference

> Last updated: 2026-06-10

Detailed structural and implementation reference for the **Auth** module.

---

## Overview

Handles authentication: login, password management, account activation, account recovery, and RBAC permissions.

### Submodules

- `Login`
- `Password`
- `Account` (user account activation, migrated from `ActivationToken`)
- `ApiTokens` (general token management, `api_tokens` table)
- `AccountRecovery`
- `Permissions`
- `SuperAdmin`

---

## Actions

| File | Class | Extends |
| ---- | ----- | ------- |
| `Login/Actions/LoginAction.php` | `LoginAction` | `BaseAction` |
| `Password/Actions/ConfirmPasswordAction.php` | `ConfirmPasswordAction` | `BaseAction` |
| `Password/Actions/ResetPasswordAction.php` | `ResetPasswordAction` | `BaseAction` |
| `Password/Actions/ResetUserPasswordAction.php` | `ResetUserPasswordAction` | `BaseAction` |
| `Password/Actions/SendPasswordResetLinkAction.php` | `SendPasswordResetLinkAction` | `BaseAction` |
| `Password/Actions/UpdateUserPasswordAction.php` | `UpdateUserPasswordAction` | `BaseAction` |
| `AccountRecovery/Actions/GenerateRecoverySlipAction.php` | `GenerateRecoverySlipAction` | `BaseAction` |
| `AccountRecovery/Actions/RedeemRecoverySlipAction.php` | `RedeemRecoverySlipAction` | `BaseAction` |
| `SuperAdmin/Actions/InitializeSuperAdminAction.php` | `InitializeSuperAdminAction` | `BaseAction` |
| `SuperAdmin/Actions/RecoverSuperAdminAction.php` | `RecoverSuperAdminAction` | `BaseAction` |

---

## Models

| File | Class | Extends |
| ---- | ----- | ------- |
| `ApiTokens/Models/ApiToken.php` | `ApiToken` | `BaseModel` |

---

## Enums

| File | Enum | Implements | Values |
| ---- | ---- | ---------- | ------ |
| `Permissions/Enums/Role.php` | `Role` | `LabelEnum` | super_admin, admin, teacher, supervisor, student, func_mentor, func_mentee |

---

## Entities

| File | Class | Extends |
| ---- | ----- | ------- |
| `Account/Entities/AccountActivation.php` | `AccountActivation` | `BaseEntity` |
| `AccountRecovery/Entities/RecoveryCodeState.php` | `RecoveryCodeState` | `BaseEntity` |
| `SuperAdmin/Entities/SuperAdminIntegrityRules.php` | `SuperAdminIntegrityRules` | `BaseEntity` |
| `ApiTokens/Entities/ActivationToken.php` | `ActivationToken` | `BaseEntity` |
| `ApiTokens/Entities/ApiTokenState.php` | `ApiTokenState` | `BaseEntity` |

---

## Policies

| File | Policy | Extends |
| ---- | ------ | ------- |
| `Permissions/Policies/UserPolicy.php` | `UserPolicy` | `BasePolicy` |

---

## Data / DTOs

| File | Class | Extends |
| ---- | ----- | ------- |
| `Login/Data/LoginData.php` | `LoginData` | `BaseData` |
| `AccountRecovery/Data/RecoveryCodeData.php` | `RecoveryCodeData` | `BaseData` |

## Events

| File | Class | Dispatched By |
| ---- | ----- | ------------- |
| `Login/Events/LoginSucceeded.php` | `LoginSucceeded` | `LoginAction` |
| `Login/Events/LoginFailed.php` | `LoginFailed` | `LoginAction` |
| `Password/Events/PasswordUpdated.php` | `PasswordUpdated` | `UpdateUserPasswordAction` |

## Listeners

| File | Class | Listens To |
| ---- | ----- | ---------- |
| `Login/Listeners/SendSuperAdminWelcomeNotification.php` | `SendSuperAdminWelcomeNotification` | `LoginSucceeded` |

## Livewire Components

| File | Component | Extends |
| ---- | --------- | ------- |
| `Login/Livewire/Login.php` | `Login` | `Component` |
| `Password/Livewire/ForgotPassword.php` | `ForgotPassword` | `Component` |
| `Password/Livewire/ResetPassword.php` | `ResetPassword` | `Component` |
| `Password/Livewire/ConfirmPassword.php` | `ConfirmPassword` | `Component` |
| `Account/Livewire/ActivateAccount.php` | `ActivateAccount` | `Component` |
| `AccountRecovery/Livewire/AccountRecovery.php` | `AccountRecovery` | `Component` |
| `AccountRecovery/Livewire/RecoveryCode.php` | `RecoveryCode` | `Component` |
| `AccountRecovery/Livewire/RecoverySlipManager.php` | `RecoverySlipManager` | `Component` |

## Notifications

| File | Class | Purpose |
| ---- | ----- | ------- |
| `SuperAdmin/Notifications/SuperAdminRecoveredNotification.php` | `SuperAdminRecoveredNotification` | Notifies admins on recovery |
| `SuperAdmin/Notifications/RecoveryOtpNotification.php` | `RecoveryOtpNotification` | Notifies on recovery OTP |

## Livewire Forms

| File | Form |
| ---- | ---- |
| `Login/Livewire/Forms/LoginForm.php` | `LoginForm` |
| `Password/Livewire/Forms/ConfirmPasswordForm.php` | `ConfirmPasswordForm` |
| `Password/Livewire/Forms/ForgotPasswordForm.php` | `ForgotPasswordForm` |
| `Password/Livewire/Forms/ResetPasswordForm.php` | `ResetPasswordForm` |
| `AccountRecovery/Livewire/Forms/AccountRecoveryForm.php` | `AccountRecoveryForm` |

## Middleware

| File | Middleware | Purpose |
| ---- | ---------- | ------- |
| `Login/Http/Middleware/AuthThrottleMiddleware.php` | `AuthThrottleMiddleware` | Rate-limits login attempts |
| `Permissions/Http/Middleware/CheckRoleMiddleware.php` | `CheckRoleMiddleware` | Route-level role gate |

## Form Requests

| File | Request | Purpose |
| ---- | ------- | ------- |
| `Permissions/Http/Requests/RoleRequest.php` | `RoleRequest` | Role assignment validation |

---

## Routes

File: `routes/web/auth.php`
Naming pattern: `auth.{resource}.{action}`

## Views

Views are located in `resources/views/auth/`. See [UI/UX](../foundation/ui-ux.md) for the design system.

## Tests

Tests are located in `tests/{Feature,Unit}/Auth/`. See [Testing](../infrastructure/testing.md) for the testing conventions.

## Factories

None.

## Migrations

| Migration | Table |
| --------- | ----- |
| `create_api_tokens_table` | `api_tokens` |
| `create_permission_tables` | `permissions` |

---

## File Organization

```
app/Auth/
├── Account/
│   ├── Entities/AccountActivation.php
│   └── Livewire/ActivateAccount.php
├── AccountRecovery/
│   ├── Actions/
│   │   ├── GenerateRecoverySlipAction.php
│   │   └── RedeemRecoverySlipAction.php
│   ├── Data/RecoveryCodeData.php
│   ├── Entities/RecoveryCodeState.php
│   └── Livewire/
│       ├── Forms/AccountRecoveryForm.php
│       ├── AccountRecovery.php
│       ├── RecoveryCode.php
│       └── RecoverySlipManager.php
├── ApiTokens/
│   ├── Entities/ActivationToken.php
│   └── Models/ApiToken.php
├── Login/
│   ├── Actions/LoginAction.php
│   ├── Data/LoginData.php
│   ├── Events/
│   │   ├── LoginFailed.php
│   │   └── LoginSucceeded.php
│   ├── Http/Middleware/AuthThrottleMiddleware.php
│   └── Livewire/
│       ├── Forms/LoginForm.php
│       └── Login.php
├── Password/
│   ├── Actions/
│   │   ├── ConfirmPasswordAction.php
│   │   ├── ResetPasswordAction.php
│   │   ├── ResetUserPasswordAction.php
│   │   ├── SendPasswordResetLinkAction.php
│   │   └── UpdateUserPasswordAction.php
│   └── Livewire/
│       ├── Forms/
│       │   ├── ConfirmPasswordForm.php
│       │   ├── ForgotPasswordForm.php
│       │   └── ResetPasswordForm.php
│       ├── ConfirmPassword.php
│       ├── ForgotPassword.php
│       └── ResetPassword.php
├── Permissions/
│   ├── Enums/Role.php
│   ├── Http/
│   │   ├── Middleware/CheckRoleMiddleware.php
│   │   └── Requests/RoleRequest.php
│   └── Policies/UserPolicy.php
└── SuperAdmin/
    ├── Actions/
    │   ├── InitializeSuperAdminAction.php
    │   └── RecoverSuperAdminAction.php
    ├── Entities/SuperAdminIntegrityRules.php
    └── Notifications/SuperAdminRecoveredNotification.php
```

---

## Architectural Integration

- **Submodules**: `Login`, `Password`, `Account`, `ApiTokens`, `AccountRecovery`, `Permissions`, `SuperAdmin`
- **Business Logic**: `app/Auth/`
- **Routing**: `routes/web/auth.php`
- **Views**: `resources/views/auth/`
- **Testing**: `tests/Feature/Auth/`, `tests/Unit/Auth/`
- **Dependencies**: Core, User

*For overview and business context, see [auth.md](auth.md).*

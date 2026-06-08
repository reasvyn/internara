# Auth — Technical Reference

> Last updated: 2026-06-08

Detailed structural and implementation reference for the **Auth** module.

---

## Overview

Handles authentication: login, password management, account activation, account recovery, and RBAC permissions.

### Submodules

- `Login`
- `Password`
- `ActivationToken`
- `AccountRecovery`
- `Permissions`

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

---

## Models

| File | Class | Extends |
| ---- | ----- | ------- |
| `ActivationToken/Models/ActivationToken.php` | `ActivationToken` | `BaseModel` |
| `AccountRecovery/Models/AccountRecoveryCode.php` | `AccountRecoveryCode` | `BaseModel` |

---

## Enums

| File | Enum | Implements | Values |
| ---- | ---- | ---------- | ------ |
| `Permissions/Enums/Role.php` | `Role` | `LabelEnum` | super_admin, admin, teacher, supervisor, student |

---

## Entities

| File | Class | Extends |
| ---- | ----- | ------- |
| `AccountRecovery/Entities/RecoveryCodeState.php` | `RecoveryCodeState` | `BaseEntity` |

---

## Policies

| File | Policy | Extends |
| ---- | ------ | ------- |
| `Permissions/Policies/UserPolicy.php` | `UserPolicy` | `BasePolicy` |

---

## Livewire Components

| File | Component | Extends |
| ---- | --------- | ------- |
| `Login/Livewire/Login.php` | `Login` | `Component` |
| `Password/Livewire/ForgotPassword.php` | `ForgotPassword` | `Component` |
| `Password/Livewire/ResetPassword.php` | `ResetPassword` | `Component` |
| `Password/Livewire/ConfirmPassword.php` | `ConfirmPassword` | `Component` |
| `ActivationToken/Livewire/ActivateAccount.php` | `ActivateAccount` | `Component` |
| `AccountRecovery/Livewire/AccountRecovery.php` | `AccountRecovery` | `Component` |
| `AccountRecovery/Livewire/RecoveryCode.php` | `RecoveryCode` | `Component` |
| `AccountRecovery/Livewire/RecoverySlipManager.php` | `RecoverySlipManager` | `Component` |

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

---

## File Organization

```
app/Auth/
├── Login/
│   ├── Actions/LoginAction.php
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
├── ActivationToken/
│   ├── Livewire/ActivateAccount.php
│   └── Models/ActivationToken.php
├── AccountRecovery/
│   ├── Actions/
│   │   ├── GenerateRecoverySlipAction.php
│   │   └── RedeemRecoverySlipAction.php
│   ├── Entities/RecoveryCodeState.php
│   ├── Livewire/
│   │   ├── Forms/AccountRecoveryForm.php
│   │   ├── AccountRecovery.php
│   │   ├── RecoveryCode.php
│   │   └── RecoverySlipManager.php
│   └── Models/AccountRecoveryCode.php
└── Permissions/
    ├── Enums/Role.php
    ├── Http/
    │   ├── Middleware/CheckRoleMiddleware.php
    │   └── Requests/RoleRequest.php
    └── Policies/UserPolicy.php
```

---

## Architectural Integration

- **Submodules**: `Login`, `Password`, `ActivationToken`, `AccountRecovery`, `Permissions`
- **Business Logic**: `app/Auth/`
- **Routing**: `routes/web/auth.php`
- **Views**: `resources/views/auth/`
- **Testing**: `tests/Feature/Auth/`, `tests/Unit/Auth/`
- **Dependencies**: Core, User

*For overview and business context, see [auth.md](auth.md).*

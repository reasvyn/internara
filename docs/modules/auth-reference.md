# Auth — Technical Reference

> Last updated: 2026-06-06
> Changes: refactor: extract Auth from User module into standalone Auth module

Detailed structural and implementation reference for the **Auth** module.

---

## Overview

Handles authentication, authorization, account activation, password management, recovery, and super admin integrity

### Module Statistics
- **Actions**: 13 business logic operations
- **Models**: 2 data entities
- **Livewire Components**: 7 UI components
- **Policies**: 1 authorization rules
- **Submodules**: 6 module submodules

### Submodules
- `Permissions`
- `SuperAdmin`
- `Login`
- `ActivationToken`
- `AccountRecovery`
- `Password`

---

## Dependency Graph

This module depends on:
- **Core**
- **User**

---

## Actions

| File | Class | Extends |
|---|---|---|
| `AccountRecovery/Actions/GenerateRecoverySlipAction.php` | `GenerateRecoverySlipAction` | `BaseAction` |
| `SuperAdmin/Actions/InitializeSuperAdminAction.php` | `InitializeSuperAdminAction` | `BaseAction` |
| `Login/Actions/LoginAction.php` | `LoginAction` | `BaseAction` |
| `SuperAdmin/Actions/RecoverSuperAdminAction.php` | `RecoverSuperAdminAction` | `BaseAction` |
| `AccountRecovery/Actions/RedeemRecoverySlipAction.php` | `RedeemRecoverySlipAction` | `BaseAction` |
| `Password/Actions/ResetPasswordAction.php` | `ResetPasswordAction` | `BaseAction` |
| `Password/Actions/ResetUserPasswordAction.php` | `ResetUserPasswordAction` | `BaseAction` |
| `Password/Actions/SendPasswordResetLinkAction.php` | `SendPasswordResetLinkAction` | `BaseAction` |
| `Password/Actions/UpdateUserPasswordAction.php` | `UpdateUserPasswordAction` | `BaseAction` |
| `Password/Actions/ConfirmPasswordAction.php` | `ConfirmPasswordAction` | `BaseAction` |

---

## Models

| File | Class |
|---|---|
| `ActivationToken/Models/ActivationToken.php` | `ActivationToken` |
| `AccountRecovery/Models/AccountRecoveryCode.php` | `AccountRecoveryCode` |

---

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `AccountRecovery/Livewire/AccountRecovery.php` | `AccountRecovery` | `Component` |
| `ActivationToken/Livewire/ActivateAccount.php` | `ActivateAccount` | `Component` |
| `Password/Livewire/ConfirmPassword.php` | `ConfirmPassword` | `Component` |
| `Password/Livewire/ForgotPassword.php` | `ForgotPassword` | `Component` |
| `Login/Livewire/Login.php` | `Login` | `Component` |
| `AccountRecovery/Livewire/RecoveryCode.php` | `RecoveryCode` | `Component` |
| `AccountRecovery/Livewire/RecoverySlipManager.php` | `RecoverySlipManager` | `Component` |
| `Password/Livewire/ResetPassword.php` | `ResetPassword` | `Component` |

---

## Authorization Policies

| File | Policy |
|---|---|
| `Permissions/Policies/UserPolicy.php` | `UserPolicy` |

---

## Enums

| File | Class | Type |
|---|---|---|
| `Permissions/Enums/Role.php` | `Role` | String-backed, `LabelEnum` |

---

## HTTP Middleware

| File | Middleware | Alias |
|---|---|---|
| `Permissions/Http/Middleware/CheckRoleMiddleware.php` | `CheckRoleMiddleware` | `role` |
| `Login/Http/Middleware/AuthThrottleMiddleware.php` | `AuthThrottleMiddleware` | `auth.throttle` |

---

## File Organization

```
app/Auth/
├── {SubModule}/
│   ├── Actions/
│   ├── Models/
│   ├── Entities/
│   ├── Livewire/
│   │   └── Forms/
│   ├── Http/
│   │   ├── Middleware/
│   │   └── Requests/
│   ├── Policies/
│   └── Notifications/
├── Types/
└── Support/
```

---

## Architectural Integration

This module integrates with the system across the following directories and resources:

- **Submodules**: `Permissions`, `SuperAdmin`, `Login`, `ActivationToken`, `AccountRecovery`, `Password`
- **Business Logic (`app/`)**: Located in [app/Auth/](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/app/Auth/)
- **Routing (`routes/`)**: [routes/web/auth.php](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/routes/web/auth.php)
- **Views (`views/`)**: Blade templates and layouts are in [resources/views/auth/](file:///home/reasnovynt/Projects/Dev/reasvyn/internara/resources/views/auth/)
- **Testing (`tests/`)**: Feature `tests/Feature/Auth/`, Unit `tests/Unit/Auth/`


*For overview and business context, see [auth.md](auth.md)*

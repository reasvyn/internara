# Auth — Technical Reference

## Description

Detailed structural and implementation reference for the **Auth** module.

---

## Overview

Handles authentication: login, password management, account activation, account recovery, and RBAC
permissions.

## Actions

| File | Class | Extends |
|---|---|---|
| `Domain/Account/Actions/ActivateAccountAction.php` | `ActivateAccountAction` | `BaseCommandAction` |
| `Domain/AccountRecovery/Actions/GenerateRecoverySlipAction.php` | `GenerateRecoverySlipAction` | `BaseCommandAction` |
| `Domain/AccountRecovery/Actions/RedeemRecoverySlipAction.php` | `RedeemRecoverySlipAction` | `BaseCommandAction` |
| `Domain/Login/Actions/LoginAction.php` | `LoginAction` | `BaseCommandAction` |
| `Domain/Password/Actions/ConfirmPasswordAction.php` | `ConfirmPasswordAction` | `BaseCommandAction` |
| `Domain/Password/Actions/ResetPasswordAction.php` | `ResetPasswordAction` | `BaseCommandAction` |
| `Domain/Password/Actions/SendPasswordResetLinkAction.php` | `SendPasswordResetLinkAction` | `BaseCommandAction` |
| `Domain/Password/Actions/UpdateUserPasswordAction.php` | `UpdateUserPasswordAction` | `BaseCommandAction` |
| `Domain/SuperAdmin/Actions/InitializeSuperAdminAction.php` | `InitializeSuperAdminAction` | `BaseCommandAction` |
| `Domain/SuperAdmin/Actions/RecoverSuperAdminAction.php` | `RecoverSuperAdminAction` | `BaseCommandAction` |

## Models

| File | Class | Extends |
|---|---|---|
| `Domain/AccessTokens/Models/AccessToken.php` | `AccessToken` | `BaseModel` |

## Enums

| File | Enum | Implements | Values |
|---|---|---|---|
| `Domain/Permissions/Enums/Role.php` | `Role` | `LabelEnum` | — |

## Policies

| File | Policy | Extends |
|---|---|---|
| `Domain/Permissions/Policies/UserPolicy.php` | `UserPolicy` | `BasePolicy` |

## Data / DTOs

| File | Class | Extends |
|---|---|---|
| `Domain/Account/Data/ActivateAccountData.php` | `ActivateAccountData` | `BaseData` |
| `Domain/AccountRecovery/Data/RecoveryCodeData.php` | `RecoveryCodeData` | `BaseData` |
| `Domain/AccountRecovery/Data/RedeemRecoverySlipData.php` | `RedeemRecoverySlipData` | `BaseData` |
| `Domain/Login/Data/LoginData.php` | `LoginData` | `BaseData` |
| `Domain/Password/Data/ResetPasswordData.php` | `ResetPasswordData` | `BaseData` |

## Events

| File | Event | Extends |
|---|---|---|
| `Domain/AccountRecovery/Events/RecoverySlipGenerated.php` | `RecoverySlipGenerated` | `BaseEvent` |
| `Domain/Login/Events/LoginFailed.php` | `LoginFailed` | `BaseEvent` |
| `Domain/Login/Events/LoginSucceeded.php` | `LoginSucceeded` | `BaseEvent` |
| `Domain/Password/Events/PasswordUpdated.php` | `PasswordUpdated` | `BaseEvent` |
| `Domain/SuperAdmin/Events/SuperAdminRecovered.php` | `SuperAdminRecovered` | `BaseEvent` |

## Listeners

| File | Listener | Listens To |
|---|---|---|
| `Domain/Login/Listeners/LogLoginFailed.php` | `LogLoginFailed` | — |
| `Domain/Login/Listeners/SendRoleWelcomeNotification.php` | `SendRoleWelcomeNotification` | — |
| `Domain/Password/Listeners/InvalidateSessionOnPasswordChange.php` | `InvalidateSessionOnPasswordChange` | — |
| `Domain/Password/Listeners/SendPasswordChangedMail.php` | `SendPasswordChangedMail` | — |
| `Domain/SuperAdmin/Listeners/NotifySuperAdminsOfRecovery.php` | `NotifySuperAdminsOfRecovery` | — |

## Livewire Components

| File | Component | Extends |
|---|---|---|
| `Domain/Account/Livewire/ActivateAccount.php` | `ActivateAccount` | `Component` |
| `Domain/AccountRecovery/Livewire/AccountRecovery.php` | `AccountRecovery` | `Component` |
| `Domain/AccountRecovery/Livewire/Forms/AccountRecoveryForm.php` | `AccountRecoveryForm` | `BaseFormView` |
| `Domain/AccountRecovery/Livewire/RecoveryCode.php` | `RecoveryCode` | `Component` |
| `Domain/AccountRecovery/Livewire/RecoverySlipManager.php` | `RecoverySlipManager` | `BaseRecordManager` |
| `Domain/Login/Livewire/Forms/LoginForm.php` | `LoginForm` | `BaseFormView` |
| `Domain/Login/Livewire/Login.php` | `Login` | `Component` |
| `Domain/Password/Livewire/ConfirmPassword.php` | `ConfirmPassword` | `Component` |
| `Domain/Password/Livewire/ForgotPassword.php` | `ForgotPassword` | `Component` |
| `Domain/Password/Livewire/Forms/ConfirmPasswordForm.php` | `ConfirmPasswordForm` | `BaseFormView` |
| `Domain/Password/Livewire/Forms/ForgotPasswordForm.php` | `ForgotPasswordForm` | `BaseFormView` |
| `Domain/Password/Livewire/Forms/ResetPasswordForm.php` | `ResetPasswordForm` | `BaseFormView` |
| `Domain/Password/Livewire/ResetPassword.php` | `ResetPassword` | `Component` |

## Notifications

| File                                                                | Class                             | Purpose                                 |
| --------------------------------------------------------------------- | --------------------------------- | --------------------------------------- |
| `Domain/SuperAdmin/Notifications/SuperAdminRecoveredNotification.php` | `SuperAdminRecoveredNotification` | Notifies admins on recovery             |
| `Domain/SuperAdmin/Notifications/RecoveryOtpNotification.php`       | `RecoveryOtpNotification`         | Notifies on recovery OTP                |
| `Notifications/CredentialChangedNotification.php`                   | `CredentialChangedNotification`   | Email on password/email/username change |

## Middleware

| File                                                          | Middleware               | Purpose                    |
| ------------------------------------------------------------- | ------------------------ | -------------------------- |
| `Domain/Login/Http/Middleware/AuthThrottleMiddleware.php`    | `AuthThrottleMiddleware` | Rate-limits login attempts |
| `Domain/Permissions/Http/Middleware/CheckRoleMiddleware.php` | `CheckRoleMiddleware`    | Route-level role gate      |

## Form Requests

| File | Request | Purpose |
|---|---|---|
| `Domain/Permissions/Http/Requests/RoleRequest.php` | `RoleRequest` | — |

## Routes

File: `routes/web/auth.php` Named routes: `login`, `activate` (password/recovery routes live in `routes/web/user.php`)

## Views

Views are located in `resources/views/auth/`. See [UI/UX](../../guides/ui-ux.md) for the design
system.

## Tests

Tests are located in `tests/Auth/`. See [Testing](../../guides/infra/testing.md) for
the testing conventions.

## Factories

None.

## Console Commands

| Command Signature | Class                 | Description                          |
| ----------------- | --------------------- | ------------------------------------ |
| `admin:create`    | `CreateAdminCommand`  | Creates initial superadmin           |

## Migrations

| Migration                    | Table           |
| ---------------------------- | --------------- |
| `create_access_tokens_table` | `access_tokens` |
| `create_permission_tables`   | `permissions`   |

---

## Architectural Integration

- **Submodules**: `Login`, `Password`, `Account`, `AccessTokens`, `AccountRecovery`, `Permissions`,
  `SuperAdmin`
- **Business Logic**: `app/Modules/Auth/`
- **Routing**: `routes/web/auth.php`
- **Views**: `resources/views/auth/`
- **Testing**: `tests/Auth/`
- **Dependencies**: Core, User

_For overview and business context, see [auth.md](auth.md)._

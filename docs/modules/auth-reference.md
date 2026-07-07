# Auth — Technical Reference

> **Last updated:** 2026-07-06
>
> **Changes:** sync — add SendPasswordChangedMail, InvalidateSessionOnPasswordChange listeners; add
> CredentialChangedNotification

## Description

Detailed structural and implementation reference for the **Auth** module.

---

## Overview

Handles authentication: login, password management, account activation, account recovery, and RBAC
permissions.

## Actions

| File                                                     | Class                         | Extends             |
| -------------------------------------------------------- | ----------------------------- | ------------------- |
| `Login/Actions/LoginAction.php`                          | `LoginAction`                 | `BaseCommandAction` |
| `Password/Actions/ConfirmPasswordAction.php`             | `ConfirmPasswordAction`       | `BaseCommandAction` |
| `Password/Actions/ResetPasswordAction.php`               | `ResetPasswordAction`         | `BaseCommandAction` |
| `Password/Actions/ResetUserPasswordAction.php`           | `ResetUserPasswordAction`     | `BaseCommandAction` |
| `Password/Actions/SendPasswordResetLinkAction.php`       | `SendPasswordResetLinkAction` | `BaseCommandAction` |
| `Password/Actions/UpdateUserPasswordAction.php`          | `UpdateUserPasswordAction`    | `BaseCommandAction` |
| `Account/Actions/ActivateAccountAction.php`              | `ActivateAccountAction`       | `BaseCommandAction` |
| `AccountRecovery/Actions/GenerateRecoverySlipAction.php` | `GenerateRecoverySlipAction`  | `BaseCommandAction` |
| `AccountRecovery/Actions/RedeemRecoverySlipAction.php`   | `RedeemRecoverySlipAction`    | `BaseCommandAction` |
| `SuperAdmin/Actions/InitializeSuperAdminAction.php`      | `InitializeSuperAdminAction`  | `BaseCommandAction` |
| `SuperAdmin/Actions/RecoverSuperAdminAction.php`         | `RecoverSuperAdminAction`     | `BaseCommandAction` |

---

## Models

| File                                  | Class         | Extends     |
| ------------------------------------- | ------------- | ----------- |
| `AccessTokens/Models/AccessToken.php` | `AccessToken` | `BaseModel` |

---

## Enums

| File                         | Enum   | Implements  | Values                                                                     |
| ---------------------------- | ------ | ----------- | -------------------------------------------------------------------------- |
| `Permissions/Enums/Role.php` | `Role` | `LabelEnum` | super_admin, admin, teacher, supervisor, student, func_mentor, func_mentee |

---

## Entities

| File                                               | Class                      | Extends      |
| -------------------------------------------------- | -------------------------- | ------------ |
| `Account/Entities/AccountActivation.php`           | `AccountActivation`        | `BaseEntity` |
| `AccountRecovery/Entities/RecoveryCodeState.php`   | `RecoveryCodeState`        | `BaseEntity` |
| `SuperAdmin/Entities/SuperAdminIntegrityRules.php` | `SuperAdminIntegrityRules` | `BaseEntity` |
| `AccessTokens/Entities/ActivationToken.php`        | `ActivationToken`          | `BaseEntity` |
| `AccessTokens/Entities/AccessTokenState.php`       | `AccessTokenState`         | `BaseEntity` |

---

## Policies

| File                                  | Policy       | Extends      |
| ------------------------------------- | ------------ | ------------ |
| `Permissions/Policies/UserPolicy.php` | `UserPolicy` | `BasePolicy` |

---

## Data / DTOs

| File                                        | Class              | Extends    |
| ------------------------------------------- | ------------------ | ---------- |
| `Login/Data/LoginData.php`                  | `LoginData`        | `BaseData` |
| `AccountRecovery/Data/RecoveryCodeData.php` | `RecoveryCodeData` | `BaseData` |

## Events

| File                                               | Class                   | Dispatched By                |
| -------------------------------------------------- | ----------------------- | ---------------------------- |
| `Login/Events/LoginSucceeded.php`                  | `LoginSucceeded`        | `LoginAction`                |
| `Login/Events/LoginFailed.php`                     | `LoginFailed`           | `LoginAction`                |
| `AccountRecovery/Events/RecoverySlipGenerated.php` | `RecoverySlipGenerated` | `GenerateRecoverySlipAction` |
| `SuperAdmin/Events/SuperAdminRecovered.php`        | `SuperAdminRecovered`   | `RecoverSuperAdminAction`    |
| `Password/Events/PasswordUpdated.php`              | `PasswordUpdated`       | `UpdateUserPasswordAction`   |

## Listeners

| File                                                       | Class                               | Listens To            |
| ---------------------------------------------------------- | ----------------------------------- | --------------------- |
| `Login/Listeners/SendRoleWelcomeNotification.php`          | `SendRoleWelcomeNotification`       | `LoginSucceeded`      |
| `Login/Listeners/LogLoginFailed.php`                       | `LogLoginFailed`                    | `LoginFailed`         |
| `Password/Listeners/InvalidateSessionOnPasswordChange.php` | `InvalidateSessionOnPasswordChange` | `PasswordUpdated`     |
| `Password/Listeners/SendPasswordChangedMail.php`           | `SendPasswordChangedMail`           | `PasswordUpdated`     |
| `SuperAdmin/Listeners/NotifySuperAdminsOfRecovery.php`     | `NotifySuperAdminsOfRecovery`       | `SuperAdminRecovered` |

## Livewire Components

| File                                               | Component             | Extends     |
| -------------------------------------------------- | --------------------- | ----------- |
| `Login/Livewire/Login.php`                         | `Login`               | `Component` |
| `Password/Livewire/ForgotPassword.php`             | `ForgotPassword`      | `Component` |
| `Password/Livewire/ResetPassword.php`              | `ResetPassword`       | `Component` |
| `Password/Livewire/ConfirmPassword.php`            | `ConfirmPassword`     | `Component` |
| `Account/Livewire/ActivateAccount.php`             | `ActivateAccount`     | `Component` |
| `AccountRecovery/Livewire/AccountRecovery.php`     | `AccountRecovery`     | `Component` |
| `AccountRecovery/Livewire/RecoveryCode.php`        | `RecoveryCode`        | `Component` |
| `AccountRecovery/Livewire/RecoverySlipManager.php` | `RecoverySlipManager` | `Component` |

## Notifications

| File                                                           | Class                             | Purpose                                 |
| -------------------------------------------------------------- | --------------------------------- | --------------------------------------- |
| `SuperAdmin/Notifications/SuperAdminRecoveredNotification.php` | `SuperAdminRecoveredNotification` | Notifies admins on recovery             |
| `SuperAdmin/Notifications/RecoveryOtpNotification.php`         | `RecoveryOtpNotification`         | Notifies on recovery OTP                |
| `Notifications/CredentialChangedNotification.php`              | `CredentialChangedNotification`   | Email on password/email/username change |

## Livewire Forms

| File                                                     | Form                  |
| -------------------------------------------------------- | --------------------- |
| `Login/Livewire/Forms/LoginForm.php`                     | `LoginForm`           |
| `Password/Livewire/Forms/ConfirmPasswordForm.php`        | `ConfirmPasswordForm` |
| `Password/Livewire/Forms/ForgotPasswordForm.php`         | `ForgotPasswordForm`  |
| `Password/Livewire/Forms/ResetPasswordForm.php`          | `ResetPasswordForm`   |
| `AccountRecovery/Livewire/Forms/AccountRecoveryForm.php` | `AccountRecoveryForm` |

## Middleware

| File                                                  | Middleware               | Purpose                    |
| ----------------------------------------------------- | ------------------------ | -------------------------- |
| `Login/Http/Middleware/AuthThrottleMiddleware.php`    | `AuthThrottleMiddleware` | Rate-limits login attempts |
| `Permissions/Http/Middleware/CheckRoleMiddleware.php` | `CheckRoleMiddleware`    | Route-level role gate      |

## Form Requests

| File                                        | Request       | Purpose                    |
| ------------------------------------------- | ------------- | -------------------------- |
| `Permissions/Http/Requests/RoleRequest.php` | `RoleRequest` | Role assignment validation |

---

## Routes

File: `routes/web/auth.php` Naming pattern: `auth.{resource}.{action}`

## Views

Views are located in `resources/views/auth/`. See [UI/UX](../foundation/ui-ux.md) for the design
system.

## Tests

Tests are located in `tests/{Feature,Unit}/Auth/`. See [Testing](../infrastructure/testing.md) for
the testing conventions.

## Factories

None.

## Console Commands

| Command Signature | Class                 | Description                          |
| ----------------- | --------------------- | ------------------------------------ |
| `admin:create`    | `CreateAdminCommand`  | Creates initial superadmin           |
| `admin:recover`   | `RecoverAdminCommand` | Super admin account recovery via CLI |

## Migrations

| Migration                    | Table           |
| ---------------------------- | --------------- |
| `create_access_tokens_table` | `access_tokens` |
| `create_permission_tables`   | `permissions`   |

---

---

## Architectural Integration

- **Submodules**: `Login`, `Password`, `Account`, `AccessTokens`, `AccountRecovery`, `Permissions`,
  `SuperAdmin`
- **Business Logic**: `app/Auth/`
- **Routing**: `routes/web/auth.php`
- **Views**: `resources/views/auth/`
- **Testing**: `tests/Feature/Auth/`, `tests/Unit/Auth/`
- **Dependencies**: Core, User

_For overview and business context, see [auth.md](auth.md)._
